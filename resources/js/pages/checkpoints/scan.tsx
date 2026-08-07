import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import {
    allClear,
    store,
} from '@/actions/App/Http/Controllers/CheckpointScanController';
import CheckpointPhotoPicker from '@/components/checkpoint-photo-picker';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import UrgentReviewFields from '@/components/urgent-review-fields';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { show as showPatrol } from '@/routes/guard/patrols';
import { incident as scanIncident } from '@/routes/checkpoints/scan';

type OptionRow = {
    id: number;
    label: string;
    position: number;
};

type QuestionRow = {
    id: number;
    body: string;
    position: number;
    options: OptionRow[];
};

type ConfirmAction = 'questionnaire' | 'all_clear' | null;

export default function CheckpointScan({
    area,
    round,
    checkpoint,
    questions,
    patrol,
}: {
    area: { id: number; name: string; code: string };
    round: { id: number; title: string };
    checkpoint: {
        id: number;
        name: string;
        instructions: string | null;
        token: string;
    };
    questions: QuestionRow[];
    patrol: { id: number; already_reviewed: boolean } | null;
}) {
    const [photos, setPhotos] = useState<File[]>([]);
    const [isUrgent, setIsUrgent] = useState(false);
    const [urgentNotes, setUrgentNotes] = useState('');
    const [confirmAction, setConfirmAction] = useState<ConfirmAction>(null);

    const form = useForm<{
        answers: Record<string, number | null>;
        photos: File[];
        is_urgent: boolean;
        urgent_notes: string;
    }>({
        answers: Object.fromEntries(
            questions.map((question) => [String(question.id), null]),
        ),
        photos: [],
        is_urgent: false,
        urgent_notes: '',
    });

    const allClearForm = useForm<{
        photos: File[];
        is_urgent: boolean;
        urgent_notes: string;
    }>({
        photos: [],
        is_urgent: false,
        urgent_notes: '',
    });

    const busy = form.processing || allClearForm.processing;

    const photoErrors = {
        ...Object.fromEntries(
            Object.entries(form.errors).filter(([key]) =>
                key.startsWith('photos'),
            ),
        ),
        ...Object.fromEntries(
            Object.entries(allClearForm.errors).filter(([key]) =>
                key.startsWith('photos'),
            ),
        ),
    };

    const urgentErrors = {
        ...Object.fromEntries(
            Object.entries(form.errors).filter(
                ([key]) => key.startsWith('urgent') || key === 'is_urgent',
            ),
        ),
        ...Object.fromEntries(
            Object.entries(allClearForm.errors).filter(
                ([key]) => key.startsWith('urgent') || key === 'is_urgent',
            ),
        ),
    };

    const confirmCopy: Record<
        Exclude<ConfirmAction, null>,
        { title: string; description: string; confirmLabel: string }
    > = {
        questionnaire: {
            title: '¿Enviar respuestas?',
            description:
                'Se registrará el cuestionario de este punto. Esta acción no se puede deshacer.',
            confirmLabel: 'Sí, enviar respuestas',
        },
        all_clear: {
            title: '¿Marcar área sin novedad?',
            description:
                'Se registrará este punto como sin novedad. Asegúrate de no querer enviar el cuestionario en su lugar.',
            confirmLabel: 'Sí, área sin novedad',
        },
    };

    const submitQuestionnaire = () => {
        form.transform((data) => ({
            ...data,
            photos,
            is_urgent: isUrgent,
            urgent_notes: isUrgent ? urgentNotes : '',
        }));
        form.post(store.url(checkpoint.token), {
            forceFormData: true,
            preserveScroll: true,
        });
    };

    const submitAllClear = () => {
        allClearForm.transform(() => ({
            photos,
            is_urgent: isUrgent,
            urgent_notes: isUrgent ? urgentNotes : '',
        }));
        allClearForm.post(allClear.url(checkpoint.token), {
            forceFormData: true,
        });
    };

    const handleConfirm = () => {
        const action = confirmAction;
        setConfirmAction(null);

        if (action === 'questionnaire') {
            submitQuestionnaire();
        }

        if (action === 'all_clear') {
            submitAllClear();
        }
    };

    if (patrol?.already_reviewed) {
        return (
            <>
                <Head title={`Punto · ${checkpoint.name}`} />

                <div className="mx-auto flex w-full max-w-lg flex-col gap-6 p-4">
                    <Heading
                        title={checkpoint.name}
                        description={`${area.name} · ${round.title}`}
                    />

                    <p className="text-sm text-muted-foreground">
                        Este punto ya fue revisado en el recorrido actual.
                    </p>

                    <Button asChild className="w-full">
                        <Link href={showPatrol(patrol.id)}>
                            Volver al recorrido
                        </Link>
                    </Button>
                </div>
            </>
        );
    }

    const dialog = confirmAction ? confirmCopy[confirmAction] : null;

    return (
        <>
            <Head title={`Punto · ${checkpoint.name}`} />

            <div className="mx-auto flex w-full max-w-lg flex-col gap-6 p-4">
                <Heading
                    title={checkpoint.name}
                    description={`${area.name} · ${round.title}`}
                />

                {checkpoint.instructions && (
                    <p className="text-sm text-muted-foreground">
                        {checkpoint.instructions}
                    </p>
                )}

                {questions.length > 0 && (
                    <div className="space-y-6">
                        {questions.map((question) => (
                            <fieldset
                                key={question.id}
                                className="space-y-3 rounded-xl border p-4"
                            >
                                <legend className="px-1 text-base font-medium">
                                    {question.body}
                                </legend>

                                <div className="space-y-2">
                                    {question.options.map((option) => {
                                        const inputId = `q-${question.id}-o-${option.id}`;

                                        return (
                                            <div
                                                key={option.id}
                                                className="flex items-center gap-3"
                                            >
                                                <input
                                                    id={inputId}
                                                    type="radio"
                                                    name={`answers[${question.id}]`}
                                                    value={option.id}
                                                    checked={
                                                        form.data.answers[
                                                            String(question.id)
                                                        ] === option.id
                                                    }
                                                    onChange={() =>
                                                        form.setData(
                                                            'answers',
                                                            {
                                                                ...form.data
                                                                    .answers,
                                                                [String(
                                                                    question.id,
                                                                )]: option.id,
                                                            },
                                                        )
                                                    }
                                                    className="size-4 accent-primary"
                                                    required
                                                />
                                                <Label
                                                    htmlFor={inputId}
                                                    className="font-normal"
                                                >
                                                    {option.label}
                                                </Label>
                                            </div>
                                        );
                                    })}
                                </div>

                                <InputError
                                    message={
                                        form.errors[
                                            `answers.${question.id}`
                                        ] ??
                                        form.errors[
                                            `answers.${String(question.id)}`
                                        ]
                                    }
                                />
                            </fieldset>
                        ))}

                        <InputError message={form.errors.answers} />
                        <InputError message={form.errors.patrol} />
                    </div>
                )}

                <div className="space-y-3 border-t pt-4">
                    <CheckpointPhotoPicker
                        photos={photos}
                        onChange={setPhotos}
                        errors={photoErrors}
                        disabled={busy || !patrol}
                    />

                    <UrgentReviewFields
                        isUrgent={isUrgent}
                        urgentNotes={urgentNotes}
                        onUrgentChange={setIsUrgent}
                        onNotesChange={setUrgentNotes}
                        errors={urgentErrors}
                        disabled={busy || !patrol}
                    />

                    {questions.length === 0 && (
                        <p className="text-sm text-muted-foreground">
                            Este punto no tiene cuestionario. Confirma el
                            estado del área.
                        </p>
                    )}

                    {questions.length > 0 && (
                        <Button
                            type="button"
                            className="w-full"
                            disabled={busy || !patrol}
                            onClick={() => setConfirmAction('questionnaire')}
                        >
                            {form.processing && <Spinner />}
                            Enviar respuestas
                        </Button>
                    )}

                    <Button
                        type="button"
                        className="w-full"
                        variant={questions.length > 0 ? 'secondary' : 'default'}
                        disabled={busy || !patrol}
                        onClick={() => setConfirmAction('all_clear')}
                    >
                        {allClearForm.processing && <Spinner />}
                        Área sin novedad
                    </Button>

                    <Button
                        type="button"
                        variant="outline"
                        className="w-full"
                        disabled={busy || !patrol}
                        onClick={() =>
                            router.visit(scanIncident.url(checkpoint.token))
                        }
                    >
                        Reportar incidencia
                    </Button>

                    <InputError message={allClearForm.errors.checkpoint} />
                    <InputError message={allClearForm.errors.patrol} />

                    {!patrol && (
                        <p className="text-sm text-destructive">
                            Debes iniciar un recorrido antes de revisar este
                            punto.
                        </p>
                    )}
                </div>
            </div>

            <Dialog
                open={confirmAction !== null}
                onOpenChange={(open) => {
                    if (!open) {
                        setConfirmAction(null);
                    }
                }}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{dialog?.title}</DialogTitle>
                        <DialogDescription>
                            {dialog?.description}
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setConfirmAction(null)}
                        >
                            Cancelar
                        </Button>
                        <Button type="button" onClick={handleConfirm}>
                            {dialog?.confirmLabel}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

CheckpointScan.layout = {
    breadcrumbs: [
        {
            title: 'Punto de revisión',
            href: '#',
        },
    ],
};
