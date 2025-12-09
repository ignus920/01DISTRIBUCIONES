<div class="w-full max-w-md lg:max-w-4xl mx-auto bg-white dark:bg-gray-900 rounded-lg shadow-lg overflow-visible border-2 border-gray-300 dark:border-gray-700">
    <!-- Header con botones de estado -->
    <div class="bg-gray-50 dark:bg-gray-800 p-3 border-b dark:border-gray-700">
        <!-- Títulos de secciones -->
        <div class="flex justify-between text-xs text-gray-600 dark:text-gray-400 mb-2">
            <span>Inventario</span>
            <span>Surtir</span>
            <span>Finalizar</span>
            <span>Historial</span>
        </div>

        <!-- Botones de estado -->
        <div class="flex justify-between gap-1 mb-3">
            <button class="w-12 h-12 bg-red-500 text-white rounded border-2 border-red-600 font-bold hover:bg-red-600 transition">
                <span class="text-lg font-bold">I</span>
            </button>
            <a href="{{ route('tenant.quoter.products') }}"
               class="w-12 h-12 bg-red-500 text-white rounded border-2 border-red-600 font-bold hover:bg-red-600 transition flex items-center justify-center">
                <span class="text-lg font-bold">S</span>
            </a>
            <button class="w-12 h-12 bg-purple-500 text-white rounded border-2 border-purple-600 font-bold hover:bg-purple-600 transition">
                <span class="text-lg font-bold">F</span>
            </button>
            <button class="w-12 h-12 bg-orange-500 text-white rounded border-2 border-orange-600 font-bold hover:bg-orange-600 transition">
                <span class="text-lg font-bold">H</span>
            </button>
        </div>

        <!-- Total -->
        <div class="bg-black dark:bg-gray-950 text-white p-3 rounded mb-3 ring-2 ring-blue-500 dark:ring-blue-600">
            <div class="text-right text-2xl font-bold text-white">{{ number_format($total, 0, '.', '.') }}</div>
        </div>

        <!-- Cliente -->
        <div class="bg-green-100 dark:bg-green-900/30 p-2 rounded border border-green-200 dark:border-green-700">
            @if($showClientSearch)
                <!-- Modo búsqueda de cliente -->
                <div class="space-y-2">
                    <div class="flex gap-1">
                        <input
                            wire:model.live.debounce.300ms="customerSearch"
                            type="text"
                            placeholder="Buscar por nombre o cédula..."
                            class="flex-1 text-sm px-2 py-1 border border-green-300 rounded focus:outline-none focus:ring-1 focus:ring-green-500"
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
                        <div class="max-h-32 overflow-y-auto border border-green-300 rounded bg-white">
                            @foreach($customerSearchResults as $customer)
                                <div
                                    wire:click="selectCustomer({{ $customer['id'] }})"
                                    class="p-2 text-xs hover:bg-green-50 cursor-pointer border-b last:border-b-0"
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
                    <div>
                        @if($selectedCustomer)
                            <div class="text-green-700 dark:text-green-300 font-mono text-sm font-bold">
                                {{ $selectedCustomer['identification'] }}
                            </div>
                            <div class="text-xs text-green-600 dark:text-green-400">{{ $selectedCustomer['display_name'] }}</div>
                        @else
                            <div class="text-green-700 dark:text-green-300 font-mono text-sm font-bold">
                                Sin cliente
                            </div>
                            <div class="text-xs text-green-600 dark:text-green-400">Seleccionar cliente</div>
                        @endif
                    </div>
                    <button
                        wire:click="enableClientSearch"
                        class="px-2 py-1 bg-green-600 text-white text-xs rounded hover:bg-green-700 flex items-center justify-center"
                        title="Cambiar cliente"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </button>
                </div>
            @endif
        </div>

        <!-- Input de búsqueda de productos - Ahora debajo del cliente -->
        <div class="space-y-1 mt-3">
            <!-- Siempre mostrar un input activo para nueva búsqueda -->
            <div class="relative">
                <input type="text"
                       wire:model.live.debounce.300ms="currentSearch"
                       placeholder="Buscar producto... (Use ↑↓ para navegar, Enter para seleccionar)"
                       class="w-full px-3 py-2 lg:px-4 lg:py-3 text-sm lg:text-base bg-white dark:bg-gray-700 dark:text-white border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:border-blue-500 dark:focus:border-blue-600 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-600 shadow-sm"
                       autocomplete="off"
                       id="product-search-input"
                       x-on:keydown.arrow-down.prevent="$wire.navigateResults('down')"
                       x-on:keydown.arrow-up.prevent="$wire.navigateResults('up')"
                       x-on:keydown.enter.prevent="$wire.selectCurrentProduct()"
                       x-on:keydown.escape.prevent="$wire.clearSearch()"

                <!-- Dropdown de resultados mejorado -->
                @if(!empty($currentSearch) && count($searchResults) > 0)
                    <div class="absolute z-50 w-full bg-white dark:bg-gray-800 border-2 border-blue-500 dark:border-blue-600 rounded-lg shadow-2xl max-h-60 overflow-y-auto mt-1">
                        @foreach($searchResults as $index => $product)
                            @php
                                $hasStock = $product['stock'] > 0;
                                $isSelected = $selectedIndex === $index;
                            @endphp
                            <div class="px-3 py-3 border-b border-gray-200 dark:border-gray-700 last:border-b-0 transition-colors duration-150
                                        {{ $hasStock ? 'cursor-pointer' : 'cursor-not-allowed opacity-60' }}
                                        {{ $isSelected && $hasStock ? 'bg-blue-100 dark:bg-blue-900/50 ring-2 ring-blue-500 dark:ring-blue-400' : '' }}
                                        {{ $hasStock && !$isSelected ? 'hover:bg-blue-50 dark:hover:bg-gray-700' : '' }}
                                        {{ !$hasStock ? 'bg-red-50 dark:bg-red-900/20' : '' }}"
                                 {{ $hasStock ? 'wire:click=selectProduct(' . $product['id'] . ')' : '' }}>
                                <div class="font-semibold text-gray-800 dark:text-gray-200 text-sm mb-1 {{ !$hasStock ? 'line-through' : '' }}">
                                    {{ $product['name'] }}
                                </div>
                                <div class="flex justify-between items-center text-xs">
                                    <span class="text-gray-600 dark:text-gray-400">SKU: {{ $product['sku'] ?? 'N/A' }}</span>
                                    @if($hasStock)
                                        <span class="text-green-600 dark:text-green-400 font-medium">Stock: {{ number_format($product['stock'], 0) }}</span>
                                    @else
                                        <span class="text-red-600 dark:text-red-400 font-medium">Sin Stock</span>
                                    @endif
                                </div>
                                <div class="text-blue-600 dark:text-blue-400 font-bold text-sm mt-1">
                                    ${{ number_format($product['price'], 0, '.', '.') }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Sugerencias adicionales mejoradas -->
            @if(!empty($currentSearch) && count($additionalSuggestions) > 0)
                <div class="mt-2 space-y-2">
                    <div class="text-xs text-gray-500 dark:text-gray-400 font-semibold px-2">Más resultados:</div>
                    @foreach($additionalSuggestions as $product)
                        @php
                            $hasStock = $product['stock'] > 0;
                        @endphp
                        <div class="bg-gray-50 dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg px-3 py-2 transition-all duration-150
                                    {{ $hasStock ? 'cursor-pointer hover:bg-blue-50 dark:hover:bg-gray-700 hover:border-blue-400 dark:hover:border-blue-500' : 'cursor-not-allowed opacity-60 bg-red-50 dark:bg-red-900/20' }}"
                             {{ $hasStock ? 'wire:click=selectProduct(' . $product['id'] . ')' : '' }}>
                            <div class="font-medium text-gray-800 dark:text-gray-200 text-sm {{ !$hasStock ? 'line-through' : '' }}">{{ $product['name'] }}</div>
                            <div class="flex justify-between items-center mt-1">
                                @if($hasStock)
                                    <span class="text-xs text-gray-600 dark:text-gray-400">Stock: {{ number_format($product['stock'], 0) }}</span>
                                @else
                                    <span class="text-xs text-red-600 dark:text-red-400">Sin Stock</span>
                                @endif
                                <span class="text-xs text-blue-600 dark:text-blue-400 font-semibold">${{ number_format($product['price'], 0, '.', '.') }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <!-- Contenido principal -->
    <div class="p-3">
        <!-- Headers de la tabla -->
        <div class="hidden lg:grid lg:grid-cols-5 gap-2 mb-2 text-xs font-semibold text-gray-700 dark:text-gray-300">
            <div>Producto</div>
            <div class="text-center">Precio Unit.</div>
            <div class="text-center">Cant.</div>
            <div class="text-right">Subtotal</div>
            <div class="text-center">Acción</div>
        </div>
        

        <!-- Productos en el carrito -->
        <div class="space-y-2 mb-3">
            @foreach($cartItems as $index => $item)
                <!-- Vista Desktop -->
                <div class="hidden lg:grid lg:grid-cols-5 gap-2 items-center text-sm bg-gray-50 dark:bg-gray-800 p-2 rounded border border-gray-200 dark:border-gray-700">
                    <!-- Nombre del producto -->
                    <div class="text-xs font-medium text-gray-800 dark:text-gray-200">
                        {{ $item['name'] }}
                        <div class="text-xs text-gray-500 dark:text-gray-400">SKU: {{ $item['sku'] ?? 'N/A' }}</div>
                    </div>

                    <!-- Precio Unitario Editable -->
                    <div class="text-center">
                        <input type="number"
                               value="{{ number_format($item['price'], 0, '.', '') }}"
                               wire:change="updatePrice({{ $item['id'] }}, $event.target.value)"
                               class="w-28 text-center bg-white dark:bg-gray-700 dark:text-white border border-gray-300 dark:border-gray-600 rounded px-3 py-2 text-sm font-semibold focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-600"
                               min="0"
                               step="1">
                    </div>

                    <!-- Cantidad -->
                    <div class="text-center">
                        <input type="number"
                               value="{{ $item['quantity'] }}"
                               wire:change="updateQuantity({{ $item['id'] }}, $event.target.value)"
                               class="w-20 text-center bg-white dark:bg-gray-700 dark:text-white border border-gray-300 dark:border-gray-600 rounded px-3 py-2 text-sm font-semibold focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-600"
                               min="0">
                    </div>

                    <!-- Subtotal -->
                    <div class="text-right font-semibold text-gray-800 dark:text-gray-200 text-sm">
                        ${{ number_format($item['subtotal'], 0, '.', '.') }}
                    </div>

                    <!-- Botón Eliminar -->
                    <div class="text-center">
                        <button wire:click="removeFromCart({{ $item['id'] }})"
                                class="bg-red-500 hover:bg-red-600 dark:bg-red-600 dark:hover:bg-red-700 text-white rounded px-3 py-1 text-xs transition-colors duration-150">
                                Eliminar
                        </button>
                    </div>
                </div>
                
                <!-- Vista Móvil - Nombre completo arriba, controles abajo -->
                <div class="lg:hidden">
                    <div class="bg-gray-50 dark:bg-gray-800 p-2 rounded border border-gray-200 dark:border-gray-700 space-y-2">
                        <!-- Fila 1: Nombre del producto con SKU y Stock (ocupa todo el ancho) -->
                        <div class="w-full">
                            <div class="font-medium text-gray-800 dark:text-gray-200 text-sm">
                                {{ $item['name'] }}
                            </div>
                            <div class="text-[10px] text-gray-500 dark:text-gray-400 mt-0.5">
                                SKU: {{ $item['sku'] ?? 'N/A' }}
                                @if(isset($item['stock']))
                                    <span class="ml-2">| Stock: {{ number_format($item['stock'], 0) }}</span>
                                @endif
                            </div>
                        </div>

                        <!-- Fila 2: Controles (Precio, Cantidad, Subtotal, Eliminar) - Mejor distribuidos -->
                        <div class="flex items-end gap-2">
                            <!-- Precio (35% del espacio) -->
                            <div class="flex-[0.40]">
                                <div class="text-[10px] text-gray-500 dark:text-gray-400 mb-1">Precio</div>
                                <input type="number"
                                       value="{{ number_format($item['price'], 0, '.', '') }}"
                                       wire:change="updatePrice({{ $item['id'] }}, $event.target.value)"
                                       class="w-full text-center bg-white dark:bg-gray-700 dark:text-white border border-gray-300 dark:border-gray-600 rounded px-2 py-2 text-sm font-semibold"
                                       min="0"
                                       step="1">
                            </div>

                            <!-- Cantidad (20% del espacio) -->
                            <div class="flex-[0.2]">
                                <div class="text-[10px] text-gray-500 dark:text-gray-400 mb-1 text-center">Cant.</div>
                                <input type="number"
                                       value="{{ $item['quantity'] }}"
                                       wire:change="updateQuantity({{ $item['id'] }}, $event.target.value)"
                                       class="w-full text-center bg-white dark:bg-gray-700 dark:text-white border border-gray-300 dark:border-gray-600 rounded px-2 py-2 text-sm font-semibold"
                                       min="0">
                            </div>

                            <!-- Subtotal (30% del espacio) -->
                            <div class="flex-[0.40]">
                                <div class="text-[10px] text-gray-500 dark:text-gray-400 mb-1 text-right">Subtotal</div>
                                <div class="text-right font-bold text-gray-800 dark:text-gray-200 text-sm bg-gray-100 dark:bg-gray-700 rounded px-2 py-2">
                                    ${{ number_format($item['subtotal'], 0, '.', '.') }}
                                </div>
                            </div>

                            <!-- Botón Eliminar (tamaño optimizado) -->
                            <button wire:click="removeFromCart({{ $item['id'] }})"
                                    class="bg-red-500 hover:bg-red-600 dark:bg-red-600 dark:hover:bg-red-700 text-white rounded px-2 py-2 text-sm flex-shrink-0 font-bold">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
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
                @livewire('TAT.Customers.tat-customers-manager', key('customer-modal'))
            </div>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script>
    // Mejorar la funcionalidad del input de búsqueda
    document.addEventListener('livewire:init', () => {
        const searchInput = document.getElementById('product-search-input');

        if (searchInput) {
            // Mantener el foco en el input después de seleccionar un producto
            Livewire.on('product-selected', () => {
                setTimeout(() => {
                    searchInput.focus();
                }, 100);
            });

            // Prevenir que el input pierda el foco cuando se hace clic en el dropdown
            searchInput.addEventListener('blur', (e) => {
                // Solo permitir blur si se hace clic fuera del área de búsqueda
                if (!e.relatedTarget || !e.relatedTarget.closest('.relative')) {
                    setTimeout(() => {
                        searchInput.focus();
                    }, 50);
                }
            });
        }
    });
</script>
@endpush