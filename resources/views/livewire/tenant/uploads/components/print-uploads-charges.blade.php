<div class="p-6">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <p class="text-sm text-gray-600 dark:text-gray-400">Lista de entregas</p>
        </div>
        
        <!-- Selector de items por página -->
        <div class="flex items-center gap-2">
            <label for="perPage" class="text-sm text-gray-700 dark:text-gray-300">Mostrar:</label>
            <select wire:model.live="perPage" 
                    id="perPage"
                    class="px-3 py-1 text-sm border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400">
                <option value="5">5</option>
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="100">100</option>
            </select>
            <span class="text-sm text-gray-700 dark:text-gray-300">por página</span>
        </div>
    </div>

    @if($deliveries->count() > 0)
        <!-- Desktop View -->
        <div class="hidden md:block overflow-x-auto shadow-md rounded-lg">
            <table class="min-w-full bg-white dark:bg-gray-800">
                <thead class="bg-gray-100 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            Cargue
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            Transportador
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            Opciones
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 dark:text-gray-300 uppercase tracking-wider">
                            Fecha
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($deliveries as $delivery)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-gray-100">
                               #{{ $delivery->id }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                {{ $delivery->transportador_name ?? 'Sin asignar' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                              <div class="flex gap-2">
                                    <button wire:click="printDetail({{ $delivery->id }})"
                                       class="inline-flex items-center px-3 py-1 bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 text-white text-xs font-medium rounded transition">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                        </svg>
                                        Cargue Ventas
                                    </button>
                                    <button wire:click="printOrders({{ $delivery->id }})"
                                       class="inline-flex items-center px-3 py-1 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white text-xs font-medium rounded transition">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                        </svg>
                                        Pedidos Cargues
                                    </button>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                {{ \Carbon\Carbon::parse($delivery->sale_date)->format('d/m/Y') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Mobile View -->
        <div class="md:hidden space-y-4">
            @foreach($deliveries as $delivery)
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 border border-gray-200 dark:border-gray-700">
                    <!-- Delivery ID -->
                    <div class="mb-4 pb-4 border-b border-gray-200 dark:border-gray-700">
                        <p class="text-xs text-gray-600 dark:text-gray-400 uppercase tracking-wider font-medium">Cargue</p>
                        <p class="text-lg font-semibold text-gray-900 dark:text-gray-100">#{{ $delivery->id }}</p>
                    </div>

                    <!-- Transportador -->
                    <div class="mb-4 pb-4 border-b border-gray-200 dark:border-gray-700">
                        <p class="text-xs text-gray-600 dark:text-gray-400 uppercase tracking-wider font-medium">Transportador</p>
                        <p class="text-sm text-gray-900 dark:text-gray-100">{{ $delivery->transportador_name ?? 'Sin asignar' }}</p>
                    </div>

                    <!-- Action Buttons -->
                    <div class="mb-4 pb-4 border-b border-gray-200 dark:border-gray-700">
                        <p class="text-xs text-gray-600 dark:text-gray-400 uppercase tracking-wider font-medium mb-3">Opciones</p>
                        <div class="flex flex-col gap-2">
                            <button wire:click="printDetail({{ $delivery->id }})"
                               class="w-full inline-flex items-center justify-center px-3 py-2 bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 text-white text-sm font-medium rounded transition">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                </svg>
                                Cargue Ventas 
                            </button>
                            <button wire:click="printOrders({{ $delivery->id }})"
                               class="w-full inline-flex items-center justify-center px-3 py-2 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white text-sm font-medium rounded transition">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                </svg>
                                Pedidos Cargues
                            </button>
                        </div>
                    </div>

                    <!-- Date -->
                    <div>
                        <p class="text-xs text-gray-600 dark:text-gray-400 uppercase tracking-wider font-medium">Fecha</p>
                        <p class="text-sm text-gray-900 dark:text-gray-100">{{ \Carbon\Carbon::parse($delivery->sale_date)->format('d/m/Y') }}</p>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Paginación -->
        <div class="mt-4">
            <x-responsive-pagination :paginator="$deliveries" />
        </div>
    @else
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border-l-4 border-yellow-400 dark:border-yellow-500 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <svg class="h-5 w-5 text-yellow-400 dark:text-yellow-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-yellow-700 dark:text-yellow-400">
                        No se encontraron cargues confirmados.
                    </p>
                </div>
            </div>
        </div>
    @endif
</div>
