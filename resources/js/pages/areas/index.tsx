import { Head, Link, router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { index as areasIndex, create as areasCreate, edit as areasEdit, destroy as areasDestroy } from '@/routes/areas';

type AreaRow = {
    id: number;
    name: string;
    code: string;
    location: string | null;
    is_active: boolean;
    users_count: number;
};

export default function AreasIndex({
    areas,
    canCreate,
}: {
    areas: AreaRow[];
    canCreate: boolean;
}) {
    return (
        <>
            <Head title="Areas" />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title="Areas"
                        description="Industrial plants and locations scoped for reports"
                    />
                    {canCreate && (
                        <Button asChild>
                            <Link href={areasCreate()}>
                                <Plus className="size-4" />
                                New area
                            </Link>
                        </Button>
                    )}
                </div>

                <div className="overflow-hidden rounded-xl border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left">
                            <tr>
                                <th className="px-4 py-3 font-medium">Name</th>
                                <th className="px-4 py-3 font-medium">Code</th>
                                <th className="px-4 py-3 font-medium">Location</th>
                                <th className="px-4 py-3 font-medium">Users</th>
                                <th className="px-4 py-3 font-medium">Status</th>
                                {canCreate && (
                                    <th className="px-4 py-3 font-medium text-right">
                                        Actions
                                    </th>
                                )}
                            </tr>
                        </thead>
                        <tbody>
                            {areas.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={canCreate ? 6 : 5}
                                        className="px-4 py-8 text-center text-muted-foreground"
                                    >
                                        No areas yet.
                                    </td>
                                </tr>
                            )}
                            {areas.map((area) => (
                                <tr key={area.id} className="border-t">
                                    <td className="px-4 py-3 font-medium">
                                        {area.name}
                                    </td>
                                    <td className="px-4 py-3 font-mono text-xs">
                                        {area.code}
                                    </td>
                                    <td className="px-4 py-3 text-muted-foreground">
                                        {area.location ?? '—'}
                                    </td>
                                    <td className="px-4 py-3">{area.users_count}</td>
                                    <td className="px-4 py-3">
                                        <Badge
                                            variant={
                                                area.is_active
                                                    ? 'default'
                                                    : 'secondary'
                                            }
                                        >
                                            {area.is_active
                                                ? 'Active'
                                                : 'Inactive'}
                                        </Badge>
                                    </td>
                                    {canCreate && (
                                        <td className="px-4 py-3 text-right">
                                            <div className="flex justify-end gap-2">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <Link
                                                        href={areasEdit(area)}
                                                    >
                                                        Edit
                                                    </Link>
                                                </Button>
                                                <Button
                                                    variant="destructive"
                                                    size="sm"
                                                    onClick={() => {
                                                        if (
                                                            confirm(
                                                                'Delete this area?',
                                                            )
                                                        ) {
                                                            router.delete(
                                                                areasDestroy(
                                                                    area,
                                                                ),
                                                            );
                                                        }
                                                    }}
                                                >
                                                    Delete
                                                </Button>
                                            </div>
                                        </td>
                                    )}
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </>
    );
}

AreasIndex.layout = {
    breadcrumbs: [
        {
            title: 'Areas',
            href: areasIndex(),
        },
    ],
};
