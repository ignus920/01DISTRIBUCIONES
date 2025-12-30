{{-- Establecer el header --}}
@php
$header = 'Seleccionar productos';
@endphp

<div class="fixed inset-0 z-[35] bg-gray-50 dark:bg-gray-900 flex flex-col overflow-hidden">
    <!-- Header fijo con búsqueda y categorías -->
    <div class="flex-shrink-0 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shadow-md">
        <div class="px-4 py-3">
            <!-- Barra superior con botón regresar y menú -->
            <div class="flex items-center justify-between mb-3">
                @if(auth()->check() && auth()->user()->profile_id == 17)
                <a
                    href="{{ route('tenant.tat.restock.list') }}"
                    class="p-2 text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200
                        flex items-center gap-2"
                    wire:navigate.hover>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>

                    <span>Regresar a Solicitudes</span>
                </a>
                @else
                <a
                    href="{{ route('tenant.quoter') }}"
                    class="p-2 text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200
                        flex items-center gap-2"
                    wire:navigate.hover>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>

                    <span>Regresar cotizaciones</span>
                </a>
                @endif

                <!-- Mobile menu button (Hamburger) -->
                <button type="button" class="-m-2.5 p-2.5 text-gray-700 dark:text-gray-300 lg:hidden" @click="sidebarOpen = true">
                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>
            </div>

            <!-- Contenedor principal con elementos verticales -->
            <div class="space-y-3">
                <!-- Búsqueda -->
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <!-- Icono de búsqueda normal -->
                        <svg wire:loading.remove wire:target="search" class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>

                        <!-- Spinner de carga -->
                        <svg wire:loading wire:target="search" class="h-4 w-4 text-indigo-500 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                    <input wire:model.live.debounce.500ms="search"
                        wire:key="mobile-search-input"
                        type="text"
                        placeholder="Buscar productos..."
                        class="block w-full pl-10 pr-3 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        autocomplete="off"
                        inputmode="search"
                        onkeydown="handleProductSearchKeydown(event)"
                        id="productSearchInput">
                </div>

                <!-- Filtro de Categorías -->
                <div class="w-full">
                    <select wire:model.live="selectedCategory"
                        class="block w-full px-3 py-3 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="">Todas las categorías</option>
                        @foreach($this->getCategories() as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

        </div>
    </div>

    <!-- Lista de productos con scroll independiente -->
    <div class="flex-1 overflow-y-auto pb-20">

    <!-- Lista de productos en Grid de 2 columnas -->
    <div class="px-3 py-4 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3">
        @forelse($products as $product)
        @php
        $quantity = $this->getProductQuantity($product->id);
        $isSelected = $quantity > 0;
        @endphp

        <div class="relative flex flex-col bg-white dark:bg-gray-800 rounded-xl border-2 transition-all duration-300 overflow-hidden
                        {{ $isSelected 
                            ? 'border-indigo-500 ring-2 ring-indigo-200 dark:ring-indigo-900/40 shadow-md scale-[1.02]' 
                            : 'border-transparent shadow-sm hover:border-gray-200 dark:hover:border-gray-700' }}">

            <!-- Badge de cantidad (Flotante) -->
            @if($quantity > 0)
            <div class="absolute top-2 right-2 z-10">
                <span class="flex items-center justify-center w-7 h-7 bg-indigo-600 dark:bg-indigo-500 text-white text-xs font-bold rounded-full shadow-lg border-2 border-white dark:border-gray-800 animate-in zoom-in duration-300">
                    {{ $quantity }}
                </span>
            </div>
            @endif

            <!-- Imagen del producto -->
            <div class="aspect-square bg-gray-50 dark:bg-gray-700/50 flex items-center justify-center p-2 relative group">
                @if($product->principalImage)
                <img class="w-full h-full object-contain rounded-lg transition-transform duration-300 group-hover:scale-110"
                    src="{{ $product->principalImage->getImageUrl() }}"
                    alt="{{ $product->display_name }}">
                @else
                <div class="w-16 h-16 bg-gray-200 dark:bg-gray-600 rounded-2xl flex items-center justify-center shadow-inner">
                    <span class="text-2xl font-bold text-gray-400 dark:text-gray-500">
                        {{ strtoupper(substr($product->name, 0, 1)) }}
                    </span>
                </div>
                @endif
                
                @if($isSelected)
                <div class="absolute inset-0 bg-indigo-500/5 transition-opacity"></div>
                @endif
            </div>

            <!-- Información del producto -->
            <div class="p-3 flex-1 flex flex-col">
                <!-- SKU y Nombre -->
                <div class="mb-3">
                    <div class="text-[10px] uppercase tracking-wider text-gray-400 dark:text-gray-500 font-semibold truncate mb-1">
                        {{ $product->sku ?: 'Sin SKU' }}
                    </div>
                    <div class="font-bold text-gray-900 dark:text-white text-xs line-clamp-2 leading-tight min-h-[2rem]">
                        {{ $product->display_name }}
                    </div>
                </div>

                <!-- Precios/Botón o Selector de cantidad -->
                <div class="mt-auto pt-2">
                    @if($isSelected)
                        <!-- Selector de cantidad Inline -->
                        <div class="flex items-center justify-between bg-indigo-50 dark:bg-indigo-900/20 rounded-lg p-1 border border-indigo-100 dark:border-indigo-800 animate-in fade-in slide-in-from-bottom-2 duration-300">
                            <!-- Botón Menos -->
                            <button wire:click="decreaseQuantity({{ $product->id }})"
                                    class="w-8 h-8 flex items-center justify-center bg-white dark:bg-gray-800 text-indigo-600 dark:text-indigo-400 rounded-md shadow-sm border border-indigo-100 dark:border-indigo-800 active:scale-90 transition-transform">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 12H4"></path>
                                </svg>
                            </button>

                            <!-- Input de cantidad -->
                            <input type="number" 
                                   value="{{ $quantity }}"
                                   wire:change="updateQuantityById({{ $product->id }}, $event.target.value)"
                                   onfocus="this.select()"
                                   oncontextmenu="return false;"
                                   class="w-12 text-center bg-transparent border-none text-gray-900 dark:text-white font-black text-sm p-0 focus:ring-0"
                                   inputmode="numeric">

                            <!-- Botón Más -->
                            <button wire:click="increaseQuantity({{ $product->id }})"
                                    class="w-8 h-8 flex items-center justify-center bg-indigo-600 text-white rounded-md shadow-sm active:scale-90 transition-transform">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                            </button>
                        </div>
                    @else
                        @php
                        $allPrices = $product->all_prices;
                        @endphp

                        @if(!empty($allPrices))
                            @php
                            $filteredPrices = auth()->user()->profile_id == 17
                                ? collect($allPrices)->filter(fn($price, $label) => $label === 'Precio Regular')
                                : collect($allPrices);
                            @endphp

                            @foreach($filteredPrices as $label => $price)
                                <button
                                    wire:click="addToQuoter({{ $product->id }}, {{ $price }}, '{{ $label }}')"
                                    wire:loading.attr="disabled"
                                    class="w-full py-2 px-2 text-center rounded-lg border-2 transition-all duration-200 flex flex-col items-center justify-center gap-0.5
                                    bg-green-50 dark:bg-green-900/10 border-green-100 dark:border-green-800 text-green-700 dark:text-green-400 hover:bg-green-100 dark:hover:bg-green-900/20 active:scale-95">
                                    
                                    <span class="text-[9px] uppercase font-bold opacity-60">{{ $label == 'Precio Regular' ? 'Precio' : $label }}</span>
                                    <span class="text-sm font-black tracking-tight">${{ number_format($price) }}</span>
                                    
                                    <div wire:loading wire:target="addToQuoter" class="absolute inset-0 flex items-center justify-center bg-inherit rounded-lg">
                                        <svg class="w-5 h-5 animate-spin text-current" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </div>
                                </button>
                            @endforeach
                        @else
                            <div class="text-[10px] font-bold text-gray-400 dark:text-gray-500 italic text-center py-2 border border-dashed border-gray-200 dark:border-gray-700 rounded-lg">
                                Sin precio
                            </div>
                        @endif
                    @endif
                </div>
            </div>

            <!-- Efecto de pulso si está seleccionado -->
            @if($isSelected)
            <div class="absolute -inset-0.5 rounded-xl border border-indigo-500/20 animate-pulse pointer-events-none"></div>
            @endif
        </div>
        @empty
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
            </svg>
            <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">No hay productos</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                @if($search)
                No se encontraron productos que coincidan con "{{ $search }}".
                @else
                No hay productos disponibles en este momento.
                @endif
            </p>
        </div>
        @endforelse
    </div>

    <!-- Paginación -->
    @if($products->hasPages())
    <div class="px-4 py-4">
        {{ $products->links('livewire.tenant.quoter.components.simple-pagination') }}
    </div>
    @endif
</div>

    <!-- Modal del carrito -->
    @if($showCartModal)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <!-- Overlay -->
        <div class="fixed inset-0 bg-black bg-opacity-50" wire:click="toggleCartModal"></div>

        <!-- Modal - Pantalla completa -->
        <div class="relative h-full flex items-stretch">
            <div class="w-full h-full bg-white dark:bg-gray-800 flex flex-col">

                <!-- Header del modal -->
                <div class="px-4 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between flex-shrink-0 bg-white dark:bg-gray-800">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $this->quoterCount }} Productos seleccionados</h2>
                    <div class="flex items-center gap-2">
                        <!-- Botón limpiar carrito -->
                        @if(!empty($quoterItems))
                        <button
                            onclick="confirmClearCart()"
                            title="Limpiar carrito"
                            class="text-red-500 hover:text-red-700 p-2 mr-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                            </svg>
                        </button>
                        @endif

                        <!-- Botón Seguir Comprando (Cerrar) -->
                         <button wire:click="toggleCartModal" class="flex items-center gap-1 text-indigo-600 hover:text-indigo-800 font-medium text-sm bg-indigo-50 px-3 py-1.5 rounded-lg transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                            Seguir Comprando
                        </button>
                    </div>
                </div>

                <!-- Búsqueda de clientes -->
                <div class="px-4 py-4 border-b border-gray-200 dark:border-gray-700 flex-shrink-0 bg-white dark:bg-gray-800">
                    @if($selectedCustomer)
                    <!-- Cliente seleccionado -->
                    <div wire:key="customer-selected-box"
                        x-data="{ show: true }"
                        x-show="show"
                        x-transition.opacity
                        class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-lg p-3 mb-4">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h4 class="font-semibold text-green-800 dark:text-green-200 text-sm">
                                    {{ $selectedCustomer['businessName'] ?: $selectedCustomer['firstName'] . ' ' . $selectedCustomer['lastName'] }}
                                </h4>

                                <p class="text-xs text-green-600 dark:text-green-300">
                                    Identificación: {{ $selectedCustomer['identification'] }}
                                </p>
                            </div>
                            @if(auth()->user()->profile_id != 17)
                            <div class="flex items-center ml-2">
                                <!-- Botón Editar -->
                                <button
                                    wire:click="editCustomer"
                                    wire:loading.attr="disabled"
                                    wire:loading.class="opacity-50 cursor-wait"
                                    class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200 mr-4"
                                    title="Editar cliente">

                                    <!-- Ícono normal -->
                                    <svg wire:loading.remove wire:target="editCustomer" class="w-7 h-7 mr-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>

                                    <!-- Ícono de loading -->
                                    <svg wire:loading wire:target="editCustomer" class="w-6 h-6 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                    </svg>
                                </button>

                                <!-- Botón Limpiar -->
                                <button
                                    x-on:click="show = false"
                                    wire:click="clearCustomer"
                                    class="text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-200"
                                    title="Limpiar cliente">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif

                    @if(($showCreateCustomerButton || $showCreateCustomerForm) && auth()->user()->profile_id != 17)
                    <!-- Formulario para crear/editar cliente -->

                    @if (!$editingCustomerId)
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-medium text-gray-700 dark:text-gray-300">Crear Cliente</label>
                        <button
                            x-on:click="show = false"
                            wire:click="clearCustomer"
                            class="text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-200 ml-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    @endif

                    @if (!$editingCustomerId)
                    <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-2">
                        @endif
                        <livewire:tenant.vnt-company.vnt-company-form
                            :reusable="true"
                            :companyId="$editingCustomerId"
                            key="customer-form-{{ $editingCustomerId ?? 'new' }}" />
                        @if (!$editingCustomerId)
                    </div>
                    @endif


                    @endif

                    @if(!$selectedCustomer && !$showCreateCustomerForm && !$showCreateCustomerButton && auth()->user()->profile_id != 17)
                    <!-- Formulario de búsqueda -->
                    <div class="space-y-2">
                        <label class="text-xs font-medium text-gray-700 dark:text-gray-300">Buscar Cliente</label>
                        <!-- Input de búsqueda -->
                        <input
                            wire:model.live.debounce.150ms="customerSearch"
                            type="text"
                            placeholder="Escribe nombre o cédula..."
                            class="w-full px-3 py-2 text-sm border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-green-500"
                            onkeydown="handleCustomerSearchKeydownMobile(event)"
                            id="customerSearchInputMobile"
                            autocomplete="off"
                            inputmode="text">

                        <!-- Resultados de búsqueda -->
                        @if(count($customerSearchResults) > 0)
                        <div id="customerSearchResultsMobile" class="max-h-60 overflow-y-auto border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 mt-2">
                            @foreach($customerSearchResults as $index => $customer)
                            <div
                                wire:click="selectCustomer({{ $customer['id'] }})"
                                data-customer-id="{{ $customer['id'] }}"
                                data-index="{{ $index }}"
                                class="customer-result-mobile px-3 py-2 text-xs hover:bg-gray-50 dark:hover:bg-gray-600 cursor-pointer border-b border-gray-100 dark:border-gray-600 last:border-b-0 transition-colors duration-150">
                                <div class="font-mono font-bold text-gray-900 dark:text-white">{{ $customer['identification'] }}</div>
                                <div class="text-gray-600 dark:text-gray-300">{{ $customer['display_name'] }}</div>
                            </div>
                            @endforeach
                        </div>
                        @elseif(strlen($customerSearch) >= 1)
                        <div class="border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 mt-2">
                            <div class="p-3 text-sm text-gray-500 dark:text-gray-400">
                                <div class="mb-2">No se encontraron clientes</div>
                                <button
                                    wire:click="openCustomerModal"
                                    class="px-3 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded text-xs transition-colors">
                                    Crear nuevo cliente
                                </button>
                            </div>
                        </div>
                        @endif

                    </div>
                    @endif

                    {{-- Información para usuarios de tienda (profile_id 17) cuando no hay cliente seleccionado --}}
                    @if(!$selectedCustomer && auth()->user()->profile_id == 17)
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-3 mb-4">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            <div class="flex-1">
                                <h4 class="font-medium text-blue-800 dark:text-blue-200 text-sm">{{ auth()->user()->name }}</h4>
                                <p class="text-xs text-blue-600 dark:text-blue-300">{{ auth()->user()->email }}</p>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Contenido del carrito -->
                <div class="flex-1 overflow-y-auto px-4 py-4 min-h-0">
                    @if(empty($quoterItems))

                    <div class="text-center py-8">
                        <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>

                        <p class="text-gray-500 dark:text-gray-400">Tu carrito está vacío</p>
                    </div>

                    @else

                    <div class="space-y-4">
                        @foreach($quoterItems as $index => $item)

                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3">

                            <div class="flex items-center justify-between mb-2">
                                <div class="flex-1">
                                    <h4 class="font-medium text-gray-900 dark:text-white text-sm">{{ $item['name'] }}</h4>
                                    @if(isset($item['price_label']))
                                    <p class="text-xs text-indigo-600 dark:text-indigo-400 mt-1">Precio: {{ $item['price_label'] }}</p>
                                    @endif

                                </div>

                                <button wire:click="removeFromQuoter({{ $index }})" class="text-red-500 hover:text-red-700">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                    </svg>
                                </button>
                            </div>

                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <label for="quantity-{{ $index }}" class="text-xs font-medium text-gray-500 dark:text-gray-400">Cant:</label>
                                    <input
                                        id="quantity-{{ $index }}"
                                        type="number"
                                        wire:model.lazy="quoterItems.{{ $index }}.quantity"
                                        wire:change="validateQuantity({{ $index }})"
                                        min="1"
                                        max="9999"
                                        step="1"
                                        inputmode="numeric"
                                        pattern="[0-9]*"
                                        class="w-16 px-2 py-1 text-center text-sm font-medium border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                                        value="{{ $item['quantity'] }}"
                                        onwheel="this.blur()"
                                        autocomplete="off">
                                </div>


                                <!-- <div class="flex items-center space-x-2">
                                    <label for="quantity-{{ $index }}" class="text-xs font-medium text-gray-500 dark:text-gray-400">Desc:</label>
                                    <input
                                        id="quantity-{{ $index }}"
                                        type="number"
                                        wire:model.lazy="quoterItems.{{ $index }}.quantity"
                                        wire:change="validateQuantity({{ $index }})"
                                        min="1"
                                        max="9999"
                                        step="1"
                                        inputmode="numeric"
                                        pattern="[0-9]*"
                                        class="w-16 px-2 py-1 text-center text-sm font-medium border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 [appearance:textfield] [&::-webkit-outer-spin-button]:appearance-none [&::-webkit-inner-spin-button]:appearance-none"
                                        value="{{ $item['quantity'] }}"
                                        onwheel="this.blur()"
                                        autocomplete="off">
                                </div> -->

                                <div class="text-sm font-bold text-gray-900 dark:text-white">
                                    ${{ number_format($item['price'] * $item['quantity']) }}
                                </div>
                            </div>

                        </div>

                        @endforeach
                    </div>

                    @endif
                </div>

                <!-- Footer del modal -->
                @if(!empty($quoterItems))
                <div class="px-4 py-4 border-t border-gray-200 dark:border-gray-700 space-y-4 flex-shrink-0 bg-white dark:bg-gray-800">

                    <!-- Observaciones -->
                    @if(auth()->user()->profile_id != 17)
                    <div x-data="{ open: @entangle('showObservations') }" class="w-full">

                        <button @click="open = !open"
                            class="w-full flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">

                            <span class="text-sm font-bold text-gray-900 dark:text-white flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                Observaciones:
                            </span>

                            <svg class="w-5 h-5 text-gray-600 dark:text-gray-400 transform transition-transform"
                                :class="{ 'rotate-180': open }"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div x-show="open" x-transition class="mt-3">
                            <textarea
                                wire:model="observaciones"
                                rows="4"
                                placeholder="Escribe observaciones adicionales..."
                                class="block w-full p-2 text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300
                               dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                        </div>
                    </div>
                    @endif

                    <!-- Total -->
                    <div class="flex justify-between items-center text-lg font-bold text-gray-900 dark:text-white">
                        <span>Total:</span>
                        <span>${{ number_format($totalAmount) }}</span>
                    </div>

                    <!-- Botones ---->
                    @if($isEditing)

                    <div class="flex flex-col gap-3">

                        <!-- Fila superior: Actualizar / Cancelar -->
                        <div class="flex gap-2">
                            <button wire:click="updateQuote"
                                wire:loading.attr="disabled"
                                wire:target="updateQuote"
                                class="flex-1 bg-green-600 hover:bg-green-700 text-white font-medium py-3 px-4 rounded-lg flex items-center justify-center disabled:opacity-50 text-sm whitespace-nowrap">

                                <svg wire:loading.remove wire:target="updateQuote" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>

                                <span wire:loading.remove wire:target="updateQuote">Actualizar</span>
                                <span wire:loading wire:target="updateQuote">Actualizando...</span>
                            </button>

                            <button wire:click="cancelEditing"
                                wire:loading.attr="disabled"
                                wire:target="cancelEditing"
                                class="flex-1 bg-gray-500 hover:bg-gray-600 text-white font-medium py-3 px-4 rounded-lg flex items-center justify-center disabled:opacity-50 text-sm whitespace-nowrap">

                                <svg wire:loading.remove wire:target="cancelEditing" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>

                                <span wire:loading.remove wire:target="cancelEditing">Cancelar</span>
                                <span wire:loading wire:target="cancelEditing">Cancelando...</span>
                            </button>
                        </div>

                        <!-- Botón inferior: Confirmar pedido -->
                        @if($quoteHasRemission)
                             <div class="w-full bg-gray-200 dark:bg-gray-700 text-gray-500 dark:text-gray-400 font-medium py-3 px-4 rounded-lg flex items-center justify-center text-sm border border-gray-300 dark:border-gray-600">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Remisión ya generada
                            </div>
                        @else
                            <button wire:click="confirmarPedido"
                                class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-4 rounded-lg
                                       transition-colors flex items-center justify-center text-sm">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z" />
                                </svg>
                                Confirmar pedido
                            </button>
                        @endif
                    </div>


                    @else

                    <div class="space-y-2">

                        @if(!$selectedCustomer && auth()->user()->profile_id != 17)
                        <button disabled
                            class="w-full bg-gray-400 dark:bg-gray-600 text-gray-200 dark:text-gray-400 font-medium py-3 px-4 rounded-lg cursor-not-allowed flex items-center justify-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.802-.833-2.572 0L4.242 16.5c-.77.833.192 2.5 1.732 2.5z" />
                            </svg>
                            Seleccione un Cliente
                        </button>
                        @elseif($selectedCustomer || auth()->user()->profile_id == 17)

                        @if(auth()->user()->profile_id != 17)
                        <button wire:click="saveQuote"
                            wire:loading.attr="disabled"
                            wire:target="saveQuote"
                            class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-3 px-4 rounded-lg flex items-center justify-center disabled:opacity-50">

                            <svg wire:loading.remove wire:target="saveQuote" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1" />
                            </svg>

                            <svg wire:loading wire:target="saveQuote" class="w-5 h-5 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                <path class="opacity-75" fill="currentColor"
                                    d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                            </svg>

                            <span wire:loading.remove wire:target="saveQuote">Guardar Cotización</span>
                            <span wire:loading wire:target="saveQuote">Guardando...</span>
                        </button>
                        @endif

                        @if(auth()->user()->profile_id == 17)
                        <!-- Botones TAT específicos -->
                        <div class="space-y-2">
                            @if(!$isEditingRestock)
                            <!-- Botón Agregar a Lista Preliminar - Solo cuando NO se está editando -->
                            <button wire:click="saveRestockRequest(false)"
                                wire:loading.attr="disabled"
                                wire:target="saveRestockRequest"
                                class="w-full bg-orange-600 hover:bg-orange-700 dark:bg-orange-500 dark:hover:bg-orange-600 text-white font-medium py-3 px-4 rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed">

                                <svg wire:loading.remove wire:target="saveRestockRequest" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>

                                <svg wire:loading wire:target="saveRestockRequest" class="w-5 h-5 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 818-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>

                                <span wire:loading.remove wire:target="saveRestockRequest">Agregar a Lista de Favoritos</span>
                                <span wire:loading wire:target="saveRestockRequest">Agregando...</span>
                            </button>
                            @endif

                            <!-- Botón Confirmar y Migrar Directamente - Siempre disponible -->
                            <button wire:click="saveRestockRequest(true)"
                                wire:loading.attr="disabled"
                                wire:target="saveRestockRequest"
                                class="w-full bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 text-white font-medium py-3 px-4 rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed">

                                <svg wire:loading.remove wire:target="saveRestockRequest" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>

                                <svg wire:loading wire:target="saveRestockRequest" class="w-5 h-5 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 818-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>

                                <span wire:loading.remove wire:target="saveRestockRequest">Confirmar Directamente</span>
                                <span wire:loading wire:target="saveRestockRequest">Procesando...</span>
                            </button>
                        </div>
                        @endif

                    </div>

                    @endif

                    @if($isEditingRestock)
                    <button wire:click="saveRestockRequest"
                        wire:loading.attr="disabled"
                        wire:target="saveRestockRequest"
                        class="mt-2 w-full bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white font-medium py-3 px-4 rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed">

                        <svg wire:loading.remove wire:target="saveRestockRequest" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>

                        <span wire:loading.remove wire:target="saveRestockRequest">Actualizar Solicitud</span>
                        <span wire:loading wire:target="saveRestockRequest">Actualizando...</span>
                    </button>
                    @endif
                </div>
                @endif

            </div>
        </div>

    </div>
    @endif
    @endif
    <!-- Carrito flotante fijo siempre visible -->
    @if(!$showCartModal && $this->quoterCount > 0)
    <div class="fixed bottom-6 right-6 z-50">
        <button
            @click="$wire.toggleCartModal()"
            class="relative bg-indigo-600 hover:bg-indigo-700 text-white rounded-full p-4 shadow-lg transition-all duration-200 hover:scale-110">

            <!-- Icono del carrito -->
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17M17 16a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>

            <!-- Badge de cantidad -->
            <span class="absolute -top-2 -right-2 bg-red-500 text-white text-xs font-bold rounded-full w-6 h-6 flex items-center justify-center animate-pulse">
                {{ $this->quoterCount }}
            </span>
        </button>
    </div>
    @endif
</div>


@push('scripts')
<script>
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('show-toast', (data) => {
            const payload = Array.isArray(data) ? data[0] : data;
            console.log('Mobile Toast triggered:', payload); // Debug
            Swal.fire({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2000,
                timerProgressBar: true,
                icon: payload.type || 'info',
                title: payload.message,
                background: '#ffffff',
                color: '#111827'
            });
        });

        Livewire.on('confirm-add-duplicate', (data) => {
            const payload = Array.isArray(data) ? data[0] : data;
            Swal.fire({
                title: 'Producto ya confirmado',
                text: payload.message + "\n¿Deseas agregarlo de todas formas?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, agregar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    console.log('Mobile Calling forceAddToQuoter directly:', payload);
                    Livewire.find('{{ $this->getId() }}').call('forceAddToQuoter',
                        payload.productId,
                        payload.selectedPrice,
                        payload.priceLabel
                    );
                }
            });
        });
    });

    // Función para confirmar limpiar carrito
    function confirmClearCart() {
        Swal.fire({
            title: '¿Limpiar carrito?',
            text: 'Se eliminarán todos los productos del carrito. El cliente seleccionado se mantendrá.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Sí, limpiar',
            cancelButtonText: 'Cancelar',
            background: '#ffffff',
            color: '#111827'
        }).then((result) => {
            if (result.isConfirmed) {
                @this.call('clearCart');
            }
        });
    }

    // Función para manejar Enter en búsqueda de productos
    function handleProductSearchKeydown(event) {
        if (event.key === 'Enter') {
            event.preventDefault();

            // Buscar el primer producto visible con precio disponible
            const products = document.querySelectorAll('[wire\\:click*="addToQuoter"]');

            if (products.length > 0) {
                // Buscar el primer botón de precio que no esté deshabilitado
                for (let product of products) {
                    if (!product.disabled && !product.hasAttribute('disabled')) {
                        // Hacer scroll al producto
                        product.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });

                        // Simular click después del scroll
                        setTimeout(() => {
                            product.click();
                        }, 300);
                        break;
                    }
                }
            }
        }
    }

    // Keyboard navigation for mobile customer search
    let selectedCustomerIndexMobile = -1;

    function handleCustomerSearchKeydownMobile(event) {
        const resultsContainer = document.getElementById('customerSearchResultsMobile');
        const results = resultsContainer ? resultsContainer.querySelectorAll('.customer-result-mobile') : [];

        if (results.length === 0) return;

        switch (event.key) {
            case 'ArrowDown':
                event.preventDefault();
                selectedCustomerIndexMobile = Math.min(selectedCustomerIndexMobile + 1, results.length - 1);
                updateCustomerSelectionMobile(results);
                break;
            case 'ArrowUp':
                event.preventDefault();
                selectedCustomerIndexMobile = Math.max(selectedCustomerIndexMobile - 1, -1);
                updateCustomerSelectionMobile(results);
                break;
            case 'Enter':
                event.preventDefault();
                if (selectedCustomerIndexMobile >= 0 && results[selectedCustomerIndexMobile]) {
                    const customerId = results[selectedCustomerIndexMobile].getAttribute('data-customer-id');
                    if (customerId) {
                        results[selectedCustomerIndexMobile].click();
                    }
                }
                break;
            case 'Escape':
                event.preventDefault();
                selectedCustomerIndexMobile = -1;
                updateCustomerSelectionMobile(results);
                document.getElementById('customerSearchInputMobile').value = '';
                // Clear Livewire model
                Livewire.find(document.querySelector('[wire\\:id]').getAttribute('wire:id')).set('customerSearch', '');
                break;
        }
    }

    function updateCustomerSelectionMobile(results) {
        // Remove previous highlights
        results.forEach(result => {
            result.classList.remove('bg-blue-100', 'dark:bg-blue-700');
        });

        // Add highlight to current selection
        if (selectedCustomerIndexMobile >= 0 && results[selectedCustomerIndexMobile]) {
            const selected = results[selectedCustomerIndexMobile];
            selected.classList.add('bg-blue-100', 'dark:bg-blue-700');

            // Scroll into view if needed
            const container = document.getElementById('customerSearchResultsMobile');
            if (container) {
                const containerRect = container.getBoundingClientRect();
                const selectedRect = selected.getBoundingClientRect();

                if (selectedRect.bottom > containerRect.bottom) {
                    selected.scrollIntoView({
                        block: 'end',
                        behavior: 'smooth'
                    });
                } else if (selectedRect.top < containerRect.top) {
                    selected.scrollIntoView({
                        block: 'start',
                        behavior: 'smooth'
                    });
                }
            }
        }
    }

    // Reset selection when customer search results change
    document.addEventListener('livewire:updated', function() {
        selectedCustomerIndexMobile = -1;
    });
</script>
@endpush