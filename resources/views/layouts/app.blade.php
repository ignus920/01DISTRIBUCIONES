<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <!-- PWA Meta Tags -->
        <meta name="theme-color" content="#ffffff">
        <link rel="manifest" href="{{ asset('build/manifest.webmanifest') }}" crossorigin="use-credentials">
        <link rel="apple-touch-icon" href="{{ asset('icons/icon-192x192.png') }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
        @livewireStyles

        <!-- Dexie.js para base de datos local (Soporte Offline) -->
        <script src="https://cdn.jsdelivr.net/npm/dexie@4.0.1/dist/dexie.min.js"></script>
        <script>
            /**
             * Inicialización global de la base de datos IndexedDB
             */
            try {
                const db = new Dexie('01DistribucionesDB');
                db.version(2).stores({
                    pedidos: '++id, cliente_nombre, fecha, sincronizado',
                    productos: 'id, name, sku, category_id, display_name',
                    categorias: 'id, name',
                    clientes: 'id, full_name, identification'
                });
                window.db = db;
                console.log('✅ IndexedDB: Base de datos inicializada globalmente.');
            } catch (e) {
                console.error('❌ Error inicializando IndexedDB:', e);
            }
        </script>

        <!-- Registro del Service Worker para PWA -->
        <script>
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', () => {
                    navigator.serviceWorker.register("{{ asset('build/sw.js') }}");
                });
            }
        </script>

        <!-- SweetAlert2 CDN fallback -->
        <script>
            // Verifica si SweetAlert2 se cargó vía Vite, si no, lo carga desde el CDN
            document.addEventListener('DOMContentLoaded', function() {

                if (typeof window.Swal === 'undefined') {
                    console.log('Loading SweetAlert2 from CDN as fallback');
                    const script = document.createElement('script');
                    script.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';
                    script.onload = function() {
                        console.log('SweetAlert2 loaded from CDN');
                    };
                    document.head.appendChild(script);
                } else {
                    console.log('SweetAlert2 loaded from Vite bundle');
                }
            });
        </script>
    </head>
    <body class="font-sans antialiased"
          x-data="{
              sidebarOpen: false,
              sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true' || false,
              darkMode: localStorage.getItem('darkMode') === 'true' || false
          }"
          x-init="
              $watch('sidebarOpen', value => {
                  if (value) document.body.style.overflow = 'hidden';
                  else document.body.style.overflow = 'auto';
              });
              $watch('sidebarCollapsed', value => localStorage.setItem('sidebarCollapsed', value));
              $watch('darkMode', value => localStorage.setItem('darkMode', value));
          "
          :class="darkMode ? 'dark' : ''">

        <!-- Mobile sidebar overlay -->
        @if(auth()->user()->profile_id != 13)
        <div x-show="sidebarOpen" x-transition:enter="transition-opacity ease-linear duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-linear duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 z-40 lg:hidden">
            <div class="fixed inset-0 bg-gray-600 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-80" @click="sidebarOpen = false"></div>
        </div>
        @endif

        <!-- Mobile sidebar -->
        @if(auth()->user()->profile_id != 13)
        <div x-show="sidebarOpen" x-transition:enter="transition ease-in-out duration-300 transform" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in-out duration-300 transform" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full" class="fixed inset-y-0 left-0 z-50 w-64 bg-white dark:bg-gray-900 shadow-xl lg:hidden border-r border-gray-200 dark:border-gray-700">
            <div class="flex h-full flex-col">
                <livewire:layout.sidebar-navigation />
            </div>
        </div>
        @endif

        <!-- Desktop sidebar -->
        @if(auth()->user()->profile_id != 13)
        <div class="hidden lg:fixed lg:inset-y-0 lg:flex lg:flex-col transition-all duration-300 z-30"
             :class="sidebarCollapsed ? 'lg:w-16' : 'lg:w-64'">
            <div class="flex min-h-0 flex-1 flex-col bg-white dark:bg-gray-900 shadow-xl border-r border-gray-200 dark:border-gray-700">
                <livewire:layout.sidebar-navigation />
            </div>
        </div>
        @endif

        <!-- Main content -->
        <div class="flex flex-1 flex-col min-h-screen transition-all duration-300 bg-gray-50 dark:bg-gray-900"
             @if(auth()->user()->profile_id == 13)
                 :class="'lg:pl-0'"
             @else
                 :class="sidebarCollapsed ? 'lg:pl-16' : 'lg:pl-64'"
             @endif
        >
            <!-- Top bar - Oculto en móvil para la página de quoter TAT -->
            <div class="sticky top-0 z-10 bg-white dark:bg-gray-800 shadow-sm border-b border-gray-200 dark:border-gray-700
                        {{ request()->routeIs('tenant.tat.quoter.index') ? 'hidden lg:block' : '' }}">
                <div class="flex h-16 items-center gap-x-4 px-4 sm:gap-x-6 sm:px-6 lg:px-8">
                    <!-- Desktop sidebar toggle -->
                    @if(auth()->user()->profile_id != 13)
                    <button type="button" class="hidden lg:block -m-2.5 p-2.5 text-gray-700 dark:text-gray-300 hover:text-indigo-600 dark:hover:text-indigo-400" @click="sidebarCollapsed = !sidebarCollapsed">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                    </button>
                    @endif

                    <!-- Mobile menu button -->
                    @if(auth()->user()->profile_id != 13)
                    <button type="button" class="-m-2.5 p-2.5 text-gray-700 dark:text-gray-300 lg:hidden" @click="sidebarOpen = true">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                    </button>
                    @endif

                    <!-- Botones de navegación rápida (solo para profile_id == 17) -->
                    @auth
                        <!-- DEBUG: Profile ID = {{ auth()->user()->profile_id ?? 'NULL' }} -->
                        @if(auth()->user()->profile_id == 17)
                            <div class="flex items-center gap-3 sm:gap-2 ml-2 sm:ml-4">
                                <!-- Inventario -->
                                <!--<div class="relative group">
                                    <button class="w-9 h-9 sm:w-10 sm:h-10 bg-red-500 dark:bg-red-600 text-white rounded border-2 border-red-600 dark:border-red-700 font-bold hover:bg-red-600 dark:hover:bg-red-700 transition-all duration-200 flex items-center justify-center shadow-md hover:scale-110 active:scale-95"
                                            title="Inventario">
                                        <span class="text-xs sm:text-sm font-bold">I</span>
                                    </button>
                                    <!-- Tooltip para desktop -->
                                    <div class="hidden sm:block absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 bg-gray-800 dark:bg-gray-700 text-white text-xs rounded shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-50">
                                        Inventario
                                        <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-t-4 border-l-transparent border-r-transparent border-t-gray-800 dark:border-t-gray-700"></div>
                                    </div>
                                <!--</div>-->

                                <!-- Surtir -->
                                <!--<div class="relative group">
                                    <a href="{{ route('tenant.quoter.products') }}"
                                       class="w-9 h-9 sm:w-10 sm:h-10 bg-red-500 dark:bg-red-600 text-white rounded border-2 border-red-600 dark:border-red-700 font-bold hover:bg-red-600 dark:hover:bg-red-700 transition-all duration-200 flex items-center justify-center shadow-md hover:scale-110 active:scale-95"
                                       title="Surtir">
                                        <span class="text-xs sm:text-sm font-bold">S</span>
                                    </a>
                                    <!-- Tooltip para desktop -->
                                    <div class="hidden sm:block absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 bg-gray-800 dark:bg-gray-700 text-white text-xs rounded shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-50">
                                        Surtir
                                        <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-t-4 border-l-transparent border-r-transparent border-t-gray-800 dark:border-t-gray-700"></div>
                                    </div>
                                <!--</div>-->

                                <!-- Finalizar -->
                                <!--<div class="relative group">
                                    <button class="w-9 h-9 sm:w-10 sm:h-10 bg-purple-500 dark:bg-purple-600 text-white rounded border-2 border-purple-600 dark:border-purple-700 font-bold hover:bg-purple-600 dark:hover:bg-purple-700 transition-all duration-200 flex items-center justify-center shadow-md hover:scale-110 active:scale-95"
                                            title="Finalizar">
                                        <span class="text-xs sm:text-sm font-bold">F</span>
                                    </button>
                                    <!-- Tooltip para desktop -->
                                    <div class="hidden sm:block absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 bg-gray-800 dark:bg-gray-700 text-white text-xs rounded shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-50">
                                        Finalizar
                                        <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-t-4 border-l-transparent border-r-transparent border-t-gray-800 dark:border-t-gray-700"></div>
                                    </div>
                               <!----  </div> -->

                                <!-- Historial 
                                <div class="relative group">
                                    <button class="w-9 h-9 sm:w-10 sm:h-10 bg-orange-500 dark:bg-orange-600 text-white rounded border-2 border-orange-600 dark:border-orange-700 font-bold hover:bg-orange-600 dark:hover:bg-orange-700 transition-all duration-200 flex items-center justify-center shadow-md hover:scale-110 active:scale-95"
                                            title="Historial">
                                        <span class="text-xs sm:text-sm font-bold">H</span>
                                    </button>
                                    <!-- Tooltip para desktop -->
                                    <div class="hidden sm:block absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 bg-gray-800 dark:bg-gray-700 text-white text-xs rounded shadow-lg opacity-0 group-hover:opacity-100 transition-opacity duration-200 pointer-events-none whitespace-nowrap z-50">
                                        Historial
                                        <div class="absolute top-full left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-4 border-r-4 border-t-4 border-l-transparent border-r-transparent border-t-gray-800 dark:border-t-gray-700"></div>
                                    </div>
                               <!---- </div> -->
                            </div>
                        @endif
                    @endauth

                    <!-- Page title -->
                    <div class="flex flex-1">
                        @if (isset($header))
                            <h1 class="text-xl font-semibold text-gray-900 dark:text-white">{{ $header }}</h1>
                        @endif
                    </div>

                    <!-- Dark mode toggle -->
                    <button @click="darkMode = !darkMode" class="p-2 text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                        <svg x-show="!darkMode" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                        </svg>
                        <svg x-show="darkMode" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                    </button>

                    

                    <!-- User menu -->
                    <livewire:layout.user-menu />
                </div>
            </div>

            <!-- Page content -->
            <main class="flex-1 {{ request()->routeIs('tenant.tat.quoter.index') || auth()->user()->profile_id == 13 ? 'lg:pt-0' : '' }}">
            {{ $slot }}
            </main>
        </div>

        @livewireScripts
        @stack('scripts')
    </body>
</html>
