import { Head } from '@inertiajs/react';
import Heading from '@/components/heading';
import {
    ReportDateFilters,
    type ReportFilters,
} from '@/components/report-date-filters';
import {
    Card,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { tiempos } from '@/routes/reportes';

type AreaSummary = {
    id: number;
    name: string;
    code: string;
};

type Summary = {
    with_response: number;
    without_response: number;
    with_resolution: number;
    without_resolution: number;
    avg_response_seconds: number | null;
    avg_response_label: string | null;
    median_response_seconds: number | null;
    median_response_label: string | null;
    avg_resolution_seconds: number | null;
    avg_resolution_label: string | null;
    median_resolution_seconds: number | null;
    median_resolution_label: string | null;
    urgent_avg_response_seconds: number | null;
    urgent_avg_response_label: string | null;
    non_urgent_avg_response_seconds: number | null;
    non_urgent_avg_response_label: string | null;
};

type CategoryRow = {
    category: string;
    with_response: number;
    avg_response_seconds: number | null;
    avg_response_label: string | null;
    with_resolution: number;
    avg_resolution_seconds: number | null;
    avg_resolution_label: string | null;
};

type Report = {
    summary: Summary;
    by_category: CategoryRow[];
};

function duration(label: string | null): string {
    return label ?? '—';
}

export default function ReportesTiempos({
    area,
    filters,
    report,
}: {
    area: AreaSummary;
    filters: ReportFilters;
    report: Report;
}) {
    const { summary } = report;

    return (
        <>
            <Head title="Tiempos de atención" />

            <div className="flex flex-col gap-6 p-4">
                <Heading
                    title="Tiempos de atención"
                    description={`Respuesta y cierre en ${area.name} (${area.code})`}
                />

                <ReportDateFilters
                    filters={filters}
                    applyUrl={(query) => tiempos.url({ query })}
                    resetUrl={tiempos.url()}
                />

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Respuesta promedio</CardDescription>
                            <CardTitle className="text-2xl">
                                {duration(summary.avg_response_label)}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Respuesta mediana</CardDescription>
                            <CardTitle className="text-2xl">
                                {duration(summary.median_response_label)}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Cierre promedio</CardDescription>
                            <CardTitle className="text-2xl">
                                {duration(summary.avg_resolution_label)}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Cierre mediana</CardDescription>
                            <CardTitle className="text-2xl">
                                {duration(summary.median_resolution_label)}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Con acuse</CardDescription>
                            <CardTitle className="text-2xl">
                                {summary.with_response}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>Sin acuse</CardDescription>
                            <CardTitle className="text-2xl">
                                {summary.without_response}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>
                                Respuesta urgentes
                            </CardDescription>
                            <CardTitle className="text-2xl">
                                {duration(summary.urgent_avg_response_label)}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardDescription>
                                Respuesta no urgentes
                            </CardDescription>
                            <CardTitle className="text-2xl">
                                {duration(
                                    summary.non_urgent_avg_response_label,
                                )}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                </div>

                <div className="overflow-hidden rounded-xl border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left">
                            <tr>
                                <th className="px-4 py-3 font-medium">
                                    Categoría
                                </th>
                                <th className="px-4 py-3 font-medium">
                                    Con acuse
                                </th>
                                <th className="px-4 py-3 font-medium">
                                    Resp. promedio
                                </th>
                                <th className="px-4 py-3 font-medium">
                                    Con cierre
                                </th>
                                <th className="px-4 py-3 font-medium">
                                    Cierre promedio
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {report.by_category.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={5}
                                        className="px-4 py-8 text-center text-muted-foreground"
                                    >
                                        Sin datos en este periodo.
                                    </td>
                                </tr>
                            )}
                            {report.by_category.map((row) => (
                                <tr key={row.category} className="border-t">
                                    <td className="px-4 py-3">{row.category}</td>
                                    <td className="px-4 py-3">
                                        {row.with_response}
                                    </td>
                                    <td className="px-4 py-3">
                                        {duration(row.avg_response_label)}
                                    </td>
                                    <td className="px-4 py-3">
                                        {row.with_resolution}
                                    </td>
                                    <td className="px-4 py-3">
                                        {duration(row.avg_resolution_label)}
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

ReportesTiempos.layout = {
    breadcrumbs: [
        {
            title: 'Tiempos de atención',
            href: tiempos(),
        },
    ],
};
