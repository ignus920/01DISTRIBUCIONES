<div class="space-y-6">
    <!-- Header -->
    <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
            Configuración de Empresa TAT
        </h3>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            Administra las configuraciones específicas para operaciones de punto de venta.
        </p>
    </div>

    <!-- Información de la empresa (si está disponible) -->
    @if($this->companyInfo)
        <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-4">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                </svg>
                <div>
                    <h4 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                        {{ $this->companyInfo->businessName ?? 'Empresa' }}
                    </h4>
                    <p class="text-xs text-blue-600 dark:text-blue-400">
                        ID: {{ $this->companyInfo->identification ?? 'N/A' }}
                    </p>
                </div>
            </div>
        </div>
    @endif

    <!-- Configuraciones -->
    @if($this->canEditConfig)
        <form wire:submit="save" class="space-y-6">
            <!-- Vender sin saldo -->
            <div class="space-y-3">
                <div class="flex items-start">
                    <div class="flex items-center h-6">
                        <input
                            id="vender_sin_saldo"
                            wire:model.live="vender_sin_saldo"
                            type="checkbox"
                            class="h-5 w-5 text-blue-600 dark:text-blue-500 border-gray-300 dark:border-gray-600 rounded focus:ring-blue-500 dark:focus:ring-blue-400 bg-white dark:bg-gray-700"
                            @if($isLoading) disabled @endif
                        >
                    </div>
                    <div class="ml-3">
                        <label for="vender_sin_saldo" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            Permitir ventas sin saldo
                        </label>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            <p>Permite realizar ventas de productos aunque no tengan stock disponible.</p>
                            <p class="text-red-600 dark:text-red-400 mt-1">
                                ⚠️ <strong>Advertencia:</strong> Puede generar stock negativo en el inventario.
                            </p>
                        </div>
                    </div>
                </div>

                @error('vender_sin_saldo')
                    <span class="text-red-600 dark:text-red-400 text-xs ml-8">{{ $message }}</span>
                @enderror
            </div>

            <!-- Permitir cambio de precio -->
            <div class="space-y-3">
                <div class="flex items-start">
                    <div class="flex items-center h-6">
                        <input
                            id="permitir_cambio_precio"
                            wire:model.live="permitir_cambio_precio"
                            type="checkbox"
                            class="h-5 w-5 text-blue-600 dark:text-blue-500 border-gray-300 dark:border-gray-600 rounded focus:ring-blue-500 dark:focus:ring-blue-400 bg-white dark:bg-gray-700"
                            @if($isLoading) disabled @endif
                        >
                    </div>
                    <div class="ml-3">
                        <label for="permitir_cambio_precio" class="text-sm font-medium text-gray-700 dark:text-gray-300">
                            Permitir modificar precios en POS
                        </label>
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            <p>Habilita la edición de precios directamente en el punto de venta.</p>
                            <p class="text-yellow-600 dark:text-yellow-400 mt-1">
                                ⚠️ <strong>Nota:</strong> Los operadores podrán modificar precios durante la venta.
                            </p>
                        </div>
                    </div>
                </div>

                @error('permitir_cambio_precio')
                    <span class="text-red-600 dark:text-red-400 text-xs ml-8">{{ $message }}</span>
                @enderror
            </div>

            <!-- Botones de acción -->
            <div class="flex items-center justify-between pt-6 border-t border-gray-200 dark:border-gray-700">
                <div class="flex space-x-3">
                    <!-- Botón guardar -->
                    <button
                        type="submit"
                        wire:loading.attr="disabled"
                        wire:target="save"
                        @if(!$hasChanges || $isLoading) disabled @endif
                        class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white text-sm font-medium rounded-lg transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 disabled:opacity-50 disabled:cursor-not-allowed">

                        <!-- Icono normal -->
                        <svg wire:loading.remove wire:target="save" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>

                        <!-- Spinner de carga -->
                        <svg wire:loading wire:target="save" class="animate-spin w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>

                        <span wire:loading.remove wire:target="save">Guardar cambios</span>
                        <span wire:loading wire:target="save">Guardando...</span>
                    </button>

                    <!-- Botón cancelar -->
                    @if($hasChanges)
                        <button
                            type="button"
                            wire:click="cancel"
                            wire:loading.attr="disabled"
                            class="inline-flex items-center px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white text-sm font-medium rounded-lg transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-gray-500 disabled:opacity-50">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            Cancelar
                        </button>
                    @endif
                </div>

                <!-- Botón resetear -->
                <button
                    type="button"
                    wire:click="resetToDefaults"
                    wire:loading.attr="disabled"
                    class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-600 dark:text-gray-400 hover:text-gray-800 dark:hover:text-gray-200 transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-gray-500 disabled:opacity-50">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    Restaurar por defecto
                </button>
            </div>

            <!-- Indicador de cambios -->
            @if($hasChanges)
                <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg p-3">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.464 0L4.35 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                        <span class="text-sm text-yellow-800 dark:text-yellow-200 font-medium">
                            Tienes cambios sin guardar
                        </span>
                    </div>
                </div>
            @endif
        </form>

    @else
        <!-- Sin permisos -->
        <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-lg p-4">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-red-600 dark:text-red-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.464 0L4.35 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                </svg>
                <div>
                    <h4 class="text-sm font-medium text-red-800 dark:text-red-200">
                        Acceso restringido
                    </h4>
                    <p class="text-xs text-red-600 dark:text-red-400 mt-1">
                        No tienes permisos para modificar la configuración de la empresa.
                    </p>
                </div>
            </div>
        </div>
    @endif

    <!-- Error sin empresa -->
    @if(!$companyId)
        <div class="bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg p-4">
            <div class="flex items-center">
                <svg class="w-5 h-5 text-gray-500 dark:text-gray-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div>
                    <h4 class="text-sm font-medium text-gray-700 dark:text-gray-300">
                        Información no disponible
                    </h4>
                    <p class="text-xs text-gray-600 dark:text-gray-400 mt-1">
                        No se pudo determinar la empresa asociada a tu usuario.
                    </p>
                </div>
            </div>
        </div>
    @endif
</div>

<!-- Alertas de estado -->
@if (session()->has('success'))
    <div x-data="{ show: true }" x-show="show" x-transition class="fixed top-4 right-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded shadow-lg z-50">
        {{ session('success') }}
        <button @click="show = false" class="ml-4 text-green-600 hover:text-green-800">×</button>
    </div>
@endif

@if (session()->has('error'))
    <div x-data="{ show: true }" x-show="show" x-transition class="fixed top-4 right-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded shadow-lg z-50">
        {{ session('error') }}
        <button @click="show = false" class="ml-4 text-red-600 hover:text-red-800">×</button>
    </div>
@endif

@if (session()->has('info'))
    <div x-data="{ show: true }" x-show="show" x-transition class="fixed top-4 right-4 bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded shadow-lg z-50">
        {{ session('info') }}
        <button @click="show = false" class="ml-4 text-blue-600 hover:text-blue-800">×</button>
    </div>
@endif