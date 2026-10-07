/// <reference lib="webworker" />
import { clientsClaim } from 'workbox-core';
import { ExpirationPlugin } from 'workbox-expiration';
import { cleanupOutdatedCaches, precacheAndRoute } from 'workbox-precaching';
import { registerRoute } from 'workbox-routing';
import { CacheFirst, NetworkFirst } from 'workbox-strategies';
import { OUTBOX_SYNC_TAG, syncOutbox } from './guard-app/sync';

declare const self: ServiceWorkerGlobalScope;

const GUARD_APP_PATH = '/guardia/app';
const SHELL_CACHE = 'rdi-guard-shell';

self.skipWaiting();
clientsClaim();

cleanupOutdatedCaches();
precacheAndRoute(self.__WB_MANIFEST);

registerRoute(
    ({ request, url }) =>
        request.mode === 'navigate' && url.pathname.startsWith(GUARD_APP_PATH),
    new NetworkFirst({
        cacheName: SHELL_CACHE,
        networkTimeoutSeconds: 4,
        plugins: [
            {
                cacheKeyWillBeUsed: async () => GUARD_APP_PATH,
                cacheWillUpdate: async ({ response }) =>
                    response.ok && !response.redirected ? response : null,
            },
        ],
    }),
);

registerRoute(
    ({ url }) =>
        url.origin === self.location.origin &&
        (url.pathname.startsWith('/img/') ||
            url.pathname.startsWith('/storage/branding/')),
    new CacheFirst({
        cacheName: 'rdi-branding',
        plugins: [new ExpirationPlugin({ maxEntries: 30 })],
    }),
);

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches
            .open(SHELL_CACHE)
            .then(async (cache) => {
                const response = await fetch(GUARD_APP_PATH, {
                    credentials: 'same-origin',
                });

                if (response.ok && !response.redirected) {
                    await cache.put(GUARD_APP_PATH, response);
                }
            })
            .catch(() => undefined),
    );
});

type SyncEvent = ExtendableEvent & { tag: string };

self.addEventListener('sync', ((event: SyncEvent) => {
    if (event.tag === OUTBOX_SYNC_TAG) {
        event.waitUntil(syncOutbox().then(() => undefined));
    }
}) as EventListener);

self.addEventListener('message', (event) => {
    if (event.data?.type === 'CACHE_GUARD_SHELL') {
        event.waitUntil(
            caches.open(SHELL_CACHE).then(async (cache) => {
                const response = await fetch(GUARD_APP_PATH, {
                    credentials: 'same-origin',
                });

                if (response.ok && !response.redirected) {
                    await cache.put(GUARD_APP_PATH, response);
                }
            }),
        );
    }
});

type PushPayload = {
    title?: string;
    body?: string;
    icon?: string;
    badge?: string;
    data?: { url?: string };
};

self.addEventListener('push', (event) => {
    let payload: PushPayload = {
        title: 'RDI',
        body: 'Nueva alerta',
        data: { url: '/' },
    };

    try {
        if (event.data) {
            payload = { ...payload, ...(event.data.json() as PushPayload) };
        }
    } catch (error) {
        console.error('No se pudo leer el payload push', error);
    }

    event.waitUntil(
        self.registration.showNotification(payload.title || 'RDI', {
            body: payload.body || '',
            icon: payload.icon || '/img/favicon.png',
            badge: payload.badge || '/img/favicon.png',
            data: payload.data || { url: '/' },
        }),
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const targetUrl: string = event.notification.data?.url || '/';

    event.waitUntil(
        self.clients
            .matchAll({ type: 'window', includeUncontrolled: true })
            .then((windowClients) => {
                for (const client of windowClients) {
                    if ('focus' in client) {
                        void client.navigate(targetUrl);

                        return client.focus();
                    }
                }

                return self.clients.openWindow(targetUrl);
            }),
    );
});
