<div class="fixed inset-0 bg-gray-900/80 dark:bg-black/90 backdrop-blur-sm flex items-center justify-center z-50 p-4"
     x-data="paymentKeyboard()"
     x-init="init()">

    <!-- Modal Principal -->
    <div class="w-full max-w-7xl h-[90vh] bg-white dark:bg-slate-900 rounded-2xl shadow-2xl overflow-hidden flex flex-col border border-gray-200 dark:border-slate-700">

        <!-- Header -->
        <div class="bg-gray-900 dark:bg-black text-white px-6 py-4 flex-none">
            <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-white/90">CAJA REGISTRADORA</h1>
                    <div class="flex items-center gap-2 text-sm text-gray-400">
                        <span class="font-mono bg-gray-800 px-2 py-0.5 rounded">{{ $quoteNumber }}</span>
                        <span>•</span>
                        <span class="font-medium truncate max-w-[200px] sm:max-w-md" title="{{ $quoteCustumer }}">{{ $quoteCustumer }}</span>
                    </div>
                </div>
                <div class="text-right">
                    @if($activePettyCash)
                        <div class="inline-flex items-center gap-2 bg-green-500/10 text-green-400 border border-green-500/20 px-3 py-1.5 rounded-full text-sm font-medium">
                            <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                            Caja {{ $activePettyCash['consecutive'] }} Abierta
                        </div>
                    @else
                        <div class="inline-flex items-center gap-2 bg-red-500/10 text-red-400 border border-red-500/20 px-3 py-1.5 rounded-full text-sm font-medium">
                            <span class="w-2 h-2 rounded-full bg-red-500"></span>
                            Caja Cerrada
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Contenido Principal -->
        <div class="flex flex-col lg:flex-row flex-1 overflow-hidden">

            <!-- Panel Izquierdo - Resumen (Scrollable en móvil, fijo en desktop) -->
            <div class="w-full lg:w-1/3 bg-gray-50 dark:bg-slate-800/50 p-6 border-b lg:border-b-0 lg:border-r border-gray-200 dark:border-slate-700 overflow-y-auto">
                <div class="space-y-6 max-w-sm mx-auto lg:max-w-none">

                    <!-- Total de la Venta -->
                    <div class="bg-white dark:bg-slate-800 rounded-xl p-6 shadow-sm border border-gray-100 dark:border-slate-700">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-slate-400 mb-2">Total a Pagar</h3>
                        <div class="text-center py-2">
                            <div class="text-4xl sm:text-5xl font-extrabold text-gray-900 dark:text-white tracking-tight">
                                ${{ number_format($quoteTotal, 0, ',', '.') }}
                            </div>
                        </div>
                    </div>

                    <!-- Estado del Pago -->
                    <div class="bg-white dark:bg-slate-800 rounded-xl p-6 shadow-sm border border-gray-100 dark:border-slate-700 space-y-4">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-slate-400">Balance</h3>

                        <div class="flex justify-between items-baseline">
                            <span class="text-gray-600 dark:text-slate-300">Pagado</span>
                            <span class="text-xl font-bold text-green-600 dark:text-green-500">
                                ${{ number_format($totalPaid, 0, ',', '.') }}
                            </span>
                        </div>

                        <div class="w-full bg-gray-200 dark:bg-slate-700 rounded-full h-2.5 overflow-hidden">
                            @php
                                $percent = $quoteTotal > 0 ? min(100, ($totalPaid / $quoteTotal) * 100) : 0;
                            @endphp
                            <div class="bg-green-500 h-2.5 rounded-full transition-all duration-500 ease-out" style="width: {{ $percent }}%"></div>
                        </div>

                        <div class="flex justify-between items-baseline pt-2 border-t border-gray-100 dark:border-slate-700/50">
                            <span class="text-gray-600 dark:text-slate-300 font-medium">Restante</span>
                            <span class="text-2xl font-bold {{ $remainingBalance > 0 ? 'text-red-600 dark:text-red-500' : 'text-green-600 dark:text-green-500' }}">
                                ${{ number_format($remainingBalance, 0, ',', '.') }}
                            </span>
                        </div>
                    </div>

                    <!-- Instrucciones -->
                    <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-5 border border-blue-100 dark:border-blue-800/30">
                        <div class="flex items-center gap-2 font-semibold text-blue-800 dark:text-blue-300 mb-3 text-sm uppercase tracking-wide">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Teclado
                        </div>
                        <ul class="space-y-2 text-sm text-blue-900 dark:text-blue-200/80 font-medium">
                            <li class="flex items-center gap-2"><kbd class="bg-white dark:bg-slate-900 px-1.5 py-0.5 rounded border border-blue-200 dark:border-blue-800 text-xs font-sans">TAB</kbd> Siguiente método</li>
                            <li class="flex items-center gap-2"><kbd class="bg-white dark:bg-slate-900 px-1.5 py-0.5 rounded border border-blue-200 dark:border-blue-800 text-xs font-sans">↑ ↓</kbd> Navegar</li>
                            <li class="flex items-center gap-2"><kbd class="bg-white dark:bg-slate-900 px-1.5 py-0.5 rounded border border-blue-200 dark:border-blue-800 text-xs font-sans">ENTER</kbd> Confirmar</li>
                        </ul>
                    </div>

                </div>
            </div>

            <!-- Panel Derecho - Métodos de Pago -->
            <div class="flex-1 bg-white dark:bg-slate-900 flex flex-col min-h-0">
                
                <div class="p-4 sm:p-6 lg:p-8 flex-1 overflow-y-auto">
                    <h2 class="text-xl font-bold mb-6 text-gray-900 dark:text-white flex items-center gap-2">
                        <span>Forma de Pago</span>
                        <span class="text-sm font-normal text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-slate-800 px-2 py-0.5 rounded-md">Seleccione o distribuya el valor</span>
                    </h2>

                    <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 overflow-hidden">
                        
                        <!-- Header Tabla Desktop -->
                        <div class="hidden sm:grid grid-cols-12 gap-4 bg-gray-50 dark:bg-slate-800 px-6 py-3 text-xs font-semibold uppercase tracking-wider text-gray-500 dark:text-slate-400">
                            <div class="col-span-4 lg:col-span-5">Método</div>
                            <div class="col-span-8 lg:col-span-7">Valor a Pagar</div>
                        </div>

                        <div class="divide-y divide-gray-100 dark:divide-slate-700/50">
                            @foreach($paymentMethods as $key => $method)
                                <div wire:click="selectMethod('{{ $key }}')"
                                     class="group sm:grid sm:grid-cols-12 gap-4 p-4 sm:px-6 sm:py-5 items-center cursor-pointer transition-all duration-200
                                        {{ $currentMethod === $key 
                                            ? 'bg-blue-50/80 dark:bg-blue-900/20 ring-1 ring-inset ring-blue-500/50 z-10' 
                                            : 'hover:bg-gray-50 dark:hover:bg-slate-800/50' }}
                                        {{ $method['value'] > 0 && $currentMethod !== $key 
                                            ? 'bg-green-50/50 dark:bg-green-900/10' 
                                            : '' }}">

                                    <!-- Nombre + Icono -->
                                    <div class="col-span-12 sm:col-span-4 lg:col-span-5 flex items-center justify-between sm:justify-start gap-3 mb-2 sm:mb-0">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-lg flex items-center justify-center transition-colors
                                                {{ $currentMethod === $key ? 'bg-blue-500 text-white' : 'bg-gray-100 dark:bg-slate-800 text-gray-400 dark:text-slate-500 group-hover:bg-gray-200 dark:group-hover:bg-slate-700' }}">
                                                @if($key === 'efectivo') <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                                                @elseif($key === 'nequi' || $key === 'daviplata') <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z" /></svg>
                                                @else <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z" /></svg>
                                                @endif
                                            </div>
                                            <span class="font-bold text-gray-700 dark:text-gray-200">{{ $method['name'] }}</span>
                                        </div>
                                        
                                        @if($currentMethod === $key)
                                            <span class="sm:hidden text-xs font-bold text-blue-600 dark:text-blue-400 bg-blue-100 dark:bg-blue-900/50 px-2 py-0.5 rounded-full">Activo</span>
                                        @endif
                                    </div>

                                    <!-- Input -->
                                    <div class="col-span-12 sm:col-span-8 lg:col-span-7 relative">
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                                <span class="text-gray-400 font-bold">$</span>
                                            </div>
                                            <input type="number"
                                                   wire:model.live="paymentMethods.{{ $key }}.value"
                                                   wire:change="autoDistributePayments()"
                                                   id="input_{{ $key }}"
                                                   class="pl-8 block w-full rounded-lg border-0 py-3 text-gray-900 dark:text-white shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-slate-600 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-600 sm:text-xl sm:font-bold font-mono bg-white dark:bg-slate-800 transition-all
                                                          {{ $currentMethod === $key ? 'ring-blue-500 dark:ring-blue-400 bg-white dark:bg-slate-800' : 'bg-gray-50 dark:bg-slate-800/50' }}"
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
                                                   @focus="$wire.selectMethod('{{ $key }}'); if($el.value == '0') { $el.value = ''; $wire.set('paymentMethods.{{ $key }}.value', '') }"
                                                   @click="$wire.selectMethod('{{ $key }}'); if($el.value == '0') { $wire.set('paymentMethods.{{ $key }}.value', '') }"
                                                   @keydown.arrow-down.prevent="navigate('down')"
                                                   @keydown.arrow-up.prevent="navigate('up')"
                                                   @keydown.enter.prevent="$wire.confirmPayment()"
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
                                                >
                                            <!-- Indicador de acción rápida -->
                                            @if($currentMethod === $key)
                                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                                    <span class="text-[10px] text-blue-500 uppercase font-bold tracking-tight bg-blue-50 dark:bg-blue-900/50 px-1.5 py-0.5 rounded border border-blue-100 dark:border-blue-800">TAB: Pagar Todo</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                            
                            <!-- Total Row -->
                            <div class="p-4 sm:px-6 bg-gray-50 dark:bg-slate-800/80 flex justify-between items-center border-t border-gray-200 dark:border-slate-700">
                                <span class="font-bold text-gray-500 dark:text-slate-400 uppercase tracking-widest text-sm">Total Registrado</span>
                                <span class="text-2xl font-bold text-gray-900 dark:text-white">${{ number_format($totalPaid, 0, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer de Acciones -->
                <div class="p-6 bg-white dark:bg-slate-900 border-t border-gray-100 dark:border-slate-800 flex flex-col sm:flex-row gap-4 justify-end items-center">
                    
                    <button onclick="window.history.back()"
                            class="w-full sm:w-auto px-6 py-3 border border-gray-300 dark:border-slate-600 hover:bg-gray-50 dark:hover:bg-slate-800 text-gray-700 dark:text-gray-300 font-semibold rounded-xl transition-colors focus:ring-2 focus:ring-gray-200 dark:focus:ring-slate-700 outline-none">
                        Cancelar
                    </button>

                    @if($canProceedToPayment)
                    <button wire:click="confirmPayment()"
                            class="w-full sm:w-auto px-8 py-3 bg-green-600 hover:bg-green-500 dark:bg-green-600 dark:hover:bg-green-500 text-white font-bold rounded-xl shadow-lg shadow-green-500/20 hover:shadow-green-500/30 transform hover:-translate-y-0.5 transition-all focus:ring-2 focus:ring-green-500 focus:ring-offset-2 dark:focus:ring-offset-slate-900 outline-none flex items-center justify-center gap-2">
                        <span>CONFIRMAR PAGO</span>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    </button>
                    @else
                     <div class="text-sm text-gray-400 italic">Complete el pago para continuar</div>
                    @endif
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
                // Podríamos usar un modal más bonito aquí, pero alert funciona
                alert(message);
            });
        });
    </script>
    
    <!-- Toast Notifications (Opcional, si tienes un componente de notificaciones global, esto podría ser redundante pero seguro) -->
    <div class="fixed top-4 right-4 z-[60] flex flex-col gap-2 pointer-events-none">
        @if (session()->has('success'))
            <div class="bg-green-500 text-white px-4 py-3 rounded-lg shadow-lg flex items-center gap-2 animate-bounce-in pointer-events-auto">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                {{ session('success') }}
            </div>
        @endif

        @if (session()->has('error'))
            <div class="bg-red-500 text-white px-4 py-3 rounded-lg shadow-lg flex items-center gap-2 animate-shake pointer-events-auto">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                {{ session('error') }}
            </div>
        @endif
    </div>
</div>