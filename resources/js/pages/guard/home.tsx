import { Head, Link } from '@inertiajs/react';
import { AlertTriangle, Route } from 'lucide-react';
import Heading from '@/components/heading';
import { Button } from '@/components/ui/button';
import { home as guardHome } from '@/routes/guard';
import { create as incidentsCreate } from '@/routes/incidents';
import { index as guardRoundsIndex } from '@/routes/guard/rounds';

export default function GuardHome() {
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
                </div>
            </div>
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
