import { Head } from '@inertiajs/react';
import { DayBars, type DayCount } from '@/components/day-bars';
import Heading from '@/components/heading';
import {
    ReportDateFilters,
    type ReportFilters,
} from '@/components/report-date-filters';
import TextLink from '@/components/text-link';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { incidentListUrl } from '@/lib/list-filters';
import { volumen } from '@/routes/reportes';

type AreaSummary = {
    id: number;
    name: string;
    code: string;
};

type CategoryRow = {
    category_id: number | null;
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
                            Clic en un día para ver esas incidencias
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {report.totals.total === 0 ? (
                            <p className="text-sm text-muted-foreground">
                                No hay incidencias en este periodo.
                            </p>
                        ) : (
                            <DayBars
                                days={report.series}
                                layout="scroll"
                                countNoun="incidencias"
                                hrefForDay={(day) =>
                                    incidentListUrl({
                                        from: day.date,
                                        to: day.date,
                                    })
                                }
                            />
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
                                <tr
                                    key={row.category_id ?? row.category}
                                    className="border-t"
                                >
                                    <td className="px-4 py-3">
                                        {row.category_id ? (
                                            <TextLink
                                                href={incidentListUrl({
                                                    from: filters.from,
                                                    to: filters.to,
                                                    category_id: row.category_id,
                                                })}
                                            >
                                                {row.category}
                                            </TextLink>
                                        ) : (
                                            row.category
                                        )}
                                    </td>
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
