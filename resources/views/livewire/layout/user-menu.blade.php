<?php

use App\Auth\Livewire\Logout;
use Livewire\Volt\Component;

new class extends Component
{
    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect(route('login'), navigate: true);
    }
}; ?>

@auth
<div class="flex items-center gap-x-4 lg:gap-x-6" x-data="{ open: false }">
    <!-- Notifications -->
    <button type="button" class="relative -m-2.5 p-2.5 text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-300 transition-colors">
        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
        </svg>
        <!-- Notification badge -->
        <span class="absolute top-1 right-1 h-2 w-2 rounded-full bg-red-500"></span>
    </button>

    <!-- Profile dropdown -->
    <div class="relative">
        <button type="button" class="-m-1.5 flex items-center p-1.5 hover:bg-gray-50 dark:hover:bg-gray-700 rounded-lg transition-colors" @click="open = !open">
            <span class="sr-only">Abrir menu usuario</span>
            <img id="header-avatar-button" class="h-8 w-8 rounded-full bg-gray-50 dark:bg-gray-700 ring-2 ring-gray-200 dark:ring-gray-600 object-cover" src="{{ auth()->user()->getAvatarUrl() }}" alt="{{ auth()->user()->name }}">
            <span class="hidden lg:flex lg:items-center">
                <span class="ml-3 text-sm font-semibold leading-6 text-gray-900 dark:text-white">{{ auth()->user()->name }}</span>
                <svg class="ml-2 h-4 w-4 text-gray-400 dark:text-gray-500 transition-transform" :class="open ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                </svg>
            </span>
        </button>

        <!-- Dropdown menu -->
        <div x-show="open" @click.away="open = false" x-transition:enter="transition ease-out duration-100" x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100" x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100" x-transition:leave-end="transform opacity-0 scale-95" class="absolute right-0 z-20 mt-2.5 w-56 origin-top-right rounded-lg bg-white dark:bg-gray-800 py-2 shadow-xl ring-1 ring-gray-900/5 dark:ring-gray-700">

            <!-- User info -->
            <div class="px-4 py-3 border-b border-gray-100 dark:border-gray-700">
                <div class="flex items-center space-x-3">
                    <img id="header-avatar-dropdown" class="h-10 w-10 rounded-full object-cover ring-2 ring-gray-200 dark:ring-gray-600" src="{{ auth()->user()->getAvatarUrl() }}" alt="{{ auth()->user()->name }}">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ auth()->user()->name }}</p>
                        <p class="text-sm text-gray-500 dark:text-gray-400 truncate">{{ auth()->user()->email }}</p>
                    </div>
                </div>
            </div>

            <!-- Menu items -->
            <div class="py-1">
                <a href="{{ route('profile') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors" @click="open = false">
                    <svg class="mr-3 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                    </svg>
                    Tu Perfil
                </a>

                <a href="{{ route('tenant.select') }}" class="flex items-center px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors" @click="open = false">
                    <svg class="mr-3 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    Cambiar Empresa
                </a>

                <div class="border-t border-gray-100 dark:border-gray-700 my-1"></div>

                <form method="POST" action="{{ route('logout') }}" class="w-full">
                    @csrf
                    <button type="submit" class="flex items-center w-full px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors" @click="open = false">
                        <svg class="mr-3 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                        Cerrar Sesión
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endauth

@guest
<div class="flex items-center gap-x-4 lg:gap-x-6">
    <a href="{{ route('login') }}" class="text-sm font-semibold leading-6 text-gray-900 dark:text-white">
        Iniciar Sesión
    </a>
</div>
@endguest

