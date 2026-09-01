const BAMA_SW_VERSION = 'bama-pwa-v1';
const STATIC_CACHE = `${BAMA_SW_VERSION}-static`;
const RUNTIME_CACHE = `${BAMA_SW_VERSION}-runtime`;

const CORE_ASSETS = [
    '/offline.html',
    '/manifest.webmanifest',
    '/favicon.ico',
    '/images/bama-favicon.png',
    '/images/bama-logo.png',
    '/images/bama-logo-cropped.png',
    '/images/bama-solutions-02.png',
    '/pwa-icons/icon-192.png',
    '/pwa-icons/icon-512.png',
    '/pwa-icons/maskable-192.png',
    '/pwa-icons/maskable-512.png'
];

const SAFE_STATIC_PATHS = [
    '/build/',
    '/images/bama-',
    '/pwa-icons/'
];

const PRIVATE_PATHS = [
    '/api/',
    '/uploads/',
    '/storage/',
    '/invoice/',
    '/invoices',
    '/receipts',
    '/payments',
    '/finance',
    '/reports',
    '/communication',
    '/messages',
    '/administration'
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(STATIC_CACHE)
            .then((cache) => cache.addAll(CORE_ASSETS))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys
                .filter((key) => ! [STATIC_CACHE, RUNTIME_CACHE].includes(key))
                .map((key) => caches.delete(key))
            ))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('message', (event) => {
    if (event.data?.type === 'SKIP_WAITING') {
        self.skipWaiting();
    }
});

self.addEventListener('fetch', (event) => {
    const { request } = event;

    if (request.method !== 'GET') return;

    const url = new URL(request.url);

    if (url.origin !== self.location.origin) {
        return;
    }

    if (request.mode === 'navigate') {
        event.respondWith(
            fetch(request).catch(() => caches.match('/offline.html'))
        );

        return;
    }

    if (isPrivatePath(url.pathname) || ! isSafeStaticRequest(request, url)) {
        return;
    }

    event.respondWith(staleWhileRevalidate(request));
});

function isPrivatePath(pathname) {
    return PRIVATE_PATHS.some((path) => pathname === path || pathname.startsWith(path));
}

function isSafeStaticRequest(request, url) {
    if (SAFE_STATIC_PATHS.some((path) => url.pathname.startsWith(path))) {
        return true;
    }

    return ['style', 'script', 'font'].includes(request.destination);
}

async function staleWhileRevalidate(request) {
    const cache = await caches.open(RUNTIME_CACHE);
    const cached = await cache.match(request);
    const network = fetch(request)
        .then((response) => {
            if (response.ok) {
                cache.put(request, response.clone());
            }

            return response;
        })
        .catch(() => cached);

    return cached || network;
}
