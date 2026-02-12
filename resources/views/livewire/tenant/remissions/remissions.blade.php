<div class="p-6 bg-gray-50 dark:bg-slate-900 min-h-screen transition-colors">
    <!-- Encabezado -->
    <div class="bg-white dark:bg-slate-800 rounded-lg p-6 mb-6 border border-gray-200 dark:border-slate-700 transition-colors">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-xl font-semibold text-gray-900 dark:text-white">Remisiones</h1>
                <p class="text-gray-600 dark:text-slate-400 text-sm mt-1">Gestión de registros</p>
            </div>
            <!-- <div class="flex items-center space-x-3">
                <button class="bg-indigo-500 hover:bg-indigo-600 text-white px-4 py-2 rounded text-sm font-medium flex items-center transition-all duration-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    <span>Nueva Remisión</span>
                </button>
            </div> -->
        </div>
    </div>

    <!-- Barra de Herramientas -->
    <div class="bg-white dark:bg-slate-800 rounded-lg p-4 mb-6 border border-gray-200 dark:border-slate-700 transition-colors">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between space-y-4 lg:space-y-0">
            <!-- Buscador y Filtros -->
            <div class="flex-1 max-w-2xl flex items-center space-x-3">
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-4 w-4 text-gray-400 dark:text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                    <input type="text" wire:model.live="search" placeholder="Búsqueda rápida..."
                        class="block w-full pl-10 pr-3 py-2 border border-gray-300 dark:border-slate-600 rounded bg-white dark:bg-slate-700 text-gray-900 dark:text-slate-200 placeholder-gray-500 dark:placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm transition-colors">
                </div>
                <button wire:click="$toggle('showAdvancedSearch')" 
                    class="flex items-center px-4 py-2 text-sm font-medium border border-gray-300 dark:border-slate-600 rounded bg-white dark:bg-slate-700 text-gray-700 dark:text-slate-300 hover:bg-gray-50 dark:hover:bg-slate-600 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                    Búsqueda Avanzada
                </button>
            </div>

            <!-- Acciones y Paginación -->
            <div class="flex items-center space-x-3">
                @if(count($selectedRemissions) > 0)
                    <button wire:click="facturarMasivo" 
                        class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded text-sm font-medium flex items-center transition-all animate-pulse">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Facturar ({{ count($selectedRemissions) }})
                    </button>
                @endif

                <div class="flex items-center gap-2">
                    <label class="text-sm text-gray-700 dark:text-gray-300">Mostrar:</label>
                    <select wire:model.live="perPage"
                        class="border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Panel de Búsqueda Avanzada -->
        <div x-show="$wire.showAdvancedSearch" x-transition 
            class="mt-4 pt-4 border-t border-gray-100 dark:border-slate-700 grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-slate-400 mb-1">NIT / Cédula</label>
                <input type="text" wire:model.live="searchNit" placeholder="Ej: 900..."
                    class="block w-full px-3 py-1.5 border border-gray-300 dark:border-slate-600 rounded bg-gray-50 dark:bg-slate-800 text-sm focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-slate-400 mb-1">Nombre / Razón Social</label>
                <input type="text" wire:model.live="searchName" placeholder="Buscar cliente..."
                    class="block w-full px-3 py-1.5 border border-gray-300 dark:border-slate-600 rounded bg-gray-50 dark:bg-slate-800 text-sm focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-slate-400 mb-1">Número Cotización</label>
                <input type="text" wire:model.live="searchQuote" placeholder="Ej: COT-123"
                    class="block w-full px-3 py-1.5 border border-gray-300 dark:border-slate-600 rounded bg-gray-50 dark:bg-slate-800 text-sm focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 dark:text-slate-400 mb-1">Fecha Desde</label>
                <input type="date" wire:model.live="searchStartDate"
                    class="block w-full px-3 py-1.5 border border-gray-300 dark:border-slate-600 rounded bg-gray-50 dark:bg-slate-800 text-sm focus:ring-indigo-500">
            </div>
            <div class="flex items-end space-x-2">
                <div class="flex-1">
                    <label class="block text-xs font-medium text-gray-500 dark:text-slate-400 mb-1">Fecha Hasta</label>
                    <input type="date" wire:model.live="searchEndDate"
                        class="block w-full px-3 py-1.5 border border-gray-300 dark:border-slate-600 rounded bg-gray-50 dark:bg-slate-800 text-sm focus:ring-indigo-500">
                </div>
                <button wire:click="clearFilters" 
                    class="p-2 text-gray-500 hover:text-red-500 transition-colors" title="Limpiar filtros">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
            </div>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-800 rounded-lg border border-gray-200 dark:border-slate-700 transition-colors">
        <!-- Vista de Tabla (Desktop) -->
        <div class="hidden lg:block overflow-x-auto">
            <table class="w-full">
                <thead>
                    <tr class="border-b border-gray-200 dark:border-slate-700">
                        <th class="px-4 py-3 text-center w-10">
                            <input type="checkbox" wire:model.live="selectAll"
                                class="rounded border-gray-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500 h-4 w-4">
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-slate-300 transition-colors">
                            <div class="flex items-center space-x-1">
                                <span>REMISIÓN #</span>
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"></path>
                                </svg>
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-400 uppercase tracking-wider cursor-pointer hover:text-gray-700 dark:hover:text-slate-300 transition-colors">
                            <div class="flex items-center space-x-1">
                                <span>CLIENTE</span>
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"></path>
                                </svg>
                            </div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase tracking-wider cursor-pointer hover:text-slate-300 transition-colors">
                            <div class="flex items-center space-x-1">
                                <span>ESTADO</span>
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"></path>
                                </svg>
                            </div>
                        </th>
                        <th class="hidden lg:table-cell px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase tracking-wider cursor-pointer hover:text-slate-300 transition-colors">
                            <div class="flex items-center space-x-1">
                                <span>SUCURSAL</span>
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"></path>
                                </svg>
                            </div>
                        </th>
                        <th class="hidden lg:table-cell px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase tracking-wider cursor-pointer hover:text-slate-300 transition-colors">
                            <div class="flex items-center space-x-1">
                                <span>TELÉFONO</span>
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"></path>
                                </svg>
                            </div>
                        </th>
                        <th class="hidden md:table-cell px-6 py-3 text-left text-xs font-medium text-slate-400 uppercase tracking-wider cursor-pointer hover:text-slate-300 transition-colors">
                            <div class="flex items-center space-x-1">
                                <span>FECHA</span>
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"></path>
                                </svg>
                            </div>
                        </th>
                        <th class="px-6 py-3 text-center text-xs font-medium text-slate-400 uppercase tracking-wider">
                            ACCIONES
                        </th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($remissions as $remission)
                        <tr class="border-b border-gray-200 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-700 transition-colors">
                            <td class="px-4 py-4 text-center">
                                @if(!in_array($remission->status, ['DEVUELTO', 'ANULADO', 'VENCIDO']))
                                    <input type="checkbox" wire:model.live="selectedRemissions" value="{{ $remission->id }}"
                                        class="rounded border-gray-300 dark:border-slate-600 text-indigo-600 focus:ring-indigo-500 h-4 w-4">
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                #{{ $remission->consecutive }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700 dark:text-slate-300">
                                {{ $remission->quote->customer_name ?? 'N/A' }}
                                @if(isset($remission->quote->customer->billingEmail))
                                    <br><small class="text-gray-500 hidden sm:inline">{{ $remission->quote->customer->billingEmail }}</small>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <span class="px-2 py-1 text-xs font-semibold rounded-full 
                                    @if($remission->status === 'REGISTRADO') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                    @elseif($remission->status === 'ANULADO') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                    @else bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 @endif">
                                    {{ $remission->status }}
                                </span>
                            </td>
                            <td class="hidden lg:table-cell px-6 py-4 text-sm text-gray-700 dark:text-slate-300">
                                {{ $remission->quote->warehouse->name ?? 'N/A' }}
                            </td>
                            <td class="hidden lg:table-cell px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-slate-300">
                                @php
                                    $contact = $remission->quote?->warehouse?->contacts?->first();
                                @endphp
                                {{ $contact ? ($contact->business_phone ?? $contact->personal_phone) : 'Sin contacto' }}
                            </td>
                            <td class="hidden md:table-cell px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-slate-300">
                                {{ $remission->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-center text-sm font-medium">
                                <div x-data="{ open: false, top: 0, left: 0 }" 
                                     @scroll.window="open = false" 
                                     class="relative inline-block text-left">
                                    <button @click="
                                            open = !open; 
                                            $nextTick(() => {
                                                const rect = $el.getBoundingClientRect();
                                                top = rect.bottom + window.scrollY;
                                                left = rect.right - 192; 
                                            });
                                        "
                                        class="flex items-center text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none focus:ring-2 focus:ring-indigo-500 rounded-lg p-1 transition-colors">
                                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                                            <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
                                        </svg>
                                    </button>
                                    <div x-show="open"
                                        x-transition:enter="transition ease-out duration-100"
                                        x-transition:enter-start="transform opacity-0 scale-95"
                                        x-transition:enter-end="transform opacity-100 scale-100"
                                        x-transition:leave="transition ease-in duration-75"
                                        x-transition:leave-start="transform opacity-100 scale-100"
                                        x-transition:leave-end="transform opacity-0 scale-95"
                                        @click.outside="open = false"
                                        class="fixed w-48 rounded-lg shadow-lg bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 dark:ring-gray-700 z-[9999]"
                                        :style="`top: ${top}px; left: ${left}px;`"
                                        style="display: none;">
                                        <div class="py-1">
                                            <button wire:click="viewDetails({{ $remission->id }}); open=false"
                                                class="w-full text-left px-4 py-2 text-sm text-indigo-800 dark:text-indigo-300 hover:bg-indigo-50 dark:hover:bg-indigo-900/20 transition-colors flex items-center">
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                Ver Detalle
                                            </button>
                                            <button wire:click="editarRemision({{ $remission->id }}); open=false" class="w-full text-left px-4 py-2 text-sm text-yellow-800 dark:text-yellow-300 hover:bg-yellow-50 dark:hover:bg-yellow-900/20 transition-colors flex items-center">
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                Editar
                                            </button>
                                            <button wire:click="printRemission({{ $remission->id }}); open=false" class="w-full text-left px-4 py-2 text-sm text-green-800 dark:text-green-300 hover:bg-green-50 dark:hover:bg-green-900/20 transition-colors flex items-center border-b border-gray-100 dark:border-gray-700">
                                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                                                Imprimir
                                            </button>
                                            @if(!in_array($remission->status, ['ANULADO', 'ENTREGADO']))
                                                <button @click="open = false; confirmAnnul({{ $remission->id }}, '{{ $remission->consecutive }}')" 
                                                    class="w-full text-left px-4 py-2 text-sm text-red-800 dark:text-red-300 hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors flex items-center">
                                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                    Anular
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Vista de Tarjetas (Mobile) -->
        <!-- Vista de Tarjetas (Mobile) -->
        <div class="lg:hidden p-4 space-y-4">
            @forelse($remissions as $remission)
                <div class="bg-white dark:bg-slate-800 rounded-xl shadow-sm border border-gray-100 dark:border-slate-700 overflow-hidden">
                    <!-- Header Tarjeta Dark -->
                    <div class="bg-slate-900 dark:bg-slate-950 px-4 py-3 flex justify-between items-center">
                        <div class="flex flex-col">
                            <span class="text-white font-bold text-base">Remisión #{{ $remission->consecutive }}</span>
                        </div>
                        <div class="text-right">
                            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-tighter">{{ $remission->created_at->format('d/m/Y') }}</p>
                            <p class="text-[10px] text-slate-500 font-medium">{{ $remission->created_at->format('H:i') }}</p>
                        </div>
                    </div>

                    <div class="p-4">
                        <div class="space-y-4 mb-6">
                            <!-- Cliente -->
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-0.5">CLIENTE</p>
                                    <p class="text-sm font-bold text-gray-900 dark:text-white leading-tight">
                                        {{ $remission->quote->customer_name ?? 'N/A' }}
                                    </p>
                                    @if(isset($remission->quote->customer->billingEmail))
                                        <p class="text-xs text-gray-500 truncate max-w-[200px]">{{ $remission->quote->customer->billingEmail }}</p>
                                    @endif
                                </div>
                            </div>

                            <!-- Estado y Sucursal -->
                            <div class="grid grid-cols-2 gap-4 pt-2 border-t border-gray-50 dark:border-slate-700/50">
                                <div>
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">ESTADO</p>
                                    <span class="inline-block px-3 py-1 text-[10px] font-bold rounded-full
                                        @if($remission->status === 'REGISTRADO') bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300
                                        @elseif($remission->status === 'ANULADO') bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300
                                        @else bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300 @endif">
                                        {{ $remission->status }}
                                    </span>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1 text-right">SUCURSAL</p>
                                    <p class="text-[11px] text-gray-700 dark:text-slate-300 font-medium text-right flex items-center justify-end gap-1">
                                        <span>🏢 {{ $remission->quote->warehouse->name ?? 'N/A' }}</span>
                                    </p>
                                </div>
                            </div>

                            <!-- Vendedor -->
                            <div class="pt-2 border-t border-gray-50 dark:border-slate-700/50">
                                <p class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-1">VENDEDOR</p>
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-slate-100 dark:bg-slate-700 flex items-center justify-center text-slate-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    </div>
                                    <p class="text-xs text-gray-700 dark:text-slate-300 font-medium">
                                        {{ $remission->user->name ?? 'N/A' }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Acciones -->
                        <div class="flex items-center gap-2 border-t border-gray-100 dark:border-slate-700 pt-4 mt-2">
                            <button wire:click="viewDetails({{ $remission->id }})" 
                                class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl py-2.5 text-xs font-bold flex items-center justify-center gap-2 transition-all active:scale-95 shadow-sm shadow-indigo-200">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                Detalle
                            </button>

                            <button wire:click="editarRemision({{ $remission->id }})"
                                class="bg-yellow-500 hover:bg-yellow-600 text-white p-2.5 rounded-xl transition-all active:scale-95 shadow-sm shadow-yellow-100"
                                title="Editar remisión">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            </button>
                            
                            <button wire:click="printRemission({{ $remission->id }})"
                                class="bg-blue-500 hover:bg-blue-600 text-white p-2.5 rounded-xl transition-all active:scale-95 shadow-sm shadow-blue-100"
                                title="Imprimir remisión">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                            </button>

                            @if(!in_array($remission->status, ['ANULADO', 'ENTREGADO']))
                                <button @click="confirmAnnul({{ $remission->id }}, '{{ $remission->consecutive }}')"
                                    class="bg-red-500 hover:bg-red-600 text-white p-2.5 rounded-xl transition-all active:scale-95 shadow-sm shadow-red-100"
                                    title="Anular remisión">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <div class="bg-white dark:bg-slate-800 p-12 text-center rounded-xl border-2 border-dashed border-gray-100 dark:border-slate-700">
                    <div class="w-16 h-16 bg-gray-50 dark:bg-slate-700/50 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0a2 2 0 01-2 2H6a2 2 0 01-2-2m16 0V9a2 2 0 00-2-2H6a2 2 0 00-2 2v4m16 0l-4-4m-8 4l4-4"/></svg>
                    </div>
                    <p class="text-gray-500 font-medium italic">No se encontraron remisiones</p>
                </div>
            @endforelse
        </div>
    </div>

        <script>
            function confirmAnnul(id, consecutive) {
                Swal.fire({
                    title: '¿Estás seguro?',
                    text: `La remisión No. ${consecutive} será anulada y la cotización volverá a estar disponible para facturar o remisionar.`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Sí, anular remisión',
                    cancelButtonText: 'No, cancelar',
                    background: document.documentElement.className.includes('dark') ? '#1e293b' : '#fff',
                    color: document.documentElement.className.includes('dark') ? '#f8fafc' : '#1e293b'
                }).then((result) => {
                    if (result.isConfirmed) {
                        @this.anularRemision(id);
                    }
                });
            }
        </script>

        <!-- Paginación -->
        @if($remissions->hasPages())
            <div class="px-6 py-4 border-t border-gray-200 dark:border-slate-700">
                {{ $remissions->links() }}
            </div>
        @endif
    <!-- Modal de Detalle de Remisión -->
    <div x-data="{ show: @entangle('showDetailModal') }"
         x-show="show"
         class="fixed inset-0 z-[60] overflow-y-auto"
         style="display: none;"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">
        
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <!-- Overlay -->
            <div class="fixed inset-0 transition-opacity" aria-hidden="true" @click="show = false">
                <div class="absolute inset-0 bg-gray-500 dark:bg-slate-900 opacity-75"></div>
            </div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <!-- Modal Content -->
            <div class="inline-block align-bottom bg-white dark:bg-slate-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full border border-gray-200 dark:border-slate-700"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                
                @if($selectedRemission)
                    <!-- Header -->
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-700 flex justify-between items-start">
                        <div>
                            <h3 class="text-xl font-bold text-gray-900 dark:text-white">
                                Detalles de Remisión #{{ $selectedRemission->consecutive }}
                            </h3>
                            <p class="text-sm text-gray-500 dark:text-slate-400 mt-1">
                                Fecha: {{ $selectedRemission->created_at->format('d/m/Y H:i') }} | Estado: 
                                <span class="font-medium text-indigo-500">{{ $selectedRemission->status }}</span>
                            </p>
                        </div>
                        <button @click="show = false" class="text-gray-400 hover:text-gray-500 dark:hover:text-slate-300 transition-colors">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                            <!-- Info Cliente -->
                            <div class="bg-gray-50 dark:bg-slate-700/50 rounded-xl p-5 border border-gray-100 dark:border-slate-700">
                                <h4 class="text-xs font-bold text-indigo-500 uppercase tracking-wider mb-4">Información del Cliente</h4>
                                <div class="space-y-3">
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-500 dark:text-slate-400">Nombre:</span>
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $selectedRemission->quote->customer_name ?? 'N/A' }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-500 dark:text-slate-400">Identificación:</span>
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $selectedRemission->quote->customer->identification ?? 'N/A' }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-500 dark:text-slate-400">Tipo persona:</span>
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">Natural</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-500 dark:text-slate-400">Email:</span>
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $selectedRemission->quote->customer->billingEmail ?? 'N/A' }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Detalles Generales -->
                            <div class="bg-gray-50 dark:bg-slate-700/50 rounded-xl p-5 border border-gray-100 dark:border-slate-700">
                                <h4 class="text-xs font-bold text-indigo-500 uppercase tracking-wider mb-4">Detalles Generales</h4>
                                <div class="space-y-3">
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-500 dark:text-slate-400">Cotización:</span>
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">#{{ $selectedRemission->quote->consecutive ?? 'N/A' }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-500 dark:text-slate-400">Sucursal:</span>
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $selectedRemission->quote->warehouse->name ?? 'N/A' }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-sm text-gray-500 dark:text-slate-400">Fecha Entrega:</span>
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $selectedRemission->deliveryDate ? \Carbon\Carbon::parse($selectedRemission->deliveryDate)->format('d/m/Y') : 'N/A' }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tabla de Productos -->
                        <div class="overflow-hidden rounded-xl border border-gray-200 dark:border-slate-700 mb-6">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                                <thead class="bg-gray-50 dark:bg-slate-700/50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Producto</th>
                                        <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Cant.</th>
                                        <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Precio Unit.</th>
                                        <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 dark:text-slate-400 uppercase tracking-wider">Total</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-slate-800 divide-y divide-gray-200 dark:divide-slate-700">
                                    @php $totalModal = 0; @endphp
                                    @foreach($selectedRemission->details as $detalle)
                                        @php 
                                            $subtotal = $detalle->quantity * $detalle->value;
                                            $totalModal += $subtotal;
                                        @endphp
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white font-medium">
                                                {{ $detalle->item->name ?? $detalle->description }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-500 dark:text-slate-300">
                                                {{ number_format($detalle->quantity, 2) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm text-gray-500 dark:text-slate-300">
                                                ${{ number_format($detalle->value, 2) }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-bold text-gray-900 dark:text-white">
                                                ${{ number_format($subtotal, 2) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="bg-gray-50 dark:bg-slate-700/50">
                                    <tr>
                                        <td colspan="3" class="px-6 py-4 text-right text-sm font-bold text-gray-700 dark:text-white uppercase tracking-wider">
                                            Total General:
                                        </td>
                                        <td class="px-6 py-4 text-right text-sm font-bold text-indigo-500">
                                            ${{ number_format($totalModal, 2) }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="px-6 py-4 bg-gray-50 dark:bg-slate-700/30 border-t border-gray-200 dark:border-slate-700 flex justify-end">
                        <button @click="show = false" class="bg-indigo-500 hover:bg-indigo-600 text-white px-6 py-2 rounded-lg text-sm font-bold transition-all transform hover:scale-105 active:scale-95 shadow-lg shadow-indigo-500/30">
                            Cerrar
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>




