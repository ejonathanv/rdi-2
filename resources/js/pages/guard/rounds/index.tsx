import { Head, Link, router, usePage } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { home as guardHome } from '@/routes/guard';
import { index as guardRoundsIndex, start as startPatrol } from '@/routes/guard/rounds';

type RoundRow = {
    id: number;
    title: string;
    instructions: string | null;
    checkpoints_count: number;
    area: { id: number; name: string };
};

export default function GuardRoundsIndex({ rounds }: { rounds: RoundRow[] }) {
    const { errors } = usePage().props as { errors: Record<string, string> };

    return (
        <>
            <Head title="Iniciar recorrido" />

            <div className="mx-auto flex w-full max-w-lg flex-col gap-6 p-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <Heading
                        title="Iniciar recorrido"
                        description="Recorridos disponibles en tus áreas como guardia."
                    />
                    <Button variant="outline" asChild>
                        <Link href={guardHome()}>Volver</Link>
                    </Button>
                </div>

                <InputError message={errors.round} />

                {rounds.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        No hay recorridos activos disponibles por ahora.
                    </p>
                ) : (
                    <div className="space-y-3">
                        {rounds.map((round) => (
                            <button
                                key={round.id}
                                type="button"
                                className="flex w-full items-start justify-between gap-3 rounded-xl border p-4 text-left transition-colors hover:bg-muted/50"
                                onClick={() =>
                                    router.post(startPatrol.url(round))
                                }
                            >
                                <div className="space-y-1">
                                    <p className="font-medium">{round.title}</p>
                                    <p className="text-sm text-muted-foreground">
                                        {round.area.name} ·{' '}
                                        {round.checkpoints_count}{' '}
                                        {round.checkpoints_count === 1
                                            ? 'punto'
                                            : 'puntos'}
                                    </p>
                                    {round.instructions && (
                                        <p className="text-sm text-muted-foreground">
                                            {round.instructions}
                                        </p>
                                    )}
                                </div>
                                <ChevronRight className="mt-1 size-5 shrink-0 text-muted-foreground" />
                            </button>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}

GuardRoundsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Panel',
            href: guardHome(),
        },
        {
            title: 'Recorridos',
            href: guardRoundsIndex(),
        },
    ],
};
