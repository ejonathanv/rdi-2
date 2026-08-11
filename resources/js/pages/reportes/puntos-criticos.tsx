import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import {
    ReportDateFilters,
    type ReportFilters,
} from '@/components/report-date-filters';
import { puntosCriticos } from '@/routes/reportes';

type AreaSummary = {
    id: number;
    name: string;
    code: string;
};

type HotspotRow = {
    checkpoint_id: number | null;
    checkpoint: string;
    round: string | null;
    incidents: number;
    urgent_visits: number;
    score: number;
    last_at: string | null;
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

export default function ReportesPuntosCriticos({
    area,
    filters,
    report,
}: {
    area: AreaSummary;
    filters: ReportFilters;
    report: { rows: HotspotRow[] };
}) {
    return (
        <>
            <Head title="Puntos críticos" />

            <div className="flex flex-col gap-6 p-4">
                <Heading
                    title="Puntos críticos"
                    description={`Concentración de fallos en ${area.name} (${area.code})`}
                />

                <ReportDateFilters
                    filters={filters}
                    applyUrl={(query) => puntosCriticos.url({ query })}
                    resetUrl={puntosCriticos.url()}
                />

                <div className="overflow-hidden rounded-xl border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left">
                            <tr>
                                <th className="px-4 py-3 font-medium">Punto</th>
                                <th className="px-4 py-3 font-medium">
                                    Recorrido
                                </th>
                                <th className="px-4 py-3 font-medium">
                                    Incidencias
                                </th>
                                <th className="px-4 py-3 font-medium">
                                    Urgentes en rondín
                                </th>
                                <th className="px-4 py-3 font-medium">
                                    Score
                                </th>
                                <th className="px-4 py-3 font-medium">
                                    Última actividad
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {report.rows.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={6}
                                        className="px-4 py-8 text-center text-muted-foreground"
                                    >
                                        No hay puntos críticos en este periodo.
                                    </td>
                                </tr>
                            )}
                            {report.rows.map((row) => (
                                <tr
                                    key={row.checkpoint_id ?? row.checkpoint}
                                    className="border-t"
                                >
                                    <td className="px-4 py-3 font-medium">
                                        {row.checkpoint}
                                    </td>
                                    <td className="px-4 py-3">
                                        {row.round ?? '—'}
                                    </td>
                                    <td className="px-4 py-3">
                                        {row.incidents}
                                    </td>
                                    <td className="px-4 py-3">
                                        {row.urgent_visits}
                                    </td>
                                    <td className="px-4 py-3">{row.score}</td>
                                    <td className="px-4 py-3 text-muted-foreground">
                                        {formatDateTime(row.last_at)}
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

ReportesPuntosCriticos.layout = {
    breadcrumbs: [
        {
            title: 'Puntos críticos',
            href: puntosCriticos(),
        },
    ],
};
