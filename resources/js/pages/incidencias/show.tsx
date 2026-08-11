import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import Heading from '@/components/heading';
import { IncidentStatusBadge } from '@/components/incident-status-badge';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
import {
    index as incidenciasIndex,
    show as incidenciasShow,
    status as incidenciasStatus,
} from '@/routes/incidencias';

type AreaSummary = {
    id: number;
    name: string;
    code: string;
};

type Transition = {
    value: string;
    label: string;
};

type IncidentDetail = {
    id: number;
    message_raw: string;
    message_cleaned: string | null;
    is_urgent: boolean;
    status: string;
    status_label: string;
    allowed_transitions: Transition[];
    assigned_to: { id: number; name: string } | null;
    acknowledged_at: string | null;
    resolved_by: { id: number; name: string } | null;
    resolved_at: string | null;
    resolution_notes: string | null;
    response_seconds: number | null;
    response_label: string | null;
    resolution_seconds: number | null;
    resolution_label: string | null;
    categorized_at: string | null;
    created_at: string | null;
    guard: { id: number; name: string; email: string };
    category: { id: number; name: string; code: string } | null;
    checkpoint: { id: number; name: string } | null;
    round: { id: number; title: string } | null;
    photos: { id: number; url: string; position: number }[];
};

function formatDateTime(value: string | null): string {
    if (!value) {
        return '—';
    }

    return new Date(value).toLocaleString('es-MX', {
        dateStyle: 'short',
        timeStyle: 'short',
    });
}

export default function IncidenciasShow({
    area,
    incident,
    can_update_status = false,
}: {
    area: AreaSummary;
    incident: IncidentDetail;
    can_update_status?: boolean;
}) {
    const [closeStatus, setCloseStatus] = useState<string | null>(null);
    const [notes, setNotes] = useState('');
    const [notesError, setNotesError] = useState<string | null>(null);
    const [processing, setProcessing] = useState(false);

    const canTake =
        can_update_status &&
        incident.allowed_transitions.some((item) => item.value === 'en_atencion');
    const canResolve =
        can_update_status &&
        incident.allowed_transitions.some((item) => item.value === 'resuelta');
    const canDiscard =
        can_update_status &&
        incident.allowed_transitions.some((item) => item.value === 'descartada');

    const submitStatus = (status: string, resolutionNotes?: string) => {
        setProcessing(true);
        setNotesError(null);

        router.patch(
            incidenciasStatus.url(incident.id),
            {
                status,
                resolution_notes: resolutionNotes ?? null,
            },
            {
                preserveScroll: true,
                onError: (errors) => {
                    if (errors.resolution_notes) {
                        setNotesError(errors.resolution_notes);
                    }
                },
                onFinish: () => {
                    setProcessing(false);
                    setCloseStatus(null);
                    setNotes('');
                },
            },
        );
    };

    const confirmClose = () => {
        if (!closeStatus) {
            return;
        }

        if (!notes.trim()) {
            setNotesError('Las notas de cierre son obligatorias.');

            return;
        }

        submitStatus(closeStatus, notes.trim());
    };

    return (
        <>
            <Head title={`Incidencia #${incident.id}`} />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <Heading
                        title={`Incidencia #${incident.id}`}
                        description={`${area.name} (${area.code}) · ${formatDateTime(incident.created_at)}`}
                    />
                    <Button variant="outline" asChild>
                        <Link href={incidenciasIndex()}>Volver</Link>
                    </Button>
                </div>

                <div className="grid gap-4 lg:grid-cols-2">
                    <div className="space-y-4 rounded-xl border p-4">
                        <div className="flex flex-wrap items-center gap-2">
                            <IncidentStatusBadge
                                status={incident.status}
                                label={incident.status_label}
                            />
                            {incident.is_urgent && (
                                <Badge variant="destructive">Urgente</Badge>
                            )}
                            {incident.category ? (
                                <Badge variant="secondary">
                                    {incident.category.name}
                                </Badge>
                            ) : (
                                <Badge variant="secondary">Sin categoría</Badge>
                            )}
                        </div>

                        <dl className="grid gap-3 text-sm">
                            <div>
                                <dt className="text-muted-foreground">
                                    Guardia
                                </dt>
                                <dd className="font-medium">
                                    {incident.guard.name}
                                    <span className="text-muted-foreground">
                                        {' '}
                                        · {incident.guard.email}
                                    </span>
                                </dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">
                                    Contexto
                                </dt>
                                <dd className="font-medium">
                                    {incident.round || incident.checkpoint
                                        ? [
                                              incident.round?.title,
                                              incident.checkpoint?.name,
                                          ]
                                              .filter(Boolean)
                                              .join(' · ')
                                        : 'Reporte independiente'}
                                </dd>
                            </div>
                            {incident.categorized_at && (
                                <div>
                                    <dt className="text-muted-foreground">
                                        Categorizada
                                    </dt>
                                    <dd className="font-medium">
                                        {formatDateTime(
                                            incident.categorized_at,
                                        )}
                                    </dd>
                                </div>
                            )}
                        </dl>
                    </div>

                    <div className="space-y-4 rounded-xl border p-4">
                        <h2 className="font-medium">Gestión</h2>
                        <dl className="grid gap-3 text-sm">
                            <div>
                                <dt className="text-muted-foreground">
                                    Asignado a
                                </dt>
                                <dd className="font-medium">
                                    {incident.assigned_to?.name ?? '—'}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">
                                    Tomada
                                </dt>
                                <dd className="font-medium">
                                    {formatDateTime(incident.acknowledged_at)}
                                    {incident.response_label
                                        ? ` · ${incident.response_label}`
                                        : ''}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-muted-foreground">
                                    Cierre
                                </dt>
                                <dd className="font-medium">
                                    {incident.resolved_by
                                        ? `${incident.resolved_by.name} · ${formatDateTime(incident.resolved_at)}`
                                        : '—'}
                                    {incident.resolution_label
                                        ? ` · ${incident.resolution_label}`
                                        : ''}
                                </dd>
                            </div>
                            {incident.resolution_notes && (
                                <div>
                                    <dt className="text-muted-foreground">
                                        Notas de cierre
                                    </dt>
                                    <dd className="whitespace-pre-wrap font-medium">
                                        {incident.resolution_notes}
                                    </dd>
                                </div>
                            )}
                        </dl>

                        {(canTake || canResolve || canDiscard) && (
                            <div className="flex flex-wrap gap-2 pt-2">
                                {canTake && (
                                    <Button
                                        type="button"
                                        disabled={processing}
                                        onClick={() =>
                                            submitStatus('en_atencion')
                                        }
                                    >
                                        {processing && <Spinner />}
                                        Tomar caso
                                    </Button>
                                )}
                                {canResolve && (
                                    <Button
                                        type="button"
                                        variant="outline"
                                        disabled={processing}
                                        onClick={() =>
                                            setCloseStatus('resuelta')
                                        }
                                    >
                                        Resolver
                                    </Button>
                                )}
                                {canDiscard && (
                                    <Button
                                        type="button"
                                        variant="destructive"
                                        disabled={processing}
                                        onClick={() =>
                                            setCloseStatus('descartada')
                                        }
                                    >
                                        Descartar
                                    </Button>
                                )}
                            </div>
                        )}
                    </div>
                </div>

                <div className="grid gap-4 lg:grid-cols-2">
                    <div className="space-y-4 rounded-xl border p-4">
                        <div>
                            <h2 className="font-medium">Mensaje limpio</h2>
                            <p className="mt-2 text-sm whitespace-pre-wrap">
                                {incident.message_cleaned ??
                                    'Pendiente de procesamiento'}
                            </p>
                        </div>
                        <div>
                            <h2 className="font-medium">Mensaje original</h2>
                            <p className="mt-2 text-sm text-muted-foreground whitespace-pre-wrap">
                                {incident.message_raw}
                            </p>
                        </div>
                    </div>
                </div>

                {incident.photos.length > 0 && (
                    <div className="space-y-3">
                        <h2 className="font-medium">Evidencia fotográfica</h2>
                        <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            {incident.photos.map((photo) => (
                                <a
                                    key={photo.id}
                                    href={photo.url}
                                    target="_blank"
                                    rel="noreferrer"
                                    className="overflow-hidden rounded-xl border"
                                >
                                    <img
                                        src={photo.url}
                                        alt={`Evidencia ${photo.position}`}
                                        className="aspect-video w-full object-cover"
                                    />
                                </a>
                            ))}
                        </div>
                    </div>
                )}
            </div>

            <Dialog
                open={closeStatus !== null}
                onOpenChange={(open) => {
                    if (!open && !processing) {
                        setCloseStatus(null);
                        setNotes('');
                        setNotesError(null);
                    }
                }}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>
                            {closeStatus === 'descartada'
                                ? '¿Descartar incidencia?'
                                : '¿Resolver incidencia?'}
                        </DialogTitle>
                        <DialogDescription>
                            Se notificará el cierre a los contactos y al guardia
                            si tiene canales activos. Esta acción no se puede
                            deshacer.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="grid gap-2">
                        <Label htmlFor="resolution_notes">
                            Notas de cierre
                        </Label>
                        <textarea
                            id="resolution_notes"
                            rows={4}
                            value={notes}
                            onChange={(event) =>
                                setNotes(event.target.value)
                            }
                            className="border-input placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-ring/50 flex w-full rounded-md border bg-background px-3 py-2 text-sm shadow-xs outline-none focus-visible:ring-[3px]"
                            placeholder="Describe la acción tomada o el motivo del descarte…"
                        />
                        <InputError message={notesError ?? undefined} />
                    </div>
                    <DialogFooter>
                        <Button
                            type="button"
                            variant="outline"
                            disabled={processing}
                            onClick={() => setCloseStatus(null)}
                        >
                            Cancelar
                        </Button>
                        <Button
                            type="button"
                            variant={
                                closeStatus === 'descartada'
                                    ? 'destructive'
                                    : 'default'
                            }
                            disabled={processing}
                            onClick={confirmClose}
                        >
                            {processing && <Spinner />}
                            {closeStatus === 'descartada'
                                ? 'Sí, descartar'
                                : 'Sí, resolver'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

IncidenciasShow.layout = {
    breadcrumbs: [
        {
            title: 'Incidencias',
            href: incidenciasIndex(),
        },
        {
            title: 'Detalle',
            href: incidenciasShow(1),
        },
    ],
};
