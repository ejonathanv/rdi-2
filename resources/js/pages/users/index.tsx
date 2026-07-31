import { Head, Link, router } from '@inertiajs/react';
import { Plus } from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { AreaMembership } from '@/types';
import {
    index as usersIndex,
    create as usersCreate,
    edit as usersEdit,
    destroy as usersDestroy,
} from '@/routes/users';

type UserRow = {
    id: number;
    name: string;
    email: string;
    is_super_admin: boolean;
    memberships: AreaMembership[];
};

export default function UsersIndex({
    users,
    canCreate,
}: {
    users: UserRow[];
    canCreate: boolean;
}) {
    return (
        <>
            <Head title="Users" />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex items-start justify-between gap-4">
                    <Heading
                        title="Users"
                        description="Guards, contacts and area admins"
                    />
                    {canCreate && (
                        <Button asChild>
                            <Link href={usersCreate()}>
                                <Plus className="size-4" />
                                New user
                            </Link>
                        </Button>
                    )}
                </div>

                <div className="overflow-hidden rounded-xl border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted/50 text-left">
                            <tr>
                                <th className="px-4 py-3 font-medium">Name</th>
                                <th className="px-4 py-3 font-medium">Email</th>
                                <th className="px-4 py-3 font-medium">
                                    Memberships
                                </th>
                                <th className="px-4 py-3 font-medium text-right">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            {users.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={4}
                                        className="px-4 py-8 text-center text-muted-foreground"
                                    >
                                        No users yet.
                                    </td>
                                </tr>
                            )}
                            {users.map((user) => (
                                <tr key={user.id} className="border-t">
                                    <td className="px-4 py-3">
                                        <div className="font-medium">
                                            {user.name}
                                        </div>
                                        {user.is_super_admin && (
                                            <Badge className="mt-1">
                                                Super admin
                                            </Badge>
                                        )}
                                    </td>
                                    <td className="px-4 py-3 text-muted-foreground">
                                        {user.email}
                                    </td>
                                    <td className="px-4 py-3">
                                        <div className="flex flex-wrap gap-1">
                                            {user.memberships.length === 0 && (
                                                <span className="text-muted-foreground">
                                                    —
                                                </span>
                                            )}
                                            {user.memberships.map(
                                                (membership) => (
                                                    <Badge
                                                        key={`${membership.area_id}-${membership.role}`}
                                                        variant="secondary"
                                                    >
                                                        {membership.area_code}:{' '}
                                                        {membership.role}
                                                    </Badge>
                                                ),
                                            )}
                                        </div>
                                    </td>
                                    <td className="px-4 py-3 text-right">
                                        <div className="flex justify-end gap-2">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                asChild
                                            >
                                                <Link href={usersEdit(user)}>
                                                    Edit
                                                </Link>
                                            </Button>
                                            <Button
                                                variant="destructive"
                                                size="sm"
                                                onClick={() => {
                                                    if (
                                                        confirm(
                                                            'Delete this user?',
                                                        )
                                                    ) {
                                                        router.delete(
                                                            usersDestroy(user),
                                                        );
                                                    }
                                                }}
                                            >
                                                Delete
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

UsersIndex.layout = {
    breadcrumbs: [
        {
            title: 'Users',
            href: usersIndex(),
        },
    ],
};
