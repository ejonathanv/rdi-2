import { Head, Link, usePage } from '@inertiajs/react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { show as showScan } from '@/routes/checkpoints/scan';
import { dashboard } from '@/routes';

export default function CheckpointScanComplete({
    checkpoint,
    round,
}: {
    checkpoint: { name: string; token: string };
    round: { title: string };
}) {
    const { auth } = usePage<{
        auth: { user: { home_path?: string } | null };
    }>().props;
    const panelHref = auth.user?.home_path ?? dashboard();

    return (
        <>
            <Head title="Respuestas enviadas" />

            <div className="mx-auto flex w-full max-w-lg flex-col gap-6 p-4">
                <Heading
                    title="Respuestas enviadas"
                    description={`${round.title} · ${checkpoint.name}`}
                />

                <p className="text-sm text-muted-foreground">
                    El cuestionario de este punto quedó registrado. Puedes
                    continuar con el recorrido.
                </p>

                <div className="flex flex-col gap-2 sm:flex-row">
                    <Button asChild>
                        <Link href={panelHref}>Ir al panel</Link>
                    </Button>
                    <Button variant="outline" asChild>
                        <Link href={showScan(checkpoint.token)}>
                            Volver al punto
                        </Link>
                    </Button>
                </div>
            </div>
        </>
    );
}

CheckpointScanComplete.layout = {
    breadcrumbs: [
        {
            title: 'Respuestas enviadas',
            href: '#',
        },
    ],
};
