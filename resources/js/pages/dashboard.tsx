import { Head, Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { dashboard } from '@/routes';
import { show as showPatrol } from '@/routes/rondines/patrols';
import { index as rondinesIndex } from '@/routes/rondines';

type AreaSummary = {
    id: number;
    name: string;
    code: string;
};

type Kpis = {
    urgents_today: number;
    in_progress: number;
    completed_today: number;
    average_duration_seconds: number | null;
    average_duration_label: string | null;
};

type UrgentRow = {
    id: number;
    reviewed_at: string;
    urgent_notes: string | null;
    checkpoint: string;
    round: { id: number; title: string };
    guard: string;
    patrol_id: number;
};

type ActivePatrolRow = {
    id: number;
    started_at: string;
    duration_so_far_label: string;
    round: { id: number; title: string };
    guard: string;
};

type DayCount = {
    date: string;
    label: string;
    count: number;
};

function formatDateTime(value: string): string {
    return new Date(value).toLocaleString('es-MX', {
        dateStyle: 'short',
        timeStyle: 'short',
    });
}

export default function Dashboard({
    area,
    kpis,
    recent_urgents,
    active_patrols,
    completed_last_7_days,
}: {
    area: AreaSummary | null;
    kpis: Kpis;
    recent_urgents: UrgentRow[];
    active_patrols: ActivePatrolRow[];
    completed_last_7_days: DayCount[];
}) {
    const maxCompleted = Math.max(
        1,
        ...completed_last_7_days.map((day) => day.count),
    );

    return (
        <>
            <Head title="Panel" />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <Heading
                        title="Panel"
                        description={
                            area
                                ? `Indicadores de rondines · ${area.name} (${area.code})`
                                : 'Selecciona un área para ver indicadores'
                        }
                    />
                    <Button variant="outline" asChild>
                        <Link href={rondinesIndex()}>Ver rondines</Link>
                    </Button>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <Card className="gap-3 py-4">
                        <CardHeader className="px-4">
                            <CardDescription>Urgentes hoy</CardDescription>
                            <CardTitle className="text-3xl text-destructive">
                                {kpis.urgents_today}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                    <Card className="gap-3 py-4">
                        <CardHeader className="px-4">
                            <CardDescription>En curso</CardDescription>
                            <CardTitle className="text-3xl">
                                {kpis.in_progress}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                    <Card className="gap-3 py-4">
                        <CardHeader className="px-4">
                            <CardDescription>Finalizados hoy</CardDescription>
                            <CardTitle className="text-3xl">
                                {kpis.completed_today}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                    <Card className="gap-3 py-4">
                        <CardHeader className="px-4">
                            <CardDescription>
                                Tiempo promedio (7 días)
                            </CardDescription>
                            <CardTitle className="text-3xl">
                                {kpis.average_duration_label ?? '—'}
                            </CardTitle>
                        </CardHeader>
                    </Card>
                </div>

                <div className="grid gap-4 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Últimos puntos urgentes</CardTitle>
                            <CardDescription>
                                Los 5 más recientes del área
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {recent_urgents.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    No hay puntos marcados como urgentes.
                                </p>
                            ) : (
                                <ul className="divide-y rounded-xl border">
                                    {recent_urgents.map((item) => (
                                        <li
                                            key={item.id}
                                            className="flex flex-col gap-2 p-3 sm:flex-row sm:items-center sm:justify-between"
                                        >
                                            <div className="space-y-1">
                                                <div className="flex flex-wrap items-center gap-2">
                                                    <span className="font-medium">
                                                        {item.checkpoint}
                                                    </span>
                                                    <Badge variant="destructive">
                                                        Urgente
                                                    </Badge>
                                                </div>
                                                <p className="text-sm text-muted-foreground">
                                                    {item.round.title} ·{' '}
                                                    {item.guard} ·{' '}
                                                    {formatDateTime(
                                                        item.reviewed_at,
                                                    )}
                                                </p>
                                                {item.urgent_notes && (
                                                    <p className="line-clamp-2 text-sm">
                                                        {item.urgent_notes}
                                                    </p>
                                                )}
                                            </div>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                asChild
                                            >
                                                <Link
                                                    href={showPatrol.url({
                                                        round: item.round.id,
                                                        patrol: item.patrol_id,
                                                    })}
                                                >
                                                    Ver detalle
                                                </Link>
                                            </Button>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Rondines en curso</CardTitle>
                            <CardDescription>
                                Patrullas activas ahora
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {active_patrols.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                    No hay rondines en curso.
                                </p>
                            ) : (
                                <ul className="divide-y rounded-xl border">
                                    {active_patrols.map((patrol) => (
                                        <li
                                            key={patrol.id}
                                            className="flex flex-col gap-2 p-3 sm:flex-row sm:items-center sm:justify-between"
                                        >
                                            <div className="space-y-1">
                                                <div className="font-medium">
                                                    {patrol.round.title}
                                                </div>
                                                <p className="text-sm text-muted-foreground">
                                                    {patrol.guard} · desde{' '}
                                                    {formatDateTime(
                                                        patrol.started_at,
                                                    )}{' '}
                                                    ·{' '}
                                                    {
                                                        patrol.duration_so_far_label
                                                    }
                                                </p>
                                            </div>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                asChild
                                            >
                                                <Link
                                                    href={showPatrol.url({
                                                        round: patrol.round.id,
                                                        patrol: patrol.id,
                                                    })}
                                                >
                                                    Ver
                                                </Link>
                                            </Button>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Finalizados (últimos 7 días)</CardTitle>
                        <CardDescription>
                            Volumen diario de rondines completados
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-7 gap-2">
                            {completed_last_7_days.map((day) => (
                                <div
                                    key={day.date}
                                    className="flex flex-col items-center gap-2"
                                >
                                    <div className="flex h-28 w-full items-end justify-center rounded-lg bg-muted/40 px-1 py-2">
                                        <div
                                            className="w-full max-w-8 rounded-t bg-primary"
                                            style={{
                                                height: `${Math.max(
                                                    day.count === 0
                                                        ? 0
                                                        : 12,
                                                    (day.count / maxCompleted) *
                                                        100,
                                                )}%`,
                                            }}
                                            title={`${day.count} finalizados`}
                                        />
                                    </div>
                                    <div className="text-center">
                                        <div className="text-xs text-muted-foreground">
                                            {day.label}
                                        </div>
                                        <div className="text-sm font-medium">
                                            {day.count}
                                        </div>
                                    </div>
                                </div>
                            ))}
                        </div>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Panel',
            href: dashboard(),
        },
    ],
};
