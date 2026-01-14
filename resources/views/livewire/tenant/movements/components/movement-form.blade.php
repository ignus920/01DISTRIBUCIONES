<div>
    <style>
        /* Estilos personalizados para SweetAlert2 en dark mode */
        .swal2-dark {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.5), 0 4px 6px -2px rgba(0, 0, 0, 0.3) !important;
        }
        
        .swal2-dark .swal2-title {
            color: #f9fafb !important;
        }
        
        .swal2-dark .swal2-html-container {
            color: #e5e7eb !important;
        }
        
        .swal2-timer-dark {
            background: rgba(255, 255, 255, 0.2) !important;
        }
        
        .swal2-light .swal2-timer-progress-bar {
            background: rgba(79, 70, 229, 0.8) !important;
        }
        
        /* Mejorar el ícono de éxito en dark mode */
        .swal2-dark .swal2-icon.swal2-success [class^='swal2-success-line'] {
            background-color: currentColor !important;
        }
        
        .swal2-dark .swal2-icon.swal2-success .swal2-success-ring {
            border-color: currentColor !important;
            opacity: 0.3;
        }
        
        /* Animaciones suaves */
        .swal2-popup {
            transition: all 0.3s ease-in-out !important;
        }
    </style>

    @if(!$reusable)
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 p-6">
        <div class="max-w-12xl mx-auto">
            <!-- Header -->
            <div
                class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                            Gestión de Movimientos
                            <span class="text-xl font-semibold text-gray-700 dark:text-gray-300">
                                | {{ $this->warehouseMovement }}
                            </span>
                        </h1>
                        <p class="text-gray-600 dark:text-gray-400 mt-1">Administración de movimientos del sistema</p>
                    </div>
                    <div
                        class="flex flex-col sm:flex-row items-start sm:items-start justify-start sm:justify-between gap-4">
                        <button wire:click="create"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4v16m8-8H4"></path>
                            </svg>
                            Agregar
                        </button>
                        <div
                            class="flex flex-col sm:flex-row items-start sm:items-start justify-start sm:justify-between gap-4">
                            <button wire:click="$set('movementType', 'entrada')"
                                class="inline-flex items-center px-4 py-2 rounded-lg font-semibold text-xs uppercase transition-all duration-200 {{ $movementType === 'entrada' ? 'bg-green-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m0 0l-4-4m4 4l4-4"></path>
                                </svg>
                                Entradas
                            </button>
                            <button wire:click="$set('movementType', 'salida')"
                                class="inline-flex items-center px-4 py-2 rounded-lg font-semibold text-xs uppercase transition-all duration-200 {{ $movementType === 'salida' ? 'bg-red-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-200 dark:hover:bg-gray-600' }}">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 20V4m0 0l4 4m-4-4l-4 4"></path>
                                </svg>
                                Salidas
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Movement List Component -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                    {{ $movementType === 'entrada' ? 'Movimientos de Entrada' : 'Movimientos de Salida' }}
                </h2>
                @livewire('tenant.movements.components.movement-list', ['type' => $movementType], key($movementType))
            </div>
        </div>

        <!-- Modal -->
        @if($showModal)
        <div
            class="fixed inset-0 bg-gray-600 dark:bg-gray-900 bg-opacity-50 dark:bg-opacity-75 overflow-y-auto h-full w-full z-50">
            <div class="relative min-h-screen flex items-center justify-center p-4">
                <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-4xl w-full">
                    <!-- Header -->
                    <div
                        class="border-b border-gray-200 dark:border-gray-700 px-6 py-4 flex justify-between items-center">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            Nuevo Movimiento
                            <span class="text-base font-medium text-gray-700 dark:text-gray-300">
                                | {{ $this->warehouseMovement }}
                            </span>
                        </h3>
                        <button wire:click="closeModal"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Content -->
                    <div class="p-6">
                        <!-- Messages -->
                        @if($successMessage)
                        <div
                            class="mb-4 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                            <p class="text-sm text-green-700 dark:text-green-400">{{ $successMessage }}</p>
                        </div>
                        @endif

                        @if($errorMessage)
                        <div
                            class="mb-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                            <p class="text-sm text-red-700 dark:text-red-400">{{ $errorMessage }}</p>
                        </div>
                        @endif

                        <!-- Form -->
                        <div class="space-y-4">
                            <!-- Tipo de Movimiento -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Bodegas -->
                                @if($showSelectStore)
                                <div>
                                    @livewire('selects.generic-select', [
                                        'selectedValue' => $selectedStoreId,
                                        'items' => $this->stores,
                                        'name' => 'selectedStoreId',
                                        'placeholder' => 'Seleccionar',
                                        'label' => 'Bodega',
                                        'required' => true,
                                        'showLabel' => true,
                                        'class' => 'w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400',
                                        'eventName' => 'storeSelected',
                                        'displayField' => 'name',
                                        'valueField' => 'id',
                                        'searchFields' => ['name']
                                    ], key('store-select-' . now()->timestamp))
                                </div>
                                @endif

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        Tipo de Movimiento <span class="text-red-500">*</span>
                                    </label>
                                    <div class="flex gap-4 justify-center">
                                        <label
                                            class="flex items-center {{ !empty($warehouseForm['movementType']) ? 'cursor-not-allowed' : 'cursor-pointer' }}">
                                            <input type="radio" wire:model.live="warehouseForm.movementType"
                                                value="ENTRADA" class="sr-only peer" {{
                                                !empty($warehouseForm['movementType']) ? 'disabled' : '' }}>
                                            <div
                                                class="flex items-center px-4 py-2 border-2 rounded-lg transition-all border-gray-300 dark:border-gray-600 peer-checked:border-green-500 peer-checked:bg-green-50 dark:peer-checked:bg-green-900/20 {{ !empty($warehouseForm['movementType']) ? 'opacity-75' : '' }}">
                                                <svg class="w-5 h-5 mr-2 text-green-600 dark:text-green-400" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                                                </svg>
                                                <span
                                                    class="text-sm font-medium text-gray-700 dark:text-gray-300">Entradas</span>
                                            </div>
                                        </label>
                                        <label
                                            class="flex items-center {{ !empty($warehouseForm['movementType']) ? 'cursor-not-allowed' : 'cursor-pointer' }}">
                                            <input type="radio" wire:model.live="warehouseForm.movementType"
                                                value="SALIDA" class="sr-only peer" {{
                                                !empty($warehouseForm['movementType']) ? 'disabled' : '' }}>
                                            <div
                                                class="flex items-center px-4 py-2 border-2 rounded-lg transition-all border-gray-300 dark:border-gray-600 peer-checked:border-red-500 peer-checked:bg-red-50 dark:peer-checked:bg-red-900/20 {{ !empty($warehouseForm['movementType']) ? 'opacity-75' : '' }}">
                                                <span
                                                    class="text-sm font-medium text-gray-700 dark:text-gray-300">Salidas</span>
                                                <svg class="w-5 h-5 ml-2 text-red-600 dark:text-red-400" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                                                </svg>
                                            </div>
                                        </label>
                                    </div>
                                    @error('warehouseForm.movementType')
                                    <span class="text-red-500 text-sm mt-1">{{ $message }}</span>
                                    @enderror
                                </div>
                                <!-- Motivo -->
                                <div>
                                    @livewire('selects.generic-select', [
                                        'selectedValue' => $movementForm['reasonId'],
                                        'items' => $this->reasons,
                                        'name' => 'movementForm.reasonId',
                                        'placeholder' => empty($warehouseForm['movementType']) ? 'Primero seleccione el tipo' : 'Seleccionar motivo',
                                        'label' => 'Motivo',
                                        'required' => true,
                                        'showLabel' => true,
                                        'class' => 'w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400',
                                        'eventName' => 'reasonSelected',
                                        'displayField' => 'name',
                                        'valueField' => 'id',
                                        'searchFields' => ['name']
                                    ], key('reason-select-' . now()->timestamp))
                                </div>
                            </div>
                            <!-- Observaciones -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Observaciones
                                </label>
                                <textarea wire:model.defer="movementForm.observations" rows="3"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400"></textarea>
                                @error('movementForm.observations')
                                <span class="text-red-500 text-sm mt-1">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- Items Section (only if reason is selected) -->
                            @if(!empty($movementForm['reasonId']))
                            <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                                <h4 class="font-medium text-gray-900 dark:text-white mb-4">Agregar productos</h4>

                                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Item <span class="text-red-500">*</span></label>
                                        @livewire('selects.generic-select', [
                                            'selectedValue' => $detailForm['itemId'],
                                            'items' => $this->items,
                                            'name' => 'detailForm.itemId',
                                            'placeholder' => 'Seleccionar',
                                            'label' => '',
                                            'required' => true,
                                            'showLabel' => false,
                                            'class' => 'w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400',
                                            'eventName' => 'itemSelected',
                                            'displayField' => 'name',
                                            'valueField' => 'id',
                                            'searchFields' => ['name', 'sku']
                                        ], key('item-select-' . now()->timestamp))
                                        @error('detailForm.itemId') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Cantidad <span class="text-red-500">*</span></label>
                                        <input type="number" step="0.01" wire:model.defer="detailForm.quantity"
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400">
                                        @error('detailForm.quantity') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Unidad <span class="text-red-500">*</span></label>
                                        @livewire('selects.generic-select', [
                                            'selectedValue' => $detailForm['unitMeasurementId'],
                                            'items' => $this->unitMeasurements,
                                            'name' => 'detailForm.unitMeasurementId',
                                            'placeholder' => 'Seleccionar',
                                            'label' => '',
                                            'required' => true,
                                            'showLabel' => false,
                                            'class' => 'w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400',
                                            'eventName' => 'unitMeasurementSelected',
                                            'displayField' => 'description',
                                            'valueField' => 'id',
                                            'searchFields' => ['description']
                                        ], key('unit-select-' . now()->timestamp))
                                        @error('detailForm.unitMeasurementId') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
                                    </div>
                                      @if($warehouseForm['movementType'] == 'ENTRADA' && $movementForm['reasonId'] == 1)
                                       <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Costo <span class="text-red-500">*</span></label>
                                        <input type="number" step="0.01" wire:model.defer="detailForm.cost"
                                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400">
                                        @error('detailForm.cost') <span class="text-red-500 text-sm mt-1">{{ $message }}</span> @enderror
                                    </div>
                                    @endif
                                </div>

                                <button wire:click="addDetail" type="button" 
                                    wire:loading.attr="disabled"
                                    wire:target="addDetail"
                                    {{ $isProcessing ? 'disabled' : '' }}
                                    class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 text-white rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                                    <svg wire:loading.remove wire:target="addDetail" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4v16m8-8H4"></path>
                                    </svg>
                                    <svg wire:loading wire:target="addDetail" class="animate-spin w-4 h-4 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span wire:loading.remove wire:target="addDetail">Agregar Item</span>
                                    <span wire:loading wire:target="addDetail">Agregando...</span>
                                </button>
                            </div>
                            @endif
                            <!-- Details Table -->
                            @if(count($details) > 0)
                            <div class="mt-4 overflow-x-auto">
                                <table class="w-full border border-gray-200 dark:border-gray-700 rounded-lg">
                                    <thead class="bg-gray-50 dark:bg-gray-900">
                                        <tr>
                                            <th
                                                class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                                Item</th>
                                            <th
                                                class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                                Sku</th>
                                            <th
                                                class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                                Cant Movimiento</th>
                                            <th
                                                class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                                Unidad medida</th>
                                            <th
                                                class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                                Unidad consumo</th>
                                            <th
                                                class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                                Cant Actual</th>
                                            <th
                                                class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                                Cant Ajustada</th>
                                            @if($warehouseForm['movementType'] == 'ENTRADA' && $movementForm['reasonId'] == 1)
                                            <th
                                                class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                                Costo Unitario</th>
                                            @endif
                                            <th
                                                class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                                Precio base</th>
                                            <th
                                                class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                                Precio final</th>
                                            <th
                                                class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                                Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody
                                        class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                        @foreach($details as $index => $detail)
                                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                            <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">{{
                                                $detail['itemName'] }}</td>
                                            <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">{{
                                                $detail['sku'] }}</td>
                                            <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">{{
                                                number_format($detail['quantity'], 0) }}</td>
                                            <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">{{
                                                $detail['unitMeasurementName'] }}</td>
                                            <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">{{
                                                $detail['consumptionUnitName'] }}</td>
                                            <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">{{
                                                number_format($detail['currentQuantity'], 0) }}</td>
                                            <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">{{
                                                number_format($detail['adjustedQuantity'], 0) }}</td>
                                            @if($warehouseForm['movementType'] == 'ENTRADA' && $movementForm['reasonId'] == 1)
                                            <td class="px-4 py-2 text-sm text-gray-900 dark:text-white font-medium">
                                                ${{ number_format($detail['cost'] ?? 0, 2) }}</td>
                                            @endif
                                            <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">${{
                                                number_format($detail['price'], 0) }}</td>
                                            <td class="px-4 py-2 text-sm text-gray-900 dark:text-white font-semibold">
                                                ${{ number_format($detail['total'], 0) }}</td>
                                            <td class="px-4 py-2 text-center">
                                                <button wire:click="removeDetail({{ $index }})"
                                                    class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 transition-colors">
                                                    <svg class="w-5 h-5 inline" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                        </path>
                                                    </svg>
                                                </button>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            @endif
                        </div>

                        <!-- Actions -->
                        <div class="mt-6 flex justify-end gap-2">
                            <button wire:click="closeModal" type="button"
                                class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                Cancelar
                            </button>
                            <button wire:click="saveMovement" type="button"
                                class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white rounded-lg transition-colors">
                                Guardar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Modal de Detalles del Movimiento -->
        @if($showDetailsModal && !empty($movementDetails))
        <div class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
                <!-- Header -->
                <div
                    class="sticky top-0 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-6 py-4 flex items-center justify-between">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Detalles del Movimiento #{{ $movementDetails['consecutive'] }}
                    </h3>
                    <button wire:click="closeDetailsModal"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Messages -->
                @if($successMessage)
                <div class="px-6 pt-4">
                    <div class="p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-green-600 dark:text-green-400 mt-0.5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                            <p class="text-sm text-green-700 dark:text-green-400">{{ $successMessage }}</p>
                        </div>
                    </div>
                </div>
                @endif

                @if($errorMessage)
                <div class="px-6 pt-4">
                    <div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                        <div class="flex items-start">
                            <svg class="w-5 h-5 text-red-600 dark:text-red-400 mt-0.5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                            <p class="text-sm text-red-700 dark:text-red-400">{{ $errorMessage }}</p>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Información del Movimiento -->
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Fecha</p>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $movementDetails['date'] }}
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Tipo</p>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $movementDetails['type'] }}
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Bodega</p>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $movementDetails['store_name'] }}
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Usuario</p>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $movementDetails['user_name'] }}
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Razón</p>
                            <p class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $movementDetails['reason_name'] }}
                            </p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Estado</p>
                            @if($movementDetails['status'] === 1)
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                Registrado
                            </span>
                            @else
                            <span
                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                Anulado
                            </span>
                            @endif
                        </div>
                    </div>
                    <div class="mt-4">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Observaciones</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ $movementDetails['observations'] ? $movementDetails['observations'] : 'Sin observaciones'
                            }}
                        </p>
                    </div>
                </div>

                <!-- Tabla de Items -->
                <div class="px-6 py-4">
                    <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Items del Movimiento</h4>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-700">
                                <tr>
                                    <th
                                        class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                        Producto</th>
                                    <th
                                        class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                        Cantidad</th>
                                    <th
                                        class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                        Unidad</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @forelse($movementDetails['details'] as $detail)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                    <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">
                                        {{ $detail['item_name'] }}
                                    </td>
                                    <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">
                                        {{ $detail['quantity'] }}
                                    </td>
                                    <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">
                                        {{ $detail['unit_name'] }}
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3"
                                        class="px-4 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                        No hay items en este movimiento
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

            <!-- Footer -->
            <div class="bg-gray-50 dark:bg-gray-700 px-6 py-4 flex justify-between items-center">
                <div>
                    @if($movementDetails['status'] === 1)
                        <button @click="$dispatch('confirm-annul-from-details', { movementId: {{ $movementDetails['id'] }}, consecutive: '{{ $movementDetails['consecutive'] }}' })"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600 text-white rounded-lg transition-colors text-sm font-medium focus:outline-none focus:ring-2 focus:ring-red-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            Anular Movimiento
                        </button>
                    @endif
                </div>
                <button wire:click="closeDetailsModal" @click="Swal.close()"
                    class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-900 dark:text-white rounded-lg hover:bg-gray-300 dark:hover:bg-gray-500 transition-colors text-sm font-medium">
                    Cerrar
                </button>
            </div>
        </div>
        @endif
    </div>
    @else

    {{-- <button wire:click="create"
        class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150">
        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
        </svg>
        Agregar
    </button> --}}

    <!-- Modal para registrar nuevo movimiento -->
    @if($showModal)
    <div
        class="fixed inset-0 bg-gray-600 dark:bg-gray-900 bg-opacity-50 dark:bg-opacity-75 overflow-y-auto h-full w-full z-50">
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-4xl w-full">
                <!-- Header -->
                <div class="border-b border-gray-200 dark:border-gray-700 px-6 py-4 flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Nuevo Movimiento
                        <span class="text-base font-medium text-gray-700 dark:text-gray-300">
                            | {{ $this->warehouseMovement }}
                        </span>
                    </h3>
                    <button wire:click="closeModal"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Content --> 
                <div class="p-6">
                    <!-- Messages -->
                    @if($successMessage)
                    <div
                        class="mb-4 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                        <p class="text-sm text-green-700 dark:text-green-400">{{ $successMessage }}</p>
                    </div>
                    @endif

                    @if($errorMessage)
                    <div
                        class="mb-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                        <p class="text-sm text-red-700 dark:text-red-400">{{ $errorMessage }}</p>
                    </div>
                    @endif

                    <!-- Form -->
                    <div class="space-y-4">
                        <!-- Tipo de Movimiento -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Bodegas -->
                            @if($showSelectStore)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Bodega
                                    <span class="text-red-500">*</span></label>
                                <select wire:model.live="selectedStoreId" {{ !empty($selectedStoreId) ? 'disabled' : ''
                                    }}
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 {{ !empty($selectedStoreId) ? 'opacity-75 cursor-not-allowed' : '' }}">
                                    <option value="">Seleccionar</option>
                                    @foreach($this->stores as $store)
                                    <option value="{{ $store->id }}">{{ $store->name }}</option>
                                    @endforeach
                                </select>
                                @error('selectedStoreId') <span class="text-red-500 text-sm mt-1">{{ $message }}</span>
                                @enderror
                            </div>
                            @endif

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Tipo de Movimiento <span class="text-red-500">*</span>
                                </label>
                                <div class="flex gap-4 justify-center">
                                    <label
                                        class="flex items-center {{ !empty($warehouseForm['movementType']) ? 'cursor-not-allowed' : 'cursor-pointer' }}">
                                        <input type="radio" wire:model.live="warehouseForm.movementType" value="ENTRADA"
                                            class="sr-only peer" {{ !empty($warehouseForm['movementType']) ? 'disabled'
                                            : '' }}>
                                        <div
                                            class="flex items-center px-4 py-2 border-2 rounded-lg transition-all border-gray-300 dark:border-gray-600 peer-checked:border-green-500 peer-checked:bg-green-50 dark:peer-checked:bg-green-900/20 {{ !empty($warehouseForm['movementType']) ? 'opacity-75' : '' }}">
                                            <svg class="w-5 h-5 mr-2 text-green-600 dark:text-green-400" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                                            </svg>
                                            <span
                                                class="text-sm font-medium text-gray-700 dark:text-gray-300">Entradas</span>
                                        </div>
                                    </label>
                                    <label
                                        class="flex items-center {{ !empty($warehouseForm['movementType']) ? 'cursor-not-allowed' : 'cursor-pointer' }}">
                                        <input type="radio" wire:model.live="warehouseForm.movementType" value="SALIDA"
                                            class="sr-only peer" {{ !empty($warehouseForm['movementType']) ? 'disabled'
                                            : '' }}>
                                        <div
                                            class="flex items-center px-4 py-2 border-2 rounded-lg transition-all border-gray-300 dark:border-gray-600 peer-checked:border-red-500 peer-checked:bg-red-50 dark:peer-checked:bg-red-900/20 {{ !empty($warehouseForm['movementType']) ? 'opacity-75' : '' }}">
                                            <span
                                                class="text-sm font-medium text-gray-700 dark:text-gray-300">Salidas</span>
                                            <svg class="w-5 h-5 ml-2 text-red-600 dark:text-red-400" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                                            </svg>
                                        </div>
                                    </label>
                                </div>
                                @error('warehouseForm.movementType')
                                <span class="text-red-500 text-sm mt-1">{{ $message }}</span>
                                @enderror
                            </div>
                            <!-- Motivo -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Motivo <span class="text-red-500">*</span>
                                </label>
                                <select wire:model.live="movementForm.reasonId" @if(( empty($selectedStoreId) &&
                                    $showSelectStore ) || empty($warehouseForm['movementType']) ||
                                    !empty($movementForm['reasonId'])) disabled @endif
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400">
                                    <option value="">{{ empty($warehouseForm['movementType']) ? 'Primero seleccione el
                                        tipo' : 'Seleccionar motivo' }}</option>
                                    @foreach($this->reasons as $reason)
                                    <option value="{{ $reason->id }}">{{ $reason->name }}</option>
                                    @endforeach
                                </select>
                                @error('movementForm.reasonId')
                                <span class="text-red-500 text-sm mt-1">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <!-- Observaciones -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Observaciones
                            </label>
                            <textarea wire:model.defer="movementForm.observations" rows="3"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400"></textarea>
                            @error('movementForm.observations')
                            <span class="text-red-500 text-sm mt-1">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Items Section (only if reason is selected) -->
                        @if(!empty($movementForm['reasonId']))
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                            <h4 class="font-medium text-gray-900 dark:text-white mb-4">Agregar productos</h4>

                            <div class="grid grid-cols-3 gap-4 mb-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Item
                                        *</label>
                                    <select wire:model.defer="detailForm.itemId"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400">
                                        <option value="">Seleccionar</option>
                                        @foreach($this->items as $item)
                                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('detailForm.itemId') <span class="text-red-500 text-sm mt-1">{{ $message
                                        }}</span> @enderror
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Cantidad
                                        *</label>
                                    <input type="number" step="0.01" wire:model.defer="detailForm.quantity"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400">
                                    @error('detailForm.quantity') <span class="text-red-500 text-sm mt-1">{{ $message
                                        }}</span> @enderror
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Unidad
                                        *</label>
                                    <select wire:model.defer="detailForm.unitMeasurementId"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400">
                                        <option value="">Seleccionar</option>
                                        @foreach($this->unitMeasurements as $unit)
                                        <option value="{{ $unit->id }}">{{ $unit->description }}</option>
                                        @endforeach
                                    </select>
                                    @error('detailForm.unitMeasurementId') <span class="text-red-500 text-sm mt-1">{{
                                        $message }}</span> @enderror
                                </div>
                            </div>

                            <button wire:click="addDetail" type="button"
                                class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 text-white rounded-lg transition-colors">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4"></path>
                                </svg>
                                Agregar Item
                            </button>
                        </div>
                        @endif
                        <!-- Details Table -->
                        @if(count($details) > 0)
                        <div class="mt-4 overflow-x-auto">
                            <table class="w-full border border-gray-200 dark:border-gray-700 rounded-lg">
                                <thead class="bg-gray-50 dark:bg-gray-900">
                                    <tr>
                                        <th
                                            class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                            Item</th>
                                        <th
                                            class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                            Sku</th>
                                        <th
                                            class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                            Cant Movimiento</th>
                                        <th
                                            class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                            Unidad medida</th>
                                        <th
                                            class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                            Unidad consumo</th>
                                        <th
                                            class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                            Cant Actual</th>
                                        <th
                                            class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                            Cant Ajustada</th>
                                        <th
                                            class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                            Precio base</th>
                                        <th
                                            class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                            Precio final</th>
                                        <th
                                            class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                            Acción</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($details as $index => $detail)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">{{
                                            $detail['itemName'] }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">{{ $detail['sku']
                                            }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">{{
                                            number_format($detail['quantity'], 0) }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">{{
                                            $detail['unitMeasurementName'] }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">{{
                                            $detail['consumptionUnitName'] }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">{{
                                            number_format($detail['currentQuantity'], 0) }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">{{
                                            number_format($detail['adjustedQuantity'], 0) }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">${{
                                            number_format($detail['price'], 0) }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-white font-semibold">${{
                                            number_format($detail['total'], 0) }}</td>
                                        <td class="px-4 py-2 text-center">
                                            <button wire:click="removeDetail({{ $index }})"
                                                class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 transition-colors">
                                                <svg class="w-5 h-5 inline" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                    </path>
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif
                    </div>

                    <!-- Actions -->
                    <div class="mt-6 flex justify-end gap-2">
                        <button wire:click="closeModal" type="button"
                            class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            Cancelar
                        </button>
                        <button wire:click="saveMovement" type="button"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white rounded-lg transition-colors">
                            Guardar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Modal de Detalles del Movimiento -->
    @if($showDetailsModal && !empty($movementDetails))
    <div
        class="fixed inset-0 bg-gray-600 dark:bg-gray-900 bg-opacity-50 dark:bg-opacity-75 overflow-y-auto h-full w-full z-50">
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-4xl w-full">
                <!-- Header -->
                <div class="border-b border-gray-200 dark:border-gray-700 px-6 py-4 flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Nuevo Movimiento
                        <span class="text-base font-medium text-gray-700 dark:text-gray-300">
                            | {{ $this->warehouseMovement }}
                        </span>
                    </h3>
                    <button wire:click="closeModal"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Content -->
                <div class="p-6">
                    <!-- Messages -->
                    @if($successMessage)
                    <div
                        class="mb-4 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                        <p class="text-sm text-green-700 dark:text-green-400">{{ $successMessage }}</p>
                    </div>
                    @endif

                    @if($errorMessage)
                    <div
                        class="mb-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                        <p class="text-sm text-red-700 dark:text-red-400">{{ $errorMessage }}</p>
                    </div>
                    @endif

                    <!-- Form -->
                    <div class="space-y-4">
                        <!-- Tipo de Movimiento -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <!-- Bodegas -->
                            @if($showSelectStore)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Bodega
                                    <span class="text-red-500">*</span></label>
                                <select wire:model.live="selectedStoreId" {{ !empty($selectedStoreId) ? 'disabled' : ''
                                    }}
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 {{ !empty($selectedStoreId) ? 'opacity-75 cursor-not-allowed' : '' }}">
                                    <option value="">Seleccionar</option>
                                    @foreach($this->stores as $store)
                                    <option value="{{ $store->id }}">{{ $store->name }}</option>
                                    @endforeach
                                </select>
                                @error('selectedStoreId') <span class="text-red-500 text-sm mt-1">{{ $message }}</span>
                                @enderror
                            </div>
                            @endif

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Tipo de Movimiento <span class="text-red-500">*</span>
                                </label>
                                <div class="flex gap-4 justify-center">
                                    <label
                                        class="flex items-center {{ !empty($warehouseForm['movementType']) ? 'cursor-not-allowed' : 'cursor-pointer' }}">
                                        <input type="radio" wire:model.live="warehouseForm.movementType" value="ENTRADA"
                                            class="sr-only peer" {{ !empty($warehouseForm['movementType']) ? 'disabled'
                                            : '' }}>
                                        <div
                                            class="flex items-center px-4 py-2 border-2 rounded-lg transition-all border-gray-300 dark:border-gray-600 peer-checked:border-green-500 peer-checked:bg-green-50 dark:peer-checked:bg-green-900/20 {{ !empty($warehouseForm['movementType']) ? 'opacity-75' : '' }}">
                                            <svg class="w-5 h-5 mr-2 text-green-600 dark:text-green-400" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                                            </svg>
                                            <span
                                                class="text-sm font-medium text-gray-700 dark:text-gray-300">Entradas</span>
                                        </div>
                                    </label>
                                    <label
                                        class="flex items-center {{ !empty($warehouseForm['movementType']) ? 'cursor-not-allowed' : 'cursor-pointer' }}">
                                        <input type="radio" wire:model.live="warehouseForm.movementType" value="SALIDA"
                                            class="sr-only peer" {{ !empty($warehouseForm['movementType']) ? 'disabled'
                                            : '' }}>
                                        <div
                                            class="flex items-center px-4 py-2 border-2 rounded-lg transition-all border-gray-300 dark:border-gray-600 peer-checked:border-red-500 peer-checked:bg-red-50 dark:peer-checked:bg-red-900/20 {{ !empty($warehouseForm['movementType']) ? 'opacity-75' : '' }}">
                                            <span
                                                class="text-sm font-medium text-gray-700 dark:text-gray-300">Salidas</span>
                                            <svg class="w-5 h-5 ml-2 text-red-600 dark:text-red-400" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M14 5l7 7m0 0l-7 7m7-7H3"></path>
                                            </svg>
                                        </div>
                                    </label>
                                </div>
                                @error('warehouseForm.movementType')
                                <span class="text-red-500 text-sm mt-1">{{ $message }}</span>
                                @enderror
                            </div>
                            <!-- Motivo -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                    Motivo <span class="text-red-500">*</span>
                                </label>
                                <select wire:model.live="movementForm.reasonId" @if(( empty($selectedStoreId) &&
                                    $showSelectStore ) || empty($warehouseForm['movementType']) ||
                                    !empty($movementForm['reasonId'])) disabled @endif
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white disabled:opacity-50 disabled:cursor-not-allowed focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400">
                                    <option value="">{{ empty($warehouseForm['movementType']) ? 'Primero seleccione el
                                        tipo' : 'Seleccionar motivo' }}</option>
                                    @foreach($this->reasons as $reason)
                                    <option value="{{ $reason->id }}">{{ $reason->name }}</option>
                                    @endforeach
                                </select>
                                @error('movementForm.reasonId')
                                <span class="text-red-500 text-sm mt-1">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                        <!-- Observaciones -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Observaciones
                            </label>
                            <textarea wire:model.defer="movementForm.observations" rows="3"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400"></textarea>
                            @error('movementForm.observations')
                            <span class="text-red-500 text-sm mt-1">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Items Section (only if reason is selected) -->
                        @if(!empty($movementForm['reasonId']))
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                            <h4 class="font-medium text-gray-900 dark:text-white mb-4">Agregar productos</h4>

                            <div class="grid grid-cols-3 gap-4 mb-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Item
                                        *</label>
                                    <select wire:model.defer="detailForm.itemId"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400">
                                        <option value="">Seleccionar</option>
                                        @foreach($this->items as $item)
                                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('detailForm.itemId') <span class="text-red-500 text-sm mt-1">{{ $message
                                        }}</span> @enderror
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Cantidad
                                        *</label>
                                    <input type="number" step="0.01" wire:model.defer="detailForm.quantity"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400">
                                    @error('detailForm.quantity') <span class="text-red-500 text-sm mt-1">{{ $message
                                        }}</span> @enderror
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Unidad
                                        *</label>
                                    <select wire:model.defer="detailForm.unitMeasurementId"
                                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400">
                                        <option value="">Seleccionar</option>
                                        @foreach($this->unitMeasurements as $unit)
                                        <option value="{{ $unit->id }}">{{ $unit->description }}</option>
                                        @endforeach
                                    </select>
                                    @error('detailForm.unitMeasurementId') <span class="text-red-500 text-sm mt-1">{{
                                        $message }}</span> @enderror
                                </div>
                            </div>

                            <button wire:click="addDetail" type="button"
                                class="inline-flex items-center px-4 py-2 bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 text-white rounded-lg transition-colors">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4"></path>
                                </svg>
                                Agregar Item
                            </button>
                        </div>
                        @endif
                        <!-- Details Table -->
                        @if(count($details) > 0)
                        <div class="mt-4 overflow-x-auto">
                            <table class="w-full border border-gray-200 dark:border-gray-700 rounded-lg">
                                <thead class="bg-gray-50 dark:bg-gray-900">
                                    <tr>
                                        <th
                                            class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                            Item</th>
                                        <th
                                            class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                            Sku</th>
                                        <th
                                            class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                            Cant Movimiento</th>
                                        <th
                                            class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                            Unidad medida</th>
                                        <th
                                            class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                            Unidad consumo</th>
                                        <th
                                            class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                            Cant Actual</th>
                                        <th
                                            class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                            Cant Ajustada</th>
                                        <th
                                            class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                            Precio base</th>
                                        <th
                                            class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                            Precio final</th>
                                        <th
                                            class="px-4 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">
                                            Acción</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach($details as $index => $detail)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">{{
                                            $detail['itemName'] }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">{{ $detail['sku']
                                            }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">{{
                                            number_format($detail['quantity'], 0) }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">{{
                                            $detail['unitMeasurementName'] }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">{{
                                            $detail['consumptionUnitName'] }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">{{
                                            number_format($detail['currentQuantity'], 0) }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">{{
                                            number_format($detail['adjustedQuantity'], 0) }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-500 dark:text-gray-400">${{
                                            number_format($detail['price'], 0) }}</td>
                                        <td class="px-4 py-2 text-sm text-gray-900 dark:text-white font-semibold">${{
                                            number_format($detail['total'], 0) }}</td>
                                        <td class="px-4 py-2 text-center">
                                            <button wire:click="removeDetail({{ $index }})"
                                                class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300 transition-colors">
                                                <svg class="w-5 h-5 inline" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                    </path>
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif
                    </div>

                    <!-- Actions -->
                    <div class="mt-6 flex justify-end gap-2">
                        <button wire:click="closeModal" type="button"
                            class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            Cancelar
                        </button>
                        <button wire:click="saveMovement" type="button"
                            class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white rounded-lg transition-colors">
                            Guardar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>


    @if($showDetailsModal && !empty($movementDetails))
    <div class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] overflow-y-auto">
            <!-- Header -->
            <div
                class="sticky top-0 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 px-6 py-4 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                    Detalles del Movimiento #{{ $movementDetails['consecutive'] }}
                </h3>
                <button wire:click="closeDetailsModal"
                    class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12">
                        </path>
                    </svg>
                </button>
            </div>

            <!-- Información del Movimiento -->
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Fecha</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ $movementDetails['date'] }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Tipo</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ $movementDetails['type'] }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Bodega</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ $movementDetails['store_name'] }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Usuario</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ $movementDetails['user_name'] }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Razón</p>
                        <p class="text-sm font-medium text-gray-900 dark:text-white">
                            {{ $movementDetails['reason_name'] }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500 dark:text-gray-400">Estado</p>
                        @if($movementDetails['status'] === 1)
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                            Registrado
                        </span>
                        @else
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                            Anulado
                        </span>
                        @endif
                    </div>
                </div>
                <div class="mt-4">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Observaciones</p>
                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                        {{ $movementDetails['observations'] ? $movementDetails['observations'] : 'Sin observaciones' }}
                    </p>
                </div>
            </div>

            <!-- Tabla de Items -->
            <div class="px-6 py-4">
                <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-3">Items del Movimiento</h4>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th
                                    class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                    Producto</th>
                                <th
                                    class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                    Cantidad</th>
                                <th
                                    class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">
                                    Unidad</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($movementDetails['details'] as $detail)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50">
                                <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">
                                    {{ $detail['item_name'] }}
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">
                                    {{ $detail['quantity'] }}
                                </td>
                                <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">
                                    {{ $detail['unit_name'] }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="px-4 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                    No hay items en este movimiento
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Footer -->
            <div class="bg-gray-50 dark:bg-gray-700 px-6 py-4 flex justify-between items-center">
                <div>
                    @if($movementDetails['status'] === 1)
                        <button @click="$dispatch('confirm-annul-from-details', { movementId: {{ $movementDetails['id'] }}, consecutive: '{{ $movementDetails['consecutive'] }}' })"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600 text-white rounded-lg transition-colors text-sm font-medium focus:outline-none focus:ring-2 focus:ring-red-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            Anular Movimiento
                        </button>
                    @endif
                </div>
                <button wire:click="closeDetailsModal"
                    class="px-4 py-2 bg-gray-200 dark:bg-gray-600 text-gray-900 dark:text-white rounded-lg hover:bg-gray-300 dark:hover:bg-gray-500 transition-colors text-sm font-medium">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
    @endif
  @endif  
  @endif

  <!-- Modal de Confirmación para Anular (Global) -->
  <div x-data="{ 
      showConfirm: false, 
      movementId: null, 
      consecutive: '' 
  }"
      @confirm-annul-from-details.window="showConfirm = true; movementId = $event.detail.movementId; consecutive = $event.detail.consecutive"
      x-show="showConfirm"
      x-cloak
      class="fixed inset-0 z-[60] overflow-y-auto"
      style="display: none;">
      
      <!-- Overlay -->
      <div class="fixed inset-0 bg-black bg-opacity-50 transition-opacity" @click="showConfirm = false"></div>
      
      <!-- Modal -->
      <div class="flex items-center justify-center min-h-screen p-4">
          <div @click.away="showConfirm = false"
              x-transition:enter="transition ease-out duration-300"
              x-transition:enter-start="opacity-0 transform scale-90"
              x-transition:enter-end="opacity-100 transform scale-100"
              x-transition:leave="transition ease-in duration-200"
              x-transition:leave-start="opacity-100 transform scale-100"
              x-transition:leave-end="opacity-0 transform scale-90"
              class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full p-6">
              
              <!-- Icon -->
              <div class="flex items-center justify-center w-12 h-12 mx-auto mb-4 bg-red-100 dark:bg-red-900/20 rounded-full">
                  <svg class="w-6 h-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                  </svg>
              </div>
              
              <!-- Content -->
              <div class="text-center">
                  <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                      Anular Movimiento
                  </h3>
                  <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">
                      ¿Está seguro de que desea anular el movimiento 
                      <span class="font-semibold text-gray-900 dark:text-white" x-text="'#' + consecutive"></span>?
                  </p>
                  <p class="text-sm text-red-600 dark:text-red-400 font-medium">
                      Esta acción no se puede deshacer.
                  </p>
              </div>
              
              <!-- Actions -->
              <div class="flex gap-3 mt-6">
                  <button @click="showConfirm = false"
                      class="flex-1 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-colors">
                      Cancelar
                  </button>
                  <button @click="$wire.annulMovement(movementId); showConfirm = false"
                      class="flex-1 px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 dark:bg-red-500 dark:hover:bg-red-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors">
                      Sí, Anular
                  </button>
              </div>
          </div>
      </div>
  </div>

  <script>
      document.addEventListener('DOMContentLoaded', () => {
          // Escuchar el evento de movimiento creado
          Livewire.on('movementCreated', (event) => {
              const data = event[0];
              const isEntrada = data.type === 'entrada';
              
              // Detectar si el tema es dark
              const isDarkMode = document.documentElement.classList.contains('dark') || 
                                localStorage.getItem('darkMode') === 'true';
              
              // Colores según el tipo de movimiento
              const iconColor = isEntrada ? '#10b981' : '#ef4444';
              const buttonColor = isEntrada ? '#10b981' : '#ef4444';
              
              // Colores según el tema
              const backgroundColor = isDarkMode ? '#1f2937' : '#ffffff';
              const textColor = isDarkMode ? '#f9fafb' : '#111827';
              const borderColor = isDarkMode ? '#374151' : '#e5e7eb';
              
              // Mostrar alerta de éxito con SweetAlert2
              Swal.fire({
                  title: '¡Éxito!',
                  text: data.message,
                  icon: 'success',
                  iconColor: iconColor,
                  confirmButtonText: 'Aceptar',
                  confirmButtonColor: buttonColor,
                  background: backgroundColor,
                  color: textColor,
                  timer: 3000,
                  timerProgressBar: true,
                  allowOutsideClick: true,
                  allowEscapeKey: true,
                  customClass: {
                      popup: isDarkMode ? 'swal2-dark' : 'swal2-light',
                      confirmButton: 'focus:ring-2 focus:ring-offset-2'
                  },
                  didOpen: () => {
                      // Aplicar estilos adicionales para dark mode
                      const popup = Swal.getPopup();
                      if (isDarkMode && popup) {
                          popup.style.border = `1px solid ${borderColor}`;
                          popup.style.boxShadow = '0 10px 15px -3px rgba(0, 0, 0, 0.5), 0 4px 6px -2px rgba(0, 0, 0, 0.3)';
                      }
                  }
              });
          });

          // Escuchar el evento de anulación exitosa
          Livewire.on('annulMovementSuccess', (event) => {
              const data = event[0];
              const isDarkMode = document.documentElement.classList.contains('dark') || 
                                localStorage.getItem('darkMode') === 'true';
              
              const backgroundColor = isDarkMode ? '#1f2937' : '#ffffff';
              const textColor = isDarkMode ? '#f9fafb' : '#111827';
              const borderColor = isDarkMode ? '#374151' : '#e5e7eb';
              
              Swal.fire({
                  title: '¡Movimiento Anulado!',
                  text: data.message,
                  icon: 'success',
                  iconColor: '#10b981',
                  confirmButtonText: 'Aceptar',
                  confirmButtonColor: '#10b981',
                  background: backgroundColor,
                  color: textColor,
                  timer: 3000,
                  timerProgressBar: true,
                  allowOutsideClick: true,
                  allowEscapeKey: true,
                  customClass: {
                      popup: isDarkMode ? 'swal2-dark' : 'swal2-light',
                      confirmButton: 'focus:ring-2 focus:ring-offset-2'
                  },
                  didOpen: () => {
                      const popup = Swal.getPopup();
                      if (isDarkMode && popup) {
                          popup.style.border = `1px solid ${borderColor}`;
                          popup.style.boxShadow = '0 10px 15px -3px rgba(0, 0, 0, 0.5), 0 4px 6px -2px rgba(0, 0, 0, 0.3)';
                      }
                  }
              });
          });

          // Escuchar el evento de error en anulación
          Livewire.on('annulMovementFailed', (event) => {
              const data = event[0];
              const isDarkMode = document.documentElement.classList.contains('dark') || 
                                localStorage.getItem('darkMode') === 'true';
              
              const backgroundColor = isDarkMode ? '#1f2937' : '#ffffff';
              const textColor = isDarkMode ? '#f9fafb' : '#111827';
              const borderColor = isDarkMode ? '#374151' : '#e5e7eb';
              
              Swal.fire({
                  title: 'Error al Anular',
                  text: data.message,
                  icon: 'error',
                  iconColor: '#ef4444',
                  confirmButtonText: 'Aceptar',
                  confirmButtonColor: '#ef4444',
                  background: backgroundColor,
                  color: textColor,
                  allowOutsideClick: true,
                  allowEscapeKey: true,
                  customClass: {
                      popup: isDarkMode ? 'swal2-dark' : 'swal2-light',
                      confirmButton: 'focus:ring-2 focus:ring-offset-2'
                  },
                  didOpen: () => {
                      const popup = Swal.getPopup();
                      if (isDarkMode && popup) {
                          popup.style.border = `1px solid ${borderColor}`;
                          popup.style.boxShadow = '0 10px 15px -3px rgba(0, 0, 0, 0.5), 0 4px 6px -2px rgba(0, 0, 0, 0.3)';
                      }
                  }
              });
          });

          // Escuchar cuando se cierra el modal de detalles para cerrar el SweetAlert
          Livewire.on('closeDetailsModal', () => {
              Swal.close();
          });
      });
  </script>
</div>