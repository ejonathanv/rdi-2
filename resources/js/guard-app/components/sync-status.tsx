import { CloudOff, CloudUpload, RefreshCw, Wifi } from 'lucide-react';
import { useState } from 'react';
import { cn } from '@/lib/utils';
import { syncNow } from '../store';
import type { OutboxItem } from '../types';

export function SyncStatus({
    online,
    outbox,
    onOpenOutbox,
}: {
    online: boolean;
    outbox: OutboxItem[];
    onOpenOutbox: () => void;
}) {
    const [syncing, setSyncing] = useState(false);
    const failed = outbox.filter((item) => item.status === 'failed').length;
    const pending = outbox.length - failed;

    const label = !online
        ? pending > 0
            ? `Sin red · ${pending} pendiente${pending === 1 ? '' : 's'}`
            : 'Sin red'
        : failed > 0
          ? `${failed} con error`
          : pending > 0
            ? `${pending} por enviar`
            : 'En línea';

    const Icon = !online ? CloudOff : pending > 0 ? CloudUpload : Wifi;

    return (
        <div className="flex items-center gap-1">
            <button
                type="button"
                onClick={onOpenOutbox}
                className={cn(
                    'inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-medium',
                    !online && 'bg-amber-500/20 text-amber-100',
                    online && failed > 0 && 'bg-red-500/25 text-red-100',
                    online && failed === 0 && 'bg-white/15 text-white',
                )}
            >
                <Icon className="size-3.5" />
                {label}
            </button>
            {online && pending > 0 && (
                <button
                    type="button"
                    aria-label="Sincronizar ahora"
                    className="rounded-full p-1.5 text-white/80 hover:bg-white/10"
                    disabled={syncing}
                    onClick={() => {
                        setSyncing(true);
                        void syncNow().finally(() => setSyncing(false));
                    }}
                >
                    <RefreshCw
                        className={cn('size-3.5', syncing && 'animate-spin')}
                    />
                </button>
            )}
        </div>
    );
}
