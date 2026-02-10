import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
    server: {
        host: '192.168.1.17',
        port: 5173,
        strictPort: true,
    },
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
            ],
            refresh: true,
        }),
        VitePWA({
            registerType: 'autoUpdate',
            injectRegister: 'auto',
            manifest: {
                id: '/',
                name: 'DOSIL ERP',
                short_name: 'DOSILERP',
                description: 'Sistema de Distribución y Ventas',
                theme_color: '#ffffff',
                background_color: '#ffffff',
                display: 'standalone',
                prefer_related_applications: false,
                orientation: 'any',
                scope: '/',
                start_url: '/',
                icons: [
                    {
                        src: '/pwa-icons/icon-192x192.png',
                        sizes: '192x192',
                        type: 'image/png',
                        purpose: 'any'
                    },
                    {
                        src: '/pwa-icons/icon-192x192.png',
                        sizes: '192x192',
                        type: 'image/png',
                        purpose: 'maskable'
                    },
                    {
                        src: '/pwa-icons/icon-512x512.png',
                        sizes: '512x512',
                        type: 'image/png',
                        purpose: 'any'
                    },
                    {
                        src: '/pwa-icons/icon-512x512.png',
                        sizes: '512x512',
                        type: 'image/png',
                        purpose: 'maskable'
                    }
                ]
            }
        })
    ],
    build: {
        rollupOptions: {
            external: ['sweetalert2'],
        },
    },
});
