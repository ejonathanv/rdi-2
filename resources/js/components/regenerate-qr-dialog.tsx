import { router } from '@inertiajs/react';
import { RefreshCw } from 'lucide-react';
import { useState } from 'react';
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

export function RegenerateQrDialog({
    url,
    label,
    description,
}: {
    url: string;
    label: string;
    description: string;
}) {
    const [open, setOpen] = useState(false);
    const [processing, setProcessing] = useState(false);

    return (
        <>
            <Button
                type="button"
                variant="outline"
                size="sm"
                onClick={() => setOpen(true)}
            >
                <RefreshCw className="size-4" />
                {label}
            </Button>

            <Dialog open={open} onOpenChange={setOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>{label}</DialogTitle>
                        <DialogDescription>{description}</DialogDescription>
                    </DialogHeader>

                    <ul className="list-disc space-y-1 pl-5 text-sm text-muted-foreground">
                        <li>
                            Los QR impresos actuales y sus fotos dejarán de
                            funcionar.
                        </li>
                        <li>
                            Debes imprimir y colocar los QR nuevos en cada
                            punto.
                        </li>
                        <li>
                            Las revisiones capturadas sin conexión antes de este
                            cambio se seguirán aceptando.
                        </li>
                    </ul>

                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setOpen(false)}
                        >
                            Cancelar
                        </Button>
                        <Button
                            type="button"
                            variant="destructive"
                            disabled={processing}
                            onClick={() => {
                                router.post(
                                    url,
                                    {},
                                    {
                                        preserveScroll: true,
                                        onStart: () => setProcessing(true),
                                        onFinish: () => {
                                            setProcessing(false);
                                            setOpen(false);
                                        },
                                    },
                                );
                            }}
                        >
                            {processing && <Spinner />}
                            Regenerar
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}
