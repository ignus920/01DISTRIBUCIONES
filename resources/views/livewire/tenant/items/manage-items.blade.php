<div class="min-h-screen bg-gray-50 dark:bg-gray-900 p-6">
    <div class="max-w-full mx-auto">
        <!-- Header -->
        <div
            class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Parámetros Items</h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">Gestion de registros</p>
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

        <!-- Mensajes -->
        @if (session()->has('message'))
        <div
            class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300 px-4 py-3 rounded-lg mb-6">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                {{ session('message') }}
            </div>
        </div>
        @endif

        <!-- Mensajes -->
        @if (session()->has('error'))
        <div
            class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 px-4 py-3 rounded-lg mb-6">
            <div class="flex items-center">
                <x-heroicon-o-exclamation-circle class="w-6 h-6" />
                {{ session('error') }}
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
                                <svg class="h-5 w-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Buscar registros..."
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
            </div>

            <!-- Tabla -->
            <div class="relative overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th
                                class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Imagen</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Sku</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Codigo Interno</th>
                            <th wire:click="sortBy('name')"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 select-none">
                                <div class="flex items-center gap-1">
                                    Nombre
                                    @if($sortField === 'name')
                                    @if($sortDirection === 'asc')
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z">
                                        </path>
                                    </svg>
                                    @else
                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z">
                                        </path>
                                    </svg>
                                    @endif
                                    @endif
                                </div>
                            </th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Tipo</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Marca</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Casa</th>    
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Stock</th>
                              <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Precios</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Unidad de compra</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Unidad de consumo</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Impuesto</th>
                            <th
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Estado</th>
                            <th
                                class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($items as $it)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <td class="px-6 py-4 text-center">
                                <img class="h-12 w-12 rounded-md object-cover border border-gray-300 dark:border-gray-600 mx-auto"
                                    src="{{ $it->getPrincipalThumbnailUrl() }}" alt="{{ $it->name }}">
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                {{ $it->sku }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                {{ $it->internal_code ?? $it->internalCode ?? '' }}
                            </td>
                            <td class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $it->name }}
                            </td>
                            <td class="px-2 py-2 text-xs text-gray-500 dark:text-gray-400">
                                {{ $it->type }}
                            </td>
                            <td class="px-2 py-2 text-sm text-gray-500 dark:text-gray-400">
                                {{ $it->brand->name ?? 'SIN MARCA' }}
                            </td>
                             <td class="px-2 py-2 text-xs text-gray-500 dark:text-gray-400">
                                {{ $it->house->name ?? 'SIN MARCA' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                @if($it->inventoriable == 1)
                                @if($it->invItemsStore->isNotEmpty())
                                @foreach($it->invItemsStore as $store)
                                {{ $store->stock_items_store }}
                                @endforeach
                                @else
                                <p>Sin stock</p>
                                @endif
                                @else
                                No maneja inventario
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                @if($it->invValues->isNotEmpty())
                                    <div class="space-y-1.5">
                                        @foreach($it->invValues->where('type', 'precio') as $value)
                                            <div class="flex items-center gap-2">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-300">
                                                    {{ str_replace('Precio ', '', $value->label) }}:
                                                    <span class="text-xs text-gray-500 dark:text-gray-400">${{ number_format($value->values, 0, ',', '.') }}</span>
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-gray-400 dark:text-gray-500 italic">Sin precios</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                {{ $it->purchasingUnit->description ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                {{ $it->consumptionUnit->description ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                {{ $it->tax->name ?? 'Sin impuesto' }}
                            </td>
                            <td
                                class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium text-gray-900 dark:text-white">
                                <!-- Estado Toggle -->
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-3">
                                        <!-- Toggle Switch -->
                                        <button type="button" wire:click="toggleItemStatus({{ $it->id }})"
                                            class="relative inline-flex h-4 w-8 items-center rounded-full transition-all duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 hover:shadow-md {{ $it->status ? 'bg-indigo-600 hover:bg-indigo-700' : 'bg-gray-300 dark:bg-gray-600 hover:bg-gray-400 dark:hover:bg-gray-500' }}"
                                            role="switch" aria-checked="{{ $it->status ? 'true' : 'false' }}"
                                            aria-label="Toggle company status">
                                            <span
                                                class="inline-block h-3 w-3 transform rounded-full bg-white shadow-sm transition-all duration-200 ease-in-out {{ $it->status ? 'translate-x-4' : 'translate-x-1' }}"></span>
                                        </button>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                <!-- Menú de tres puntos con Alpine.js -->
                                <div x-data="{ open: false }" @click.outside="open = false"
                                    class="relative inline-block text-left static">
                                    <button @click="open = !open" x-ref="button"
                                        class="flex items-center text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 rounded-lg p-1 transition-colors">
                                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                            <path
                                                d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                                        </svg>
                                    </button>

                                    <!-- Menú desplegable -->
                                    <div x-show="open" x-transition:enter="transition ease-out duration-100"
                                        x-transition:enter-start="transform opacity-0 scale-95"
                                        x-transition:enter-end="transform opacity-100 scale-100"
                                        x-transition:leave="transition ease-in duration-75"
                                        x-transition:leave-start="transform opacity-100 scale-100"
                                        x-transition:leave-end="transform opacity-0 scale-95" @click="open = false"
                                        class="origin-top-left fixed left-auto right-auto mt-2 w-48 rounded-lg shadow-lg bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 dark:ring-gray-700 z-[60]"
                                        x-anchor="$refs.button"
                                        style="display: none;">

                                        <div class="py-1" role="menu" aria-orientation="vertical">
                                            <button wire:click="edit({{ $it->id }})"
                                                class="w-full text-left px-4 py-2 text-sm text-yellow-800 dark:text-yellow-300 hover:bg-yellow-50 dark:hover:bg-yellow-900/20 transition-colors flex items-center">
                                                <x-heroicon-o-pencil-square class="w-6 h-6" />

                                                Editar
                                            </button>
                                            {{-- <button wire:click="openWarehouseModal({{ $it->id }})"
                                                class="w-full text-left px-4 py-2 text-sm text-orange-800 dark:text-orange-300 hover:bg-orange-50 dark:hover:bg-orange-900/20 transition-colors flex items-center">
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                                                    </path>
                                                </svg>
                                                Ubicaciones
                                            </button> --}}
                                            {{-- <button wire:click="openValuesModal({{ $it->id }})"
                                                class="w-full text-left px-4 py-2 text-sm text-green-800 dark:text-green-300 hover:bg-green-50 dark:hover:bg-green-900/20 transition-colors flex items-center">
                                                <x-heroicon-o-currency-dollar class="w-6 h-6" />
                                                Valores
                                            </button> --}}
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                <div class="flex flex-col items-center">
                                    <svg class="w-12 h-12 mb-4 text-gray-400 dark:text-gray-600" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4">
                                        </path>
                                    </svg>
                                    <p class="text-lg font-medium">No se encontraron registros</p>
                                    <p class="text-sm">{{ $search ? 'Intenta ajustar tu búsqueda' : 'Comienza creando un
                                        nuevo registro' }}</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            @if($items->hasPages())
            <div
                class="overflow-x-auto bg-white dark:bg-gray-800 px-6 py-3 border-t border-gray-200 dark:border-gray-700 rounded-b-lg">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-700 dark:text-gray-300">
                        Mostrando {{ $items->firstItem() }} a {{ $items->lastItem() }} de {{ $items->total() }}
                        resultados
                    </div>
                    <div>
                        {{ $items->links() }}
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>

    <!-- Modal Registro Item-->
    @if($showModal)
    <div class="fixed inset-0 bg-gray-600 dark:bg-gray-900 bg-opacity-50 dark:bg-opacity-75 overflow-y-auto h-full w-full z-50"
        x-data="{ show: true }" x-show="show" x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
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
                <div class="border-b border-gray-200 dark:border-gray-700 px-6 py-4 flex justify-between items-center">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            {{ $item_id ? 'Editar Item' : 'Crear Item' }}
                        </h3>
                    </div>
                    <button wire:click="cancel"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                        <x-heroicon-o-x-mark class="w-6 h-6" />
                    </button>
                </div>

                <!-- Form -->
                <form wire:submit.prevent="save" class="p-6 space-y-6">
                    <div class="space-y-6">
                        @livewire('tenant.items.categories', [
                        'categoryId' => $category_id,
                        'name' => 'category_id',
                        'label' => 'Categoría',
                        'placeholder' => 'Seleccione una categoria',
                        'required' => true,
                        'class' => 'w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white
                        dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2
                        focus:ring-indigo-500 focus:border-indigo-500'
                        ])

                        <div class="mb-3 ">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nombre
                                <span class="text-red-500">*</span></label>
                            <input wire:model="name" type="text" id="name"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                placeholder="Ingrese nombre del producto">
                            @error('name') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="mb-3 grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Código
                                    interno <span class="text-red-500">*</span></label>
                                <input wire:model.live.debounce.400ms="internal_code" type="text" id="internal_code"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                    placeholder="Ingrese el código interno">
                                @error('internal_code') <span class="text-red-600 text-sm">{{ $message }}</span>
                                @enderror

                                @if($internal_codeExists && !$errors->has('internal_code'))
                                <span class="text-red-500 text-sm">
                                    Este código interno ya está registrado
                                </span>
                                @endif
                            </div>
                            <div>
                                <label
                                    class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">SKU</label>
                                <input wire:model.live.debounce.400ms="sku" type="text" id="sku"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                    placeholder="Ingrese el sku">
                                @error('sku') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror

                                @if($skuExists && !$errors->has('sku'))
                                <span class="text-red-500 text-sm">
                                    Este SKU ya está registrado
                                </span>
                                @endif
                            </div>
                        </div>

                        <div class="mb-3 grid grid-cols-2 gap-2">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tipo <span class="text-red-500">*</span></label>
                                <select wire:model="type" {{ $disabled ? 'disabled' : '' }}
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="">-- Seleccione --</option>
                                    @foreach($types as $k => $v)
                                    <option value="{{ $k }}">{{ $v }}</option>
                                    @endforeach
                                </select>
                                @error('type') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label
                                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Impuesto</label>
                                <select wire:model="tax"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="">-- Seleccione --</option>
                                    @foreach($this->taxes as $tax)
                                    <option value="{{ $tax->id }}">{{ $tax->name }}</option>
                                    @endforeach
                                </select>
                                @error('type') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        @if($showCommand)
                        @livewire('tenant.items.command', [
                        'commandId' => $commandId,
                        'name' => 'commandId',
                        'label' => 'Comanda',
                        'placeholder' => 'Seleccione una comanda',
                        'class' => 'w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white
                        dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2
                        focus:ring-indigo-500 focus:border-indigo-500'
                        ])
                        @endif

                        @livewire('tenant.items.brand',[
                        'brandId' => $brandId,
                        'name' => 'brandId',
                        'label' => 'Marca',
                        'placeholder' => 'Seleccione una marca',
                        'class' => 'w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white
                        dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2
                        focus:ring-indigo-500 focus:border-indigo-500'
                        ])

                        @livewire('tenant.items.house',[
                        'houseId' => $houseId,
                        'name' => 'houseId',
                        'label' => 'Casa',
                        'placeholder' => 'Seleccione una casa',
                        'class' => 'w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white
                        dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2
                        focus:ring-indigo-500 focus:border-indigo-500'
                        ])

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            @livewire('tenant.items.purchasing-unit', [
                            'purchaseUnitId' => $purchase_unit,
                            'name' => 'purchase_unit',
                            'label' => 'Unidad de compra',
                            'placeholder' => 'Seleccione una unidad de compra',
                            'class' => 'w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white
                            dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2
                            focus:ring-indigo-500 focus:border-indigo-500'
                            ])

                            @livewire('tenant.items.consumption-unit', [
                            'consumptionUnitId' => $consumption_unit,
                            'name' => 'consumption_unit',
                            'label' => 'Unidad de consumo',
                            'placeholder' => 'Seleccione una unidad de consumo',
                            'class' => 'w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white
                            dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2
                            focus:ring-indigo-500 focus:border-indigo-500'
                            ])
                        </div>

                        <div class="mb-3 grid grid-cols-2 gap-2">
                            @if($this->manageSerials())
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Maneja
                                    Serial</label>
                                <select wire:model="handles_serial"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="">-- Seleccione --</option>
                                    <option value="1">SI</option>
                                    <option value="0">NO</option>
                                </select>
                                @error('type') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                            </div>
                            @endif
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Maneja
                                    Inventario</label>
                                <select wire:model="inventoriable"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                    <option value="">-- Seleccione --</option>
                                    <option value="1">SI</option>
                                    <option value="0">NO</option>
                                </select>
                                @error('type') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Descripción</label>
                            <textarea wire:model="description"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                rows="3">
                            </textarea>
                        </div>

                        <!--div class="flex items-center justify-between">
                            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
                                Generico
                            </label>
                            <div class="flex items-center space-x-3">
                                <span class="text-sm transition-colors duration-200 {{ $generic ? 'text-gray-500 dark:text-gray-400' : 'text-gray-900 dark:text-white font-medium' }}">
                                    NO
                                </span-->
                        <!-- Toggle Switch -->
                        <!--button type="button" 
                                    wire:click="toggleGeneric" 
                                    class="relative inline-flex h-6 w-11 items-center rounded-full transition-all duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 hover:shadow-md {{ $generic ? 'bg-indigo-600 hover:bg-indigo-700' : 'bg-gray-300 dark:bg-gray-600 hover:bg-gray-400 dark:hover:bg-gray-500' }}"
                                    role="switch" 
                                    aria-checked="{{ $generic ? 'true' : 'false' }}"
                                    aria-label="Toggle company status">
                                    <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow-sm transition-all duration-200 ease-in-out {{ $generic ? 'translate-x-6' : 'translate-x-1' }}"></span>
                                </button>
                                <span class="text-sm transition-colors duration-200 {{ $generic ? 'text-gray-900 dark:text-white font-medium' : 'text-gray-500 dark:text-gray-400' }}">
                                    SI
                                </span>
                            </div>
                        </div -->

                        <div class="border-t border-gray-300 my-6"></div>
                        <h3 class="text-lg blockfont-medium text-gray-700 dark:text-gray-300 mb-2">Valores</h3>

                        {{-- Si el item es NUEVO: mostrar inputs para agregar valores --}}
                        @if (!$item_id)
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-gray-50 dark:bg-gray-900">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Etiqueta</th>
                                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Tipo</th>
                                            <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">Valor</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                        @php
                                            $staticValues = [
                                                ['label' => 'Costo Inicial', 'type' => 'Costo'],
                                                ['label' => 'Costo', 'type' => 'Costo'],
                                                ['label' => 'Precio Base', 'type' => 'Precio'],
                                                ['label' => 'Precio Regular', 'type' => 'Precio'],
                                                ['label' => 'Precio Crédito', 'type' => 'Precio'],
                                            ];
                                        @endphp
                                        @foreach ($staticValues as $index => $staticValue)
                                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                                <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                                    {{ $staticValue['label'] }}
                                                </td>
                                                <td class="px-4 py-3 text-sm">
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $staticValue['type'] === 'Costo' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' }}">
                                                        {{ $staticValue['type'] }}
                                                    </span>
                                                </td>
                                                <td class="px-4 py-3 text-sm text-right">
                                                    <input 
                                                        type="number" 
                                                        step="0.01" 
                                                        min="0"
                                                        wire:model="tempValues.{{ $staticValue['label'] }}"
                                                        placeholder="0.00"
                                                        class="w-28 px-2 py-1 text-right border border-gray-300 dark:border-gray-600 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:text-white text-sm"
                                                    >
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <p class="text-sm text-gray-600 dark:text-gray-400 mt-3">
                                <strong>Nota:</strong> Los valores serán guardados cuando registres el item.
                            </p>
                        @else
                            {{-- Si el item YA EXISTE: mostrar botón para abrir modal --}}
                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-3">
                                    <button type="button" wire:click="openValuesModal({{ $item_id }})"
                                        class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150"
                                        title="Gestionar valores del item">
                                        <x-heroicon-o-cog-6-tooth class="w-4 h-4 mr-2" />
                                        Gestionar Valores
                                    </button>
                                </div>
                            </div>
                        @endif


                            {{-- @if ($showValuesSection)
                                <div class="mb-3 grid grid-cols-2 gap-2">
                                    <!--Valor-->
                                    <div class="mb-4">
                                        <label
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Valor</label>
                                        <input wire:model="valueItem" type="text" id="valueItem"
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                                            placeholder="Ingrese el valor">
                                        @error('valueItem') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                    <!--Tipo de valor-->
                                    <div class="mb-4">
                                        <label
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tipo</label>
                                        <select wire:model.live="typeValue"
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                            <option value="">-- Seleccione --</option>
                                            <option value="costo">Costo</option>
                                            <option value="precio">Precio</option>
                                        </select>
                                        @error('typeValue') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                    <!--Etiqueta del valor-->
                                    <div class="mb-4">
                                        <label
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Etiqueta</label>
                                        <select wire:model="labelValue"
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700">
                                            <option value="">-- Seleccione --</option>
                                            @foreach($this->labelsValues as $key => $value)
                                            <option wire:key="label-{{ $key }}" value="{{ $key }}">
                                                {{ $value }}
                                            </option>
                                            @endforeach
                                        </select>
                                        @error('labelValue') <span class="text-red-600 text-sm">{{ $message }}</span> @enderror
                                    </div>
                                    <!--Sucursal / Si aplica-->
                                    <div class="mb-4">
                                        <label
                                            class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Sucursal</label>
                                        <select wire:model="warehouseIdValue"
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                                            <option value="">-- Seleccione --</option>
                                            @foreach($warehouses as $warehouse)
                                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('warehouseIdValue') <span class="text-red-600 text-sm">{{ $message }}</span>
                                        @enderror
                                    </div>
                                </div>
                                @if($temporaryErrorMessage)
                                    <div x-data="{show:true}" x-show="show"
                                        x-init="setTimeout(()=>{show = false; $wire.call('clearTemporaryMessage')}, 2000)"
                                        class="p-4 mt-2 text-sm text-red-800 rounded-lg bg-red-50 dark:bg-gray-800 dark:text-red-400"
                                        role="alert">
                                        <span class="font-medium">{{$temporaryErrorMessage}}</span>
                                    </div>
                                @endif

                                <!--Botón-->
                                <div class="flex justify-end">
                                    <button type="button" wire:click="SaveValueItem" wire:loading.attr="disabled"
                                        class="inline-flex items-center justify-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 disabled:opacity-50 disabled:cursor-not-allowed border border-transparent rounded-lg font-medium text-sm text-white transition-colors order-1 sm:order-2">
                                        <span wire:loading.remove>Agregar</span>
                                        <span wire:loading>Guardando...</span>
                                    </button>
                                </div>
                            @endif

                            <!----Tabla Valores----->

                            <div
                                class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                                @livewire('tenant.items.inv-values', ['ItemId' => $item_id])
                            </div> --}}

                        {{-- @endif --}}

                        <!-- Sección de Galería de Imágenes -->
                        @if ($item_id)
                        <div class="border-t border-gray-300 dark:border-gray-600 my-6"></div>
                        <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-300 mb-4 flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                </path>
                            </svg>
                            Imágenes del Producto
                        </h3>
                        @livewire('tenant.items.item-image-upload', ['itemId' => $item_id],
                        key('image-upload-'.$item_id))
                        @endif

                        <!-- Mensajes -->
                        @if ($messageValues)
                        <div x-data="{ showAlert: true }" x-show="showAlert"
                            x-transition:enter="transition ease-out duration-300"
                            x-transition:enter-start="opacity-0 transform scale-90"
                            x-transition:enter-end="opacity-100 transform scale-100"
                            class="mb-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                            <div class="flex items-start">
                                <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-red-700" />
                                <div class="flex-1">
                                    <p class="text-sm text-red-700 dark:text-red-400">{{ $messageValues }}</p>
                                </div>
                                <button type="button" @click="showAlert = false"
                                    class="ml-3 text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 transition-colors">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd"
                                            d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                            clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
                        </div>
                        @endif
                        <div
                            class="flex flex-col sm:flex-row sm:justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                            <button type="button" wire:click="cancel"
                                class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 font-medium text-sm transition-colors order-2 sm:order-1">
                                Cancelar
                            </button>
                            <button type="submit" wire:loading.attr="disabled"
                                class="inline-flex items-center justify-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 disabled:opacity-50 disabled:cursor-not-allowed border border-transparent rounded-lg font-medium text-sm text-white transition-colors order-1 sm:order-2">
                                <span>{{ $item_id ? 'Actualizar' : 'Crear' }}</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- Delete confirmation (simple) -->
    <div x-data="{ open: @entangle('confirmingItemDeletion') }" x-show="open" style="display:none;"
        class="fixed inset-0 z-50 flex items-center justify-center">
        <div class="fixed inset-0 bg-black opacity-50"></div>
        <div class="bg-white rounded shadow p-6 z-50 w-full max-w-md">
            <h4 class="text-lg font-medium mb-4">Confirmar eliminación</h4>
            <p class="mb-4">¿Deseas eliminar este item?</p>
            <div class="flex justify-end space-x-2">
                <button type="button" wire:click="cancel" class="px-3 py-1 border rounded">Cancelar</button>
                <button type="button" wire:click="deleteItem"
                    class="px-3 py-1 bg-red-600 text-white rounded">Eliminar</button>
            </div>
        </div>
    </div>

    <!-- Modal Values -->

    <!-- Modal Ubicaciones -->
    @if($showValuesModal)
    @livewire('tenant.items.manage-values', ['ItemId' => $item_id], key($item_id))
    @endif
</div>