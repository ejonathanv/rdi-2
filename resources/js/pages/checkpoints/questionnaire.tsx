import { Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowDown, ArrowUp, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import {
    store,
    update,
} from '@/actions/App/Http/Controllers/CheckpointQuestionController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { reorder as reorderQuestions } from '@/routes/checkpoints/questions';
import { destroy as destroyQuestion } from '@/routes/questions';
import { edit as editRound, index as roundsIndex } from '@/routes/rounds';

type AreaSummary = {
    id: number;
    name: string;
    code: string;
};

type RoundSummary = {
    id: number;
    title: string;
};

type CheckpointSummary = {
    id: number;
    name: string;
    instructions: string | null;
};

type OptionRow = {
    id?: number;
    label: string;
    position?: number;
};

type QuestionRow = {
    id: number;
    body: string;
    position: number;
    is_active: boolean;
    options: OptionRow[];
};

export default function CheckpointQuestionnaire({
    area,
    round,
    checkpoint,
    questions,
}: {
    area: AreaSummary;
    round: RoundSummary;
    checkpoint: CheckpointSummary;
    questions: QuestionRow[];
}) {
    const [editingId, setEditingId] = useState<number | null>(null);

    const createForm = useForm({
        body: '',
        is_active: true as boolean,
        options: ['Sí', 'No'] as string[],
    });

    const editForm = useForm({
        body: '',
        is_active: true as boolean,
        options: ['Sí', 'No'] as string[],
    });

    function startEdit(question: QuestionRow) {
        setEditingId(question.id);
        editForm.setData({
            body: question.body,
            is_active: question.is_active,
            options: question.options.map((option) => option.label),
        });
        editForm.clearErrors();
    }

    function moveQuestion(index: number, direction: -1 | 1) {
        const nextIndex = index + direction;
        if (nextIndex < 0 || nextIndex >= questions.length) {
            return;
        }

        const order = questions.map((question) => question.id);
        const [moved] = order.splice(index, 1);
        order.splice(nextIndex, 0, moved);

        router.put(
            reorderQuestions.url(checkpoint),
            { order },
            { preserveScroll: true },
        );
    }

    return (
        <>
            <Head title={`Cuestionario · ${checkpoint.name}`} />

            <div className="flex flex-col gap-8 p-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <Heading
                        title="Configurar cuestionario"
                        description={`${area.name} · ${round.title} · ${checkpoint.name}`}
                    />
                    <Button variant="outline" asChild>
                        <Link href={editRound(round)}>Volver al recorrido</Link>
                    </Button>
                </div>

                <div className="space-y-4">
                    <Heading
                        variant="small"
                        title="Preguntas del cuestionario"
                        description="El guardia podrá responder estas preguntas de opción múltiple al escanear el QR de este punto."
                    />

                    {questions.length === 0 && (
                        <p className="text-sm text-muted-foreground">
                            Todavía no hay preguntas. Agrega la primera abajo.
                        </p>
                    )}

                    {questions.map((question, index) => (
                        <div
                            key={question.id}
                            className="rounded-xl border p-4"
                        >
                            {editingId === question.id ? (
                                <form
                                    className="space-y-4"
                                    onSubmit={(event) => {
                                        event.preventDefault();
                                        editForm.put(update.url(question), {
                                            preserveScroll: true,
                                            onSuccess: () => setEditingId(null),
                                        });
                                    }}
                                >
                                    <QuestionFields
                                        body={editForm.data.body}
                                        options={editForm.data.options}
                                        isActive={editForm.data.is_active}
                                        errors={editForm.errors}
                                        onBodyChange={(value) =>
                                            editForm.setData('body', value)
                                        }
                                        onOptionsChange={(options) =>
                                            editForm.setData('options', options)
                                        }
                                        onActiveChange={(value) =>
                                            editForm.setData(
                                                'is_active',
                                                value,
                                            )
                                        }
                                    />
                                    <div className="flex gap-2">
                                        <Button
                                            type="submit"
                                            disabled={editForm.processing}
                                        >
                                            {editForm.processing && <Spinner />}
                                            Guardar pregunta
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            onClick={() => setEditingId(null)}
                                        >
                                            Cancelar
                                        </Button>
                                    </div>
                                </form>
                            ) : (
                                <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                    <div className="space-y-3">
                                        <div className="flex flex-wrap items-center gap-2">
                                            <span className="text-xs text-muted-foreground">
                                                #{question.position}
                                            </span>
                                            <p className="font-medium">
                                                {question.body}
                                            </p>
                                            <Badge
                                                variant={
                                                    question.is_active
                                                        ? 'default'
                                                        : 'secondary'
                                                }
                                            >
                                                {question.is_active
                                                    ? 'Activa'
                                                    : 'Inactiva'}
                                            </Badge>
                                        </div>
                                        <ul className="space-y-1 text-sm text-muted-foreground">
                                            {question.options.map((option) => (
                                                <li
                                                    key={`${question.id}-${option.position}-${option.label}`}
                                                >
                                                    ( ) {option.label}
                                                </li>
                                            ))}
                                        </ul>
                                    </div>
                                    <div className="flex flex-wrap gap-2">
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="icon"
                                            disabled={index === 0}
                                            onClick={() =>
                                                moveQuestion(index, -1)
                                            }
                                        >
                                            <ArrowUp className="size-4" />
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="icon"
                                            disabled={
                                                index === questions.length - 1
                                            }
                                            onClick={() =>
                                                moveQuestion(index, 1)
                                            }
                                        >
                                            <ArrowDown className="size-4" />
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            onClick={() => startEdit(question)}
                                        >
                                            Editar
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="destructive"
                                            size="icon"
                                            onClick={() => {
                                                if (
                                                    confirm(
                                                        '¿Eliminar esta pregunta?',
                                                    )
                                                ) {
                                                    router.delete(
                                                        destroyQuestion(
                                                            question,
                                                        ),
                                                    );
                                                }
                                            }}
                                        >
                                            <Trash2 className="size-4" />
                                        </Button>
                                    </div>
                                </div>
                            )}
                        </div>
                    ))}
                </div>

                <form
                    className="max-w-2xl space-y-4 rounded-xl border p-4"
                    onSubmit={(event) => {
                        event.preventDefault();
                        createForm.post(store.url(checkpoint), {
                            preserveScroll: true,
                            onSuccess: () => {
                                createForm.setData({
                                    body: '',
                                    is_active: true,
                                    options: ['Sí', 'No'],
                                });
                                createForm.clearErrors();
                            },
                        });
                    }}
                >
                    <div className="flex items-center gap-2">
                        <Plus className="size-4" />
                        <Heading
                            variant="small"
                            title="Agregar pregunta"
                            description="Redacta la pregunta y define al menos dos opciones"
                        />
                    </div>

                    <QuestionFields
                        body={createForm.data.body}
                        options={createForm.data.options}
                        isActive={createForm.data.is_active}
                        errors={createForm.errors}
                        onBodyChange={(value) =>
                            createForm.setData('body', value)
                        }
                        onOptionsChange={(options) =>
                            createForm.setData('options', options)
                        }
                        onActiveChange={(value) =>
                            createForm.setData('is_active', value)
                        }
                    />

                    <Button type="submit" disabled={createForm.processing}>
                        {createForm.processing && <Spinner />}
                        Agregar pregunta
                    </Button>
                </form>
            </div>
        </>
    );
}

function QuestionFields({
    body,
    options,
    isActive,
    errors,
    onBodyChange,
    onOptionsChange,
    onActiveChange,
}: {
    body: string;
    options: string[];
    isActive: boolean;
    errors: Record<string, string>;
    onBodyChange: (value: string) => void;
    onOptionsChange: (options: string[]) => void;
    onActiveChange: (value: boolean) => void;
}) {
    return (
        <div className="space-y-4">
            <div className="grid gap-2">
                <Label>Pregunta</Label>
                <Input
                    value={body}
                    onChange={(e) => onBodyChange(e.target.value)}
                    required
                    placeholder="¿Está el candado de la puerta bien cerrado?"
                />
                <InputError message={errors.body} />
            </div>

            <div className="space-y-3">
                <Label>Opciones de respuesta</Label>
                {options.map((option, index) => (
                    <div key={index} className="flex items-center gap-2">
                        <Input
                            value={option}
                            onChange={(e) => {
                                const next = [...options];
                                next[index] = e.target.value;
                                onOptionsChange(next);
                            }}
                            required
                            placeholder={
                                index === 0
                                    ? 'Sí'
                                    : index === 1
                                      ? 'No'
                                      : 'Otra opción'
                            }
                        />
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            disabled={options.length <= 2}
                            onClick={() =>
                                onOptionsChange(
                                    options.filter((_, i) => i !== index),
                                )
                            }
                        >
                            <Trash2 className="size-4" />
                        </Button>
                    </div>
                ))}
                <InputError message={errors.options} />
                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={() => onOptionsChange([...options, ''])}
                >
                    <Plus className="size-4" />
                    Agregar opción
                </Button>
            </div>

            <div className="flex items-center gap-3">
                <Checkbox
                    checked={isActive}
                    onCheckedChange={(checked) =>
                        onActiveChange(checked === true)
                    }
                />
                <Label>Pregunta activa</Label>
            </div>
        </div>
    );
}

CheckpointQuestionnaire.layout = {
    breadcrumbs: [
        {
            title: 'Recorridos',
            href: roundsIndex(),
        },
        {
            title: 'Cuestionario',
            href: '#',
        },
    ],
};
