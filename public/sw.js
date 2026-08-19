/* global self, clients */

self.addEventListener('push', (event) => {
    let payload = {
        title: 'RDI',
        body: 'Nueva alerta',
        data: { url: '/' },
    };

    try {
        if (event.data) {
            payload = { ...payload, ...event.data.json() };
        }
    } catch (error) {
        console.error('No se pudo leer el payload push', error);
    }

    const title = payload.title || 'RDI';
    const options = {
        body: payload.body || '',
        icon: payload.icon || '/img/favicon.png',
        badge: payload.badge || '/img/favicon.png',
        data: payload.data || { url: '/' },
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const targetUrl = event.notification.data?.url || '/';

    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windowClients) => {
            for (const client of windowClients) {
                if ('focus' in client) {
                    client.navigate(targetUrl);
                    return client.focus();
                }
            }

            if (clients.openWindow) {
                return clients.openWindow(targetUrl);
            }

            return undefined;
        }),
    );
});
