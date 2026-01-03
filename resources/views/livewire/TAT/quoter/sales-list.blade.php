<div class="min-h-screen bg-gray-50 dark:bg-slate-900 transition-colors duration-200">
    <div class="px-4 sm:px-6 lg:px-8 py-6">
        <!-- Header -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6 mb-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Ventas</h1>
                    <p class="text-sm text-gray-600 dark:text-slate-400 mt-1">Gestión de registros</p>
                </div>
                <a href="{{ route('tenant.tat.quoter.index', ['new' => 'true']) }}"
                   class="inline-flex items-center px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 text-white text-sm font-medium rounded-lg transition-all duration-200 shadow-sm hover:shadow">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Nueva Venta
                </a>
            </div>
        </div>

        <!-- Buscador y controles -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-4 mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <!-- Buscador -->
                <div class="relative flex-1 max-w-md">
                    <svg class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input wire:model.live.debounce.300ms="search"
                           type="text"
                           placeholder="Buscar registros..."
                           class="pl-10 pr-4 py-2 w-full bg-gray-50 dark:bg-slate-700 border border-gray-300 dark:border-slate-600 rounded-lg text-sm text-gray-900 dark:text-slate-200 placeholder-gray-500 dark:placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-indigo-400 focus:border-transparent transition-all">
                </div>

                <!-- Controles -->
                <div class="flex items-center gap-3">
                     <div class="flex items-center gap-2">
                            <label class="text-sm text-gray-700 dark:text-gray-300">Mostrar:</label>
                            <select wire:model.live="perPage"
                                class="border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="5">5</option>
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>
                    
                    <!-- Botones de exportar -->
                        <x-export-buttons />
                </div>
            </div>
        </div>

        <!-- Tabla -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 overflow-hidden relative">
            <div class="overflow-x-auto overflow-y-visible">
                <table class="w-full divide-y divide-gray-200 dark:divide-slate-700" style="min-width: 1000px;">
                    <thead class="bg-gray-50 dark:bg-slate-800/50">
                        <tr>
                            <th class="px-3 py-3.5 text-left text-xs font-medium text-gray-600 dark:text-slate-400 uppercase tracking-wider w-20">COTIZACIÓN #</th>
                            <th class="px-3 py-3.5 text-left text-xs font-medium text-gray-600 dark:text-slate-400 uppercase tracking-wider w-64">CLIENTE</th>
                            <th class="px-3 py-3.5 text-left text-xs font-medium text-gray-600 dark:text-slate-400 uppercase tracking-wider w-16">TIPO</th>
                            <th class="px-3 py-3.5 text-left text-xs font-medium text-gray-600 dark:text-slate-400 uppercase tracking-wider w-24">ESTADO</th>
                            <th class="px-3 py-3.5 text-left text-xs font-medium text-gray-600 dark:text-slate-400 uppercase tracking-wider w-32">SUCURSAL</th>
                            <th class="px-3 py-3.5 text-left text-xs font-medium text-gray-600 dark:text-slate-400 uppercase tracking-wider w-28">TELÉFONO</th>
                            <th class="px-3 py-3.5 text-left text-xs font-medium text-gray-600 dark:text-slate-400 uppercase tracking-wider w-32">FECHA</th>
                            <th class="px-3 py-3.5 text-center text-xs font-medium text-gray-600 dark:text-slate-400 uppercase tracking-wider w-24">ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700/50">
                        @forelse($quotes as $quote)
                            <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/30 transition-colors duration-150">
                                <td class="px-3 py-4 whitespace-nowrap">
                                    <span class="text-sm font-semibold text-gray-900 dark:text-white">#{{ $quote->consecutive }}</span>
                                </td>
                                <td class="px-3 py-4">
                                    @if($quote->customer)
                                        <div class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $quote->customer->full_name }}</div>
                                        <div class="text-xs text-gray-600 dark:text-slate-400 mt-0.5">{{ $quote->customer->identification }}</div>
                                        <div class="text-xs text-gray-500 dark:text-slate-500 mt-0.5 truncate">{{ $quote->customer->billingEmail ?? 'Sin email' }}</div>
                                    @else
                                        <div class="text-sm text-gray-500 dark:text-slate-400">Sin cliente</div>
                                    @endif
                                </td>
                                <td class="px-3 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-blue-500/10 dark:bg-blue-500/20 text-blue-700 dark:text-blue-400 border border-blue-500/20 dark:border-blue-500/30">
                                        POS
                                    </span>
                                </td>
                                <td class="px-3 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium border
                                        {{ $quote->status === 'Registrado'
                                            ? 'bg-green-500/10 dark:bg-green-500/20 text-green-700 dark:text-green-400 border-green-500/20 dark:border-green-500/30'
                                            : 'bg-red-500/10 dark:bg-red-500/20 text-red-700 dark:text-red-400 border-red-500/20 dark:border-red-500/30' }}">
                                        {{ strtoupper($quote->status) }}
                                    </span>
                                </td>
                                <td class="px-3 py-4">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">Soacha</div>
                                    <div class="text-xs text-gray-500 dark:text-slate-500 mt-0.5 truncate">calle 25a+5a-20</div>
                                </td>
                                <td class="px-3 py-4 whitespace-nowrap">
                                    <span class="text-sm text-gray-900 dark:text-slate-200">{{ $quote->customer->business_phone ?? '3208614517' }}</span>
                                </td>
                                <td class="px-3 py-4 whitespace-nowrap">
                                    <span class="text-sm text-gray-900 dark:text-slate-200">{{ $quote->created_at->format('d/m/Y H:i') }}</span>
                                </td>
                                <td class="px-3 py-4 whitespace-nowrap text-center">
                                    <div class="relative isolation-auto" x-data="{ open: false }">
                                        <!-- Botón de tres puntos -->
                                        <button @click="open = !open"
                                                class="inline-flex items-center justify-center w-8 h-8 text-gray-500 dark:text-slate-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg transition-all duration-150">
                                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"/>
                                            </svg>
                                        </button>

                                        <!-- Dropdown Menu -->
                                        <div x-show="open"
                                             @click.away="open = false"
                                             x-transition:enter="transition ease-out duration-100"
                                             x-transition:enter-start="transform opacity-0 scale-95"
                                             x-transition:enter-end="transform opacity-100 scale-100"
                                             x-transition:leave="transition ease-in duration-75"
                                             x-transition:leave-start="transform opacity-100 scale-100"
                                             x-transition:leave-end="transform opacity-0 scale-95"
                                             class="fixed z-[100] w-48 bg-white dark:bg-slate-800 rounded-lg shadow-xl border border-gray-200 dark:border-slate-700 py-1"
                                             x-init="$watch('open', value => {
                                                 if (value) {
                                                     $nextTick(() => {
                                                         let rect = $el.parentElement.getBoundingClientRect();
                                                         $el.style.top = (rect.bottom + 8) + 'px';
                                                         $el.style.left = (rect.right - 192) + 'px';
                                                     });
                                                 }
                                             })"
                                             @scroll.window="open = false"
                                             style="box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);">

                                            <!-- Opción Detalle -->
                                            <button wire:click="showDetails({{ $quote->id }})"
                                                    @click="openDropdown = null"
                                                    class="w-full flex items-center px-4 py-2 text-sm text-gray-700 dark:text-slate-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 hover:text-blue-600 dark:hover:text-blue-400 transition-colors duration-150">
                                                <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                                Ver Detalle
                                            </button>

                                            <!-- Opción Editar (solo para ventas no pagadas) -->
                                            @if($quote->status !== 'Pagado')
                                                <button wire:click="editSale({{ $quote->id }})"
                                                        @click="open = false"
                                                        class="w-full flex items-center px-4 py-2 text-sm text-gray-700 dark:text-slate-300 hover:bg-orange-50 dark:hover:bg-orange-900/20 hover:text-orange-600 dark:hover:text-orange-400 transition-colors duration-150">
                                                    <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                    </svg>
                                                    Editar Venta
                                                </button>
                                            @endif

                                            <!-- Separador -->
                                            <div class="border-t border-gray-100 dark:border-slate-700 my-1"></div>

                                            <!-- Opción Pagar -->
                                            @if($quote->status !== 'Pagado')
                                                <button wire:click="showPayment({{ $quote->id }})"
                                                        @click="open = false"
                                                        class="w-full flex items-center px-4 py-2 text-sm text-gray-700 dark:text-slate-300 hover:bg-green-50 dark:hover:bg-green-900/20 hover:text-green-600 dark:hover:text-green-400 transition-colors duration-150">
                                                    <svg class="w-4 h-4 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                                                    </svg>
                                                    Procesar Pago
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="px-6 py-16 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <div class="w-16 h-16 bg-gray-100 dark:bg-slate-700 rounded-full flex items-center justify-center mb-4">
                                            <svg class="w-8 h-8 text-gray-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                        </div>
                                        <p class="text-base font-medium text-gray-900 dark:text-white mb-1">No hay cotizaciones registradas</p>
                                        <p class="text-sm text-gray-500 dark:text-slate-400">Comienza creando una nueva cotización</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Paginación -->
        @if($quotes->hasPages())
            <div class="mt-6">
                {{ $quotes->links() }}
            </div>
        @endif
    </div>

    <!-- Modal de Detalles -->
    @if($showDetailModal && $selectedQuote)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data="{ show: @entangle('showDetailModal') }">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <!-- Overlay -->
                <div x-show="show" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-80" wire:click="closeDetailModal"></div>

                <!-- Modal -->
                <div x-show="show" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" class="inline-block align-bottom bg-white dark:bg-slate-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full sm:p-6">
                    <!-- Header -->
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                            Detalles de Cotización #{{ $selectedQuote->consecutive }}
                        </h3>
                        <button wire:click="closeDetailModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Información -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <!-- Info del Cliente -->
                        <div class="bg-gray-50 dark:bg-slate-700/50 rounded-lg p-4">
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Información del Cliente</h4>
                            @if($selectedQuote->customer)
                                <div class="space-y-2 text-sm">
                                    <p><span class="text-gray-600 dark:text-slate-400">Nombre:</span> <span class="font-medium text-gray-900 dark:text-white">{{ $selectedQuote->customer->full_name }}</span></p>
                                    <p><span class="text-gray-600 dark:text-slate-400">Identificación:</span> <span class="text-gray-900 dark:text-white">{{ $selectedQuote->customer->identification }}</span></p>
                                    @if($selectedQuote->customer->billingEmail)
                                        <p><span class="text-gray-600 dark:text-slate-400">Email:</span> <span class="text-gray-900 dark:text-white">{{ $selectedQuote->customer->billingEmail }}</span></p>
                                    @endif
                                </div>
                            @else
                                <p class="text-gray-500 dark:text-slate-400 text-sm">Sin información de cliente</p>
                            @endif
                        </div>

                        <!-- Detalles Generales -->
                        <div class="bg-gray-50 dark:bg-slate-700/50 rounded-lg p-4">
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-3">Detalles Generales</h4>
                            <div class="space-y-2 text-sm">
                                <p><span class="text-gray-600 dark:text-slate-400">Fecha:</span> <span class="text-gray-900 dark:text-white">{{ $selectedQuote->created_at->format('d/m/Y H:i') }}</span></p>
                                <p><span class="text-gray-600 dark:text-slate-400">Estado:</span>
                                    <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium border
                                        {{ $selectedQuote->status === 'Registrado'
                                            ? 'bg-green-500/10 text-green-700 border-green-500/20'
                                            : 'bg-red-500/10 text-red-700 border-red-500/20' }}">
                                        {{ strtoupper($selectedQuote->status) }}
                                    </span>
                                </p>
                                <p><span class="text-gray-600 dark:text-slate-400">Tipo:</span> <span class="text-blue-600 dark:text-blue-400 font-medium">POS</span></p>
                                <p><span class="text-gray-600 dark:text-slate-400">Sucursal:</span> <span class="text-gray-900 dark:text-white">Soacha</span></p>
                            </div>
                        </div>
                    </div>

                    <!-- Productos -->
                    <div class="bg-gray-50 dark:bg-slate-700/50 rounded-lg overflow-hidden">
                        <div class="px-4 py-3 border-b border-gray-200 dark:border-slate-600">
                            <h4 class="text-sm font-medium text-gray-900 dark:text-white">Productos</h4>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full">
                                <thead class="bg-gray-100 dark:bg-slate-700">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-medium text-gray-600 dark:text-slate-400 uppercase">Producto</th>
                                        <th class="px-4 py-2 text-center text-xs font-medium text-gray-600 dark:text-slate-400 uppercase">Cant.</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-600 dark:text-slate-400 uppercase">Precio Unit.</th>
                                        <th class="px-4 py-2 text-right text-xs font-medium text-gray-600 dark:text-slate-400 uppercase">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700">
                                    @foreach($selectedQuote->items as $item)
                                        <tr>
                                            <td class="px-4 py-3">
                                                <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $item->descripcion }}</div>
                                                @if($item->item)
                                                    <div class="text-xs text-gray-500 dark:text-slate-400">SKU: {{ $item->item->sku ?? 'N/A' }}</div>
                                                @endif
                                            </td>
                                            <td class="px-4 py-3 text-center text-sm text-gray-900 dark:text-white">{{ $item->quantity }}</td>
                                            <td class="px-4 py-3 text-right text-sm text-gray-900 dark:text-white">${{ number_format($item->price, 0, '.', '.') }}</td>
                                            <td class="px-4 py-3 text-right text-sm font-medium text-gray-900 dark:text-white">${{ number_format($item->quantity * $item->price, 0, '.', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="bg-gray-50 dark:bg-slate-700">
                                    <tr>
                                        <td colspan="3" class="px-4 py-3 text-right text-sm font-medium text-gray-900 dark:text-white">Total General:</td>
                                        <td class="px-4 py-3 text-right text-lg font-bold text-blue-600 dark:text-blue-400">
                                            ${{ number_format($selectedQuote->items->sum(function($item) { return $item->quantity * $item->price; }), 0, '.', '.') }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="mt-6 flex justify-end">
                        <button wire:click="closeDetailModal" class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white text-sm font-medium rounded-lg transition-colors duration-150">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

</div>