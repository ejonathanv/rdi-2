import { Head, Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { index as rondinesIndex } from '@/routes/rondines';
import { show as showPatrol } from '@/routes/rondines/patrols';

type AreaSummary = {
    id: number;
    name: string;
    code: string;
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

export default function RondinesRound({
    area,
    round,
    patrols,
}: {
    area: AreaSummary;
    round: { id: number; title: string; is_active: boolean };
    patrols: PatrolRow[];
}) {
    return (
        <>
            <Head title={`Rondines · ${round.title}`} />

            <div className="flex flex-col gap-6 p-4">
                <Heading
                    title={round.title}
                    description={`Rondines realizados en ${area.name}`}
                />

                <div className="overflow-x-auto rounded-xl border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left">
                            <tr>
                                <th className="px-4 py-3 font-medium">Estado</th>
                                <th className="px-4 py-3 font-medium">
                                    Guardia
                                </th>
                                <th className="px-4 py-3 font-medium">Inicio</th>
                                <th className="px-4 py-3 font-medium">Fin</th>
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
                                        Aún no hay rondines realizados para este
                                        recorrido.
                                    </td>
                                </tr>
                            )}
                            {patrols.map((patrol) => (
                                <tr key={patrol.id} className="border-t">
                                    <td className="px-4 py-3">
                                        <Badge
                                            variant={
                                                patrol.status === 'completed'
                                                    ? 'default'
                                                    : 'secondary'
                                            }
                                        >
                                            {patrol.status_label}
                                        </Badge>
                                    </td>
                                    <td className="px-4 py-3 font-medium">
                                        {patrol.guard.name}
                                    </td>
                                    <td className="px-4 py-3">
                                        {formatDateTime(patrol.started_at)}
                                    </td>
                                    <td className="px-4 py-3">
                                        {formatDateTime(patrol.finished_at)}
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
                                                    round: round.id,
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
        </>
    );
}

RondinesRound.layout = {
    breadcrumbs: [
        {
            title: 'Rondines',
            href: rondinesIndex(),
        },
        {
            title: 'Realizados',
            href: '#',
        },
    ],
};
