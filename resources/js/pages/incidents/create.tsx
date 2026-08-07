import { Head, Link, useForm } from '@inertiajs/react';
import { useState } from 'react';
import CheckpointPhotoPicker from '@/components/checkpoint-photo-picker';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { home as guardHome } from '@/routes/guard';

type AreaSummary = {
    id: number;
    name: string;
    code: string;
};

type Context = {
    checkpoint_token: string;
    checkpoint_name: string;
    round_title: string;
} | null;

export default function IncidentCreate({
    area,
    context,
    store_url,
    cancel_url,
}: {
    area: AreaSummary;
    context: Context;
    store_url: string;
    cancel_url: string;
}) {
    const [photos, setPhotos] = useState<File[]>([]);
    const [confirmOpen, setConfirmOpen] = useState(false);

    const form = useForm<{
        message: string;
        is_urgent: boolean;
        photos: File[];
        checkpoint_token: string | null;
    }>({
        message: '',
        is_urgent: false,
        photos: [],
        checkpoint_token: context?.checkpoint_token ?? null,
    });

    const requestConfirm = (event: React.FormEvent) => {
        event.preventDefault();
        setConfirmOpen(true);
    };

    const submitIncident = () => {
        setConfirmOpen(false);

        form.transform((data) => ({
            ...data,
            photos,
            checkpoint_token: context?.checkpoint_token ?? null,
        }));

        form.post(store_url, {
            forceFormData: true,
        });
    };

    const photoErrors = Object.fromEntries(
        Object.entries(form.errors).filter(([key]) => key.startsWith('photos')),
    );

    return (
        <>
            <Head title="Reportar incidencia" />

            <div className="mx-auto flex w-full max-w-lg flex-col gap-6 p-4">
                <Heading
                    title="Reportar incidencia"
                    description={
                        context
                            ? `${area.name} · ${context.round_title} · ${context.checkpoint_name}`
                            : `${area.name} (${area.code})`
                    }
                />

                <form onSubmit={requestConfirm} className="space-y-6">
                    <div className="grid gap-2">
                        <Label htmlFor="message">¿Qué está pasando?</Label>
                        <textarea
                            id="message"
                            rows={5}
                            value={form.data.message}
                            onChange={(event) =>
                                form.setData('message', event.target.value)
                            }
                            placeholder="Describe la incidencia con el mayor detalle posible…"
                            className="border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 flex w-full rounded-md border bg-background px-3 py-2 text-base shadow-xs outline-none focus-visible:ring-[3px] md:text-sm"
                            required
                        />
                        <InputError message={form.errors.message} />
                    </div>

                    <CheckpointPhotoPicker
                        photos={photos}
                        onChange={setPhotos}
                        errors={photoErrors}
                        disabled={form.processing}
                    />

                    <div className="space-y-3 rounded-xl border p-4">
                        <div className="flex items-center justify-between gap-4">
                            <div className="space-y-1">
                                <Label
                                    htmlFor="is_urgent"
                                    className="text-destructive"
                                >
                                    Urgente de revisión
                                </Label>
                                <p className="text-sm text-muted-foreground">
                                    Se destaca al notificar a los contactos de
                                    la categoría asignada.
                                </p>
                            </div>
                            <Checkbox
                                id="is_urgent"
                                checked={form.data.is_urgent}
                                disabled={form.processing}
                                onCheckedChange={(checked) =>
                                    form.setData(
                                        'is_urgent',
                                        checked === true,
                                    )
                                }
                            />
                        </div>
                        <InputError message={form.errors.is_urgent} />
                    </div>

                    <InputError message={form.errors.checkpoint_token} />

                    <div className="flex flex-col gap-3">
                        <Button
                            type="submit"
                            className="w-full"
                            disabled={form.processing}
                        >
                            {form.processing && <Spinner />}
                            Guardar incidencia
                        </Button>
                        <Button variant="outline" className="w-full" asChild>
                            <Link href={cancel_url}>Cancelar</Link>
                        </Button>
                    </div>
                </form>
            </div>

            <Dialog
                open={confirmOpen}
                onOpenChange={(open) => {
                    if (!open && !form.processing) {
                        setConfirmOpen(false);
                    }
                }}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>¿Guardar incidencia?</DialogTitle>
                        <DialogDescription>
                            {form.data.is_urgent
                                ? 'Se registrará como urgente y se notificará a los contactos. Esta acción no se puede deshacer.'
                                : 'Se registrará la incidencia y se notificará a los contactos de la categoría. Esta acción no se puede deshacer.'}
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            disabled={form.processing}
                            onClick={() => setConfirmOpen(false)}
                        >
                            Cancelar
                        </Button>
                        <Button
                            type="button"
                            disabled={form.processing}
                            onClick={submitIncident}
                        >
                            {form.processing && <Spinner />}
                            Sí, guardar incidencia
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

IncidentCreate.layout = {
    breadcrumbs: [
        {
            title: 'Panel',
            href: guardHome(),
        },
        {
            title: 'Reportar incidencia',
            href: '#',
        },
    ],
};
