<div class="min-h-screen bg-gray-50 dark:bg-gray-900 p-6">
    <div class="max-w-full mx-auto">
        <!-- Header -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Parámetros - Listas de Precios</h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">Gestión de listas de precios</p>
                </div>
                <button wire:click="create" 
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Crear Nuevo
                </button>
            </div>
        </div>
        
        <!-- Mensajes de éxito -->
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
                                placeholder="Buscar listas de precios..."
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
                        <x-export-buttons />
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
                            <th wire:click="sortBy('title')"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 select-none">
                                <div class="flex items-center gap-1">
                                    Título
                                    @if($sortField === 'title')
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
                            <th wire:click="sortBy('value')"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 select-none">
                                <div class="flex items-center gap-1">
                                    Valor
                                    @if($sortField === 'value')
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
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Estado</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse ($pricelists as $pl)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    {{ $pl->id }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ $pl->title }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                    {{ number_format($pl->value, 2) }}
                                </td>
                                <td class="px-6 py-3 whitespace-nowrap text-center text-sm font-medium text-gray-900 dark:text-white">
                                    <!-- Estado Toggle -->
                                    <div class="flex items-center justify-center">
                                        <div class="flex items-center space-x-3">
                                            <!-- Toggle Switch -->
                                            <button type="button"
                                                wire:click="togglePriceListStatus({{ $pl->id }})"
                                                class="relative inline-flex h-4 w-8 items-center rounded-full transition-all duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 hover:shadow-md {{ $pl->status ? 'bg-indigo-600 hover:bg-indigo-700' : 'bg-gray-300 dark:bg-gray-600 hover:bg-gray-400 dark:hover:bg-gray-500' }}"
                                                role="switch"
                                                aria-checked="{{ $pl->status ? 'true' : 'false' }}"
                                                aria-label="Toggle price list status">
                                                <span class="inline-block h-3 w-3 transform rounded-full bg-white shadow-sm transition-all duration-200 ease-in-out {{ $pl->status ? 'translate-x-4' : 'translate-x-1' }}"></span>
                                            </button>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                    <div class="flex items-center justify-center gap-2">
                                        <button wire:click="edit({{ $pl->id }})"
                                            class="inline-flex items-center px-3 py-1 bg-yellow-100 dark:bg-yellow-900/30 text-yellow-800 dark:text-yellow-300 text-xs font-medium rounded-full hover:bg-yellow-200 dark:hover:bg-yellow-900/50 transition-colors">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                            Editar
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-12 h-12 mb-4 text-gray-400 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                        </svg>
                                        <p class="text-lg font-medium">No se encontraron registros</p>
                                        <p class="text-sm">{{ $search ? 'Intenta ajustar tu búsqueda' : 'Comienza creando una nueva lista de precios' }}</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            @if($pricelists->hasPages())
            <div class="bg-white dark:bg-gray-800 px-6 py-3 border-t border-gray-200 dark:border-gray-700 rounded-b-lg">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-700 dark:text-gray-300">
                        Mostrando {{ $pricelists->firstItem() }} a {{ $pricelists->lastItem() }} de {{ $pricelists->total() }} resultados
                    </div>
                    <div>
                        {{ $pricelists->links() }}
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Modal de Creación/Edición -->
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

                <!-- Header del Modal -->
                <div class="border-b border-gray-200 dark:border-gray-700 px-6 py-4">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        {{ $pricelist_id ? 'Editar Lista de Precios' : 'Crear Lista de Precios' }}
                    </h3>
                </div>

                <!-- Formulario -->
                <form wire:submit.prevent="save" class="p-6 space-y-6">
                    <!-- Campo Título -->
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Título *</label>
                        <input wire:model="title" type="text" id="title"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                            placeholder="Ej: P1, P2, P3 (máx. 10 caracteres)">
                        @error('title') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <!-- Campo Valor -->
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Valor *</label>
                        <input wire:model="value" type="number" step="0.01" id="value"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                            placeholder="Ej: 1.0, 0.9, 1.15">
                        @error('value') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <!-- Botones del Formulario -->
                    <div class="flex flex-col sm:flex-row sm:justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button type="button" wire:click="cancel" 
                            class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 font-medium text-sm transition-colors order-2 sm:order-1">
                            Cancelar
                        </button>
                        <button type="submit"
                            wire:loading.attr="disabled"
                            class="inline-flex items-center justify-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 disabled:opacity-50 disabled:cursor-not-allowed border border-transparent rounded-lg font-medium text-sm text-white transition-colors order-1 sm:order-2">
                            {{ $pricelist_id ? 'Actualizar' : 'Crear' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- Modal de Confirmación de Eliminación -->
    <div x-data="{ open: @entangle('confirmingItemDeletion') }" x-show="open" style="display:none;" class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="fixed inset-0 bg-black opacity-50"></div>
        <div class="bg-white dark:bg-gray-800 rounded shadow p-6 z-50 w-full max-w-md">
            <h4 class="text-lg font-medium mb-4 text-gray-900 dark:text-white">Confirmar eliminación</h4>
            <p class="mb-4 text-gray-700 dark:text-gray-300">¿Deseas eliminar esta lista de precios?</p>
            <div class="flex justify-end space-x-2">
                <button type="button" wire:click="cancel" class="px-3 py-1 border border-gray-300 dark:border-gray-600 rounded text-gray-700 dark:text-gray-300">Cancelar</button>
                <button type="button" wire:click="deletePriceList" class="px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700">Eliminar</button>
            </div>
        </div>
    </div>
</div>
