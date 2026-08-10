import { Bell, BellOff, BellRing, LoaderCircle } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import type { WebPushStatus } from '@/hooks/use-web-push';
import { cn } from '@/lib/utils';

type PushStatusIndicatorProps = {
    status: WebPushStatus;
    onEnable: () => void;
};

export function PushStatusIndicator({
    status,
    onEnable,
}: PushStatusIndicatorProps) {
    if (status === 'unsupported') {
        return null;
    }

    if (status === 'active') {
        return (
            <Tooltip>
                <TooltipTrigger asChild>
                    <span
                        className="inline-flex items-center gap-1.5 rounded-md px-2 py-1 text-xs text-emerald-700 dark:text-emerald-400"
                        aria-label="Web Push activo"
                    >
                        <span className="size-1.5 rounded-full bg-emerald-500" />
                        <BellRing className="size-3.5" />
                        <span className="hidden sm:inline">Push activo</span>
                    </span>
                </TooltipTrigger>
                <TooltipContent>Este navegador recibirá alertas push</TooltipContent>
            </Tooltip>
        );
    }

    if (status === 'loading') {
        return (
            <span className="inline-flex items-center gap-1.5 px-2 py-1 text-xs text-muted-foreground">
                <LoaderCircle className="size-3.5 animate-spin" />
                <span className="hidden sm:inline">Push…</span>
            </span>
        );
    }

    if (status === 'denied') {
        return (
            <Tooltip>
                <TooltipTrigger asChild>
                    <span
                        className="inline-flex items-center gap-1.5 rounded-md px-2 py-1 text-xs text-amber-700 dark:text-amber-400"
                        aria-label="Notificaciones bloqueadas"
                    >
                        <BellOff className="size-3.5" />
                        <span className="hidden sm:inline">Push bloqueado</span>
                    </span>
                </TooltipTrigger>
                <TooltipContent>
                    Activa las notificaciones en los ajustes del sitio del navegador
                </TooltipContent>
            </Tooltip>
        );
    }

    return (
        <Tooltip>
            <TooltipTrigger asChild>
                <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    className={cn(
                        'h-8 gap-1.5 px-2 text-xs',
                        status === 'error' && 'text-destructive',
                    )}
                    onClick={onEnable}
                >
                    <Bell className="size-3.5" />
                    <span className="hidden sm:inline">
                        {status === 'error' ? 'Reintentar push' : 'Activar push'}
                    </span>
                </Button>
            </TooltipTrigger>
            <TooltipContent>
                {status === 'error'
                    ? 'No se pudo registrar el push. Intenta de nuevo.'
                    : 'Permitir notificaciones del sistema en este navegador'}
            </TooltipContent>
        </Tooltip>
    );
}
