import { router } from '@inertiajs/react';
import { QrScanner } from '@/components/qr-scanner';
import { verifyCheckpoint } from '@/routes/guard/patrols';

export function CheckpointQrScanner({
    patrolId,
    checkpointId,
    expectedToken,
    onClose,
}: {
    patrolId: number;
    checkpointId: number;
    expectedToken: string;
    onClose: () => void;
}) {
    return (
        <QrScanner
            onClose={onClose}
            onDetected={(token) =>
                token === expectedToken
                    ? null
                    : 'El QR no corresponde a este punto de revisión.'
            }
            onAccepted={(_, raw) => {
                router.post(
                    verifyCheckpoint.url({
                        patrol: patrolId,
                        checkpoint: checkpointId,
                    }),
                    { token: raw },
                );
            }}
        />
    );
}
