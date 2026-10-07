import { useState } from 'react';
import { toast } from 'sonner';
import CheckpointPhotoPicker from '@/components/checkpoint-photo-picker';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { ConfirmDialog } from '../components/confirm-dialog';
import { reportIncident } from '../store';
import type { GuardData } from '../use-guard-data';
import type { GuardRoute } from '../use-hash-route';

export function IncidentScreen({
    data,
    checkpointId,
    navigate,
}: {
    data: GuardData;
    checkpointId: number | null;
    navigate: (route: GuardRoute) => void;
}) {
    const [message, setMessage] = useState('');
    const [isUrgent, setIsUrgent] = useState(false);
    const [photos, setPhotos] = useState<File[]>([]);
    const [error, setError] = useState<string | null>(null);
    const [confirmOpen, setConfirmOpen] = useState(false);
    const [processing, setProcessing] = useState(false);

    const pkg = data.pkg!;
    const patrol = data.activePatrol;
    const round = patrol
        ? pkg.rounds.find((item) => item.id === patrol.round_id)
        : null;
    const checkpoint = checkpointId
        ? round?.checkpoints.find((item) => item.id === checkpointId)
        : null;
    const area =
        checkpoint && round
            ? pkg.areas.find((item) => item.id === round.area_id)
            : pkg.areas.find((item) => item.id === data.selectedAreaId);

    const invalidCheckpoint =
        checkpointId !== null &&
        (!checkpoint ||
            patrol?.visits.some(
                (visit) => visit.checkpoint_id === checkpointId,
            ));

    const back = () =>
        navigate(
            checkpointId
                ? { name: 'checkpoint', checkpointId }
                : { name: 'home' },
        );

    if (invalidCheckpoint || !area) {
        return (
            <div className="flex flex-col gap-4">
                <Heading
                    title="No se puede reportar"
                    description="El punto ya fue revisado o no pertenece a tu recorrido en curso."
                />
                <Button onClick={() => navigate({ name: 'home' })}>
                    Volver al inicio
                </Button>
            </div>
        );
    }

    const submit = async () => {
        setProcessing(true);

        try {
            await reportIncident({
                userId: pkg.user.id,
                areaId: area.id,
                message: message.trim(),
                isUrgent,
                photos,
                checkpointContext:
                    checkpoint && patrol && round
                        ? { patrol, round, checkpoint }
                        : undefined,
            });

            toast.success(
                data.online
                    ? 'Incidencia enviada.'
                    : 'Incidencia guardada. Se enviará al recuperar la red.',
            );
            navigate(checkpoint ? { name: 'patrol' } : { name: 'home' });
        } catch (exception) {
            toast.error((exception as Error).message);
        } finally {
            setProcessing(false);
            setConfirmOpen(false);
        }
    };

    return (
        <div className="flex flex-col gap-6">
            <Heading
                title="Reportar incidencia"
                description={
                    checkpoint
                        ? `${checkpoint.name} · ${round?.title}`
                        : `Área: ${area.name}`
                }
            />

            <div className="grid gap-2">
                <Label htmlFor="message">¿Qué está pasando?</Label>
                <textarea
                    id="message"
                    rows={5}
                    value={message}
                    maxLength={5000}
                    disabled={processing}
                    onChange={(event) => setMessage(event.target.value)}
                    className="flex w-full rounded-md border border-input bg-transparent px-3 py-2 text-base shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                    placeholder="Describe la incidencia con el mayor detalle posible."
                />
                {error && <p className="text-sm text-destructive">{error}</p>}
            </div>

            <div className="flex items-center gap-3 rounded-xl border p-4">
                <Checkbox
                    id="incident_urgent"
                    checked={isUrgent}
                    disabled={processing}
                    onCheckedChange={(checked) => setIsUrgent(checked === true)}
                />
                <Label htmlFor="incident_urgent" className="text-destructive">
                    Es urgente
                </Label>
            </div>

            <CheckpointPhotoPicker
                photos={photos}
                onChange={setPhotos}
                disabled={processing}
            />

            <div className="flex flex-col gap-3">
                <Button
                    size="lg"
                    disabled={processing}
                    onClick={() => {
                        if (message.trim() === '') {
                            setError('Describe la incidencia.');

                            return;
                        }

                        setError(null);
                        setConfirmOpen(true);
                    }}
                >
                    Enviar incidencia
                </Button>
                <Button
                    size="lg"
                    variant="outline"
                    disabled={processing}
                    onClick={back}
                >
                    Cancelar
                </Button>
            </div>

            <ConfirmDialog
                open={confirmOpen}
                title="¿Enviar incidencia?"
                description={
                    checkpoint
                        ? 'El punto quedará registrado con esta incidencia.'
                        : 'Se notificará a los responsables del área.'
                }
                confirmLabel="Enviar"
                processing={processing}
                onCancel={() => setConfirmOpen(false)}
                onConfirm={() => void submit()}
            />
        </div>
    );
}
