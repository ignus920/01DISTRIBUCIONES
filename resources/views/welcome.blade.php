<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Tienda Multitenancy</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,600&display=swap" rel="stylesheet" />

        <!-- Styles -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <!-- SweetAlert2 -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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

            <!-- Mobile menu -->
            <div id="mobile-menu" class="lg:hidden hidden">
                <div class="fixed inset-0 z-50"></div>
                <div class="fixed inset-y-0 right-0 z-50 w-full overflow-y-auto bg-white dark:bg-gray-900 px-6 py-6 sm:max-w-sm sm:ring-1 sm:ring-gray-900/10 dark:sm:ring-gray-100/10">
                    <div class="flex items-center justify-between">
                        <a href="#inicio" class="-m-1.5 p-1.5">
                            <span class="sr-only">Tienda</span>
                            <h1 class="text-xl font-bold text-gray-900 dark:text-white">Tienda</h1>
                        </a>
                        <button type="button" id="mobile-menu-close" class="-m-2.5 rounded-md p-2.5 text-gray-700 dark:text-gray-400">
                            <span class="sr-only">Cerrar menú</span>
                            <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>
                    <div class="mt-6 flow-root">
                        <div class="-my-6 divide-y divide-gray-500/10 dark:divide-gray-400/10">
                            <div class="space-y-2 py-6">
                                <a href="#inicio" class="-mx-3 block rounded-lg px-3 py-2 text-base font-semibold leading-7 text-gray-900 dark:text-white hover:bg-gray-50 dark:hover:bg-gray-800">Inicio</a>
                                <a href="#productos" class="-mx-3 block rounded-lg px-3 py-2 text-base font-semibold leading-7 text-gray-900 dark:text-white hover:bg-gray-50 dark:hover:bg-gray-800">Productos</a>
                                <a href="#servicios" class="-mx-3 block rounded-lg px-3 py-2 text-base font-semibold leading-7 text-gray-900 dark:text-white hover:bg-gray-50 dark:hover:bg-gray-800">Servicios</a>
                                <a href="#contacto" class="-mx-3 block rounded-lg px-3 py-2 text-base font-semibold leading-7 text-gray-900 dark:text-white hover:bg-gray-50 dark:hover:bg-gray-800">Contacto</a>
                            </div>
                            @if (Route::has('login'))
                                <div class="py-6">
                                    <div class="-mx-3 px-3">
                                        <livewire:welcome.navigation />
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </header>

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

                // Función para alternar el menú móvil
                function toggleMobileMenu() {
                    mobileMenu.classList.toggle('hidden');
                    document.body.classList.toggle('overflow-hidden');
                }

                // Event listeners para el menú móvil
                if (mobileMenuButton) {
                    mobileMenuButton.addEventListener('click', toggleMobileMenu);
                }

                if (mobileMenuClose) {
                    mobileMenuClose.addEventListener('click', toggleMobileMenu);
                }

                // Cerrar menú móvil al hacer click fuera
                mobileMenu.addEventListener('click', function(e) {
                    if (e.target === mobileMenu) {
                        toggleMobileMenu();
                    }
                });

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
