import { Head, useForm } from '@inertiajs/react';
import { store } from '@/actions/App/Http/Controllers/CheckpointScanController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

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

export default function CheckpointScan({
    area,
    round,
    checkpoint,
    questions,
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
}) {
    const form = useForm<{
        answers: Record<string, number | null>;
    }>({
        answers: Object.fromEntries(
            questions.map((question) => [String(question.id), null]),
        ),
    });

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

                {questions.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                        Este punto aún no tiene preguntas activas en el
                        cuestionario.
                    </p>
                ) : (
                    <form
                        className="space-y-6"
                        onSubmit={(event) => {
                            event.preventDefault();
                            form.post(store.url(checkpoint.token), {
                                preserveScroll: true,
                            });
                        }}
                    >
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

                        <Button
                            type="submit"
                            className="w-full"
                            disabled={form.processing}
                        >
                            {form.processing && <Spinner />}
                            Enviar respuestas
                        </Button>
                    </form>
                )}
            </div>
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
