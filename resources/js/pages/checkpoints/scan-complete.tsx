import { Head, Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { show as showPatrol } from '@/routes/guard/patrols';
import { home as guardHome } from '@/routes/guard';

export default function CheckpointScanComplete({
    checkpoint,
    round,
    patrol,
}: {
    checkpoint: { name: string; token: string };
    round: { title: string };
    patrol: {
        id: number;
        status: string;
        finished_at: string | null;
        duration_seconds: number | null;
    } | null;
}) {
    const finished = patrol?.status === 'completed';

    return (
        <>
            <Head title="Punto revisado" />

            <div className="mx-auto flex w-full max-w-lg flex-col gap-6 p-4">
                <Heading
                    title="Punto revisado"
                    description={`${round.title} · ${checkpoint.name}`}
                />

                <p className="text-sm text-muted-foreground">
                    {finished
                        ? 'Completaste todos los puntos del recorrido.'
                        : 'El punto quedó registrado. Continúa con el siguiente.'}
                </p>

                {finished && patrol?.duration_seconds !== null && (
                    <p className="text-sm text-muted-foreground">
                        Tiempo total del recorrido:{' '}
                        {formatDuration(patrol.duration_seconds ?? 0)}
                    </p>
                )}

                <div className="flex flex-col gap-2">
                    {patrol ? (
                        <Button asChild className="w-full">
                            <Link href={showPatrol(patrol.id)}>
                                {finished
                                    ? 'Ver resumen del recorrido'
                                    : 'Revisado, pasar al siguiente punto'}
                            </Link>
                        </Button>
                    ) : (
                        <Button asChild className="w-full">
                            <Link href={guardHome()}>Ir al panel</Link>
                        </Button>
                    )}
                </div>
            </div>
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

CheckpointScanComplete.layout = {
    breadcrumbs: [
        {
            title: 'Punto revisado',
            href: '#',
        },
    ],
};
