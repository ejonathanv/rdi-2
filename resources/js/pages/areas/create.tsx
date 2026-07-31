import { Form, Head, Link } from '@inertiajs/react';
import AreaController from '@/actions/App/Http/Controllers/AreaController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { index as areasIndex, create as areasCreate } from '@/routes/areas';

export default function AreasCreate() {
    return (
        <>
            <Head title="New area" />

            <div className="flex flex-col gap-6 p-4">
                <Heading
                    title="New area"
                    description="Create a plant or location"
                />

                <Form
                    {...AreaController.store.form()}
                    className="max-w-xl space-y-6"
                >
                    {({ processing, errors }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    required
                                    placeholder="Planta Norte"
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="code">Code</Label>
                                <Input
                                    id="code"
                                    name="code"
                                    required
                                    placeholder="PLANTA-NORTE"
                                />
                                <InputError message={errors.code} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="location">Location</Label>
                                <Input
                                    id="location"
                                    name="location"
                                    placeholder="Monterrey, MX"
                                />
                                <InputError message={errors.location} />
                            </div>

                            <div className="flex items-center gap-3">
                                <input type="hidden" name="is_active" value="0" />
                                <Checkbox
                                    id="is_active"
                                    name="is_active"
                                    value="1"
                                    defaultChecked
                                />
                                <Label htmlFor="is_active">Active</Label>
                            </div>

                            <div className="flex gap-3">
                                <Button type="submit" disabled={processing}>
                                    {processing && <Spinner />}
                                    Create area
                                </Button>
                                <Button variant="outline" asChild>
                                    <Link href={areasIndex()}>Cancel</Link>
                                </Button>
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

AreasCreate.layout = {
    breadcrumbs: [
        {
            title: 'Areas',
            href: areasIndex(),
        },
        {
            title: 'Create',
            href: areasCreate(),
        },
    ],
};
