/*
* Sistema de Service Worker Manual - DOSIL ERP
* v29 - Offline robusto con fallback HTML
*/
const CACHE_NAME = 'quoter-cache-v124';
const PRECACHE_ASSETS = [
    '/',
    '/manifest.json',
    '/Logo_DosilERPFinal.png',
    '/logo.png',
    '/pwa-icons/icon-192x192.png',
    '/pwa-icons/icon-512x512.png',
    '/js/dexie.min.js',
    '/js/sweetalert2.min.js',
    '/tenant/quoter/mobile',
    '/tenant/quoter/products/mobile',
    '/tenant/remissions',
    '/tenant/tat-quoter',
    '/tenant/tat-sales',
    '/tenant/quoter/desktop',
    '/tenant/quoter/products/desktop'
];

const OFFLINE_HTML = `<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sin conexión - DOSIL ERP</title>
<style>
  body { font-family: sans-serif; display: flex; flex-direction: column; align-items: center;
         justify-content: center; min-height: 100vh; margin: 0; background: #f3f4f6; text-align: center; padding: 1rem; }
  .icon { font-size: 4rem; margin-bottom: 1rem; }
  h1 { color: #1f2937; font-size: 1.5rem; margin-bottom: 0.5rem; }
  p { color: #6b7280; margin-bottom: 1.5rem; }
  button { background: #2563eb; color: white; border: none; padding: 0.75rem 1.5rem;
           border-radius: 0.5rem; font-size: 1rem; cursor: pointer; }
</style>
</head>
<body>
  <div class="icon">📶</div>
  <h1>Sin conexión</h1>
  <p>No hay internet. La página se cargará cuando recuperes la conexión.</p>
  <button onclick="window.location.reload()">Reintentar</button>
</body>
</html>`;

function offlineFallback() {
    return new Response(OFFLINE_HTML, {
        status: 503,
        headers: { 'Content-Type': 'text/html; charset=utf-8' }
    });
}

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(CACHE_NAME).then((cache) => {
            console.log('📦 Precargando activos...');
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

    // 1. ESTRATEGIA PARA NAVEGACIÓN Y LIVEWIRE (Network-First con fallback offline)
    // Las páginas dinámicas NUNCA se sirven desde caché si hay red disponible.
    // La caché solo se usa como fallback cuando no hay conexión.
    if (isNavigation || isLivewire) {
        event.respondWith(
            caches.open(CACHE_NAME).then((cache) => {
                return fetch(event.request)
                    .then((networkResponse) => {
                        // SOLO CACHEAR SI ES EXITOSO Y NO ES UNA REDIRECCIÓN
                        if (networkResponse && networkResponse.status === 200 && !networkResponse.redirected) {
                            cache.put(event.request, networkResponse.clone());
                        }
                        return networkResponse;
                    })
                    .catch(async () => {
                        // Sin red: intentar servir desde caché
                        const cachedResponse = await cache.match(event.request, { ignoreSearch: true });
                        if (cachedResponse) {
                            return cachedResponse;
                        }

                        const path = url.pathname;
                        const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);

                        // REDIRECCIÓN LOCAL OFFLINE
                        if (path.includes('/quoter/products')) {
                            const target = isMobile ? '/tenant/quoter/products/mobile' : '/tenant/quoter/products/desktop';
                            console.log('🔄 Offline: Redirigiendo localmente a:', target);
                            return Response.redirect(target, 302);
                        }
                        if (path.includes('/quoter')) {
                            const target = isMobile ? '/tenant/quoter/mobile' : '/tenant/quoter/desktop';
                            console.log('🔄 Offline: Redirigiendo localmente a:', target);
                            return Response.redirect(target, 302);
                        }
                        // Fallback final
                        return (await cache.match('/')) || offlineFallback();
                    });
            })
        );
        return;
    }

    // 2. ACTIVOS (CSS, JS, Imágenes) - Cache First con actualización en background
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
