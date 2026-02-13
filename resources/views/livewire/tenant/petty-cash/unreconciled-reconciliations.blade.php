<div class="w-full">
    <!-- Toolbar -->
    <div class="p-6 py-3 md:py-2 border-b border-gray-200 dark:border-gray-700">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
            <!-- Búsqueda -->
            <div class="flex-1 max-w-sm">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <x-heroicon-o-eye class="h-4 w-4 text-gray-400 dark:text-gray-500" />
                    </div>
                    <input wire:model.live.debounce.300ms="search" type="text" placeholder="Buscar registros..."
                        class="block w-full pl-10 pr-2 py-1 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500">
                </div>
            </div>

            <!-- Controles -->
            <div class="flex items-center gap-3">
                <!-- Registros por página -->
                <div class="flex items-center gap-2">
                    <label class="text-sm text-gray-700 dark:text-gray-300">Mostrar:</label>
                    <select wire:model.live="perPage"
                        class="border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="5">5</option>
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </div>

                @if($this->getStatusPettyCash()==1)
                <div class="">
                    <button wire:click="openSalesFinishModal({{$pettyCash_id}})" wire:loading.attr="disabled"
                        class="inline-flex justify-center items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 transition ease-in-out duration-150 relative">
                        <span wire:loading.remove wire:target="openSalesFinishModal({{$pettyCash_id}})" class="flex items-center">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4v16m8-8H4"></path>
                            </svg>
                            Arqueo/Cierre
                        </span>
                        <span wire:loading wire:target="openSalesFinishModal({{$pettyCash_id}})" class="flex items-center">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Cargando...
                        </span>
                    </button>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Tabla -->
    <div class="relative overflow-visible">
        <div class="min-w-full overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-900">
                    <tr>
                        <th wire:click="sortBy('id')"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 select-none">
                            <div class="flex items-center gap-1">
                                ID
                                @if($sortField === 'id')
                                @if($sortDirection === 'desc')
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z">
                                    </path>
                                </svg>
                                @else
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z">
                                    </path>
                                </svg>
                                @endif
                                @endif
                            </div>
                        </th>
                        <th wire:click="sortBy('created_at')"
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 select-none">
                            <div class="flex items-center gap-1">
                                Fecha
                                @if($sortField === 'created_at')
                                @if($sortDirection === 'desc')
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z">
                                    </path>
                                </svg>
                                @else
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z">
                                    </path>
                                </svg>
                                @endif
                                @endif
                            </div>
                        </th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Usuario</th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Observaciones</th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($reconciliations as $reconciliation)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                        <td class="px-5 py-3 md:py-1 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                            #{{ $reconciliation->id }}
                        </td>
                        <td class="px-5 py-2 md:py-1 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            {{ $reconciliation->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-5 py-2 md:py-1 text-sm text-gray-500 dark:text-gray-400">
                            {{ $reconciliation->user->name ?? 'N/A' }}
                        </td>
                        <td class="px-5 py-2 md:py-1 text-sm text-gray-500 dark:text-gray-400">
                            <span class="truncate block max-w-xs">{{ $reconciliation->observations ?? '-' }}</span>
                        </td>
                        <td class="px-5 py-2 md:py-1 whitespace-nowrap text-center text-sm font-medium">
                            <div x-data="{ open: false }" @click.outside="open = false" 
                                class="relative inline-block text-left static"
                                style="position: static !important;">
                                <button @click="open = !open" x-ref="button"
                                    class="flex items-center text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 rounded-lg p-1 transition-colors">
                                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                                    </svg>
                                </button>
    
                                <div x-show="open" x-transition:enter="transition ease-out duration-100"
                                    x-transition:enter-start="transform opacity-0 scale-95"
                                    x-transition:enter-end="transform opacity-100 scale-100"
                                    x-transition:leave="transition ease-in duration-75"
                                    x-transition:leave-start="transform opacity-100 scale-100"
                                    x-transition:leave-end="transform opacity-0 scale-95" @click="open = false"
                                    class="origin-top-left fixed left-auto right-auto mt-2 w-48 rounded-lg shadow-lg bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 dark:ring-gray-700 z-[60]"
                                    x-anchor="$refs.button"
                                    style="display: none;">
    
                                    <div class="py-1" role="menu" aria-orientation="vertical">
                                        <button wire:click="viewDetail({{ $reconciliation->id }})"
                                            class="w-full text-left px-4 py-2 text-sm text-blue-800 dark:text-blue-300 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition-colors flex items-center">
                                            <x-heroicon-o-eye class="w-5 h-5 mr-1" />
                                            
                                            Ver Detalle
                                        </button>
                                        <button wire:click="ticketPettyCash({{ $reconciliation->id }}, {{ $pettyCash_id }})"
                                            class="w-full text-left px-4 py-2 text-sm text-green-800 dark:text-green-300 hover:bg-green-50 dark:hover:bg-green-900/20 transition-colors flex items-center">
                                            <x-heroicon-o-document class="w-6 h-6 mr-1" />
                                            PDF
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                            <div class="flex flex-col items-center">
                                <svg class="w-12 h-12 mb-4 text-gray-400 dark:text-gray-600" fill="none"
                                    stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <p class="text-lg font-medium">No hay reconciliaciones pendientes</p>
                                <p class="text-sm">Todas las reconciliaciones han sido completadas</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Paginación -->
    @if($reconciliations->hasPages())
    <div class="bg-white dark:bg-gray-800 px-6 py-3 border-t border-gray-200 dark:border-gray-700 rounded-b-lg">
        <div class="flex items-center justify-between">
            <div class="text-sm text-gray-700 dark:text-gray-300">
                Mostrando {{ $reconciliations->firstItem() }} a {{ $reconciliations->lastItem() }} de
                {{ $reconciliations->total() }} resultados
            </div>
            <div>
                {{ $reconciliations->links() }}
            </div>
        </div>
    </div>
    @endif

    <!-- Modal de Detalle -->
    @if($showDetail && $selectedReconciliation)
    <div class="fixed inset-0 bg-gray-600 dark:bg-gray-900 bg-opacity-50 dark:bg-opacity-75 overflow-y-auto h-full w-full z-50"
        x-data="{ show: true }" x-show="show" x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="ease-in duration-200" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0">
        <div class="relative min-h-screen flex items-center justify-center p-4">
            <div class="relative bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                <!-- Header -->
                <div class="border-b border-gray-200 dark:border-gray-700 px-6 py-4 flex justify-between items-center">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                        Detalle de Reconciliación #{{ $selectedReconciliation }}
                    </h3>
                    <button wire:click="closeDetail"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Contenido -->
                <div class="p-6">
                    @php
                    $details = $this->getReconciliationDetails($selectedReconciliation);
                    @endphp

                    <!-- Tabla de detalles -->
                    <div class="mb-6">
                        <h4 class="text-sm font-semibold text-gray-900 dark:text-white mb-4">Desglose por Método de Pago
                        </h4>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-900">
                                    <tr>
                                        <th
                                            class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Método de Pago</th>
                                        <th
                                            class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Conteo Usuario</th>
                                        <th
                                            class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Total Sistema</th>
                                        <th
                                            class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                            Diferencia</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @forelse($details as $detail)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                            {{ $detail->methodPayments->name ?? 'N/A' }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-white font-medium">
                                            {{ number_format($detail->value, 2, ',', '.') }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-right text-gray-900 dark:text-white font-medium">
                                            {{ number_format($detail->valueSystem, 2, ',', '.') }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-right font-medium">
                                            @php
                                            $difference = $detail->value - $detail->valueSystem;
                                            $differenceClass = $difference == 0 ? 'text-green-600 dark:text-green-400' : ($difference > 0 ? 'text-blue-600 dark:text-blue-400' : 'text-red-600 dark:text-red-400');
                                            @endphp
                                            <span class="{{ $differenceClass }}">
                                                {{ number_format($difference, 2, ',', '.') }}
                                            </span>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-4 text-center text-sm text-gray-500 dark:text-gray-400">
                                            No hay detalles disponibles
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Botones de acción -->
                    <div class="flex flex-col sm:flex-row sm:justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <button type="button" wire:click="closeDetail"
                            class="inline-flex items-center justify-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 font-medium text-sm transition-colors">
                            Cerrar
                        </button>
                        {{-- <button type="button" wire:click="markAsReconciled({{ $selectedReconciliation }})"
                            class="inline-flex items-center justify-center px-4 py-2 bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 border border-transparent rounded-lg font-medium text-sm text-white transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M5 13l4 4L19 7"></path>
                            </svg>
                            Marcar como Reconciliada
                        </button> --}}
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
