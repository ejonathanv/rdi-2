import { router } from '@inertiajs/react';
import { Html5Qrcode } from 'html5-qrcode';
import { useEffect, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
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
    const [error, setError] = useState<string | null>(null);
    const [scanning, setScanning] = useState(true);
    const scannerRef = useRef<Html5Qrcode | null>(null);
    const handledRef = useRef(false);
    const elementId = 'checkpoint-qr-reader';

    useEffect(() => {
        const scanner = new Html5Qrcode(elementId);
        scannerRef.current = scanner;
        handledRef.current = false;

        scanner
            .start(
                { facingMode: 'environment' },
                { fps: 8, qrbox: { width: 250, height: 250 } },
                (decoded) => {
                    if (handledRef.current) {
                        return;
                    }

                    const token = extractToken(decoded);

                    if (token !== expectedToken) {
                        setError(
                            'El QR no corresponde a este punto de revisión.',
                        );
                        return;
                    }

                    handledRef.current = true;
                    setScanning(false);

                    void scanner
                        .stop()
                        .catch(() => undefined)
                        .finally(() => {
                            router.post(
                                verifyCheckpoint.url({
                                    patrol: patrolId,
                                    checkpoint: checkpointId,
                                }),
                                { token: decoded },
                            );
                        });
                },
                () => undefined,
            )
            .catch(() => {
                setError(
                    'No se pudo acceder a la cámara. Revisa los permisos del navegador.',
                );
                setScanning(false);
            });

        return () => {
            const active = scannerRef.current;
            if (active?.isScanning) {
                void active.stop().catch(() => undefined);
            }
        };
    }, [checkpointId, expectedToken, patrolId]);

    return (
        <div className="fixed inset-0 z-50 flex flex-col bg-background/95 p-4">
            <div className="mx-auto flex w-full max-w-lg flex-1 flex-col gap-4">
                <div className="flex items-center justify-between gap-2">
                    <p className="font-medium">Escanear QR del punto</p>
                    <Button type="button" variant="outline" onClick={onClose}>
                        Cancelar
                    </Button>
                </div>

                <div
                    id={elementId}
                    className="overflow-hidden rounded-xl border bg-black"
                />

                {scanning && !error && (
                    <p className="text-center text-sm text-muted-foreground">
                        Apunta la cámara al código QR impreso en este punto.
                    </p>
                )}

                {error && (
                    <p className="text-center text-sm text-destructive">
                        {error}
                    </p>
                )}
            </div>
        </div>
    );
}

function extractToken(value: string): string {
    const trimmed = value.trim();
    const match = trimmed.match(/\/scan\/([^/?#]+)/);

    return match ? decodeURIComponent(match[1]) : trimmed;
}
