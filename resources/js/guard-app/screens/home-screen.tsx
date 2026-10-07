import { AlertTriangle, ChevronRight, Route, Siren } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { InstallPrompt } from '../components/install-prompt';
import { PanicDialog } from '../components/panic-dialog';
import type { GuardData } from '../use-guard-data';
import type { GuardRoute } from '../use-hash-route';

export function HomeScreen({
    data,
    navigate,
}: {
    data: GuardData;
    navigate: (route: GuardRoute) => void;
}) {
    const [panicOpen, setPanicOpen] = useState(false);
    const pkg = data.pkg!;
    const area = pkg.areas.find((item) => item.id === data.selectedAreaId);
    const activeRound = data.activePatrol
        ? pkg.rounds.find((round) => round.id === data.activePatrol?.round_id)
        : null;

    return (
        <div className="flex flex-col gap-6">
            <Heading
                title={`Hola, ${pkg.user.name.split(' ')[0]}`}
                description="Elige una acción para continuar con tu turno."
            />

            <InstallPrompt />

            {pkg.areas.length > 1 && (
                <Select
                    value={
                        data.selectedAreaId
                            ? String(data.selectedAreaId)
                            : undefined
                    }
                    onValueChange={(value) =>
                        void data.selectArea(Number(value))
                    }
                >
                    <SelectTrigger className="w-full">
                        <SelectValue placeholder="Seleccionar área" />
                    </SelectTrigger>
                    <SelectContent>
                        {pkg.areas.map((item) => (
                            <SelectItem key={item.id} value={String(item.id)}>
                                {item.name}
                            </SelectItem>
                        ))}
                    </SelectContent>
                </Select>
            )}

            {data.activePatrol && activeRound && (
                <button
                    type="button"
                    onClick={() => navigate({ name: 'patrol' })}
                    className="flex items-center justify-between rounded-xl border border-primary/40 bg-primary/5 p-4 text-left"
                >
                    <div>
                        <p className="text-xs font-medium tracking-wide text-primary uppercase">
                            Recorrido en curso
                        </p>
                        <p className="font-medium">{activeRound.title}</p>
                        <p className="text-sm text-muted-foreground">
                            {data.activePatrol.visits.length} de{' '}
                            {activeRound.checkpoints.length} puntos revisados
                        </p>
                    </div>
                    <ChevronRight className="size-5 text-muted-foreground" />
                </button>
            )}

            <div className="flex flex-col gap-3">
                <Button
                    size="lg"
                    className="h-14 w-full text-base"
                    onClick={() =>
                        navigate({
                            name: data.activePatrol ? 'patrol' : 'rounds',
                        })
                    }
                >
                    <Route className="size-5" />
                    {data.activePatrol
                        ? 'Continuar recorrido'
                        : 'Iniciar recorrido'}
                </Button>

                <Button
                    size="lg"
                    variant="outline"
                    className="h-14 w-full text-base"
                    disabled={!area}
                    onClick={() =>
                        navigate({ name: 'incident', checkpointId: null })
                    }
                >
                    <AlertTriangle className="size-5" />
                    Reportar incidencia
                </Button>

                <Button
                    size="lg"
                    variant="destructive"
                    className="h-14 w-full text-base"
                    disabled={!area}
                    onClick={() => setPanicOpen(true)}
                >
                    <Siren className="size-5" />
                    Botón de pánico
                </Button>
            </div>

            {area && (
                <PanicDialog
                    open={panicOpen}
                    onOpenChange={setPanicOpen}
                    online={data.online}
                    userId={pkg.user.id}
                    userName={pkg.user.name}
                    area={area}
                    activePatrol={data.activePatrol}
                />
            )}
        </div>
    );
}
