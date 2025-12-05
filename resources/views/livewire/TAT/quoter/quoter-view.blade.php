<div class="w-full max-w-md mx-auto bg-white rounded-lg shadow-lg overflow-hidden border-2 border-gray-300">
    <!-- Header con botones de estado -->
    <div class="bg-gray-50 p-3 border-b">
        <!-- Títulos de secciones -->
        <div class="flex justify-between text-xs text-gray-600 mb-2">
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
            <button class="w-12 h-12 bg-red-500 text-white rounded border-2 border-red-600 font-bold hover:bg-red-600 transition">
                <span class="text-lg font-bold">S</span>
            </button>
            <button class="w-12 h-12 bg-purple-500 text-white rounded border-2 border-purple-600 font-bold hover:bg-purple-600 transition">
                <span class="text-lg font-bold">F</span>
            </button>
            <button class="w-12 h-12 bg-orange-500 text-white rounded border-2 border-orange-600 font-bold hover:bg-orange-600 transition">
                <span class="text-lg font-bold">H</span>
            </button>
        </div>

        <!-- Total -->
        <div class="bg-black text-white p-3 rounded mb-3">
            <div class="text-right text-2xl font-bold text-white">{{ number_format($total, 0, '.', '.') }}</div>
        </div>

        <!-- Cliente -->
        <div class="bg-green-100 p-2 rounded">
            <div class="text-green-700 font-mono text-sm font-bold">
                {{ $clientNumber }}
            </div>
            <div class="text-xs text-green-600">Consumidor final</div>
        </div>
    </div>

    <!-- Contenido principal -->
    <div class="p-3">
        <!-- Headers de la tabla -->
        <div class="grid grid-cols-3 gap-1 mb-2 text-xs font-semibold text-gray-700">
            <div>Producto</div>
            <div class="text-center">Cant.</div>
            <div class="text-right">Subtotal</div>
        </div>

        <!-- Productos en el carrito -->
        <div class="space-y-1 mb-3">
            @foreach($cartItems as $index => $item)
                <div class="grid grid-cols-3 gap-1 text-sm">
                    <!-- Nombre del producto -->
                    <div class="bg-white border border-gray-300 rounded px-2 py-1 text-xs">
                        {{ $item['name'] }}
                    </div>

                    <!-- Cantidad -->
                    <div class="text-center">
                        <input type="number"
                               value="{{ $item['quantity'] }}"
                               wire:change="updateQuantity({{ $item['id'] }}, $event.target.value)"
                               class="w-8 text-center bg-white border border-gray-300 rounded px-1 py-1 text-xs"
                               min="0">
                    </div>

                    <!-- Subtotal -->
                    <div class="bg-white border border-gray-300 rounded px-2 py-1 text-right text-xs">
                        {{ number_format($item['subtotal'], 0, '.', '.') }}
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Input de búsqueda dinámico -->
        <div class="space-y-1">
            <!-- Siempre mostrar un input activo para nueva búsqueda -->
            <div class="relative">
                <input type="text"
                       wire:model.live.debounce.300ms="currentSearch"
                       placeholder="Buscar producto..."
                       class="w-full px-2 py-1 border border-gray-300 rounded text-xs focus:outline-none focus:border-blue-500"
                       autocomplete="off">

                <!-- Dropdown de resultados -->
                @if(!empty($currentSearch) && count($searchResults) > 0)
                    <div class="absolute z-50 w-full bg-white border border-gray-300 rounded-b shadow-lg max-h-32 overflow-y-auto">
                        @foreach($searchResults as $product)
                            <div class="px-2 py-1 hover:bg-gray-100 cursor-pointer text-xs border-b"
                                 wire:click="selectProduct({{ $product->id }})">
                                <div class="font-medium">{{ $product->name }}</div>
                                <div class="text-gray-500 text-xs">
                                    Stock: {{ number_format($product->stock, 0) }} | ${{ number_format($product->price, 0) }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Mostrar productos adicionales como texto solo si hay búsqueda activa -->
            @if(!empty($currentSearch) && count($additionalSuggestions) > 0)
                <div class="space-y-1">
                    @foreach($additionalSuggestions as $product)
                        <div class="text-xs text-gray-600 cursor-pointer hover:text-blue-600 border rounded px-2 py-1"
                             wire:click="selectProduct({{ $product->id }})">
                            {{ $product->name }}
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>
</div>

@push('scripts')
<script>
    // Limpiar input después de seleccionar
    document.addEventListener('livewire:init', () => {
        Livewire.on('product-selected', () => {
            // El input se limpiará automáticamente desde el componente
        });
    });
</script>
@endpush