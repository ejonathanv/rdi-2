import { Head, Link, useForm } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import UserController from '@/actions/App/Http/Controllers/UserController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import type { AreaMembership, AreaSummary, RoleOption } from '@/types';
import { index as usersIndex } from '@/routes/users';

type MembershipForm = {
    area_id: string;
    role: string;
};

type EditableUser = {
    id: number;
    name: string;
    email: string;
    is_super_admin: boolean;
    memberships: AreaMembership[];
};

export default function UsersEdit({
    user,
    areas,
    roles,
    canGrantSuperAdmin,
}: {
    user: EditableUser;
    areas: AreaSummary[];
    roles: RoleOption[];
    canGrantSuperAdmin: boolean;
}) {
    const { data, setData, put, processing, errors } = useForm({
        name: user.name,
        email: user.email,
        password: '',
        password_confirmation: '',
        is_super_admin: user.is_super_admin,
        memberships: user.memberships.map((membership) => ({
            area_id: String(membership.area_id),
            role: membership.role,
        })) as MembershipForm[],
    });

    function addMembership() {
        setData('memberships', [
            ...data.memberships,
            {
                area_id: areas[0] ? String(areas[0].id) : '',
                role: roles[0]?.value ?? 'guard',
            },
        ]);
    }

    function removeMembership(index: number) {
        setData(
            'memberships',
            data.memberships.filter((_, i) => i !== index),
        );
    }

    function updateMembership(
        index: number,
        field: keyof MembershipForm,
        value: string,
    ) {
        setData(
            'memberships',
            data.memberships.map((membership, i) =>
                i === index ? { ...membership, [field]: value } : membership,
            ),
        );
    }

    return (
        <>
            <Head title={`Edit ${user.name}`} />

            <div className="flex flex-col gap-6 p-4">
                <Heading title="Edit user" description={user.email} />

                <form
                    className="max-w-2xl space-y-6"
                    onSubmit={(event) => {
                        event.preventDefault();
                        put(UserController.update.url(user));
                    }}
                >
                    <div className="grid gap-2">
                        <Label htmlFor="name">Name</Label>
                        <Input
                            id="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                            required
                        />
                        <InputError message={errors.name} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="email">Email</Label>
                        <Input
                            id="email"
                            type="email"
                            value={data.email}
                            onChange={(e) => setData('email', e.target.value)}
                            required
                        />
                        <InputError message={errors.email} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="password">
                            Password{' '}
                            <span className="text-muted-foreground">
                                (leave blank to keep)
                            </span>
                        </Label>
                        <PasswordInput
                            id="password"
                            value={data.password}
                            onChange={(e) =>
                                setData('password', e.target.value)
                            }
                        />
                        <InputError message={errors.password} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="password_confirmation">
                            Confirm password
                        </Label>
                        <PasswordInput
                            id="password_confirmation"
                            value={data.password_confirmation}
                            onChange={(e) =>
                                setData(
                                    'password_confirmation',
                                    e.target.value,
                                )
                            }
                        />
                    </div>

                    {canGrantSuperAdmin && (
                        <div className="flex items-center gap-3">
                            <Checkbox
                                id="is_super_admin"
                                checked={data.is_super_admin}
                                onCheckedChange={(checked) =>
                                    setData('is_super_admin', checked === true)
                                }
                            />
                            <Label htmlFor="is_super_admin">Super admin</Label>
                        </div>
                    )}

                    <div className="space-y-3">
                        <div className="flex items-center justify-between">
                            <Heading
                                variant="small"
                                title="Area memberships"
                                description="One role per area"
                            />
                            <Button
                                type="button"
                                variant="outline"
                                size="sm"
                                onClick={addMembership}
                                disabled={areas.length === 0}
                            >
                                <Plus className="size-4" />
                                Add
                            </Button>
                        </div>
                        <InputError message={errors.memberships} />

                        {data.memberships.map((membership, index) => (
                            <div
                                key={index}
                                className="flex flex-col gap-3 rounded-lg border p-3 sm:flex-row sm:items-end"
                            >
                                <div className="grid flex-1 gap-2">
                                    <Label>Area</Label>
                                    <Select
                                        value={membership.area_id}
                                        onValueChange={(value) =>
                                            updateMembership(
                                                index,
                                                'area_id',
                                                value,
                                            )
                                        }
                                    >
                                        <SelectTrigger className="w-full">
                                            <SelectValue placeholder="Select area" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {areas.map((area) => (
                                                <SelectItem
                                                    key={area.id}
                                                    value={String(area.id)}
                                                >
                                                    {area.name} ({area.code})
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError
                                        message={
                                            errors[
                                                `memberships.${index}.area_id`
                                            ]
                                        }
                                    />
                                </div>
                                <div className="grid flex-1 gap-2">
                                    <Label>Role</Label>
                                    <Select
                                        value={membership.role}
                                        onValueChange={(value) =>
                                            updateMembership(
                                                index,
                                                'role',
                                                value,
                                            )
                                        }
                                    >
                                        <SelectTrigger className="w-full">
                                            <SelectValue placeholder="Select role" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {roles.map((role) => (
                                                <SelectItem
                                                    key={role.value}
                                                    value={role.value}
                                                >
                                                    {role.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError
                                        message={
                                            errors[`memberships.${index}.role`]
                                        }
                                    />
                                </div>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="icon"
                                    onClick={() => removeMembership(index)}
                                >
                                    <Trash2 className="size-4" />
                                </Button>
                            </div>
                        ))}
                    </div>

                    <div className="flex gap-3">
                        <Button type="submit" disabled={processing}>
                            {processing && <Spinner />}
                            Save changes
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href={usersIndex()}>Cancel</Link>
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}

UsersEdit.layout = {
    breadcrumbs: [
        {
            title: 'Users',
            href: usersIndex(),
        },
        {
            title: 'Edit',
            href: '#',
        },
    ],
};
