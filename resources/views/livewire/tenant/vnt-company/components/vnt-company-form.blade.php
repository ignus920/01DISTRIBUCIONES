<div>
    @if(!$reusable)
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 p-6">
        <div class="max-w-12xl mx-auto">
            <!-- Header -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Parámetros clientes</h1>
                        <p class="text-gray-600 dark:text-gray-400 mt-1">Gestion de registros</p>
                    </div>
                    <div class="flex flex-col sm:flex-row items-start sm:items-start justify-start sm:justify-between gap-4">
                        <button wire:click="create"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                            Crear Nuevo Cliente
                        </button>
                        <div class="flex flex-col sm:flex-row items-start sm:items-start justify-start sm:justify-between gap-4">
                            <button wire:click="openRoutes"
                                class="inline-flex items-center px-4 py-2 rounded-lg font-semibold text-xs uppercase transition-all duration-200 bg-cyan-600 hover:bg-cyan-700 dark:bg-cyan-500 dark:hover:bg-cyan-600 text-white focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7">
                                    </path>
                                </svg>
                                Ruteros
                            </button>
                            <button wire:click="openMoveDistrict"
                                class="inline-flex items-center px-4 py-2 rounded-lg font-semibold text-xs uppercase transition-all duration-200 bg-slate-600 hover:bg-slate-700 dark:bg-slate-500 dark:hover:bg-slate-600 text-white focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4">
                                    </path>
                                </svg>
                                Mover Barrios
                            </button>

                        </div>
                    </div>
                </div>
            </div>

            <!-- Mensajes -->
            @if (session()->has('message'))
            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300 px-4 py-3 rounded-lg mb-6">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    {{ session('message') }}
                </div>
            </div>
            @endif

            <!-- DataTable Card -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                <!-- Toolbar -->
                <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                        <!-- Búsqueda -->
                        <div class="flex-1 max-w-md">
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </div>
                                <input wire:model.live.debounce.300ms="search"
                                    type="text"
                                    placeholder="Buscar registros..."
                                    class="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                        </div>

                        <!-- Controles -->
                        <div class="flex items-center gap-3">
                            <!-- Registros por página -->
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

                            <!-- Botones de exportar -->
                            <div class="flex items-center gap-2">
                                <!-- Botón Excel -->
                                <button wire:click="exportExcel"
                                    title="Exportar a Excel"
                                    class="inline-flex items-center justify-center p-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M21,5V19A2,2 0 0,1 19,21H5A2,2 0 0,1 3,19V5A2,2 0 0,1 5,3H19A2,2 0 0,1 21,5M19,5H12V7H19V5M19,9H12V11H19V9M19,13H12V15H19V13M19,17H12V19H19V17M5,5V7H10V5H5M5,9V11H10V9H5M5,13V15H10V13H5M5,17V19H10V17H5Z" />
                                    </svg>
                                </button>
                                <!-- Botón PDF -->
                                <button wire:click="exportPdf"
                                    title="Exportar a PDF"
                                    class="inline-flex items-center justify-center p-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20Z" />
                                    </svg>
                                </button>
                                <!-- Botón CSV -->
                                <button wire:click="exportCsv"
                                    title="Exportar a CSV"
                                    class="inline-flex items-center justify-center p-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M14,2H6A2,2 0 0,0 4,4V20A2,2 0 0,0 6,22H18A2,2 0 0,0 20,20V8L14,2M18,20H6V4H13V9H18V20M8,12V14H16V12H8M8,16V18H13V16H8Z" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabla -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th wire:click="sortBy('id')"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 select-none">
                                    <div class="flex items-center gap-1">
                                        ID
                                        @if($sortField === 'id')
                                        @if($sortDirection === 'asc')
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"></path>
                                        </svg>
                                        @else
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z"></path>
                                        </svg>
                                        @endif
                                        @endif
                                    </div>
                                </th>
                                <th wire:click="sortBy('businessName')"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 select-none">
                                    <div class="flex items-center gap-1">
                                        Razón Social
                                        @if($sortField === 'businessName')
                                        @if($sortDirection === 'asc')
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"></path>
                                        </svg>
                                        @else
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z"></path>
                                        </svg>
                                        @endif
                                        @endif
                                    </div>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Identificación</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Sucursal</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Dirección</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Teléfono</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Registro</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Estado</th>
                                <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($items as $item)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    #{{ $item->id }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ $item->businessName ? $item->businessName :  trim($item->firstName . ' ' . $item->lastName)  }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    {{ $item->identification ?? 'Sin identificación' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    {{ $item->mainWarehouse->name ?? 'Sin sucursal' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    {{ $item->mainWarehouse->address ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                    {{ $item->mainWarehouse?->contacts->first()?->business_phone ?? 'N/A' }}
                                    <br>
                                    {{ $item->mainWarehouse?->contacts->first()?->personal_phone ?? 'N/A' }}

                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $item->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium text-gray-900 dark:text-white">
                                    <!-- Estado Toggle -->
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-3">
                                            <!-- Toggle Switch -->
                                            <button type="button"
                                                wire:click="toggleItemStatus({{ $item->id }})"
                                                class="relative inline-flex h-4 w-8 items-center rounded-full transition-all duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 hover:shadow-md {{ $item->status ? 'bg-indigo-600 hover:bg-indigo-700' : 'bg-gray-300 dark:bg-gray-600 hover:bg-gray-400 dark:hover:bg-gray-500' }}"
                                                role="switch"
                                                aria-checked="{{ $item->status ? 'true' : 'false' }}"
                                                aria-label="Toggle company status">
                                                <span class="inline-block h-3 w-3 transform rounded-full bg-white shadow-sm transition-all duration-200 ease-in-out {{ $item->status ? 'translate-x-4' : 'translate-x-1' }}"></span>
                                            </button>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                    <!-- Menú de tres puntos con Alpine.js -->
                                    <div x-data="{ open: false }" @click.outside="open = false" class="relative inline-block text-left">
                                        <button @click="open = !open"
                                            class="flex items-center text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 rounded-lg p-1 transition-colors">
                                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                                            </svg>
                                        </button>

                                        <!-- Menú desplegable -->
                                        <div x-show="open"
                                            x-transition:enter="transition ease-out duration-100"
                                            x-transition:enter-start="transform opacity-0 scale-95"
                                            x-transition:enter-end="transform opacity-100 scale-100"
                                            x-transition:leave="transition ease-in duration-75"
                                            x-transition:leave-start="transform opacity-100 scale-100"
                                            x-transition:leave-end="transform opacity-0 scale-95"
                                            @click="open = false"
                                            class="origin-top-right absolute right-0 mt-2 w-48 rounded-lg shadow-lg bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 dark:ring-gray-700 z-50"
                                            style="display: none;">
                                            <div class="py-1" role="menu" aria-orientation="vertical">
                                                <button wire:click="edit({{ $item->id }})"
                                                    class="w-full text-left px-4 py-2 text-sm text-yellow-800 dark:text-yellow-300 hover:bg-yellow-50 dark:hover:bg-yellow-900/20 transition-colors flex items-center">
                                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                    </svg>
                                                    Editar
                                                </button>
                                                <button wire:click="openWarehouseModal({{ $item->id }})"
                                                    class="w-full text-left px-4 py-2 text-sm text-green-800 dark:text-green-300 hover:bg-green-50 dark:hover:bg-green-900/20 transition-colors flex items-center">
                                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                                    </svg>
                                                    Sucursales
                                                </button>
                                                <button wire:click="openContactModal({{ $item->id }})"
                                                    class="w-full text-left px-4 py-2 text-sm text-blue-800 dark:text-blue-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors flex items-center">
                                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                                    </svg>
                                                    Contactos
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-12 h-12 mb-4 text-gray-400 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                        </svg>
                                        <p class="text-lg font-medium">No se encontraron registros</p>
                                        <p class="text-sm">{{ $search ? 'Intenta ajustar tu búsqueda' : 'Comienza creando un nuevo registro' }}</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Paginación -->
                @if($items->hasPages())
                <div class="bg-white dark:bg-gray-800 px-6 py-3 border-t border-gray-200 dark:border-gray-700 rounded-b-lg">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-700 dark:text-gray-300">
                            Mostrando {{ $items->firstItem() }} a {{ $items->lastItem() }} de {{ $items->total() }} resultados
                        </div>
                        <div>
                            {{ $items->links() }}
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>






        <!-- Modal  de creacion de clientes con suaurios para distribuidora-->
        @if($showModal)
        <div class="fixed inset-0 bg-gray-600 dark:bg-gray-900 bg-opacity-50 dark:bg-opacity-75 overflow-y-auto h-full w-full z-50"
            x-data="{ show: true }"
            x-show="show"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0">
            <div class="relative min-h-screen flex items-center justify-center p-4">
                <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">

                    <!-- Header -->
                    <div class="border-b border-gray-200 dark:border-gray-700 px-6 py-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $editingId ? 'Editar' : 'Crear' }} Cliente
                        </h3>
                    </div>
                    <!-- Alert de Errores de Validación -->
                    @if (session()->has('error'))
                    <div x-data="{ showAlert: true }"
                        x-show="showAlert"
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 transform scale-90"
                        x-transition:enter-end="opacity-100 transform scale-100"
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="opacity-100 transform scale-100"
                        x-transition:leave-end="opacity-0 transform scale-90"
                        class="mx-6 mt-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-red-600 dark:text-red-400 mt-0.5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                            <div class="flex-1">
                                <h4 class="text-sm font-medium text-red-800 dark:text-red-300 mb-1">
                                    Error de Validación
                                </h4>
                                <div class="text-sm text-red-700 dark:text-red-400">
                                    {!! session('error') !!}
                                </div>
                            </div>
                            <button type="button"
                                @click="showAlert = false"
                                class="ml-3 text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 transition-colors">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    @endif

                    <!-- Form -->
                    <form wire:submit="save" class="p-6 space-y-6">
                        <div class="space-y-6">
                            <!-- Tipo de Identificación -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Tipo de Identificación <span class="text-red-500">*</span>
                                </label>
                                @livewire('selects.type-identification-select', [
                                'typeIdentificationId' => $typeIdentificationId,
                                'name' => 'typeIdentificationId',
                                'placeholder' => 'Seleccione un tipo de identificación',
                                'label' => '',
                                'showLabel' => false,
                                'class' => 'w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500'
                                ])
                                @error('typeIdentificationId')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- NIT/Identificación con campo DV condicional -->
                            @if($typeIdentificationId > 0)
                            @if($typeIdentificationId == 2)
                            <!-- NIT con DV -->
                            <div class="grid grid-cols-3 gap-4">
                                <div class="col-span-2">
                                    <label for="identification" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">NIT *</label>
                                    <div class="relative">
                                        <input wire:model.live.debounce.500ms="identification" type="text" id="identification" maxlength="15"
                                            class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500
                                               @error('identification') border-red-500 @enderror
                                               @if($identificationExists) border-red-500 @endif"
                                            placeholder="123456789" required>

                                        @if($validatingIdentification)
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                            <svg class="animate-spin h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                        </div>
                                        @endif
                                    </div>

                                    @error('identification')
                                    <span class="text-red-500 text-sm">{{ $message }}</span>
                                    @enderror

                                    @if($identificationExists && !$errors->has('identification'))
                                    <span class="text-red-500 text-sm">
                                        Este número de identificación ya está registrado
                                    </span>
                                    @endif
                                </div>
                                <div>
                                    <label for="verification_digit" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">DV *</label>
                                    <input wire:model="verification_digit" type="text" id="verification_digit" maxlength="1"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                        placeholder="0"
                                        oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                    @error('verification_digit') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                                <!-- Tipo de Persona -->
                                <div>
                                    <label for="typePerson" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tipo de Persona *</label>
                                    <select wire:model.live="typePerson" id="typePerson"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                        <option value="">Seleccionar tipo</option>
                                        <option value="Natural" {{ $typePerson === 'Natural' ? 'selected' : '' }}>Persona Natural</option>
                                        <option value="Juridica" {{ $typePerson === 'Juridica' ? 'selected' : '' }}>Persona Jurídica</option>
                                    </select>
                                    @error('typePerson') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            @else
                            <!-- Otros tipos de identificación -->
                            <div>
                                <label for="identification" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Número de Identificación *</label>
                                <div class="relative">
                                    <input wire:model.live.debounce.500ms="identification" type="text" id="identification" maxlength="15"
                                        class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500
                                           @error('identification') border-red-500 @enderror
                                           @if($identificationExists) border-red-500 @endif"
                                        placeholder="Ingrese el número">

                                    @if($validatingIdentification)
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                        <svg class="animate-spin h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </div>
                                    @endif
                                </div>

                                @error('identification')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                                @enderror

                                @if($identificationExists && !$errors->has('identification'))
                                <span class="text-red-500 text-sm">
                                    Este número de identificación ya está registrado
                                </span>
                                @endif
                            </div>
                            @endif
                            @endif

                            <!-- Campos condicionales según tipo de persona -->
                            @if($typePerson)
                            @if($typePerson == 'Natural' || $showNaturalPersonFields)
                            <!-- Persona Natural: Nombre y Apellido -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="firstName" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Primer Nombre *</label>
                                    <input wire:model="firstName" type="text" id="firstName"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                        placeholder="Ingrese su nombre">
                                    @error('firstName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="lastName" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Segundo Nombre </label>
                                    <input wire:model="lastName" type="text" id="lastName"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                        placeholder="Ingrese su apellido">
                                    @error('lastName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="secondName" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Primer Apellido *</label>
                                    <input wire:model="secondName" type="text" id="secondName"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                        placeholder="Ingrese su nombre">
                                    @error('secondName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="secondLastName" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Segundo Apellido </label>
                                    <input wire:model="secondLastName" type="text" id="secondLastName"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                        placeholder="Ingrese su apellido">
                                    @error('secondLastName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            @elseif($typePerson == 'Juridica' && !$showNaturalPersonFields)
                            <!-- Persona Jurídica: Razón Social -->
                            <div>
                                <label for="businessName" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Razón Social *</label>
                                <input wire:model="businessName" type="text" id="businessName"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                    placeholder="Ingrese la razón social de la empresa">
                                @error('businessName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <!-- Selects para configuraciones fiscales -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <!-- Régimen -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Régimen <span class="text-red-500">*</span>
                                        </label>
                                        @livewire('selects.regime-select', [
                                        'regimeId' => $regimeId,
                                        'name' => 'regimeId',
                                        'label' => '',
                                        'showLabel' => false,
                                        'placeholder' => 'Seleccionar régimen',
                                        'class' => 'w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500'
                                        ])
                                        @error('regimeId')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <!-- Responsabilidad Fiscal -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Responsabilidad Fiscal <span class="text-red-500">*</span>
                                        </label>
                                        @livewire('selects.fiscal-responsibility-select', [
                                        'fiscalResponsibilityId' => $fiscalResponsabilityId,
                                        'name' => 'fiscalResponsibilityId',
                                        'label' => '',
                                        'showLabel' => false,
                                        'placeholder' => 'Seleccionar responsabilidad fiscal',
                                        'class' => 'w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500'
                                        ])
                                        @error('fiscalResponsabilityId')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                            </div>
                            @endif
                            @else()
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="firstName" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Primer Nombre *</label>
                                    <input wire:model="firstName" type="text" id="firstName"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                        placeholder="Ingrese su nombre">
                                    @error('firstName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="lastName" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Segundo Nombre </label>
                                    <input wire:model="lastName" type="text" id="lastName"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                        placeholder="Ingrese su apellido">
                                    @error('lastName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="secondName" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Primer Apellido *</label>
                                    <input wire:model="secondName" type="text" id="secondName"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                        placeholder="Ingrese su nombre">
                                    @error('secondName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="secondLastName" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Segundo Apellido </label>
                                    <input wire:model="secondLastName" type="text" id="secondLastName"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                        placeholder="Ingrese su apellido">
                                    @error('secondLastName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            @endif
                            <!-- Email de Facturación -->
                            <label for="billingEmail" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email de Facturación</label>
                            <input wire:model.live.debounce.500ms="billingEmail" type="email" id="billingEmail"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500
                                 @error('billingEmail') border-red-500 @enderror
                                 @if($emailExists) border-red-500 @endif"
                                placeholder="Ingrese el email de facturación" required>
                            @error('billingEmail') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror

                            @if($emailExists && !$errors->has('billingEmail'))
                            <span class="text-red-500 text-sm">
                                Este email ya está registrado
                            </span>
                            @endif
                        </div>


                        <!--  vendedor -->

                        @livewire('selects.route-sales-day', [
                        'name' => 'routeId',
                        'label' => 'Ruta',
                        'required' => false,
                        'placeholder' => 'Seleccione una ruta (opcional)',
                        'routeId' => $routeId ?? ''
                        ])

                        <!-- district -->
                        <div>
                            <label for="district" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Barrio</label>
                            <input wire:model="district" type="text" id="district"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="ej: Galan" required>
                            @error('district') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Teléfono Empresarial -->
                        <div>
                            <label for="business_phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Teléfono Empresarial</label>
                            <input wire:model="business_phone" type="text" id="business_phone"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="ej: +57 300 123 4567" required>
                            @error('business_phone') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Teléfono Personal -->
                        <div>
                            <label for="personal_phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Teléfono Personal</label>
                            <input wire:model="personal_phone" type="text" id="personal_phone"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="ej: +57 310 987 6543">
                            @error('personal_phone') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Código CIIU -->
                        <div>
                            <label for="code_ciiu" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Código CIIU</label>
                            <input wire:model="code_ciiu" type="text" id="code_ciiu"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="ej: 4711">
                            @error('code_ciiu') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Nombre de la Sucursal -->
                        @if($editingId)
                        <div>
                            <label for="warehouseName" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Sucursal *</label>
                            <input wire:model="warehouseName" type="text" id="warehouseName" name="warehouseName"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="Ej: Sucursal Principal">
                            @error('warehouseName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        @endif
                        <!-- Ciudad de la Sucursal -->
                        <div>
                            @livewire('selects.city-select', [
                            'cityId' => $warehouseCityId ?? '',
                            'countryId' => 48,
                            'name' => 'warehouseCityId',
                            'placeholder' => 'Seleccionar ciudad',
                            'label' => 'Ciudad',
                            'required' => true,
                            'showLabel' => true,
                            'class' => 'w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500',
                            'index' => 0
                            ], key('city-select-warehouse'))

                            @error('warehouseCityId')
                            <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Código Postal -->
                        <div>
                            <label for="warehousePostcode" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Código Postal</label>
                            <input wire:model="warehousePostcode" type="text" id="warehousePostcode" name="warehousePostcode"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="Ej: 110111">
                            @error('warehousePostcode') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Dirección de la Sucursal -->
                        <div class="md:col-span-2">
                            <label for="warehouseAddress" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Dirección de la Sucursal *</label>
                            <input wire:model="warehouseAddress" type="text" id="warehouseAddress" name="warehouseAddress"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="Ej: Calle 123 #45-67">
                            @error('warehouseAddress') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Crear Usuario Checkbox (solo para nuevos clientes) -->
                        @if(!$editingId)
                        <div class="md:col-span-2">
                            <div class="flex items-center gap-3 p-4 rounded-lg
                                {{ empty($billingEmail) || $emailExists ? 'bg-gray-50 dark:bg-gray-900/20 border border-gray-200 dark:border-gray-700' : 'bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800' }}">
                                <input
                                    wire:model="createUser"
                                    type="checkbox"
                                    id="createUser"
                                    {{ empty($billingEmail) || $emailExists ? 'disabled' : '' }}
                                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded {{ empty($billingEmail) || $emailExists ? 'opacity-50 cursor-not-allowed' : '' }}">
                                <label for="createUser" class="text-sm font-medium flex-1 {{ empty($billingEmail) || $emailExists ? 'text-gray-400 dark:text-gray-600' : 'text-gray-700 dark:text-gray-300' }}">
                                    <span class="font-semibold">Convertir en Usuario</span>
                                    <p class="text-xs mt-1 {{ empty($billingEmail) || $emailExists ? 'text-gray-400 dark:text-gray-600' : 'text-gray-600 dark:text-gray-400' }}">
                                        @if(empty($billingEmail))
                                        Ingrese un email de facturación válido para habilitar esta opción
                                        @elseif($emailExists)
                                        No disponible: el email ya está registrado
                                        @else
                                        Crear automáticamente un usuario para acceder al sistema con perfil de Tienda
                                        @endif
                                    </p>
                                </label>
                                <div class="text-xs px-2 py-1 rounded {{ empty($billingEmail) || $emailExists ? 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400' : 'bg-indigo-100 dark:bg-indigo-900/50 text-indigo-800 dark:text-indigo-300' }}">
                                    Perfil: Tienda
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Actions -->
                        <div class="flex flex-col sm:flex-row sm:justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <button type="button"
                                wire:click="cancelForm"
                                class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 font-medium text-sm transition-colors order-2 sm:order-1">
                                Cancelar
                            </button>
                            <button type="submit"
                                wire:loading.attr="disabled"
                                class="inline-flex items-center justify-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 disabled:opacity-50 disabled:cursor-not-allowed border border-transparent rounded-lg font-medium text-sm text-white transition-colors order-1 sm:order-2">
                                <span wire:loading.remove>{{ $editingId ? 'Actualizar' : 'Crear' }}</span>
                                <span wire:loading class="flex items-center">
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
        @endif

        <!-- Warehouse Management Modal -->
        @if($showWarehouseModal && $selectedCompanyId)
        @livewire('tenant.vnt-company.warehouse-management-modal', ['companyId' => $selectedCompanyId], key('warehouse-modal-' . $selectedCompanyId))
        @endif

        <!-- Contact Management Modal -->
        @if($showContactModal && $selectedCompanyIdForContacts)
        @livewire('tenant.vnt-company.contact-management-modal', ['companyId' => $selectedCompanyIdForContacts], key('contact-modal-' . $selectedCompanyIdForContacts))
        @endif
    </div>
    @else
    <div>
        @if($companyId)
        <!-- Si hay un companyId, automáticamente abrir en modo edición sin botón -->
        <div wire:init="edit({{ $companyId }})"></div>
        @else
        <!-- Si no hay companyId, mostrar botón crear -->
        <button wire:click="create"
            class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
            </svg>
            Crear Nuevo
        </button>
        @endif
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
            x-transition:leave-end="opacity-0">
            <div class="relative min-h-screen flex items-center justify-center p-4">
                <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto"
                    x-transition:enter="ease-out duration-300"
                    x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="ease-in duration-200"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">

                    <!-- Header -->
                    <div class="border-b border-gray-200 dark:border-gray-700 px-6 py-4">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $editingId ? 'Editar' : 'Crear' }} Cliente
                        </h3>
                    </div>
                    <!-- Alert de Errores de Validación -->
                    @if (session()->has('error'))
                    <div x-data="{ showAlert: true }"
                        x-show="showAlert"
                        x-transition:enter="transition ease-out duration-300"
                        x-transition:enter-start="opacity-0 transform scale-90"
                        x-transition:enter-end="opacity-100 transform scale-100"
                        x-transition:leave="transition ease-in duration-200"
                        x-transition:leave-start="opacity-100 transform scale-100"
                        x-transition:leave-end="opacity-0 transform scale-90"
                        class="mx-6 mt-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-red-600 dark:text-red-400 mt-0.5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                            <div class="flex-1">
                                <h4 class="text-sm font-medium text-red-800 dark:text-red-300 mb-1">
                                    Error de Validación
                                </h4>
                                <div class="text-sm text-red-700 dark:text-red-400">
                                    {!! session('error') !!}
                                </div>
                            </div>
                            <button type="button"
                                @click="showAlert = false"
                                class="ml-3 text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 transition-colors">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>
                    </div>
                    @endif

                    <!-- Form -->
                    <form wire:submit="save" class="p-6 space-y-6">
                        <div class="space-y-6">
                            <!-- Tipo de Identificación -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Tipo de Identificación <span class="text-red-500">*</span>
                                </label>
                                @livewire('selects.type-identification-select', [
                                'typeIdentificationId' => $typeIdentificationId,
                                'name' => 'typeIdentificationId',
                                'placeholder' => 'Seleccione un tipo de identificación',
                                'label' => '',
                                'showLabel' => false,
                                'class' => 'w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500'
                                ])
                                @error('typeIdentificationId')
                                <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                            </div>

                            <!-- NIT/Identificación con campo DV condicional -->
                            @if($typeIdentificationId > 0)
                            @if($typeIdentificationId == 2)
                            <!-- NIT con DV -->
                            <div class="grid grid-cols-3 gap-4">
                                <div class="col-span-2">
                                    <label for="identification" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">NIT *</label>
                                    <div class="relative">
                                        <input wire:model.live.debounce.500ms="identification" type="text" id="identification" maxlength="15"
                                            class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500
                                               @error('identification') border-red-500 @enderror
                                               @if($identificationExists) border-red-500 @endif"
                                            placeholder="123456789" required>

                                        @if($validatingIdentification)
                                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                            <svg class="animate-spin h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                        </div>
                                        @endif
                                    </div>

                                    @error('identification')
                                    <span class="text-red-500 text-sm">{{ $message }}</span>
                                    @enderror

                                    @if($identificationExists && !$errors->has('identification'))
                                    <span class="text-red-500 text-sm">
                                        Este número de identificación ya está registrado
                                    </span>
                                    @endif
                                </div>
                                <div>
                                    <label for="verification_digit" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">DV *</label>
                                    <input wire:model="verification_digit" type="text" id="verification_digit" maxlength="1"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                        placeholder="0"
                                        oninput="this.value = this.value.replace(/[^0-9]/g, '')">
                                    @error('verification_digit') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                                <!-- Tipo de Persona -->
                                <div>
                                    <label for="typePerson" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tipo de Persona *</label>
                                    <select wire:model.live="typePerson" id="typePerson"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                        <option value="">Seleccionar tipo</option>
                                        <option value="Natural" {{ $typePerson === 'Natural' ? 'selected' : '' }}>Persona Natural</option>
                                        <option value="Juridica" {{ $typePerson === 'Juridica' ? 'selected' : '' }}>Persona Jurídica</option>
                                    </select>
                                    @error('typePerson') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            @else
                            <!-- Otros tipos de identificación -->
                            <div>
                                <label for="identification" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Número de Identificación *</label>
                                <div class="relative">
                                    <input wire:model.live.debounce.500ms="identification" type="text" id="identification" maxlength="15"
                                        class="w-full px-3 py-2 border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500
                                           @error('identification') border-red-500 @enderror
                                           @if($identificationExists) border-red-500 @endif"
                                        placeholder="Ingrese el número">

                                    @if($validatingIdentification)
                                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                        <svg class="animate-spin h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </div>
                                    @endif
                                </div>

                                @error('identification')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                                @enderror

                                @if($identificationExists && !$errors->has('identification'))
                                <span class="text-red-500 text-sm">
                                    Este número de identificación ya está registrado
                                </span>
                                @endif
                            </div>
                            @endif
                            @endif

                            <!-- Campos condicionales según tipo de persona -->
                            @if($typePerson)
                            @if($typePerson == 'Natural' || $showNaturalPersonFields)
                            <!-- Persona Natural: Nombre y Apellido -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="firstName" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Primer Nombre *</label>
                                    <input wire:model="firstName" type="text" id="firstName"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                        placeholder="Ingrese su nombre">
                                    @error('firstName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="lastName" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Segundo Nombre </label>
                                    <input wire:model="lastName" type="text" id="lastName"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                        placeholder="Ingrese su apellido">
                                    @error('lastName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="secondName" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Primer Apellido *</label>
                                    <input wire:model="secondName" type="text" id="secondName"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                        placeholder="Ingrese su nombre">
                                    @error('secondName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="secondLastName" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Segundo Apellido </label>
                                    <input wire:model="secondLastName" type="text" id="secondLastName"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                        placeholder="Ingrese su apellido">
                                    @error('secondLastName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            @elseif($typePerson == 'Juridica' && !$showNaturalPersonFields)
                            <!-- Persona Jurídica: Razón Social -->
                            <div>
                                <label for="businessName" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Razón Social *</label>
                                <input wire:model="businessName" type="text" id="businessName"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                    placeholder="Ingrese la razón social de la empresa">
                                @error('businessName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <!-- Selects para configuraciones fiscales -->
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <!-- Régimen -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Régimen <span class="text-red-500">*</span>
                                        </label>
                                        @livewire('selects.regime-select', [
                                        'regimeId' => $regimeId,
                                        'name' => 'regimeId',
                                        'label' => '',
                                        'showLabel' => false,
                                        'placeholder' => 'Seleccionar régimen',
                                        'class' => 'w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500'
                                        ])
                                        @error('regimeId')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <!-- Responsabilidad Fiscal -->
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            Responsabilidad Fiscal <span class="text-red-500">*</span>
                                        </label>
                                        @livewire('selects.fiscal-responsibility-select', [
                                        'fiscalResponsibilityId' => $fiscalResponsabilityId,
                                        'name' => 'fiscalResponsibilityId',
                                        'label' => '',
                                        'showLabel' => false,
                                        'placeholder' => 'Seleccionar responsabilidad fiscal',
                                        'class' => 'w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500'
                                        ])
                                        @error('fiscalResponsabilityId')
                                        <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                            </div>
                            @endif
                            @else()
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="firstName" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Primer Nombre *</label>
                                    <input wire:model="firstName" type="text" id="firstName"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                        placeholder="Ingrese su nombre">
                                    @error('firstName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="lastName" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Segundo Nombre </label>
                                    <input wire:model="lastName" type="text" id="lastName"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                        placeholder="Ingrese su apellido">
                                    @error('lastName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="secondName" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Primer Apellido *</label>
                                    <input wire:model="secondName" type="text" id="secondName"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                        placeholder="Ingrese su nombre">
                                    @error('secondName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label for="secondLastName" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Segundo Apellido </label>
                                    <input wire:model="secondLastName" type="text" id="secondLastName"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                        placeholder="Ingrese su apellido">
                                    @error('secondLastName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>
                            </div>
                            @endif
                            <!-- Email de Facturación -->
                            <label for="billingEmail" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email de Facturación</label>
                            <input wire:model.live.debounce.500ms="billingEmail" type="email" id="billingEmail"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500
                                 @error('billingEmail') border-red-500 @enderror
                                 @if($emailExists) border-red-500 @endif"
                                placeholder="Ingrese el email de facturación" required>
                            @error('billingEmail') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror

                            @if($emailExists && !$errors->has('billingEmail'))
                            <span class="text-red-500 text-sm">
                                Este email ya está registrado
                            </span>
                            @endif
                        </div>

                        <!-- Teléfono Empresarial -->
                        <div>
                            <label for="business_phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Teléfono Empresarial</label>
                            <input wire:model="business_phone" type="text" id="business_phone"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="ej: +57 300 123 4567" required>
                            @error('business_phone') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Teléfono Personal -->
                        <div>
                            <label for="personal_phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Teléfono Personal</label>
                            <input wire:model="personal_phone" type="text" id="personal_phone"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="ej: +57 310 987 6543">
                            @error('personal_phone') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Código CIIU -->
                        <div>
                            <label for="code_ciiu" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Código CIIU</label>
                            <input wire:model="code_ciiu" type="text" id="code_ciiu"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="ej: 4711">
                            @error('code_ciiu') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Nombre de la Sucursal -->
                        @if($editingId)
                        <div>
                            <label for="warehouseName" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Sucursal *</label>
                            <input wire:model="warehouseName" type="text" id="warehouseName" name="warehouseName"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="Ej: Sucursal Principal">
                            @error('warehouseName') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>
                        @endif
                        <!-- Ciudad de la Sucursal -->
                        <div>
                            @livewire('selects.city-select', [
                            'cityId' => $warehouseCityId ?? '',
                            'countryId' => 48,
                            'name' => 'warehouseCityId',
                            'placeholder' => 'Seleccionar ciudad',
                            'label' => 'Ciudad',
                            'required' => true,
                            'showLabel' => true,
                            'class' => 'w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500',
                            'index' => 0
                            ], key('city-select-warehouse'))
                        </div>

                        <!-- Código Postal -->
                        <div>
                            <label for="warehousePostcode" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Código Postal</label>
                            <input wire:model="warehousePostcode" type="text" id="warehousePostcode" name="warehousePostcode"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="Ej: 110111">
                            @error('warehousePostcode') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Dirección de la Sucursal -->
                        <div class="md:col-span-2">
                            <label for="warehouseAddress" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Dirección de la Sucursal *</label>
                            <input wire:model="warehouseAddress" type="text" id="warehouseAddress" name="warehouseAddress"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="Ej: Calle 123 #45-67">
                            @error('warehouseAddress') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <!-- Crear Usuario Checkbox (solo para nuevos clientes) -->
                        @if(!$editingId)
                        <div class="md:col-span-2">
                            <div class="flex items-center gap-3 p-4 rounded-lg
                                {{ empty($billingEmail) || $emailExists ? 'bg-gray-50 dark:bg-gray-900/20 border border-gray-200 dark:border-gray-700' : 'bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800' }}">
                                <input
                                    wire:model="createUser"
                                    type="checkbox"
                                    id="createUser"
                                    {{ empty($billingEmail) || $emailExists ? 'disabled' : '' }}
                                    class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded {{ empty($billingEmail) || $emailExists ? 'opacity-50 cursor-not-allowed' : '' }}">
                                <label for="createUser" class="text-sm font-medium flex-1 {{ empty($billingEmail) || $emailExists ? 'text-gray-400 dark:text-gray-600' : 'text-gray-700 dark:text-gray-300' }}">
                                    <span class="font-semibold">Convertir en Usuario</span>
                                    <p class="text-xs mt-1 {{ empty($billingEmail) || $emailExists ? 'text-gray-400 dark:text-gray-600' : 'text-gray-600 dark:text-gray-400' }}">
                                        @if(empty($billingEmail))
                                        Ingrese un email de facturación válido para habilitar esta opción
                                        @elseif($emailExists)
                                        No disponible: el email ya está registrado
                                        @else
                                        Crear automáticamente un usuario para acceder al sistema con perfil de Tienda
                                        @endif
                                    </p>
                                </label>
                                <div class="text-xs px-2 py-1 rounded {{ empty($billingEmail) || $emailExists ? 'bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400' : 'bg-indigo-100 dark:bg-indigo-900/50 text-indigo-800 dark:text-indigo-300' }}">
                                    Perfil: Tienda
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Actions -->
                        <div class="flex flex-col sm:flex-row sm:justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <button type="button"
                                wire:click="cancelForm"
                                class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 font-medium text-sm transition-colors order-2 sm:order-1">
                                Cancelar
                            </button>
                            <button type="submit"
                                wire:loading.attr="disabled"
                                class="inline-flex items-center justify-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 disabled:opacity-50 disabled:cursor-not-allowed border border-transparent rounded-lg font-medium text-sm text-white transition-colors order-1 sm:order-2">
                                <span wire:loading.remove>{{ $editingId ? 'Actualizar' : 'Crear' }}</span>
                                <span wire:loading class="flex items-center">
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
        @endif
    </div>
    @endif

    <!-- Routes Modal -->
    @if($showRoutesModal)
        @livewire('tenant.vnt-company.company-routes-modal', ['showModal' => true], key('routes-modal'))
    @endif

    <!-- Move District Modal -->
    @if($showMoveDistrictModal)
        @livewire('tenant.vnt-company.move-district-modal', ['showModal' => true], key('move-district-modal'))
    @endif
</div>