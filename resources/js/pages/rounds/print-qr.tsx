import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Printer } from 'lucide-react';
import QRCode from 'qrcode';
import { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import { edit as roundsEdit } from '@/routes/rounds';

type PrintableCheckpoint = {
    id: number;
    name: string;
    position: number;
    scan_url: string;
};

export default function RoundsPrintQr({
    area,
    round,
    checkpoints,
    tokensRotatedAt,
}: {
    area: { id: number; name: string; code: string };
    round: { id: number; title: string };
    checkpoints: PrintableCheckpoint[];
    tokensRotatedAt: string | null;
}) {
    const [images, setImages] = useState<Record<number, string>>({});

    useEffect(() => {
        let cancelled = false;

        void Promise.all(
            checkpoints.map(
                async (checkpoint) =>
                    [
                        checkpoint.id,
                        await QRCode.toDataURL(checkpoint.scan_url, {
                            width: 512,
                            margin: 2,
                            errorCorrectionLevel: 'M',
                        }),
                    ] as const,
            ),
        ).then((entries) => {
            if (!cancelled) {
                setImages(Object.fromEntries(entries));
            }
        });

        return () => {
            cancelled = true;
        };
    }, [checkpoints]);

    const ready = Object.keys(images).length === checkpoints.length;

    return (
        <>
            <Head title={`QR · ${round.title}`} />

            <div className="mx-auto max-w-5xl p-6 print:max-w-none print:p-0">
                <div className="mb-6 flex flex-wrap items-start justify-between gap-4 print:hidden">
                    <div>
                        <h1 className="text-xl font-semibold">{round.title}</h1>
                        <p className="text-sm text-muted-foreground">
                            {area.name} · {checkpoints.length} puntos activos
                            {tokensRotatedAt &&
                                ` · QR generados el ${new Date(tokensRotatedAt).toLocaleString('es-MX')}`}
                        </p>
                    </div>
                    <div className="flex gap-2">
                        <Button variant="outline" asChild>
                            <Link href={roundsEdit(round)}>
                                <ArrowLeft className="size-4" />
                                Volver
                            </Link>
                        </Button>
                        <Button
                            type="button"
                            disabled={!ready}
                            onClick={() => window.print()}
                        >
                            <Printer className="size-4" />
                            Imprimir
                        </Button>
                    </div>
                </div>

                {checkpoints.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        Este recorrido no tiene puntos activos.
                    </p>
                ) : (
                    <div className="grid grid-cols-2 gap-6 sm:grid-cols-3 print:grid-cols-2 print:gap-4">
                        {checkpoints.map((checkpoint) => (
                            <div
                                key={checkpoint.id}
                                className="flex break-inside-avoid flex-col items-center gap-2 rounded-xl border p-4 text-center"
                            >
                                {images[checkpoint.id] ? (
                                    <img
                                        src={images[checkpoint.id]}
                                        alt={`QR ${checkpoint.name}`}
                                        className="aspect-square w-full max-w-56"
                                    />
                                ) : (
                                    <div className="aspect-square w-full max-w-56 animate-pulse rounded bg-muted" />
                                )}
                                <p className="text-xs text-muted-foreground">
                                    {round.title} · Punto #{checkpoint.position}
                                </p>
                                <p className="font-semibold">
                                    {checkpoint.name}
                                </p>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}
