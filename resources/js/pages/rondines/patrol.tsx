import { Head, Link } from '@inertiajs/react';
import { Download } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { pdf as patrolPdf } from '@/routes/rondines/patrols';
import { index as rondinesIndex } from '@/routes/rondines';
import { show as showRound } from '@/routes/rondines/rounds';

type AreaSummary = {
    id: number;
    name: string;
    code: string;
};

type AnswerRow = {
    question: string;
    option: string;
};

type PhotoRow = {
    id: number;
    url: string;
    path: string;
    position: number;
};

type CheckpointRow = {
    id: number;
    name: string;
    position: number;
    visited: boolean;
    reviewed_at: string | null;
    outcome: string | null;
    outcome_label: string | null;
    answers: AnswerRow[];
    photos: PhotoRow[];
};

type PatrolDetail = {
    id: number;
    status: string;
    status_label: string;
    started_at: string;
    finished_at: string | null;
    duration_seconds: number | null;
    duration_label: string | null;
    guard: { id: number; name: string; email: string };
};

function formatDateTime(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleString('es-MX', {
        dateStyle: 'short',
        timeStyle: 'medium',
    });
}

export default function RondinesPatrol({
    area,
    round,
    patrol,
    checkpoints,
}: {
    area: AreaSummary;
    round: { id: number; title: string };
    patrol: PatrolDetail;
    checkpoints: CheckpointRow[];
    pdf_url: string;
}) {
    return (
        <>
            <Head title={`Rondín · ${patrol.guard.name}`} />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <Heading
                        title={`Rondín de ${patrol.guard.name}`}
                        description={`${area.name} · ${round.title}`}
                    />
                    <Button asChild>
                        <a
                            href={patrolPdf.url({
                                round: round.id,
                                patrol: patrol.id,
                            })}
                        >
                            <Download className="size-4" />
                            Descargar PDF
                        </a>
                    </Button>
                </div>

                <dl className="grid gap-3 text-sm sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <dt className="text-muted-foreground">Estado</dt>
                        <dd className="mt-1">
                            <Badge
                                variant={
                                    patrol.status === 'completed'
                                        ? 'default'
                                        : 'secondary'
                                }
                            >
                                {patrol.status_label}
                            </Badge>
                        </dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">Inicio</dt>
                        <dd className="mt-1 font-medium">
                            {formatDateTime(patrol.started_at)}
                        </dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">Fin</dt>
                        <dd className="mt-1 font-medium">
                            {formatDateTime(patrol.finished_at)}
                        </dd>
                    </div>
                    <div>
                        <dt className="text-muted-foreground">Tiempo total</dt>
                        <dd className="mt-1 font-medium">
                            {patrol.duration_label ?? '—'}
                        </dd>
                    </div>
                </dl>

                <div className="space-y-4">
                    <h2 className="text-lg font-medium">Puntos de revisión</h2>

                    {checkpoints.length === 0 && (
                        <p className="text-sm text-muted-foreground">
                            Este recorrido no tiene puntos activos.
                        </p>
                    )}

                    {checkpoints.map((checkpoint) => (
                        <section
                            key={checkpoint.id}
                            className="space-y-3 rounded-xl border p-4"
                        >
                            <div className="flex flex-wrap items-center gap-2">
                                <h3 className="font-medium">
                                    {checkpoint.position}. {checkpoint.name}
                                </h3>
                                {checkpoint.visited ? (
                                    <Badge variant="outline">
                                        {checkpoint.outcome_label}
                                    </Badge>
                                ) : (
                                    <Badge variant="secondary">Pendiente</Badge>
                                )}
                            </div>

                            {checkpoint.visited && (
                                <>
                                    <p className="text-sm text-muted-foreground">
                                        Revisado:{' '}
                                        {formatDateTime(checkpoint.reviewed_at)}
                                    </p>

                                    {checkpoint.answers.length > 0 && (
                                        <ul className="space-y-2 text-sm">
                                            {checkpoint.answers.map(
                                                (answer, index) => (
                                                    <li key={index}>
                                                        <span className="font-medium">
                                                            {answer.question}
                                                        </span>
                                                        <span className="text-muted-foreground">
                                                            {' '}
                                                            → {answer.option}
                                                        </span>
                                                    </li>
                                                ),
                                            )}
                                        </ul>
                                    )}

                                    {checkpoint.outcome === 'all_clear' &&
                                        checkpoint.answers.length === 0 && (
                                            <p className="text-sm">
                                                Área sin novedad.
                                            </p>
                                        )}

                                    {checkpoint.photos.length > 0 && (
                                        <div className="grid grid-cols-3 gap-2 sm:grid-cols-4">
                                            {checkpoint.photos.map((photo) => (
                                                <a
                                                    key={photo.id}
                                                    href={photo.url}
                                                    target="_blank"
                                                    rel="noreferrer"
                                                    className="block overflow-hidden rounded-lg border"
                                                >
                                                    <img
                                                        src={photo.url}
                                                        alt={`Evidencia ${photo.position}`}
                                                        className="aspect-square w-full object-cover"
                                                    />
                                                </a>
                                            ))}
                                        </div>
                                    )}
                                </>
                            )}
                        </section>
                    ))}
                </div>

                <Button variant="outline" asChild className="w-fit">
                    <Link href={showRound.url(round.id)}>
                        Volver a rondines del recorrido
                    </Link>
                </Button>
            </div>
        </>
    );
}

RondinesPatrol.layout = {
    breadcrumbs: [
        {
            title: 'Rondines',
            href: rondinesIndex(),
        },
        {
            title: 'Detalle',
            href: '#',
        },
    ],
};
