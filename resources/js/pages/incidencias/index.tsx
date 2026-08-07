import { Head, Link, router } from '@inertiajs/react';
import Heading from '@/components/heading';
import { IncidentStatusBadge } from '@/components/incident-status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { index as incidenciasIndex, show as incidenciasShow } from '@/routes/incidencias';

type AreaSummary = {
    id: number;
    name: string;
    code: string;
};

type StatusOption = {
    value: string;
    label: string;
};

type IncidentRow = {
    id: number;
    created_at: string | null;
    is_urgent: boolean;
    status: string;
    status_label: string;
    message: string;
    guard: string;
    assigned_to: string | null;
    category: string | null;
    checkpoint: string | null;
    round: string | null;
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

export default function IncidenciasIndex({
    area,
    incidents,
    filters,
    status_options,
}: {
    area: AreaSummary;
    incidents: IncidentRow[];
    filters: { status: string | null };
    status_options: StatusOption[];
}) {
    const setStatusFilter = (value: string | null) => {
        router.get(
            incidenciasIndex.url({
                query: value ? { status: value } : {},
            }),
            {},
            { preserveState: true, replace: true },
        );
    };

    return (
        <>
            <Head title="Incidencias" />

            <div className="flex flex-col gap-6 p-4">
                <Heading
                    title="Incidencias"
                    description={`Reportes registrados en ${area.name} (${area.code})`}
                />

                <div className="flex flex-wrap gap-2">
                    <Button
                        type="button"
                        size="sm"
                        variant={filters.status ? 'outline' : 'default'}
                        onClick={() => setStatusFilter(null)}
                    >
                        Todas
                    </Button>
                    {status_options.map((option) => (
                        <Button
                            key={option.value}
                            type="button"
                            size="sm"
                            variant={
                                filters.status === option.value
                                    ? 'default'
                                    : 'outline'
                            }
                            onClick={() => setStatusFilter(option.value)}
                        >
                            {option.label}
                        </Button>
                    ))}
                </div>

                <div className="overflow-hidden rounded-xl border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left">
                            <tr>
                                <th className="px-4 py-3 font-medium">Fecha</th>
                                <th className="px-4 py-3 font-medium">
                                    Estado
                                </th>
                                <th className="px-4 py-3 font-medium">
                                    Categoría
                                </th>
                                <th className="px-4 py-3 font-medium">
                                    Detalle
                                </th>
                                <th className="px-4 py-3 font-medium">
                                    Guardia
                                </th>
                                <th className="px-4 py-3 font-medium">
                                    Asignado
                                </th>
                                <th className="px-4 py-3 font-medium text-right">
                                    Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {incidents.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={7}
                                        className="px-4 py-8 text-center text-muted-foreground"
                                    >
                                        No hay incidencias con este filtro.
                                    </td>
                                </tr>
                            )}
                            {incidents.map((incident) => (
                                <tr
                                    key={incident.id}
                                    className="border-t align-top"
                                >
                                    <td className="px-4 py-3 whitespace-nowrap text-muted-foreground">
                                        {formatDateTime(incident.created_at)}
                                    </td>
                                    <td className="px-4 py-3">
                                        <div className="flex flex-wrap gap-1">
                                            <IncidentStatusBadge
                                                status={incident.status}
                                                label={incident.status_label}
                                            />
                                            {incident.is_urgent && (
                                                <Badge variant="destructive">
                                                    Urgente
                                                </Badge>
                                            )}
                                        </div>
                                    </td>
                                    <td className="px-4 py-3">
                                        {incident.category ?? '—'}
                                    </td>
                                    <td className="max-w-xs px-4 py-3">
                                        <p className="line-clamp-2">
                                            {incident.message}
                                        </p>
                                        {(incident.round ||
                                            incident.checkpoint) && (
                                            <p className="mt-1 text-xs text-muted-foreground">
                                                {[
                                                    incident.round,
                                                    incident.checkpoint,
                                                ]
                                                    .filter(Boolean)
                                                    .join(' · ')}
                                            </p>
                                        )}
                                    </td>
                                    <td className="px-4 py-3">
                                        {incident.guard}
                                    </td>
                                    <td className="px-4 py-3">
                                        {incident.assigned_to ?? '—'}
                                    </td>
                                    <td className="px-4 py-3 text-right">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            asChild
                                        >
                                            <Link
                                                href={incidenciasShow(
                                                    incident.id,
                                                )}
                                            >
                                                Ver
                                            </Link>
                                        </Button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </>
    );
}

IncidenciasIndex.layout = {
    breadcrumbs: [
        {
            title: 'Incidencias',
            href: incidenciasIndex(),
        },
    ],
};
