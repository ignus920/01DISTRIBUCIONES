<div 
x-data="{
        init() {
            // Toast notifications
            Livewire.on('show-toast', (data) => {
                const payload = Array.isArray(data) ? data[0] : data;
                const Toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true
                });
                Toast.fire({
                    icon: payload.type || 'info',
                    title: payload.message
                });
            });
            
            // Redirect
            Livewire.on('item-marked-received', () => {
                window.location.href = '{{ route('tenant.tat.restock.list') }}';
            });
        },
        confirmMarkAsReceived() {
            Swal.fire({
                title: '¿Confirmar recepción?',
                text: '¿Estás seguro de marcar este producto como recibido? Esta acción es definitiva.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#ef4444',
                confirmButtonText: 'Sí, confirmar',
                cancelButtonText: 'Cancelar',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    $wire.proceedMarkReceived();
                }
            });
        }
    }"
    @confirm-mark-received.window="confirmMarkAsReceived"

    class="p-2 sm:p-4 dark:bg-slate-900 flex items-start sm:items-center justify-center transition-colors">
    <!-- Main Card Container -->
    <div class="bg-white dark:bg-slate-800 rounded-lg shadow-xl w-full max-w-5xl border border-gray-200 dark:border-slate-700 overflow-hidden">

        <!-- Header with improved mobile layout -->
        <div class="bg-indigo-900 px-3 sm:px-5 py-3 sm:py-4 text-white">
            <!-- Top row: Title and close -->
            <div class="flex justify-between items-center mb-2">
                <h2 class="text-base sm:text-lg font-bold flex items-center">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                    </svg>
                    <span class="hidden sm:inline">Conteo &middot; </span>
                    @if($currentItem)
                        <span class="ml-2 text-indigo-200">
                             #{{ $currentItem->order_number }}
                        </span>
                    @else
                        <span>Conteo</span>
                    @endif
                </h2>

                <a href="{{ route('tenant.tat.restock.list') }}" class="text-gray-300 hover:text-white transition-colors p-1">
                    <svg class="w-5 h-5 sm:w-6 sm:h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </a>
            </div>

            <!-- Bottom row: Counter, Date and Status -->
            <div class="flex justify-between items-center">
                <div class="flex items-center space-x-2">
                    <div class="text-xs sm:text-sm font-mono bg-indigo-800 px-2 py-1 rounded">
                        {{ $currentIndex + 1 }} / {{ count($items) }}
                    </div>

                    @if($currentItem && $currentItem->created_at)
                        <div class="text-xs text-indigo-200 hidden sm:block">
                            <svg class="w-3 h-3 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/>
                            </svg>
                            {{ \Carbon\Carbon::parse($currentItem->created_at)->format('d/m/Y') }}
                        </div>
                    @endif
                </div>

                <div class="flex items-center space-x-2">
                    @if($currentItem && $currentItem->created_at)
                        <div class="text-xs text-indigo-200 block sm:hidden">
                            {{ \Carbon\Carbon::parse($currentItem->created_at)->format('d/m') }}
                        </div>
                    @endif

                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                        <div class="w-2 h-2 bg-yellow-500 rounded-full mr-1 animate-pulse"></div>
                        Pendiente
                    </span>
                </div>
            </div>
        </div>

        @if($currentItem)
            <div class="p-3 sm:p-6 space-y-4 sm:space-y-6">

                <!-- Product Name Header - Mobile first -->
                <div class="text-center py-2">
                    <h1 class="text-lg sm:text-xl lg:text-2xl font-bold text-gray-800 dark:text-white uppercase leading-tight">
                        {{ $currentItem->item_name }}
                    </h1>
                    <p class="text-gray-500 dark:text-slate-400 text-xs sm:text-sm mt-1">
                        {{ $currentItem->remise_number ? 'Remisión: ' . $currentItem->remise_number : 'Orden #' . $currentItem->order_number }}
                    </p>
                </div>

                <!-- Enhanced Info Grid - Mobile optimized -->
                <div class="space-y-3 border-b pb-4 dark:border-slate-700">
                    <!-- Código - Full width on all screens -->
                    <div class="bg-gray-50 dark:bg-slate-700/50 p-3 rounded-lg">
                        <span class="block text-gray-500 dark:text-slate-400 text-xs uppercase tracking-wide mb-1">Código</span>
                        <span class="text-lg sm:text-xl font-bold dark:text-white font-mono">{{ $currentItem->sku }}</span>
                    </div>

                    <!-- Existencias y Solicitado - Side by side on mobile -->
                    <div class="grid grid-cols-2 gap-3 sm:gap-4">
                        <div class="bg-gray-50 dark:bg-slate-700/50 p-3 rounded-lg text-center">
                            <span class="block text-gray-500 dark:text-slate-400 text-xs uppercase tracking-wide mb-1">Existencias</span>
                            <span class="inline-block bg-black text-white dark:bg-slate-900 rounded px-2 py-1 text-base sm:text-lg lg:text-xl font-bold font-mono">
                                {{ number_format($currentItem->stock ?? 0, 0) }}
                            </span>
                        </div>

                        <div class="bg-blue-50 dark:bg-blue-900/30 p-3 rounded-lg text-center">
                            <span class="block text-blue-600 dark:text-blue-400 text-xs uppercase tracking-wide mb-1">Solicitado</span>
                            <span class="text-base sm:text-lg lg:text-xl font-bold text-blue-800 dark:text-blue-300 font-mono">
                                {{ number_format($currentItem->quantity_request, 0) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Enhanced Inputs Grid - Mobile optimized side by side -->
                <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:gap-6">
                    <!-- Cantidad Input -->
                    <div>
                        <label class="block text-gray-700 dark:text-slate-300 font-bold mb-2 text-xs sm:text-sm lg:text-base">
                            Cantidad Contada *
                        </label>
                        <input
                            type="number"
                            wire:model.live.debounce.300ms="quantityReceived"
                            class="w-full text-xl sm:text-2xl lg:text-3xl font-bold text-center border-2 border-indigo-500 rounded-lg p-2 sm:p-3 lg:p-4 dark:bg-slate-700 dark:text-white focus:ring-4 focus:ring-indigo-200 focus:border-indigo-600 transition-all touch-manipulation"
                            min="0"
                            pattern="[0-9]*"
                            inputmode="numeric"
                            placeholder="0"
                        >
                    </div>

                    <!-- Diferencia Display -->
                    <div>
                        <label class="block text-gray-700 dark:text-slate-300 font-medium mb-2 text-xs sm:text-sm lg:text-base">Diferencia</label>
                        <div class="w-full text-xl sm:text-2xl lg:text-3xl font-bold text-center border-2 rounded-lg p-2 sm:p-3 lg:p-4 transition-all
                            {{ $difference < 0 ? 'border-red-300 bg-red-50 text-red-600 dark:border-red-600 dark:bg-red-900/20 dark:text-red-400' :
                               ($difference > 0 ? 'border-green-300 bg-green-50 text-green-600 dark:border-green-600 dark:bg-green-900/20 dark:text-green-400' :
                                'border-gray-300 bg-gray-50 text-gray-800 dark:border-slate-600 dark:bg-slate-800 dark:text-gray-200') }}">
                            {{ $difference > 0 ? '+' : '' }}{{ $difference }}
                        </div>
                    </div>
                </div>

                <!-- Difference status indicator - Full width on mobile -->
                <div class="text-center -mt-2 sm:mt-0">
                    @if($difference < 0)
                        
                    @elseif($difference > 0)
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/50 dark:text-green-300">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            Exceso
                        </span>
                    @else
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-gray-900/50 dark:text-gray-300">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            Exacto
                        </span>
                    @endif
                </div>
                
                <!-- Observations -->
                <!-- <div>
                   <label class="block text-gray-700 dark:text-slate-300 font-medium mb-2 text-xs sm:text-sm">
                       Observaciones (opcional)
                   </label>
                   <textarea
                        wire:model.blur="observations"
                        class="w-full border border-gray-300 dark:border-slate-600 rounded-lg p-3 dark:bg-slate-700 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm resize-none transition-all"
                        rows="3"
                        placeholder="Agregar observaciones sobre el conteo..."
                   ></textarea>
                </div> -->

                <!-- Enhanced Footer Actions -->
                <div class="border-t dark:border-slate-700 pt-4 sm:pt-6">
                    <!-- Mobile Layout - Navigation only -->
                    <div class="block lg:hidden">
                        <div class="grid grid-cols-2 gap-3">
                            <button
                                wire:click="previous"
                                class="px-4 py-3 border border-gray-300 dark:border-slate-600 rounded-lg text-gray-700 dark:text-white font-medium hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed touch-manipulation"
                                {{ $currentIndex == 0 ? 'disabled' : '' }}
                            >
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                </svg>
                                Anterior
                            </button>

                            <button
                                wire:click="next"
                                class="px-4 py-3 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white rounded-lg font-medium transition-colors flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed touch-manipulation"
                                {{ $currentIndex == count($items) - 1 ? 'disabled' : '' }}
                            >
                                Siguiente
                                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Desktop/Tablet Layout - All 4 buttons in one line -->
                    <div class="hidden lg:flex justify-between items-center">
                        <!-- All Actions in one line -->
                        <div class="flex items-center justify-between w-full">
                            <!-- Left side: Navigation -->
                            <div class="flex items-center space-x-3">
                                <button
                                    wire:click="previous"
                                    class="px-6 py-3 border border-gray-300 dark:border-slate-600 rounded-lg text-gray-700 dark:text-white font-medium hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors flex items-center disabled:opacity-50 disabled:cursor-not-allowed"
                                    {{ $currentIndex == 0 ? 'disabled' : '' }}
                                >
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                    </svg>
                                    Anterior
                                </button>

                                <button
                                    wire:click="next"
                                    class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-medium transition-colors flex items-center disabled:opacity-50 disabled:cursor-not-allowed"
                                    {{ $currentIndex == count($items) - 1 ? 'disabled' : '' }}
                                >
                                    Siguiente
                                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </button>
                            </div>

                            <!-- Right side: Main actions -->
                            <div class="flex items-center space-x-3">
                                @if($currentIndex === count($items) - 1 && $quantityReceived > 0)
                                    <button
                                        type="button"
                                        wire:click="proceedMarkReceived"
                                        wire:confirm="¿Estás seguro de marcar este producto como recibido? Esta acción es definitiva."
                                        class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg font-semibold transition-colors shadow-sm flex items-center"
                                    >
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                        Finalizar y Confirmar
                                    </button>
                                @endif

                                <a href="{{ route('tenant.tat.restock.list') }}" class="inline-flex items-center px-6 py-3 bg-red-600 border border-transparent rounded-lg font-semibold text-white hover:bg-red-500 active:bg-red-700 transition-colors">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                    Volver a la lista
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
             <!-- Enhanced Footer with Actions -->
             <div class="bg-gray-50 dark:bg-slate-900/50 px-3 sm:px-6 py-3 sm:py-4">
                  <!-- Mobile: Primary actions side by side -->
                  <div class="block lg:hidden">
                      <div class="grid grid-cols-2 gap-3">
                          @if($currentIndex === count($items) - 1 && $quantityReceived > 0)
                              <button
                                  type="button"
                                  wire:click="proceedMarkReceived"
                                  wire:confirm="¿Estás seguro de marcar este producto como recibido? Esta acción es definitiva."
                                  class="w-full px-4 py-3 bg-green-600 hover:bg-green-700 active:bg-green-800 text-white rounded-lg font-semibold text-sm transition-all shadow-lg flex items-center justify-center touch-manipulation"
                              >
                                  <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                  </svg>
                                  Finalizar
                              </button>
                          @endif

                          <a href="{{ route('tenant.tat.restock.list') }}" class="inline-flex items-center justify-center w-full px-4 py-3 bg-red-600 border border-transparent rounded-lg font-semibold text-sm text-white hover:bg-red-500 active:bg-red-700 transition-all touch-manipulation {{ $currentIndex === count($items) - 1 ? '' : 'col-span-2' }}">
                              <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                              </svg>
                              Volver
                          </a>
                      </div>
                  </div>

                  <!-- Desktop: Footer hidden since buttons are in top section -->
                  <div class="hidden lg:block">
                      <!-- Footer empty on desktop since all buttons are above -->
                  </div>
            </div>

        @else
            <!-- Enhanced Empty State -->
            <div class="p-6 sm:p-12 text-center">
                <div class="text-gray-400 mb-6">
                     <svg class="w-12 h-12 sm:w-16 sm:h-16 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
                <h3 class="text-lg sm:text-xl font-semibold text-gray-900 dark:text-white">¡Todo listo!</h3>
                <p class="text-gray-500 dark:text-slate-400 mt-2 text-sm sm:text-base max-w-md mx-auto">
                    No hay pedidos pendientes por recibir. Todos los productos han sido procesados correctamente.
                </p>
                <div class="mt-8">
                    <a href="{{ route('tenant.tat.restock.list') }}" class="inline-flex items-center bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white px-6 py-3 rounded-lg font-medium transition-all shadow-sm touch-manipulation">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5a2 2 0 012-2h4a2 2 0 012 2v6a2 2 0 01-2 2H6a2 2 0 01-2-2V7a2 2 0 012-2h2V5z"></path>
                        </svg>
                        Volver a la lista
                    </a>
                </div>
            </div>
        @endif

    </div>
</div>


