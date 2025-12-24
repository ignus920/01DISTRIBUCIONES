<div class="p-6 bg-gray-50 dark:bg-slate-900 min-h-screen transition-colors">
    <div class="max-w-12xl mx-auto">
        <!-- Header -->
        <div
            class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Gestión de Items de Tienda</h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">Administra los items de tu tienda</p>
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
        @if($successMessage)
        <div
            class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300 px-4 py-3 rounded-lg mb-6">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                {{ $successMessage }}
            </div>
        </div>
        @endif

        @if($errorMessage)
        <div
            class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 px-4 py-3 rounded-lg mb-6">
            <div class="flex items-center">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                {{ $errorMessage }}
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
                            <input wire:model.live.debounce.300ms="search" type="text" placeholder="Buscar por nombre o SKU..."
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
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                             <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Imagen
                            </th>
                            <th wire:click="sortBy('sku')"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 select-none">
                                <div class="flex items-center gap-1">
                                    SKU
                                    @if($sortField === 'sku')
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
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Categoría
                            </th>
                            <th wire:click="sortBy('stock')"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 select-none">
                                <div class="flex items-center gap-1">
                                    Stock
                                    @if($sortField === 'stock')
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
                            <th wire:click="sortBy('cost')"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 select-none">
                                <div class="flex items-center gap-1">
                                    Costo
                                    @if($sortField === 'cost')
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
                            <th wire:click="sortBy('price')"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 select-none">
                                <div class="flex items-center gap-1">
                                    Precio
                                    @if($sortField === 'price')
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
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Estado
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Acciones
                            </th>
                    </tr>
                </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($items as $item)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">

                                <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    @if($item->img_path && file_exists(public_path('storage/' . $item->img_path)))
                                        <img src="{{ asset('storage/' . $item->img_path) }}"
                                             alt="{{ $item->name }}"
                                             class="w-10 h-10 rounded-lg object-cover border border-gray-200 dark:border-gray-600">
                                    @else
                                        <div class="w-10 h-10 rounded-lg {{ $this->getAvatarColorClass($item->name) }} flex items-center justify-center text-white text-sm font-semibold border border-gray-200 dark:border-gray-600">
                                            {{ $this->getProductInitials($item->name) }}
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                                {{ $item->sku }}
                            </td>
                        
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $item->name }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                {{ $item->category->name ?? 'Sin categoría' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="@if($item->stock <= 0) text-red-600 dark:text-red-400 @elseif($item->stock <= 10) text-yellow-600 dark:text-yellow-400 @else text-green-600 dark:text-green-400 @endif font-medium">
                                    {{ $item->formatted_stock }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100">
                                {{ $item->formatted_cost }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-gray-100 font-medium">
                                {{ $item->formatted_price }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <button
                                    wire:click="toggleStatus({{ $item->id }})"
                                    class="relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 {{ $item->status ? 'bg-indigo-600 dark:bg-indigo-500' : 'bg-gray-200 dark:bg-gray-600' }}"
                                >
                                    <span class="sr-only">Cambiar estado</span>
                                    <span class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out {{ $item->status ? 'translate-x-5' : 'translate-x-0' }}"></span>
                                </button>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex items-center gap-2">
                                    <button
                                        wire:click="edit({{ $item->id }})"
                                        class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300"
                                        title="Editar"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </button>
                                    <!-- <button
                                        wire:click="delete({{ $item->id }})"
                                        wire:confirm="¿Estás seguro de eliminar este item?"
                                        class="text-red-600 hover:text-red-900"
                                        title="Eliminar"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button> -->
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                <div class="flex flex-col items-center">
                                    <svg class="w-12 h-12 text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                    </svg>
                                    <p class="text-lg font-medium text-gray-900 dark:text-gray-100">No hay items registrados</p>
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Comienza creando tu primer item</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        @if($items->hasPages())
                <div class="px-6 py-3 border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                    {{ $items->links() }}
                </div>
        @endif
    </div>

    <!-- Modal para crear/editar -->
    @if($showModal)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">
                        {{ $editMode ? 'Editar Item' : 'Crear Nuevo Item' }}
                    </h3>
                </div>

                <form wire:submit="save" class="px-6 py-4 space-y-4">
                    <!-- Sección de Imagen Principal -->
                    <div class="bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg p-4 mb-4">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0">
                                <svg class="w-5 h-5 text-gray-600 dark:text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                            </div>
                            <div class="flex-1">
                                <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Imagen Principal</h4>
                                
                                <div class="flex items-start gap-4">
                                    <!-- Preview de la imagen -->
                                    <div class="flex-shrink-0">
                                        @if($image)
                                            <!-- Nueva imagen temporal -->
                                            <img src="{{ $image->temporaryUrl() }}" alt="Preview" class="w-24 h-24 object-cover rounded-lg border-2 border-indigo-500 dark:border-indigo-400">
                                        @elseif($existingImage)
                                            <!-- Imagen existente -->
                                            <img src="{{ asset('storage/' . $existingImage) }}" alt="Imagen actual" class="w-24 h-24 object-cover rounded-lg border-2 border-gray-300 dark:border-gray-600">
                                        @else
                                            <!-- Placeholder -->
                                            <div class="w-24 h-24 bg-gray-200 dark:bg-gray-700 rounded-lg flex items-center justify-center border-2 border-dashed border-gray-300 dark:border-gray-600">
                                                <svg class="w-8 h-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                                </svg>
                                            </div>
                                        @endif
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 text-center">Principal</p>
                                    </div>

                                    <!-- Botones de acción -->
                                    <div class="flex-1">
                                        <div class="space-y-2">
                                            <!-- Botón para seleccionar archivo -->
                                            <label class="block">
                                                <span class="sr-only">Seleccionar archivo</span>
                                                <input 
                                                    type="file" 
                                                    wire:model="image" 
                                                    accept="image/*"
                                                    class="block w-full text-sm text-gray-500 dark:text-gray-400
                                                        file:mr-4 file:py-2 file:px-4
                                                        file:rounded-md file:border-0
                                                        file:text-sm file:font-semibold
                                                        file:bg-indigo-50 file:text-indigo-700
                                                        dark:file:bg-indigo-900/30 dark:file:text-indigo-400
                                                        hover:file:bg-indigo-100 dark:hover:file:bg-indigo-900/50
                                                        file:cursor-pointer cursor-pointer
                                                        border border-gray-300 dark:border-gray-600 rounded-md
                                                        focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                                >
                                            </label>
                                            
                                            @if($image || $existingImage)
                                                <button 
                                                    type="button"
                                                    wire:click="deleteImage"
                                                    class="text-sm text-red-600 dark:text-red-400 hover:text-red-800 dark:hover:text-red-300 font-medium"
                                                >
                                                    Eliminar imagen principal
                                                </button>
                                            @endif

                                            @error('image')
                                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                                            @enderror

                                            <div wire:loading wire:target="image" class="text-sm text-indigo-600 dark:text-indigo-400">
                                                Cargando imagen...
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- SKU -->
                        <div>
                            <label for="sku" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                SKU *
                            </label>
                            <input
                                wire:model="sku"
                                type="text"
                                id="sku"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 @error('sku') border-red-500 @enderror"
                            >
                            @error('sku')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Nombre -->
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Nombre *
                            </label>
                            <input
                                wire:model="name"
                                type="text"
                                id="name"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 @error('name') border-red-500 @enderror"
                            >
                            @error('name')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Categoría -->
                        <div>
                            <label for="categoryId" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Categoría *
                            </label>
                            <select
                                wire:model="categoryId"
                                id="categoryId"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 @error('categoryId') border-red-500 @enderror"
                            >
                                <option value="">Seleccionar categoría</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                            @error('categoryId')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Impuesto -->
                        <div>
                            <label for="taxId" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Impuesto
                            </label>
                            <select
                                wire:model="taxId"
                                id="taxId"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500"
                            >
                                <option value="">Sin impuesto</option>
                                @foreach($taxes as $tax)
                                    <option value="{{ $tax->id }}">{{ $tax->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Stock -->
                        <div>
                            <label for="stock" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Stock *
                            </label>
                            <input
                                wire:model="stock"
                                type="number"
                                step="0.01"
                                id="stock"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 @error('stock') border-red-500 @enderror"
                            >
                            @error('stock')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Costo -->
                        <div>
                            <label for="cost" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Costo *
                            </label>
                            <input
                                wire:model="cost"
                                type="number"
                                step="0.01"
                                id="cost"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 @error('cost') border-red-500 @enderror"
                            >
                            @error('cost')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Precio -->
                        <div>
                            <label for="price" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Precio *
                            </label>
                            <input
                                wire:model="price"
                                type="number"
                                step="0.01"
                                id="price"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500 @error('price') border-red-500 @enderror"
                            >
                            @error('price')
                                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                            @enderror
                        </div>

                    </div>

                    <!-- Botones -->
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button
                            type="button"
                            wire:click="closeModal"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-600 hover:bg-gray-200 dark:hover:bg-gray-500 rounded-md"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 rounded-md"
                        >
                            {{ $editMode ? 'Actualizar' : 'Crear' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>