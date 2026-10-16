import { usePage } from '@inertiajs/react';
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

export function ProductTourWelcomeDialog({
    open,
    userName,
    processing,
    onStart,
    onSkip,
}: {
    open: boolean;
    userName: string;
    processing: boolean;
    onStart: () => void;
    onSkip: () => void;
}) {
    const { branding } = usePage().props;
    const firstName = userName.trim().split(/\s+/)[0] || userName;

    return (
        <Dialog
            open={open}
            onOpenChange={(next) => {
                // Closing without starting (Esc / overlay) skips the tour.
                if (!next && !processing && open) {
                    onSkip();
                }
            }}
        >
            <DialogContent className="overflow-hidden border-0 p-0 sm:max-w-md">
                <div className="bg-(--brand-dark) px-6 pt-8 pb-5 text-white">
                    <div className="inline-flex rounded-xl bg-white px-3 py-2">
                        <img
                            src={branding.logo_url}
                            alt="Logo"
                            className="h-9 w-auto max-w-40 object-contain object-left"
                        />
                    </div>
                    <DialogHeader className="mt-5 space-y-2 text-left">
                        <DialogTitle className="text-2xl font-semibold tracking-tight text-white">
                            Bienvenido, {firstName}
                        </DialogTitle>
                        <DialogDescription className="text-sm leading-relaxed text-white/80">
                            Te mostramos dónde está cada módulo en menos de un
                            minuto: incidencias, rondines, configuración y
                            alertas.
                        </DialogDescription>
                    </DialogHeader>
                </div>

                <div className="space-y-4 px-6 py-5">
                    <ul className="space-y-2 text-sm text-muted-foreground">
                        <li className="flex gap-2">
                            <span className="mt-1.5 size-1.5 shrink-0 rounded-full bg-(--brand-primary)" />
                            Orientación clara del panel, sin salir de la
                            pantalla.
                        </li>
                        <li className="flex gap-2">
                            <span className="mt-1.5 size-1.5 shrink-0 rounded-full bg-(--brand-primary)" />
                            Puedes omitirlo y volver a verlo desde tu menú de
                            usuario.
                        </li>
                    </ul>

                    <DialogFooter className="gap-2 sm:justify-stretch">
                        <Button
                            type="button"
                            variant="outline"
                            className="flex-1"
                            disabled={processing}
                            onClick={onSkip}
                        >
                            {processing && <Spinner />}
                            Omitir por ahora
                        </Button>
                        <Button
                            type="button"
                            className="flex-1 bg-(--brand-dark) text-white hover:bg-(--brand-dark)/90"
                            disabled={processing}
                            onClick={onStart}
                        >
                            Iniciar recorrido
                        </Button>
                    </DialogFooter>
                </div>
            </DialogContent>
        </Dialog>
    );
}
