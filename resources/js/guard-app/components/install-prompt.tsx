import { Download, Share, X } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';

type BeforeInstallPromptEvent = Event & {
    prompt: () => Promise<void>;
    userChoice: Promise<{ outcome: 'accepted' | 'dismissed' }>;
};

const DISMISSED_KEY = 'rdi-install-dismissed';

function isStandalone(): boolean {
    return (
        window.matchMedia('(display-mode: standalone)').matches ||
        (navigator as Navigator & { standalone?: boolean }).standalone === true
    );
}

function isIos(): boolean {
    return /iphone|ipad|ipod/i.test(navigator.userAgent);
}

export function InstallPrompt() {
    const [deferred, setDeferred] = useState<BeforeInstallPromptEvent | null>(
        null,
    );
    const [dismissed, setDismissed] = useState(
        () => localStorage.getItem(DISMISSED_KEY) === '1',
    );

    useEffect(() => {
        const onPrompt = (event: Event) => {
            event.preventDefault();
            setDeferred(event as BeforeInstallPromptEvent);
        };

        window.addEventListener('beforeinstallprompt', onPrompt);

        return () =>
            window.removeEventListener('beforeinstallprompt', onPrompt);
    }, []);

    if (dismissed || isStandalone() || (!deferred && !isIos())) {
        return null;
    }

    const dismiss = () => {
        localStorage.setItem(DISMISSED_KEY, '1');
        setDismissed(true);
    };

    return (
        <div className="relative rounded-xl border bg-card p-4 pr-10 text-sm shadow-xs">
            <button
                type="button"
                aria-label="Cerrar"
                className="absolute top-3 right-3 text-muted-foreground"
                onClick={dismiss}
            >
                <X className="size-4" />
            </button>
            <p className="font-medium">Instala RDI en tu teléfono</p>
            {deferred ? (
                <>
                    <p className="mt-1 text-muted-foreground">
                        Así podrás usarla sin red y abrirla desde la pantalla de
                        inicio.
                    </p>
                    <Button
                        size="sm"
                        className="mt-3"
                        onClick={() => {
                            void deferred.prompt();
                            void deferred.userChoice.finally(() =>
                                setDeferred(null),
                            );
                        }}
                    >
                        <Download className="size-4" />
                        Instalar app
                    </Button>
                </>
            ) : (
                <p className="mt-1 text-muted-foreground">
                    En Safari toca <Share className="inline size-4" /> Compartir
                    y luego «Agregar a pantalla de inicio». Es necesario para
                    conservar tus datos sin red.
                </p>
            )}
        </div>
    );
}
