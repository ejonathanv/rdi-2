export async function registerServiceWorker({
    cacheGuardShell = false,
}: {
    cacheGuardShell?: boolean;
} = {}): Promise<ServiceWorkerRegistration | null> {
    if (!('serviceWorker' in navigator) || import.meta.env.DEV) {
        return null;
    }

    try {
        const registration = await navigator.serviceWorker.register('/sw.js', {
            scope: '/',
        });

        if (cacheGuardShell) {
            const ready = await navigator.serviceWorker.ready;
            ready.active?.postMessage({ type: 'CACHE_GUARD_SHELL' });
        }

        return registration;
    } catch {
        return null;
    }
}
