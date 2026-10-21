self.addEventListener('push', (event) => {
    if (!event.data) {
        return;
    }

    const data = event.data.json();
    const title = typeof data.title === 'string' && data.title.length > 0
        ? data.title
        : 'Intranet NÉRÉ Mining';

    event.waitUntil(self.registration.showNotification(title, {
        body: typeof data.body === 'string' ? data.body : '',
        icon: '/favicon.ico',
        badge: '/favicon.ico',
        tag: typeof data.tag === 'string' ? data.tag : undefined,
        data: { url: typeof data.url === 'string' ? data.url : '/' },
    }));
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
