import { MapPin } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { startPatrol } from '../store';
import type { OfflineRound } from '../types';
import type { GuardData } from '../use-guard-data';
import type { GuardRoute } from '../use-hash-route';

export function RoundsScreen({
    data,
    navigate,
}: {
    data: GuardData;
    navigate: (route: GuardRoute) => void;
}) {
    const [startingId, setStartingId] = useState<number | null>(null);
    const pkg = data.pkg!;
    const rounds = pkg.rounds.filter(
        (round) => round.area_id === data.selectedAreaId,
    );

    const start = async (round: OfflineRound) => {
        setStartingId(round.id);

        try {
            await startPatrol(pkg.user.id, round);
            navigate({ name: 'patrol' });
        } catch (error) {
            toast.error((error as Error).message);
        } finally {
            setStartingId(null);
        }
    };

    return (
        <div className="flex flex-col gap-6">
            <Heading
                title="Recorridos"
                description="Elige el recorrido que vas a realizar."
            />

            {rounds.length === 0 && (
                <p className="rounded-xl border p-4 text-sm text-muted-foreground">
                    No hay recorridos activos en esta área. Conéctate a la red
                    para actualizar la información.
                </p>
            )}

            <ul className="flex flex-col gap-3">
                {rounds.map((round) => (
                    <li
                        key={round.id}
                        className="space-y-3 rounded-xl border p-4"
                    >
                        <div>
                            <p className="font-medium">{round.title}</p>
                            {round.instructions && (
                                <p className="text-sm text-muted-foreground">
                                    {round.instructions}
                                </p>
                            )}
                            <p className="mt-1 flex items-center gap-1 text-xs text-muted-foreground">
                                <MapPin className="size-3.5" />
                                {round.checkpoints.length} puntos
                            </p>
                        </div>
                        <Button
                            className="w-full"
                            disabled={
                                startingId !== null ||
                                round.checkpoints.length === 0
                            }
                            onClick={() => void start(round)}
                        >
                            {startingId === round.id && <Spinner />}
                            {data.activePatrol?.round_id === round.id
                                ? 'Continuar'
                                : 'Iniciar recorrido'}
                        </Button>
                    </li>
                ))}
            </ul>
        </div>
    );
}
