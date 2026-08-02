import { Form, Head, Link, router, useForm } from '@inertiajs/react';
import { ArrowDown, ArrowUp, Plus, Trash2 } from 'lucide-react';
import { useState } from 'react';
import CheckpointController from '@/actions/App/Http/Controllers/CheckpointController';
import RoundController from '@/actions/App/Http/Controllers/RoundController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { destroy as destroyCheckpoint } from '@/routes/checkpoints';
import { edit as editQuestionnaire } from '@/routes/checkpoints/questionnaire';
import { index as roundsIndex } from '@/routes/rounds';
import { reorder } from '@/routes/rounds/checkpoints';

type AreaSummary = {
    id: number;
    name: string;
    code: string;
};

type CheckpointRow = {
    id: number;
    name: string;
    instructions: string | null;
    position: number;
    token: string;
    is_active: boolean;
    questions_count: number;
};

type RoundData = {
    id: number;
    title: string;
    instructions: string | null;
    is_active: boolean;
    checkpoints: CheckpointRow[];
};

export default function RoundsEdit({
    area,
    round,
}: {
    area: AreaSummary;
    round: RoundData;
}) {
    const [editingId, setEditingId] = useState<number | null>(null);

    const checkpointForm = useForm({
        name: '',
        instructions: '',
        is_active: true as boolean,
    });

    const editForm = useForm({
        name: '',
        instructions: '',
        is_active: true as boolean,
    });

    function startEdit(checkpoint: CheckpointRow) {
        setEditingId(checkpoint.id);
        editForm.setData({
            name: checkpoint.name,
            instructions: checkpoint.instructions ?? '',
            is_active: checkpoint.is_active,
        });
    }

    function moveCheckpoint(index: number, direction: -1 | 1) {
        const nextIndex = index + direction;
        if (nextIndex < 0 || nextIndex >= round.checkpoints.length) {
            return;
        }

        const order = round.checkpoints.map((checkpoint) => checkpoint.id);
        const [moved] = order.splice(index, 1);
        order.splice(nextIndex, 0, moved);

        router.put(reorder.url(round), { order }, { preserveScroll: true });
    }

    return (
        <>
            <Head title={`Editar ${round.title}`} />

            <div className="flex flex-col gap-8 p-4">
                <Heading
                    title="Editar recorrido"
                    description={`${area.name} · ${round.title}`}
                />

                <Form
                    {...RoundController.update.form(round)}
                    className="max-w-xl space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="title">Título</Label>
                                <Input
                                    id="title"
                                    name="title"
                                    required
                                    defaultValue={round.title}
                                />
                                <InputError message={errors.title} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="instructions">
                                    Instrucciones
                                </Label>
                                <textarea
                                    id="instructions"
                                    name="instructions"
                                    rows={5}
                                    defaultValue={round.instructions ?? ''}
                                    className="border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 flex w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-[3px]"
                                />
                                <InputError message={errors.instructions} />
                            </div>

                            <div className="flex items-center gap-3">
                                <input
                                    type="hidden"
                                    name="is_active"
                                    value="0"
                                />
                                <Checkbox
                                    id="is_active"
                                    name="is_active"
                                    value="1"
                                    defaultChecked={round.is_active}
                                />
                                <Label htmlFor="is_active">Activo</Label>
                            </div>

                            <div className="flex gap-3">
                                <Button type="submit" disabled={processing}>
                                    {processing && <Spinner />}
                                    Guardar recorrido
                                </Button>
                                <Button variant="outline" asChild>
                                    <Link href={roundsIndex()}>Volver</Link>
                                </Button>
                            </div>
                        </>
                    )}
                </Form>

                <div className="space-y-4">
                    <Heading
                        variant="small"
                        title="Puntos de revisión"
                        description="Ordena los puntos que el guardia debe revisar. El token QR se usará en una fase posterior."
                    />

                    <div className="space-y-3">
                        {round.checkpoints.length === 0 && (
                            <p className="text-sm text-muted-foreground">
                                Todavía no hay puntos en este recorrido.
                            </p>
                        )}

                        {round.checkpoints.map((checkpoint, index) => (
                            <div
                                key={checkpoint.id}
                                className="rounded-xl border p-4"
                            >
                                {editingId === checkpoint.id ? (
                                    <form
                                        className="space-y-4"
                                        onSubmit={(event) => {
                                            event.preventDefault();
                                            editForm.put(
                                                CheckpointController.update.url(
                                                    checkpoint,
                                                ),
                                                {
                                                    preserveScroll: true,
                                                    onSuccess: () =>
                                                        setEditingId(null),
                                                },
                                            );
                                        }}
                                    >
                                        <div className="grid gap-2">
                                            <Label>Nombre</Label>
                                            <Input
                                                value={editForm.data.name}
                                                onChange={(e) =>
                                                    editForm.setData(
                                                        'name',
                                                        e.target.value,
                                                    )
                                                }
                                                required
                                            />
                                            <InputError
                                                message={editForm.errors.name}
                                            />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label>Instrucciones</Label>
                                            <textarea
                                                rows={3}
                                                value={
                                                    editForm.data.instructions
                                                }
                                                onChange={(e) =>
                                                    editForm.setData(
                                                        'instructions',
                                                        e.target.value,
                                                    )
                                                }
                                                className="border-input flex w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-[3px]"
                                            />
                                        </div>
                                        <div className="flex items-center gap-3">
                                            <Checkbox
                                                checked={
                                                    editForm.data.is_active
                                                }
                                                onCheckedChange={(checked) =>
                                                    editForm.setData(
                                                        'is_active',
                                                        checked === true,
                                                    )
                                                }
                                            />
                                            <Label>Activo</Label>
                                        </div>
                                        <div className="flex gap-2">
                                            <Button
                                                type="submit"
                                                disabled={editForm.processing}
                                            >
                                                Guardar punto
                                            </Button>
                                            <Button
                                                type="button"
                                                variant="outline"
                                                onClick={() =>
                                                    setEditingId(null)
                                                }
                                            >
                                                Cancelar
                                            </Button>
                                        </div>
                                    </form>
                                ) : (
                                    <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                        <div className="space-y-1">
                                            <div className="flex flex-wrap items-center gap-2">
                                                <span className="text-xs text-muted-foreground">
                                                    #{checkpoint.position}
                                                </span>
                                                <p className="font-medium">
                                                    {checkpoint.name}
                                                </p>
                                                <Badge
                                                    variant={
                                                        checkpoint.is_active
                                                            ? 'default'
                                                            : 'secondary'
                                                    }
                                                >
                                                    {checkpoint.is_active
                                                        ? 'Activo'
                                                        : 'Inactivo'}
                                                </Badge>
                                            </div>
                                            {checkpoint.instructions && (
                                                <p className="text-sm text-muted-foreground">
                                                    {checkpoint.instructions}
                                                </p>
                                            )}
                                            <p className="font-mono text-xs text-muted-foreground">
                                                Token: {checkpoint.token}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {checkpoint.questions_count}{' '}
                                                {checkpoint.questions_count ===
                                                1
                                                    ? 'pregunta'
                                                    : 'preguntas'}{' '}
                                                en el cuestionario
                                            </p>
                                        </div>
                                        <div className="flex flex-wrap gap-2">
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="icon"
                                                disabled={index === 0}
                                                onClick={() =>
                                                    moveCheckpoint(index, -1)
                                                }
                                            >
                                                <ArrowUp className="size-4" />
                                            </Button>
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="icon"
                                                disabled={
                                                    index ===
                                                    round.checkpoints.length -
                                                        1
                                                }
                                                onClick={() =>
                                                    moveCheckpoint(index, 1)
                                                }
                                            >
                                                <ArrowDown className="size-4" />
                                            </Button>
                                            <Button
                                                variant="secondary"
                                                size="sm"
                                                asChild
                                            >
                                                <Link
                                                    href={editQuestionnaire(
                                                        checkpoint,
                                                    )}
                                                >
                                                    Configurar cuestionario
                                                </Link>
                                            </Button>
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                onClick={() =>
                                                    startEdit(checkpoint)
                                                }
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
                                                            '¿Eliminar este punto de revisión?',
                                                        )
                                                    ) {
                                                        router.delete(
                                                            destroyCheckpoint(
                                                                checkpoint,
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
                        className="max-w-xl space-y-4 rounded-xl border p-4"
                        onSubmit={(event) => {
                            event.preventDefault();
                            checkpointForm.post(
                                CheckpointController.store.url(round),
                                {
                                    preserveScroll: true,
                                    onSuccess: () =>
                                        checkpointForm.reset(
                                            'name',
                                            'instructions',
                                        ),
                                },
                            );
                        }}
                    >
                        <div className="flex items-center gap-2">
                            <Plus className="size-4" />
                            <Heading
                                variant="small"
                                title="Agregar punto"
                                description="Define el siguiente punto de revisión"
                            />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="checkpoint_name">Nombre</Label>
                            <Input
                                id="checkpoint_name"
                                value={checkpointForm.data.name}
                                onChange={(e) =>
                                    checkpointForm.setData(
                                        'name',
                                        e.target.value,
                                    )
                                }
                                required
                                placeholder="Entrada principal"
                            />
                            <InputError
                                message={checkpointForm.errors.name}
                            />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="checkpoint_instructions">
                                Instrucciones
                            </Label>
                            <textarea
                                id="checkpoint_instructions"
                                rows={3}
                                value={checkpointForm.data.instructions}
                                onChange={(e) =>
                                    checkpointForm.setData(
                                        'instructions',
                                        e.target.value,
                                    )
                                }
                                className="border-input flex w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-[3px]"
                                placeholder="Qué debe revisar el guardia en este punto"
                            />
                        </div>

                        <div className="flex items-center gap-3">
                            <Checkbox
                                checked={checkpointForm.data.is_active}
                                onCheckedChange={(checked) =>
                                    checkpointForm.setData(
                                        'is_active',
                                        checked === true,
                                    )
                                }
                            />
                            <Label>Activo</Label>
                        </div>

                        <Button
                            type="submit"
                            disabled={checkpointForm.processing}
                        >
                            {checkpointForm.processing && <Spinner />}
                            Agregar punto
                        </Button>
                    </form>
                </div>
            </div>
        </>
    );
}

RoundsEdit.layout = {
    breadcrumbs: [
        {
            title: 'Recorridos',
            href: roundsIndex(),
        },
        {
            title: 'Editar',
            href: '#',
        },
    ],
};
