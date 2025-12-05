<div class="fixed inset-0 bg-gray-900 flex items-center justify-center"
     x-data="paymentKeyboard()"
     x-init="init()">

    <!-- Modal Principal -->
    <div class="w-[95vw] h-[90vh] bg-white rounded-lg shadow-2xl overflow-hidden">

        <!-- Header -->
        <div class="bg-gray-800 text-white p-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold">CAJA REGISTRADORA</h1>
                    <p class="text-gray-300">{{ $quoteCustumer }} - {{ $quoteNumber }}</p>
                </div>
                <div class="text-right">
                    @if($activePettyCash)
                        <span class="bg-green-500 px-3 py-1 rounded text-sm">
                            ✓ Caja {{ $activePettyCash['consecutive'] }} Abierta
                        </span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Contenido Principal -->
        <div class="flex h-[calc(100%-120px)]">

            <!-- Panel Izquierdo - Resumen -->
            <div class="w-1/3 bg-gray-100 p-6 border-r-2 border-gray-300">
                <div class="space-y-6">

                    <!-- Total de la Venta -->
                    <div class="bg-white rounded-lg p-6 shadow">
                        <h3 class="text-lg font-semibold mb-4 text-gray-800">TOTAL VENTA</h3>
                        <div class="text-center">
                            <div class="text-4xl font-bold text-green-600">
                                ${{ number_format($quoteTotal, 0, ',', '.') }}
                            </div>
                        </div>
                    </div>

                    <!-- Estado del Pago -->
                    <div class="bg-white rounded-lg p-6 shadow">
                        <h3 class="text-lg font-semibold mb-4 text-gray-800">ESTADO</h3>
                        <div class="space-y-3 text-lg">
                            <div class="flex justify-between">
                                <span>Pagado:</span>
                                <span class="font-bold text-blue-600">${{ number_format($totalPaid, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between border-t pt-3">
                                <span>Falta:</span>
                                <span class="font-bold text-red-600">${{ number_format($remainingBalance, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Instrucciones -->
                    <div class="bg-blue-50 rounded-lg p-4 text-sm">
                        <div class="font-semibold text-blue-800 mb-2">INSTRUCCIONES:</div>
                        <div class="space-y-1 text-blue-700">
                            <div>• <strong>TAB:</strong> Mover dinero al siguiente método</div>
                            <div>• <strong>↑ ↓ ← →:</strong> Solo navegar entre métodos</div>
                            <div>• <strong>MANUAL:</strong> Escribir combinaciones</div>
                            <div>• <strong>ENTER:</strong> Confirmar pago</div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Panel Derecho - Métodos de Pago -->
            <div class="flex-1 p-6">
                <h2 class="text-2xl font-bold mb-6 text-center text-gray-800">FORMA DE PAGO</h2>

                <!-- Tabla de Métodos de Pago -->
                <div class="bg-white rounded-lg shadow-lg overflow-hidden">

                    <!-- Header de la Tabla -->
                    <div class="bg-gray-800 text-white p-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="text-xl font-bold text-center">MÉTODO</div>
                            <div class="text-xl font-bold text-center">VALOR</div>
                        </div>
                    </div>

                    <!-- Filas de Métodos de Pago -->
                    <div class="divide-y divide-gray-200">
                        @foreach($paymentMethods as $key => $method)
                        <div wire:click="selectMethod('{{ $key }}')"
                             class="grid grid-cols-2 gap-4 p-6 cursor-pointer transition-all hover:bg-gray-50
                                @if($currentMethod === $key) bg-yellow-100 border-l-4 border-yellow-500 @endif
                                @if($method['value'] > 0 && $currentMethod !== $key) bg-blue-50 border-l-4 border-blue-400 @endif">

                            <!-- Nombre del Método -->
                            <div class="text-2xl font-semibold text-gray-800 flex items-center">
                                @if($currentMethod === $key)
                                    <span class="mr-3 text-yellow-500">▶</span>
                                @elseif($method['value'] > 0)
                                    <span class="mr-3 text-blue-500">●</span>
                                @endif
                                {{ $method['name'] }}
                            </div>

                            <!-- Input de Valor -->
                            <div class="flex items-center justify-center">
                                <input type="number"
                                       wire:model.live="paymentMethods.{{ $key }}.value"
                                       wire:change="autoDistributePayments()"
                                       x-data="{
                                           navigate(direction) {
                                               const methods = ['efectivo', 'nequi', 'daviplata', 'tarjeta'];
                                               const current = methods.indexOf('{{ $key }}');
                                               let next;
                                               if (direction === 'down' || direction === 'right') {
                                                   next = current + 1 >= methods.length ? 0 : current + 1;
                                               } else {
                                                   next = current - 1 < 0 ? methods.length - 1 : current - 1;
                                               }
                                               const nextInput = document.getElementById('input_' + methods[next]);
                                               if (nextInput) {
                                                   $wire.selectMethod(methods[next]);
                                                   setTimeout(() => { nextInput.focus(); nextInput.select(); }, 50);
                                               }
                                           }
                                       }"
                                       id="input_{{ $key }}"
                                       @focus="$wire.selectMethod('{{ $key }}'); if($el.value == '0') { $el.value = ''; $wire.set('paymentMethods.{{ $key }}.value', '') }"
                                       @click="$wire.selectMethod('{{ $key }}'); if($el.value == '0') { $el.value = ''; $wire.set('paymentMethods.{{ $key }}.value', '') }"
                                       @keydown.arrow-down.prevent="navigate('down')"
                                       @keydown.arrow-up.prevent="navigate('up')"
                                       @keydown.arrow-right.prevent="navigate('right')"
                                       @keydown.arrow-left.prevent="navigate('left')"
                                       @keydown.tab.prevent="
                                           $wire.payTotalWithCurrentMethod().then(() => {
                                               setTimeout(() => {
                                                   const methods = ['efectivo', 'nequi', 'daviplata', 'tarjeta'];
                                                   const current = methods.indexOf('{{ $key }}');
                                                   const next = current + 1 >= methods.length ? 0 : current + 1;
                                                   const nextInput = document.getElementById('input_' + methods[next]);
                                                   if (nextInput) {
                                                       nextInput.focus();
                                                       nextInput.select();
                                                   }
                                               }, 100);
                                           })
                                       "
                                       @keydown.enter.prevent="$wire.confirmPayment()"
                                       class="w-full text-3xl font-bold text-center border-2 rounded-lg p-3
                                              @if($currentMethod === $key) border-yellow-500 bg-yellow-50 @elseif($method['value'] > 0) border-blue-400 bg-blue-50 @else border-gray-300 @endif
                                              focus:outline-none focus:ring-2 focus:ring-yellow-500"
                                       placeholder=""
                                       min="0"
                                       step="1">
                            </div>
                        </div>
                        @endforeach
                    </div>

                    <!-- Total Pagado -->
                    <div class="bg-gray-100 p-4 border-t-2 border-gray-300">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="text-xl font-bold text-center">TOTAL PAGADO:</div>
                            <div class="text-3xl font-bold text-center text-green-600">
                                ${{ number_format($totalPaid, 0, ',', '.') }}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botones de Acción -->
                <div class="mt-8 flex gap-4 justify-center">
                    <!-- <button wire:click="payTotalWithCurrentMethod()"
                            class="px-8 py-4 bg-blue-600 hover:bg-blue-700 text-white text-xl font-bold rounded-lg transition shadow-lg">
                        PAGAR TODO CON {{ strtoupper($paymentMethods[$currentMethod]['name']) }}
                    </button> -->

                    @if($canProceedToPayment)
                    <button wire:click="confirmPayment()"
                            class="px-6 py-2  bg-green-600 hover:bg-green-700 text-white text-base font-medium rounded-md transition">
                        CONFIRMAR PAGO
                    </button>
                    @endif

                    <button onclick="window.history.back()"
                            class="px-6 py-2 bg-gray-600 hover:bg-gray-700 text-white text-base font-medium rounded-md transition">
                        CANCELAR
                    </button>
                </div>

            </div>
        </div>

    </div>

    <!-- JavaScript simplificado -->
    <script>
        function paymentKeyboard() {
            return {
                init() {
                    // Enfocar el primer input al cargar
                    setTimeout(() => {
                        const firstInput = document.getElementById('input_efectivo');
                        if (firstInput) {
                            firstInput.focus();
                            firstInput.select();
                        }
                    }, 100);
                }
            }
        }

        // Escuchar eventos de Livewire para alerts
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('showAlert', (message) => {
                alert(message);
            });
        });
    </script>

    <!-- Mensajes Flash -->
    @if (session()->has('success'))
        <div class="fixed top-4 right-4 bg-green-500 text-white px-6 py-4 rounded-lg shadow-lg z-50">
            {{ session('success') }}
        </div>
    @endif

    @if (session()->has('error'))
        <div class="fixed top-4 right-4 bg-red-500 text-white px-6 py-4 rounded-lg shadow-lg z-50">
            {{ session('error') }}
        </div>
    @endif

    @if (session()->has('warning'))
        <div class="fixed top-4 right-4 bg-yellow-500 text-white px-6 py-4 rounded-lg shadow-lg z-50">
            {{ session('warning') }}
        </div>
    @endif

</div>