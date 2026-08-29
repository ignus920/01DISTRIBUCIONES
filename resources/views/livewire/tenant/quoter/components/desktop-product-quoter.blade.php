{{-- Establecer el header --}}
@php
$header = 'Seleccionar productos';
@endphp

<div x-data="quoterDesktop()" 
     @customer-selected.window="showOfflineCreateForm = false"
     :class="{ 'relative z-[9999]': showOfflineCreateForm }">
    <div class="flex">
        <!-- Área principal de productos -->
        <div class="flex-1 p-6">
            <!-- Botón de regresar -->
            <div class="mb-6">
                @if(auth()->check() && auth()->user()->profile_id == 17)
                <a href="{{ route('tenant.tat.restock.list') }}" class="inline-flex items-center text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200 font-medium">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                    Regresar a Solicitudes
                </a>
                @else
                <a href="{{ route('tenant.remissions') }}" class="inline-flex items-center text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200 font-medium">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Regresar a cotizaciones
                </a>

                 
                @endif
   <!-- Boton solo para ruteros -->
                @if(auth()->check() && auth()->user()->profile_id == 4)
                <button wire:click="openRoutes"
                                class="inline-flex items-center px-4 py-2 rounded-lg font-semibold text-xs uppercase transition-all duration-200 bg-cyan-600 hover:bg-cyan-700 dark:bg-cyan-500 dark:hover:bg-cyan-600 text-white focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7">
                                    </path>
                                </svg>
                                Ruteros
                            </button>
                 @endif
            </div>

            <!-- Barra de búsqueda y filtros -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
                <div class="p-6">
                    <div class="flex flex-col lg:flex-row lg:items-center gap-4">
                        <!-- Búsqueda -->
                        <div class="flex-1">
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                </div>
                                <input wire:model.live.debounce.300ms="search"
                                    type="text"
                                    placeholder="Buscar productos..."
                                    class="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                        </div>


                        <!-- Filtro de Categorías -->
                        <div class="flex items-center gap-3">
                            <!-- Filtro de Categorías -->
                            <select wire:model.live="selectedCategory"
                                class="border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="">Todas las categorías</option>
                                @foreach($this->getCategories() as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Controles -->
                        <div class="flex items-center gap-3">
                            <!-- Registros por página -->
                            <select wire:model.live="perPage"
                                class="border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="12">12 por página</option>
                                <option value="24">24 por página</option>
                                <option value="48">48 por página</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Grid de productos -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4">
                @forelse($products as $product)
                @php
                $quantity = $this->getProductQuantity($product->id);
                @endphp
                <div wire:key="product-card-{{ $product->id }}"
                    x-data="{ productId: {{ $product->id }} }"
                    @click.stop="increaseQuantityInstant(productId)"
                    class="relative bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden transform transition-all duration-200
                                hover:shadow-lg hover:shadow-indigo-100 dark:hover:shadow-gray-900/30 hover:-translate-y-1 hover:border-indigo-300 dark:hover:border-indigo-500
                                cursor-pointer"
                    :class="getItemQuantity(productId) > 0 ? 'ring-2 ring-indigo-500 shadow-lg border-indigo-300 dark:border-indigo-500' : ''">

                    <!-- Contador en la esquina superior derecha -->
                    <div x-show="getItemQuantity(productId) > 0"
                         x-text="getItemQuantity(productId)"
                         class="absolute top-2 right-2 bg-indigo-600 dark:bg-indigo-500 text-white text-xs font-bold rounded-full w-6 h-6 flex items-center justify-center z-10">
                    </div>

                    <!-- Imagen del producto -->
                    <div class="aspect-square bg-gray-100 dark:bg-gray-700 flex items-center justify-center p-2">
                        @if($product->principalImage)
                        <img class="w-full h-full object-cover rounded-lg"
                            src="{{ $product->principalImage->getImageUrl() }}"
                            alt="{{ $product->display_name }}">
                        @else
                        <div class="w-16 h-16 bg-gray-200 dark:bg-gray-600 rounded-lg flex items-center justify-center">
                            <span class="text-2xl font-bold text-gray-400 dark:text-gray-500">
                                {{ strtoupper(substr($product->name, 0, 1)) }}
                            </span>
                        </div>
                        @endif
                    </div>



                    <div class="text-center flex-1">

                        <!-- Información del producto -->
                        <div class="p-3 flex flex-col h-full">
                            <!-- Nombre -->
                            <div class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                {{ $product->display_name }}
                            </div>
                            <!-- SKU (con altura fija para mantener estructura) -->
                            <div class="text-xs text-gray-500 dark:text-gray-500 mb-3 h-4 flex items-center justify-center">
                                @if($product->sku && trim($product->sku) !== '')
                                SKU: {{ $product->sku }}
                                @endif
                            </div>

                            <!-- Precios -->
                            @php
                            $allPrices = $product->all_prices;
                            @endphp
                            @if(!empty($allPrices))
                            @php
                            $userProfileId = auth()->user()->profile_id;
                            $filteredPrices = collect($allPrices);
                            
                            if ($userProfileId == 17) {
                                // Perfil Tienda (TAT): Solo Precio Regular
                                $filteredPrices = $filteredPrices->filter(fn($price, $label) => $label === 'Precio Regular');
                            } elseif ($userProfileId == 4) {
                                // Perfil Vendedor: Solo Precio Base (P1)
                                $filteredPrices = $filteredPrices->filter(fn($price, $label) => 
                                    strtolower($label) === 'p1' || strtolower($label) === 'precio base'
                                );
                            }
                            
                            $priceCount = $filteredPrices->count();
                            @endphp
                            <div class="mb-2 grid {{ $priceCount == 1 ? 'grid-cols-1' : 'grid-cols-2' }} gap-1">
                                @foreach($filteredPrices as $label => $price)
                                @php
                                    $taxRate      = $product->tax->percentage ?? 0;
                                    $priceWithTax = round($price * (1 + $taxRate / 100));
                                @endphp
                                <button
                                    title="{{ $label }}"
                                    x-on:click.stop="addItemInstant({{ $product->id }}, '{{ addslashes($product->display_name) }}', '{{ $product->sku }}', {{ $priceWithTax }}, '{{ $label }}')"
                                    class="px-2 py-1 text-center rounded border transition-colors min-h-[28px] flex items-center justify-center bg-gray-50 dark:bg-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 cursor-pointer">
                                    <div class="font-bold text-xs text-gray-900 dark:text-white">
                                        ${{ number_format($priceWithTax) }}
                                    </div>
                                </button>
                                @endforeach
                            </div>
                            @else
                            <div class="font-bold text-lg text-gray-400 dark:text-gray-500 mb-1">
                                Sin precio
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Overlay de selección -->
                    <div x-show="getItemQuantity(productId) > 0" 
                         class="absolute inset-0 bg-indigo-500 bg-opacity-10 dark:bg-indigo-400 dark:bg-opacity-10 pointer-events-none"></div>
                </div>
                @empty
                <div class="col-span-full">
                    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-12 text-center">
                        <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                </div>
                @endforelse
            </div>

            <!-- Paginación -->
            @if($products->hasPages())
            <div class="mt-6">
                {{ $products->links() }}
            </div>
            @endif
        </div>

        <!-- Sidebar del cotizador -->
        <div class="w-96 bg-white dark:bg-gray-800 border-l border-gray-200 dark:border-gray-700 flex flex-col h-screen">
            <!-- Header del cotizador -->
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex-shrink-0">
                <div class="flex items-center justify-between">
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">
                        <span x-text="items.reduce((sum, item) => sum + item.quantity, 0)"></span> Productos seleccionados
                    </h2>
                    <button x-show="items.length > 0" 
                        @click="clearQuoterInstant()"
                        class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 text-sm font-medium">
                        Limpiar
                    </button>
                </div>


                <!-- Búsqueda de clientes -->
                <div class="mt-4">
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
                                    class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200 mr-2"
                                    title="Editar cliente">

                                    <!-- Ícono normal -->
                                    <svg wire:loading.remove wire:target="editCustomer" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>

                                    <!-- Ícono de loading -->
                                    <svg wire:loading wire:target="editCustomer" class="w-6 h-6 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 818-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
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



                    @if(auth()->user()->profile_id != 17)
                    <!-- Lógica de Formulario Automático (Unificado: Online/Offline) -->
                    <div
                        wire:key="auto-form-logic-{{ strlen($customerSearch) }}-{{ count($customerSearchResults) }}-{{ $selectedCustomer ? 'sel' : 'no' }}" 
                        x-init="
                            // Alpine se reinicia cada vez que wire:key cambia
                            console.log('Init Auto-Form', {
                                len: @js(strlen($customerSearch)),
                                results: @js(count($customerSearchResults)),
                                selected: @js($selectedCustomer ? true : false)
                            });

                            if (@js(strlen($customerSearch) >= 3) && @js(count($customerSearchResults) === 0) && !@js($selectedCustomer)) {
                                if (!showOfflineCreateForm) {
                                    console.log('Opening form automatically (Desktop)');
                                    showOfflineCreateForm = true;
                                    newOfflineCustomer.identification = @js($customerSearch);
                                    // newOfflineCustomer.businessName = ''; // Ya no se copia el valor de búsqueda al nombre
                                }
                            } else if (@js(count($customerSearchResults) > 0) || @js($selectedCustomer)) {
                                showOfflineCreateForm = false;
                            }
                        "
                    ></div>

                    <div class="mt-2">
                        @include('livewire.tenant.quoter.components.customer-quick-form')
                    </div>
                    @endif

                    @if(!$selectedCustomer && !$showCreateCustomerForm && !$showCreateCustomerButton && auth()->user()->profile_id != 17)
                    <!-- Formulario de búsqueda -->
                    <div class="space-y-2">
                        <label class="text-xs font-medium text-gray-700 dark:text-gray-300">Buscar Cliente</label>
                        <div>

                            <!-- Input de búsqueda -->
                            <input
                                wire:model.live.debounce.300ms="customerSearch"
                                type="text"
                                placeholder="Buscar por nombre o cédula... (↑↓ navegar, Enter seleccionar)"
                                class="w-full px-3 py-2 text-sm border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                onkeydown="handleCustomerSearchKeydownDesktop(event)"
                                id="customerSearchInputDesktop">


                        </div>

                        <!-- Resultados de búsqueda -->
                        @if(count($customerSearchResults) > 0)
                        <div id="customerSearchResultsDesktop" class="max-h-60 overflow-y-auto border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 mt-2">
                            @foreach($customerSearchResults as $index => $customer)
                            <div
                                wire:click="selectCustomer({{ $customer['id'] }})"
                                data-customer-id="{{ $customer['id'] }}"
                                data-index="{{ $index }}"
                                class="customer-result-desktop px-3 py-2 text-xs hover:bg-gray-50 dark:hover:bg-gray-600 cursor-pointer border-b border-gray-100 dark:border-gray-600 last:border-b-0 transition-colors duration-150">
                                <div class="grid grid-cols-3 gap-2">
                                    <div>
                                        <div class="font-bold text-gray-900 dark:text-white">{{ $customer['display_name'] }}</div>
                                        <div class="text-[10px] font-mono text-indigo-600">ID: {{ $customer['identification'] }}</div>
                                    </div>
                                    <div class="text-xs border-l pl-2">
                                        <div class="font-bold">{{ $customer['route_name'] ?? 'Ruta' }}</div>
                                        <div>{{ $customer['sale_day'] ?? 'Día' }}</div>
                                    </div>
                                    <div class="text-[10px] border-l pl-2">
                                        <div class="font-bold">{{ $customer['district'] ?? 'Barrio' }}</div>
                                        <div>{{ $customer['address'] ?? 'Dirección' }}</div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
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

            </div>

            <div class="flex-1 overflow-y-auto min-h-0 scrollbar-thin scrollbar-thumb-gray-300 dark:scrollbar-thumb-gray-600 scrollbar-track-gray-100 dark:scrollbar-track-gray-700" style="max-height: calc(100vh - 400px);">
                <template x-if="items.length === 0">
                    <div class="flex flex-col items-center justify-center h-full p-6 text-center">
                        <div class="w-16 h-16 bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center mb-4">
                            <svg class="w-8 h-8 text-gray-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Agregar items</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400">
                            Selecciona productos de la lista para agregarlos a tu cotización
                        </p>
                    </div>
                </template>

                <div x-show="items.length > 0" class="p-4 space-y-3 pb-4">
                    <template x-for="(item, index) in items" :key="item.id">
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-3">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex-1">
                                    <h4 class="font-medium text-gray-900 dark:text-white text-sm" x-text="item.name"></h4>
                                    <template x-if="item.price_label">
                                        <p class="text-xs text-indigo-600 dark:text-indigo-400 mt-1">Precio: <span x-text="item.price_label"></span></p>
                                    </template>
                                </div>
                                <button @click="removeItemInstant(item.id)"
                                    class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 ml-2">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>

                            <div class="flex items-center justify-between">
                                <div class="flex items-center space-x-2">
                                    <label class="text-xs font-medium text-gray-500 dark:text-gray-400">Cant:</label>
                                    <input
                                        type="number"
                                        x-model.number="item.quantity"
                                        @change="updateQuantityInstant(item.id, item.quantity)"
                                        min="1"
                                        class="min-w-16 w-auto max-w-24 px-2 py-1 text-center text-sm font-medium border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 [appearance:textfield]"
                                        onwheel="this.blur()"
                                        autocomplete="off">
                                </div>

                                <div class="text-sm font-medium text-gray-900 dark:text-white" x-text="formatMoney(item.price * item.quantity)">
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Footer del cotizador - Fijo en la parte inferior -->
            @if(!empty($quoterItems) )
            <div class="border-t border-gray-200 dark:border-gray-700 p-6 flex-shrink-0 bg-white dark:bg-gray-800 sticky bottom-0">
                <div class="space-y-4">
                    <!-- Observaciones - Acordeón -->
                    @if(auth()->user()->profile_id != 17)
                    <div x-data="{ open: @entangle('showObservations') }" class="w-full">

                        <button
                            @click="open = !open"
                            class="w-full flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">

                            <span class="text-sm font-bold text-gray-900 dark:text-white flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                                Observaciones:
                            </span>

                            <svg class="w-5 h-5 text-gray-600 dark:text-gray-400 transform transition-transform"
                                :class="{ 'rotate-180': open }"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>

                        <div x-show="open" x-transition class="mt-3">
                            <textarea
                                wire:model="observaciones"
                                rows="4"
                                placeholder="Escribe observaciones adicionales..."
                                class="block w-full p-2 text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
                     </textarea>
                        </div>

                    </div>
                    @endif


                    <!-- Total -->
                    <div class="flex justify-between items-center text-lg font-bold text-gray-900 dark:text-white">
                        <span>Total:</span>
                        <span x-text="formatMoney(total)"></span>
                    </div>


                    @if($isEditing)
                    {{-- Botones para edición --}}
                    <div class="flex flex-col gap-4">

                        {{-- Fila superior: Actualizar / Cancelar --}}
                        <div class="flex gap-2">
                            <button wire:click="updateQuote"
                                wire:loading.attr="disabled"
                                wire:target="updateQuote"
                                class="flex-1 bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 text-white font-medium py-3 px-4 rounded-lg transition-colors duration-200 flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed text-sm whitespace-nowrap">

                                <svg wire:loading.remove wire:target="updateQuote" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>

                                <svg wire:loading wire:target="updateQuote" class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>

                                <span wire:loading.remove wire:target="updateQuote">
                                    @if($editingRemissionId)
                                        Editar Pedido
                                    @else
                                        Actualizar Cotización
                                    @endif
                                </span>
                                <span wire:loading wire:target="updateQuote">Actualizando...</span>
                            </button>

                            <button type="button" wire:click="cancelEditing"
                                wire:loading.attr="disabled"
                                wire:target="cancelEditing"
                                class="flex-1 bg-gray-500 hover:bg-gray-600 dark:bg-gray-600 dark:hover:bg-gray-500 text-white font-medium py-3 px-4 rounded-lg transition-colors duration-200 flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed text-sm whitespace-nowrap">
                                Cancelar
                            </button>
                        </div>

                        <!-- Fila inferior: acción final (Solo para Distribuidores durante edición) -->
                        @if(auth()->user()->profile_id != 17)
                            @if($quoteHasRemission)
                                <div class="w-full px-4 py-3 text-sm font-medium text-gray-500 dark:text-gray-400
                                        border border-gray-200 dark:border-gray-700 bg-gray-100 dark:bg-gray-800
                                        rounded-lg flex items-center justify-center cursor-not-allowed">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Pedido ya generado
                                </div>
                            @else
                                <button type="button" wire:click.prevent="abrirModalRemision"
                                    wire:loading.attr="disabled"
                                    wire:target="abrirModalRemision"
                                    class="w-full px-4 py-3 text-sm font-medium bg-indigo-600 hover:bg-indigo-700 text-white shadow-md rounded-lg transition-colors flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed">
                                    <svg wire:loading.remove wire:target="abrirModalRemision" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/>
                                    </svg>
                                    <svg wire:loading wire:target="abrirModalRemision" class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                                    </svg>
                                    <span wire:loading.remove wire:target="abrirModalRemision">Confirmar pedido</span>
                                    <span wire:loading wire:target="abrirModalRemision">Verificando...</span>
                                </button>
                            @endif
                        @endif

                    </div>{{-- fin flex-col gap-4 --}}


                    @else
                    <!-- Botón crear nueva cotización -->
                    @if(!$selectedCustomer && auth()->user()->profile_id != 17)
                    <!-- Botón deshabilitado cuando no hay cliente -->
                    <button disabled
                        class="w-full bg-gray-400 dark:bg-gray-600 text-gray-200 dark:text-gray-400 font-medium py-3 px-4 rounded-lg cursor-not-allowed flex items-center justify-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.802-.833-2.572 0L4.242 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                        Seleccione un Cliente
                    </button>
                    @elseif($selectedCustomer || auth()->user()->profile_id == 17)
                    @if(auth()->user()->profile_id != 17)
                    <!-- Botón Crear Cotización (solo para distribuidores) -->
                    <button wire:click="saveQuote"
                        wire:loading.attr="disabled"
                        wire:target="saveQuote"
                        class="w-full bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white font-medium py-3 px-4 rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed">

                        <!-- Ícono normal (se oculta durante la carga) -->
                        <svg wire:loading.remove wire:target="saveQuote" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
                        </svg>

                        <!-- Ícono de carga (se muestra durante la carga) -->
                        <svg wire:loading wire:target="saveQuote" class="w-5 h-5 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>

                        <!-- Texto del botón (cambia durante la carga) -->
                        <span wire:loading.remove wire:target="saveQuote">Crear Cotización</span>
                        <span wire:loading wire:target="saveQuote">Guardando...</span>
                    </button>
                    @endif

                    @if(auth()->user()->profile_id == 17)
                    <!-- Botones TAT para Perfil 17 (Siempre visibles) -->
                    <div class="flex flex-col gap-2 w-full">
                        <!-- Botón para agregar a lista preliminar -->
                        @if(!$isEditingRestock)
                        <button wire:click="saveRestockRequest(false)"
                            wire:loading.attr="disabled"
                            wire:target="saveRestockRequest"
                            class="w-full bg-orange-600 hover:bg-orange-700 dark:bg-orange-500 dark:hover:bg-orange-600 text-white font-medium py-3 px-4 rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed">

                            <svg wire:loading.remove wire:target="saveRestockRequest" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>

                            <svg wire:loading wire:target="saveRestockRequest" class="w-5 h-5 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>

                            <span wire:loading.remove wire:target="saveRestockRequest">Agregar al carrito</span>
                            <span wire:loading wire:target="saveRestockRequest">Agregando...</span>
                        </button>
                        @endif

                        <!-- Botón Confirmar Lista Preliminar -->
                        <button wire:click="saveRestockRequest(true)"
                            wire:loading.attr="disabled"
                            wire:target="saveRestockRequest"
                            class="w-full bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 text-white font-medium py-3 px-4 rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed">

                            <svg wire:loading.remove wire:target="saveRestockRequest" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>

                            <svg wire:loading wire:target="saveRestockRequest" class="w-5 h-5 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>

                            <span wire:loading.remove wire:target="saveRestockRequest">Confirmar pedido</span>
                            <span wire:loading wire:target="saveRestockRequest">Procesando...</span>
                        </button>
                    </div>
                    @endif
                    @endif

                    @if($isEditingRestock)
                    <button wire:click="saveRestockRequest"
                        wire:loading.attr="disabled"
                        wire:target="saveRestockRequest"
                        class="mt-2 w-full bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white font-medium py-3 px-4 rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed">

                        <svg wire:loading.remove wire:target="saveRestockRequest" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        
                        <svg wire:loading wire:target="saveRestockRequest" class="w-5 h-5 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>

                        <span wire:loading.remove wire:target="saveRestockRequest">Actualizar Carrito</span>
                        <span wire:loading wire:target="saveRestockRequest">Actualizando...</span>
                    </button>
                    @endif
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>


       <!-- Routes Modal -->
    @if($showRoutesModal)
        @livewire('tenant.vnt-company.company-routes-modal', ['showModal' => true], key('routes-modal'))
    @endif

    {{-- ====== MODAL CONFIRMAR PEDIDO — Entrega y Pago ====== --}}
    <div x-data="{ show: @entangle('showRemisionModal') }"
         x-show="show"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 z-[80] flex items-center justify-center px-4"
         style="display:none;">

        <div class="absolute inset-0 bg-black/50" @click="show = false"></div>

        <div class="relative bg-white dark:bg-slate-800 rounded-2xl shadow-2xl w-full max-w-md z-10"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95"
             x-transition:enter-end="opacity-100 scale-100">

            {{-- Header --}}
            <div class="flex items-center justify-between px-6 pt-5 pb-4 border-b border-gray-100 dark:border-slate-700">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 flex items-center justify-center">
                        <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">Confirmar Pedido</h3>
                        <p class="text-xs text-gray-500 dark:text-slate-400 mt-0.5">Seleccione el tipo de entrega y método de pago</p>
                    </div>
                </div>
                <button @click="show = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-slate-300 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Body --}}
            <div class="px-6 py-5 space-y-4">

                {{-- Tipo de entrega --}}
                <div>
                    <label class="block text-xs font-bold text-gray-600 dark:text-slate-300 uppercase tracking-wider mb-2">
                        Tipo de Entrega
                    </label>
                    <select wire:model="selectedDeliveryTypeId"
                        class="w-full px-3 py-2.5 text-sm border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                        <option value="">— Seleccionar tipo de entrega —</option>
                        @foreach($availableDeliveryTypes as $type)
                            <option value="{{ $type['id'] }}">{{ $type['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Método de pago --}}
                <div>
                    <label class="block text-xs font-bold text-gray-600 dark:text-slate-300 uppercase tracking-wider mb-2">
                        Método de Pago
                    </label>
                    <select wire:model="selectedMethodPaymentId"
                        class="w-full px-3 py-2.5 text-sm border border-gray-300 dark:border-slate-600 rounded-lg bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                        <option value="">— Seleccionar método de pago —</option>
                        @foreach($availableMethodPayments as $method)
                            <option value="{{ $method['id'] }}">{{ $method['name'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Footer --}}
            <div class="px-6 py-4 border-t border-gray-100 dark:border-slate-700 flex items-center justify-end gap-3">
                <button @click="show = false"
                    class="px-5 py-2 text-sm font-medium text-gray-700 dark:text-slate-300 bg-white dark:bg-slate-700 border border-gray-300 dark:border-slate-600 rounded-lg hover:bg-gray-50 dark:hover:bg-slate-600 transition-colors">
                    Cancelar
                </button>
                <button wire:click="confirmarPedido"
                    wire:loading.attr="disabled"
                    class="px-5 py-2 text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60 rounded-lg flex items-center gap-2 transition-colors">
                    <svg wire:loading.remove wire:target="confirmarPedido" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <svg wire:loading wire:target="confirmarPedido" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    Confirmar Pedido
                </button>
            </div>
        </div>
    </div>
</div>


@push('scripts')
<script>
    document.addEventListener('livewire:initialized', () => {
        window.addEventListener('show-toast', (event) => {
            const data = event.detail;
            const payload = Array.isArray(data) ? data[0] : data;
            console.log('Toast triggered:', payload); // Debug
            Swal.fire({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 1500,
                timerProgressBar: true,
                icon: payload.type || 'info',
                title: payload.message
            });
        });

        window.addEventListener('confirm-add-duplicate', (event) => {
            const data = event.detail;
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
                    console.log('Calling forceAddToQuoter directly:', payload);
                    // Usamos call directo para evitar problemas de mapeo de eventos
                    Livewire.find('{{ $this->getId() }}').call('forceAddToQuoter',
                        payload.productId,
                        payload.selectedPrice,
                        payload.priceLabel
                    );
                }
            });
        });

        Livewire.on('confirm-load-order', (data) => {
            const payload = Array.isArray(data) ? data[0] : data;
            Swal.fire({
                title: 'Orden Existente',
                text: payload.message,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, cargar orden',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    Livewire.find('{{ $this->getId() }}').call('loadRestockForEditing', payload.orderNumber);
                }
            });
        });
    });

    // Variables para navegación por teclado en búsqueda de clientes (Desktop)
    window.selectedCustomerIndexDesktop = window.selectedCustomerIndexDesktop || -1;
    window.customerResultsDesktop = window.customerResultsDesktop || [];

    // Función para manejar navegación por teclado en búsqueda de clientes (Desktop)
    function handleCustomerSearchKeydownDesktop(event) {
        const resultsContainer = document.getElementById('customerSearchResultsDesktop');

        if (!resultsContainer) return;

        customerResultsDesktop = Array.from(resultsContainer.querySelectorAll('.customer-result-desktop'));

        if (customerResultsDesktop.length === 0) return;

        switch (event.key) {
            case 'ArrowDown':
                event.preventDefault();
                selectedCustomerIndexDesktop = Math.min(selectedCustomerIndexDesktop + 1, customerResultsDesktop.length - 1);
                updateCustomerSelectionDesktop();
                break;

            case 'ArrowUp':
                event.preventDefault();
                selectedCustomerIndexDesktop = Math.max(selectedCustomerIndexDesktop - 1, -1);
                updateCustomerSelectionDesktop();
                break;

            case 'Enter':
                event.preventDefault();
                if (selectedCustomerIndexDesktop >= 0 && customerResultsDesktop[selectedCustomerIndexDesktop]) {
                    const customerId = customerResultsDesktop[selectedCustomerIndexDesktop].dataset.customerId;
                    // Disparar el evento de Livewire para seleccionar cliente
                    Livewire.find('{{ $this->getId() }}').call('selectCustomer', customerId);
                }
                break;

            case 'Escape':
                event.preventDefault();
                selectedCustomerIndexDesktop = -1;
                updateCustomerSelectionDesktop();
                break;
        }
    }

    // Función para actualizar la selección visual (Desktop)
    function updateCustomerSelectionDesktop() {
        customerResultsDesktop.forEach((result, index) => {
            result.classList.remove('bg-green-100', 'dark:bg-green-800', 'border-green-500');

            if (index === selectedCustomerIndexDesktop) {
                result.classList.add('bg-green-100', 'dark:bg-green-800', 'border-green-500');
                // Scroll hacia el elemento seleccionado si está fuera de vista
                result.scrollIntoView({
                    block: 'nearest',
                    behavior: 'smooth'
                });
            }
        });
    }

    // Reset de selección cuando cambian los resultados (Desktop)
    document.addEventListener('livewire:updated', () => {
        window.selectedCustomerIndexDesktop = -1;
        window.customerResultsDesktop = [];
    });

    function quoterDesktop() {
        return {
            showOfflineCreateForm: false,
            items: @js($quoterItems),
            total: @js($totalAmount),
            lockUpdates: false,
            lockTimeout: null,
            newOfflineCustomer: {
                id: null,
                typeIdentificationId: 1,
                identification: '',
                businessName: '',
                firstName: '',
                lastName: '',
                phone: '',
                address: '',
                billingEmail: '',
                createUser: false,
                route_id: @js($newCustomerRouteId)
            },
            init() {
                // Sincronizar items cuando Livewire los actualice (por ejemplo al limpiar o cargar orden)
                this.$watch('$wire.quoterItems', value => {
                    if (this.lockUpdates) {
                        console.log('🔒 Ignorando $watch quoterItems (Bloqueo activo)');
                        return;
                    }
                    this.items = value;
                    this.recalculateTotal();
                });

                window.addEventListener('cart-updated', (event) => {
                    if (this.lockUpdates) return;
                    const items = event.detail.items || event.detail[0]?.items || [];
                    this.items = items;
                    this.recalculateTotal();
                });

                // 0. Limpieza forzada vía señal local (Ej: desde Sidebar)
                if (localStorage.getItem('quoter_clear') === '1') {
                    localStorage.removeItem('quoter_clear'); // Consumir la señal siempre

                    // Solo limpiar si NO estamos editando una cotización/remisión
                    if (!@js($isEditing)) {
                        console.log('🧹 Detectada señal de limpieza local (Desktop)...');
                        this.$wire.clearQuoter();
                    }
                }

                // Escuchar evento de carga de datos para edición
                window.addEventListener('load-customer-data', (event) => {
                    const data = event.detail.customer || event.detail[0]?.customer || event.detail;
                    this.newOfflineCustomer = {
                        id: data.id || null,
                        typeIdentificationId: data.typeIdentificationId || 1,
                        identification: data.identification || '',
                        businessName: data.businessName || '',
                        firstName: data.firstName || '',
                        lastName: data.lastName || '',
                        phone: data.phone || data.business_phone || '',
                        address: data.address || '',
                        billingEmail: data.billingEmail || '',
                        createUser: false,
                        route_id: data.route_id || @js($newCustomerRouteId)
                    };
                    this.showOfflineCreateForm = true;
                });
            },

            setLock() {
                this.lockUpdates = true;
                if (this.lockTimeout) clearTimeout(this.lockTimeout);
                this.lockTimeout = setTimeout(() => {
                    this.lockUpdates = false;
                }, 2000);
            },

            // --- Lógica de Carrito Instantáneo ---
            addItemInstant(productId, name, sku, price, label) {
                this.setLock();
                // 1. Buscar si ya existe para incrementar cantidad
                let index = this.items.findIndex(i => i.id == productId);
                
                if (index !== -1) {
                    this.items[index].quantity++;
                } else {
                    // 2. Agregar nuevo item al estado local inmediatamente
                    this.items.push({
                        id: productId,
                        name: name,
                        sku: sku,
                        price: price,
                        price_label: label,
                        quantity: 1
                    });
                }

                // 3. Recalcular total localmente
                this.recalculateTotal();

                // 4. Llamar al servidor en segundo plano
                this.$wire.addToQuoter(productId, price, label);
            },

            increaseQuantityInstant(productId) {
                this.setLock();
                let index = this.items.findIndex(i => i.id == productId);
                if (index !== -1) {
                    this.items[index].quantity++;
                    let newQty = this.items[index].quantity;
                    this.recalculateTotal();
                    // Usamos updateQuantity para asegurar que el servidor ponga EXACTAMENTE lo que tiene el front
                    this.$wire.updateQuantity(productId, newQty);
                }
            },

            removeItemInstant(productId) {
                this.setLock();
                const index = this.items.findIndex(i => i.id == productId);
                if (index !== -1) {
                    this.items.splice(index, 1);
                    this.recalculateTotal();
                    this.$wire.removeFromQuoter(productId);
                }
            },

            clearQuoterInstant() {
                if (confirm('¿Estás seguro de que deseas limpiar todo el carrito?')) {
                    this.items = [];
                    this.recalculateTotal();
                    this.$wire.clearQuoter();
                }
            },

            updateQuantityInstant(productId, quantity) {
                this.setLock();
                const index = this.items.findIndex(i => i.id == productId);
                if (index === -1) return;

                if (quantity <= 0) {
                    this.removeItemInstant(productId);
                    return;
                }
                this.items[index].quantity = parseInt(quantity);
                this.recalculateTotal();
                // Usamos updateQuantity para asegurar valor absoluto
                this.$wire.updateQuantity(productId, parseInt(quantity));
            },

            recalculateTotal() {
                this.total = this.items.reduce((sum, item) => sum + (item.price * item.quantity), 0);
            },

            getItemQuantity(productId) {
                let item = this.items.find(i => i.id == productId);
                return item ? item.quantity : 0;
            },

            formatMoney(value) {
                return new Intl.NumberFormat('es-CO', { style: 'currency', currency: 'COP', maximumFractionDigits: 0 }).format(value);
            },

            async saveOfflineCustomer() { 
                const isCC = this.newOfflineCustomer.typeIdentificationId == 1;
                const hasIdentification = !!this.newOfflineCustomer.identification;
                const hasName = isCC 
                    ? (this.newOfflineCustomer.firstName && this.newOfflineCustomer.lastName)
                    : !!this.newOfflineCustomer.businessName;

                if (!hasIdentification || !hasName) {
                    const message = isCC 
                        ? 'Identificación, Nombre y Apellido son obligatorios' 
                        : 'Identificación y Razón Social son obligatorios';
                    Swal.fire('Error', message, 'error');
                    return;
                }

                if (isCC) {
                    this.newOfflineCustomer.businessName = (this.newOfflineCustomer.firstName + ' ' + (this.newOfflineCustomer.lastName || '')).trim();
                }

                try {
                    const response = await this.$wire.saveQuickCustomer(this.newOfflineCustomer);
                    if (response && response.success) {
                        this.showOfflineCreateForm = false;
                        this.newOfflineCustomer = {
                            id: null,
                            typeIdentificationId: 1,
                            identification: '',
                            businessName: '',
                            firstName: '',
                            lastName: '',
                            phone: '',
                            address: '',
                            billingEmail: '',
                            createUser: false,
                            route_id: @js($newCustomerRouteId)
                        };
                    }
                } catch (e) {
                    console.error('Error guardando cliente rápido:', e);
                    Swal.fire('Error', 'No se pudo guardar el cliente', 'error');
                }
            }
        }
    }
</script>
@endpush