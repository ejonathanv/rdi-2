import { ArrowLeft, LogIn, LogOut } from 'lucide-react';
import { Toaster } from 'sonner';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { login, logout } from '../routes';
import { SyncStatus } from './components/sync-status';
import { CheckpointScreen } from './screens/checkpoint-screen';
import { HomeScreen } from './screens/home-screen';
import { IncidentScreen } from './screens/incident-screen';
import { OutboxScreen } from './screens/outbox-screen';
import { PatrolScreen } from './screens/patrol-screen';
import { RoundsScreen } from './screens/rounds-screen';
import { useGuardData } from './use-guard-data';
import type { GuardData } from './use-guard-data';
import { useHashRoute } from './use-hash-route';
import type { GuardRoute } from './use-hash-route';

const DEFAULT_LOGO_URL = '/img/rdi-logo.svg';

function csrfToken(): string {
    return (
        document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
            ?.content ?? ''
    );
}

export function GuardApp({ logoUrl }: { logoUrl: string }) {
    const data = useGuardData();
    const [route, navigate] = useHashRoute();

    return (
        <div className="flex min-h-svh flex-col bg-background">
            <header className="sticky top-0 z-40 bg-(--brand-dark) px-4 pt-[max(env(safe-area-inset-top),0.75rem)] pb-3 text-white shadow">
                <div className="mx-auto flex w-full max-w-lg items-center justify-between gap-3">
                    <div className="flex min-w-0 items-center gap-2">
                        {route.name !== 'home' && (
                            <button
                                type="button"
                                aria-label="Volver"
                                className="-ml-1 rounded-full p-1.5 hover:bg-white/10"
                                onClick={() => navigate(parentRoute(route))}
                            >
                                <ArrowLeft className="size-5" />
                            </button>
                        )}
                        <img
                            src={data.pkg?.branding.logo_url ?? logoUrl}
                            alt="Logo"
                            className="h-8 w-auto max-w-32 object-contain"
                            onError={(event) => {
                                if (
                                    !event.currentTarget.src.endsWith(
                                        DEFAULT_LOGO_URL,
                                    )
                                ) {
                                    event.currentTarget.src = DEFAULT_LOGO_URL;
                                }
                            }}
                        />
                    </div>
                    <SyncStatus
                        online={data.online}
                        outbox={data.outbox}
                        onOpenOutbox={() => navigate({ name: 'outbox' })}
                    />
                </div>
            </header>

            {data.authRequired && (
                <div className="bg-amber-100 px-4 py-3 text-sm text-amber-900">
                    <div className="mx-auto flex w-full max-w-lg items-center justify-between gap-3">
                        <span>
                            Tu sesión expiró. Inicia sesión para enviar los
                            pendientes; no se perderán.
                        </span>
                        {data.online && (
                            <Button size="sm" variant="outline" asChild>
                                <a href={login.url()}>
                                    <LogIn className="size-4" />
                                    Entrar
                                </a>
                            </Button>
                        )}
                    </div>
                </div>
            )}

            <main className="mx-auto w-full max-w-lg flex-1 p-4 pb-10">
                <Screen data={data} route={route} navigate={navigate} />
            </main>

            <footer className="mx-auto flex w-full max-w-lg flex-col items-center gap-3 px-4 pb-[max(env(safe-area-inset-bottom),1rem)] text-center text-xs text-muted-foreground">
                {data.pkg && data.online && (
                    <form method="post" action={logout.url()}>
                        <input
                            type="hidden"
                            name="_token"
                            value={csrfToken()}
                        />
                        <button
                            type="submit"
                            className="inline-flex items-center gap-1 underline-offset-4 hover:underline"
                            disabled={data.outbox.length > 0}
                            title={
                                data.outbox.length > 0
                                    ? 'Sincroniza los pendientes antes de cerrar sesión'
                                    : undefined
                            }
                        >
                            <LogOut className="size-3.5" />
                            Cerrar sesión ({data.pkg.user.name})
                        </button>
                    </form>
                )}
                <p>
                    {new Date().getFullYear()}, RDI - Reporte de Incidentes -
                    Desarrollo de IP Media River.
                </p>
            </footer>

            <Toaster position="top-center" richColors />
        </div>
    );
}

function parentRoute(route: GuardRoute): GuardRoute {
    switch (route.name) {
        case 'checkpoint':
            return { name: 'patrol' };
        case 'incident':
            return route.checkpointId
                ? { name: 'checkpoint', checkpointId: route.checkpointId }
                : { name: 'home' };
        default:
            return { name: 'home' };
    }
}

function Screen({
    data,
    route,
    navigate,
}: {
    data: GuardData;
    route: GuardRoute;
    navigate: (route: GuardRoute) => void;
}) {
    if (data.loading) {
        return (
            <div className="flex justify-center py-20">
                <Spinner className="size-6" />
            </div>
        );
    }

    if (!data.pkg) {
        return (
            <div className="space-y-3 rounded-xl border p-6 text-center">
                <p className="font-medium">
                    {data.online
                        ? 'Descargando recorridos…'
                        : 'Necesitas conexión la primera vez'}
                </p>
                <p className="text-sm text-muted-foreground">
                    {data.online
                        ? 'Estamos preparando la información para que puedas trabajar sin red.'
                        : 'Abre la app con datos o Wi-Fi para descargar tus recorridos. Después podrás usarla sin red.'}
                </p>
                {data.online && <Spinner className="mx-auto" />}
            </div>
        );
    }

    switch (route.name) {
        case 'rounds':
            return <RoundsScreen data={data} navigate={navigate} />;
        case 'patrol':
            return <PatrolScreen data={data} navigate={navigate} />;
        case 'checkpoint':
            return (
                <CheckpointScreen
                    key={route.checkpointId}
                    data={data}
                    checkpointId={route.checkpointId}
                    navigate={navigate}
                />
            );
        case 'incident':
            return (
                <IncidentScreen
                    key={route.checkpointId ?? 'free'}
                    data={data}
                    checkpointId={route.checkpointId}
                    navigate={navigate}
                />
            );
        case 'outbox':
            return <OutboxScreen data={data} />;
        default:
            return <HomeScreen data={data} navigate={navigate} />;
    }
}
