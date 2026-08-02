import { Head, Link } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { home as guardHome } from '@/routes/guard';
import { index as guardRoundsIndex } from '@/routes/guard/rounds';

type CheckpointRow = {
    id: number;
    name: string;
    instructions: string | null;
    position: number;
};

type RoundData = {
    id: number;
    title: string;
    instructions: string | null;
    area: { id: number; name: string };
    checkpoints: CheckpointRow[];
};

export default function GuardRoundsShow({ round }: { round: RoundData }) {
    return (
        <>
            <Head title={round.title} />

            <div className="mx-auto flex w-full max-w-lg flex-col gap-6 p-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <Heading
                        title={round.title}
                        description={`${round.area.name} · Recorrido iniciado`}
                    />
                    <Button variant="outline" asChild>
                        <Link href={guardRoundsIndex()}>Cambiar recorrido</Link>
                    </Button>
                </div>

                {round.instructions && (
                    <p className="text-sm text-muted-foreground">
                        {round.instructions}
                    </p>
                )}

                <div className="space-y-3">
                    <Heading
                        variant="small"
                        title="Puntos de revisión"
                        description="Escanea el QR de cada punto para responder el cuestionario."
                    />

                    {round.checkpoints.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            Este recorrido no tiene puntos activos.
                        </p>
                    ) : (
                        round.checkpoints.map((checkpoint) => (
                            <div
                                key={checkpoint.id}
                                className="space-y-1 rounded-xl border p-4"
                            >
                                <div className="flex flex-wrap items-center gap-2">
                                    <Badge variant="secondary">
                                        #{checkpoint.position}
                                    </Badge>
                                    <p className="font-medium">
                                        {checkpoint.name}
                                    </p>
                                </div>
                                {checkpoint.instructions && (
                                    <p className="text-sm text-muted-foreground">
                                        {checkpoint.instructions}
                                    </p>
                                )}
                            </div>
                        ))
                    )}
                </div>

                <Button variant="outline" asChild>
                    <Link href={guardHome()}>Volver al panel</Link>
                </Button>
            </div>
        </>
    );
}

GuardRoundsShow.layout = {
    breadcrumbs: [
        {
            title: 'Panel',
            href: guardHome(),
        },
        {
            title: 'Recorridos',
            href: guardRoundsIndex(),
        },
        {
            title: 'Detalle',
            href: '#',
        },
    ],
};
