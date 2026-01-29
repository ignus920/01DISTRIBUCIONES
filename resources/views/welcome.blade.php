<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <!-- PWA Meta Tags -->
        <meta name="theme-color" content="#ffffff">
        <link rel="manifest" href="{{ asset('build/manifest.webmanifest') }}" crossorigin="use-credentials">
        <link rel="apple-touch-icon" href="{{ asset('icons/icon-192x192.png') }}">

        <title>Tienda Multitenancy</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />

        <!-- Styles -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- SweetAlert2 -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

        <!-- Registro del Service Worker para PWA -->
        <script>
            if ('serviceWorker' in navigator) {
                window.addEventListener('load', () => {
                    navigator.serviceWorker.register("{{ asset('build/sw.js') }}");
                });
            }
        </script>

    </head>
    <body class="antialiased font-sans">
        <!-- Navbar Sticky -->
        <header id="navbar" class="fixed top-0 left-0 right-0 z-50 transition-all duration-300 ease-in-out bg-white/80 backdrop-blur-lg border-b border-gray-200/20 dark:bg-gray-900/80 dark:border-gray-700/20 shadow-sm">
            <nav aria-label="Global" class="mx-auto flex max-w-7xl items-center justify-between px-6 py-4 lg:px-8">
                <!-- Logo -->
                <div class="flex lg:flex-1">
                    <a href="#inicio" class="-m-1.5 p-1.5">
                        <span class="sr-only">Tienda</span>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Tienda</h1>
                    </a>
                </div>

                <!-- Mobile menu button -->
                <div class="flex lg:hidden">
                    <button type="button" id="mobile-menu-button" class="-m-2.5 inline-flex items-center justify-center rounded-md p-2.5 text-gray-700 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800">
                        <span class="sr-only">Abrir menú principal</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                    </button>
                </div>

                <!-- Desktop navigation -->
                <div class="hidden lg:flex lg:gap-x-8">
                    <a href="#inicio" class="text-sm font-semibold leading-6 text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 transition-colors px-3 py-2 rounded-md">Inicio</a>
                    <a href="#productos" class="text-sm font-semibold leading-6 text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 transition-colors px-3 py-2 rounded-md">Productos</a>
                    <a href="#servicios" class="text-sm font-semibold leading-6 text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 transition-colors px-3 py-2 rounded-md">Servicios</a>
                    <a href="#contacto" class="text-sm font-semibold leading-6 text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 transition-colors px-3 py-2 rounded-md">Contacto</a>
                </div>

                <!-- Auth navigation -->
                @if (Route::has('login'))
                    <div class="hidden lg:flex lg:flex-1 lg:justify-end lg:gap-x-4">
                        <livewire:welcome.navigation />
                    </div>
                @endif
            </nav>

        </header>

        <!-- Sidebar Móvil de Pantalla Completa (Estilo AdminLTE) -->
        <div id="mobile-menu" class="hidden fixed inset-0 z-[100] h-screen overflow-hidden">
            <!-- Backdrop Oscuro -->
            <div id="mobile-menu-backdrop" class="fixed inset-0 bg-gray-900/60 transition-opacity duration-500 opacity-0"></div>
            
            <!-- Contenedor del Sidebar (Izquierda) -->
            <div id="mobile-menu-content" class="fixed inset-y-0 left-0 w-[280px] h-full bg-[#343a40] text-white shadow-2xl transform -translate-x-full transition-all duration-500 ease-in-out flex flex-col z-10">
                
                <!-- Header del Sidebar (Fijo arriba) -->
                <div class="flex-none px-6 py-4 flex items-center justify-between border-b border-gray-700 bg-[#2b3035]">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 rounded bg-blue-500 flex items-center justify-center font-bold text-white shadow-lg">T</div>
                        <span class="text-xl font-bold tracking-tight">Tienda</span>
                    </div>
                    <button type="button" id="mobile-menu-close" class="p-2 -mr-2 text-gray-400 hover:text-white transition-colors">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                

                <!-- Menú de Navegación -->
                <div class="flex-1 overflow-y-auto no-scrollbar py-4">
                    <nav class="px-2 space-y-1">
                        <a href="#inicio" class="flex items-center gap-3 px-4 py-3 rounded text-gray-300 hover:bg-white/10 hover:text-white transition-all">
                            <svg class="w-5 h-5 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                            <span>Inicio</span>
                        </a>
                        <a href="#productos" class="flex items-center gap-3 px-4 py-3 rounded text-gray-300 hover:bg-white/10 hover:text-white transition-all">
                            <svg class="w-5 h-5 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            <span>Productos</span>
                        </a>
                        <a href="#servicios" class="flex items-center gap-3 px-4 py-3 rounded text-gray-300 hover:bg-white/10 hover:text-white transition-all">
                            <svg class="w-5 h-5 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            <span>Servicios</span>
                        </a>
                        <a href="#contacto" class="flex items-center gap-3 px-4 py-3 rounded text-gray-300 hover:bg-white/10 hover:text-white transition-all pb-4 border-b border-gray-700">
                            <svg class="w-5 h-5 opacity-70" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            <span>Contacto</span>
                        </a>
                        
                        <!-- Acceso al Programa (Integrado) -->
                        <div class="pt-2">
                             <livewire:welcome.navigation />
                        </div>
                    </nav>
                </div>
            </div>
            </div>

            <!-- Estilo para ocultar scrollbar del sidebar -->
            <style>
                .no-scrollbar::-webkit-scrollbar { display: none; }
                .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
            </style>
        </div>

        <div class="bg-gray-50 text-black/50 dark:bg-black dark:text-white/50">
            <div class="relative min-h-screen pt-24">
                <div class="relative w-full max-w-7xl mx-auto px-6">
                    <!-- Hero Section -->
                    <section id="inicio" class="py-20">
                        <div class="text-center">
                            <h1 class="text-4xl md:text-6xl font-bold text-gray-900 dark:text-white mb-6">
                                Bienvenido a Tienda
                            </h1>
                            <p class="text-xl text-gray-600 dark:text-gray-300 mb-8">
                                Plataforma multitenancy para tu negocio
                            </p>
                        </div>
                    </section>

                    <!-- Productos Section -->
                    <section id="productos" class="py-20">
                        <div class="text-center mb-12">
                            <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">Nuestros Productos</h2>
                            <p class="text-lg text-gray-600 dark:text-gray-300">Descubre lo que tenemos para ti</p>
                        </div>
                        <div class="grid gap-6 lg:grid-cols-2 lg:gap-8">


                            <!-- Formulario de Registro -->
                            <div class="bg-white rounded-lg shadow-lg p-6 dark:bg-zinc-900">
                                <h3 class="text-2xl font-bold text-center mb-6 text-gray-900 dark:text-white">Registro</h3>
                                @livewire('auth.register-company')
                            </div>

                            

                            <!-- Información adicional -->
                            <div class="bg-white rounded-lg shadow-lg p-6 dark:bg-zinc-900">
                                <h3 class="text-2xl font-bold text-center mb-6 text-gray-900 dark:text-white">Características</h3>
                                <ul class="text-gray-600 dark:text-gray-300 space-y-3">
                                    <li class="flex items-center">
                                        <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                        Sistema multitenancy
                                    </li>
                                    <li class="flex items-center">
                                        <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                        Gestión de inventarios
                                    </li>
                                    <li class="flex items-center">
                                        <svg class="w-5 h-5 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                        Reportes avanzados
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </section>

                    <!-- Servicios Section -->
                    <section id="servicios" class="py-20 bg-gray-100 dark:bg-gray-800">
                        <div class="text-center mb-12">
                            <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">Nuestros Servicios</h2>
                            <p class="text-lg text-gray-600 dark:text-gray-300">Soluciones completas para tu negocio</p>
                        </div>
                        <div class="grid gap-8 md:grid-cols-3">
                            <div class="bg-white rounded-lg shadow-lg p-6 dark:bg-zinc-900">
                                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Gestión de Inventario</h3>
                                <p class="text-gray-600 dark:text-gray-300">Control total de tu inventario con seguimiento en tiempo real.</p>
                            </div>
                            <div class="bg-white rounded-lg shadow-lg p-6 dark:bg-zinc-900">
                                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Ventas y Facturación</h3>
                                <p class="text-gray-600 dark:text-gray-300">Sistema completo de ventas y generación de facturas.</p>
                            </div>
                            <div class="bg-white rounded-lg shadow-lg p-6 dark:bg-zinc-900">
                                <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-4">Reportes</h3>
                                <p class="text-gray-600 dark:text-gray-300">Análisis detallado de tu negocio con reportes personalizados.</p>
                            </div>
                        </div>
                    </section>

                    <!-- Contacto Section -->
                    <section id="contacto" class="py-20">
                        <div class="text-center">
                            <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">Contáctanos</h2>
                            <p class="text-lg text-gray-600 dark:text-gray-300 mb-8">¿Tienes preguntas? Estamos aquí para ayudarte</p>
                            <div class="max-w-md mx-auto">
                                <p class="text-gray-600 dark:text-gray-300">Email: contacto@tienda.com</p>
                                <p class="text-gray-600 dark:text-gray-300">Teléfono: +1 234 567 8900</p>
                            </div>
                        </div>
                    </section>

                    <footer class="py-16 text-center text-sm text-gray-600 dark:text-gray-400">
                        © {{ date('Y') }} Tienda Multitenancy. Todos los derechos reservados.
                    </footer>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const navbar = document.getElementById('navbar');
                const mobileMenuButton = document.getElementById('mobile-menu-button');
                const mobileMenu = document.getElementById('mobile-menu');
                const mobileMenuClose = document.getElementById('mobile-menu-close');
                let lastScrollY = window.scrollY;

                // Función para alternar el menú móvil con animaciones corregidas y robustas
                function toggleMobileMenu() {
                    const isHidden = mobileMenu.classList.contains('hidden');
                    const backdrop = document.getElementById('mobile-menu-backdrop');
                    const content = document.getElementById('mobile-menu-content');

                    if (isHidden) {
                        mobileMenu.classList.remove('hidden');
                        document.body.style.overflow = 'hidden';
                        
                        // Estado inicial para animar desde la izquierda (AdminLTE style)
                        backdrop.style.opacity = '0';
                        content.style.transform = 'translateX(-100%)';
                        
                        setTimeout(() => {
                            backdrop.classList.replace('opacity-0', 'opacity-100');
                            content.classList.replace('-translate-x-full', 'translate-x-0');
                            backdrop.style.opacity = '';
                            content.style.transform = '';
                        }, 50);
                    } else {
                        // Salida hacia la izquierda
                        backdrop.classList.replace('opacity-100', 'opacity-0');
                        content.classList.replace('translate-x-0', '-translate-x-full');
                        
                        setTimeout(() => {
                            mobileMenu.classList.add('hidden');
                            document.body.style.overflow = '';
                        }, 500);
                    }
                }

                // Event listeners para el menú móvil
                if (mobileMenuButton) {
                    mobileMenuButton.addEventListener('click', toggleMobileMenu);
                }

                if (mobileMenuClose) {
                    mobileMenuClose.addEventListener('click', toggleMobileMenu);
                }

                // Cerrar menú móvil al hacer click fuera (en el backdrop)
                document.getElementById('mobile-menu-backdrop').addEventListener('click', toggleMobileMenu);

                // Función para actualizar el navbar en scroll
                function updateNavbar() {
                    const currentScrollY = window.scrollY;

                    if (currentScrollY > 50) {
                        navbar.classList.add('bg-white/95', 'dark:bg-gray-900/95');
                        navbar.classList.remove('bg-white/80', 'dark:bg-gray-900/80');
                    } else {
                        navbar.classList.add('bg-white/80', 'dark:bg-gray-900/80');
                        navbar.classList.remove('bg-white/95', 'dark:bg-gray-900/95');
                    }

                    lastScrollY = currentScrollY;
                }

                // Smooth scrolling para los enlaces del nav
                document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                    anchor.addEventListener('click', function (e) {
                        e.preventDefault();
                        const target = document.querySelector(this.getAttribute('href'));
                        if (target) {
                            const offsetTop = target.offsetTop - 100; // Ajuste para el navbar
                            window.scrollTo({
                                top: offsetTop,
                                behavior: 'smooth'
                            });

                            // Cerrar menú móvil si está abierto
                            if (!mobileMenu.classList.contains('hidden')) {
                                toggleMobileMenu();
                            }
                        }
                    });
                });

                // Listener para el scroll
                window.addEventListener('scroll', updateNavbar, { passive: true });

                // Cerrar menú móvil al redimensionar ventana
                window.addEventListener('resize', function() {
                    if (window.innerWidth >= 1024 && !mobileMenu.classList.contains('hidden')) {
                        toggleMobileMenu();
                    }
                });

                // Ejecutar una vez al cargar
                updateNavbar();
            });
        </script>

        @stack('scripts')
    </body>
</html>
