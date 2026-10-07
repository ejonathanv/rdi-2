import { AlertCircle, Clock } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { syncNow } from '../store';
import { retryFailedItems } from '../sync';
import type { GuardData } from '../use-guard-data';

export function OutboxScreen({ data }: { data: GuardData }) {
    const [syncing, setSyncing] = useState(false);
    const failed = data.outbox.filter((item) => item.status === 'failed');

    const retry = async () => {
        setSyncing(true);

        try {
            await retryFailedItems();
            await syncNow();
        } finally {
            setSyncing(false);
        }
    };

    return (
        <div className="flex flex-col gap-6">
            <Heading
                title="Pendientes por enviar"
                description={
                    data.lastSyncAt
                        ? `Última sincronización: ${new Date(data.lastSyncAt).toLocaleString('es-MX')}`
                        : 'Los registros se envían automáticamente cuando hay red.'
                }
            />

            {data.outbox.length === 0 ? (
                <p className="rounded-xl border p-4 text-sm text-muted-foreground">
                    Todo está sincronizado.
                </p>
            ) : (
                <ul className="flex flex-col gap-2">
                    {data.outbox.map((item) => (
                        <li
                            key={item.id}
                            className="flex gap-3 rounded-xl border p-3"
                        >
                            {item.status === 'failed' ? (
                                <AlertCircle className="size-5 shrink-0 text-destructive" />
                            ) : (
                                <Clock className="size-5 shrink-0 text-muted-foreground" />
                            )}
                            <div className="min-w-0 flex-1">
                                <p className="truncate text-sm font-medium">
                                    {item.label}
                                </p>
                                <p className="text-xs text-muted-foreground">
                                    {new Date(item.created_at).toLocaleString(
                                        'es-MX',
                                    )}
                                    {item.photos.length > 0 &&
                                        ` · ${item.photos.length} foto(s)`}
                                </p>
                                {item.last_error && (
                                    <p
                                        className={
                                            item.status === 'failed'
                                                ? 'text-xs text-destructive'
                                                : 'text-xs text-muted-foreground'
                                        }
                                    >
                                        {item.last_error}
                                    </p>
                                )}
                            </div>
                        </li>
                    ))}
                </ul>
            )}

            {data.outbox.length > 0 && (
                <Button
                    disabled={!data.online || syncing}
                    onClick={() => void retry()}
                >
                    {syncing && <Spinner />}
                    {failed.length > 0
                        ? 'Reintentar todo'
                        : 'Sincronizar ahora'}
                </Button>
            )}

            {!data.online && data.outbox.length > 0 && (
                <p className="text-center text-sm text-muted-foreground">
                    Sin red. Se enviará automáticamente al reconectarte.
                </p>
            )}
        </div>
    );
}
