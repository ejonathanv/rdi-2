import { Head, Link } from '@inertiajs/react';
import { ClipboardList } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { index as rondinesIndex } from '@/routes/rondines';
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

export default function RondinesIndex({
    area,
    rounds: roundRows,
}: {
    area: AreaSummary;
    rounds: RoundRow[];
}) {
    return (
        <>
            <Head title="Rondines" />

            <div className="flex flex-col gap-6 p-4">
                <Heading
                    title="Rondines"
                    description={`Recorridos realizados en ${area.name} (${area.code})`}
                />

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
