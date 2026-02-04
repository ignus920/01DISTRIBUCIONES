const CACHE_NAME = 'quoter-cache-v13';
// Lista de recursos críticos para precargar
const PRECACHE_ASSETS = [
    '/build/assets/app-OdaYl3l1.css',
    '/build/assets/app-Cp30cTxS.js',
    '/Logo_DosilERPFinal.png',
    '/logo.png',
    '/build/manifest.webmanifest',
    '/tenant/quoter/mobile', // Lista de cotizaciones
    '/tenant/quoter/products/mobile' // Editor de cotizaciones (CRÍTICO para offline)
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            console.log('📦 Precargando activos críticos...');
            // Usar una promesa para cada asset para que si uno falla no detenga todo
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

    // 1. Estrategia para Navegación (HTML): Network First, Fallback to Cache
    if (event.request.mode === 'navigate') {
        event.respondWith(
            fetch(event.request)
                .then((response) => {
                    if (response && response.status === 200) {
                        const copy = response.clone();
                        caches.open(CACHE_NAME).then((cache) => cache.put(event.request, copy));
                    }
                    return response;
                })
                .catch(() => caches.match(event.request) || caches.match('/tenant/quoter/mobile'))
        );
        return;
    }

    // 2. Estrategia para Activos (CSS, JS, Imágenes, Fuentes, CDNs): Cache First + Update
    const isAsset =
        url.pathname.includes('/build/') ||
        event.request.destination === 'style' ||
        event.request.destination === 'script' ||
        event.request.destination === 'image' ||
        event.request.destination === 'font' ||
        url.hostname.includes('fonts.bunny.net') ||
        url.hostname.includes('cdn.jsdelivr.net');

    if (isAsset) {
        event.respondWith(
            caches.match(event.request).then((cachedResponse) => {
                const networkFetch = fetch(event.request).then((networkResponse) => {
                    // Solo intentar cachear si la respuesta es válida y no se ha usado
                    if (networkResponse && networkResponse.status === 200) {
                        const responseToCache = networkResponse.clone();
                        caches.open(CACHE_NAME).then((cache) => {
                            cache.put(event.request, responseToCache).catch(err => {
                                console.warn('❌ Error al guardar en cache:', event.request.url, err);
                            });
                        });
                    }
                    return networkResponse;
                }).catch(() => null);

                // Servir desde caché si existe, si no, esperar a la red
                return cachedResponse || networkFetch;
            })
        );
    }
});
