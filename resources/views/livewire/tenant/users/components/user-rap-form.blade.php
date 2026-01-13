<div class="min-h-screen bg-gray-50 dark:bg-gray-900 p-6">
    <div class="max-w-full mx-auto">
        <!-- Header -->
        @if(\App\Helpers\PermissionHelper::userCan('Usuarios', 'show'))
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Gestión de Usuarios</h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">Administración de usuarios del sistema</p>
                </div>
                @if(\App\Helpers\PermissionHelper::userCan('Usuarios', 'create'))
                <button wire:click="create"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Crear Nuevo
                </button>
                @endif
            </div>
        </div>

        <!-- Success Message -->
        @if($successMessage)
        <div x-data="{ show: true }"
            x-show="show"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform scale-90"
            x-transition:enter-end="opacity-100 transform scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 transform scale-100"
            x-transition:leave-end="opacity-0 transform scale-90"
            x-init="setTimeout(() => show = false, 5000)"
            class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300 px-4 py-3 rounded-lg mb-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    {{ $successMessage }}
                </div>
                <button @click="show = false" class="text-green-700 dark:text-green-300 hover:text-green-900 dark:hover:text-green-100">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                </button>
            </div>
        </div>
        @endif

        <!-- Error Message -->
        @if($errorMessage)
        <div x-data="{ show: true }"
            x-show="show"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 transform scale-90"
            x-transition:enter-end="opacity-100 transform scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 transform scale-100"
            x-transition:leave-end="opacity-0 transform scale-90"
            class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 px-4 py-3 rounded-lg mb-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                    {{ $errorMessage }}
                </div>
                <button @click="show = false" class="text-red-700 dark:text-red-300 hover:text-red-900 dark:hover:text-red-100">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                </button>
            </div>
        </div>
        @endif

        <!-- DataTable Card -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <!-- Toolbar -->
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <!-- Search -->
                    <div class="flex-1 max-w-md">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                            <input wire:model.live.debounce.300ms="search"
                                type="text"
                                placeholder="Buscar por nombre o email..."
                                class="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                    </div>

                    <!-- Controls -->
                    <div class="flex items-center gap-3">
                        <!-- Records per page -->
                        <div class="flex items-center gap-2">
                            <label class="text-sm text-gray-700 dark:text-gray-300">Mostrar:</label>
                            <select wire:model.live="perPage"
                                class="border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="5">5</option>
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>

                        <!-- Export buttons -->
                        <x-export-buttons />
                    </div>
                </div>
            </div>
            <!-- Datatable -->
            <div class="overflow-x-auto overflow-visible">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600" wire:click="sortBy('id')">
                                <div class="flex items-center gap-2">
                                    ID
                                    @if($sortField === 'id')
                                        @if($sortDirection === 'asc')
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/>
                                            </svg>
                                        @else
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z"/>
                                            </svg>
                                        @endif
                                    @endif
                                </div>
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600" wire:click="sortBy('name')">
                                <div class="flex items-center gap-2">
                                    Nombre Completo
                                    @if($sortField === 'name')
                                        @if($sortDirection === 'asc')
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/>
                                            </svg>
                                        @else
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z"/>
                                            </svg>
                                        @endif
                                    @endif
                                </div>
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-600" wire:click="sortBy('email')">
                                <div class="flex items-center gap-2">
                                    Email
                                    @if($sortField === 'email')
                                        @if($sortDirection === 'asc')
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/>
                                            </svg>
                                        @else
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z"/>
                                            </svg>
                                        @endif
                                    @endif
                                </div>
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Teléfono
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Perfil
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Sucursal
                            </th>
                              <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Estado
                            </th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">
                                Acciones
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($users as $user)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                {{ $user->id }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $user->contact->firstName  ? $user->contact->firstName . ' ' . $user->contact->lastName : 'N/A' }}
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                {{ $user->email }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                {{ $user->phone ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                    {{ $user->profile->name ?? 'N/A' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                @if($user->contact && $user->contact->warehouse)
                                    <div>
                                        <div class="font-medium">{{ $user->contact->warehouse->name }}</div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $user->contact->warehouse->company->name ?? 'N/A' }}
                                        </div>
                                    </div>
                                @else
                                    <span class="text-gray-400">N/A</span>
                                @endif
                            </td>

                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                <!-- Estado Toggle -->
                                <div class="flex items-center justify-center gap-2">
                                    <!-- Toggle Switch -->
                                    <button type="button"
                                        wire:click="toggleItemStatus({{ $user->id }})"
                                        wire:loading.attr="disabled"
                                        wire:loading.class="opacity-50 cursor-not-allowed"
                                        wire:target="toggleItemStatus({{ $user->id }})"
                                        class="relative inline-flex h-4 w-8 items-center rounded-full transition-all duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 hover:shadow-md {{ $user->contact && $user->contact->status ? 'bg-indigo-600 hover:bg-indigo-700' : 'bg-gray-300 dark:bg-gray-600 hover:bg-gray-400 dark:hover:bg-gray-500' }}"
                                        role="switch"
                                        aria-checked="{{ $user->contact && $user->contact->status ? 'true' : 'false' }}"
                                        aria-label="Toggle user status for {{ $user->name }}">
                                        <span class="inline-block h-3 w-3 transform rounded-full bg-white shadow-sm transition-all duration-200 ease-in-out {{ $user->contact && $user->contact->status ? 'translate-x-5' : 'translate-x-1' }}"></span>
                                    </button>
                                    <!-- Estado de carga -->
                                    <div wire:loading wire:target="toggleItemStatus({{ $user->id }})" class="ml-1">
                                        <svg class="animate-spin h-4 w-4 text-indigo-600 dark:text-indigo-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                <!-- Menú de tres puntos con Alpine.js -->
                                <div x-data="{ 
                                    open: false,
                                    openUp: false,
                                    toggleMenu() {
                                        this.open = !this.open;
                                        if (this.open) {
                                            this.$nextTick(() => {
                                                const button = this.$refs.button;
                                                const menu = this.$refs.menu;
                                                const buttonRect = button.getBoundingClientRect();
                                                const spaceBelow = window.innerHeight - buttonRect.bottom;
                                                const menuHeight = menu.offsetHeight || 150;
                                                this.openUp = spaceBelow < (menuHeight + 50);
                                            });
                                        }
                                    }
                                }" @click.outside="open = false" class="relative inline-block text-left">
                                    <button @click="toggleMenu()" x-ref="button"
                                        class="flex items-center text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 rounded-lg p-1 transition-colors">
                                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                                        </svg>
                                    </button>

                                    <!-- Menú desplegable -->
                                    <div x-show="open"
                                        x-ref="menu"
                                        x-cloak
                                        x-transition:enter="transition ease-out duration-100"
                                        x-transition:enter-start="transform opacity-0 scale-95"
                                        x-transition:enter-end="transform opacity-100 scale-100"
                                        x-transition:leave="transition ease-in duration-75"
                                        x-transition:leave-start="transform opacity-100 scale-100"
                                        x-transition:leave-end="transform opacity-0 scale-95"
                                        @click="open = false"
                                        :class="openUp ? 'bottom-full mb-2 origin-bottom-right' : 'top-full mt-2 origin-top-right'"
                                        class="absolute right-0 w-48 rounded-lg shadow-lg bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 dark:ring-gray-700 z-50">
                                        <div class="py-1" role="menu" aria-orientation="vertical">
                                            <!-- Editar Usuario -->
                                            @if(\App\Helpers\PermissionHelper::userCan('Usuarios', 'edit'))
                                            <button wire:click="edit({{ $user->id }})"
                                                class="w-full text-left px-4 py-2 text-sm text-indigo-800 dark:text-indigo-300 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-colors flex items-center">
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                </svg>
                                                Editar
                                            </button>
                                            @endif

                                            <!-- Cambiar Contraseña -->
                                            @if(\App\Helpers\PermissionHelper::userCan('Usuarios', 'edit'))
                                            <button wire:click="openChangePasswordModal({{ $user->id }})"
                                                class="w-full text-left px-4 py-2 text-sm text-orange-800 dark:text-orange-300 hover:bg-orange-50 dark:hover:bg-orange-900/20 transition-colors flex items-center">
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 112 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path>
                                                </svg>
                                                Cambiar Contraseña
                                            </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <div class="flex flex-col items-center justify-center">
                                    <svg class="w-16 h-16 text-gray-400 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                    </svg>
                                    <p class="text-gray-500 dark:text-gray-400 text-lg font-medium">No se encontraron usuarios</p>
                                    <p class="text-gray-400 dark:text-gray-500 text-sm mt-1">
                                        @if($search)
                                            Intenta con otros términos de búsqueda
                                        @else
                                            Comienza creando un nuevo usuario
                                        @endif
                                    </p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            @if($users->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $users->links() }}
            </div>
            @endif
        </div>

        <!-- Modal -->
        @if($showModal)
       <div class="fixed inset-0 bg-gray-600 dark:bg-gray-900 bg-opacity-50 dark:bg-opacity-75 overflow-y-auto h-full w-full z-50"
    x-data="{ show: true }"
    x-show="show"
    x-transition:enter="ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    @keydown.escape.window="$wire.closeModal()">
    
    <div class="relative min-h-screen flex items-center justify-center p-4">
        <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
            
            <!-- Header -->
            <div class="border-b border-gray-200 dark:border-gray-700 px-6 py-4 flex justify-between items-center">
                <div>
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ $editingId ? 'Editar Usuario' : 'Crear Nuevo Usuario' }}
                    </h3>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        {{ $editingId ? 'Actualiza la información del usuario' : 'Completa los datos para crear un nuevo usuario' }}
                    </p>
                </div>
                <button wire:click="closeModal" 
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Content -->
            <div class="p-6">
                <!-- Error Message Alert -->
                @if($errorMessage)
                <div x-data="{ showAlert: true }" 
                     x-show="showAlert"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 transform scale-90"
                     x-transition:enter-end="opacity-100 transform scale-100"
                     class="mb-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-red-600 dark:text-red-400 mt-0.5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        <div class="flex-1">
                            <p class="text-sm text-red-700 dark:text-red-400">{{ $errorMessage }}</p>
                        </div>
                        <button type="button" 
                                @click="showAlert = false" 
                                class="ml-3 text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 transition-colors flex-shrink-0">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                    </div>
                </div>
                @endif

                <!-- Validation Errors Summary -->
                @if($errors->any())
                <div x-data="{ showAlert: true }" 
                     x-show="showAlert"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0 transform scale-90"
                     x-transition:enter-end="opacity-100 transform scale-100"
                     class="mb-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-red-600 dark:text-red-400 mt-0.5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-red-700 dark:text-red-400 mb-2">Por favor corrija los siguientes errores:</p>
                            <ul class="list-disc list-inside space-y-1">
                                @foreach($errors->all() as $error)
                                    <li class="text-sm text-red-700 dark:text-red-400">{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                        <button type="button" 
                                @click="showAlert = false" 
                                class="ml-3 text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 transition-colors flex-shrink-0">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                            </svg>
                        </button>
                    </div>
                </div>
                @endif

                <!-- User Form -->
                <form wire:submit.prevent="save">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- First Name -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Primer Nombre <span class="text-red-500">*</span>
                            </label>
                            <input wire:model.defer="firstName" type="text"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('firstName') border-red-500 @enderror"
                                placeholder="Ej: Juan">
                            @error('firstName') 
                                <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> 
                            @enderror
                        </div>

                        <!-- Second Name -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Segundo Nombre
                            </label>
                            <input wire:model.defer="secondName" type="text"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('secondName') border-red-500 @enderror"
                                placeholder="Ej: Carlos">
                            @error('secondName') 
                                <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> 
                            @enderror
                        </div>

                        <!-- Last Name -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Primer Apellido <span class="text-red-500">*</span>
                            </label>
                            <input wire:model.defer="lastName" type="text"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('lastName') border-red-500 @enderror"
                                placeholder="Ej: Pérez">
                            @error('lastName') 
                                <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> 
                            @enderror
                        </div>

                        <!-- Second Last Name -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Segundo Apellido
                            </label>
                            <input wire:model.defer="secondLastName" type="text"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('secondLastName') border-red-500 @enderror"
                                placeholder="Ej: García">
                            @error('secondLastName') 
                                <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> 
                            @enderror
                        </div>

                        <!-- Email -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Correo <span class="text-red-500">*</span>
                            </label>
                            <input wire:model.defer="email" type="email"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('email') border-red-500 @enderror"
                                placeholder="Ej: juan.perez@empresa.com">
                            @error('email') 
                                <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> 
                            @enderror
                        </div>

                        <!-- Phone -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Teléfono
                            </label>
                            <input wire:model.defer="phone" type="text"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('phone') border-red-500 @enderror"
                                placeholder="Ej: +57 300 123 4567">
                            @error('phone') 
                                <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> 
                            @enderror
                        </div>

                        <!-- Password (only in create mode) -->
                        @if(!$editingId)
                        <div x-data="{ 
                            get password() { return $wire.password || ''; },
                            get passwordConfirmation() { return $wire.password_confirmation || ''; },
                            get passwordsMatch() {
                                if (!this.password || !this.passwordConfirmation) return true;
                                return this.password === this.passwordConfirmation;
                            }
                        }">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Contraseña <span class="text-red-500">*</span>
                                </label>
                                <input wire:model.defer="password" type="password"
                                    autocomplete="new-password"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('password') border-red-500 @enderror"
                                    placeholder="Mínimo 8 caracteres">
                                @error('password') 
                                    <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> 
                                @enderror
                            </div>

                            <!-- Password Confirmation (only in create mode) -->
                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Confirmar Contraseña <span class="text-red-500">*</span>
                                </label>
                                <input wire:model.defer="password_confirmation" type="password"
                                    autocomplete="new-password"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('password_confirmation') border-red-500 @enderror"
                                    placeholder="Repite la contraseña">
                                @error('password_confirmation') 
                                    <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> 
                                @enderror
                            </div>

                            <!-- Password Match Alert -->
                            <div x-show="!passwordsMatch && password && passwordConfirmation"
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 transform scale-95"
                                x-transition:enter-end="opacity-100 transform scale-100"
                                x-transition:leave="transition ease-in duration-150"
                                x-transition:leave-start="opacity-100 transform scale-100"
                                x-transition:leave-end="opacity-0 transform scale-95"
                                class="mt-3 p-3 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
                                <div class="flex items-start">
                                    <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400 mt-0.5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                    </svg>
                                    <p class="text-sm text-yellow-700 dark:text-yellow-300">
                                        Las contraseñas deben ser iguales
                                    </p>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Position Select -->
                        @livewire('selects.position-select', ['positionId' => $positionId])
                        
                        <!-- Profile Select -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Perfil <span class="text-red-500">*</span>
                            </label>
                            <select wire:model.live="profile_id"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('profile_id') border-red-500 @enderror">
                                <option value="">Seleccionar perfil</option>
                                @foreach($profiles as $profile)
                                    <option value="{{ $profile->id }}">{{ $profile->name }}</option>
                                @endforeach
                            </select>
                            @error('profile_id')
                                <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Warehouse Select -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Sucursal <span class="text-red-500">*</span>
                            </label>
                            <select wire:model.defer="warehouseId"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 @error('warehouseId') border-red-500 @enderror">
                                <option value="">Seleccionar sucursal</option>
                                @foreach($warehouses as $warehouse)
                                    <option value="{{ $warehouse->id }}">
                                        {{ $warehouse->name }} - {{ $warehouse->company->name ?? 'N/A' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('warehouseId') 
                                <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span> 
                            @enderror
                        </div>
                    </div>

                    <!-- Permisos del Perfil -->
                    @if(count($profilePermissions) > 0)
                    <div class="mt-8 pt-6 border-t border-gray-200 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Permisos del Perfil</h3>
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                            <div class="overflow-x-auto">
                                <table class="min-w-full">
                                    <thead>
                                        <tr class="border-b border-gray-200 dark:border-gray-600">
                                            <th class="text-left py-2 px-3 text-sm font-medium text-gray-600 dark:text-gray-300">
                                                Módulo
                                            </th>
                                            <th class="text-center py-2 px-3 text-sm font-medium text-gray-600 dark:text-gray-300">
                                                Ver
                                            </th>
                                            <th class="text-center py-2 px-3 text-sm font-medium text-gray-600 dark:text-gray-300">
                                                Crear
                                            </th>
                                            <th class="text-center py-2 px-3 text-sm font-medium text-gray-600 dark:text-gray-300">
                                                Editar
                                            </th>
                                            <th class="text-center py-2 px-3 text-sm font-medium text-gray-600 dark:text-gray-300">
                                                Eliminar
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-200 dark:divide-gray-600">
                                        @foreach($profilePermissions as $permission)
                                        <tr>
                                            <td class="py-3 px-3 text-sm text-gray-900 dark:text-white font-medium">
                                                {{ $permission['name'] }}
                                            </td>
                                            <td class="py-3 px-3 text-center">
                                                <div class="flex justify-center">
                                                    <input type="checkbox"
                                                           @if($permission['ver']) checked @endif
                                                           disabled
                                                           class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded cursor-not-allowed opacity-60">
                                                </div>
                                            </td>
                                            <td class="py-3 px-3 text-center">
                                                <div class="flex justify-center">
                                                    <input type="checkbox"
                                                           @if($permission['crear']) checked @endif
                                                           disabled
                                                           class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded cursor-not-allowed opacity-60">
                                                </div>
                                            </td>
                                            <td class="py-3 px-3 text-center">
                                                <div class="flex justify-center">
                                                    <input type="checkbox"
                                                           @if($permission['editar']) checked @endif
                                                           disabled
                                                           class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded cursor-not-allowed opacity-60">
                                                </div>
                                            </td>
                                            <td class="py-3 px-3 text-center">
                                                <div class="flex justify-center">
                                                    <input type="checkbox"
                                                           @if($permission['eliminar']) checked @endif
                                                           disabled
                                                           class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded cursor-not-allowed opacity-60">
                                                </div>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Action Buttons -->
                    <div class="flex justify-end gap-2 mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button wire:click="closeModal" type="button"
                            class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            Cancelar
                        </button>
                        <button type="submit"
                            wire:loading.attr="disabled"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center">
                            <span wire:loading.remove wire:target="save">
                                {{ $editingId ? 'Actualizar' : 'Crear Usuario' }}
                            </span>
                            <span wire:loading wire:target="save" class="flex items-center">
                                <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                Guardando...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
        @endif

        <!-- Modal de Cambiar Contraseña -->
        @if($showChangePasswordModal)
        <div class="fixed inset-0 bg-gray-600 dark:bg-gray-900 bg-opacity-50 dark:bg-opacity-75 overflow-y-auto h-full w-full z-50"
            x-data="{ show: true }"
            x-show="show"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @keydown.escape.window="$wire.closeChangePasswordModal()">

            <div class="relative min-h-screen flex items-center justify-center p-4">
                <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">

                    <!-- Header -->
                    <div class="border-b border-gray-200 dark:border-gray-700 px-6 py-4 flex justify-between items-center">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white flex items-center">
                                <svg class="w-5 h-5 mr-2 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 112 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1721 9z"></path>
                                </svg>
                                Cambiar Contraseña
                            </h3>
                            @if($userToChangePassword)
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                Usuario: <span class="font-medium text-gray-900 dark:text-white">{{ $userToChangePassword->name }}</span>
                            </p>
                            @endif
                        </div>
                        <button wire:click="closeChangePasswordModal"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Content -->
                    <div class="p-6">
                        <form wire:submit.prevent="changePassword">
                            <div class="space-y-4">
                                <!-- Nueva Contraseña -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Nueva Contraseña <span class="text-red-500">*</span>
                                    </label>
                                    <input wire:model.defer="newPassword" type="password"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 @error('newPassword') border-red-500 @enderror"
                                        placeholder="Ingresa la nueva contraseña">
                                    @error('newPassword')
                                        <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
                                    @enderror
                                </div>

                                <!-- Confirmar Contraseña -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Confirmar Contraseña <span class="text-red-500">*</span>
                                    </label>
                                    <input wire:model.defer="confirmPassword" type="password"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:border-orange-500 @error('confirmPassword') border-red-500 @enderror"
                                        placeholder="Confirma la nueva contraseña">
                                    @error('confirmPassword')
                                        <span class="text-red-500 text-sm mt-1 block">{{ $message }}</span>
                                    @enderror
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex justify-end gap-2 mt-6">
                                <button wire:click="closeChangePasswordModal" type="button"
                                    class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    Cancelar
                                </button>
                                <button type="submit"
                                    wire:loading.attr="disabled"
                                    wire:target="changePassword"
                                    class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center">
                                    <span wire:loading.remove wire:target="changePassword">
                                        Cambiar Contraseña
                                    </span>
                                    <span wire:loading wire:target="changePassword" class="flex items-center">
                                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Cambiando...
                                    </span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        @endif

        @endif {{-- Cierre del permiso 'show' para todo el módulo de Usuarios --}}
    </div>
</div>