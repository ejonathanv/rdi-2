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

type IncidentRow = {
    id: number;
    created_at: string | null;
    is_urgent: boolean;
    message: string;
    guard: string;
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
}: {
    area: AreaSummary;
    incidents: IncidentRow[];
}) {
    return (
        <>
            <Head title="Incidencias" />

            <div className="flex flex-col gap-6 p-4">
                <Heading
                    title="Incidencias"
                    description={`Reportes registrados en ${area.name} (${area.code})`}
                />

                <div className="overflow-hidden rounded-xl border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left">
                            <tr>
                                <th className="px-4 py-3 font-medium">Fecha</th>
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
                                    Contexto
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
                                        colSpan={6}
                                        className="px-4 py-8 text-center text-muted-foreground"
                                    >
                                        Aún no hay incidencias en esta área.
                                    </td>
                                </tr>
                            )}
                            {incidents.map((incident) => (
                                <tr key={incident.id} className="border-t">
                                    <td className="px-4 py-3 whitespace-nowrap">
                                        <div className="flex flex-wrap items-center gap-2">
                                            {formatDateTime(
                                                incident.created_at,
                                            )}
                                            {incident.is_urgent && (
                                                <Badge variant="destructive">
                                                    Urgente
                                                </Badge>
                                            )}
                                        </div>
                                    </td>
                                    <td className="px-4 py-3">
                                        {incident.category ?? (
                                            <span className="text-muted-foreground">
                                                Sin categoría
                                            </span>
                                        )}
                                    </td>
                                    <td className="px-4 py-3 max-w-xs">
                                        <p className="line-clamp-2">
                                            {incident.message}
                                        </p>
                                    </td>
                                    <td className="px-4 py-3">
                                        {incident.guard}
                                    </td>
                                    <td className="px-4 py-3 text-muted-foreground">
                                        {incident.round || incident.checkpoint
                                            ? [
                                                  incident.round,
                                                  incident.checkpoint,
                                              ]
                                                  .filter(Boolean)
                                                  .join(' · ')
                                            : 'Independiente'}
                                    </td>
                                    <td className="px-4 py-3 text-right">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            asChild
                                        >
                                            <Link
                                                href={incidenciasShow.url(
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
