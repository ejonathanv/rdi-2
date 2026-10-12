import { router } from '@inertiajs/react';
import { QrScanner } from '@/components/qr-scanner';
import { canHashInBrowser, sha256Hex } from '@/lib/sha256';
import { verifyCheckpoint } from '@/routes/guard/patrols';

export function CheckpointQrScanner({
    patrolId,
    checkpointId,
    expectedTokenHash,
    onClose,
}: {
    patrolId: number;
    checkpointId: number;
    expectedTokenHash: string;
    onClose: () => void;
}) {
    return (
        <QrScanner
            onClose={onClose}
            onDetected={async (token) => {
                if (!canHashInBrowser()) {
                    return null;
                }

                return (await sha256Hex(token)) === expectedTokenHash
                    ? null
                    : 'El QR no corresponde a este punto de revisión.';
            }}
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
