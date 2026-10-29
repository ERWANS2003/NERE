self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') {
        return;
    }

    // Never cache authenticated pages or responses containing personal data.
    event.respondWith(fetch(event.request));
});

self.addEventListener('push', (event) => {
    if (!event.data) {
        return;
    }

    const data = event.data.json();
    const title = typeof data.title === 'string' && data.title.length > 0
        ? data.title
        : 'Intranet NÉRÉ Mining';

    const urgent = data.urgent === true;

    event.waitUntil(Promise.all([
        self.registration.showNotification(title, {
            body: typeof data.body === 'string' ? data.body : '',
            icon: '/favicon.ico',
            badge: '/favicon.ico',
            tag: typeof data.tag === 'string' ? data.tag : undefined,
            requireInteraction: urgent,
            silent: false,
            data: { url: typeof data.url === 'string' ? data.url : '/' },
        }),
        // Les onglets ouverts de l'intranet jouent le son de l'intranet.
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windowClients) => {
            windowClients.forEach((client) => client.postMessage({ type: 'intranet-notification', urgent }));
        }),
    ]));
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();

    const requestedUrl = new URL(event.notification.data?.url ?? '/', self.location.origin);
    const targetUrl = requestedUrl.origin === self.location.origin
        ? requestedUrl.href
        : self.location.origin;

    event.waitUntil(self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(async (windowClients) => {
        for (const client of windowClients) {
            if (new URL(client.url).origin === self.location.origin) {
                await client.navigate(targetUrl);
                return client.focus();
            }
        }

        return self.clients.openWindow(targetUrl);
    }));
});
