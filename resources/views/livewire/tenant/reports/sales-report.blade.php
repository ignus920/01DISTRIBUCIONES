<div class="min-h-screen bg-gray-50 dark:bg-gray-900 p-6">
    <div class="max-w-full mx-auto">
        <!-- Header -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6 mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Reportes - Resumen de Ventas por Vendedor</h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">Consulta consolidada de ventas por vendedor</p>
                </div>
            </div>
        </div>

        <!-- Mensajes -->
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

        <!-- Filtros -->
        @livewire('tenant.reports.filter-reports-generic', ['fields' => $filterFields], key('sales-report-filters'))

        <!-- DataTable Card -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
            <!-- Toolbar -->
            <div class="p-6 border-b border-gray-200 dark:border-gray-700">
                <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                    <!-- Info -->
                    <div class="flex-1">
                        @if($hasSearched)
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                @if($startDate && $endDate)
                                    Mostrando resultados del {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}
                                @elseif($startDate)
                                    Mostrando resultados desde {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }}
                                @elseif($endDate)
                                    Mostrando resultados hasta {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}
                                @else
                                    Mostrando todos los resultados
                                @endif
                            </p>
                        @else
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                Selecciona fechas y haz clic en "Buscar" para ver los resultados
                            </p>
                        @endif
                    </div>

                    <!-- Controles -->
                    <div class="flex items-center gap-3">
                        <!-- Registros por página -->
                        <div class="flex items-center gap-2">
                            <label class="text-sm text-gray-700 dark:text-gray-300">Mostrar:</label>
                            <select 
                                wire:model.live="perPage"
                                class="border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <option value="5">5</option>
                                <option value="10">10</option>
                                <option value="25">25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                        </div>
                        <x-export-buttons />
                    </div>
                </div>
            </div>

            <!-- Tabla -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-900">
                        <tr>
                            <th wire:click="sortBy('uv.id')"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 select-none">
                                <div class="flex items-center gap-1">
                                    ID Vendedor
                                    @if($sortField === 'uv.id')
                                        @if($sortDirection === 'asc')
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"></path>
                                            </svg>
                                        @else
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z"></path>
                                            </svg>
                                        @endif
                                    @endif
                                </div>
                            </th>
                            <th wire:click="sortBy('uv.name')"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 select-none">
                                <div class="flex items-center gap-1">
                                    Nombre Vendedor
                                    @if($sortField === 'uv.name')
                                        @if($sortDirection === 'asc')
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"></path>
                                            </svg>
                                        @else
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z"></path>
                                            </svg>
                                        @endif
                                    @endif
                                </div>
                            </th>
                            <th wire:click="sortBy('total')"
                                class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider cursor-pointer hover:bg-gray-100 dark:hover:bg-gray-800 select-none">
                                <div class="flex items-center justify-end gap-1">
                                    Total Ventas
                                    @if($sortField === 'total')
                                        @if($sortDirection === 'asc')
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"></path>
                                            </svg>
                                        @else
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M14.707 12.707a1 1 0 01-1.414 0L10 9.414l-3.293 3.293a1 1 0 01-1.414-1.414l4-4a1 1 0 011.414 0l4 4a1 1 0 010 1.414z"></path>
                                            </svg>
                                        @endif
                                    @endif
                                </div>
                            </th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Acciones
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                        @if(!$hasSearched)
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                    <div class="flex flex-col items-center">
                                        <svg class="w-16 h-16 mb-4 text-gray-400 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                        </svg>
                                        <p class="text-lg font-medium">Selecciona un rango de fechas para buscar</p>
                                        <p class="text-sm mt-2">Usa los filtros de arriba para generar el reporte</p>
                                    </div>
                                </td>
                            </tr>
                        @else
                            @forelse ($salesSummary as $record)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                        {{ $record->vendedor_id }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                        {{ $record->vendedor }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-bold text-gray-900 dark:text-white">
                                        <span class="{{ $record->total >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                                            ${{ number_format($record->total, 2) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                        <button 
                                            wire:click="generatePDF({{ $record->vendedor_id }})"
                                            class="inline-flex items-center px-3 py-1 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300 text-xs font-medium rounded-full hover:bg-red-200 dark:hover:bg-red-900/50 transition-colors">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                            Descargar PDF
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                                        <div class="flex flex-col items-center">
                                            <svg class="w-12 h-12 mb-4 text-gray-400 dark:text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                            </svg>
                                            <p class="text-lg font-medium">No se encontraron registros</p>
                                            <p class="text-sm">Intenta ajustar los filtros de búsqueda</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        @endif
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                {{ $salesSummary->links() }}
            </div>
        </div>
    </div>
</div>
