import { Head, Link, router } from '@inertiajs/react';
import { AlertTriangle, Route, Siren } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import { home as guardHome } from '@/routes/guard';
import { create as incidentsCreate } from '@/routes/incidents';
import { index as guardRoundsIndex } from '@/routes/guard/rounds';
import { store as panicStore } from '@/routes/panic';

export default function GuardHome() {
    const [confirmOpen, setConfirmOpen] = useState(false);
    const [processing, setProcessing] = useState(false);

    const submitPanic = () => {
        setProcessing(true);

        router.post(
            panicStore.url(),
            {},
            {
                onFinish: () => {
                    setProcessing(false);
                    setConfirmOpen(false);
                },
            },
        );
    };

    return (
        <>
            <Head title="Panel del guardia" />

            <div className="mx-auto flex w-full max-w-lg flex-col gap-8 p-4">
                <Heading
                    title="Panel del guardia"
                    description="Elige una acción para continuar con tu turno."
                />

                <div className="flex flex-col gap-3">
                    <Button asChild size="lg" className="h-14 w-full text-base">
                        <Link href={guardRoundsIndex()}>
                            <Route className="size-5" />
                            Iniciar recorrido
                        </Link>
                    </Button>

                    <Button
                        asChild
                        size="lg"
                        variant="outline"
                        className="h-14 w-full text-base"
                    >
                        <Link href={incidentsCreate()}>
                            <AlertTriangle className="size-5" />
                            Reportar incidencia
                        </Link>
                    </Button>

                    <Button
                        type="button"
                        size="lg"
                        variant="destructive"
                        className="h-14 w-full text-base"
                        disabled={processing}
                        onClick={() => setConfirmOpen(true)}
                    >
                        <Siren className="size-5" />
                        Botón de pánico
                    </Button>
                </div>
            </div>

            <Dialog
                open={confirmOpen}
                onOpenChange={(open) => {
                    if (!open && !processing) {
                        setConfirmOpen(false);
                    }
                }}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>¿Activar botón de pánico?</DialogTitle>
                        <DialogDescription>
                            Se enviará una alerta inmediata a todos los
                            contactos del área. Usa esta opción solo en una
                            emergencia real.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            disabled={processing}
                            onClick={() => setConfirmOpen(false)}
                        >
                            Cancelar
                        </Button>
                        <Button
                            type="button"
                            variant="destructive"
                            disabled={processing}
                            onClick={submitPanic}
                        >
                            {processing && <Spinner />}
                            Sí, activar pánico
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

GuardHome.layout = {
    breadcrumbs: [
        {
            title: 'Panel',
            href: guardHome(),
        },
    ],
};
