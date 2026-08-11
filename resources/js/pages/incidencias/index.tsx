import { Head, Link, router } from '@inertiajs/react';
import Heading from '@/components/heading';
import { IncidentStatusBadge } from '@/components/incident-status-badge';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
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

type CategoryOption = {
    value: number;
    label: string;
};

type Filters = {
    status: string | null;
    from: string | null;
    to: string | null;
    category_id: number | null;
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

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type PaginatedIncidents = {
    data: IncidentRow[];
    links: PaginationLink[];
    from: number | null;
    to: number | null;
    total: number;
    current_page: number;
    last_page: number;
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

function buildQuery(filters: Filters): Record<string, string> {
    const query: Record<string, string> = {};

    if (filters.status) {
        query.status = filters.status;
    }

    if (filters.from) {
        query.from = filters.from;
    }

    if (filters.to) {
        query.to = filters.to;
    }

    if (filters.category_id) {
        query.category_id = String(filters.category_id);
    }

    return query;
}

export default function IncidenciasIndex({
    area,
    incidents,
    filters,
    status_options,
    category_options,
}: {
    area: AreaSummary;
    incidents: PaginatedIncidents;
    filters: Filters;
    status_options: StatusOption[];
    category_options: CategoryOption[];
}) {
    const applyFilters = (overrides: Partial<Filters>) => {
        router.get(
            incidenciasIndex.url({
                query: buildQuery({ ...filters, ...overrides }),
            }),
            {},
            { preserveState: true, replace: true },
        );
    };

    const clearExtraFilters = () => {
        applyFilters({ from: null, to: null, category_id: null });
    };

    const hasExtraFilters = Boolean(
        filters.from || filters.to || filters.category_id,
    );

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
                        onClick={() => applyFilters({ status: null })}
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
                            onClick={() =>
                                applyFilters({ status: option.value })
                            }
                        >
                            {option.label}
                        </Button>
                    ))}
                </div>

                <div className="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-end">
                    <div className="grid gap-2">
                        <Label htmlFor="filter-from">Desde</Label>
                        <Input
                            id="filter-from"
                            type="date"
                            value={filters.from ?? ''}
                            onChange={(event) =>
                                applyFilters({
                                    from: event.target.value || null,
                                })
                            }
                            className="w-full sm:w-auto"
                        />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="filter-to">Hasta</Label>
                        <Input
                            id="filter-to"
                            type="date"
                            value={filters.to ?? ''}
                            onChange={(event) =>
                                applyFilters({
                                    to: event.target.value || null,
                                })
                            }
                            className="w-full sm:w-auto"
                        />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="filter-category">Categoría</Label>
                        <Select
                            value={
                                filters.category_id
                                    ? String(filters.category_id)
                                    : 'all'
                            }
                            onValueChange={(value) =>
                                applyFilters({
                                    category_id:
                                        value === 'all' ? null : Number(value),
                                })
                            }
                        >
                            <SelectTrigger
                                id="filter-category"
                                className="w-full sm:w-56"
                            >
                                <SelectValue placeholder="Todas las categorías" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">
                                    Todas las categorías
                                </SelectItem>
                                {category_options.map((option) => (
                                    <SelectItem
                                        key={option.value}
                                        value={String(option.value)}
                                    >
                                        {option.label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                    {hasExtraFilters && (
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            onClick={clearExtraFilters}
                        >
                            Limpiar fechas y categoría
                        </Button>
                    )}
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
                            {incidents.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={7}
                                        className="px-4 py-8 text-center text-muted-foreground"
                                    >
                                        No hay incidencias con este filtro.
                                    </td>
                                </tr>
                            )}
                            {incidents.data.map((incident) => (
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

                {incidents.last_page > 1 && (
                    <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <p className="text-sm text-muted-foreground">
                            Mostrando {incidents.from ?? 0}–{incidents.to ?? 0}{' '}
                            de {incidents.total}
                        </p>
                        <div className="flex flex-wrap gap-1">
                            {incidents.links.map((link, index) => {
                                const label = link.label
                                    .replace('&laquo;', '«')
                                    .replace('&raquo;', '»');

                                if (!link.url) {
                                    return (
                                        <Button
                                            key={`${label}-${index}`}
                                            type="button"
                                            size="sm"
                                            variant="outline"
                                            disabled
                                        >
                                            {label}
                                        </Button>
                                    );
                                }

                                return (
                                    <Button
                                        key={`${label}-${index}`}
                                        size="sm"
                                        variant={
                                            link.active ? 'default' : 'outline'
                                        }
                                        asChild
                                    >
                                        <Link
                                            href={link.url}
                                            preserveState
                                            preserveScroll
                                        >
                                            {label}
                                        </Link>
                                    </Button>
                                );
                            })}
                        </div>
                    </div>
                )}
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
