import { Head, Link } from '@inertiajs/react';
import { ClipboardList } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { index as rondinesIndex } from '@/routes/rondines';
import { show as showPatrol } from '@/routes/rondines/patrols';
import { show as showRound } from '@/routes/rondines/rounds';

type AreaSummary = {
    id: number;
    name: string;
    code: string;
};

type RoundRow = {
    id: number;
    title: string;
    is_active: boolean;
    checkpoints_count: number;
    patrol_runs_count: number;
};

type PatrolRow = {
    id: number;
    status: string;
    status_label: string;
    started_at: string;
    finished_at: string | null;
    duration_seconds: number | null;
    duration_label: string | null;
    guard: { id: number; name: string };
    round: { id: number; title: string };
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
        timeStyle: 'medium',
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

export default function RondinesIndex({
    area,
    rounds: roundRows,
    patrols = [],
    filters = { from: null, to: null },
}: {
    area: AreaSummary;
    rounds: RoundRow[];
    patrols?: PatrolRow[];
    filters?: Filters;
}) {
    const hasDateFilter = Boolean(filters.from || filters.to);

    return (
        <>
            <Head title="Rondines" />

            <div className="flex flex-col gap-6 p-4">
                <Heading
                    title="Rondines"
                    description={`Recorridos realizados en ${area.name} (${area.code})`}
                />

                {hasDateFilter && (
                    <div className="flex flex-col gap-3">
                        <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                            <Heading
                                variant="small"
                                title="Finalizados"
                                description={`Rondines completados el ${periodLabel(filters)}`}
                            />
                            <Button variant="ghost" size="sm" asChild>
                                <Link href={rondinesIndex.url()}>
                                    Quitar filtro de fecha
                                </Link>
                            </Button>
                        </div>

                        <div className="overflow-x-auto rounded-xl border">
                            <table className="w-full text-sm">
                                <thead className="bg-muted/50 text-left">
                                    <tr>
                                        <th className="px-4 py-3 font-medium">
                                            Recorrido
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Guardia
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Inicio
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Fin
                                        </th>
                                        <th className="px-4 py-3 font-medium">
                                            Tiempo total
                                        </th>
                                        <th className="px-4 py-3 font-medium text-right">
                                            Acciones
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {patrols.length === 0 && (
                                        <tr>
                                            <td
                                                colSpan={6}
                                                className="px-4 py-8 text-center text-muted-foreground"
                                            >
                                                No hay rondines finalizados en
                                                esa fecha.
                                            </td>
                                        </tr>
                                    )}
                                    {patrols.map((patrol) => (
                                        <tr
                                            key={patrol.id}
                                            className="border-t"
                                        >
                                            <td className="px-4 py-3 font-medium">
                                                {patrol.round.title}
                                            </td>
                                            <td className="px-4 py-3">
                                                {patrol.guard.name}
                                            </td>
                                            <td className="px-4 py-3">
                                                {formatDateTime(
                                                    patrol.started_at,
                                                )}
                                            </td>
                                            <td className="px-4 py-3">
                                                {formatDateTime(
                                                    patrol.finished_at,
                                                )}
                                            </td>
                                            <td className="px-4 py-3">
                                                {patrol.duration_label ?? '—'}
                                            </td>
                                            <td className="px-4 py-3 text-right">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <Link
                                                        href={showPatrol.url({
                                                            round: patrol.round
                                                                .id,
                                                            patrol: patrol.id,
                                                        })}
                                                    >
                                                        Ver detalle
                                                    </Link>
                                                </Button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                )}

                <div className="overflow-hidden rounded-xl border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left">
                            <tr>
                                <th className="px-4 py-3 font-medium">
                                    Recorrido
                                </th>
                                <th className="px-4 py-3 font-medium">Puntos</th>
                                <th className="px-4 py-3 font-medium">
                                    Realizados
                                </th>
                                <th className="px-4 py-3 font-medium">Estado</th>
                                <th className="px-4 py-3 font-medium text-right">
                                    Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {roundRows.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={5}
                                        className="px-4 py-8 text-center text-muted-foreground"
                                    >
                                        No hay recorridos configurados en esta
                                        área.
                                    </td>
                                </tr>
                            )}
                            {roundRows.map((round) => (
                                <tr key={round.id} className="border-t">
                                    <td className="px-4 py-3 font-medium">
                                        {round.title}
                                    </td>
                                    <td className="px-4 py-3">
                                        {round.checkpoints_count}
                                    </td>
                                    <td className="px-4 py-3">
                                        {round.patrol_runs_count}
                                    </td>
                                    <td className="px-4 py-3">
                                        <Badge
                                            variant={
                                                round.is_active
                                                    ? 'default'
                                                    : 'secondary'
                                            }
                                        >
                                            {round.is_active
                                                ? 'Activo'
                                                : 'Inactivo'}
                                        </Badge>
                                    </td>
                                    <td className="px-4 py-3 text-right">
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            asChild
                                        >
                                            <Link href={showRound.url(round)}>
                                                <ClipboardList className="size-4" />
                                                Ver rondines
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

RondinesIndex.layout = {
    breadcrumbs: [
        {
            title: 'Rondines',
            href: rondinesIndex(),
        },
    ],
};
