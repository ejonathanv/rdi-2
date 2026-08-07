import { Head, Link, router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    create as categoriesCreate,
    destroy as categoriesDestroy,
    edit as categoriesEdit,
    index as categoriesIndex,
} from '@/routes/incident-categories';

type AreaSummary = {
    id: number;
    name: string;
    code: string;
};

type CategoryRow = {
    id: number;
    name: string;
    code: string;
    description: string | null;
    is_active: boolean;
    contacts_count: number;
};

export default function IncidentCategoriesIndex({
    area,
    categories,
}: {
    area: AreaSummary;
    categories: CategoryRow[];
}) {
    return (
        <>
            <Head title="Categorías de incidencia" />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title="Categorías de incidencia"
                        description={`Configuración para ${area.name} (${area.code})`}
                    />
                    <Button asChild>
                        <Link href={categoriesCreate()}>
                            <Plus className="size-4" />
                            Nueva categoría
                        </Link>
                    </Button>
                </div>

                <div className="overflow-hidden rounded-xl border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left">
                            <tr>
                                <th className="px-4 py-3 font-medium">Nombre</th>
                                <th className="px-4 py-3 font-medium">Código</th>
                                <th className="px-4 py-3 font-medium">
                                    Contactos
                                </th>
                                <th className="px-4 py-3 font-medium">Estado</th>
                                <th className="px-4 py-3 font-medium text-right">
                                    Acciones
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {categories.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={5}
                                        className="px-4 py-8 text-center text-muted-foreground"
                                    >
                                        Aún no hay categorías en esta área.
                                    </td>
                                </tr>
                            )}
                            {categories.map((category) => (
                                <tr key={category.id} className="border-t">
                                    <td className="px-4 py-3">
                                        <div className="font-medium">
                                            {category.name}
                                        </div>
                                        {category.description && (
                                            <p className="mt-1 line-clamp-1 text-muted-foreground">
                                                {category.description}
                                            </p>
                                        )}
                                    </td>
                                    <td className="px-4 py-3 font-mono text-xs">
                                        {category.code}
                                    </td>
                                    <td className="px-4 py-3">
                                        {category.contacts_count}
                                    </td>
                                    <td className="px-4 py-3">
                                        <Badge
                                            variant={
                                                category.is_active
                                                    ? 'default'
                                                    : 'secondary'
                                            }
                                        >
                                            {category.is_active
                                                ? 'Activa'
                                                : 'Inactiva'}
                                        </Badge>
                                    </td>
                                    <td className="px-4 py-3 text-right">
                                        <div className="flex justify-end gap-2">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                asChild
                                            >
                                                <Link
                                                    href={categoriesEdit(
                                                        category,
                                                    )}
                                                >
                                                    Editar
                                                </Link>
                                            </Button>
                                            <Button
                                                variant="destructive"
                                                size="sm"
                                                onClick={() => {
                                                    if (
                                                        confirm(
                                                            '¿Eliminar esta categoría?',
                                                        )
                                                    ) {
                                                        router.delete(
                                                            categoriesDestroy.url(
                                                                category,
                                                            ),
                                                        );
                                                    }
                                                }}
                                            >
                                                Eliminar
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </>
    );
}

IncidentCategoriesIndex.layout = {
    breadcrumbs: [
        {
            title: 'Categorías',
            href: categoriesIndex(),
        },
    ],
};
