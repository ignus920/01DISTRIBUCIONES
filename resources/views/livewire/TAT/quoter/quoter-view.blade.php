<div class="min-h-screen bg-gray-50 dark:bg-gray-900 p-6">
    <div class="max-w-12xl mx-auto">
    <!-- Header con botones de estado -->
    <div class="bg-gray-50 dark:bg-gray-800 p-3 border-b dark:border-gray-700">
     

        <!-- Total -->
        <div class="bg-black dark:bg-gray-950 text-white p-4 lg:p-6 rounded mb-3 ring-2 ring-blue-500 dark:ring-blue-600">
            <div class="text-right lg:text-center text-2xl lg:text-4xl font-bold text-white">${{ number_format($total, 0, '.', '.') }}</div>
        </div>

        <!-- Cliente -->
        <div class="bg-green-100 dark:bg-green-900/30 p-3 lg:p-4 rounded border border-green-200 dark:border-green-700">
            @if($showClientSearch)
                <!-- Modo búsqueda de cliente -->
                <div class="space-y-2">
                    <div class="flex gap-1">
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
                    <div>
                        @if($selectedCustomer)
                            <div class="text-green-700 dark:text-green-300 font-mono text-sm lg:text-lg font-bold">
                                {{ $selectedCustomer['identification'] }}
                            </div>
                            <div class="text-xs lg:text-sm text-green-600 dark:text-green-400">{{ $selectedCustomer['display_name'] }}</div>
                        @else
                            <div class="text-green-700 dark:text-green-300 font-mono text-sm lg:text-lg font-bold">
                                Sin cliente
                            </div>
                            <div class="text-xs lg:text-sm text-green-600 dark:text-green-400">Seleccionar cliente</div>
                        @endif
                    </div>
                    <button
                        wire:click="enableClientSearch"
                        class="px-2 py-1 bg-green-600 text-white text-xs rounded hover:bg-green-700 flex items-center justify-center"
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
            <!-- Siempre mostrar un input activo para nueva búsqueda -->
            <div class="relative">
                <input type="text"
                       wire:model.live.debounce.150ms="currentSearch"
                       placeholder="Buscar producto... (Use ↑↓ para navegar, Enter para seleccionar)"
                       class="w-full px-3 py-2 lg:px-4 lg:py-3 text-sm lg:text-base bg-white dark:bg-gray-700 dark:text-white border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:border-blue-500 dark:focus:border-blue-600 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-600 shadow-sm"
                       autocomplete="off"
                       id="product-search-input"
                       onkeydown="handleProductSearchKeydown(event)"

                <!-- Dropdown de resultados mejorado -->
                @if(!empty($currentSearch) && count($searchResults) > 0)
                    <div id="productSearchResults" class="absolute z-50 w-full bg-white dark:bg-gray-800 border-2 border-blue-500 dark:border-blue-600 rounded-lg shadow-2xl max-h-60 overflow-y-auto mt-1">
                        @foreach($searchResults as $index => $product)
                            @php
                                $hasStock = $product['stock'] > 0;
                                $isSelected = $selectedIndex === $index;
                            @endphp
                            <div class="product-result px-3 py-3 border-b border-gray-200 dark:border-gray-700 last:border-b-0 transition-colors duration-75
                                        {{ $hasStock ? 'cursor-pointer' : 'cursor-not-allowed opacity-60' }}
                                        {{ $isSelected && $hasStock ? 'bg-blue-100 dark:bg-blue-900/50 ring-2 ring-blue-500 dark:ring-blue-400' : '' }}
                                        {{ $hasStock && !$isSelected ? 'hover:bg-blue-50 dark:hover:bg-gray-700' : '' }}
                                        {{ !$hasStock ? 'bg-red-50 dark:bg-red-900/20' : '' }}"
                                 data-product-id="{{ $product['id'] }}"
                                 data-index="{{ $index }}"
                                 data-has-stock="{{ $hasStock ? 'true' : 'false' }}"
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

        </div>
    </div>

    <!-- Contenido principal -->
    <div class="p-3">
        <!-- Headers de la tabla -->
        <div class="hidden lg:grid lg:grid-cols-7 gap-2 mb-3 lg:mb-4 text-sm lg:text-lg font-semibold text-gray-700 dark:text-gray-300 lg:py-2">
            <div class="col-span-2">Producto</div>
            <div class="text-center">IVA</div>
            <div class="text-center">Precio Unit.</div>
            <div class="text-center">Cant.</div>
            <div class="text-right">Subtotal</div>
            <div class="text-center">Eliminar</div>
        </div>
        

        <!-- Productos en el carrito -->
        <div class="space-y-2 mb-3">
            @foreach($cartItems as $index => $item)
                <!-- Vista Desktop -->
                <div wire:key="cart-item-desktop-{{ $item['id'] }}" class="hidden lg:grid lg:grid-cols-7 gap-2 items-center text-sm lg:text-base bg-gray-50 dark:bg-gray-800 p-3 lg:p-4 rounded border border-gray-200 dark:border-gray-700">
                    <!-- Nombre del producto -->
                    <div class="col-span-2 text-sm lg:text-base font-medium text-gray-800 dark:text-gray-200">
                        {{ $item['name'] }}
                        <div class="text-xs lg:text-sm text-gray-500 dark:text-gray-400">SKU: {{ $item['sku'] ?? 'N/A' }}</div>
                    </div>

                    <!-- IVA -->
                    <div class="text-center text-xs lg:text-sm text-gray-600 dark:text-gray-400">
                        {{ $item['tax_name'] ?? 'N/A' }}
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
                               wire:model.live.debounce.500ms="cartItems.{{ $index }}.quantity"
                               class="w-20 text-center bg-white dark:bg-gray-700 dark:text-white border border-gray-300 dark:border-gray-600 rounded px-3 py-2 text-sm font-semibold focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-600"
                               min="1"
                               step="1"
                               pattern="\d*">
                    </div>

                    <!-- Subtotal -->
                    <div class="text-right font-semibold text-gray-800 dark:text-gray-200 text-sm">
                        ${{ number_format($item['subtotal'], 0, '.', '.') }}
                    </div>

                    <!-- Botón Eliminar -->
                    <div class="text-center">
                        <button wire:click="removeFromCart({{ $item['id'] }})"
                                class="bg-red-500 hover:bg-red-600 dark:bg-red-600 dark:hover:bg-red-700 text-white rounded px-3 py-1 text-xs transition-colors duration-150">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                        </button>
                    </div>
                </div>
                
                <!-- Vista Móvil - Nombre completo arriba, controles abajo -->
                <div wire:key="cart-item-mobile-{{ $item['id'] }}" class="lg:hidden">
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
                                <span class="ml-2 font-semibold text-blue-600 dark:text-blue-400">| IVA: {{ $item['tax_name'] ?? 'N/A' }}</span>
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
                                       wire:model.live.debounce.500ms="cartItems.{{ $index }}.quantity"
                                       class="w-full text-center bg-white dark:bg-gray-700 dark:text-white border border-gray-300 dark:border-gray-600 rounded px-2 py-2 text-sm font-semibold"
                                       min="1"
                                       step="1"
                                       pattern="\d*">
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

        <!-- Botones de acción -->
        @if(count($cartItems) > 0)
        <div class="border-t border-gray-200 dark:border-gray-700 pt-3 mb-3">
            <div class="flex flex-col sm:flex-row gap-2 justify-center">
                <button
                    wire:click="saveQuote"
                    wire:loading.attr="disabled"
                    wire:target="saveQuote"
                    class="flex-1 sm:flex-none px-6 py-3 bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 text-white rounded-lg font-semibold text-sm transition-colors duration-150 shadow-md hover:shadow-lg flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">

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
                    <span wire:loading.remove wire:target="saveQuote">Registrar Venta</span>

                    <!-- Texto cuando está cargando -->
                    <span wire:loading wire:target="saveQuote">Guardando...</span>
                <button
                    wire:click="clearCart"
                    class="flex-1 sm:flex-none px-4 py-3 bg-red-500 hover:bg-red-600 dark:bg-red-600 dark:hover:bg-red-700 text-white rounded-lg font-semibold text-sm transition-colors duration-150 shadow-md hover:shadow-lg flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                    </svg>
                    Limpiar todo
                </button>
            </div>

            <!-- Información adicional -->
            <div class="mt-2 text-center">
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

    // Función para manejar navegación por teclado en búsqueda de productos (optimizada)
    function handleProductSearchKeydown(event) {
        const resultsContainer = document.getElementById('productSearchResults');

        if (!resultsContainer) return;

        productResults = Array.from(resultsContainer.querySelectorAll('.product-result'));

        if (productResults.length === 0) return;

        // Filtrar solo productos con stock para navegación
        const availableProducts = productResults.filter(result =>
            result.dataset.hasStock === 'true'
        );

        if (availableProducts.length === 0) return;

        switch(event.key) {
            case 'ArrowDown':
                event.preventDefault();
                // Encontrar el siguiente producto disponible
                let nextIndex = -1;
                for (let i = selectedProductIndex + 1; i < productResults.length; i++) {
                    if (productResults[i].dataset.hasStock === 'true') {
                        nextIndex = i;
                        break;
                    }
                }
                if (nextIndex === -1) {
                    // Si llegamos al final, ir al primer producto disponible
                    nextIndex = productResults.findIndex(result => result.dataset.hasStock === 'true');
                }
                selectedProductIndex = nextIndex;
                updateProductSelection();
                break;

            case 'ArrowUp':
                event.preventDefault();
                // Encontrar el producto anterior disponible
                let prevIndex = -1;
                for (let i = selectedProductIndex - 1; i >= 0; i--) {
                    if (productResults[i].dataset.hasStock === 'true') {
                        prevIndex = i;
                        break;
                    }
                }
                if (prevIndex === -1) {
                    // Si llegamos al inicio, ir al último producto disponible
                    for (let i = productResults.length - 1; i >= 0; i--) {
                        if (productResults[i].dataset.hasStock === 'true') {
                            prevIndex = i;
                            break;
                        }
                    }
                }
                selectedProductIndex = prevIndex;
                updateProductSelection();
                break;

            case 'Enter':
                event.preventDefault();
                if (selectedProductIndex >= 0 && productResults[selectedProductIndex] &&
                    productResults[selectedProductIndex].dataset.hasStock === 'true') {
                    const productId = productResults[selectedProductIndex].dataset.productId;
                    // Disparar el evento de Livewire para seleccionar producto
                    Livewire.find('{{ $this->getId() }}').call('selectProduct', productId);
                }
                break;

            case 'Escape':
                event.preventDefault();
                selectedProductIndex = -1;
                updateProductSelection();
                // Limpiar búsqueda
                Livewire.find('{{ $this->getId() }}').call('clearSearch');
                break;
        }
    }

    // Función para actualizar la selección visual de productos (optimizada)
    function updateProductSelection() {
        productResults.forEach((result, index) => {
            result.classList.remove('bg-blue-100', 'dark:bg-blue-900/50', 'ring-2', 'ring-blue-500', 'dark:ring-blue-400');

            if (index === selectedProductIndex && result.dataset.hasStock === 'true') {
                result.classList.add('bg-blue-100', 'dark:bg-blue-900/50', 'ring-2', 'ring-blue-500', 'dark:ring-blue-400');
                // Scroll hacia el elemento seleccionado si está fuera de vista
                result.scrollIntoView({
                    block: 'nearest',
                    behavior: 'smooth'
                });
            }
        });
    }
</script>
@endpush
</div>