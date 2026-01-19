<div class="p-4 sm:p-6 bg-gray-50 dark:bg-slate-950 min-h-screen transition-colors duration-300">
    <!-- Encabezado Estilo Premium -->
    <div class="mb-6 bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-100 dark:border-slate-800 p-4 sm:p-6 sticky top-0">
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-gray-800 dark:text-gray-100 flex items-center gap-2">
                    <x-heroicon-o-truck class="w-6 h-6 text-blue-600 dark:text-blue-400" />
                    @if($selectedDeliveryId)
                        Gestión de entregas cargue # {{ $selectedDeliveryId }}
                    @else
                        Gestión Global de Entregas
                    @endif
                </h1>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    @if($selectedDeliveryId)
                        Administra las entregas y recaudos del cargue #{{ $selectedDeliveryId }}.
                    @else
                        Visualizando todos los pedidos de la empresa.
                    @endif
                </p>
            </div>
            
            <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
                <div class="bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 px-4 py-2 rounded-lg font-bold text-lg shadow-inner">
                    $ {{ number_format($remissions->sum('total_amount'), 0, ',', '.') }}
                </div>
                @if(auth()->user()->profile_id != 13)
                <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors shadow-sm">
                    Cargue
                </button>
                <button class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium transition-colors shadow-sm flex items-center gap-1">
                    <x-heroicon-o-plus class="w-4 h-4" />
                    Nuevo pedido
                </button>
                @endif
            </div>
        </div>
    </div>

    <div class="flex flex-col lg:flex-row gap-6 w-full">
        <!-- Sidebar de Filtros -->
        <aside class="w-full lg:w-1/4 lg:flex-shrink-0 space-y-6">
            <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-100 dark:border-slate-800 p-4 sticky lg:top-32">
                @if(auth()->user()->profile_id != 13)
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Cargue:</label>
                        <select wire:model.live="selectedDeliveryId" class="w-full rounded-lg border-gray-200 dark:border-slate-700 dark:bg-slate-800 dark:text-gray-200 focus:border-blue-500 focus:ring-blue-500 text-sm">
                            <option value="">Seleccione un cargue</option>
                            @foreach($deliveries as $del)
                                <option value="{{ $del->id }}">Cargue #{{ $del->id }} ({{ $del->created_at->format('Y-m-d') }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Estado Pedido:</label>
                        <select wire:model.live="status" class="w-full rounded-lg border-gray-200 dark:border-slate-700 dark:bg-slate-800 dark:text-gray-200 focus:border-blue-500 focus:ring-blue-500 text-sm">
                            <option value="Todos">Todos</option>
                            <option value="EN RECORRIDO">En Recorrido</option>
                            <option value="ENTREGADO">Entregado</option>
                            <option value="DEVUELTO">Devuelto</option>
                            <option value="REGISTRADO">Registrado</option>
                        </select>
                    </div>

                    <div class="pt-4 border-t border-gray-100 dark:border-slate-800 flex flex-col gap-2">
                        <button class="w-full bg-green-500 hover:bg-green-600 text-white font-bold py-2 rounded-lg transition-all flex items-center justify-center gap-2">
                            <x-heroicon-o-check-circle class="w-5 h-5" />
                            Cierre
                        </button>
                        <div class="grid grid-cols-2 gap-2">
                            <button class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 rounded-lg text-xs flex flex-col items-center">
                                <span class="text-lg">$</span>
                                Recaudado
                            </button>
                            <button class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 rounded-lg text-xs flex flex-col items-center">
                                <x-heroicon-o-arrow-path class="w-5 h-5" />
                                Devoluciones
                            </button>
                        </div>
                    </div>
                </div>
                @else
                <div>
                    <label class="block text-[10px] font-black text-gray-400 dark:text-slate-500 uppercase mb-2 tracking-widest">Mis Cargues Asignados:</label>
                    <select wire:model.live="selectedDeliveryId" class="w-full rounded-lg border-gray-200 dark:border-slate-700 dark:bg-slate-800 dark:text-gray-200 focus:border-blue-500 focus:ring-blue-500 text-sm font-bold">
                        <option value="">Ver todos mis pedidos</option>
                        @foreach($deliveries as $del)
                            <option value="{{ $del->id }}">Cargue #{{ $del->id }} ({{ $del->created_at->format('Y-m-d') }})</option>
                        @endforeach
                    </select>
                    <div class="mt-4 w-full h-px bg-gray-100 dark:bg-slate-800"></div>
                </div>
                @endif
            </div>
        </aside>

        <!-- Lista de Pedidos -->
        <main class="flex-grow space-y-6 w-full">
            <!-- Barra de Búsqueda -->
            <div class="bg-white dark:bg-slate-900 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-slate-800">
                <div class="relative">
                    <x-heroicon-o-magnifying-glass class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 dark:text-gray-500" />
                    <input type="text" wire:model.live="search" placeholder="Buscar por cliente..." 
                           class="w-full pl-10 pr-4 py-2 rounded-lg border-gray-200 dark:border-slate-700 dark:bg-slate-800 dark:text-gray-200 focus:border-blue-500 focus:ring-blue-500 text-sm">
                </div>
            </div>

            <!-- Lista de Pedidos Stacked -->
            <div class="space-y-4 w-full">
                @forelse($remissions as $remission)
                <div wire:click="viewOrder({{ $remission->id }})" 
                     class="bg-white dark:bg-slate-900 text-gray-900 dark:text-white rounded-xl shadow-lg border border-gray-100 dark:border-slate-800 overflow-hidden transform transition hover:scale-[1.01] cursor-pointer group">
                    <div class="p-4 sm:p-6">
                        <div class="flex justify-between items-start mb-4">
                             <h3 class="text-base sm:text-lg font-black tracking-widest uppercase text-blue-600 dark:text-blue-400 group-hover:text-blue-700 dark:group-hover:text-blue-300 transition-colors">
                                PEDIDO # {{ $remission->consecutive ?? $remission->id }} RUTA {{ $remission->quote->branch->name ?? 'N/A' }}
                             </h3>
                             <span class="px-2 py-1 rounded text-xs font-bold uppercase transition-colors {{ $remission->status == 'Cargue' ? 'bg-blue-600 text-white' : ($remission->status == 'Entregado' ? 'bg-green-600 text-white' : 'bg-red-600 text-white') }}">
                                {{ $remission->status }}
                             </span>
                        </div>

                        <div class="space-y-4 text-sm sm:text-base">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div class="space-y-2">
                                    <p class="flex flex-col">
                                        <span class="text-gray-500 dark:text-slate-400 text-xs uppercase font-bold tracking-tighter">Entregar en:</span>
                                        <span class="text-gray-800 dark:text-slate-100 font-semibold">{{ $remission->quote->warehouse->address ?? 'Sin dirección' }}</span>
                                    </p>
                                    <p class="flex flex-col">
                                        <span class="text-gray-500 dark:text-slate-400 text-xs uppercase font-bold tracking-tighter">Cliente:</span>
                                        <span class="text-gray-800 dark:text-slate-100 font-semibold italic">{{ $remission->quote->customer->businessName ?? 'Sin nombre' }}</span>
                                    </p>
                                </div>
                                <div class="space-y-2">
                                    <p class="flex flex-col">
                                        <span class="text-gray-500 dark:text-slate-400 text-xs uppercase font-bold tracking-tighter">Contacto:</span>
                                        <span class="text-gray-800 dark:text-slate-100 font-semibold uppercase">
                                            {{ ($remission->quote->customer->firstName ?? '') . ' ' . ($remission->quote->customer->lastName ?? '') }}
                                        </span>
                                    </p>
                                </div>
                            </div>
                            
                            <div class="pt-4 border-t border-gray-100 dark:border-slate-800 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2">
                                <div class="text-lg sm:text-xl font-black">
                                    TOTAL: <span class="text-gray-900 dark:text-white">$ {{ number_format($remission->total_amount, 0, ',', '.') }}</span>
                                </div>
                                <div class="text-lg sm:text-xl font-black text-blue-600 dark:text-blue-400">
                                    A PAGAR: $ {{ number_format($remission->balance_amount, 0, ',', '.') }}
                                </div>
                            </div>
                        </div>

                        <!-- Botones de Acción -->
                        <div class="mt-6 flex flex-wrap gap-2">
                            @if($remission->balance_amount > 0)
                                <button wire:click.stop="payOrder({{ $remission->id }})" 
                                        class="flex-1 min-w-[120px] bg-green-600 hover:bg-green-700 text-white py-2.5 px-3 rounded-lg text-xs font-bold uppercase flex items-center justify-center gap-1 transition-all shadow-md active:scale-95">
                                    <x-heroicon-o-currency-dollar class="w-4 h-4" />
                                    Pagar
                                </button>
                                <button wire:click.stop="returnOrder({{ $remission->id }})" 
                                        class="flex-1 min-w-[120px] bg-red-600 hover:bg-red-700 text-white py-2.5 px-3 rounded-lg text-xs font-bold uppercase flex items-center justify-center gap-1 transition-all shadow-md active:scale-95">
                                    <x-heroicon-o-arrow-uturn-left class="w-4 h-4" />
                                    Devolver
                                </button>
                            @else
                                <div class="flex-1 min-w-[120px] bg-gray-100 dark:bg-slate-800 text-gray-400 dark:text-slate-400 py-2.5 px-3 rounded-lg text-xs font-bold uppercase text-center flex items-center justify-center gap-1 border border-gray-200 dark:border-slate-700">
                                    <x-heroicon-o-check-circle class="w-4 h-4 text-green-500" />
                                    Pagado
                                </div>
                            @endif

                            <button wire:click.stop="printOrder({{ $remission->id }})" 
                                    class="bg-blue-700 hover:bg-blue-800 text-white p-2.5 rounded-lg transition-all shadow-md active:scale-95 flex items-center justify-center px-4">
                                <x-heroicon-o-printer class="w-5 h-5 mr-1" />
                                <span class="text-xs font-bold uppercase sm:hidden">Imprimir</span>
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="bg-white dark:bg-slate-900 p-12 rounded-xl shadow-sm border border-gray-100 dark:border-slate-800 text-center w-full">
                        <x-heroicon-o-document-magnifying-glass class="w-16 h-16 text-gray-200 dark:text-slate-700 mx-auto mb-4" />
                        <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">No se encontraron pedidos</h3>
                        <p class="text-gray-500 dark:text-gray-400">Intenta cambiar los filtros o el término de búsqueda.</p>
                    </div>
                @endforelse
            </div> <!-- Cierre de la lista -->
        </main> <!-- Cierre del main -->
    </div> <!-- Cierre del flex principal -->

    <div class="py-4">
        {{ $remissions->links() }}
    </div>

    <!-- Modal de Detalle de Pedido -->
    <div x-data="{ open: @entangle('showingOrderModal') }" 
         x-show="open" 
         class="fixed inset-0 z-50 overflow-y-auto" 
         x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="open" 
                 x-transition:enter="ease-out duration-300" 
                 x-transition:enter-start="opacity-0" 
                 x-transition:enter-end="opacity-100" 
                 x-transition:leave="ease-in duration-200" 
                 x-transition:leave-start="opacity-100" 
                 x-transition:leave-end="opacity-0" 
                 class="fixed inset-0 transition-opacity" aria-hidden="true">
                <div class="absolute inset-0 bg-gray-500 dark:bg-slate-950 opacity-75"></div>
            </div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="open" 
                 x-transition:enter="ease-out duration-300" 
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
                 x-transition:leave="ease-in duration-200" 
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                 class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-5xl sm:w-full border border-gray-200 dark:border-slate-800">
                
                @if($selectedOrder)
                <!-- Cabecera del Modal Rediseñada -->
                <div class="bg-slate-900 dark:bg-black px-4 py-4 border-b border-slate-800 dark:border-slate-800 flex justify-between items-center transition-colors">
                    <div class="flex flex-col">
                        <h3 class="text-lg font-black text-yellow-500 uppercase tracking-tighter leading-tight">
                            Pedido # {{ $selectedOrder->consecutive ?? $selectedOrder->id }}
                        </h3>
                        <span class="text-sm font-bold text-gray-200 dark:text-gray-300 uppercase tracking-widest break-words max-w-[250px] sm:max-w-none">
                            {{ $selectedOrder->quote->customer->businessName ?? ($selectedOrder->quote->customer->firstName . ' ' . $selectedOrder->quote->customer->lastName) }}
                        </span>
                    </div>
                    <button @click="open = false; $wire.closeOrderModal()" class="text-slate-400 hover:text-white transition-colors p-2 hover:bg-slate-800 rounded-full">
                        <x-heroicon-o-x-mark class="w-7 h-7" />
                    </button>
                </div>

                <div class="bg-white dark:bg-slate-950 p-0 transform transition-all">
                    <!-- Área de Edición de Producto (Especial Táctil) -->
                    @php
                        $currentDetail = $selectedOrder->details[$currentItemIndex] ?? null;
                    @endphp

                    @if($currentDetail)
                    <div class="p-6 bg-blue-50/50 dark:bg-blue-900/10 border-b border-blue-100 dark:border-blue-900/30">
                        <div class="flex flex-col items-center text-center space-y-6">
                            <div class="space-y-1">
                                <span class="text-[10px] font-black uppercase tracking-[0.2em] text-blue-600 dark:text-blue-400 opacity-70">Editando Producto</span>
                                <h4 class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white leading-tight">
                                    {{ $currentDetail->item->name ?? 'N/A' }}
                                </h4>
                            </div>

                            <!-- Controles de Cantidad Gigantes -->
                            <div class="flex items-center justify-center gap-6 sm:gap-10 py-4">
                                <button wire:click="decrementReturn({{ $currentDetail->id }})" 
                                        class="w-16 h-16 sm:w-20 sm:h-20 flex items-center justify-center bg-white dark:bg-slate-900 border-2 border-red-500 text-red-500 rounded-2xl shadow-lg active:scale-95 transition-all hover:bg-red-50 dark:hover:bg-red-900/20">
                                    <x-heroicon-o-minus class="w-8 h-8 sm:w-10 sm:h-10 stroke-[3]" />
                                </button>

                                <div class="relative group">
                                    <input type="number" 
                                           id="qty-{{ $currentDetail->id }}" 
                                           inputmode="numeric"
                                           wire:model.live="returnQuantities.{{ $currentDetail->id }}"
                                           class="w-32 sm:w-40 text-center text-4xl sm:text-5xl font-black bg-transparent border-none focus:ring-0 text-gray-900 dark:text-white p-0" />
                                    <div class="absolute -bottom-2 left-0 right-0 h-1 bg-blue-500/30 dark:bg-blue-500/20 rounded-full"></div>
                                </div>

                                <button wire:click="incrementReturn({{ $currentDetail->id }})" 
                                        class="w-16 h-16 sm:w-20 sm:h-20 flex items-center justify-center bg-white dark:bg-slate-900 border-2 border-green-500 text-green-500 rounded-2xl shadow-lg active:scale-95 transition-all hover:bg-green-50 dark:hover:bg-green-900/20">
                                    <x-heroicon-o-plus class="w-8 h-8 sm:w-10 sm:h-10 stroke-[3]" />
                                </button>
                            </div>

                            <div class="w-full max-w-md flex justify-between items-center text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-slate-400 px-4">
                                <span>Pedido: {{ number_format($currentDetail->quantity, 0) }}</span>
                                <span class="text-blue-600 dark:text-blue-400">Devolución actual</span>
                            </div>
                        </div>
                    </div>
                    @endif

                    <div class="p-6">
                        <!-- Navegación y Buscador -->
                        <div class="flex flex-col sm:flex-row items-center justify-between gap-4 mb-6">
                            <div class="flex items-center gap-3 w-full sm:w-auto">
                                <button wire:click="previousItem" 
                                        {{ $currentItemIndex == 0 ? 'disabled' : '' }}
                                        class="flex-1 sm:flex-none px-6 py-3 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 disabled:opacity-30 disabled:pointer-events-none rounded-xl font-black text-xs uppercase tracking-widest transition-all text-gray-700 dark:text-gray-200">
                                    Anterior
                                </button>
                                <button wire:click="nextItem" 
                                        {{ $currentItemIndex >= count($selectedOrder->details) - 1 ? 'disabled' : '' }}
                                        class="flex-1 sm:flex-none px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-black text-xs uppercase tracking-widest transition-all shadow-md active:scale-95 shadow-blue-500/20">
                                    Siguiente
                                </button>
                            </div>
                            
                            <div class="relative w-full sm:w-64">
                                <x-heroicon-o-magnifying-glass class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                                <input type="text" placeholder="Buscar producto..." class="w-full pl-10 pr-4 py-2.5 rounded-xl border-gray-100 dark:border-slate-800 dark:bg-slate-900 dark:text-gray-200 text-sm focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 transition-all">
                            </div>
                        </div>

                        <!-- Mini Tabla Resumen -->
                        <div class="overflow-x-auto border border-gray-100 dark:border-slate-800 rounded-2xl">
                            <table class="w-full text-sm text-left">
                                <thead class="bg-gray-50 dark:bg-slate-900/50 text-gray-400 dark:text-slate-500 text-[10px] uppercase font-black tracking-[0.1em]">
                                    <tr>
                                        <th class="px-5 py-4">Ítem</th>
                                        <th class="px-5 py-4 text-center">Cant</th>
                                        <th class="px-5 py-4 text-center">Devol</th>
                                        <th class="px-5 py-4 text-right">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-50 dark:divide-slate-900">
                                    @foreach($selectedOrder->details as $index => $detail)
                                    <tr class="transition-colors {{ $index === $currentItemIndex ? 'bg-blue-500/[0.03] dark:bg-blue-500/[0.05]' : '' }}">
                                        <td class="px-5 py-4 font-bold text-gray-700 dark:text-gray-300">
                                            <div class="flex items-center gap-3">
                                                @if($index === $currentItemIndex)
                                                    <div class="w-2 h-2 bg-blue-500 rounded-full animate-pulse"></div>
                                                @endif
                                                {{ $detail->item->name ?? 'N/A' }}
                                            </div>
                                        </td>
                                        <td class="px-5 py-4 text-center text-gray-600 dark:text-gray-400 font-medium">
                                            {{ number_format($detail->quantity, 0) }}
                                        </td>
                                        <td class="px-5 py-4 text-center">
                                            <span class="px-2.5 py-1 rounded-lg {{ ($returnQuantities[$detail->id] ?? 0) > 0 ? 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 font-black' : 'text-gray-400 dark:text-slate-600' }}">
                                                {{ $returnQuantities[$detail->id] ?? 0 }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-4 text-right font-black text-gray-900 dark:text-white">
                                            $ {{ number_format(($detail->quantity - ($returnQuantities[$detail->id] ?? 0)) * $detail->value, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="bg-gray-50 dark:bg-slate-900/80 text-gray-900 dark:text-white font-black border-t border-gray-100 dark:border-slate-800">
                                    <tr>
                                        <td class="px-5 py-4 uppercase text-[10px] tracking-widest opacity-60">Total Pedido</td>
                                        <td class="px-5 py-4 text-center">{{ number_format($selectedOrder->details->sum('quantity'), 0) }}</td>
                                        <td class="px-5 py-4 text-center text-red-500">{{ array_sum($returnQuantities) }}</td>
                                        <td class="px-5 py-4 text-right text-lg">
                                            $ {{ number_format($selectedOrder->details->sum(fn($d) => ($d->quantity - ($returnQuantities[$d->id] ?? 0)) * $d->value), 0, ',', '.') }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 dark:bg-slate-900/50 px-6 py-4 flex flex-col sm:flex-row justify-between items-center gap-4 border-t border-gray-100 dark:border-slate-800">
                    <div class="flex flex-col items-center sm:items-start text-center sm:text-left">
                        <span class="text-[10px] font-black uppercase tracking-widest text-gray-400 dark:text-slate-500">Valor Final Neto</span>
                        <div class="text-2xl font-black text-green-600 dark:text-green-500">
                            @php
                                $netTotal = $selectedOrder->details->sum(function($d) use ($returnQuantities) {
                                    $returns = $returnQuantities[$d->id] ?? 0;
                                    return ($d->quantity - $returns) * $d->value;
                                });
                            @endphp
                            $ {{ number_format($netTotal, 0, ',', '.') }}
                        </div>
                    </div>
                    <button @click="open = false; $wire.closeOrderModal()" class="w-full sm:w-auto bg-red-500 hover:bg-red-600 text-white font-black py-3 px-8 rounded-xl transition-all shadow-md active:scale-95 text-xs uppercase tracking-widest">
                        Cerrar
                    </button>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
