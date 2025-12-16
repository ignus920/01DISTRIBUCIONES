<div x-data="{ 
    messageTimeout: null,
    clearMessageAfterDelay() {
        if (this.messageTimeout) clearTimeout(this.messageTimeout);
        this.messageTimeout = setTimeout(() => {
            @this.call('clearMessages');
        }, 3000);
    }
}" 
x-init="
    $watch('$wire.showModal', value => {
        if (value) {
            clearMessageAfterDelay();
        }
    });
    Livewire.on('order-updated', () => {
        clearMessageAfterDelay();
    });
">
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
        <div class="relative min-h-screen flex items-start justify-center p-4 pt-12">
            <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-6xl w-full max-h-[90vh] overflow-y-auto"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">

                <!-- Header -->
                <div class="border-b border-gray-200 dark:border-gray-700 px-6 py-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Orden de Clientes en Ruta
                    </h3>
                    <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Messages -->
                @if (session()->has('message'))
                <div class="mx-6 mt-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300 px-4 py-3 rounded-lg">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        {{ session('message') }}
                    </div>
                </div>
                @endif

                @if (session()->has('error'))
                <div class="mx-6 mt-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 px-4 py-3 rounded-lg">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        {{ session('error') }}
                    </div>
                </div>
                @endif

                <!-- Content -->
                <div class="p-6">
                    <!-- Filter Section -->
                    <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center gap-2 mb-3">
                            <svg class="w-5 h-5 text-cyan-600 dark:text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            <h5 class="text-sm font-semibold text-gray-900 dark:text-white">
                                Selecciona una ruta para ver y reordenar sus clientes
                            </h5>
                        </div>
                        <div class="max-w-md">
                            @livewire('selects.route-select', [
                                'selectedValue' => $filterRouteId,
                                'eventName' => 'filter-route-changed',
                                'placeholder' => 'Seleccione una ruta'
                            ], key('filter-route-select'))
                        </div>
                    </div>

                    @if($filterRouteId && $items->count() > 0)
                    <!-- Instructions -->
                    <div class="mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                        <p class="text-sm text-blue-700 dark:text-blue-300 flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                            </svg>
                            Usa las flechas o cambia el número de orden directamente para reordenar los clientes
                        </p>
                    </div>

                    <!-- Table -->
                    <div class="overflow-x-auto rounded-lg border border-gray-200 dark:border-gray-700">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-20">
                                        Orden
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Cliente
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Identificación
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                        Zona
                                    </th>
                                    <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider w-32">
                                        Acciones
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach($items as $index => $item)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors" wire:key="row-{{ $item->id }}-{{ $item->sales_order }}">
                                    <td class="px-6 py-4 text-center">
                                        <input 
                                            type="number" 
                                            wire:change="updateOrder({{ $item->id }}, $event.target.value)"
                                            value="{{ $item->sales_order }}"
                                            min="1"
                                            max="{{ $items->count() }}"
                                            class="w-16 text-center rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-cyan-500 focus:border-cyan-500 text-sm font-semibold"
                                            wire:key="input-{{ $item->id }}-{{ $item->sales_order }}"
                                        />
                                    </td>
                                    <td class="px-6 py-4 text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $item->company->businessName && trim($item->company->businessName) !== '' ? $item->company->businessName : $item->company->firstName . ' ' . $item->company->lastName }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                        {{ $item->company->identification ?? 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-500 dark:text-gray-400">
                                        {{ $item->route->zones->name ?? 'N/A' }}
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <!-- Botón Subir -->
                                            <button 
                                                wire:click="moveUp({{ $item->id }})"
                                                @if($index === 0) disabled @endif
                                                class="p-1 rounded {{ $index === 0 ? 'text-gray-300 dark:text-gray-600 cursor-not-allowed' : 'text-cyan-600 hover:text-cyan-800 dark:text-cyan-400 dark:hover:text-cyan-300 hover:bg-cyan-50 dark:hover:bg-cyan-900/20' }} transition-colors"
                                                title="Subir">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>
                                                </svg>
                                            </button>
                                            
                                            <!-- Botón Bajar -->
                                            <button 
                                                wire:click="moveDown({{ $item->id }})"
                                                @if($index === $items->count() - 1) disabled @endif
                                                class="p-1 rounded {{ $index === $items->count() - 1 ? 'text-gray-300 dark:text-gray-600 cursor-not-allowed' : 'text-cyan-600 hover:text-cyan-800 dark:text-cyan-400 dark:hover:text-cyan-300 hover:bg-cyan-50 dark:hover:bg-cyan-900/20' }} transition-colors"
                                                title="Bajar">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @elseif($filterRouteId && $items->count() === 0)
                    <!-- No results -->
                    <div class="py-12 text-center">
                        <svg class="w-16 h-16 mx-auto mb-4 text-gray-400 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                        </svg>
                        <p class="text-lg font-medium text-gray-500 dark:text-gray-400">
                            No hay clientes asignados a esta ruta
                        </p>
                    </div>
                    @else
                    <!-- Empty state -->
                    <div class="py-12 text-center">
                        <svg class="w-16 h-16 mx-auto mb-4 text-gray-400 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"></path>
                        </svg>
                        <p class="text-lg font-medium text-gray-500 dark:text-gray-400">
                            Selecciona una ruta para comenzar
                        </p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
