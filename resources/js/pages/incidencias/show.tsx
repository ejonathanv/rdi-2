import { Head, Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { index as incidenciasIndex, show as incidenciasShow } from '@/routes/incidencias';

type AreaSummary = {
    id: number;
    name: string;
    code: string;
};

type IncidentDetail = {
    id: number;
    message_raw: string;
    message_cleaned: string | null;
    is_urgent: boolean;
    categorized_at: string | null;
    created_at: string | null;
    guard: { id: number; name: string; email: string };
    category: { id: number; name: string; code: string } | null;
    checkpoint: { id: number; name: string } | null;
    round: { id: number; title: string } | null;
    photos: { id: number; url: string; position: number }[];
};

function formatDateTime(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleString('es-MX', {
        dateStyle: 'short',
        timeStyle: 'short',
    });
}

export default function IncidenciasShow({
    area,
    incident,
}: {
    area: AreaSummary;
    incident: IncidentDetail;
}) {
    return (
        <>
            <Head title={`Incidencia #${incident.id}`} />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <Heading
                        title={`Incidencia #${incident.id}`}
                        description={`${area.name} (${area.code}) · ${formatDateTime(incident.created_at)}`}
                    />
                    <Button variant="outline" asChild>
                        <Link href={incidenciasIndex()}>Volver</Link>
                    </Button>
                </div>

                <div className="grid gap-4 lg:grid-cols-2">
                    <div className="space-y-4 rounded-xl border p-4">
                        <div className="flex flex-wrap items-center gap-2">
                            {incident.is_urgent && (
                                <Badge variant="destructive">Urgente</Badge>
                            )}
                            {incident.category ? (
                                <Badge>{incident.category.name}</Badge>
                            ) : (
                                <Badge variant="secondary">Sin categoría</Badge>
                            )}
                        </div>

                        <dl className="grid gap-3 text-sm">
                            <div>
                                <dt className="text-muted-foreground">
                                    Guardia
                                </dt>
                                <dd className="font-medium">
                                    {incident.guard.name}
                                    <span className="text-muted-foreground">
                                        {' '}
                                        · {incident.guard.email}
                                    </span>
                                </dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">
                                    Contexto
                                </dt>
                                <dd className="font-medium">
                                    {incident.round || incident.checkpoint
                                        ? [
                                              incident.round?.title,
                                              incident.checkpoint?.name,
                                          ]
                                              .filter(Boolean)
                                              .join(' · ')
                                        : 'Reporte independiente'}
                                </dd>
                            </div>
                            {incident.categorized_at && (
                                <div>
                                    <dt className="text-muted-foreground">
                                        Categorizada
                                    </dt>
                                    <dd className="font-medium">
                                        {formatDateTime(
                                            incident.categorized_at,
                                        )}
                                    </dd>
                                </div>
                            )}
                        </dl>
                    </div>

                    <div className="space-y-4 rounded-xl border p-4">
                        <div>
                            <h2 className="font-medium">Mensaje limpio</h2>
                            <p className="mt-2 text-sm whitespace-pre-wrap">
                                {incident.message_cleaned ??
                                    'Pendiente de procesamiento'}
                            </p>
                        </div>
                        <div>
                            <h2 className="font-medium">Mensaje original</h2>
                            <p className="mt-2 text-sm text-muted-foreground whitespace-pre-wrap">
                                {incident.message_raw}
                            </p>
                        </div>
                    </div>
                </div>

                {incident.photos.length > 0 && (
                    <div className="space-y-3">
                        <h2 className="font-medium">Evidencia fotográfica</h2>
                        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            {incident.photos.map((photo) => (
                                <a
                                    key={photo.id}
                                    href={photo.url}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="overflow-hidden rounded-xl border"
                                >
                                    <img
                                        src={photo.url}
                                        alt={`Evidencia ${photo.position}`}
                                        className="aspect-video w-full object-cover"
                                    />
                                </a>
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </>
    );
}

IncidenciasShow.layout = {
    breadcrumbs: [
        {
            title: 'Incidencias',
            href: incidenciasIndex(),
        },
        {
            title: 'Detalle',
            href: incidenciasShow(1),
        },
    ],
};
