<?php

use App\Auth\Livewire\Logout;
use App\Helpers\PermissionHelper;
use Livewire\Volt\Component;

new class extends Component
{
    public bool $contactsOpen = false;

    public function mount(): void
    {
        $persisted = session('sidebar.contactsOpen');
        if ($persisted !== null) {
            $this->contactsOpen = (bool) $persisted;
        } else {
            $this->contactsOpen = request()->routeIs('customers.*') || request()->routeIs('users.*');
        }
    }

    public function toggleContacts(): void
    {
        $this->contactsOpen = !$this->contactsOpen;
        session(['sidebar.contactsOpen' => $this->contactsOpen]);
    }

    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect(route('login'), navigate: true);
    }
}; ?>

<div class="flex h-full flex-col" :class="sidebarCollapsed ? 'overflow-visible' : 'overflow-y-auto'">
    <!-- Logo -->
    <div class="flex shrink-0 items-center justify-center px-4 py-6 border-b border-gray-200 dark:border-gray-700">
        <!-- LOGO -->
        <div class="flex items-center justify-center transition-all duration-300" 
             :class="sidebarCollapsed ? 'h-10 w-10' : 'h-16 w-full max-w-[160px]'">
            <img 
                src="{{ asset('Logo_DosilERPFinal.png') }}"
                alt="Logo Dosil ERP"
                class="h-full w-full object-contain transition-all duration-300"
            >
        </div>
    </div>


    <!-- Navigation -->
    <nav class="flex flex-1 flex-col p-4 space-y-1" x-data="{ 
        async startNewQuote() {
            if (window.db && window.db.estado_quoter) {
                try {
                    await window.db.estado_quoter.delete('actual');
                    console.log('🧹 Estado local limpiado desde sidebar');
                } catch (e) {
                    console.error('Error limpiando estado local:', e);
                }
            }
        }
    }">
        <!-- Empresas -->
         @if(auth()->user()->profile_id != 17)
        <a href="{{ route('tenant.select') }}" wire:navigate
            class="group flex items-center rounded-lg px-3 py-2.5 text-sm font-medium transition-all duration-200 {{ request()->routeIs('tenant.select') ? 'bg-indigo-50 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 border-r-2 border-indigo-500' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-indigo-600 dark:hover:text-indigo-400' }}"
            :class="sidebarCollapsed ? 'justify-center' : 'justify-start'" x-data="{ tooltip: false }"
            @mouseenter="tooltip = sidebarCollapsed" @mouseleave="tooltip = false">
            <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
            <span x-show="!sidebarCollapsed" x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-x-0" x-transition:leave-end="opacity-0 translate-x-4"
                class="ml-3">
                Dashboard
            </span> 
            <!-- Tooltip -->
            <div x-show="tooltip" x-transition
                class="absolute left-full ml-2 px-2 py-1 bg-gray-900 dark:bg-gray-700 text-white text-xs rounded shadow-lg z-50 whitespace-nowrap">
                Dashboard
            </div>
        </a>
        @endif


        <!-- Ventas -->
 @if(auth()->user()->profile_id === 17 && PermissionHelper::userCan('Ventas', 'show'))
<a href="{{ route('tenant.quoter.products', ['clear' => 1]) }}" @click="startNewQuote" wire:navigate
    class="group flex items-center rounded-lg px-3 py-2.5 text-sm font-medium transition-all duration-200
    {{ request()->routeIs('tenant.quoter.products*')
        ? 'bg-indigo-50 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 border-r-2 border-indigo-500'
        : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-indigo-600 dark:hover:text-indigo-400' }}"
    :class="sidebarCollapsed ? 'justify-center' : 'justify-start'"
    x-data="{ tooltip: false }"
    @mouseenter="tooltip = sidebarCollapsed"
    @mouseleave="tooltip = false">

    <!-- Icono -->
    <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z" />
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M8 5v6m4-6v6m4-6v6" />
    </svg>

    <span x-show="!sidebarCollapsed" class="ml-3" x-transition>
        Realizar pedido
    </span>

    <!-- Tooltip -->
    <div x-show="tooltip" x-transition
        class="absolute left-full ml-2 px-2 py-1 bg-gray-900 dark:bg-gray-700 text-white text-xs rounded shadow-lg z-50 whitespace-nowrap">
        Realizar pedido
    </div>
</a>
@endif







@if(auth()->user()->profile_id !== 17 && PermissionHelper::userCanAny(['Ventas'], 'show'))
<div 
    x-data="{ 
        tooltip: false, 
        open: {{ request()->routeIs('tenant.quoter.*') || request()->routeIs('tenant.remissions.*') ? 'true' : 'false' }} 
    }" 
    class="w-full relative"
>

    <!-- Botón principal -->
    <div
        class="group flex items-center rounded-lg px-3 py-2.5 text-sm font-medium transition-all duration-200
        {{ request()->routeIs('tenant.quoter.*') || request()->routeIs('tenant.remissions.*')
            ? 'text-indigo-600 dark:text-indigo-400 bg-indigo-50/50 dark:bg-indigo-900/20'
            : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-indigo-600 dark:hover:text-indigo-400' }}"
        :class="sidebarCollapsed ? 'justify-center' : 'justify-start'"
        @mouseenter="tooltip = sidebarCollapsed"
        @mouseleave="tooltip = false"
        @click="open = !open"
    >

        <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z" />
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M8 5v6m4-6v6m4-6v6" />
        </svg>

        <span x-show="!sidebarCollapsed" class="ml-3 flex-1" x-transition>
            Ventas
        </span>

        <svg x-show="!sidebarCollapsed" :class="open ? 'rotate-90' : ''"
            class="w-4 h-4 ml-auto transition-transform duration-200"
            fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
        </svg>

        <div x-show="tooltip" x-transition
            class="absolute left-full ml-2 px-2 py-1 bg-gray-900 dark:bg-gray-700 text-white text-xs rounded shadow-lg z-50 whitespace-nowrap">
            Ventas
        </div>
    </div>

    <!-- Submenú -->
     <div x-show="open && !sidebarCollapsed" x-transition
                class="ml-8 mt-1 space-y-1 text-sm text-gray-600 dark:text-gray-400">

        <a href="{{ route('tenant.quoter.products', ['clear' => 1]) }}" @click="startNewQuote" wire:navigate class="block px-2 py-1 hover:text-indigo-600">
            Ventas
        </a>

        <a href="{{ route('tenant.quoter') }}" wire:navigate class="block px-2 py-1 hover:text-indigo-600">
            Cotizaciones
        </a>

        <a href="{{ route('tenant.remissions') }}" wire:navigate class="block px-2 py-1 hover:text-indigo-600">
            Remisiones
        </a>
    </div>

    <!-- Submenú desplegable (para sidebar colapsado) -->
    <div x-show="sidebarCollapsed && tooltip" x-transition
        class="absolute left-full ml-2 bg-gray-900 dark:bg-gray-700 text-white text-xs rounded shadow-lg z-[9999] py-1 whitespace-nowrap"
        @mouseenter="tooltip = true" @mouseleave="tooltip = false">
        
        <a href="{{ route('tenant.quoter.products', ['clear' => 1]) }}" @click="startNewQuote" wire:navigate
            class="block px-2 py-1 hover:bg-gray-700 dark:hover:bg-gray-600 text-white">Ventas</a>
        <a href="{{ route('tenant.quoter') }}" wire:navigate
            class="block px-2 py-1 hover:bg-gray-700 dark:hover:bg-gray-600 text-white">Cotizaciones</a>
        <a href="{{ route('tenant.remissions') }}" wire:navigate
            class="block px-2 py-1 hover:bg-gray-700 dark:hover:bg-gray-600 text-white">Remisiones</a>
    </div>
</div>
@endif








        <!-- Punto de Venta TAT (menú con subitems) -->
        @if(auth()->user() && auth()->user()->profile_id == 17)
        <div x-data="{ tooltip: false, open: false }" class="w-full">
            <!-- Botón principal -->
            <div class="group flex items-center rounded-lg px-3 py-2.5 text-sm font-medium transition-all duration-200 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-green-600 dark:hover:text-green-400 cursor-pointer"
                :class="sidebarCollapsed ? 'justify-center' : 'justify-start'" @mouseenter="tooltip = sidebarCollapsed"
                @mouseleave="tooltip = false" @click="open = !open">
                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 7h6l2 4m-8-4v8m0-8V6a1 1 0 0 0-1-1H4a1 1 0 0 0-1 1v9h2m8 0H9m4 0h2m4 0h2v-4m0 0h-5m3.5 5.5a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0Zm-10 0a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0Z" />
                </svg>
                <span x-show="!sidebarCollapsed" class="ml-3 flex-1" x-transition>
                    Punto de Venta
                </span>
                <!-- Icono desplegable -->
                <svg x-show="!sidebarCollapsed" :class="open ? 'rotate-90' : ''"
                    class="w-4 h-4 ml-auto transition-transform duration-200" fill="none" stroke="currentColor"
                    stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path>
                </svg>
                <!-- Tooltip (solo cuando está colapsado) -->
                <div x-show="tooltip" x-transition
                    class="absolute left-full ml-2 px-2 py-1 bg-gray-900 dark:bg-gray-700 text-white text-xs rounded shadow-lg z-50 whitespace-nowrap">
                    Punto de Venta
                </div>
            </div>

            <!-- Submenú -->
            <div x-show="open && !sidebarCollapsed" x-transition
                class="ml-8 mt-1 space-y-1 text-sm text-gray-600 dark:text-gray-400">
                
                <a href="{{ route('tenant.tat.quoter.index') }}" wire:navigate
                    class="block rounded-md px-2 py-1 text-sm transition-colors duration-150 {{ request()->routeIs('tenant.tat.quoter.index') ? 'bg-green-50 dark:bg-green-900/50 text-green-700 dark:text-green-300' : 'hover:text-green-600 dark:hover:text-green-400' }}">
                    Nueva Venta
                </a>
                <a href="{{ route('tenant.tat.sales.list') }}" wire:navigate
                    class="block rounded-md px-2 py-1 text-sm transition-colors duration-150 {{ request()->routeIs('tenant.tat.sales.list') ? 'bg-green-50 dark:bg-green-900/50 text-green-700 dark:text-green-300' : 'hover:text-green-600 dark:hover:text-green-400' }}">
                    Listar Ventas
                </a>

                <a href="{{ route('petty-cash.petty-cash') }}" wire:navigate
                    class="block rounded-md px-2 py-1 text-sm transition-colors duration-150 {{ request()->routeIs('petty-cash.*') ? 'bg-green-50 dark:bg-green-900/50 text-green-700 dark:text-green-300' : 'hover:text-green-600 dark:hover:text-green-400' }}">
                    Caja Menor
                </a>
                <a href="{{ route('tenant.tat.restock.list') }}" wire:navigate
                    class="block rounded-md px-2 py-1 text-sm transition-colors duration-150 {{ request()->routeIs('tenant.tat.restock.list') ? 'bg-green-50 dark:bg-green-900/50 text-green-700 dark:text-green-300' : 'hover:text-green-600 dark:hover:text-green-400' }}">
                    Solicitudes Reabastecimiento
                </a>
                <a href="{{ route('tenant.customers') }}" wire:navigate
                    class="block rounded-md px-2 py-1 text-sm transition-colors duration-150 {{ request()->routeIs('tenant.customers*') ? 'bg-indigo-50 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300' : 'hover:text-indigo-600 dark:hover:text-indigo-400' }}">
                    Clientes
                </a>
                <a href="{{ route('tenant.items') }}" wire:navigate
                    class="block px-2 py-1 hover:text-indigo-600 dark:hover:text-indigo-400">
                    Items
                </a>
                <a href="{{ route('tenant.categories') }}" wire:navigate
                    class="block px-2 py-1 hover:text-indigo-600 dark:hover:text-indigo-400">Categorías</a>
            </div>
            <!-- Submenú desplegable (para sidebar colapsado) -->
            <div x-show="sidebarCollapsed && tooltip" x-transition
                class="absolute left-full ml-2 bg-gray-900 dark:bg-gray-700 text-white text-xs rounded shadow-lg z-[9999] py-1 whitespace-nowrap"
                @mouseenter="tooltip = true" @mouseleave="tooltip = false">
                
                <a href="{{ route('tenant.tat.quoter.index') }}" wire:navigate
                    class="block px-2 py-1 hover:bg-gray-700 dark:hover:bg-gray-600 text-white">Nueva Venta</a>
                <a href="{{ route('tenant.tat.sales.list') }}" wire:navigate
                    class="block px-2 py-1 hover:bg-gray-700 dark:hover:bg-gray-600 text-white">Listar Ventas</a>
                <a href="{{ route('petty-cash.petty-cash') }}" wire:navigate
                    class="block px-2 py-1 hover:bg-gray-700 dark:hover:bg-gray-600 text-white">Caja Menor</a>
                <a href="{{ route('tenant.tat.restock.list') }}" wire:navigate
                    class="block px-2 py-1 hover:bg-gray-700 dark:hover:bg-gray-600 text-white">Solicitudes Reabastecimiento</a>
                <a href="{{ route('tenant.customers') }}" wire:navigate
                    class="block px-2 py-1 hover:bg-gray-700 dark:hover:bg-gray-600 text-white">Clientes</a>
                <a href="{{ route('tenant.items') }}" wire:navigate
                    class="block px-2 py-1 hover:bg-gray-700 dark:hover:bg-gray-600 text-white">Ítems</a>
                <a href="{{ route('tenant.categories') }}" wire:navigate
                    class="block px-2 py-1 hover:bg-gray-700 dark:hover:bg-gray-600 text-white">Categorías</a>
            </div>
        </div>
        @endif





        <!-- Clientes (menú con subitems: ruta por defecto + navegación AJAX) -->
        @if(PermissionHelper::userCanAny(['Usuarios'], 'show') && auth()->user() && auth()->user()->profile_id != 17)
        <div x-data="{ tooltip: false, open: false }" class="w-full">
            <!-- Botón principal -->
            <div class="group flex items-center rounded-lg px-3 py-2.5 text-sm font-medium transition-all duration-200 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-indigo-600 dark:hover:text-indigo-400 cursor-pointer"
                :class="sidebarCollapsed ? 'justify-center' : 'justify-start'" @mouseenter="tooltip = sidebarCollapsed"
                @mouseleave="tooltip = false" @click="open = !open">
                <!-- Icono de Parámetros (sliders/ajustes) -->
                <svg class="h-5 w-5 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24">
                    <path fill="currentColor"
                        d="M5 19V5v4.475V9zm3-6h3.525q.425 0 .713-.288t.287-.712t-.288-.712t-.712-.288H8q-.425 0-.712.288T7 12t.288.713T8 13m0 4h3.525q.425 0 .713-.288t.287-.712t-.288-.712t-.712-.288H8q-.425 0-.712.288T7 16t.288.713T8 17m0-8h8q.425 0 .713-.288T17 8t-.288-.712T16 7H8q-.425 0-.712.288T7 8t.288.713T8 9M5 21q-.825 0-1.412-.587T3 19V5q0-.825.588-1.412T5 3h14q.825 0 1.413.588T21 5v4.45q0 .425-.288.713T20 10.45t-.712-.287T19 9.45V5H5v14h4q.425 0 .713.288T10 20t-.288.713T9 21zm12-5q-1.05 0-1.775-.725T14.5 13.5t.725-1.775T17 11t1.775.725t.725 1.775t-.725 1.775T17 16m0 1q.975 0 1.938.188t1.862.562q.575.225.888.738T22 19.6v.4q0 .425-.288.713T21 21h-8q-.425 0-.712-.288T12 20v-.4q0-.6.313-1.112t.887-.738q.9-.375 1.863-.562T17 17" />
                </svg>
                <span x-show="!sidebarCollapsed" class="ml-3 flex-1" x-transition>
                    Gestión de contactos
                </span>
                <!-- Icono desplegable -->
                <svg x-show="!sidebarCollapsed" :class="open ? 'rotate-90' : ''"
                    class="w-4 h-4 ml-auto transition-transform duration-200" fill="none" stroke="currentColor"
                    stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path>
                </svg>
                <!-- Tooltip (solo cuando está colapsado) -->
                <div x-show="tooltip" x-transition
                    class="absolute left-full ml-2 px-2 py-1 bg-gray-900 dark:bg-gray-700 text-white text-xs rounded shadow-lg z-50 whitespace-nowrap">
                    Gestión de contactos
                </div>
            </div>
            <!-- Submenú -->
            <div x-show="open && !sidebarCollapsed" x-transition
                class="ml-8 mt-1 space-y-1 text-sm text-gray-600 dark:text-gray-400">
                <a href="{{ route('customers.customers') }}" wire:navigate
                    class="block rounded-md px-2 py-1 text-sm transition-colors duration-150 {{ request()->routeIs('customers.*') ? 'bg-indigo-50 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300' : 'hover:text-indigo-600 dark:hover:text-indigo-400' }}">
                    Gestión Contactos
                </a>
               
                <a href="{{ route('users.users') }}" wire:navigate
                    class="block rounded-md px-2 py-1 text-sm transition-colors duration-150 {{ request()->routeIs('users.*') ? 'bg-indigo-50 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300' : 'hover:text-indigo-600 dark:hover:text-indigo-400' }}">
                    Gestión Usuarios
                </a>

                 <!-- <a href="{{ route('tenant.vnt-customers') }}" wire:navigate
                    class="block rounded-md px-2 py-1 text-sm transition-colors duration-150 {{ request()->routeIs('tenant.vnt-customers') ? 'bg-indigo-50 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300' : 'hover:text-indigo-600 dark:hover:text-indigo-400' }}">
                    Clientes Distribuidor (TAT)
                </a> -->
            </div>
            <!-- Submenú desplegable (para sidebar colapsado) -->
            <div x-show="sidebarCollapsed && tooltip" x-transition
                class="absolute left-full ml-2 bg-gray-900 dark:bg-gray-700 text-white text-xs rounded shadow-lg z-[9999] py-1 whitespace-nowrap"
                @mouseenter="tooltip = true" @mouseleave="tooltip = false">
                <a href="{{ route('customers.customers') }}" wire:navigate
                    class="block px-2 py-1 hover:bg-gray-700 dark:hover:bg-gray-600 text-white">Gestión Contactos</a>
                <a href="{{ route('users.users') }}" wire:navigate
                    class="block px-2 py-1 hover:bg-gray-700 dark:hover:bg-gray-600 text-white">Gestión Usuarios</a>
            </div>
        </div>
        @endif





        <!-- Parámetros (menú con subitems) -->
        @if(PermissionHelper::userCan('Parametros', 'show') && auth()->user() && auth()->user()->profile_id != 17)
        <div x-data="{ tooltip: false, open: false }" class="w-full">
            <!-- Botón principal -->
            <div class="group flex items-center rounded-lg px-3 py-2.5 text-sm font-medium transition-all duration-200 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-indigo-600 dark:hover:text-indigo-400 cursor-pointer"
                :class="sidebarCollapsed ? 'justify-center' : 'justify-start'" @mouseenter="tooltip = sidebarCollapsed"
                @mouseleave="tooltip = false" @click="open = !open">
                <!-- Icono de Parámetros (sliders/ajustes) -->
                <svg class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" />
                </svg>
                <span x-show="!sidebarCollapsed" class="ml-3 flex-1" x-transition>
                    Parámetros
                </span>

                <!-- Icono desplegable -->
                <svg x-show="!sidebarCollapsed" :class="open ? 'rotate-90' : ''"
                    class="w-4 h-4 ml-auto transition-transform duration-200" fill="none" stroke="currentColor"
                    stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path>
                </svg>

                <!-- Tooltip (solo cuando está colapsado) -->
                <div x-show="tooltip" x-transition
                    class="absolute left-full ml-2 px-2 py-1 bg-gray-900 dark:bg-gray-700 text-white text-xs rounded shadow-lg z-50 whitespace-nowrap">
                    Parámetros
                </div>
            </div>

            <!-- Submenú -->
            <div x-show="open && !sidebarCollapsed" x-transition
                class="ml-8 mt-1 space-y-1 text-sm text-gray-600 dark:text-gray-400">
                <!-- <a href="{{ route('tenant.parameters.pricelists') }}" wire:navigate
                    class="block rounded-md px-2 py-1 transition-colors duration-150 {{ request()->routeIs('tenant.parameters.pricelists') ? 'bg-indigo-50 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300' : 'hover:text-indigo-600 dark:hover:text-indigo-400' }}">
                    Listas de Precios
                </a> -->
                <!-- <a href="#" class="block rounded-md px-2 py-1 hover:text-indigo-600 dark:hover:text-indigo-400">
                    Impuestos
                </a> -->
                <a href="{{ route('tenant.parameters.zones') }}" wire:navigate
                    class="block rounded-md px-2 py-1 transition-colors duration-150 {{ request()->routeIs('tenant.parameters.zones') ? 'bg-indigo-50 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300' : 'hover:text-indigo-600 dark:hover:text-indigo-400' }}">
                    Zonas
                </a>
                
                <a href="{{ route('tenant.parameters.routes') }}" wire:navigate
                    class="block rounded-md px-2 py-1 transition-colors duration-150 {{ request()->routeIs('tenant.parameters.routes') ? 'bg-indigo-50 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300' : 'hover:text-indigo-600 dark:hover:text-indigo-400' }}">
                    Rutas
                </a>

                  <a href="{{url('/inventory/categories')}}" wire:navigate
                        class="block px-2 py-1 hover:text-indigo-600 dark:hover:text-indigo-400">Categorías</a>


                    <a href="{{url('/inventory/commands')}}" wire:navigate
                        class="block px-2 py-1 hover:text-indigo-600 dark:hover:text-indigo-400">Comandas</a>
                    <a href="{{url('/inventory/brands')}}" wire:navigate
                        class="block px-2 py-1 hover:text-indigo-600 dark:hover:text-indigo-400">Marcas</a>
                    <a href="{{url('/inventory/houses')}}" wire:navigate
                        class="block px-2 py-1 hover:text-indigo-600 dark:hover:text-indigo-400">Casas</a>
                    <a href="{{url('/inventory/units')}}" wire:navigate
                        class="block px-2 py-1 hover:text-indigo-600 dark:hover:text-indigo-400">Unidades de Medida</a>
            </div>
            <!-- Submenú desplegable (para sidebar colapsado) -->
            <div x-show="sidebarCollapsed && tooltip" x-transition
                class="absolute left-full ml-2 bg-gray-900 dark:bg-gray-700 text-white text-xs rounded shadow-lg z-[9999] py-1 whitespace-nowrap"
                @mouseenter="tooltip = true" @mouseleave="tooltip = false">
                <a href="{{ route('tenant.parameters.zones') }}" wire:navigate
                    class="block px-2 py-1 hover:bg-gray-700 dark:hover:bg-gray-600 text-white">Zonas</a>
                <a href="{{ route('tenant.parameters.routes') }}" wire:navigate
                    class="block px-2 py-1 hover:bg-gray-700 dark:hover:bg-gray-600 text-white">Rutas</a>
                <a href="{{url('/inventory/categories')}}" wire:navigate
                    class="block px-2 py-1 hover:bg-gray-700 dark:hover:bg-gray-600 text-white">Categorías</a>
                <a href="{{url('/inventory/commands')}}" wire:navigate
                    class="block px-2 py-1 hover:bg-gray-700 dark:hover:bg-gray-600 text-white">Comandas</a>
                <a href="{{url('/inventory/brands')}}" wire:navigate
                    class="block px-2 py-1 hover:bg-gray-700 dark:hover:bg-gray-600 text-white">Marcas</a>
                <a href="{{url('/inventory/houses')}}" wire:navigate
                    class="block px-2 py-1 hover:bg-gray-700 dark:hover:bg-gray-600 text-white">Casas</a>
                <a href="{{url('/inventory/units')}}" wire:navigate
                    class="block px-2 py-1 hover:bg-gray-700 dark:hover:bg-gray-600 text-white">Unidades de Medida</a>
            </div>
            @endif






            <!-- Inventario (menú con subitems) -->
            @if(auth()->user() && auth()->user()->profile_id != 17 && auth()->user()->profile_id != 4)
            <div x-data="{ tooltip: false, open: false }" class="w-full">
                <!-- Botón principal -->
                <div class="group flex items-center rounded-lg px-3 py-2.5 text-sm font-medium transition-all duration-200 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-indigo-600 dark:hover:text-indigo-400 cursor-pointer"
                    :class="sidebarCollapsed ? 'justify-center' : 'justify-start'"
                    @mouseenter="tooltip = sidebarCollapsed" @mouseleave="tooltip = false" @click="open = !open">
                    <svg class="h-5 w-5 shrink-0" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24"
                        height="24" fill="none" viewBox="0 0 24 24">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 7h6l2 4m-8-4v8m0-8V6a1 1 0 0 0-1-1H4a1 1 0 0 0-1 1v9h2m8 0H9m4 0h2m4 0h2v-4m0 0h-5m3.5 5.5a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0Zm-10 0a2.5 2.5 0 1 1-5 0 2.5 2.5 0 0 1 5 0Z" />
                    </svg>

                    <span x-show="!sidebarCollapsed" class="ml-3 flex-1" x-transition>
                        Inventario
                    </span>

                    <!-- Icono desplegable -->
                    <svg x-show="!sidebarCollapsed" :class="open ? 'rotate-90' : ''"
                        class="w-4 h-4 ml-auto transition-transform duration-200" fill="none" stroke="currentColor"
                        stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path>
                    </svg>

                    <!-- Tooltip (solo cuando está colapsado) -->
                    <div x-show="tooltip" x-transition
                        class="absolute left-full ml-2 px-2 py-1 bg-gray-900 dark:bg-gray-700 text-white text-xs rounded shadow-lg z-50 whitespace-nowrap">
                        Inventario
                    </div>
                </div>

                <!-- Submenú -->
                <div x-show="open && !sidebarCollapsed" x-transition
                    class="ml-8 mt-1 space-y-1 text-sm text-gray-600 dark:text-gray-400">
                    <a href="{{ url('/items/items') }}" wire:navigate
                        class="block px-2 py-1 hover:text-indigo-600 dark:hover:text-indigo-400">


                        Ítems

                    </a>

                    <a href="{{ route('movements.movements') }}" wire:navigate
                        class="block rounded-md px-2 py-1 text-sm transition-colors duration-150 {{ request()->routeIs('movements.*') ? 'bg-indigo-50 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300' : 'hover:text-indigo-600 dark:hover:text-indigo-400' }}">
                        Gestión movimientos
                    </a>
                </div>

                <!-- Submenú desplegable (para sidebar colapsado) -->
                <div x-show="sidebarCollapsed && tooltip" x-transition
                    class="absolute left-full ml-2 bg-gray-900 dark:bg-gray-700 text-white text-xs rounded shadow-lg z-[9999] py-1 whitespace-nowrap"
                    @mouseenter="tooltip = true" @mouseleave="tooltip = false">
                    
                    <a href="{{ url('/items/items') }}" wire:navigate
                        class="block px-2 py-1 hover:bg-gray-700 dark:hover:bg-gray-600 text-white">
                        Ítems
                    </a>
                    
                    <a href="{{ route('movements.movements') }}" wire:navigate
                        class="block px-2 py-1 hover:bg-gray-700 dark:hover:bg-gray-600 text-white">
                        Gestión movimientos
                    </a>
                </div>
            </div>
            @endif


               <!-- Reportes (menú con subitems) -->
            @if(auth()->user() && auth()->user()->profile_id != 17 && auth()->user()->profile_id != 4)
            <div x-data="{ tooltip: false, open: false }" class="w-full">
                <!-- Botón principal -->
                <div class="group flex items-center rounded-lg px-3 py-2.5 text-sm font-medium transition-all duration-200 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-indigo-600 dark:hover:text-indigo-400 cursor-pointer"
                    :class="sidebarCollapsed ? 'justify-center' : 'justify-start'"
                    @mouseenter="tooltip = sidebarCollapsed" @mouseleave="tooltip = false" @click="open = !open">
                    <svg class="h-5 w-5 shrink-0" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                      width="24" height="24" fill="none" viewBox="0 0 24 24">
                      <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                       d="M3 8a1 1 0 0 1 1-1h4l2 2h10a1 1 0 0 1 1 1v1M3 10l2 9a1 1 0 0 0 1 .8h12a1 1 0 0 0 1-.8l2-9H3Z" />
                    </svg>

                    <span x-show="!sidebarCollapsed" class="ml-3 flex-1" x-transition>
                        Reportes
                    </span>

                    <!-- Icono desplegable -->
                    <svg x-show="!sidebarCollapsed" :class="open ? 'rotate-90' : ''"
                        class="w-4 h-4 ml-auto transition-transform duration-200" fill="none" stroke="currentColor"
                        stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"></path>
                    </svg>

                    
                    <!-- Tooltip (solo cuando está colapsado) -->
                    <div x-show="tooltip" x-transition
                        class="absolute left-full ml-2 px-2 py-1 bg-gray-900 dark:bg-gray-700 text-white text-xs rounded shadow-lg z-50 whitespace-nowrap">
                        Reportes
                    </div>
                </div>

                <!-- Submenú -->
                <div x-show="open && !sidebarCollapsed" x-transition
                    class="ml-8 mt-1 space-y-1 text-sm text-gray-600 dark:text-gray-400">
                    <a href="{{ url('/reports/sales') }}" wire:navigate
                        class="block px-2 py-1 hover:text-indigo-600 dark:hover:text-indigo-400">
                        Vendedor x vendedor
                    </a>

                    <a href="{{ url('/reports/profitability') }}" wire:navigate
                        class="block rounded-md px-2 py-1 text-sm transition-colors duration-150 {{ request()->routeIs('movements.*') ? 'bg-indigo-50 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300' : 'hover:text-indigo-600 dark:hover:text-indigo-400' }}">
                        Rentabilidad
                    </a>
                    <a href="{{ route('tenant.reports.sales-x-items') }}" wire:navigate
                        class="block rounded-md px-2 py-1 text-sm transition-colors duration-150 {{ request()->routeIs('movements.*') ? 'bg-indigo-50 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300' : 'hover:text-indigo-600 dark:hover:text-indigo-400' }}">
                        Ventas x producto
                    </a>
                    <a href="{{ url('/reports/portfolio') }}" wire:navigate
                        class="block rounded-md px-2 py-1 text-sm transition-colors duration-150 {{ request()->routeIs('tenant.reports.portfolio') ? 'bg-indigo-50 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300' : 'hover:text-indigo-600 dark:hover:text-indigo-400' }}">
                        Cartera
                    </a>
                    <a href="{{ route('tenant.reports.price-list') }}"
                        class="block rounded-md px-2 py-1 text-sm transition-colors duration-150 hover:text-indigo-600 dark:hover:text-indigo-400">
                        Lista de precios
                    </a>
                    <a href="{{ route('movements.movements') }}" wire:navigate
                        class="block rounded-md px-2 py-1 text-sm transition-colors duration-150 {{ request()->routeIs('movements.*') ? 'bg-indigo-50 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300' : 'hover:text-indigo-600 dark:hover:text-indigo-400' }}">
                        Comisiones
                    </a>
                    <a href="{{ route('tenant.reports.salesman-x-item') }}" wire:navigate
                        class="block rounded-md px-2 py-1 text-sm transition-colors duration-150 {{ request()->routeIs('movements.*') ? 'bg-indigo-50 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300' : 'hover:text-indigo-600 dark:hover:text-indigo-400' }}">
                        Vendedor x Producto
                    </a>
                    <a href="{{ route('tenant.reports.impact-sales') }}" wire:navigate
                        class="block rounded-md px-2 py-1 text-sm transition-colors duration-150 {{ request()->routeIs('movements.*') ? 'bg-indigo-50 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300' : 'hover:text-indigo-600 dark:hover:text-indigo-400' }}">
                        Informe impacto de ventas
                    </a>
                </div>

                <!-- Submenú desplegable (para sidebar colapsado) -->
                <div x-show="sidebarCollapsed && tooltip" x-transition
                    class="absolute left-full ml-2 bg-gray-900 dark:bg-gray-700 text-white text-xs rounded shadow-lg z-[9999] py-1 whitespace-nowrap"
                    @mouseenter="tooltip = true" @mouseleave="tooltip = false">
                    
                    <a href="{{ url('/reports/sales') }}" wire:navigate
                        class="block px-2 py-1 hover:bg-gray-700 dark:hover:bg-gray-600 text-white">Vendedor x vendedor</a>
                    
                    <a href="{{ url('/reports/profitability') }}" wire:navigate
                        class="block px-2 py-1 hover:bg-gray-700 dark:hover:bg-gray-600 text-white">Rentabilidad</a>
                    
                    <a href="{{ route('tenant.reports.sales-x-items') }}" wire:navigate
                        class="block px-2 py-1 hover:bg-gray-700 dark:hover:bg-gray-600 text-white">Ventas x producto</a>
                    
                    <a href="{{ url('/reports/portfolio') }}" wire:navigate
                        class="block px-2 py-1 hover:bg-gray-700 dark:hover:bg-gray-600 text-white">Cartera</a>
                    
                    <a href="{{ route('tenant.reports.price-list') }}"
                        class="block px-2 py-1 hover:bg-gray-700 dark:hover:bg-gray-600 text-white">Lista de precios</a>
                    
                    <a href="{{ route('movements.movements') }}" wire:navigate
                        class="block px-2 py-1 hover:bg-gray-700 dark:hover:bg-gray-600 text-white">Comisiones</a>
                    
                    <a href="{{ route('tenant.reports.salesman-x-item') }}" wire:navigate
                        class="block px-2 py-1 hover:bg-gray-700 dark:hover:bg-gray-600 text-white">Vendedor x Producto</a>
                    
                    <a href="{{ route('tenant.reports.impact-sales') }}" wire:navigate
                        class="block px-2 py-1 hover:bg-gray-700 dark:hover:bg-gray-600 text-white">Informe impacto de ventas</a>
                </div>
            </div>
            @endif


            <!-- Caja -->
            @if(auth()->user() && auth()->user()->profile_id != 17 && auth()->user()->profile_id != 4)
            <a href="{{ route('petty-cash.petty-cash') }}" wire:navigate
                class="group flex items-center rounded-lg px-3 py-2.5 text-sm font-medium transition-all duration-200 {{ request()->routeIs('petty-cash.*') ? 'bg-indigo-50 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 border-r-2 border-indigo-500' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-indigo-600 dark:hover:text-indigo-400' }}"
                :class="sidebarCollapsed ? 'justify-center' : 'justify-start'" x-data="{ tooltip: false }"
                @mouseenter="tooltip = sidebarCollapsed" @mouseleave="tooltip = false">

                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                    stroke="currentColor" class="h-5 w-5 shrink-0">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm3 0h.008v.008H18V10.5Zm-12 0h.008v.008H6V10.5Z" />
                </svg>
                <span x-show="!sidebarCollapsed" x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 translate-x-4"
                    x-transition:enter-end="opacity-100 translate-x-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-x-0"
                    x-transition:leave-end="opacity-0 translate-x-4" class="ml-3">
                    Caja
                </span>
                <!-- Tooltip -->
                <div x-show="tooltip" x-transition
                    class="absolute left-full ml-2 px-2 py-1 bg-gray-900 dark:bg-gray-700 text-white text-xs rounded shadow-lg z-50 whitespace-nowrap">
                    Caja
                </div>
            </a>
            @endif

            <!-- Cargue de pedidos -->
            @if(auth()->user() && auth()->user()->profile_id != 17 && auth()->user()->profile_id != 4)
            <a href="{{ route('tenant.uploads.uploads') }}" wire:navigate
                class="group flex items-center rounded-lg px-3 py-2.5 text-sm font-medium transition-all duration-200 {{ request()->routeIs('tenant.uploads.*') ? 'bg-indigo-50 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 border-r-2 border-indigo-500' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-indigo-600 dark:hover:text-indigo-400' }}"
                :class="sidebarCollapsed ? 'justify-center' : 'justify-start'" x-data="{ tooltip: false }"
                @mouseenter="tooltip = sidebarCollapsed" @mouseleave="tooltip = false">
                <x-heroicon-o-clipboard-document-list class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" />

                <span x-show="!sidebarCollapsed" x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 translate-x-4"
                    x-transition:enter-end="opacity-100 translate-x-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-x-0"
                    x-transition:leave-end="opacity-0 translate-x-4" class="ml-3">
                    Cargue de Pedidos
                </span>

                <!-- Tooltip -->
                <div x-show="tooltip" x-transition
                    class="absolute left-full ml-2 px-2 py-1 bg-gray-900 dark:bg-gray-700 text-white text-xs rounded shadow-lg z-50 whitespace-nowrap">
                    Cargue de Pedidos
                </div>
            </a>
            @endif









            <!-- Cargue de entregas -->
            @if(auth()->user() && auth()->user()->profile_id != 17 && auth()->user()->profile_id != 4)
            <a href="{{ route('tenant.deliveries') }}" wire:navigate
                class="group flex items-center rounded-lg px-3 py-2.5 text-sm font-medium transition-all duration-200 {{ request()->routeIs('tenant.deliveries') ? 'bg-indigo-50 dark:bg-indigo-900/50 text-indigo-700 dark:text-indigo-300 border-r-2 border-indigo-500' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-indigo-600 dark:hover:text-indigo-400' }}"
                :class="sidebarCollapsed ? 'justify-center' : 'justify-start'" x-data="{ tooltip: false }"
                @mouseenter="tooltip = sidebarCollapsed" @mouseleave="tooltip = false">
                <x-heroicon-o-truck class="w-5 h-5 shrink-0" fill="none" viewBox="0 0 24 24" />

                <span x-show="!sidebarCollapsed" x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 translate-x-4"
                    x-transition:enter-end="opacity-100 translate-x-0"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-x-0"
                    x-transition:leave-end="opacity-0 translate-x-4" class="ml-3">
                    Entregas de pedidos
                </span>

                <!-- Tooltip -->
                <div x-show="tooltip" x-transition
                    class="absolute left-full ml-2 px-2 py-1 bg-gray-900 dark:bg-gray-700 text-white text-xs rounded shadow-lg z-50 whitespace-nowrap">
                    Entregas de pedidos
                </div>
            </a>
            @endif

            <!-- Spacer -->
            <div class="flex-1"></div>

            <!-- User Menu -->
            <div class="border-t border-gray-200 dark:border-gray-700 pt-4" x-data="{ userMenuOpen: false }">

                <!-- Sidebar expandido: Menú completo con avatar -->
                <div x-show="!sidebarCollapsed" class="w-full">
                    <button @click="userMenuOpen = !userMenuOpen"
                        class="group flex w-full items-center rounded-lg px-3 py-2.5 text-sm font-medium transition-all duration-200 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800 hover:text-gray-900 dark:hover:text-white">

                        <!-- Avatar -->
                        <img class="h-8 w-8 rounded-full bg-gray-50 dark:bg-gray-700 ring-2 ring-gray-200 dark:ring-gray-600 object-cover shrink-0"
                             src="{{ auth()->user()->getAvatarUrl() }}"
                             alt="{{ auth()->user()->name }}">

                        <!-- Información del usuario -->
                        <div class="ml-3 flex-1 text-left min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ auth()->user()->name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ auth()->user()->email }}</p>
                        </div>

                        <!-- Icono desplegable -->
                        <svg :class="userMenuOpen ? 'rotate-180' : ''" class="w-4 h-4 ml-2 transition-transform duration-200 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>

                    <!-- Dropdown Menu -->
                    <div x-show="userMenuOpen" x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="transform opacity-0 scale-95"
                         x-transition:enter-end="transform opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="transform opacity-100 scale-100"
                         x-transition:leave-end="transform opacity-0 scale-95"
                         class="mt-2 ml-3 mr-3 bg-white dark:bg-gray-800 rounded-lg shadow-lg ring-1 ring-black ring-opacity-5 dark:ring-gray-700 py-1">

                        <a href="{{ route('profile') }}" class="flex items-center px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors rounded-md mx-1" @click="userMenuOpen = false">
                            <svg class="mr-3 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            Tu Perfil
                        </a>

                        <!-- <a href="{{ route('tenant.select') }}" class="flex items-center px-3 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors rounded-md mx-1" @click="userMenuOpen = false">
                            <svg class="mr-3 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            Cambiar Empresa
                        </a> -->

                        <div class="border-t border-gray-100 dark:border-gray-700 my-1"></div>

                        <button wire:click="logout" class="flex items-center w-full px-3 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors rounded-md mx-1" @click="userMenuOpen = false">
                            <svg class="mr-3 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            Cerrar Sesión
                        </button>
                    </div>
                </div>

                <!-- Sidebar colapsado: Ocultar menú de usuario completamente -->
            </div>
    </nav>
</div>