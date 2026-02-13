
<div class="min-h-screen bg-gray-50 dark:bg-gray-900 " x-data="quoterListOffline">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            
        </div>

        <!-- Banner de estado Offline -->
        <div x-show="!isOnline" 
             style="display: none;"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="-translate-y-full"
             x-transition:enter-end="translate-y-0"
             class="bg-red-600 text-white text-[10px] py-1 text-center font-bold sticky top-0 z-[60] flex items-center justify-center gap-2">
            <span>⚠️ MODO OFFLINE ACTIVADO</span>
        </div>

        <!-- Search Input and Add Button - Sticky -->
        <div class="sticky top-0 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shadow-sm z-50">
            <div class="px-4 py-4">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Cotizaciones</h1>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Gestión de registros</p>
                    </div>
                    <div class="flex gap-2 flex-1 md:max-w-md">
                        <div class="relative flex-1">
                            <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </span>
                            <input
                                type="text"
                                wire:model.live="search"
                                placeholder="Búsqueda rápida..."
                                class="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm transition-colors"
                            >
                        </div>
                        <button
                            @click="startNewQuote"
                            class="bg-[#2CBF64] hover:bg-green-600 text-white p-2 rounded-lg shadow-sm flex items-center justify-center transition-all duration-200 active:scale-95"
                            title="Nueva Cotización"
                        >
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Area -->
        <div class="px-4 py-6">
            <!-- Success Message -->
            @if (session()->has('message'))
                <div class="bg-green-100 dark:bg-green-800 border border-green-400 dark:border-green-600 text-green-700 dark:text-green-200 px-4 py-3 rounded-lg mb-6 shadow-sm">
                    {{ session('message') }}
                </div>
            @endif

            <!-- Quotes List -->
            <div class="space-y-6">
            <!-- Offline Quotes List (Alpine) -->
            <template x-if="offlineQuotes.length > 0">
                <div class="space-y-6 mb-8">
                    <div class="flex items-center gap-2 px-1">
                        <span class="w-2.5 h-2.5 rounded-full bg-orange-500 animate-pulse"></span>
                        <h3 class="text-xs font-black text-orange-600 uppercase tracking-widest">Pendientes de Sincronización</h3>
                    </div>

                    <template x-for="quote in offlineQuotes" :key="quote.uuid">
                        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-orange-200 dark:border-orange-800/50 overflow-hidden transform transition-all hover:scale-[1.01]">
                            <!-- Header Oscuro -->
                            <div class="bg-gray-900 dark:bg-black text-white px-4 py-3 flex justify-between items-center border-b border-orange-500/30">
                                <div>
                                    <span class="text-sm font-black uppercase tracking-wider text-orange-400">Cotización Offline</span>
                                    <p class="text-[10px] text-gray-500 font-mono" x-text="quote.uuid.substring(0,18)"></p>
                                </div>
                                <div class="text-right">
                                    <p class="text-xs font-bold" x-text="new Date(quote.date).toLocaleDateString('es-CO', {day:'2-digit', month:'2-digit', year:'numeric'})"></p>
                                    <p class="text-[10px] text-gray-400 font-medium" x-text="new Date(quote.date).toLocaleTimeString('es-CO', {hour:'2-digit', minute:'2-digit'})"></p>
                                </div>
                            </div>

                            <div class="p-5">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="space-y-4">
                                        <!-- Cliente -->
                                        <div>
                                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1">Cliente</p>
                                            <p class="text-sm font-bold text-gray-900 dark:text-white" x-text="quote.customer ? (quote.customer.businessName || quote.customer.firstName + ' ' + (quote.customer.lastName || '')) : 'Cliente no registrado'"></p>
                                        </div>
                                    </div>
                                    
                                    <div class="space-y-4">
                                        <!-- Total -->
                                        <div>
                                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1 text-right md:text-left">Total Estimado</p>
                                            <p class="text-lg font-black text-indigo-600 dark:text-indigo-400 text-right md:text-left" x-text="'$' + new Intl.NumberFormat('es-CO').format(quote.total)"></p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Acciones -->
                                <div class="mt-6 flex flex-wrap gap-2">
                                    <button
                                        @click="editOfflineQuote(quote)"
                                        class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2.5 rounded-lg text-sm font-bold flex items-center justify-center gap-2 transition-all shadow-md active:scale-95"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5 c4.478 0 8.268 2.943 9.542 7 -1.274 4.057-5.064 7-9.542 7 -4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        <span>Editar Offline</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </template>

            @forelse($quotes as $quote)
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-md border border-gray-200 dark:border-gray-700 overflow-hidden transform transition-all hover:shadow-lg">
                    <!-- Header Oscuro -->
                    <div class="bg-slate-900 dark:bg-slate-950 px-4 py-3 flex justify-between items-center">
                        <div class="flex items-center gap-3">
                            <span class="text-sm font-black uppercase tracking-widest text-white">Cotización #{{ $quote->consecutive }}</span>
                        </div>
                        <div class="text-right">
                            <p class="text-xs font-bold text-white">{{ $quote->created_at->format('d/m/Y') }}</p>
                            <p class="text-[10px] text-slate-300 font-medium">{{ $quote->created_at->format('H:i') }}</p>
                        </div>
                    </div>

                    <div class="p-5">
                        <!-- Información Principal -->
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                            <!-- Columna Cliente -->
                            <div class="lg:col-span-2">
                                <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Cliente</p>
                                <div class="space-y-1">
                                    <p class="text-sm font-black text-gray-900 dark:text-white uppercase leading-tight">
                                        {{ $quote->customer->company->businessName ?? $quote->customer_name }}
                                    </p>
                                    @php
                                        $mainContact = $quote->customer->contacts->where('status', 1)->first();
                                        $routeInfo = $quote->customer->company->routes->first();
                                    @endphp
                                    @if($mainContact)
                                        <p class="text-[11px] font-medium text-gray-600 dark:text-gray-400 flex items-center">
                                            <svg class="w-3 h-3 mr-1 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                            {{ $mainContact->firstName }} {{ $mainContact->lastName }}
                                        </p>
                                    @endif
                                </div>
                            </div>

                            <!-- Columna Estado y Sucursal -->
                            <div>
                                <div class="mb-4">
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Estado</p>
                                    <span class="px-2.5 py-1 text-[10px] font-black rounded-lg uppercase tracking-wider
                                        @if($quote->status === 'REGISTRADO') bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400
                                        @elseif($quote->status === 'ANULADO') bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400
                                        @elseif($quote->status === 'FACTURADO') bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400
                                        @else bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400 @endif">
                                        {{ $quote->status }}
                                    </span>
                                </div>
                                @if($quote->warehouse)
                                    <div>
                                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Sucursal</p>
                                        <p class="text-xs font-bold text-gray-700 dark:text-gray-300 flex items-center">
                                            <svg class="w-3 h-3 mr-1.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                            {{ $quote->warehouse->name }}
                                        </p>
                                    </div>
                                @endif
                            </div>

                            <!-- Columna Vendedor y Ruta -->
                            <div>
                                <div class="mb-4">
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Vendedor</p>
                                    <p class="text-xs font-bold text-gray-700 dark:text-gray-300 flex items-center">
                                        <svg class="w-3 h-3 mr-1.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                        {{ $quote->user->name ?? 'N/A' }}
                                    </p>
                                </div>
                                @if($routeInfo && $routeInfo->route)
                                    <div>
                                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest mb-1.5">Ruta de Entrega</p>
                                        <p class="text-xs font-black text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/20 px-2 py-0.5 rounded inline-block">
                                            {{ $routeInfo->route->name }} ({{ $routeInfo->route->delivery_day }})
                                        </p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Acciones -->
                        <div class="mt-8 flex flex-wrap gap-2">
                            <!-- Botón Detalles -->
                            <button
                                wire:click="verDetalles({{ $quote->id }})"
                                class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2.5 rounded-lg text-sm font-black flex items-center justify-center gap-2 transition-all shadow-md active:scale-95"
                            >
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5 c4.478 0 8.268 2.943 9.542 7 -1.274 4.057-5.064 7-9.542 7 -4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <span>Ver Detalle</span>
                            </button>

                            <!-- Botón Editar -->
                            @if($quote->status != 'REMISIÓN')
                            <button
                                wire:click="irAlCarrito({{ $quote->id }})"
                                class="bg-yellow-500 hover:bg-yellow-600 text-white p-2.5 rounded-lg transition-all shadow-md active:scale-90"
                                title="Editar"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                </svg>
                            </button>
                            @endif

                            <!-- Botón Imprimir -->
                            <button
                                wire:click="printQuote({{ $quote->id }})"
                                class="bg-blue-500 hover:bg-blue-600 text-white p-2.5 rounded-lg transition-all shadow-md active:scale-90"
                                title="Imprimir"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                                </svg>
                            </button>

                            <!-- Botón Eliminar -->
                            <button
                                wire:click="eliminar({{ $quote->id }})"
                                onclick="return confirm('¿Está seguro de eliminar esta cotización?')"
                                class="bg-rose-500 hover:bg-rose-600 text-white p-2.5 rounded-lg transition-all shadow-md active:scale-90"
                                title="Eliminar"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-12">
                    <div class="text-gray-500 dark:text-gray-400">
                        <svg class="mx-auto h-16 w-16 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                        </svg>
                        <h3 class="text-lg font-medium text-gray-700 dark:text-gray-300 mb-2">
                            @if($search)
                                Sin resultados
                            @else
                                No hay cotizaciones
                            @endif
                        </h3>
                        <p class="mb-6 text-sm">
                            @if($search)
                                No se encontraron cotizaciones que coincidan con "{{ $search }}".
                            @else
                                Comienza creando tu primera cotización.
                            @endif
                        </p>
                        @if(!$search)
                            <button
                    @click="startNewQuote"
                    :disabled="loadingNewQuote"
                    :class="loadingNewQuote ? 'opacity-75 cursor-wait' : ''"
                    class="bg-[#2CBF64] hover:bg-green-600 text-white rounded-xl shadow-lg flex items-center justify-center transition-all duration-200 active:scale-95 w-12 h-12"
                    title="Nueva Cotización"
                >
                    <!-- Spinner de loading -->
                    <svg x-show="loadingNewQuote" class="animate-spin h-6 w-6 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>

                    <!-- Ícono normal -->
                    <svg x-show="!loadingNewQuote" class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                </button>
                    @endif
                </div>
                </div>
            @endforelse
        </div>

        </div>

        <!-- Pagination -->
        @if($quotes->hasPages())
            <div class="px-4 py-8">
                {{ $quotes->links() }}
            </div>
        @endif

        <!-- Modal de Detalles (Móvil) -->
        @if($showDetailsModal && $selectedQuote)
        <div class="fixed inset-0 z-[100] overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity" aria-hidden="true" wire:click="cerrarDetalles"></div>

                <!-- Modal panel -->
                <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom bg-white dark:bg-slate-900 rounded-t-2xl sm:rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full w-full">
                    
                    <!-- Modal Header -->
                    <div class="bg-slate-800 text-white px-6 py-4 flex justify-between items-center border-b border-slate-700">
                        <div>
                            <h3 class="text-lg font-black uppercase tracking-widest">Cotización #{{ $selectedQuote->consecutive }}</h3>
                            <p class="text-xs text-slate-300">{{ $selectedQuote->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                        <button type="button" wire:click="cerrarDetalles" class="bg-slate-700 hover:bg-slate-600 p-2 rounded-full transition-colors">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="px-4 py-6 sm:p-8 overflow-y-auto max-h-[75vh]">
                        <!-- Información del Cliente -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                            <div class="bg-gray-50 dark:bg-slate-800/50 p-5 rounded-xl border border-gray-100 dark:border-slate-800">
                                <h4 class="text-[10px] font-black text-indigo-500 uppercase tracking-[0.2em] mb-4">Información del Cliente</h4>
                                @if($selectedQuote->customer)
                                    @php
                                        $mainContact = $selectedQuote->customer->contacts->where('status', 1)->first();
                                        $routeInfo = $selectedQuote->customer->company->routes->first();
                                    @endphp
                                    <div class="space-y-4">
                                        <div>
                                            <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Empresa / Negocio</p>
                                            <p class="text-base font-black text-gray-900 dark:text-white">{{ $selectedQuote->customer->company->businessName ?? $selectedQuote->customer_name }}</p>
                                        </div>
                                        <div class="grid grid-cols-2 gap-4">
                                            <div>
                                                <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Contacto</p>
                                                <p class="text-sm font-bold text-gray-800 dark:text-slate-200">{{ $mainContact->firstName ?? 'N/A' }} {{ $mainContact->lastName ?? '' }}</p>
                                            </div>
                                            <div>
                                                <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Teléfono</p>
                                                <p class="text-sm font-bold text-gray-800 dark:text-slate-200">{{ $mainContact->personal_phone ?? 'N/A' }}</p>
                                            </div>
                                        </div>
                                        <div>
                                            <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Dirección</p>
                                            <p class="text-sm font-bold text-gray-800 dark:text-slate-200">{{ $selectedQuote->customer->address }}</p>
                                            <p class="text-xs text-gray-500">{{ $selectedQuote->customer->city->name ?? '' }}</p>
                                        </div>
                                    </div>
                                @else
                                    <p class="text-sm text-gray-500 italic">Cliente no registrado</p>
                                @endif
                            </div>

                            <div class="bg-gray-50 dark:bg-slate-800/50 p-5 rounded-xl border border-gray-100 dark:border-slate-800">
                                <h4 class="text-[10px] font-black text-indigo-500 uppercase tracking-[0.2em] mb-4">Venta y Entrega</h4>
                                <div class="space-y-4">
                                    <div>
                                        <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Vendedor</p>
                                        <p class="text-sm font-bold text-gray-800 dark:text-slate-200">{{ $selectedQuote->user->name ?? 'N/A' }}</p>
                                    </div>
                                    @if($routeInfo && $routeInfo->route)
                                    <div>
                                        <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Ruta Asignada</p>
                                        <p class="text-sm font-black text-indigo-600 dark:text-indigo-400">{{ $routeInfo->route->name }} ({{ $routeInfo->route->delivery_day }})</p>
                                    </div>
                                    @endif
                                    @if($selectedQuote->observations)
                                    <div>
                                        <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Observaciones</p>
                                        <p class="text-sm italic text-gray-700 dark:text-slate-300">"{{ $selectedQuote->observations }}"</p>
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Detalles de Productos -->
                        <div class="space-y-3">
                            <h4 class="text-[10px] font-black text-indigo-500 uppercase tracking-[0.2em] mb-4">Productos Cotizados</h4>
                            @foreach($selectedQuote->detalles as $detalle)
                            <div class="flex justify-between items-center p-3 bg-white dark:bg-slate-800 rounded-lg border border-gray-100 dark:border-slate-800 shadow-sm">
                                <div class="flex-1">
                                    <p class="text-xs font-black text-gray-900 dark:text-white uppercase">{{ $detalle->item->name ?? 'N/A' }}</p>
                                    <p class="text-[10px] text-gray-500 font-bold">{{ number_format($detalle->quantity, 0) }} UNID x ${{ number_format($detalle->value, 0) }}</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-black text-gray-900 dark:text-white">${{ number_format($detalle->quantity * $detalle->value, 0) }}</p>
                                </div>
                            </div>
                            @endforeach
                        </div>

                        <!-- Total -->
                        <div class="mt-8 pt-6 border-t border-gray-100 dark:border-slate-800">
                            <div class="flex justify-between items-center px-1">
                                <span class="text-xs font-black text-gray-500 uppercase">Total Cotización</span>
                                <span class="text-2xl font-black text-indigo-600 dark:text-indigo-400">${{ number_format($selectedQuote->total, 0) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div class="bg-gray-50 dark:bg-slate-800/80 px-4 py-4 border-t border-gray-100 dark:border-slate-800">
                        <button type="button" wire:click="cerrarDetalles" class="w-full bg-slate-800 hover:bg-slate-900 text-white font-black py-3 rounded-xl transition-all active:scale-95 uppercase tracking-widest text-xs">
                            Cerrar Detalles
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

@script
<script>
    Alpine.data('quoterListOffline', () => ({
        offlineQuotes: [],
        isOnline: navigator.onLine,
        filteredQuotes: [], // Para búsqueda local
        loadingNewQuote: false, // Estado de carga para nueva cotización

        async init() {
            // Escuchar cambios de conexión
            window.addEventListener('online', async () => {
                this.isOnline = true;
                console.log('🌐 Conexión recuperada en Lista');
                await this.syncPendingOrders();
                await this.loadOfflineQuotes();
            });
            
            window.addEventListener('offline', () => {
                this.isOnline = false;
                this.loadOfflineQuotes();
            });
            
            // Carga inicial
            await this.loadOfflineQuotes();

            // Si ya estamos online al cargar, intentar sincronizar
            if(this.isOnline) {
                await this.syncPendingOrders();
            }
        },

        async getDb() {
            let attempts = 0;
            while (!window.db && attempts < 20) { 
                await new Promise(resolve => setTimeout(resolve, 100));
                attempts++;
            }
            return window.db;
        },

        async loadOfflineQuotes() {
            const db = await this.getDb();
            if (!db) return;

            try {
                // Cargar pedidos no sincronizados
                this.offlineQuotes = await db.pedidos
                    .where('sincronizado').equals(0)
                    .reverse()
                    .toArray();
                
                console.log('📱 Cotizaciones offline cargadas:', this.offlineQuotes.length);
            } catch (e) {
                console.error('❌ Error cargando cotizaciones offline:', e);
            }
        },

        async clearLocalState() {
            const db = await this.getDb();
            if (db) {
                await db.estado_quoter.delete('actual');
                console.log('🧹 Estado local limpiado para nueva cotización');
            }
        },

        async startNewQuote() {
            if (this.loadingNewQuote) return;
            this.loadingNewQuote = true;
            
            // 1. Limpiar estado local
            await this.clearLocalState();
            
            // 2. Redirigir a ruta móvil directamente (Offline Safe)
            // ?clear=1 fuerza al servidor a limpiar la sesión si hay internet
            window.location.href = "{{ route('tenant.quoter.products.mobile') }}?clear=1";
        },

        async editOfflineQuote(quote) {
            const db = await this.getDb();
            if (!db) return;

            try {
                // 1. Preparar el estado "actual" con los datos de esta cotización
                await db.estado_quoter.put({
                    id: 'actual',
                    cart: JSON.parse(JSON.stringify(quote.items)),
                    customer: quote.customer ? JSON.parse(JSON.stringify(quote.customer)) : null,
                    uuid: quote.uuid, // IMPORTANTE: Pasar el UUID para que sea un UPDATE
                    timestamp: new Date().toISOString()
                });

                // 2. Redirigir al editor (mobile-product-quoter)
                // Usamos la ruta directa móvil para evitar redirecciones de servidor que fallan offline
                window.location.href = "{{ route('tenant.quoter.products.mobile') }}"; 

            } catch (e) {
                console.error('❌ Error al preparar edición offline:', e);
                alert('Error al abrir la cotización: ' + e.message);
            }
        },

        async syncPendingOrders() {
            if (!this.isOnline) return;
            
            const db = await this.getDb();
            if (!db) return;

            const pending = await db.pedidos.where('sincronizado').equals(0).toArray();
            if (pending.length === 0) return;

            console.log(`🔄 [Lista] Sincronizando ${pending.length} pedidos pendientes...`);

            // Mostrar toast de inicio de sincronización
            const validPedidos = pending.filter(p => p.items && p.items.length > 0);
            
            if (validPedidos.length > 0) {
                    Swal.fire({
                    title: 'Sincronizando...',
                    text: 'Subiendo pedidos offline al servidor',
                    toast: true,
                    position: 'top-end',
                    timer: 3000,
                    showConfirmButton: false,
                    didOpen: () => { Swal.showLoading(); }
                });
            }

            for (const order of validPedidos) {
                try {
                    // Llamar al endpoint de Livewire para procesar
                    const response = await $wire.processOfflineOrder(order);
                    
                    if (response && response.success) {
                        // Marcar como sincronizado en local
                        await db.pedidos.update(order.id || order.uuid, { sincronizado: 1 });
                        console.log('✅ [Lista] Pedido sincronizado:', order.uuid);
                    }
                } catch (e) {
                    console.error('❌ [Lista] Error sincronizando pedido:', e);
                }
            }
            
            // Recargar lista local (deberían desaparecer de la sección naranja)
            await this.loadOfflineQuotes();
            
            // Recargar lista del servidor (Livewire) para que aparezcan en blanco
            $wire.dispatch('refresh-component'); 
            $wire.$refresh();
            
            Swal.fire({
                icon: 'success',
                title: '¡Sincronización Completada!',
                toast: true,
                position: 'top-end',
                timer: 2000,
                showConfirmButton: false
            });
        }
    }));
</script>
@endscript


