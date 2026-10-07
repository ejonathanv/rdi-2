import { AlertTriangle, CheckCircle2, Send } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
import CheckpointPhotoPicker from '@/components/checkpoint-photo-picker';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import UrgentReviewFields from '@/components/urgent-review-fields';
import { cn } from '@/lib/utils';
import { ConfirmDialog } from '../components/confirm-dialog';
import { recordAllClear, recordQuestionnaire } from '../store';
import type { GuardData } from '../use-guard-data';
import type { GuardRoute } from '../use-hash-route';

type PendingAction = 'questionnaire' | 'all_clear' | null;

export function CheckpointScreen({
    data,
    checkpointId,
    navigate,
}: {
    data: GuardData;
    checkpointId: number;
    navigate: (route: GuardRoute) => void;
}) {
    const [answers, setAnswers] = useState<Record<number, number>>({});
    const [photos, setPhotos] = useState<File[]>([]);
    const [isUrgent, setIsUrgent] = useState(false);
    const [urgentNotes, setUrgentNotes] = useState('');
    const [errors, setErrors] = useState<Record<string, string>>({});
    const [pendingAction, setPendingAction] = useState<PendingAction>(null);
    const [processing, setProcessing] = useState(false);

    const patrol = data.activePatrol;
    const round = patrol
        ? data.pkg!.rounds.find((item) => item.id === patrol.round_id)
        : null;
    const checkpoint = round?.checkpoints.find(
        (item) => item.id === checkpointId,
    );
    const alreadyVisited = patrol?.visits.some(
        (visit) => visit.checkpoint_id === checkpointId,
    );

    if (!patrol || !round || !checkpoint || alreadyVisited) {
        return (
            <div className="flex flex-col gap-4">
                <Heading
                    title="Punto no disponible"
                    description={
                        alreadyVisited
                            ? 'Este punto ya fue revisado en el recorrido actual.'
                            : 'Escanea el QR de un punto pendiente de tu recorrido en curso.'
                    }
                />
                <Button onClick={() => navigate({ name: 'patrol' })}>
                    Volver al recorrido
                </Button>
            </div>
        );
    }

    const validate = (action: Exclude<PendingAction, null>): boolean => {
        const nextErrors: Record<string, string> = {};

        if (action === 'questionnaire') {
            checkpoint.questions.forEach((question) => {
                if (!answers[question.id]) {
                    nextErrors[`answers.${question.id}`] =
                        'Debes responder todas las preguntas.';
                }
            });
        }

        if (isUrgent && urgentNotes.trim() === '') {
            nextErrors.urgent_notes =
                'Agrega notas cuando marques el punto como urgente.';
        }

        setErrors(nextErrors);

        return Object.keys(nextErrors).length === 0;
    };

    const submit = async () => {
        if (!pendingAction) {
            return;
        }

        setProcessing(true);

        const input = {
            patrol,
            round,
            checkpoint,
            isUrgent,
            urgentNotes: urgentNotes.trim(),
            photos,
        };

        try {
            const updated =
                pendingAction === 'questionnaire'
                    ? await recordQuestionnaire({ ...input, answers })
                    : await recordAllClear(input);

            toast.success(
                updated.finished_at
                    ? 'Recorrido completado.'
                    : data.online
                      ? 'Punto registrado.'
                      : 'Punto guardado. Se enviará al recuperar la red.',
            );
            navigate({ name: 'patrol' });
        } catch (error) {
            toast.error((error as Error).message);
        } finally {
            setProcessing(false);
            setPendingAction(null);
        }
    };

    const hasQuestions = checkpoint.questions.length > 0;

    return (
        <div className="flex flex-col gap-6">
            <Heading
                title={checkpoint.name}
                description={checkpoint.instructions ?? round.title}
            />

            {hasQuestions && (
                <div className="space-y-5">
                    {checkpoint.questions.map((question) => (
                        <fieldset key={question.id} className="space-y-2">
                            <legend className="text-sm font-medium">
                                {question.body}
                            </legend>
                            <div className="grid gap-2">
                                {question.options.map((option) => (
                                    <button
                                        key={option.id}
                                        type="button"
                                        disabled={processing}
                                        onClick={() =>
                                            setAnswers((current) => ({
                                                ...current,
                                                [question.id]: option.id,
                                            }))
                                        }
                                        className={cn(
                                            'rounded-lg border px-3 py-2.5 text-left text-sm',
                                            answers[question.id] ===
                                                option.id &&
                                                'border-primary bg-primary/10 font-medium',
                                        )}
                                    >
                                        {option.label}
                                    </button>
                                ))}
                            </div>
                            {errors[`answers.${question.id}`] && (
                                <p className="text-sm text-destructive">
                                    {errors[`answers.${question.id}`]}
                                </p>
                            )}
                        </fieldset>
                    ))}
                </div>
            )}

            <CheckpointPhotoPicker
                photos={photos}
                onChange={setPhotos}
                disabled={processing}
            />

            <UrgentReviewFields
                isUrgent={isUrgent}
                urgentNotes={urgentNotes}
                onUrgentChange={setIsUrgent}
                onNotesChange={setUrgentNotes}
                errors={errors}
                disabled={processing}
            />

            <div className="flex flex-col gap-3">
                {hasQuestions && (
                    <Button
                        size="lg"
                        disabled={processing}
                        onClick={() =>
                            validate('questionnaire') &&
                            setPendingAction('questionnaire')
                        }
                    >
                        <Send className="size-4" />
                        Enviar respuestas
                    </Button>
                )}
                <Button
                    size="lg"
                    variant={hasQuestions ? 'outline' : 'default'}
                    disabled={processing}
                    onClick={() =>
                        validate('all_clear') && setPendingAction('all_clear')
                    }
                >
                    <CheckCircle2 className="size-4" />
                    Área sin novedad
                </Button>
                <Button
                    size="lg"
                    variant="outline"
                    disabled={processing}
                    onClick={() =>
                        navigate({
                            name: 'incident',
                            checkpointId: checkpoint.id,
                        })
                    }
                >
                    <AlertTriangle className="size-4" />
                    Reportar incidencia
                </Button>
            </div>

            <ConfirmDialog
                open={pendingAction !== null}
                title={
                    pendingAction === 'questionnaire'
                        ? '¿Enviar respuestas?'
                        : '¿Marcar área sin novedad?'
                }
                description="Una vez registrado, el punto no se puede volver a revisar en este recorrido."
                confirmLabel="Confirmar"
                processing={processing}
                onCancel={() => setPendingAction(null)}
                onConfirm={() => void submit()}
            />
        </div>
    );
}
