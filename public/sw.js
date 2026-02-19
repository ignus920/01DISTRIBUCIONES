/*
* Sistema de Service Worker Manual - DOSIL ERP
* v27 - Estrategia Ultra-Rápida (Stale-While-Revalidate)
*/
const CACHE_NAME = 'quoter-cache-v27';
const PRECACHE_ASSETS = [
    '/',
    '/manifest.json',
    '/Logo_DosilERPFinal.png',
    '/logo.png',
    '/pwa-icons/icon-192x192.png',
    '/pwa-icons/icon-512x512.png',
    '/tenant/quoter/mobile',
    '/tenant/quoter/products/mobile',
    '/tenant/remissions',
    '/tenant/tat-quoter',
    '/tenant/tat-sales'
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            console.log('📦 Precargando v27...');
            return Promise.allSettled(
                PRECACHE_ASSETS.map(asset =>
                    cache.add(asset).catch(err => console.warn(`⚠️ Error precargando ${asset}:`, err))
                )
            );
        })
    );
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return Promise.all(
                cacheNames.map((cacheName) => {
                    if (cacheName !== CACHE_NAME) {
                        console.log('🧹 Limpiando caché antigua:', cacheName);
                        return caches.delete(cacheName);
                    }
                })
            );
        })
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') return;

    const url = new URL(event.request.url);
    const isLivewire = event.request.headers.get('X-Livewire');
    const isNavigation = event.request.mode === 'navigate';

    // 1. RUTAS CRÍTICAS (Cotizador y Productos) - STALE WHILE REVALIDATE
    if (isNavigation && (url.pathname.includes('/quoter') || url.pathname.includes('/products/mobile'))) {
        event.respondWith(
            caches.match(event.request, { ignoreSearch: true }).then((cachedResponse) => {
                const fetchPromise = fetch(event.request).then((networkResponse) => {
                    if (networkResponse && networkResponse.status === 200) {
                        const copy = networkResponse.clone();
                        caches.open(CACHE_NAME).then((cache) => cache.put(event.request, copy));
                    }
                    return networkResponse;
                }).catch(() => null);

                return cachedResponse || fetchPromise || new Response("Offline (No Cache)", { status: 503 });
            })
        );
        return;
    }

    // 2. OTROS NAVEGACIONES Y LIVEWIRE - Network First
    if (isNavigation || isLivewire) {
        event.respondWith(
            fetch(event.request)
                .then((networkResponse) => {
                    if (networkResponse && networkResponse.status === 200) {
                        const copy = networkResponse.clone();
                        caches.open(CACHE_NAME).then((cache) => cache.put(event.request, copy));
                    }
                    return networkResponse;
                })
                .catch(async () => {
                    const cached = await caches.match(event.request, { ignoreSearch: true });
                    if (cached) return cached;

                    if (url.pathname.includes('/products/mobile')) return await caches.match('/tenant/quoter/products/mobile');
                    if (url.pathname.includes('/quoter')) return await caches.match('/tenant/quoter/mobile');

                    return await caches.match('/') || new Response("Offline", { status: 503 });
                })
        );
        return;
    }

    // 3. ACTIVOS (CSS, JS, Imágenes)
    const isAsset =
        url.pathname.includes('/build/') ||
        event.request.destination === 'style' ||
        event.request.destination === 'script' ||
        event.request.destination === 'image' ||
        event.request.destination === 'font';

    if (isAsset) {
        event.respondWith(
            caches.match(event.request).then((cachedResponse) => {
                const networkFetch = fetch(event.request).then((networkResponse) => {
                    if (networkResponse && networkResponse.status === 200) {
                        const responseToCache = networkResponse.clone();
                        caches.open(CACHE_NAME).then((cache) => cache.put(event.request, responseToCache));
                    }
                    return networkResponse;
                }).catch(() => null);
                return cachedResponse || networkFetch;
            })
        );
    }
});
