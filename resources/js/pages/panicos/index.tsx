import { Head, Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { index as panicosIndex } from '@/routes/panicos';
import { show as showPatrol } from '@/routes/rondines/patrols';

type AreaSummary = {
    id: number;
    name: string;
    code: string;
};

type AlertRow = {
    id: number;
    created_at: string | null;
    guard: string;
    round: { id: number; title: string } | null;
    patrol_id: number | null;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type PaginatedAlerts = {
    data: AlertRow[];
    links: PaginationLink[];
    from: number | null;
    to: number | null;
    total: number;
    current_page: number;
    last_page: number;
};

type Filters = {
    from: string | null;
    to: string | null;
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

function periodLabel(filters: Filters): string {
    if (filters.from && filters.to && filters.from === filters.to) {
        return filters.from;
    }

    if (filters.from && filters.to) {
        return `${filters.from} a ${filters.to}`;
    }

    return filters.from ?? filters.to ?? '';
}

export default function PanicosIndex({
    area,
    alerts,
    filters,
}: {
    area: AreaSummary;
    alerts: PaginatedAlerts;
    filters: Filters;
}) {
    const hasDateFilter = Boolean(filters.from || filters.to);

    return (
        <>
            <Head title="Pánicos" />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between">
                    <Heading
                        title="Pánicos"
                        description={
                            hasDateFilter
                                ? `Alertas de pánico en ${area.name} el ${periodLabel(filters)}`
                                : `Alertas de pánico en ${area.name} (${area.code})`
                        }
                    />
                    {hasDateFilter && (
                        <Button variant="ghost" size="sm" asChild>
                            <Link href={panicosIndex.url()}>
                                Quitar filtro de fecha
                            </Link>
                        </Button>
                    )}
                </div>

                <div className="overflow-x-auto rounded-xl border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left">
                            <tr>
                                <th className="px-4 py-3 font-medium">Fecha</th>
                                <th className="px-4 py-3 font-medium">
                                    Guardia
                                </th>
                                <th className="px-4 py-3 font-medium">
                                    Recorrido
                                </th>
                                <th className="px-4 py-3 font-medium text-right">
                                    Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {alerts.data.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={4}
                                        className="px-4 py-8 text-center text-muted-foreground"
                                    >
                                        No hay alertas de pánico en este
                                        periodo.
                                    </td>
                                </tr>
                            )}
                            {alerts.data.map((alert) => (
                                <tr key={alert.id} className="border-t">
                                    <td className="px-4 py-3">
                                        {formatDateTime(alert.created_at)}
                                    </td>
                                    <td className="px-4 py-3 font-medium">
                                        {alert.guard}
                                    </td>
                                    <td className="px-4 py-3">
                                        {alert.round?.title ?? '—'}
                                    </td>
                                    <td className="px-4 py-3 text-right">
                                        {alert.round && alert.patrol_id ? (
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                asChild
                                            >
                                                <Link
                                                    href={showPatrol.url({
                                                        round: alert.round.id,
                                                        patrol: alert.patrol_id,
                                                    })}
                                                >
                                                    Ver rondín
                                                </Link>
                                            </Button>
                                        ) : (
                                            '—'
                                        )}
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

PanicosIndex.layout = {
    breadcrumbs: [
        {
            title: 'Pánicos',
            href: panicosIndex(),
        },
    ],
};
