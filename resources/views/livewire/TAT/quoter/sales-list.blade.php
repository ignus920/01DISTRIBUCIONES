<div class="min-h-screen bg-gray-50 dark:bg-slate-900 transition-colors duration-200">
    <div class="px-4 sm:px-6 lg:px-8 py-6">
        <!-- Header -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 p-6 mb-6">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-semibold text-gray-900 dark:text-white">Ventas</h1>
                    <p class="text-sm text-gray-600 dark:text-slate-400 mt-1">Gestión de registros</p>
                </div>
                <a href="{{ route('tenant.tat.quoter.index') }}"
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
                    <div class="flex items-center gap-2 text-sm text-gray-600 dark:text-slate-400">
                        <span>Mostrar:</span>
                        <select wire:model.live="perPage" class="px-3 py-1.5 bg-gray-50 dark:bg-slate-700 border border-gray-300 dark:border-slate-600 rounded-lg text-sm text-gray-900 dark:text-slate-200 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all">
                            <option value="10">10</option>
                            <option value="25">25</option>
                            <option value="50">50</option>
                        </select>
                    </div>
                    
                    <div class="flex gap-2">
                        <button class="p-2 bg-gray-50 dark:bg-slate-700 border border-gray-300 dark:border-slate-600 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-600 transition-colors">
                            <svg class="w-4 h-4 text-gray-600 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                            </svg>
                        </button>
                        <button class="p-2 bg-gray-50 dark:bg-slate-700 border border-gray-300 dark:border-slate-600 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-600 transition-colors">
                            <svg class="w-4 h-4 text-gray-600 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </button>
                        <button class="p-2 bg-gray-50 dark:bg-slate-700 border border-gray-300 dark:border-slate-600 rounded-lg hover:bg-gray-100 dark:hover:bg-slate-600 transition-colors">
                            <svg class="w-4 h-4 text-gray-600 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla -->
        <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                    <thead class="bg-gray-50 dark:bg-slate-800/50">
                        <tr>
                            <th class="px-6 py-3.5 text-left text-xs font-medium text-gray-600 dark:text-slate-400 uppercase tracking-wider">COTIZACIÓN #</th>
                            <th class="px-6 py-3.5 text-left text-xs font-medium text-gray-600 dark:text-slate-400 uppercase tracking-wider">CLIENTE</th>
                            <th class="px-6 py-3.5 text-left text-xs font-medium text-gray-600 dark:text-slate-400 uppercase tracking-wider">TIPO</th>
                            <th class="px-6 py-3.5 text-left text-xs font-medium text-gray-600 dark:text-slate-400 uppercase tracking-wider">ESTADO</th>
                            <th class="px-6 py-3.5 text-left text-xs font-medium text-gray-600 dark:text-slate-400 uppercase tracking-wider">SUCURSAL</th>
                            <th class="px-6 py-3.5 text-left text-xs font-medium text-gray-600 dark:text-slate-400 uppercase tracking-wider">TELÉFONO</th>
                            <th class="px-6 py-3.5 text-left text-xs font-medium text-gray-600 dark:text-slate-400 uppercase tracking-wider">FECHA</th>
                            <th class="px-6 py-3.5 text-left text-xs font-medium text-gray-600 dark:text-slate-400 uppercase tracking-wider">ACCIONES</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700/50">
                        @forelse($quotes as $quote)
                            <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/30 transition-colors duration-150">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm font-semibold text-gray-900 dark:text-white">#{{ $quote->consecutive }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    @if($quote->customer)
                                        <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $quote->customer->full_name }}</div>
                                        <div class="text-xs text-gray-600 dark:text-slate-400 mt-0.5">{{ $quote->customer->identification }}</div>
                                        <div class="text-xs text-gray-500 dark:text-slate-500 mt-0.5">{{ $quote->customer->billingEmail ?? 'Sin email' }}</div>
                                    @else
                                        <div class="text-sm text-gray-500 dark:text-slate-400">Sin cliente</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium bg-blue-500/10 dark:bg-blue-500/20 text-blue-700 dark:text-blue-400 border border-blue-500/20 dark:border-blue-500/30">
                                        POS
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-medium border
                                        {{ $quote->status === 'Registrado' 
                                            ? 'bg-green-500/10 dark:bg-green-500/20 text-green-700 dark:text-green-400 border-green-500/20 dark:border-green-500/30' 
                                            : 'bg-red-500/10 dark:bg-red-500/20 text-red-700 dark:text-red-400 border-red-500/20 dark:border-red-500/30' }}">
                                        {{ strtoupper($quote->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">Soacha</div>
                                    <div class="text-xs text-gray-500 dark:text-slate-500 mt-0.5">calle 25a+5a-20</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm text-gray-900 dark:text-slate-200">{{ $quote->customer->business_phone ?? '3208614517' }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="text-sm text-gray-900 dark:text-slate-200">{{ $quote->created_at->format('d/m/Y H:i') }}</span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <button class="inline-flex items-center justify-center w-8 h-8 text-gray-500 dark:text-slate-400 hover:text-gray-900 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-slate-700 rounded-lg transition-all duration-150">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"/>
                                        </svg>
                                    </button>
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
</div>