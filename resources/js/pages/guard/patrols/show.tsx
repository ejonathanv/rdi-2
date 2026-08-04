import { Head, Link } from '@inertiajs/react';
import { Camera, CheckCircle2 } from 'lucide-react';
import { useState } from 'react';
import { CheckpointQrScanner } from '@/components/checkpoint-qr-scanner';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { home as guardHome } from '@/routes/guard';
import { index as guardRoundsIndex } from '@/routes/guard/rounds';

type CheckpointRow = {
    id: number;
    name: string;
    instructions: string | null;
    position: number;
    token: string;
    questions_count: number;
    reviewed: boolean;
    reviewed_at: string | null;
    outcome: string | null;
};

type PatrolData = {
    id: number;
    status: string;
    started_at: string;
    finished_at: string | null;
    duration_seconds: number | null;
    round: {
        id: number;
        title: string;
        instructions: string | null;
        area: { id: number; name: string };
    };
    checkpoints: CheckpointRow[];
};

export default function GuardPatrolShow({ patrol }: { patrol: PatrolData }) {
    const [scanningCheckpoint, setScanningCheckpoint] =
        useState<CheckpointRow | null>(null);

    const completed =
        patrol.status === 'completed' ||
        patrol.checkpoints.every((checkpoint) => checkpoint.reviewed);

    return (
        <>
            <Head title={patrol.round.title} />

            <div className="mx-auto flex w-full max-w-lg flex-col gap-6 p-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <Heading
                        title={patrol.round.title}
                        description={`${patrol.round.area.name} · ${completed ? 'Recorrido finalizado' : 'Recorrido en curso'}`}
                    />
                    <Button variant="outline" asChild>
                        <Link href={guardRoundsIndex()}>
                            {completed ? 'Nuevo recorrido' : 'Cambiar'}
                        </Link>
                    </Button>
                </div>

                <p className="text-sm text-muted-foreground">
                    Inicio:{' '}
                    {new Date(patrol.started_at).toLocaleString('es-MX')}
                    {patrol.finished_at && (
                        <>
                            {' '}
                            · Fin:{' '}
                            {new Date(patrol.finished_at).toLocaleString(
                                'es-MX',
                            )}
                        </>
                    )}
                    {patrol.duration_seconds !== null && (
                        <> · Duración: {formatDuration(patrol.duration_seconds)}</>
                    )}
                </p>

                {patrol.round.instructions && (
                    <p className="text-sm text-muted-foreground">
                        {patrol.round.instructions}
                    </p>
                )}

                {completed && (
                    <div className="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-100">
                        Completaste todos los puntos de este recorrido.
                    </div>
                )}

                <div className="space-y-3">
                    <Heading
                        variant="small"
                        title="Puntos de revisión"
                        description="Escanea el QR de cada punto para validar tu presencia."
                    />

                    {patrol.checkpoints.map((checkpoint) => (
                        <div
                            key={checkpoint.id}
                            className={
                                checkpoint.reviewed
                                    ? 'space-y-2 rounded-xl border border-emerald-200/80 bg-emerald-50/60 p-4 dark:border-emerald-900 dark:bg-emerald-950/30'
                                    : 'space-y-2 rounded-xl border p-4'
                            }
                        >
                            <div className="flex items-start justify-between gap-3">
                                <div className="space-y-1">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <Badge variant="secondary">
                                            #{checkpoint.position}
                                        </Badge>
                                        <p className="font-medium">
                                            {checkpoint.name}
                                        </p>
                                        {checkpoint.reviewed && (
                                            <CheckCircle2 className="size-4 text-emerald-600" />
                                        )}
                                    </div>
                                    {checkpoint.instructions && (
                                        <p className="text-sm text-muted-foreground">
                                            {checkpoint.instructions}
                                        </p>
                                    )}
                                    {checkpoint.reviewed_at && (
                                        <p className="text-xs text-emerald-800 dark:text-emerald-200">
                                            Revisado:{' '}
                                            {new Date(
                                                checkpoint.reviewed_at,
                                            ).toLocaleString('es-MX')}
                                        </p>
                                    )}
                                </div>

                                {!checkpoint.reviewed &&
                                    patrol.status === 'in_progress' && (
                                        <Button
                                            type="button"
                                            size="icon"
                                            variant="secondary"
                                            onClick={() =>
                                                setScanningCheckpoint(
                                                    checkpoint,
                                                )
                                            }
                                        >
                                            <Camera className="size-5" />
                                            <span className="sr-only">
                                                Escanear QR
                                            </span>
                                        </Button>
                                    )}
                            </div>
                        </div>
                    ))}
                </div>

                <Button variant="outline" asChild>
                    <Link href={guardHome()}>Volver al panel</Link>
                </Button>
            </div>

            {scanningCheckpoint && (
                <CheckpointQrScanner
                    patrolId={patrol.id}
                    checkpointId={scanningCheckpoint.id}
                    expectedToken={scanningCheckpoint.token}
                    onClose={() => setScanningCheckpoint(null)}
                />
            )}
        </>
    );
}

function formatDuration(seconds: number): string {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const rest = seconds % 60;

    if (hours > 0) {
        return `${hours}h ${minutes}m`;
    }

    if (minutes > 0) {
        return `${minutes}m ${rest}s`;
    }

    return `${rest}s`;
}

GuardPatrolShow.layout = {
    breadcrumbs: [
        {
            title: 'Panel',
            href: guardHome(),
        },
        {
            title: 'Recorrido',
            href: '#',
        },
    ],
};
