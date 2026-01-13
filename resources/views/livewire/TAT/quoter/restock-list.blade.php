<!-- Wrapper con padding y background -->
<div class="p-3 sm:p-6 bg-gray-50 dark:bg-slate-900 min-h-screen transition-colors">
    <!-- Header Card -->
    <div class="bg-white dark:bg-slate-800 rounded-lg p-4 sm:p-6 mb-6 border border-gray-200 dark:border-slate-700 transition-colors">
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center space-y-4 sm:space-y-0">
            <div class="flex-1">
                <h1 class="text-lg sm:text-xl font-semibold text-gray-900 dark:text-white">Solicitudes de Reabastecimiento</h1>
                <p class="text-gray-600 dark:text-slate-400 text-sm mt-1">Gestión de pedidos realizados a la distribuidora</p>
            </div>
            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 sm:gap-3">
                <a href="{{ route('tenant.tat.receive.orders') }}"
                   class="bg-green-500 hover:bg-green-600 text-white px-3 sm:px-4 py-2 rounded text-sm font-medium flex items-center justify-center transition-colors">
                    <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                    </svg>
                    <span class="truncate">Recibir Pedidos</span>
                </a>
                <button wire:click="createNewRestock"
                        class="bg-indigo-500 hover:bg-indigo-600 text-white px-3 sm:px-4 py-2 rounded text-sm font-medium flex items-center justify-center transition-colors">
                    <svg class="w-4 h-4 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    <span class="truncate">Nueva Solicitud</span>
                </button>
            </div>
        </div>
    </div>

    <!-- Toolbar Card -->
    <div class="bg-white dark:bg-slate-800 rounded-lg p-4 mb-6 border border-gray-200 dark:border-slate-700 transition-colors">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between space-y-4 lg:space-y-0">
            <!-- Search Section -->
            <div class="flex-1 max-w-md">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-4 w-4 text-gray-400 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <input
                        type="text"
                        wire:model.live.debounce.300ms="search"
                        placeholder="Buscar por número de orden..."
                        class="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-slate-600 rounded bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200 placeholder-gray-500 dark:placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 dark:focus:ring-slate-600 text-sm transition-colors"
                    >
                </div>
            </div>

            <!-- Actions Section -->
            <div class="flex items-center space-x-3">
                <!-- Registros por página -->
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

    <!-- Data Table Card -->
    <div class="bg-white dark:bg-slate-800 rounded-lg overflow-hidden border border-gray-200 dark:border-slate-700 transition-colors">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-slate-700">
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">
                            <div class="flex items-center space-x-1">
                                <span>ORDEN #</span>
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider">
                            <div class="flex items-center space-x-1">
                                <span>FECHA</span>
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">
                            <div class="flex items-center space-x-1">
                                <span>ITEMS</span>
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">
                            <div class="flex items-center space-x-1">
                                <span>SOLICITUD</span>
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase tracking-wider">
                            <div class="flex items-center space-x-1">
                                <span>PEDIDO</span>
                            </div>
                        </th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-slate-400 uppercase tracking-wider">
                            ACCIONES
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($restockOrders as $order)
                        <tr class="border-b border-gray-200 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                <div class="flex flex-col gap-1">
                                    <span class="font-bold">#{{ $order->order_number }}</span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200 w-fit">
                                        Remisión: {{ $order->remise_number ?: 'N/A' }}
                                    </span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 w-fit">
                                        Cotización: {{ $order->quote_consecutive ?: 'N/A' }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-slate-300">
                                {{ \Carbon\Carbon::parse($order->created_at)->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-slate-300">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200">
                                    {{ $order->total_items }} productos
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-slate-300">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full
                                    {{ $order->status === 'Registrado' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 
                                       ($order->status === 'Recibido' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' :
                                       'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200') }}">
                                    {{ $order->status }}
                                </span>
                            </td>
                             <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-slate-300">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200">
                                    {{ $order->rem_status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                @if($order->order_number)
                                    <!-- Registro confirmado -->
                                    @if($order->status === 'Confirmado')
                                         @if($order->rem_status === 'EN RECORRIDO')
                                         <a href="{{ route('tenant.tat.receive.orders', ['order_number' => $order->order_number]) }}"
                                           class="text-green-600 hover:text-green-900 dark:text-green-400 dark:hover:text-green-300 inline-flex items-center transition-colors text-sm font-medium">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                            </svg>
                                            Ver detalle
                                        </a>
                                        @else
                                         <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-sm font-medium text-yellow-800 dark:text-yellow-300">
                                          <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 mr-1" viewBox="0 0 24 24" fill="none"
                                                stroke="currentColor"  stroke-linecap="round" stroke-linejoin="round" stroke-width="2" aria-hidden="true">
                                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2z" /> <path d="M7 17L17 7" />
                                          </svg>
                                           No habilitado
                                          </span>
                                        @endif
                                    @elseif($order->status === 'Recibido')
                                        <a href="{{ route('tenant.tat.receive.orders', ['order_number' => $order->order_number]) }}" 
                                           class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 inline-flex items-center text-sm font-medium transition-colors">
                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            Ver Completado
                                        </a>
                                    @else
                                        <!-- Fallback para editar si es necesario, pero órdenes confirmadas/recibidas usualmente no se editan igual -->
                                         <button wire:click="editConfirmedRestock({{ $order->order_number }})"
                                            class="text-yellow-600 hover:text-yellow-900 dark:text-yellow-400 dark:hover:text-yellow-300 inline-flex items-center transition-colors"
                                            title="Editar Solicitud">
                                            <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                            </svg>
                                            Ver Detalle
                                        </button>
                                    @endif
                                @else
                                    <!-- Registro preliminar: se puede editar también -->
                                    <button wire:click="editPreliminaryRestock"
                                        class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 inline-flex items-center transition-colors"
                                        title="Editar Lista Preliminar">
                                        <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                        Editar Lista
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center">
                                <div class="text-gray-500 dark:text-slate-400">
                                    <svg class="mx-auto h-12 w-12 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                    </svg>
                                    <h3 class="text-lg font-medium text-gray-700 dark:text-slate-300 mb-2">No tienes solicitudes</h3>
                                    <p class="mb-6">
                                        @if($search)
                                            No se encontraron solicitudes que coincidan con "{{ $search }}".
                                        @else
                                            Comienza creando tu primera solicitud de reabastecimiento.
                                        @endif
                                    </p>
                                    @if(!$search)
                                        <a href="{{ route('tenant.quoter.products.desktop') }}" 
                                           class="bg-indigo-500 hover:bg-indigo-600 text-white px-4 py-2 rounded font-medium transition-colors inline-block">
                                            Crear Primera Solicitud
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination Footer -->
        <div class="px-6 py-4 border-t border-gray-200 dark:border-slate-700">
             {{ $restockOrders->links() }}
        </div>
    </div>
</div>
