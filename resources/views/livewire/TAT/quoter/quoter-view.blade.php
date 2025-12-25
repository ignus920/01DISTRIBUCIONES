<div class="quoter-main-container min-h-screen bg-gray-50 dark:bg-gray-900 p-2 sm:p-6 relative
     {{ request()->routeIs('tenant.tat.quoter.index') ? 'lg:min-h-[calc(100vh-4rem)]' : '' }}">
    <div class="w-full max-w-full sm:max-w-12xl mx-auto">
    <!-- Header con botones de estado -->
    <div class="bg-gray-50 dark:bg-gray-800 p-3 border-b dark:border-gray-700">
     

        <!-- Total -->
        <div class="hidden lg:block bg-black dark:bg-gray-950 text-white p-4 lg:p-6 rounded mb-3 ring-2 ring-blue-500 dark:ring-blue-600">
    <div class="text-right lg:text-center text-2xl lg:text-4xl font-bold text-white">${{ number_format($total, 0, '.', '.') }}</div>
</div>

        <!-- Cliente -->
        <div class="bg-green-100 dark:bg-green-900/30 p-3 lg:p-4 rounded border border-green-200 dark:border-green-700">
            @if($showClientSearch)
                <!-- Modo búsqueda de cliente -->
                <div class="space-y-2">
                    <div class="flex gap-1">
                        <!-- Botón hamburguesa solo en móvil -->
                        <button
                            x-data
                            @click="$dispatch('toggle-mobile-menu')"
                            class="lg:hidden flex items-center justify-center w-10 h-10 bg-green-600 hover:bg-green-700 active:bg-green-800 text-white rounded-lg transition-all duration-200 mr-1 shadow-md hover:shadow-lg active:scale-95"
                            title="Abrir menú de navegación"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                            </svg>
                        </button>

                        <input
                            wire:model.live.debounce.300ms="customerSearch"
                            type="text"
                            placeholder="Buscar por nombre o cédula... (↑↓ navegar, Enter seleccionar)"
                            class="flex-1 text-sm px-3 py-2 lg:px-4 lg:py-3 lg:text-base bg-white dark:bg-gray-700 dark:text-white border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:border-blue-500 dark:focus:border-blue-600 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-600 shadow-sm"
                            onkeydown="handleCustomerSearchKeydown(event)"
                            id="customerSearchInput"
                            autofocus
                        >
                        <button
                            wire:click="cancelClientSearch"
                            class="px-2 py-1 bg-gray-500 text-white text-xs rounded hover:bg-gray-600"
                            title="Cancelar"
                        >
                            ✕
                        </button>
                    </div>

                    <!-- Resultados de búsqueda -->
                    @if(count($customerSearchResults) > 0)
                        <div id="customerSearchResults" class="max-h-32 overflow-y-auto border border-green-300 rounded bg-white">
                            @foreach($customerSearchResults as $index => $customer)
                                <div
                                    wire:click="selectCustomer({{ $customer['id'] }})"
                                    data-customer-id="{{ $customer['id'] }}"
                                    data-index="{{ $index }}"
                                    class="customer-result p-2 text-xs hover:bg-green-50 cursor-pointer border-b last:border-b-0 transition-colors duration-150"
                                >
                                    <div class="font-mono font-bold">{{ $customer['identification'] }}</div>
                                    <div class="text-gray-600">{{ $customer['display_name'] }}</div>
                                </div>
                            @endforeach
                        </div>
                    @elseif(strlen($customerSearch) >= 2)
                        <div class="p-2 text-xs text-gray-500 border border-green-300 rounded bg-white">
                            <div class="mb-1">No se encontraron clientes</div>
                            <button
                                wire:click="openCustomerModal"
                                class="px-2 py-1 bg-blue-500 text-white rounded hover:bg-blue-600 text-xs"
                            >
                                Crear nuevo cliente
                            </button>
                        </div>
                    @endif
                </div>
            @else
                <!-- Modo mostrar cliente seleccionado -->
                <div class="flex justify-between items-center">
                    <div class="flex items-center gap-2 flex-1">
                        <!-- Botón hamburguesa solo en móvil -->
                        <button
                            x-data
                            @click="$dispatch('toggle-mobile-menu')"
                            class="lg:hidden flex items-center justify-center w-10 h-10 bg-green-600 hover:bg-green-700 active:bg-green-800 text-white rounded-lg transition-all duration-200 flex-shrink-0 shadow-md hover:shadow-lg active:scale-95"
                            title="Abrir menú de navegación"
                        >
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                            </svg>
                        </button>

                        <div class="flex-1 min-w-0">
                            @if($selectedCustomer)
                                <div class="text-green-700 dark:text-green-300 font-mono text-sm lg:text-lg font-bold truncate">
                                    {{ $selectedCustomer['identification'] }}
                                </div>
                                <div class="text-xs lg:text-sm text-green-600 dark:text-green-400 truncate">{{ $selectedCustomer['display_name'] }}</div>
                            @else
                                <div class="text-green-700 dark:text-green-300 font-mono text-sm lg:text-lg font-bold">
                                    Sin cliente
                                </div>
                                <div class="text-xs lg:text-sm text-green-600 dark:text-green-400">Seleccionar cliente</div>
                            @endif
                        </div>
                    </div>
                    <button
                        wire:click="enableClientSearch"
                        class="px-2 py-1 bg-green-600 text-white text-xs rounded hover:bg-green-700 flex items-center justify-center flex-shrink-0"
                        title="Cambiar cliente"
                    >
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </button>
                </div>
            @endif
        </div>

        <!-- Input de búsqueda de productos - Ahora debajo del cliente -->
        <div class="space-y-1 mt-3">
            <!-- Input de búsqueda optimizado -->
            <div class="relative" x-data="{ isLoading: false, fullscreenSearch: false }" @keydown.escape="fullscreenSearch = false">
                <!-- Input principal con indicador de carga -->
                <div class="relative">
                    <input type="text"
                           wire:model.live.debounce.250ms="currentSearch"
                           placeholder="Buscar por nombre o SKU (mín. 2 caracteres)..."
                           class="w-full px-3 py-3 sm:px-4 sm:py-3 text-sm sm:text-base bg-white dark:bg-gray-700 dark:text-white border-2 border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:border-blue-500 dark:focus:border-blue-600 focus:ring-2 focus:ring-blue-200 dark:focus:ring-blue-800 shadow-sm transition-all duration-200 touch-manipulation"
                           autocomplete="off"
                           id="product-search-input"
                           onkeydown="handleProductSearchKeydown(event)"
                           wire:loading.class="border-blue-400"
                           wire:target="updatedCurrentSearch"
                           inputmode="search"
                           enterkeyhint="search"
                           @focus="if (window.innerWidth < 1024) fullscreenSearch = true"
                           x-ref="searchInput">

                    <!-- Spinner de carga -->
                    <div wire:loading wire:target="updatedCurrentSearch" class="absolute right-3 top-1/2 transform -translate-y-1/2">
                        <svg class="animate-spin h-5 w-5 text-blue-500" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>

                    <!-- Icono de búsqueda cuando no está cargando -->
                    <div wire:loading.remove wire:target="updatedCurrentSearch" class="absolute right-3 top-1/2 transform -translate-y-1/2 text-gray-400">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                </div>

                <!-- Interfaz de búsqueda fullscreen para móvil -->
                <div x-show="fullscreenSearch"
                     x-transition:enter="transition ease-out duration-300"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition ease-in duration-200"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     class="fixed inset-0 z-50 lg:hidden bg-white dark:bg-gray-900"
                     @click.self="fullscreenSearch = false">

                    <div class="h-full flex flex-col">
                        <!-- Header del fullscreen con input y botón cerrar -->
                        <div class="flex-shrink-0 p-4 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
                            <div class="flex items-center gap-3">
                                <!-- Botón cerrar -->
                                <button @click="fullscreenSearch = false; $wire.set('currentSearch', '')"
                                        class="flex-shrink-0 w-10 h-10 flex items-center justify-center text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>

                                <!-- Input de búsqueda fullscreen -->
                                <input type="text"
                                       wire:model.live.debounce.250ms="currentSearch"
                                       placeholder="Buscar productos..."
                                       class="flex-1 px-4 py-3 text-base bg-gray-100 dark:bg-gray-700 dark:text-white border-0 rounded-lg focus:outline-none focus:bg-white dark:focus:bg-gray-600 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-600 shadow-sm"
                                       autocomplete="off"
                                       x-ref="fullscreenInput"
                                       inputmode="search"
                                       enterkeyhint="search">
                            </div>

                            <!-- Info de búsqueda -->
                            <div class="mt-2 text-sm text-gray-500 dark:text-gray-400 text-center">
                                @if(strlen($currentSearch) >= 2 && count($searchResults) > 0)
                                    {{ count($searchResults) }} producto{{ count($searchResults) !== 1 ? 's' : '' }} encontrado{{ count($searchResults) !== 1 ? 's' : '' }}
                                @elseif(strlen($currentSearch) >= 2)
                                    No se encontraron productos
                                @else
                                    Escribe al menos 2 caracteres para buscar
                                @endif
                            </div>
                        </div>

                        <!-- Resultados fullscreen -->
                        <div class="flex-1 overflow-y-auto">
                            @if(strlen($currentSearch) >= 2 && count($searchResults) > 0)
                                <div class="p-4 space-y-3">
                                    @foreach($searchResults as $index => $product)
                                        @php
                                            $hasStock = $product['stock'] > 0;
                                            $canSelect = $hasStock || ($companyConfig && $companyConfig->canSellWithoutStock());
                                            $stockLevel = $product['stock_level'] ?? 'disponible';
                                        @endphp

                                        <div class="bg-white dark:bg-gray-800 rounded-lg p-4 shadow-sm border border-gray-200 dark:border-gray-700 transition-all duration-200
                                                    {{ $canSelect ? 'cursor-pointer hover:shadow-md hover:bg-gray-50 dark:hover:bg-gray-750' : 'cursor-not-allowed opacity-60' }}
                                                    {{ $stockLevel === 'agotado' && !$canSelect ? 'bg-red-50 dark:bg-red-900/20' : '' }}
                                                    {{ $stockLevel === 'bajo' ? 'bg-yellow-50 dark:bg-yellow-900/20' : '' }}"
                                             {{ $canSelect ? 'wire:click=selectProduct(' . $product['id'] . ')' : '' }}
                                             @click="if ($event.target.dataset.canSelect) fullscreenSearch = false"
                                             data-can-select="{{ $canSelect ? 'true' : 'false' }}">

                                            <div class="flex items-center gap-3">
                                                <!-- Imagen/Avatar -->
                                                <div class="flex-shrink-0">
                                                    @if($product['img_path'] && file_exists(storage_path('app/public/' . $product['img_path'])))
                                                        <img src="{{ asset('storage/' . $product['img_path']) }}"
                                                             alt="{{ $product['name'] }}"
                                                             class="w-14 h-14 rounded-lg object-cover border border-gray-200 dark:border-gray-600">
                                                    @else
                                                        <div class="w-14 h-14 rounded-lg {{ $product['avatar_color'] }} flex items-center justify-center text-white font-bold text-sm border border-gray-200 dark:border-gray-600">
                                                            {{ $product['initials'] }}
                                                        </div>
                                                    @endif
                                                </div>

                                                <!-- Info del producto -->
                                                <div class="flex-1 min-w-0">
                                                    <div class="font-semibold text-gray-900 dark:text-gray-100 text-base {{ $stockLevel === 'agotado' ? 'line-through' : '' }} truncate">
                                                        {{ $product['name'] }}
                                                    </div>
                                                    <div class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                                        SKU: {{ $product['sku'] ?? 'N/A' }} | Stock: {{ number_format($product['stock'], 0) }}
                                                    </div>
                                                    <div class="text-lg font-bold text-blue-600 dark:text-blue-400 mt-1">
                                                        ${{ number_format($product['price'], 0, '.', '.') }}
                                                    </div>
                                                </div>

                                                <!-- Badge de stock -->
                                                <div class="flex-shrink-0">
                                                    <span class="px-3 py-1 rounded-full text-xs font-medium
                                                        {{ $stockLevel === 'disponible' ? 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300' : '' }}
                                                        {{ $stockLevel === 'bajo' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300' : '' }}
                                                        {{ $stockLevel === 'agotado' ? 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300' : '' }}">
                                                        @if($stockLevel === 'disponible')
                                                            ✓ Disponible
                                                        @elseif($stockLevel === 'bajo')
                                                            ⚠ Stock Bajo
                                                        @else
                                                            ✗ Agotado
                                                        @endif
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @elseif(strlen($currentSearch) >= 2)
                                <!-- Sin resultados -->
                                <div class="flex flex-col items-center justify-center h-64 text-gray-500 dark:text-gray-400">
                                    <svg class="w-16 h-16 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                    <div class="text-lg font-medium">No se encontraron productos</div>
                                    <div class="text-sm">Intenta con otro término de búsqueda</div>
                                </div>
                            @else
                                <!-- Estado inicial -->
                                <div class="flex flex-col items-center justify-center h-64 text-gray-400 dark:text-gray-500">
                                    <svg class="w-16 h-16 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                    <div class="text-lg font-medium">Buscar productos</div>
                                    <div class="text-sm">Escribe al menos 2 caracteres</div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Dropdown de resultados mejorado con indicadores de stock (solo desktop) -->
                @if(strlen($currentSearch) >= 2 && count($searchResults) > 0)
                    <div id="productSearchResults"
                         class="search-results-dropdown z-50 w-full bg-white dark:bg-gray-800 border-2 border-blue-500 dark:border-blue-600 rounded-lg shadow-2xl max-h-60 sm:max-h-72 overflow-y-auto mt-1 hidden lg:block"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 transform scale-95"
                         x-transition:enter-end="opacity-100 transform scale-100">

                        <!-- Header con count -->
                        <div class="px-3 py-2 bg-blue-50 dark:bg-blue-900/20 border-b border-blue-200 dark:border-blue-700">
                            <span class="text-xs text-blue-700 dark:text-blue-300 font-medium">
                                {{ count($searchResults) }} producto{{ count($searchResults) !== 1 ? 's' : '' }} encontrado{{ count($searchResults) !== 1 ? 's' : '' }}
                            </span>
                        </div>

                        @foreach($searchResults as $index => $product)
                            @php
                                $hasStock = $product['stock'] > 0;
                                $canSelect = $hasStock || ($companyConfig && $companyConfig->canSellWithoutStock());
                                $isSelected = $selectedIndex === $index;
                                $stockLevel = $product['stock_level'] ?? 'disponible';
                            @endphp
                            <div class="product-result px-3 py-3 border-b border-gray-200 dark:border-gray-700 last:border-b-0 transition-all duration-150
                                        {{ $canSelect ? 'cursor-pointer' : 'cursor-not-allowed opacity-60' }}
                                        {{ $isSelected && $canSelect ? 'bg-blue-100 dark:bg-blue-900/50 ring-2 ring-blue-500 dark:ring-blue-400' : '' }}
                                        {{ $canSelect && !$isSelected ? 'hover:bg-blue-50 dark:hover:bg-gray-700' : '' }}
                                        {{ $stockLevel === 'agotado' && !$canSelect ? 'bg-red-50 dark:bg-red-900/20' : '' }}
                                        {{ $stockLevel === 'bajo' ? 'bg-yellow-50 dark:bg-yellow-900/20' : '' }}"
                                 data-product-id="{{ $product['id'] }}"
                                 data-index="{{ $index }}"
                                 data-has-stock="{{ $hasStock ? 'true' : 'false' }}"
                                 data-can-select="{{ $canSelect ? 'true' : 'false' }}"
                                 {{ $canSelect ? 'wire:click=selectProduct(' . $product['id'] . ')' : '' }}>

                                <!-- Imagen/Avatar y Nombre del producto -->
                                <div class="flex items-center justify-between mb-1">
                                    <div class="flex items-center gap-3 flex-1">
                                        <!-- Imagen del producto o avatar con iniciales -->
                                        <div class="flex-shrink-0">
                                            @if($product['img_path'] && file_exists(storage_path('app/public/' . $product['img_path'])))
                                                <!-- Imagen del producto -->
                                                <img src="{{ asset('storage/' . $product['img_path']) }}"
                                                     alt="{{ $product['name'] }}"
                                                     class="w-12 h-12 rounded-lg object-cover border-2 border-gray-200 dark:border-gray-600 shadow-lg hover:shadow-xl transition-shadow duration-200">
                                            @else
                                                <!-- Avatar con iniciales -->
                                                <div class="w-12 h-12 rounded-lg {{ $product['avatar_color'] }} flex items-center justify-center text-white font-bold text-sm border-2 border-gray-200 dark:border-gray-600 shadow-lg">
                                                    {{ $product['initials'] }}
                                                </div>
                                            @endif
                                        </div>

                                        <!-- Nombre del producto -->
                                        <div class="font-semibold text-gray-800 dark:text-gray-200 text-sm {{ $stockLevel === 'agotado' ? 'line-through' : '' }} flex-1">
                                            {{ $product['name'] }}
                                        </div>
                                    </div>
                                    <!-- Badge de estado de stock -->
                                    <span class="px-2 py-1 rounded-full text-xs font-medium
                                        {{ $stockLevel === 'disponible' ? 'bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300' : '' }}
                                        {{ $stockLevel === 'bajo' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/50 dark:text-yellow-300' : '' }}
                                        {{ $stockLevel === 'agotado' ? 'bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300' : '' }}">
                                        @if($stockLevel === 'disponible')
                                            ✓ Disponible
                                        @elseif($stockLevel === 'bajo')
                                            ⚠ Stock Bajo
                                        @else
                                            ✗ Agotado
                                        @endif
                                    </span>
                                </div>

                                <!-- Info del producto -->
                                <div class="flex justify-between items-center text-xs mb-2">
                                    <span class="text-gray-600 dark:text-gray-400 font-mono">SKU: {{ $product['sku'] ?? 'N/A' }}</span>
                                    <span class="{{ $product['stock_color'] ?? 'text-gray-600' }} font-medium">
                                        Stock: {{ number_format($product['stock'], 0) }}
                                    </span>
                                </div>

                                <!-- Precio -->
                                <div class="flex justify-between items-center">
                                    <div class="text-blue-600 dark:text-blue-400 font-bold text-sm">
                                        ${{ number_format($product['price'], 0, '.', '.') }}
                                    </div>
                                    @if($canSelect)
                                        <div class="text-xs text-gray-500 dark:text-gray-400 hidden lg:block">
                                            ↵ Enter para agregar
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach

                        <!-- Footer con navegación -->
                        <div class="px-3 py-2 bg-gray-50 dark:bg-gray-800 border-t border-gray-200 dark:border-gray-700">
                            <div class="text-xs text-gray-600 dark:text-gray-400 text-center">
                                <!-- Instrucciones para desktop -->
                                <span class="hidden lg:inline">Use ↑↓ para navegar • Enter para seleccionar • Esc para cerrar</span>
                                <!-- Instrucciones para móvil -->
                                <span class="lg:hidden">Toque un producto para agregarlo al carrito</span>
                            </div>
                        </div>
                    </div>

                @elseif(strlen($currentSearch) >= 2)
                    <!-- Estado sin resultados (solo desktop) -->
                    <div class="absolute z-50 w-full bg-white dark:bg-gray-800 border-2 border-yellow-500 dark:border-yellow-600 rounded-lg shadow-xl mt-1 p-4 hidden lg:block">
                        <div class="text-center text-gray-600 dark:text-gray-400">
                            <svg class="mx-auto h-8 w-8 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.172 16.172a4 4 0 015.656 0M9 12h6m-3-3v6m-9 1V5a2 2 0 012-2h.05A2 2 0 016 2h12a2 2 0 011.95 2.05H20a2 2 0 012 2v14a2 2 0 01-2 2H4a2 2 0 01-2-2z"></path>
                            </svg>
                            <p class="text-sm font-medium mb-1">No se encontraron productos</p>
                            <p class="text-xs">Intenta con otro término de búsqueda</p>
                        </div>
                    </div>

                @elseif(strlen($currentSearch) >= 1 && strlen($currentSearch) < 2)
                    <!-- Mensaje de mínimo caracteres (solo desktop) -->
                    <div class="absolute z-50 w-full bg-blue-50 dark:bg-blue-900/20 border border-blue-300 dark:border-blue-600 rounded-lg shadow-lg mt-1 p-3 hidden lg:block">
                        <div class="text-center text-blue-700 dark:text-blue-300 text-sm">
                            Escriba al menos 2 caracteres para buscar
                        </div>
                    </div>
                @endif
            </div>

        </div>
    </div>

    <!-- Contenido principal con layout flexible para móviles -->
    <div class="flex flex-col h-full lg:block lg:h-auto lg:p-3">
        <!-- Headers de la tabla (solo desktop) -->
        <div class="hidden lg:grid lg:grid-cols-7 gap-2 mb-3 lg:mb-4 text-sm lg:text-lg font-semibold text-gray-700 dark:text-gray-300 lg:py-2 lg:px-0 px-3">
            <div class="col-span-2">Producto</div>
            <div class="text-center">IVA</div>
            <div class="text-center">Precio Unit.</div>
            <div class="text-center">Cant.</div>
            <div class="text-right">Subtotal</div>
            <div class="text-center">Eliminar</div>
        </div>

        <!-- Contenedor de productos con scroll (móvil) / normal (desktop) -->
        <div class="flex-1 overflow-y-auto lg:overflow-visible p-3 lg:p-0" style="max-height: calc(100vh - 420px);">
            <!-- Productos en el carrito -->
            <div class="space-y-2 mb-3 lg:mb-0">
            @foreach($cartItems as $index => $item)
                <!-- Vista Desktop -->
                <div wire:key="cart-item-desktop-{{ $item['id'] }}" class="hidden lg:grid lg:grid-cols-7 gap-2 items-center text-sm lg:text-base bg-gray-50 dark:bg-gray-800 p-3 lg:p-4 rounded border border-gray-200 dark:border-gray-700">
                    <!-- Imagen/Avatar y Nombre del producto -->
                    <div class="col-span-2 flex items-center gap-3">
                        <!-- Imagen del producto o avatar con iniciales -->
                        <div class="flex-shrink-0">
                            @if(isset($item['img_path']) && $item['img_path'] && file_exists(storage_path('app/public/' . $item['img_path'])))
                                <!-- Imagen del producto -->
                                <img src="{{ asset('storage/' . $item['img_path']) }}"
                                     alt="{{ $item['name'] }}"
                                     class="w-12 h-12 lg:w-14 lg:h-14 rounded-lg object-cover border-2 border-gray-200 dark:border-gray-600 shadow-md">
                            @else
                                <!-- Avatar con iniciales -->
                                <div class="w-12 h-12 lg:w-14 lg:h-14 rounded-lg {{ $item['avatar_color'] ?? 'bg-gradient-to-br from-blue-500 to-indigo-600' }} flex items-center justify-center text-white font-bold text-sm lg:text-base border-2 border-gray-200 dark:border-gray-600 shadow-md">
                                    {{ $item['initials'] ?? substr($item['name'], 0, 2) }}
                                </div>
                            @endif
                        </div>

                        <!-- Información del producto -->
                        <div class="flex-1 min-w-0">
                            <div class="text-sm lg:text-base font-medium text-gray-800 dark:text-gray-200 truncate">
                                {{ $item['name'] }}
                            </div>
                            <div class="text-xs lg:text-sm text-gray-500 dark:text-gray-400">
                                SKU: {{ $item['sku'] ?? 'N/A' }}
                                @if(isset($item['stock']))
                                    <span class="ml-3 text-blue-600 dark:text-blue-400 font-medium">Stock: {{ number_format($item['stock'], 0) }}</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- IVA -->
                    <div class="text-center text-xs lg:text-sm text-gray-600 dark:text-gray-400">
                        {{ $item['tax_name'] ?? 'N/A' }}
                    </div>

                    <!-- Precio Unitario -->
                    <div class="text-center">
                        @if($companyConfig && $companyConfig->allowsPriceChange())
                            <!-- Precio editable -->
                            <input type="number"
                                   value="{{ number_format($item['price'], 0, '.', '') }}"
                                   wire:change="updatePrice({{ $item['id'] }}, $event.target.value)"
                                   class="w-32 lg:w-36 text-center bg-white dark:bg-gray-700 dark:text-white border border-gray-300 dark:border-gray-600 rounded px-3 py-2 text-sm lg:text-base font-semibold focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-600"
                                   min="0"
                                   step="1"
                                   title="Precio editable">
                        @else
                            <!-- Precio solo lectura -->
                            <div class="w-32 lg:w-36 mx-auto text-center bg-gray-100 dark:bg-gray-600 dark:text-white border border-gray-300 dark:border-gray-600 rounded px-3 py-2 text-sm lg:text-base font-semibold"
                                 title="Precio fijo - No editable">
                                ${{ number_format($item['price'], 0, '.', '.') }}
                            </div>
                        @endif
                    </div>

                    <!-- Cantidad -->
                    <div class="text-center">
                        <input type="number"
                               wire:model.live.debounce.500ms="cartItems.{{ $index }}.quantity"
                               class="w-24 lg:w-28 text-center bg-white dark:bg-gray-700 dark:text-white border border-gray-300 dark:border-gray-600 rounded px-3 py-2 text-sm lg:text-base font-semibold focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-600"
                               min="1"
                               step="1"
                               pattern="\d*">
                    </div>

                    <!-- Subtotal -->
                    <div class="text-right font-semibold text-gray-800 dark:text-gray-200 text-sm lg:text-base px-2">
                        ${{ number_format($item['subtotal'], 0, '.', '.') }}
                    </div>

                    <!-- Botón Eliminar -->
                    <div class="text-center">
                        <button wire:click="removeFromCart({{ $item['id'] }})"
                                class="bg-red-500 hover:bg-red-600 dark:bg-red-600 dark:hover:bg-red-700 text-white rounded-lg p-2 lg:p-3 transition-colors duration-150 inline-flex items-center justify-center"
                                title="Eliminar producto">
                                <svg class="w-4 h-4 lg:w-5 lg:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                        </button>
                    </div>
                </div>
                
                <!-- Vista Móvil - Nombre completo arriba, controles abajo -->
                <div wire:key="cart-item-mobile-{{ $item['id'] }}" class="lg:hidden">
                    <div class="bg-gray-50 dark:bg-gray-800 p-3 rounded border border-gray-200 dark:border-gray-700 space-y-3">
                        <!-- Fila 1: Imagen/Avatar + Información del producto -->
                        <div class="flex items-center gap-2 w-full">
                            <!-- Imagen del producto o avatar con iniciales -->
                            <div class="flex-shrink-0">
                                @if(isset($item['img_path']) && $item['img_path'] && file_exists(storage_path('app/public/' . $item['img_path'])))
                                    <!-- Imagen del producto -->
                                    <img src="{{ asset('storage/' . $item['img_path']) }}"
                                         alt="{{ $item['name'] }}"
                                         class="w-12 h-12 rounded-lg object-cover border-2 border-gray-200 dark:border-gray-600 shadow-md">
                                @else
                                    <!-- Avatar con iniciales -->
                                    <div class="w-12 h-12 rounded-lg {{ $item['avatar_color'] ?? 'bg-gradient-to-br from-blue-500 to-indigo-600' }} flex items-center justify-center text-white font-bold text-sm border-2 border-gray-200 dark:border-gray-600 shadow-md">
                                        {{ $item['initials'] ?? substr($item['name'], 0, 2) }}
                                    </div>
                                @endif
                            </div>

                            <!-- Información del producto -->
                            <div class="flex-1 min-w-0">
                                <div class="font-medium text-gray-800 dark:text-gray-200 text-sm truncate">
                                    {{ $item['name'] }}
                                </div>
                                <div class="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5">
                                    SKU: {{ $item['sku'] ?? 'N/A' }}
                                    @if(isset($item['stock']))
                                        <span class="ml-2">| Stock: {{ number_format($item['stock'], 0) }}</span>
                                    @endif
                                    <span class="ml-2 font-semibold text-blue-600 dark:text-blue-400">| IVA: {{ $item['tax_name'] ?? 'N/A' }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Fila 2: Controles optimizados - más espacio para inputs, menos para eliminar -->
                        <div class="grid grid-cols-12 gap-2 w-full">
                            <!-- Precio (30% del espacio) -->
                            <div class="col-span-4">
                                <div class="text-[10px] text-gray-500 dark:text-gray-400 mb-1 text-center">Precio</div>
                                @if($companyConfig && $companyConfig->allowsPriceChange())
                                    <!-- Precio editable -->
                                    <input type="number"
                                           value="{{ number_format($item['price'], 0, '.', '') }}"
                                           wire:change="updatePrice({{ $item['id'] }}, $event.target.value)"
                                           class="w-full text-center bg-white dark:bg-gray-700 dark:text-white border border-gray-300 dark:border-gray-600 rounded px-1 py-2 text-xs font-semibold"
                                           min="0"
                                           step="1"
                                           title="Precio editable">
                                @else
                                    <!-- Precio solo lectura -->
                                    <div class="w-full text-center bg-gray-100 dark:bg-gray-600 dark:text-white border border-gray-300 dark:border-gray-600 rounded px-1 py-2 text-xs font-semibold"
                                         title="Precio fijo - No editable">
                                        ${{ number_format($item['price'], 0, '.', '.') }}
                                    </div>
                                @endif
                            </div>

                            <!-- Cantidad (20% del espacio) -->
                            <div class="col-span-2">
                                <div class="text-[10px] text-gray-500 dark:text-gray-400 mb-1 text-center">Cant.</div>
                                <input type="number"
                                       wire:model.live.debounce.500ms="cartItems.{{ $index }}.quantity"
                                       class="w-full text-center bg-white dark:bg-gray-700 dark:text-white border border-gray-300 dark:border-gray-600 rounded px-1 py-2 text-xs font-semibold"
                                       min="1"
                                       step="1"
                                       pattern="\d*">
                            </div>

                            <!-- Subtotal (35% del espacio) -->
                            <div class="col-span-4">
                                <div class="text-[10px] text-gray-500 dark:text-gray-400 mb-1 text-center">Subtotal</div>
                                <div class="text-center font-bold text-gray-800 dark:text-gray-200 text-xs bg-gray-100 dark:bg-gray-700 rounded px-1 py-2">
                                    ${{ number_format($item['subtotal'], 0, '.', '.') }}
                                </div>
                            </div>

                            <!-- Botón Eliminar (15% del espacio - más compacto) -->
                            <div class="col-span-2">
                                <div class="text-[8px] text-gray-500 dark:text-gray-400 mb-1 text-center">Eliminar</div>
                                <button wire:click="removeFromCart({{ $item['id'] }})"
                                        class="w-full bg-red-500 hover:bg-red-600 dark:bg-red-600 dark:hover:bg-red-700 text-white rounded px-1 py-1 transition-colors duration-150 flex items-center justify-center h-8"
                                        title="Eliminar producto">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
            </div>
        </div>

        <!-- Footer fijo en móviles / normal en desktop -->
@if(count($cartItems) > 0)
<div class="lg:border-t lg:border-gray-200 lg:dark:border-gray-700 lg:pt-3 lg:mb-3
            fixed lg:relative bottom-0 left-0 right-0 lg:bottom-auto lg:left-auto lg:right-auto
            bg-white dark:bg-gray-900 lg:bg-transparent lg:dark:bg-transparent
            border-t border-gray-200 dark:border-gray-700 lg:border-t-0
            p-3 lg:p-0 z-10">

    <!-- Total centrado en móviles -->
    <div class="text-center mb-3 lg:hidden">
        <div class="text-2xl font-bold text-gray-800 dark:text-gray-200">
                    ${{ number_format($total, 0, '.', '.') }}
                </div>
                <div class="text-xs text-gray-600 dark:text-gray-400">
                    {{ count($cartItems) }} producto(s)
                    @if($selectedCustomer)
                    | {{ $selectedCustomer['display_name'] }}
                    @endif
                </div>
    </div>

    <!-- Botones horizontales - MODIFICADO PARA ESCRITORIO -->
    <div class="flex gap-2 justify-center lg:flex-row lg:justify-center">
        <button
            wire:click="saveQuote"
            wire:loading.attr="disabled"
            wire:target="saveQuote"
            class="flex-1 lg:flex-none px-4 lg:px-6 py-3 bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 text-white rounded-lg font-semibold text-sm transition-colors duration-150 shadow-md hover:shadow-lg flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
        >
            <!-- Icono normal (cuando no está cargando) -->
            <svg wire:loading.remove wire:target="saveQuote" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>

            <!-- Spinner (cuando está cargando) -->
            <svg wire:loading wire:target="saveQuote" class="w-5 h-5 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>

            <!-- Texto normal -->
            <span wire:loading.remove wire:target="saveQuote">{{ $this->saveButtonText }}</span>

            <!-- Texto cuando está cargando -->
            <span wire:loading wire:target="saveQuote">{{ $this->saveButtonLoadingText }}</span>
        </button>

        <button
            wire:click="clearCart"
            class="flex-1 lg:flex-none px-4 lg:px-6 py-3 bg-red-500 hover:bg-red-600 dark:bg-red-600 dark:hover:bg-red-700 text-white rounded-lg font-semibold text-sm transition-colors duration-150 shadow-md hover:shadow-lg flex items-center justify-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
            </svg>
            Limpiar todo
        </button>
    </div>

    <!-- Información adicional (solo desktop) -->
    <div class="mt-2 text-center hidden lg:block">
        <p class="text-xs text-gray-600 dark:text-gray-400">
            Total: <span class="font-bold text-gray-800 dark:text-gray-200">${{ number_format($total, 0, '.', '.') }}</span>
            | Productos: <span class="font-bold">{{ count($cartItems) }}</span>
            @if($selectedCustomer)
            | Cliente: <span class="font-bold text-green-600 dark:text-green-400">{{ $selectedCustomer['display_name'] }}</span>
            @endif
        </p>
    </div>
</div>
@endif
    </div>

    <!-- Input de búsqueda dinámico -->
        <div class="space-y-1">
            <!-- Siempre mostrar un input activo para nueva búsqueda -->
           

        </div>

    </div>
</div>

<!-- Modal para crear nuevo cliente -->
@if($showCustomerModal)
<div class="fixed inset-0 bg-gray-600 dark:bg-gray-900 bg-opacity-50 dark:bg-opacity-75 overflow-y-auto h-full w-full z-50"
     x-data="{ show: true }" x-show="show" x-transition:enter="ease-out duration-300"
     x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
     x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">
    <div class="relative min-h-screen flex items-center justify-center p-4">
        <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">

            <div class="border-b border-gray-200 dark:border-gray-700 px-6 py-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Crear Nuevo Cliente
                    </h3>
                    <button wire:click="closeCustomerModal"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>

            <div class="p-6">
                @livewire('TAT.Customers.tat-customers-manager', [
                    'preFilledIdentification' => $searchedIdentification,
                    'isModalMode' => true
                ], key('customer-modal'))
            </div>
        </div>
    </div>
</div>
@endif


@push('scripts')
<style>
    /* Optimización móvil para evitar interferencia del teclado */
    @media (max-width: 640px) {
        /* Hacer que los resultados de búsqueda se posicionen de manera fija en móviles */
        .search-results-dropdown {
            position: fixed !important;
            top: auto !important;
            bottom: 30vh !important; /* 30% desde abajo para evitar el teclado */
            left: 0.5rem !important;
            right: 0.5rem !important;
            width: auto !important;
            max-width: calc(100vw - 1rem) !important;
            max-height: 40vh !important; /* Límite de altura en móviles */
            z-index: 9999 !important;
        }

        /* Evitar que el viewport se redimensione con el teclado */
        .quoter-main-container {
            height: 100vh;
            overflow-y: auto;
            padding-bottom: 140px; /* Espacio para el footer fijo */
        }

        /* Ajustar altura cuando no hay header (página de quoter) */
        @media (max-width: 1023px) {
            .quoter-main-container {
                /* En móvil sin header, usar toda la pantalla */
                height: 100vh;
                min-height: 100vh;
            }
        }

        /* Footer fijo en móviles */
        .mobile-footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            background: white;
            border-top: 1px solid #e5e7eb;
            z-index: 10;
            box-shadow: 0 -4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        /* Dark mode para footer */
        .dark .mobile-footer {
            background: rgb(17 24 39);
            border-top-color: rgb(55 65 81);
        }

        /* Optimización de inputs táctiles */
        input[type="text"], input[type="search"], textarea, select {
            font-size: 16px !important; /* Previene zoom en iOS */
            touch-action: manipulation;
            -webkit-appearance: none;
            appearance: none;
        }

        /* Mejorar el área de touch para botones pequeños */
        button {
            touch-action: manipulation;
            min-height: 44px;
            min-width: 44px;
        }
    }

    /* Para desktop mantener comportamiento normal */
    @media (min-width: 640px) {
        .search-results-dropdown {
            position: absolute !important;
            top: 100% !important;
            bottom: auto !important;
            left: 0 !important;
            right: 0 !important;
            width: 100% !important;
            max-width: none !important;
        }
    }

    /* Animaciones suaves para transiciones */
    .search-results-dropdown {
        transition: all 0.2s ease-in-out;
        -webkit-overflow-scrolling: touch;
        overscroll-behavior: contain;
    }

    @media (max-width: 1023px) {
        .mobile-full-width {
            width: 100vw !important;
            margin-left: calc(-50vw + 50%) !important;
            margin-right: calc(-50vw + 50%) !important;
            max-width: none !important;
            border-radius: 0 !important;
            box-shadow: none !important;
            border: none !important;
            margin-top: 0 !important;
        }

        /* Forzar ancho completo en cualquier contenedor padre */
        .mobile-full-width {
            position: relative !important;
            left: 50% !important;
            transform: translateX(-50%) !important;
        }

        /* Asegurar que el contenedor padre no limite el ancho */
        body, main, [data-livewire-component] {
            overflow-x: visible !important;
        }
    }

    /* Estilos simplificados para toasts */
    @media (max-width: 767px) {
        .swal2-popup.swal2-toast {
            font-size: 14px !important;
            padding: 0.75rem !important;
            border-radius: 0.5rem !important;
            max-width: calc(100vw - 2rem) !important;
            margin-top: 1rem !important;
        }

        .swal2-toast .swal2-icon {
            width: 1.2rem !important;
            height: 1.2rem !important;
            margin-right: 0.5rem !important;
        }
    }

    /* Estilos para búsqueda fullscreen en móvil */
    @media (max-width: 1023px) {
        /* Asegurar que el fullscreen se vea correctamente */
        .fullscreen-search {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            z-index: 9999 !important;
            background: white;
        }

        /* Prevenir zoom en inputs en iOS */
        input[type="text"], input[type="search"] {
            font-size: 16px !important;
            touch-action: manipulation;
        }

        /* Optimizar scroll en la lista de resultados */
        .fullscreen-results {
            -webkit-overflow-scrolling: touch;
            overscroll-behavior: contain;
        }

        /* Mejorar area de toque para productos */
        .product-item-touch {
            min-height: 72px;
            padding: 16px;
            touch-action: manipulation;
        }
    }

    /* Asegurar que el fullscreen tenga máxima prioridad */
    .search-fullscreen {
        position: fixed !important;
        inset: 0 !important;
        z-index: 9999 !important;
        background: white !important;
    }

    /* Dark mode para fullscreen */
    .dark .search-fullscreen {
        background: rgb(17 24 39) !important;
    }
</style>
<script>
    // Forzar ancho completo en móviles
    function forceFullWidth() {
        if (window.innerWidth < 1024) {
            const element = document.querySelector('.mobile-full-width');
            if (element) {
                element.style.width = '100vw';
                element.style.maxWidth = 'none';
                element.style.position = 'relative';
                element.style.left = '50%';
                element.style.transform = 'translateX(-50%)';
                element.style.marginLeft = '0';
                element.style.marginRight = '0';
                element.style.borderRadius = '0';
                element.style.boxShadow = 'none';
                element.style.border = 'none';
                element.style.marginTop = '0';
            }
        }
    }

    // Aplicar en carga inicial y cambio de tamaño
    document.addEventListener('DOMContentLoaded', forceFullWidth);
    window.addEventListener('resize', forceFullWidth);
    document.addEventListener('livewire:navigated', forceFullWidth);

    // Mejorar la funcionalidad del input de búsqueda
    document.addEventListener('livewire:init', () => {
        // Forzar ancho completo después de que Livewire esté listo
        setTimeout(forceFullWidth, 100);

        // Listener para el botón hamburguesa móvil
        document.addEventListener('toggle-mobile-menu', () => {
            // Buscar el elemento body que tiene Alpine.js
            const body = document.body;
            if (body._x_dataStack && body._x_dataStack[0]) {
                // Activar el sidebar móvil usando la variable de Alpine.js
                body._x_dataStack[0].sidebarOpen = true;
            } else {
                // Fallback: disparar click en el botón hamburguesa del header si existe
                const headerMenuButton = document.querySelector('button[\\@click="sidebarOpen = true"]');
                if (headerMenuButton) {
                    headerMenuButton.click();
                }
            }
        });
        // Listener para alertas de SweetAlert2
        Livewire.on('swal:warning', (data) => {
            // data es un array con los argumentos pasados desde el componente
            const alertData = Array.isArray(data) ? data[0] : data;
            
            if (typeof Swal !== 'undefined') {
                const isMobile = window.innerWidth < 768; // Detectar móvil

                Swal.fire({
                    icon: 'warning',
                    title: alertData.title || 'Advertencia',
                    text: alertData.text || '',
                    // Configuración condicional
                    toast: !isMobile,
                    position: isMobile ? 'center' : 'top-end',
                    showConfirmButton: isMobile, // En móvil mostrar botón para cerrar
                    timer: isMobile ? null : 3000, // En móvil esperar confirmación, en PC timer
                    confirmButtonColor: '#3085d6',
                    confirmButtonText: 'Entendido'
                });
            } else {
                alert(alertData.text);
            }
        });

        // Manejar búsqueda fullscreen en móviles
        document.addEventListener('alpine:init', () => {
            // Escuchar cuando se abre la búsqueda fullscreen
            document.addEventListener('alpine:init', () => {
                // Observar cambios en fullscreenSearch
                setTimeout(() => {
                    const observer = new MutationObserver(() => {
                        const fullscreenElement = document.querySelector('[x-show="fullscreenSearch"]');
                        if (fullscreenElement && !fullscreenElement.style.display.includes('none')) {
                            // Enfocar el input fullscreen
                            setTimeout(() => {
                                const fullscreenInput = document.querySelector('[x-ref="fullscreenInput"]');
                                if (fullscreenInput) {
                                    fullscreenInput.focus();
                                    // Prevenir zoom en iOS
                                    fullscreenInput.setAttribute('readonly', 'readonly');
                                    setTimeout(() => fullscreenInput.removeAttribute('readonly'), 100);
                                }
                            }, 100);
                        }
                    });

                    observer.observe(document.body, { childList: true, subtree: true });
                }, 500);
            });
        });

        // Test básico de SweetAlert al cargar
        setTimeout(() => {
            if (typeof Swal !== 'undefined') {
                console.log('SweetAlert2 disponible y funcionando');

                // Test simple de toast
                window.testToast = function() {
                    Swal.fire({
                        toast: true,
                        position: 'top',
                        showConfirmButton: false,
                        timer: 2000,
                        timerProgressBar: true,
                        icon: 'success',
                        title: 'Toast de prueba - debería desaparecer en 2 segundos'
                    });
                }
            } else {
                console.error('SweetAlert2 no está disponible');
            }
        }, 1000);

        // Listener para toasts no invasivos - Configuración simplificada
        Livewire.on('swal:toast', (data) => {
            const toastData = Array.isArray(data) ? data[0] : data;

            if (typeof Swal !== 'undefined') {
                const isMobile = window.innerWidth < 768;

                // Configuración ultra-simple para garantizar funcionamiento
                const config = {
                    toast: true,
                    position: isMobile ? 'top' : 'top-end',
                    showConfirmButton: false,
                    timer: isMobile ? 1500 : 2500,
                    timerProgressBar: true,
                    title: toastData.message,
                    background: isMobile ? 'rgba(0, 0, 0, 0.85)' : undefined,
                    color: isMobile ? 'white' : undefined
                };

                // Agregar ícono según tipo
                if (toastData.type === 'success') {
                    config.icon = 'success';
                } else if (toastData.type === 'error') {
                    config.icon = 'error';
                } else if (toastData.type === 'info') {
                    config.icon = 'info';
                } else {
                    config.icon = 'success';
                }

                // Disparar el toast
                Swal.fire(config);

                // Debug
                console.log('Toast disparado con timer:', config.timer + 'ms');
            } else {
                console.log(`${toastData.type.toUpperCase()}: ${toastData.message}`);
            }
        });

        // Listener para validar caja abierta
        Livewire.on('swal:no-petty-cash', () => {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    icon: 'error',
                    title: 'Caja Cerrada',
                    html: 'No hay una caja abierta.<br>Debe aperturar una caja antes de registrar ventas.',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#6b7280',
                    confirmButtonText: 'Ir a Aperturar Caja',
                    cancelButtonText: 'Cancelar',
                    allowOutsideClick: false
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Redirigir a la página de apertura de caja
                        window.location.href = '{{ route("petty-cash.petty-cash") }}';
                    }
                });
            } else {
                if (confirm('No hay una caja abierta. Debe aperturar una caja antes de registrar ventas.\n\n¿Desea ir a aperturar una caja ahora?')) {
                    window.location.href = '{{ route("petty-cash.petty-cash") }}';
                }
            }
        });

        const searchInput = document.getElementById('product-search-input');

        if (searchInput) {
            // Mantener el foco en el input después de seleccionar un producto
            Livewire.on('product-selected', () => {
                // Solo enfocar si NO estamos buscando cliente
                const customerSearchInput = document.querySelector('input[wire\\:model\\.live\\.debounce\\.300ms="customerSearch"]');
                const isCustomerSearchActive = customerSearchInput && document.activeElement === customerSearchInput;

                if (!isCustomerSearchActive) {
                    setTimeout(() => {
                        searchInput.focus();
                    }, 100);
                }
            });

            // Prevenir que el input pierda el foco cuando se hace clic en el dropdown
            searchInput.addEventListener('blur', (e) => {
                // Verificar si el usuario está buscando cliente
                const customerSearchInput = document.querySelector('input[wire\\:model\\.live\\.debounce\\.300ms="customerSearch"]');
                const isCustomerSearchActive = customerSearchInput && (
                    document.activeElement === customerSearchInput ||
                    e.relatedTarget === customerSearchInput ||
                    customerSearchInput.matches(':focus-within')
                );

                // Verificar si el usuario está editando campos del carrito
                const isEditingCartItem = e.relatedTarget && (
                    e.relatedTarget.type === 'number' || // Inputs de cantidad y precio
                    e.relatedTarget.closest('.space-y-2') || // Área del carrito
                    e.relatedTarget.hasAttribute('wire:change') // Cualquier input de Livewire
                );

                // Solo mantener foco si NO está buscando cliente Y NO está editando items del carrito
                if (!isCustomerSearchActive && !isEditingCartItem && (!e.relatedTarget || !e.relatedTarget.closest('.relative'))) {
                    setTimeout(() => {
                        // Verificar una vez más antes de enfocar
                        const stillSearchingCustomer = document.querySelector('input[wire\\:model\\.live\\.debounce\\.300ms="customerSearch"]:focus');
                        if (!stillSearchingCustomer) {
                            searchInput.focus();
                        }
                    }, 50);
                }
            });
        }
    });

    // Variables para navegación por teclado en búsqueda de clientes
    let selectedCustomerIndex = -1;
    let customerResults = [];

    // Función para manejar navegación por teclado en búsqueda de clientes
    function handleCustomerSearchKeydown(event) {
        const resultsContainer = document.getElementById('customerSearchResults');

        if (!resultsContainer) return;

        customerResults = Array.from(resultsContainer.querySelectorAll('.customer-result'));

        if (customerResults.length === 0) return;

        switch(event.key) {
            case 'ArrowDown':
                event.preventDefault();
                selectedCustomerIndex = Math.min(selectedCustomerIndex + 1, customerResults.length - 1);
                updateCustomerSelection();
                break;

            case 'ArrowUp':
                event.preventDefault();
                selectedCustomerIndex = Math.max(selectedCustomerIndex - 1, -1);
                updateCustomerSelection();
                break;

            case 'Enter':
                event.preventDefault();
                if (selectedCustomerIndex >= 0 && customerResults[selectedCustomerIndex]) {
                    const customerId = customerResults[selectedCustomerIndex].dataset.customerId;
                    // Disparar el evento de Livewire para seleccionar cliente
                    Livewire.find('{{ $this->getId() }}').call('selectCustomer', customerId);
                }
                break;

            case 'Escape':
                event.preventDefault();
                selectedCustomerIndex = -1;
                updateCustomerSelection();
                break;
        }
    }

    // Función para actualizar la selección visual
    function updateCustomerSelection() {
        customerResults.forEach((result, index) => {
            result.classList.remove('bg-green-100', 'border-green-500');

            if (index === selectedCustomerIndex) {
                result.classList.add('bg-green-100', 'border-green-500');
                // Scroll hacia el elemento seleccionado si está fuera de vista
                result.scrollIntoView({
                    block: 'nearest',
                    behavior: 'smooth'
                });
            }
        });
    }

    // Reset de selección cuando cambian los resultados
    document.addEventListener('livewire:updated', () => {
        selectedCustomerIndex = -1;
        customerResults = [];
        selectedProductIndex = -1;
        productResults = [];
    });

    // Variables para navegación por teclado en búsqueda de productos
    let selectedProductIndex = -1;
    let productResults = [];
    let searchTimeout = null;

    // Función mejorada para manejar navegación por teclado en búsqueda de productos
    function handleProductSearchKeydown(event) {
        const resultsContainer = document.getElementById('productSearchResults');

        if (!resultsContainer) {
            // Si no hay dropdown pero presiona Enter, hacer búsqueda directa
            if (event.key === 'Enter' && event.target.value.trim().length >= 2) {
                event.preventDefault();
                const searchTerm = event.target.value.trim();
                Livewire.find('{{ $this->getId() }}').call('quickSearch', searchTerm);
            }
            return;
        }

        productResults = Array.from(resultsContainer.querySelectorAll('.product-result'));

        if (productResults.length === 0) return;

        // Filtrar solo productos seleccionables para navegación
        const availableProducts = productResults.filter(result =>
            result.dataset.canSelect === 'true'
        );

        if (availableProducts.length === 0) return;

        switch(event.key) {
            case 'ArrowDown':
                event.preventDefault();
                // Auto-seleccionar el primer elemento si no hay selección
                if (selectedProductIndex === -1) {
                    selectedProductIndex = productResults.findIndex(result =>
                        result.dataset.canSelect === 'true'
                    );
                } else {
                    // Encontrar el siguiente producto disponible
                    let nextIndex = -1;
                    for (let i = selectedProductIndex + 1; i < productResults.length; i++) {
                        if (productResults[i].dataset.canSelect === 'true') {
                            nextIndex = i;
                            break;
                        }
                    }
                    if (nextIndex === -1) {
                        // Si llegamos al final, ir al primer producto disponible
                        nextIndex = productResults.findIndex(result => result.dataset.canSelect === 'true');
                    }
                    selectedProductIndex = nextIndex;
                }
                updateProductSelection();
                break;

            case 'ArrowUp':
                event.preventDefault();
                if (selectedProductIndex === -1) {
                    // Si no hay selección, ir al último elemento
                    for (let i = productResults.length - 1; i >= 0; i--) {
                        if (productResults[i].dataset.canSelect === 'true') {
                            selectedProductIndex = i;
                            break;
                        }
                    }
                } else {
                    // Encontrar el producto anterior disponible
                    let prevIndex = -1;
                    for (let i = selectedProductIndex - 1; i >= 0; i--) {
                        if (productResults[i].dataset.canSelect === 'true') {
                            prevIndex = i;
                            break;
                        }
                    }
                    if (prevIndex === -1) {
                        // Si llegamos al inicio, ir al último producto disponible
                        for (let i = productResults.length - 1; i >= 0; i--) {
                            if (productResults[i].dataset.canSelect === 'true') {
                                prevIndex = i;
                                break;
                            }
                        }
                    }
                    selectedProductIndex = prevIndex;
                }
                updateProductSelection();
                break;

            case 'Enter':
                event.preventDefault();
                if (selectedProductIndex >= 0 && productResults[selectedProductIndex] &&
                    productResults[selectedProductIndex].dataset.canSelect === 'true') {
                    const productId = productResults[selectedProductIndex].dataset.productId;
                    // Mostrar feedback visual
                    productResults[selectedProductIndex].classList.add('bg-green-100', 'dark:bg-green-900/50');

                    // Disparar el evento de Livewire para seleccionar producto
                    Livewire.find('{{ $this->getId() }}').call('selectProduct', productId);
                } else if (availableProducts.length === 1) {
                    // Si solo hay un resultado disponible, seleccionarlo automáticamente
                    const productId = availableProducts[0].dataset.productId;
                    availableProducts[0].classList.add('bg-green-100', 'dark:bg-green-900/50');
                    Livewire.find('{{ $this->getId() }}').call('selectProduct', productId);
                }
                break;

            case 'Escape':
                event.preventDefault();
                selectedProductIndex = -1;
                updateProductSelection();
                // Limpiar búsqueda y enfocar input
                Livewire.find('{{ $this->getId() }}').call('clearSearch');
                setTimeout(() => {
                    const input = document.getElementById('product-search-input');
                    if (input) input.focus();
                }, 100);
                break;

            case 'Tab':
                // Permitir navegación con Tab pero cerrar dropdown
                selectedProductIndex = -1;
                Livewire.find('{{ $this->getId() }}').call('clearSearch');
                break;
        }
    }

    // Función mejorada para actualizar la selección visual de productos
    function updateProductSelection() {
        productResults.forEach((result, index) => {
            result.classList.remove('bg-blue-100', 'dark:bg-blue-900/50', 'ring-2', 'ring-blue-500', 'dark:ring-blue-400');

            if (index === selectedProductIndex && result.dataset.canSelect === 'true') {
                result.classList.add('bg-blue-100', 'dark:bg-blue-900/50', 'ring-2', 'ring-blue-500', 'dark:ring-blue-400');

                // Scroll suave hacia el elemento seleccionado
                const container = result.closest('#productSearchResults');
                if (container) {
                    const containerTop = container.scrollTop;
                    const containerBottom = containerTop + container.clientHeight;
                    const elementTop = result.offsetTop;
                    const elementBottom = elementTop + result.offsetHeight;

                    if (elementTop < containerTop) {
                        container.scrollTop = elementTop;
                    } else if (elementBottom > containerBottom) {
                        container.scrollTop = elementBottom - container.clientHeight;
                    }
                }
            }
        });
    }

    // Funciones adicionales para mejorar la UX
    function resetProductSelection() {
        selectedProductIndex = -1;
        productResults = [];
    }

    // Auto-focus en el input después de seleccionar producto
    document.addEventListener('livewire:updated', () => {
        resetProductSelection();

        // Si no hay resultados y el input tiene foco, mantenerlo
        const input = document.getElementById('product-search-input');
        const resultsContainer = document.getElementById('productSearchResults');

        if (input && !resultsContainer && document.activeElement === input) {
            // El input ya está enfocado, no hacer nada
        } else if (input && !resultsContainer) {
            // Auto-focus después de limpiar resultados
            setTimeout(() => input.focus(), 100);
        }
    });

    // Funciones para mejorar experiencia móvil
    function isMobile() {
        return window.innerWidth <= 640;
    }

    // Prevenir zoom automático en inputs en iOS
    function preventZoom() {
        const inputs = document.querySelectorAll('input[type="text"], input[type="search"], textarea, select');
        inputs.forEach(input => {
            input.addEventListener('focus', () => {
                if (isMobile()) {
                    // Prevenir zoom en móviles ajustando el viewport temporalmente
                    const viewport = document.querySelector('meta[name="viewport"]');
                    const originalContent = viewport.getAttribute('content');
                    viewport.setAttribute('content', originalContent + ', maximum-scale=1.0');

                    setTimeout(() => {
                        viewport.setAttribute('content', originalContent);
                    }, 100);
                }
            });
        });
    }

    // Manejar el redimensionamiento de la ventana cuando aparece/desaparece el teclado
    function handleKeyboardResize() {
        if (!isMobile()) return;

        let initialViewportHeight = window.visualViewport ? window.visualViewport.height : window.innerHeight;

        function onViewportChange() {
            const currentHeight = window.visualViewport ? window.visualViewport.height : window.innerHeight;
            const heightDifference = initialViewportHeight - currentHeight;

            // Si la diferencia es significativa, probablemente apareció el teclado
            if (heightDifference > 150) {
                document.body.classList.add('keyboard-visible');
                // Ajustar la posición de los resultados de búsqueda si están visibles
                const searchResults = document.getElementById('productSearchResults');
                if (searchResults && searchResults.classList.contains('search-results-dropdown')) {
                    searchResults.style.bottom = `${heightDifference + 50}px`;
                }
            } else {
                document.body.classList.remove('keyboard-visible');
                const searchResults = document.getElementById('productSearchResults');
                if (searchResults && searchResults.classList.contains('search-results-dropdown')) {
                    searchResults.style.bottom = '30vh';
                }
            }
        }

        if (window.visualViewport) {
            window.visualViewport.addEventListener('resize', onViewportChange);
        } else {
            window.addEventListener('resize', onViewportChange);
        }
    }

    // Inicializar mejoras móviles cuando el DOM esté listo
    document.addEventListener('DOMContentLoaded', () => {
        preventZoom();
        handleKeyboardResize();
    });
</script>
@endpush
</div>