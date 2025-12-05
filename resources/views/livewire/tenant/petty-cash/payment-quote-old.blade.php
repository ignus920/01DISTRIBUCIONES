<div class="min-h-screen bg-gray-50 dark:bg-gray-900 p-6">
    <div class="max-w-6xl mx-auto">
        <!-- Header -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Procesar Pago de Cotización</h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">{{ $quoteCustumer }} - {{ $quoteNumber }}</p>
                </div>
                <div class="text-right">
                    @if($activePettyCash)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                            Caja {{ $activePettyCash['consecutive'] }} Abierta
                        </span>
                    @else
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                            </svg>
                            No hay caja abierta
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Mensajes -->
        @if (session()->has('success'))
            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300 px-4 py-3 rounded-lg mb-6">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    {{ session('success') }}
                </div>
            </div>
        @endif

        @if (session()->has('error'))
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 px-4 py-3 rounded-lg mb-6">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    {{ session('error') }}
                </div>
            </div>
        @endif

        @if (session()->has('warning'))
            <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 text-yellow-700 dark:text-yellow-300 px-4 py-3 rounded-lg mb-6">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                    {{ session('warning') }}
                </div>
            </div>
        @endif

        <div class="flex flex-col xl:flex-row gap-6">
            <!-- Panel izquierdo - Información de la cotización -->
            <div class="w-full xl:w-1/3 flex-shrink-0">
                <!-- Resumen de Cotización -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Resumen de Cotización</h3>

                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Subtotal:</span>
                                <span class="font-medium text-gray-900 dark:text-white">${{ number_format($quoteSubtotal, 0, ',', '.') }}</span>
                            </div>

                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Impuestos:</span>
                                <span class="font-medium text-gray-900 dark:text-white">${{ number_format($quoteTaxes, 0, ',', '.') }}</span>
                            </div>

                            <hr class="border-gray-200 dark:border-gray-700">

                            <div class="flex justify-between text-lg font-bold">
                                <span class="text-gray-900 dark:text-white">Total:</span>
                                <span class="text-indigo-600 dark:text-indigo-400">${{ number_format($quoteTotal, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Anticipos -->
                @if(!empty($advances))
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Anticipos Registrados</h3>

                        <div class="space-y-2">
                            @foreach($advances as $advance)
                            <div class="flex justify-between items-center">
                                <div>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $advance['method_name'] }}</span>
                                    <span class="text-xs text-gray-500 dark:text-gray-400 block">{{ $advance['date'] }}</span>
                                </div>
                                <span class="text-green-600 dark:text-green-400 font-medium">${{ number_format($advance['value'], 0, ',', '.') }}</span>
                            </div>
                            @endforeach

                            <hr class="border-gray-200 dark:border-gray-700">

                            <div class="flex justify-between font-semibold">
                                <span class="text-gray-900 dark:text-white">Total Anticipos:</span>
                                <span class="text-green-600 dark:text-green-400">${{ number_format($totalAdvances, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Calculadora de Saldos -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Estado de Pago</h3>

                        <div class="space-y-3">
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Total Factura:</span>
                                <span class="font-medium text-gray-900 dark:text-white">${{ number_format($quoteTotal, 0, ',', '.') }}</span>
                            </div>

                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Anticipos:</span>
                                <span class="font-medium text-green-600 dark:text-green-400">-${{ number_format($totalAdvances, 0, ',', '.') }}</span>
                            </div>

                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Pagado Ahora:</span>
                                <span class="font-medium text-blue-600 dark:text-blue-400">-${{ number_format(array_sum($paymentValues), 0, ',', '.') }}</span>
                            </div>

                            <hr class="border-gray-200 dark:border-gray-700">

                            <div class="flex justify-between text-lg font-bold">
                                @if($remainingBalance > 0)
                                    <span class="text-gray-900 dark:text-white">Saldo Pendiente:</span>
                                    <span class="text-red-600 dark:text-red-400">${{ number_format($remainingBalance, 0, ',', '.') }}</span>
                                @elseif($remainingBalance < 0)
                                    <span class="text-gray-900 dark:text-white">Exceso de Pago:</span>
                                    <span class="text-orange-600 dark:text-orange-400">${{ number_format(abs($remainingBalance), 0, ',', '.') }}</span>
                                @else
                                    <span class="text-gray-900 dark:text-white">Estado:</span>
                                    <span class="text-green-600 dark:text-green-400">PAGADO COMPLETO</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Panel derecho - Métodos de pago -->
            <div class="w-full xl:w-2/3 flex-shrink-0">
                <!-- Métodos de Pago Disponibles -->
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Métodos de Pago</h3>

                            @if($remainingBalance > 0)
                            <div class="flex gap-2">
                                <button wire:click="payTotalWithCash"
                                    class="px-3 py-2 bg-green-600 hover:bg-green-700 text-white text-xs rounded-lg transition">
                                    Pagar Todo en Efectivo
                                </button>

                                @if(!empty($selectedPaymentMethods))
                                <button wire:click="distributeEqually"
                                    class="px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs rounded-lg transition">
                                    Distribuir Equitativamente
                                </button>
                                @endif
                            </div>
                            @endif
                        </div>

                        <!-- Lista de métodos de pago -->
                        <div class="space-y-3">
                            @foreach($availablePaymentMethods as $method)
                            <div wire:click="togglePaymentMethod({{ $method['id'] }})"
                                class="flex items-center justify-between p-4 border rounded-lg cursor-pointer transition-all hover:shadow-sm
                                    @if(in_array($method['id'], $selectedPaymentMethods))
                                        border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20
                                    @else
                                        border-gray-200 dark:border-gray-700 hover:border-indigo-300
                                    @endif
                                ">
                                <div class="flex items-center space-x-3">
                                    <div class="flex-shrink-0">
                                        @if(in_array($method['id'], $selectedPaymentMethods))
                                            <svg class="w-5 h-5 text-indigo-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                            </svg>
                                        @else
                                            <div class="w-5 h-5 border-2 border-gray-300 dark:border-gray-600 rounded"></div>
                                        @endif
                                    </div>

                                    <div>
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">
                                            {{ $method['name'] }}
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $method['description'] }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>

                        <!-- Inputs de valores para métodos seleccionados -->
                        @if($showPaymentMethods && !empty($selectedPaymentMethods))
                        <div class="mt-6 space-y-4">
                            <h4 class="text-md font-medium text-gray-900 dark:text-white">Valores por Método de Pago</h4>

                            @foreach($selectedPaymentMethods as $methodId)
                            <div class="flex items-center gap-4 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                                <div class="flex-1">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                        {{ $this->getSelectedMethodName($methodId) }}
                                    </label>
                                    <div class="relative">
                                        <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500 dark:text-gray-400">$</span>
                                        <input type="number"
                                            wire:model.live="paymentValues.{{ $methodId }}"
                                            min="0"
                                            step="0.01"
                                            class="block w-full pl-8 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-800 dark:text-white text-right"
                                            placeholder="0.00">
                                    </div>
                                </div>

                                <button wire:click="togglePaymentMethod({{ $methodId }})"
                                    class="p-2 text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 transition">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                            @endforeach
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Observaciones y decisiones finales -->
                @if($canProceedToPayment)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Finalizar Pago</h3>

                        <!-- Observaciones -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Observaciones (opcional)
                            </label>
                            <textarea wire:model="observations" rows="3"
                                class="block w-full border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:text-white"
                                placeholder="Ingrese observaciones sobre este pago..."></textarea>
                        </div>

                        <!-- Decisión de crédito si queda saldo -->
                        @if($remainingBalance > 0)
                        <div class="mb-6">
                            <div class="flex items-center p-4 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                                <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                </svg>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-yellow-800 dark:text-yellow-300">
                                        Queda un saldo pendiente de ${{ number_format($remainingBalance, 0, ',', '.') }}
                                    </p>
                                    <div class="mt-2">
                                        <label class="flex items-center">
                                            <input type="checkbox" wire:model="willBeCredit"
                                                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                            <span class="ml-2 text-sm text-yellow-700 dark:text-yellow-300">
                                                Sí, dejar el saldo pendiente a crédito
                                            </span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- Botones de acción -->
                        <div class="flex flex-col sm:flex-row gap-3">
                            <button wire:click="confirmPayment"
                                class="flex-1 px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Confirmar Pago
                            </button>

                            <button wire:click="resetPayment"
                                class="px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white font-medium rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2">
                                <svg class="w-5 h-5 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                Reiniciar
                            </button>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>