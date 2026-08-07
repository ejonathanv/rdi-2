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
    edit as categoriesEdit,
    index as categoriesIndex,
} from '@/routes/incident-categories';

type AreaSummary = {
    id: number;
    name: string;
    code: string;
};

type CategoryData = {
    id: number;
    name: string;
    code: string;
    description: string | null;
    is_active: boolean;
};

type ContactOption = {
    id: number;
    name: string;
    email: string;
    phone: string | null;
};

export default function IncidentCategoriesEdit({
    area,
    category,
    availableContacts,
    assignedContactIds,
}: {
    area: AreaSummary;
    category: CategoryData;
    availableContacts: ContactOption[];
    assignedContactIds: number[];
}) {
    return (
        <>
            <Head title={`Editar ${category.name}`} />

            <div className="flex flex-col gap-6 p-4">
                <Heading
                    title="Editar categoría"
                    description={`Área: ${area.name}`}
                />

                <Form
                    {...IncidentCategoryController.update.form(category)}
                    className="max-w-xl space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Nombre</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    required
                                    defaultValue={category.name}
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="code">Código</Label>
                                <Input
                                    id="code"
                                    name="code"
                                    required
                                    defaultValue={category.code}
                                />
                                <InputError message={errors.code} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="description">Descripción</Label>
                                <textarea
                                    id="description"
                                    name="description"
                                    rows={4}
                                    defaultValue={category.description ?? ''}
                                    className="border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 flex w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-[3px]"
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
                                    defaultChecked={category.is_active}
                                />
                                <Label htmlFor="is_active">Activa</Label>
                            </div>

                            <div className="space-y-3 rounded-xl border p-4">
                                <Heading
                                    variant="small"
                                    title="Contactos asignados"
                                    description="Reciben alertas cuando una incidencia se clasifica en esta categoría"
                                />

                                {availableContacts.length === 0 && (
                                    <p className="text-sm text-muted-foreground">
                                        No hay contactos en esta área. Crea un
                                        usuario con rol Contacto.
                                    </p>
                                )}

                                {availableContacts.map((contact) => (
                                    <div
                                        key={contact.id}
                                        className="flex items-start gap-3"
                                    >
                                        <Checkbox
                                            id={`contact-${contact.id}`}
                                            name="contact_ids[]"
                                            value={String(contact.id)}
                                            defaultChecked={assignedContactIds.includes(
                                                contact.id,
                                            )}
                                        />
                                        <div>
                                            <Label
                                                htmlFor={`contact-${contact.id}`}
                                            >
                                                {contact.name}
                                            </Label>
                                            <p className="text-sm text-muted-foreground">
                                                {contact.email}
                                                {contact.phone
                                                    ? ` · ${contact.phone}`
                                                    : ' · Sin teléfono'}
                                            </p>
                                        </div>
                                    </div>
                                ))}

                                <InputError message={errors.contact_ids} />
                            </div>

                            <div className="flex gap-3">
                                <Button type="submit" disabled={processing}>
                                    {processing && <Spinner />}
                                    Guardar categoría
                                </Button>
                                <Button variant="outline" asChild>
                                    <Link href={categoriesIndex()}>Volver</Link>
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

IncidentCategoriesEdit.layout = {
    breadcrumbs: [
        {
            title: 'Categorías',
            href: categoriesIndex(),
        },
        {
            title: 'Editar',
            href: categoriesEdit(1),
        },
    ],
};
