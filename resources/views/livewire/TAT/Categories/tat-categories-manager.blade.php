<div class="min-h-screen bg-gray-50 dark:bg-slate-900 transition-colors duration-200">
    <div class="px-4 sm:px-6 lg:px-8 py-6">
        <!-- Header -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6 mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Parámetros Categorías</h1>
                    <p class="text-sm text-gray-600 dark:text-slate-400 mt-1">Gestión de registros</p>
                </div>
                <button wire:click="create"
                    class="inline-flex items-center px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white text-sm font-medium rounded-lg transition-all duration-200 shadow-sm hover:shadow">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Crear Nuevo
                </button>
            </div>
        </div>

        <!-- Mensajes -->
        @if (session()->has('message'))
        <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300 px-4 py-3 rounded-xl mb-6">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                {{ session('message') }}
            </div>
        </div>
        @endif

        <!-- Toolbar -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-4 mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <!-- Búsqueda -->
                <div class="relative flex-1 max-w-md">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-4 w-4 text-gray-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Buscar registros..."
                        class="block w-full pl-10 pr-3 py-2 bg-gray-50 dark:bg-slate-700 border border-gray-300 dark:border-slate-600 rounded-lg text-sm text-gray-900 dark:text-slate-200 placeholder-gray-500 dark:placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-transparent transition-all">
                </div>

                <!-- Controles -->
                 <div class="flex items-center gap-3">
                        <!-- Registros por página -->
                        <div class="flex items-center gap-2">
                            <label class="text-sm text-gray-700 dark:text-gray-300">Mostrar:</label>
                            <select wire:model.live="perPage"
                                class="border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>
                    </div>
            </div>
        </div>

        <!-- Tabla -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                    <thead class="bg-gray-50 dark:bg-slate-800/50">
                        <tr>
                            <th wire:click="sortBy('id')"
                                class="px-6 py-3.5 text-left text-xs font-medium text-gray-600 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-slate-700/50 select-none transition-colors">
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
                            <th wire:click="sortBy('name')"
                                class="px-6 py-3.5 text-left text-xs font-medium text-gray-600 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-slate-700/50 select-none transition-colors">
                                <div class="flex items-center gap-1">
                                    Nombre
                                    @if($sortField === 'name')
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
                            <th class="px-6 py-3.5 text-center text-xs font-medium text-gray-600 dark:text-slate-400 uppercase tracking-wider">
                                Estado
                            </th>
                            <th class="px-6 py-3.5 text-center text-xs font-medium text-gray-600 dark:text-slate-400 uppercase tracking-wider">
                                Acciones
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700/50">
                        @forelse ($categories as $category)
                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/30 transition-colors duration-150">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900 dark:text-white">
                                #{{ $category->id }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                {{ $category->name }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium text-gray-900 dark:text-white">
                                <!-- Estado Toggle -->
                                <div class="flex items-center justify-center">
                                    <button type="button" wire:click="toggleStatus({{ $category->id }})"
                                        class="relative inline-flex h-5 w-9 items-center rounded-full transition-all duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 hover:shadow-md {{ $category->status ? 'bg-indigo-600 hover:bg-indigo-700' : 'bg-gray-300 dark:bg-gray-600 hover:bg-gray-400 dark:hover:bg-gray-500' }}"
                                        role="switch" aria-checked="{{ $category->status ? 'true' : 'false' }}">
                                        <span class="inline-block h-3.5 w-3.5 transform rounded-full bg-white shadow-sm transition-all duration-200 ease-in-out {{ $category->status ? 'translate-x-4' : 'translate-x-1' }}"></span>
                                    </button>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                <button wire:click="edit({{ $category->id }})"
                                    class="inline-flex items-center px-3 py-1.5 bg-yellow-50 dark:bg-yellow-900/30 text-yellow-700 dark:text-yellow-400 text-xs font-medium rounded-lg hover:bg-yellow-100 dark:hover:bg-yellow-900/50 transition-colors border border-yellow-200 dark:border-yellow-900/50">
                                    <svg class="w-3.5 h-3.5 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                    Editar
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-6 py-16 text-center text-gray-500 dark:text-gray-400">
                                <div class="flex flex-col items-center">
                                    <div class="w-16 h-16 bg-gray-100 dark:bg-slate-700 rounded-full flex items-center justify-center mb-4">
                                        <svg class="w-8 h-8 text-gray-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                                        </svg>
                                    </div>
                                    <p class="text-base font-medium text-gray-900 dark:text-white">No se encontraron registros</p>
                                    <p class="text-sm mt-1">{{ $search ? 'Intenta ajustar tu búsqueda' : 'Comienza creando un nuevo registro' }}</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            <!-- Paginación -->
            @if($categories->hasPages())
            <div class="bg-gray-50 dark:bg-slate-800/50 px-6 py-4 border-t border-gray-200 dark:border-slate-700">
                {{ $categories->links() }}
            </div>
            @endif
        </div>
    </div>

    <!-- Modal -->
    @if($showModal)
    <div class="fixed inset-0 bg-gray-900/50 dark:bg-gray-900/80 backdrop-blur-sm overflow-y-auto h-full w-full z-50 transition-opacity"
        x-data="{ show: true }" x-show="show" x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0">
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="relative bg-white dark:bg-slate-800 rounded-xl shadow-2xl max-w-lg w-full transform transition-all border border-gray-100 dark:border-slate-700"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">

                <!-- Header -->
                <div class="border-b border-gray-200 dark:border-slate-700 px-6 py-5 flex justify-between items-center bg-gray-50 dark:bg-slate-800/50 rounded-t-xl">
                    <h3 class="text-lg font-bold text-gray-900 dark:text-white">
                        {{ $editingId ? 'Editar Categoría' : 'Crear Nueva Categoría' }}
                    </h3>
                    <button wire:click="closeModal" class="text-gray-400 hover:text-gray-500 dark:hover:text-gray-300 focus:outline-none">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <!-- Form -->
                <form wire:submit.prevent="save" class="p-6 space-y-6">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Nombre de la Categoría <span class="text-red-500">*</span></label>
                        <input wire:model="name" type="text" id="name"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-slate-600 rounded-lg bg-gray-50 dark:bg-slate-700 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-transparent transition-all"
                            placeholder="Ej: Bebidas, Lácteos...">
                        @error('name') <span class="text-red-600 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    
                    <div class="flex flex-col sm:flex-row sm:justify-end gap-3 pt-2">
                        <button type="button" wire:click="closeModal"
                            class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2.5 border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-slate-700 font-medium text-sm transition-colors focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 dark:focus:ring-offset-gray-900">
                            Cancelar
                        </button>
                        <button type="submit" wire:loading.attr="disabled"
                            class="w-full sm:w-auto inline-flex items-center justify-center px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 border border-transparent rounded-lg font-medium text-sm text-white transition-all shadow-sm hover:shadow focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-600 dark:focus:ring-offset-gray-900 disabled:opacity-50 disabled:cursor-not-allowed">
                            <svg wire:loading wire:target="save" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>{{ $editingId ? 'Guardar Cambios' : 'Crear Categoría' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
</div>