import { CheckCircle2, Circle, QrCode } from 'lucide-react';
import { useRef, useState } from 'react';
import Heading from '@/components/heading';
import { QrScanner } from '@/components/qr-scanner';
import { Button } from '@/components/ui/button';
import { canHashInBrowser } from '@/lib/sha256';
import { matchScannedToken } from '../store';
import type { GuardData } from '../use-guard-data';
import type { GuardRoute } from '../use-hash-route';

const outcomeLabels = {
    questionnaire: 'Cuestionario',
    all_clear: 'Sin novedad',
    incident: 'Incidencia',
} as const;

export function PatrolScreen({
    data,
    navigate,
}: {
    data: GuardData;
    navigate: (route: GuardRoute) => void;
}) {
    const [scanning, setScanning] = useState(false);
    const scannedCheckpointId = useRef<number | null>(null);
    const patrol = data.activePatrol;
    const round = patrol
        ? data.pkg!.rounds.find((item) => item.id === patrol.round_id)
        : null;

    if (!patrol || !round) {
        const lastPatrol = [...data.patrols].sort((a, b) =>
            b.started_at.localeCompare(a.started_at),
        )[0];

        return (
            <div className="flex flex-col gap-4">
                <Heading
                    title={
                        lastPatrol?.finished_at
                            ? 'Recorrido finalizado'
                            : 'Sin recorrido en curso'
                    }
                    description={
                        lastPatrol?.finished_at
                            ? 'Revisaste todos los puntos. Se enviará al recuperar la red si estás sin conexión.'
                            : 'Inicia un recorrido para revisar puntos.'
                    }
                />
                <Button onClick={() => navigate({ name: 'rounds' })}>
                    Ver recorridos
                </Button>
                <Button
                    variant="outline"
                    onClick={() => navigate({ name: 'home' })}
                >
                    Volver al inicio
                </Button>
            </div>
        );
    }

    const visits = new Map(
        patrol.visits.map((visit) => [visit.checkpoint_id, visit]),
    );

    return (
        <div className="flex flex-col gap-6">
            <Heading
                title={round.title}
                description={`${patrol.visits.length} de ${round.checkpoints.length} puntos revisados · Inicio ${new Date(patrol.started_at).toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' })}`}
            />

            <Button
                size="lg"
                className="h-14 w-full text-base"
                onClick={() => setScanning(true)}
            >
                <QrCode className="size-5" />
                Escanear punto
            </Button>

            <ul className="flex flex-col gap-2">
                {round.checkpoints.map((checkpoint) => {
                    const visit = visits.get(checkpoint.id);

                    return (
                        <li
                            key={checkpoint.id}
                            className="flex items-center gap-3 rounded-xl border p-3"
                        >
                            {visit ? (
                                <CheckCircle2 className="size-5 shrink-0 text-emerald-600" />
                            ) : (
                                <Circle className="size-5 shrink-0 text-muted-foreground" />
                            )}
                            <div className="min-w-0 flex-1">
                                <p className="truncate font-medium">
                                    {checkpoint.name}
                                </p>
                                <p className="text-xs text-muted-foreground">
                                    {visit
                                        ? `${outcomeLabels[visit.outcome]} · ${new Date(visit.reviewed_at).toLocaleTimeString('es-MX', { hour: '2-digit', minute: '2-digit' })}${visit.is_urgent ? ' · Urgente' : ''}`
                                        : 'Pendiente'}
                                </p>
                            </div>
                        </li>
                    );
                })}
            </ul>

            {scanning && (
                <QrScanner
                    onClose={() => setScanning(false)}
                    onDetected={async (token) => {
                        if (!canHashInBrowser()) {
                            return 'Este navegador no puede validar el QR. Abre la app con HTTPS.';
                        }

                        const result = await matchScannedToken(round.id, token);

                        if (result.kind === 'not_found') {
                            return 'QR no válido o desactualizado para este recorrido.';
                        }

                        if (visits.has(result.checkpoint.id)) {
                            return 'Este punto ya fue revisado en el recorrido actual.';
                        }

                        scannedCheckpointId.current = result.checkpoint.id;

                        return null;
                    }}
                    onAccepted={() => {
                        setScanning(false);

                        if (scannedCheckpointId.current !== null) {
                            navigate({
                                name: 'checkpoint',
                                checkpointId: scannedCheckpointId.current,
                            });
                        }
                    }}
                />
            )}
        </div>
    );
}
