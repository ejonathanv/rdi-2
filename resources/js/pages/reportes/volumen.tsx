import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import {
    ReportDateFilters,
    type ReportFilters,
} from '@/components/report-date-filters';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { volumen } from '@/routes/reportes';

type AreaSummary = {
    id: number;
    name: string;
    code: string;
};

type DayCount = {
    date: string;
    label: string;
    count: number;
};

type CategoryRow = {
    category: string;
    total: number;
    urgent: number;
    open: number;
    resolved: number;
    discarded: number;
};

type Report = {
    totals: {
        total: number;
        open: number;
        resolved: number;
        discarded: number;
        urgent: number;
    };
    by_category: CategoryRow[];
    series: DayCount[];
};

function SeriesBars({ days }: { days: DayCount[] }) {
    const maxCount = Math.max(1, ...days.map((day) => day.count));

    return (
        <div className="flex gap-1 overflow-x-auto pb-2">
            {days.map((day) => (
                <div
                    key={day.date}
                    className="flex w-8 shrink-0 flex-col items-center gap-2"
                    title={`${day.label}: ${day.count}`}
                >
                    <div className="flex h-28 w-full items-end justify-center rounded-lg bg-muted/40 px-1 py-2">
                        <div
                            className="w-full rounded-t bg-primary"
                            style={{
                                height: `${Math.max(
                                    day.count === 0 ? 0 : 12,
                                    (day.count / maxCount) * 100,
                                )}%`,
                            }}
                        />
                    </div>
                    <span className="text-[10px] text-muted-foreground">
                        {day.label}
                    </span>
                </div>
            ))}
        </div>
    );
}

export default function ReportesVolumen({
    area,
    filters,
    report,
}: {
    area: AreaSummary;
    filters: ReportFilters;
    report: Report;
}) {
    return (
        <>
            <Head title="Volumen de incidencias" />

            <div className="flex flex-col gap-6 p-4">
                <Heading
                    title="Volumen de incidencias"
                    description={`Carga y distribución en ${area.name} (${area.code})`}
                />

                <ReportDateFilters
                    filters={filters}
                    applyUrl={(query) => volumen.url({ query })}
                    resetUrl={volumen.url()}
                />

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    {[
                        { label: 'Total', value: report.totals.total },
                        { label: 'Abiertas', value: report.totals.open },
                        { label: 'Resueltas', value: report.totals.resolved },
                        {
                            label: 'Descartadas',
                            value: report.totals.discarded,
                        },
                        { label: 'Urgentes', value: report.totals.urgent },
                    ].map((kpi) => (
                        <Card key={kpi.label}>
                            <CardHeader className="pb-2">
                                <CardDescription>{kpi.label}</CardDescription>
                                <CardTitle className="text-3xl">
                                    {kpi.value}
                                </CardTitle>
                            </CardHeader>
                        </Card>
                    ))}
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Serie diaria</CardTitle>
                        <CardDescription>
                            Incidencias creadas por día en el periodo
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {report.totals.total === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                No hay incidencias en este periodo.
                            </p>
                        ) : (
                            <SeriesBars days={report.series} />
                        )}
                    </CardContent>
                </Card>

                <div className="overflow-hidden rounded-xl border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left">
                            <tr>
                                <th className="px-4 py-3 font-medium">
                                    Categoría
                                </th>
                                <th className="px-4 py-3 font-medium">Total</th>
                                <th className="px-4 py-3 font-medium">
                                    Urgentes
                                </th>
                                <th className="px-4 py-3 font-medium">
                                    Abiertas
                                </th>
                                <th className="px-4 py-3 font-medium">
                                    Resueltas
                                </th>
                                <th className="px-4 py-3 font-medium">
                                    Descartadas
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {report.by_category.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={6}
                                        className="px-4 py-8 text-center text-muted-foreground"
                                    >
                                        Sin datos por categoría.
                                    </td>
                                </tr>
                            )}
                            {report.by_category.map((row) => (
                                <tr key={row.category} className="border-t">
                                    <td className="px-4 py-3">{row.category}</td>
                                    <td className="px-4 py-3">{row.total}</td>
                                    <td className="px-4 py-3">{row.urgent}</td>
                                    <td className="px-4 py-3">{row.open}</td>
                                    <td className="px-4 py-3">{row.resolved}</td>
                                    <td className="px-4 py-3">
                                        {row.discarded}
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

ReportesVolumen.layout = {
    breadcrumbs: [
        {
            title: 'Volumen de incidencias',
            href: volumen(),
        },
    ],
};
