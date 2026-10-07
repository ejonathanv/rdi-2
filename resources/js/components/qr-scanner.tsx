import { Html5Qrcode } from 'html5-qrcode';
import { useEffect, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';

/**
 * Full-screen camera QR reader. `onDetected` returns an error message to keep
 * scanning, or null to accept the token and stop the camera.
 */
export function QrScanner({
    title = 'Escanear QR del punto',
    hint = 'Apunta la cámara al código QR impreso en este punto.',
    onDetected,
    onAccepted,
    onClose,
}: {
    title?: string;
    hint?: string;
    onDetected: (token: string) => string | null;
    onAccepted: (token: string, raw: string) => void;
    onClose: () => void;
}) {
    const [error, setError] = useState<string | null>(null);
    const [scanning, setScanning] = useState(true);
    const scannerRef = useRef<Html5Qrcode | null>(null);
    const handledRef = useRef(false);
    const detectRef = useRef(onDetected);
    const acceptRef = useRef(onAccepted);
    const elementId = 'qr-reader';

    useEffect(() => {
        detectRef.current = onDetected;
        acceptRef.current = onAccepted;
    });

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
                    const detectionError = detectRef.current(token);

                    if (detectionError) {
                        setError(detectionError);

                        return;
                    }

                    handledRef.current = true;
                    setScanning(false);

                    void scanner
                        .stop()
                        .catch(() => undefined)
                        .finally(() => acceptRef.current(token, decoded));
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
    }, []);

    return (
        <div className="fixed inset-0 z-50 flex flex-col bg-background/95 p-4">
            <div className="mx-auto flex w-full max-w-lg flex-1 flex-col gap-4">
                <div className="flex items-center justify-between gap-2">
                    <p className="font-medium">{title}</p>
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
                        {hint}
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

export function extractToken(value: string): string {
    const trimmed = value.trim();
    const match = trimmed.match(/\/scan\/([^/?#]+)/);

    return match ? decodeURIComponent(match[1]) : trimmed;
}
