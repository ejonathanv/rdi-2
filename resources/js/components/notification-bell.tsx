import { router, useHttp, usePage } from '@inertiajs/react';
import { Bell } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { cn } from '@/lib/utils';
import { index as notificationsIndex, read, readAll } from '@/routes/notifications';

const POLL_INTERVAL_MS = 20_000;

type AppNotification = {
    id: string;
    type: string;
    data: {
        title?: string;
        body?: string;
        url?: string;
        type?: string;
    };
    read_at: string | null;
    created_at: string | null;
};

type NotificationsResponse = {
    notifications: AppNotification[];
    unread_count: number;
};

export function NotificationBell() {
    const { submit } = useHttp();
    const page = usePage();
    const unreadNotificationsCount = page.props.unreadNotificationsCount ?? 0;
    const [open, setOpen] = useState(false);
    const [loading, setLoading] = useState(false);
    const [items, setItems] = useState<AppNotification[]>([]);
    const [unreadCount, setUnreadCount] = useState(unreadNotificationsCount);

    useEffect(() => {
        setUnreadCount(unreadNotificationsCount);
    }, [unreadNotificationsCount]);

    const loadNotifications = useCallback(
        async (withSpinner = true): Promise<void> => {
            if (withSpinner) {
                setLoading(true);
            }

            try {
                const response = (await submit(notificationsIndex())) as NotificationsResponse;
                setItems(response.notifications);
                setUnreadCount(response.unread_count);
            } catch (error) {
                console.error('No se pudieron cargar las notificaciones', error);
            } finally {
                if (withSpinner) {
                    setLoading(false);
                }
            }
        },
        [submit],
    );

    useEffect(() => {
        if (open) {
            void loadNotifications();
        }
    }, [open, loadNotifications]);

    useEffect(() => {
        const tick = (): void => {
            if (document.visibilityState !== 'visible' || open) {
                return;
            }

            void loadNotifications(false);
        };

        const id = window.setInterval(tick, POLL_INTERVAL_MS);

        return () => window.clearInterval(id);
    }, [loadNotifications, open]);

    const markRead = async (notification: AppNotification): Promise<void> => {
        if (!notification.read_at) {
            try {
                const response = (await submit(read(notification.id))) as {
                    unread_count: number;
                };
                setUnreadCount(response.unread_count);
                setItems((current) =>
                    current.map((item) =>
                        item.id === notification.id
                            ? { ...item, read_at: new Date().toISOString() }
                            : item,
                    ),
                );
            } catch (error) {
                console.error('No se pudo marcar la notificación', error);
            }
        }

        if (notification.data.url) {
            setOpen(false);
            router.visit(notification.data.url);
        }
    };

    const markAll = async (): Promise<void> => {
        try {
            await submit(readAll());
            setUnreadCount(0);
            setItems((current) =>
                current.map((item) => ({
                    ...item,
                    read_at: item.read_at ?? new Date().toISOString(),
                })),
            );
        } catch (error) {
            console.error('No se pudieron marcar todas', error);
        }
    };

    return (
        <DropdownMenu open={open} onOpenChange={setOpen}>
            <DropdownMenuTrigger asChild>
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    className="relative"
                    aria-label="Notificaciones"
                >
                    <Bell className="size-4" />
                    {unreadCount > 0 ? (
                        <Badge className="absolute -top-1 -right-1 h-5 min-w-5 justify-center rounded-full px-1 text-[10px]">
                            {unreadCount > 99 ? '99+' : unreadCount}
                        </Badge>
                    ) : null}
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" className="w-80 p-0">
                <div className="flex items-center justify-between px-3 py-2">
                    <DropdownMenuLabel className="p-0">Notificaciones</DropdownMenuLabel>
                    {unreadCount > 0 ? (
                        <button
                            type="button"
                            className="text-xs text-muted-foreground hover:text-foreground"
                            onClick={() => void markAll()}
                        >
                            Marcar todas
                        </button>
                    ) : null}
                </div>
                <DropdownMenuSeparator />
                <div className="max-h-80 overflow-y-auto py-1">
                    {loading ? (
                        <p className="px-3 py-6 text-center text-sm text-muted-foreground">
                            Cargando…
                        </p>
                    ) : items.length === 0 ? (
                        <p className="px-3 py-6 text-center text-sm text-muted-foreground">
                            Sin notificaciones
                        </p>
                    ) : (
                        items.map((notification) => (
                            <DropdownMenuItem
                                key={notification.id}
                                className={cn(
                                    'flex cursor-pointer flex-col items-start gap-0.5 rounded-none px-3 py-2',
                                    !notification.read_at && 'bg-muted/50',
                                )}
                                onSelect={(event) => {
                                    event.preventDefault();
                                    void markRead(notification);
                                }}
                            >
                                <span className="text-sm font-medium">
                                    {notification.data.title ?? 'Alerta'}
                                </span>
                                <span className="line-clamp-2 text-xs text-muted-foreground">
                                    {notification.data.body}
                                </span>
                            </DropdownMenuItem>
                        ))
                    )}
                </div>
            </DropdownMenuContent>
        </DropdownMenu>
    );
}
