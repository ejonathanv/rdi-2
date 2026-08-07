import { Form, Head, Link } from '@inertiajs/react';
import IncidentCategoryController from '@/actions/App/Http/Controllers/IncidentCategoryController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import {
    create as categoriesCreate,
    index as categoriesIndex,
} from '@/routes/incident-categories';

type AreaSummary = {
    id: number;
    name: string;
    code: string;
};

export default function IncidentCategoriesCreate({
    area,
}: {
    area: AreaSummary;
}) {
    return (
        <>
            <Head title="Nueva categoría" />

            <div className="flex flex-col gap-6 p-4">
                <Heading
                    title="Nueva categoría"
                    description={`Área: ${area.name}`}
                />

                <Form
                    {...IncidentCategoryController.store.form()}
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
                                <Label htmlFor="name">Nombre</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    required
                                    placeholder="Accidente"
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="code">Código</Label>
                                <Input
                                    id="code"
                                    name="code"
                                    required
                                    placeholder="ACCIDENTE"
                                />
                                <p className="text-xs text-muted-foreground">
                                    Se normaliza a MAYÚSCULAS_CON_GUION_BAJO.
                                </p>
                                <InputError message={errors.code} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="description">Descripción</Label>
                                <textarea
                                    id="description"
                                    name="description"
                                    rows={4}
                                    className="border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 flex w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-[3px]"
                                    placeholder="Ayuda a OpenAI a clasificar este tipo de incidencia…"
                                />
                                <InputError message={errors.description} />
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
                                <Label htmlFor="is_active">Activa</Label>
                            </div>

                            <div className="flex gap-3">
                                <Button type="submit" disabled={processing}>
                                    {processing && <Spinner />}
                                    Crear categoría
                                </Button>
                                <Button variant="outline" asChild>
                                    <Link href={categoriesIndex()}>
                                        Cancelar
                                    </Link>
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

IncidentCategoriesCreate.layout = {
    breadcrumbs: [
        {
            title: 'Categorías',
            href: categoriesIndex(),
        },
        {
            title: 'Crear',
            href: categoriesCreate(),
        },
    ],
};
