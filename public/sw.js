const CACHE_NAME = 'ojt-tracker-static-v2';
const OFFLINE_URL = '/offline.html';
const PRECACHE_URLS = [
    OFFLINE_URL,
    '/offline-drafts.js',
    '/manifest.webmanifest',
    '/icons/icon-192.png',
    '/icons/icon-512.png',
    '/icons/maskable-192.png',
    '/icons/maskable-512.png',
];

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(CACHE_NAME).then((cache) => cache.addAll(PRECACHE_URLS)));
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(
                keys
                    .filter((key) => key.startsWith('ojt-tracker-static-') && key !== CACHE_NAME)
                    .map((key) => caches.delete(key)),
            ))
            .then(() => self.clients.claim()),
    );
});

function isPublicStaticAsset(url) {
    return url.origin === self.location.origin && (
        url.pathname.startsWith('/build/assets/')
        || url.pathname.startsWith('/icons/')
        || url.pathname === '/manifest.webmanifest'
        || url.pathname === OFFLINE_URL
        || url.pathname === '/offline-drafts.js'
    );
}

self.addEventListener('fetch', (event) => {
    const request = event.request;
    const url = new URL(request.url);

    if (request.method !== 'GET' || url.origin !== self.location.origin) {
        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request, { cache: 'no-store' })
                .catch(() => caches.match(OFFLINE_URL)),
        );
        return;
    }

    if (!isPublicStaticAsset(url)) {
        return;
    }

    event.respondWith(
        caches.match(request).then((cached) => cached || fetch(request).then((response) => {
            if (response.ok && response.type === 'basic') {
                const copy = response.clone();
                caches.open(CACHE_NAME).then((cache) => cache.put(request, copy));
            }
            return response;
        })),
    );
});

self.addEventListener('sync', (event) => {
    if (event.tag !== 'ojt-draft-sync') {
        return;
    }

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clients) => {
            clients.forEach((client) => client.postMessage({ type: 'SYNC_OFFLINE_DRAFTS' }));
        }),
    );
});
