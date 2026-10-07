import { MessageSquare, Phone } from 'lucide-react';
import { useState } from 'react';
import { toast } from 'sonner';
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
import { triggerPanic } from '../store';
import type { LocalPatrol, OfflineArea } from '../types';

export function PanicDialog({
    open,
    onOpenChange,
    online,
    userId,
    userName,
    area,
    activePatrol,
}: {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    online: boolean;
    userId: number;
    userName: string;
    area: OfflineArea;
    activePatrol: LocalPatrol | null;
}) {
    const [processing, setProcessing] = useState(false);
    const [queued, setQueued] = useState(false);

    const smsBody = encodeURIComponent(
        `RDI - BOTÓN DE PÁNICO. Área: ${area.name}. Guardia: ${userName}. Hora: ${new Date().toLocaleString('es-MX')}`,
    );

    const close = () => {
        if (processing) {
            return;
        }

        setQueued(false);
        onOpenChange(false);
    };

    const activate = async () => {
        setProcessing(true);

        try {
            await triggerPanic(userId, area.id, activePatrol);
            setQueued(true);

            if (online) {
                toast.success(
                    'Alerta de pánico enviada a los contactos del área.',
                );
            }
        } finally {
            setProcessing(false);
        }
    };

    return (
        <Dialog open={open} onOpenChange={(value) => !value && close()}>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {queued
                            ? online
                                ? 'Alerta enviada'
                                : 'Alerta guardada sin red'
                            : '¿Activar botón de pánico?'}
                    </DialogTitle>
                    <DialogDescription>
                        {queued
                            ? online
                                ? 'Se notificó a los contactos del área.'
                                : 'Se enviará en cuanto recuperes la red. Mientras tanto llama o envía SMS a un contacto de emergencia.'
                            : online
                              ? 'Se enviará una alerta inmediata a todos los contactos del área. Úsalo solo en una emergencia real.'
                              : 'No tienes red. La alerta se guardará y se enviará al recuperarla; además puedes llamar o enviar SMS ahora.'}
                    </DialogDescription>
                </DialogHeader>

                {(!online || queued) && area.emergency_contacts.length > 0 && (
                    <ul className="space-y-2">
                        {area.emergency_contacts.map((contact) => (
                            <li
                                key={contact.phone}
                                className="flex items-center justify-between gap-2 rounded-lg border p-3"
                            >
                                <span className="text-sm font-medium">
                                    {contact.name}
                                </span>
                                <span className="flex gap-2">
                                    <Button size="sm" variant="outline" asChild>
                                        <a href={`tel:${contact.phone}`}>
                                            <Phone className="size-4" />
                                            Llamar
                                        </a>
                                    </Button>
                                    <Button size="sm" variant="outline" asChild>
                                        <a
                                            href={`sms:${contact.phone}?&body=${smsBody}`}
                                        >
                                            <MessageSquare className="size-4" />
                                            SMS
                                        </a>
                                    </Button>
                                </span>
                            </li>
                        ))}
                    </ul>
                )}

                {(!online || queued) &&
                    area.emergency_contacts.length === 0 && (
                        <p className="text-sm text-muted-foreground">
                            No hay contactos de emergencia con teléfono en esta
                            área.
                        </p>
                    )}

                <DialogFooter>
                    {queued ? (
                        <Button type="button" onClick={close}>
                            Cerrar
                        </Button>
                    ) : (
                        <>
                            <Button
                                type="button"
                                variant="outline"
                                disabled={processing}
                                onClick={close}
                            >
                                Cancelar
                            </Button>
                            <Button
                                type="button"
                                variant="destructive"
                                disabled={processing}
                                onClick={() => void activate()}
                            >
                                {processing && <Spinner />}
                                Sí, activar pánico
                            </Button>
                        </>
                    )}
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
