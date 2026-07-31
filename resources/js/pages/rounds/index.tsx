import { Head, Link, router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    index as roundsIndex,
    create as roundsCreate,
    edit as roundsEdit,
    destroy as roundsDestroy,
} from '@/routes/rounds';

type AreaSummary = {
    id: number;
    name: string;
    code: string;
};

type RoundRow = {
    id: number;
    title: string;
    instructions: string | null;
    is_active: boolean;
    checkpoints_count: number;
};

export default function RoundsIndex({
    area,
    rounds,
}: {
    area: AreaSummary;
    rounds: RoundRow[];
}) {
    return (
        <>
            <Head title="Recorridos" />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title="Recorridos"
                        description={`Configuración para ${area.name} (${area.code})`}
                    />
                    <Button asChild>
                        <Link href={roundsCreate()}>
                            <Plus className="size-4" />
                            Nuevo recorrido
                        </Link>
                    </Button>
                </div>

                <div className="overflow-hidden rounded-xl border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left">
                            <tr>
                                <th className="px-4 py-3 font-medium">Título</th>
                                <th className="px-4 py-3 font-medium">Puntos</th>
                                <th className="px-4 py-3 font-medium">Estado</th>
                                <th className="px-4 py-3 font-medium text-right">
                                    Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {rounds.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={4}
                                        className="px-4 py-8 text-center text-muted-foreground"
                                    >
                                        Aún no hay recorridos en esta área.
                                    </td>
                                </tr>
                            )}
                            {rounds.map((round) => (
                                <tr key={round.id} className="border-t">
                                    <td className="px-4 py-3">
                                        <div className="font-medium">
                                            {round.title}
                                        </div>
                                        {round.instructions && (
                                            <p className="mt-1 line-clamp-1 text-muted-foreground">
                                                {round.instructions}
                                            </p>
                                        )}
                                    </td>
                                    <td className="px-4 py-3">
                                        {round.checkpoints_count}
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
                                        <div className="flex justify-end gap-2">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                asChild
                                            >
                                                <Link href={roundsEdit(round)}>
                                                    Editar
                                                </Link>
                                            </Button>
                                            <Button
                                                variant="destructive"
                                                size="sm"
                                                onClick={() => {
                                                    if (
                                                        confirm(
                                                            '¿Eliminar este recorrido y sus puntos?',
                                                        )
                                                    ) {
                                                        router.delete(
                                                            roundsDestroy(
                                                                round,
                                                            ),
                                                        );
                                                    }
                                                }}
                                            >
                                                Eliminar
                                            </Button>
                                        </div>
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

RoundsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Recorridos',
            href: roundsIndex(),
        },
    ],
};
