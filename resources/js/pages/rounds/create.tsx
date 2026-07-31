import { Form, Head, Link } from '@inertiajs/react';
import RoundController from '@/actions/App/Http/Controllers/RoundController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { index as roundsIndex, create as roundsCreate } from '@/routes/rounds';

type AreaSummary = {
    id: number;
    name: string;
    code: string;
};

export default function RoundsCreate({ area }: { area: AreaSummary }) {
    return (
        <>
            <Head title="Nuevo recorrido" />

            <div className="flex flex-col gap-6 p-4">
                <Heading
                    title="Nuevo recorrido"
                    description={`Área: ${area.name}`}
                />

                <Form
                    {...RoundController.store.form()}
                    className="max-w-xl space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <input
                                type="hidden"
                                name="area_id"
                                value={area.id}
                            />

                            <div className="grid gap-2">
                                <Label htmlFor="title">Título</Label>
                                <Input
                                    id="title"
                                    name="title"
                                    required
                                    placeholder="Recorrido perimetral noche"
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
                                    className="border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 flex w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-[3px]"
                                    placeholder="Indica cómo debe realizarse este recorrido..."
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
                                    defaultChecked
                                />
                                <Label htmlFor="is_active">Activo</Label>
                            </div>

                            <div className="flex gap-3">
                                <Button type="submit" disabled={processing}>
                                    {processing && <Spinner />}
                                    Crear recorrido
                                </Button>
                                <Button variant="outline" asChild>
                                    <Link href={roundsIndex()}>Cancelar</Link>
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

RoundsCreate.layout = {
    breadcrumbs: [
        {
            title: 'Recorridos',
            href: roundsIndex(),
        },
        {
            title: 'Crear',
            href: roundsCreate(),
        },
    ],
};
