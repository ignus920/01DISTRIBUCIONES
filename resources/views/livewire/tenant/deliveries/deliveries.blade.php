    <div x-data="deliveriesOffline" 
     @pedido-actualizado.window="isOnline ? null : refreshLocalData()"
     class="p-4 sm:p-6 bg-gray-50 dark:bg-slate-950 min-h-screen transition-colors duration-300 relative">

    <!-- Banner de estado Offline -->
    <div x-show="!isOnline" 
         style="display: none;"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="-translate-y-full"
         x-transition:enter-end="translate-y-0"
         class="bg-red-600 text-white text-[10px] py-1 text-center font-bold sticky top-0  flex items-center justify-center gap-2">
        <span>⚠️ MODO OFFLINE ACTIVADO - TRABAJANDO CON DATOS LOCALES</span>
    </div>

        <!-- Toast Compacto Superior Derecha -->
        <div x-show="showToast" 
             x-transition:enter="transition ease-out duration-300 transform" 
             x-transition:enter-start="-translate-y-10 opacity-0" 
             x-transition:enter-end="translate-y-0 opacity-100" 
             x-transition:leave="transition ease-in duration-200 transform" 
             x-transition:leave-start="translate-y-0 opacity-100" 
             x-transition:leave-end="-translate-y-10 opacity-0"
             class="fixed top-6 left-1/2 -translate-x-1/2 z-[999] px-6 py-3 rounded-2xl shadow-2xl flex items-center gap-3 border shadow-lg"
             :class="isError ? 'bg-red-600 border-red-500 text-white' : 'bg-slate-900 border-slate-700 text-white'"
             style="display: none;">
            <div class="w-2 h-2 rounded-full animate-pulse" :class="isError ? 'bg-white' : 'bg-green-500'"></div>
            <span class="text-xs font-black uppercase tracking-widest" x-text="toastMsg"></span>
        </div>
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
                <!-- Botón de Sincronización Offline -->
                <button @click="syncOfflineData" 
                        :disabled="syncing"
                        class="bg-orange-600 hover:bg-orange-700 disabled:opacity-50 text-white px-4 py-2 rounded-lg font-medium transition-colors shadow-sm flex items-center gap-2">
                    <template x-if="!syncing">
                        <x-heroicon-o-cloud-arrow-down class="w-5 h-5" />
                    </template>
                    <template x-if="syncing">
                        <svg class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </template>
                    <span x-text="syncing ? 'Sincronizando...' : 'Sincronizar Offline'"></span>
                </button>

                <div class="bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 px-4 py-2 rounded-lg font-bold text-lg shadow-inner">
                    $ {{ number_format($remissions->sum('total_amount'), 0, ',', '.') }}
                </div>
                @if(auth()->user()->profile_id != 13)
                <a href="{{ route('tenant.uploads.uploads') }}" wire:navigate class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors shadow-sm flex items-center gap-1">
                     <x-heroicon-o-clipboard-document-list class="w-4 h-4" />
                    Cargue
                </a>
                <a href="{{ route('tenant.quoter.products') }}" wire:navigate class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium transition-colors shadow-sm flex items-center gap-1">
                    <x-heroicon-o-plus class="w-4 h-4" />
                    Nuevo pedido
                </a>
                @endif
            </div>
        </div>
    </div>

    <div class="flex flex-col lg:grid lg:grid-cols-2 gap-8 w-full items-start">
        <!-- Sidebar de Filtros -->
        <aside class="w-full lg:flex-shrink-0 space-y-6">
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

                    <div class="pt-4 border-t border-gray-100 dark:border-slate-800 grid grid-cols-3 gap-2">
                        <button 
                            @if(!$selectedDeliveryId) disabled @endif
                            class="bg-green-500 hover:bg-green-600 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold py-2 rounded-lg text-[10px] flex flex-col items-center justify-center transition-all">
                            <x-heroicon-o-check-circle class="w-5 h-5 mb-1" />
                            Cierre
                        </button>
                        <button 
                            wire:click="toggleCollections" 
                            @if(!$selectedDeliveryId) disabled @endif
                            class="bg-yellow-500 hover:bg-yellow-600 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold py-2 rounded-lg text-[10px] flex flex-col items-center justify-center transition-all {{ $showCollectionsTable ? 'ring-2 ring-white ring-offset-2 ring-offset-yellow-500' : '' }}">
                            <span class="text-base leading-none mb-1">$</span>
                            Recaudado
                        </button>
                        <button 
                            wire:click="toggleReturns" 
                            @if(!$selectedDeliveryId) disabled @endif
                            class="bg-green-600 hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed text-white font-bold py-2 rounded-lg text-[10px] flex flex-col items-center justify-center transition-all {{ $showReturnsTable ? 'ring-2 ring-white ring-offset-2 ring-offset-green-600' : '' }}">
                            <x-heroicon-o-arrow-path class="w-5 h-5 mb-1" />
                            Devoluciones
                        </button>
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
                
                @if($showReturnsTable)
                <!-- Tabla de Devoluciones (Debajo de Filtros) -->
                <div class="mt-6 bg-white dark:bg-slate-900 rounded-xl shadow-lg border border-gray-100 dark:border-slate-800 overflow-hidden">
                    <div class="bg-green-600 px-4 py-3">
                        <h3 class="text-white font-bold text-sm flex items-center gap-2">
                            <x-heroicon-o-arrow-path class="w-4 h-4 text-white" />
                            Devoluciones del cargue #{{ $selectedDeliveryId }}
                        </h3>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-blue-50 dark:bg-slate-800 text-blue-600 dark:text-blue-400 font-black uppercase tracking-wider">
                                <tr>
                                    <th class="px-4 py-3"># PEDIDO</th>
                                    <th class="px-4 py-3">ITEMS</th>
                                    <th class="px-4 py-3 text-center">CANT</th>
                                    <th class="px-4 py-3 text-right">VALOR</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-slate-800">
                                @forelse($this->returnedItems as $return)
                                <tr class="hover:bg-gray-50 dark:hover:bg-slate-800/50 transition-colors">
                                    <td class="px-4 py-3">
                                        <span class="bg-green-500 text-white px-2 py-1 rounded text-[10px] font-bold">
                                            {{ $return->remission->consecutive ?? $return->remission->id }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-700 dark:text-gray-300 font-bold uppercase truncate max-w-[140px]">
                                        {{ $return->item->name ?? 'N/A' }}
                                    </td>
                                    <td class="px-4 py-3 text-center font-bold text-gray-600 dark:text-gray-400">
                                        {{ $return->cant_return }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-black text-gray-900 dark:text-white">
                                        ${{ number_format($return->cant_return * $return->value, 0, ',', '.') }}
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-8 text-center text-gray-400 dark:text-slate-500">
                                        <p class="text-[10px] font-bold uppercase">No hay devoluciones registradas</p>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                            @if($this->returnedItems->count() > 0)
                            <tfoot class="bg-gray-50 dark:bg-slate-800/80 font-black text-gray-900 dark:text-white border-t-2 border-blue-100 dark:border-blue-900">
                                <tr>
                                    <td colspan="2" class="px-4 py-3 uppercase text-[10px] tracking-widest opacity-60">TOTALES</td>
                                    <td class="px-4 py-3 text-center font-bold">{{ $this->returnedItems->sum('cant_return') }}</td>
                                    <td class="px-4 py-3 text-right text-xs text-green-600 dark:text-green-500">
                                        ${{ number_format($this->returnedItems->sum(fn($r) => $r->cant_return * $r->value), 0, ',', '.') }}
                                    </td>
                                </tr>
                            </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
                @endif

                @if($showCollectionsTable)
                <!-- Tablas de Recaudos (Debajo de Filtros) -->
                <div class="space-y-6 mt-6">
                    <!-- Tabla Recaudo de Dinero -->
                    <div class="bg-white dark:bg-slate-900 rounded-xl shadow-lg border border-gray-100 dark:border-slate-800 overflow-hidden">
                        <div class="bg-blue-600 px-4 py-3">
                            <h3 class="text-white font-bold text-[10px] uppercase tracking-widest flex items-center gap-2">
                                Recaudo de dinero
                            </h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-[11px]">
                                <thead class="bg-blue-50 dark:bg-slate-800 text-blue-600 dark:text-blue-400 font-black uppercase tracking-wider">
                                    <tr>
                                        <th class="px-3 py-2">Forma pago</th>
                                        <th class="px-3 py-2">Sistema</th>
                                        <th class="px-3 py-2 text-right">Descuento</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-slate-800">
                                    @forelse($this->collections as $col)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-slate-800/50 transition-colors">
                                        <td class="px-3 py-2 font-bold uppercase text-gray-700 dark:text-gray-300">
                                            {{ $col->methodPayment->description ?? 'N/A' }}
                                        </td>
                                        <td class="px-3 py-2 font-black text-gray-900 dark:text-white">
                                            ${{ number_format($col->system_total, 0, ',', '.') }}
                                        </td>
                                        <td class="px-3 py-2 text-right font-black text-gray-900 dark:text-white">
                                            ${{ number_format($col->discount_total, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="3" class="px-3 py-4 text-center text-gray-400 dark:text-slate-500 uppercase font-bold text-[10px]">Sin recaudos</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                                <tfoot class="bg-gray-50 dark:bg-slate-800/80 font-black text-gray-900 dark:text-white border-t-2 border-blue-100 dark:border-blue-900">
                                    <tr>
                                        <td class="px-3 py-2 uppercase text-[10px] tracking-widest opacity-60">TOTALES</td>
                                        <td class="px-3 py-2 font-bold">${{ number_format($this->collections->sum('system_total'), 0, ',', '.') }}</td>
                                        <td class="px-3 py-2 text-right font-bold">${{ number_format($this->collections->sum('discount_total'), 0, ',', '.') }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Tabla Creditos -->
                    <div class="bg-white dark:bg-slate-900 rounded-xl shadow-lg border border-gray-100 dark:border-slate-800 overflow-hidden">
                        <div class="bg-red-600 px-4 py-3">
                            <h3 class="text-white font-bold text-[10px] uppercase tracking-widest flex items-center gap-2">
                                Credito
                            </h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-[11px]">
                                <thead class="bg-red-50 dark:bg-slate-800 text-red-600 dark:text-red-400 font-black uppercase tracking-wider">
                                    <tr>
                                        <th class="px-3 py-2">Credito</th>
                                        <th class="px-3 py-2 text-right">Valor</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-slate-800">
                                    @forelse($this->credits as $credit)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-slate-800/50 transition-colors">
                                        <td class="px-3 py-2 font-bold uppercase text-gray-700 dark:text-gray-300 truncate max-w-[150px]">
                                            {{ $credit->customer }}
                                        </td>
                                        <td class="px-3 py-2 text-right font-black text-gray-900 dark:text-white">
                                            ${{ number_format($credit->balance, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="2" class="px-3 py-4 text-center text-gray-400 dark:text-slate-500 uppercase font-bold text-[10px]">Sin créditos</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                                <tfoot class="bg-gray-50 dark:bg-slate-800/80 font-black text-gray-900 dark:text-white border-t-2 border-red-100 dark:border-red-900">
                                    <tr>
                                        <td class="px-3 py-2 uppercase text-[10px] tracking-widest opacity-60">TOTAL CRÉDITOS</td>
                                        <td class="px-3 py-2 text-right font-bold">${{ number_format($this->credits->sum('balance'), 0, ',', '.') }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </aside>

        <!-- Lista de Pedidos -->
        <main class="w-full space-y-6">
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
                <div wire:key="rem-{{ $remission->id }}" 
                     class="bg-white dark:bg-slate-900 text-gray-900 dark:text-white rounded-xl shadow-lg border border-gray-100 dark:border-slate-800 overflow-hidden transform transition hover:scale-[1.01] group">
                    <div class="p-4 sm:p-6">
                        <!-- Area Clickeable: Detalles (Header e Info) -->
                        <div @click="handleViewOrder({{ $remission->id }})" class="cursor-pointer">
                            <div class="flex justify-between items-start mb-4">
                                 <h3 class="text-base sm:text-lg font-black tracking-widest uppercase text-blue-600 dark:text-blue-400 group-hover:text-blue-700 dark:group-hover:text-blue-300 transition-colors">
                                PEDIDO # {{ $remission->consecutive ?? $remission->id }} RUTA {{ $remission->quote->branch->name ?? 'N/A' }}
                             </h3>
                             <span class="px-2 py-1 rounded text-[10px] font-bold uppercase transition-colors {{ $remission->status == 'EN RECORRIDO' ? 'bg-blue-600 text-white' : ($remission->status == 'ENTREGADO' ? 'bg-green-600 text-white' : ($remission->status == 'REGISTRADO' ? 'bg-gray-500 text-white' : 'bg-red-600 text-white')) }}">
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
                            </div>
                        </div> <!-- Cierre Area Clickeable -->
                        
                        <div class="pt-4 border-t border-gray-100 dark:border-slate-800 flex items-center justify-between gap-2">
                            <div class="text-sm sm:text-base font-black uppercase tracking-tighter flex gap-3 flex-wrap">
                                <span class="text-gray-500 dark:text-slate-400">TOTAL : <span class="text-gray-900 dark:text-white">$ {{ number_format($remission->total_amount, 0, ',', '.') }}</span></span>
                                <span class="text-blue-600 dark:text-blue-400">A PAGAR : <span class="font-bold">$ {{ number_format($remission->balance_amount, 0, ',', '.') }}</span></span>
                            </div>

                                <!-- Botones de Acción (Resposivo) -->
                                <div x-data="{ openActions: false }" class="relative">
                                    <!-- Desktop: Botones en fila -->
                                    <div class="hidden sm:flex items-center gap-2">
                                        @if($remission->delivery_id)
                                            @if($remission->balance_amount > 0)
                                                <button @click.stop="handlePayOrder({{ $remission->id }})" 
                                                        class="bg-green-600 hover:bg-green-700 text-white py-2 px-3 rounded-lg text-xs font-black uppercase transition-all shadow-md active:scale-95">
                                                    Pagar
                                                </button>
                                                <button wire:click.stop="openFullReturnModal({{ $remission->id }})" 
                                                        class="bg-red-600 hover:bg-red-700 text-white py-2 px-3 rounded-lg text-xs font-black uppercase transition-all shadow-md active:scale-95">
                                                    Devolver
                                                </button>
                                            @else
                                                <span class="text-[10px] font-black uppercase text-green-500 bg-green-500/10 px-2 py-1 rounded">Pagado</span>
                                            @endif
                                        @else
                                            <span class="text-[10px] font-black uppercase text-gray-400 bg-gray-100 dark:bg-slate-800 px-2 py-1 rounded">Pendiente Cargue</span>
                                        @endif
                                        <button wire:click.stop="printOrder({{ $remission->id }})" 
                                                class="bg-blue-700 hover:bg-blue-800 text-white p-2 rounded-lg transition-all shadow-md active:scale-95">
                                            <x-heroicon-o-printer class="w-4 h-4" />
                                        </button>
                                    </div>

                                    <!-- Mobile: Botón 'ACCIONES' Compacto al lado de A PAGAR -->
                                    <div class="sm:hidden">
                                        <button @click.stop="openActions = !openActions" 
                                                class="bg-slate-900 border border-slate-700 text-white py-2 px-4 rounded-xl text-[10px] font-black uppercase tracking-widest flex items-center gap-2 shadow-lg active:scale-95 transition-all">
                                            <span>ACCIONES</span>
                                            <x-heroicon-o-chevron-down class="w-3 h-3 transition-transform duration-300" x-bind:class="openActions ? 'rotate-180' : ''" />
                                        </button>

                                <div x-show="openActions" 
                                     @click.away="openActions = false"
                                     x-transition:enter="transition ease-out duration-200"
                                     x-transition:enter-start="opacity-0 -translate-y-2 scale-95"
                                     x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                                     x-transition:leave="transition ease-in duration-150"
                                     class="mt-2 bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-2xl shadow-2xl overflow-hidden z-[40]">
                                    <div class="p-2 space-y-1">
                                        @if($remission->balance_amount > 0)
                                            <button @click.stop="handlePayOrder({{ $remission->id }})" 
                                                    class="w-full flex items-center gap-3 px-4 py-3 bg-green-500/10 hover:bg-green-500/20 text-green-600 dark:text-green-400 rounded-xl transition-colors text-xs font-black uppercase tracking-widest">
                                                <x-heroicon-s-currency-dollar class="w-5 h-5" />
                                                Pagar 
                                            </button>
                                            <button wire:click.stop="openFullReturnModal({{ $remission->id }})" 
                                                     class="w-full flex items-center gap-3 px-4 py-3 bg-red-500/10 hover:bg-red-500/20 text-red-600 dark:text-red-400 rounded-xl transition-colors text-xs font-black uppercase tracking-widest">
                                                 <x-heroicon-s-arrow-uturn-left class="w-5 h-5" />
                                                 Devolver 
                                             </button>
                                        @else
                                            <div class="w-full flex items-center gap-3 px-4 py-3 bg-gray-100 dark:bg-slate-800 text-gray-400 rounded-xl text-xs font-black uppercase tracking-widest">
                                                <x-heroicon-s-check-circle class="w-5 h-5 text-green-500" />
                                                Ya Pagado
                                            </div>
                                        @endif
                                        <button wire:click.stop="printOrder({{ $remission->id }})" 
                                                class="w-full flex items-center gap-3 px-4 py-3 bg-blue-500/10 hover:bg-blue-500/20 text-blue-600 dark:text-blue-400 rounded-xl transition-colors text-xs font-black uppercase tracking-widest">
                                            <x-heroicon-s-printer class="w-5 h-5" />
                                            Imprimir
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
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
    <div x-show="open" 
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
                
                <template x-if="true"> <!-- Usamos template para evitar errores de renderizado de Blade cuando no hay orden -->
                <div class="w-full">
                    <!-- Cabecera del Modal Rediseñada -->
                    <div class="bg-slate-900 dark:bg-black px-4 py-4 border-b border-slate-800 dark:border-slate-800 flex justify-between items-center transition-colors">
                        <div class="flex flex-col">
                            <h3 class="text-lg font-black text-yellow-500 uppercase tracking-tighter leading-tight">
                                Pedido # <span x-text="order?.consecutive || '...'"></span>
                            </h3>
                            <span class="text-sm font-bold text-gray-200 dark:text-gray-300 uppercase tracking-widest break-words max-w-[250px] sm:max-w-none"
                                x-text="order ? order.customer_name : 'Cargando datos...'"></span>
                        </div>
                        <div class="flex items-center gap-4">
                            <!-- Indicador de modo dentro del modal -->
                            <div x-show="!isOnline" class="hidden sm:flex items-center gap-2 px-3 py-1 bg-red-500/20 border border-red-500/50 rounded-full">
                                <span class="relative flex h-2 w-2">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                                    <span class="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span>
                                </span>
                                <span class="text-[10px] font-black text-red-500 uppercase tracking-widest">Modo Local</span>
                            </div>
                            <button @click="open = false; $wire.closeOrderModal()" class="text-slate-400 hover:text-white transition-colors p-2 hover:bg-slate-800 rounded-full">
                                <x-heroicon-o-x-mark class="w-7 h-7" />
                            </button>
                        </div>
                    </div>

                    <!-- Banner Offline Móvil (Dentro del modal) -->
                    <div x-show="!isOnline" class="sm:hidden bg-red-600 text-white text-[10px] font-black text-center py-2 uppercase tracking-widest px-4 leading-tight">
                        ⚠ Trabajando con datos locales (Sin conexión)
                    </div>

                    <div class="bg-white dark:bg-slate-950 p-0 transform transition-all">
                        <!-- Área de Edición de Producto (Especial Táctil) -->
                        <template x-if="order && order.details && order.details[currentItemIndex]">
                            <div class="p-6 bg-blue-50/50 dark:bg-blue-900/10 border-b border-blue-100 dark:border-blue-900/30">
                                <div class="flex flex-col items-center text-center space-y-6">
                                    <div class="space-y-1">
                                        <span class="text-[10px] font-black uppercase tracking-[0.2em] text-blue-600 dark:text-blue-400 opacity-70">Editando Producto</span>
                                        <h4 class="text-2xl sm:text-3xl font-black text-gray-900 dark:text-white leading-tight"
                                            x-text="order.details[currentItemIndex].name"></h4>
                                    </div>

                                    <!-- Controles de Cantidad Gigantes -->
                                    <div class="flex items-center justify-center gap-6 sm:gap-10 py-4">
                                        <button @click="handleDecrement(order.details[currentItemIndex].id)" 
                                                class="w-16 h-16 sm:w-20 sm:h-20 flex items-center justify-center bg-white dark:bg-slate-900 border-2 border-red-500 text-red-500 rounded-2xl shadow-lg active:scale-95 transition-all hover:bg-red-50 dark:hover:bg-red-900/20">
                                            <x-heroicon-o-minus class="w-8 h-8 sm:w-10 sm:h-10 stroke-[3]" />
                                        </button>

                                        <div class="relative group">
                                            <input type="number" 
                                                :id="'qty-' + order.details[currentItemIndex].id" 
                                                inputmode="numeric"
                                                x-model.number="returnQuantities[order.details[currentItemIndex].id]"
                                                class="w-32 sm:w-40 text-center text-4xl sm:text-5xl font-black bg-transparent border-none focus:ring-0 text-gray-900 dark:text-white p-0" />
                                            <div class="absolute -bottom-2 left-0 right-0 h-1 bg-blue-500/30 dark:bg-blue-500/20 rounded-full"></div>
                                        </div>

                                        <button @click="handleIncrement(order.details[currentItemIndex].id, order.details[currentItemIndex].quantity)" 
                                                class="w-16 h-16 sm:w-20 sm:h-20 flex items-center justify-center bg-white dark:bg-slate-900 border-2 border-green-500 text-green-500 rounded-2xl shadow-lg active:scale-95 transition-all hover:bg-green-50 dark:hover:bg-green-900/20">
                                            <x-heroicon-o-plus class="w-8 h-8 sm:w-10 sm:h-10 stroke-[3]" />
                                        </button>
                                    </div>

                                    <div class="w-full max-w-md space-y-3">
                                        <div class="relative">
                                            <label class="text-[10px] font-black uppercase text-gray-400 mb-1 block text-left">Observaciones de Devolución</label>
                                            <textarea x-model="returnObservations[order.details[currentItemIndex].id]" 
                                                    placeholder="¿Por qué se devuelve? (Obligatorio)"
                                                    class="w-full px-4 py-3 rounded-xl border-gray-200 dark:border-slate-800 dark:bg-slate-900 dark:text-gray-200 text-sm focus:ring-2 focus:ring-blue-500/50 transition-all resize-none h-20"
                                                    :class="((returnQuantities[order.details[currentItemIndex].id] || 0) > 0 && !(returnObservations[order.details[currentItemIndex].id] || '')) ? 'border-red-500 ring-1 ring-red-500' : ''"></textarea>
                                        </div>
                                        
                                        <button @click="handleSaveReturn(order.details[currentItemIndex].id)" 
                                                :disabled="syncing"
                                                class="w-full bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-wait text-white font-black py-4 rounded-xl transition-all shadow-lg shadow-blue-500/20 active:scale-[0.98] uppercase text-xs tracking-widest flex items-center justify-center gap-3">
                                            <div x-show="syncing">
                                                <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                </svg>
                                            </div>
                                            <x-heroicon-s-check-circle x-show="!syncing" class="w-5 h-5" />
                                            <span>Guardar Producto</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <div class="p-6">
                            <!-- Navegación y Buscador -->
                            <div class="flex flex-col sm:flex-row items-center justify-between gap-4 mb-6">
                                <div class="flex items-center gap-3 w-full sm:w-auto">
                                    <button @click="currentItemIndex > 0 ? currentItemIndex-- : null" 
                                            :disabled="currentItemIndex == 0"
                                            class="flex-1 sm:flex-none px-6 py-3 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 disabled:opacity-30 disabled:pointer-events-none rounded-xl font-black text-xs uppercase tracking-widest transition-all text-gray-700 dark:text-gray-200">
                                        Anterior
                                    </button>
                                    <button @click="order && currentItemIndex < order.details.length - 1 ? currentItemIndex++ : null" 
                                            :disabled="!order || currentItemIndex >= order.details.length - 1"
                                            class="flex-1 sm:flex-none px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl font-black text-xs uppercase tracking-widest transition-all shadow-md active:scale-95 shadow-blue-500/20">
                                        Siguiente
                                    </button>
                                </div>
                                
                                <div class="relative w-full sm:w-64">
                                    <x-heroicon-o-magnifying-glass class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400" />
                                    <input type="text" x-model="searchLocal" placeholder="Buscar producto..." class="w-full pl-10 pr-4 py-2.5 rounded-xl border-gray-100 dark:border-slate-800 dark:bg-slate-900 dark:text-gray-200 text-sm focus:ring-2 focus:ring-blue-500/50 focus:border-blue-500 transition-all">
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
                                        <template x-for="(detail, index) in (order?.details || []).filter(d => d.name.toLowerCase().includes(searchLocal.toLowerCase()))" :key="detail.id">
                                            <tr class="transition-colors" :class="index === currentItemIndex ? 'bg-blue-500/[0.03] dark:bg-blue-500/[0.05]' : ''">
                                                <td class="px-5 py-4 font-bold text-gray-700 dark:text-gray-300">
                                                    <div class="flex items-center gap-3">
                                                        <div x-show="index === currentItemIndex" class="w-2 h-2 bg-blue-500 rounded-full animate-pulse"></div>
                                                        <span x-text="detail.name"></span>
                                                    </div>
                                                </td>
                                                <td class="px-5 py-4 text-center text-gray-600 dark:text-gray-400 font-medium" x-text="detail.quantity"></td>
                                                <td class="px-5 py-4 text-center">
                                                    <span class="px-2.5 py-1 rounded-lg" 
                                                        :class="(returnQuantities[detail.id] || 0) > 0 ? 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 font-black' : 'text-gray-400 dark:text-slate-600'"
                                                        x-text="returnQuantities[detail.id] || 0"></span>
                                                </td>
                                                <td class="px-5 py-4 text-right font-black text-gray-900 dark:text-white" 
                                                    x-text="'$ ' + ((detail.quantity - (returnQuantities[detail.id] || 0)) * detail.value).toLocaleString()"></td>
                                            </tr>
                                        </template>
                                    </tbody>
                                    <tfoot class="bg-gray-50 dark:bg-slate-900/80 text-gray-900 dark:text-white font-black border-t border-gray-100 dark:border-slate-800">
                                        <tr>
                                            <td class="px-5 py-4 uppercase text-[10px] tracking-widest opacity-60">Total Pedido</td>
                                            <td class="px-5 py-4 text-center" x-text="order?.details?.reduce((acc, d) => acc + d.quantity, 0) || 0"></td>
                                            <td class="px-5 py-4 text-center text-red-500" x-text="Object.values(returnQuantities).reduce((acc, v) => acc + (v || 0), 0)"></td>
                                            <td class="px-5 py-4 text-right text-lg" 
                                                x-text="'$ ' + (order?.details?.reduce((acc, d) => acc + ((d.quantity - (returnQuantities[d.id] || 0)) * d.value), 0) || 0).toLocaleString()"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>

                        <div class="bg-gray-50 dark:bg-slate-900/50 px-6 py-4 flex flex-col sm:flex-row justify-between items-center gap-4 border-t border-gray-100 dark:border-slate-800">
                            <div class="flex flex-col items-center sm:items-start text-center sm:text-left">
                                <span class="text-[10px] font-black uppercase tracking-widest text-gray-400 dark:text-slate-500">Valor Final Neto</span>
                                <div class="text-2xl font-black text-green-600 dark:text-green-500" 
                                    x-text="'$ ' + (order?.details?.reduce((acc, d) => acc + ((d.quantity - (returnQuantities[d.id] || 0)) * d.value), 0) || 0).toLocaleString()"></div>
                            </div>
                            <button @click="open = false; $wire.closeOrderModal()" class="w-full sm:w-auto bg-red-500 hover:bg-red-600 text-white font-black py-3 px-8 rounded-xl transition-all shadow-md active:scale-95 text-xs uppercase tracking-widest">
                                Cerrar
                            </button>
                        </div>
                    </div>
                </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Modal de Devolución Total / Reporte -->
    <div x-data="{ open: @entangle('showingFullReturnModal') }" 
         x-show="open" 
         class="fixed inset-0 z-[100] overflow-y-auto" 
         x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 text-center">
            <div x-show="open" x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" 
                 class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity"></div>

            <div x-show="open" 
                 x-transition:enter="ease-out duration-300" 
                 x-transition:enter-start="opacity-0 translate-y-4 scale-95" 
                 x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                 class="inline-block bg-white dark:bg-slate-900 rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:max-w-md w-full p-6 border border-gray-100 dark:border-slate-800">
                
                <div class="mb-4">
                    <h3 class="text-xl font-black text-gray-900 dark:text-white uppercase tracking-tighter text-center">Reportar Devolución</h3>
                    <p class="text-sm text-gray-500 dark:text-slate-400 text-center">Indica el motivo por el cual se devuelve este pedido completo.</p>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-2">Justificación Obligatoria:</label>
                        <textarea wire:model="fullReturnObservation" 
                                class="w-full bg-gray-50 dark:bg-slate-800 border-none rounded-2xl p-4 text-sm focus:ring-2 focus:ring-red-500 transition-all text-gray-800 dark:text-white" 
                                rows="4" 
                                placeholder="Escribe aquí el motivo del reporte..."></textarea>
                    </div>

                    <div class="flex flex-col gap-2">
                        <button wire:click="confirmFullReturn" 
                                wire:loading.attr="disabled"
                                class="w-full bg-red-600 hover:bg-red-700 text-white py-4 rounded-2xl text-xs font-black uppercase tracking-widest flex items-center justify-center gap-2 transition-all shadow-lg active:scale-95 disabled:opacity-50">
                            <span wire:loading.remove wire:target="confirmFullReturn">Confirmar Devolución Total</span>
                            <span wire:loading wire:target="confirmFullReturn">Procesando...</span>
                        </button>
                        <button @click="open = false" 
                                class="w-full bg-gray-100 dark:bg-slate-800 text-gray-500 dark:text-slate-400 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all">
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Scripts para Offline Mode -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://unpkg.com/dexie/dist/dexie.js"></script>
    <!-- Modal de Pagos Offline -->
    <div x-show="paymentModalOpen" 
         style="display: none;"
         class="fixed inset-0 z-[60] overflow-y-auto" 
         aria-labelledby="modal-title" 
         role="dialog" 
         aria-modal="true">
        
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="paymentModalOpen" 
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0" 
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="ease-in duration-200" 
                 x-transition:leave-start="opacity-100" 
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" 
                 @click="paymentModalOpen = false"
                 aria-hidden="true"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="paymentModalOpen" 
                 x-transition:enter="ease-out duration-300"
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave="ease-in duration-200"
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                 class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
                
                <!-- Modal Header -->
                <div class="bg-gray-900 px-4 py-3 sm:px-6 flex justify-between items-center">
                    <h3 class="text-lg leading-6 font-medium text-white" id="modal-title">
                        Pago de Pedido #<span x-text="selectedOrderData?.consecutive"></span>
                    </h3>
                    <div class="bg-red-600 text-white text-xs px-2 py-1 rounded">Modo Offline</div>
                </div>

                <!-- Modal Body -->
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        
                        <!-- Columna Izquierda: Totales -->
                        <div>
                            <div class="bg-gray-100 p-4 rounded-lg mb-4 text-center">
                                <span class="block text-gray-500 text-sm uppercase">Total a Pagar</span>
                                <span class="block text-4xl font-bold text-gray-800" x-text="'$' + paymentTotal.toLocaleString()"></span>
                            </div>

                             <div class="bg-white border rounded-lg p-4 mb-4">
                                <h4 class="font-bold text-gray-700 mb-2">Balance</h4>
                                <div class="flex justify-between mb-2">
                                    <span>Pagado:</span>
                                    <span class="font-bold text-green-600" x-text="'$' + paymentPaid.toLocaleString()"></span>
                                </div>
                                <div class="flex justify-between">
                                    <span>Restante:</span>
                                    <span class="font-bold" :class="paymentTotal - paymentPaid > 0 ? 'text-red-600' : 'text-green-600'" x-text="'$' + Math.max(0, paymentTotal - paymentPaid).toLocaleString()"></span>
                                </div>
                            </div>
                            
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <h4 class="font-bold text-blue-800 mb-2 text-sm">Cambio / Vueltas</h4>
                                <div class="flex justify-between items-center mb-2">
                                     <label class="text-sm">Paga con:</label>
                                     <input type="number" class="w-32 rounded border-gray-300 p-1 text-right" placeholder="$0" @input="paymentChange = $event.target.value - paymentTotal">
                                </div>
                                <div class="text-right">
                                    <span class="text-2xl font-bold text-blue-600" x-text="'$' + (paymentChange > 0 ? paymentChange.toLocaleString() : '0')"></span>
                                </div>
                            </div>

                        </div>

                        <!-- Columna Derecha: Métodos de Pago -->
                        <div>
                            <h4 class="font-bold text-gray-700 mb-4">Formas de Pago</h4>
                            
                            <div class="space-y-3">
                                <template x-for="(method, key) in paymentMethods" :key="key">
                                    <div class="flex items-center justify-between bg-gray-50 p-3 rounded border">
                                        <div class="flex items-center gap-2">
                                            <span class="capitalize font-medium" x-text="key"></span>
                                        </div>
                                        <div class="relative w-1/2">
                                            <span class="absolute left-3 top-2 text-gray-500">$</span>
                                            <input type="number" 
                                                   x-model.number="method.value" 
                                                   @input="calculatePayment()"
                                                   class="w-full pl-6 pr-2 py-1 rounded border border-gray-300 focus:ring-blue-500 focus:border-blue-500 text-right font-bold"
                                                   placeholder="0">
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <div class="mt-4">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Observaciones</label>
                                <textarea x-model="paymentObs" class="w-full rounded border-gray-300 shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" 
                            class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-base font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 sm:ml-3 sm:w-auto sm:text-sm"
                            @click="saveOfflinePayment()">
                        Guardar Pago Offline
                    </button>
                    <button type="button" 
                            class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm"
                            @click="paymentModalOpen = false">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('deliveriesOffline', () => ({
                isOnline: navigator.onLine,
                syncing: false,
                db: null,
                showToast: false,
                toastMsg: '',
                isError: false,

                // Propiedades del Modal (Entrelazadas con Livewire)
                open: @entangle('showingOrderModal'),
                currentItemIndex: @entangle('currentItemIndex'),
                returnQuantities: @entangle('returnQuantities'),
                returnObservations: @entangle('returnObservations'),
                order: @entangle('selectedOrderData'),
                // Estado del Modal de Pagos Offline
                paymentModalOpen: false,
                selectedOrderData: null,
                paymentTotal: 0,
                paymentPaid: 0,
                paymentChange: 0,
                paymentObs: '',
                paymentMethods: {},
                paymentModalOpen: false,
                paymentTotal: 0,
                paymentPaid: 0,
                paymentChange: 0,
                paymentObs: '',
                paymentMethods: {
                    efectivo: { value: 0 },
                    nequi: { value: 0 },
                    daviplata: { value: 0 },
                    tarjeta: { value: 0 }
                },
                searchLocal: '',

                async init() {
                    // Inicializar base de datos
                    this.db = new Dexie("deliveries_db");
                    this.db.version(3).stores({
                        cargues: 'id, deliveryman_id, created_at',
                        remisiones: 'id, delivery_id, quoteId, status, customer_name, consecutive',
                        detalles: 'id, remissionId, itemId',
                        pagos_locales: '++id, remissionId, value, methodPaymentId, synced',
                        devoluciones_locales: '++id, detail_id, quantity, observation, synced',
                        config: 'key'
                    });

                    window.addEventListener('online', () => { 
                        this.isOnline = true; 
                        console.log("🌐 Volvimos a estar online");
                        this.syncBackOnline();
                    });
                    window.addEventListener('offline', () => { 
                        this.isOnline = false; 
                        console.log("⚠️ Estamos offline");
                    });

                    // Escuchar eventos de Livewire para toast (Restaurando funcionalidad original)
                    Livewire.on('pedido-actualizado', () => {
                        this.toastMsg = 'Pedido actualizado';
                        this.isError = false;
                        this.showToast = true;
                        setTimeout(() => { this.showToast = false; }, 3000);
                    });
                    Livewire.on('notificar-error', (data) => {
                        this.toastMsg = data.msg || data[0].msg;
                        this.isError = true;
                        this.showToast = true;
                        setTimeout(() => { this.showToast = false; }, 4000);
                    });
                },

                async handleViewOrder(id) {
                    if (this.isOnline) {
                        this.$wire.viewOrder(id);
                        return;
                    }

                    // Lógica Offline
                    try {
                        const rem = await this.db.remisiones.get(Number(id));
                        if (!rem) throw new Error("Pedido no encontrado localmente.");

                        const details = await this.db.detalles.where('remissionId').equals(Number(id)).toArray();
                        
                        // Poblar el estado serializado manualmente (mimetizando el backend)
                        this.order = {
                            id: rem.id,
                            consecutive: rem.consecutive || rem.id,
                            customer_name: rem.customer_name,
                            details: details
                        };

                        this.currentItemIndex = 0;
                        
                        // Inicializar cantidades de devolución desde IndexedDB o local
                        for (const d of details) {
                            this.returnQuantities[d.id] = d.cant_return || 0;
                            this.returnObservations[d.id] = d.observations_return || '';
                        }

                        this.open = true;
                    } catch (e) {
                        console.error(e);
                        Swal.fire('Error Offline', e.message, 'error');
                    }
                },

                handleIncrement(detailId, maxQty) {
                    let current = this.returnQuantities[detailId] || 0;
                    if (current < maxQty) {
                        this.returnQuantities[detailId] = current + 1;
                    }
                },

                handleDecrement(detailId) {
                    let current = this.returnQuantities[detailId] || 0;
                    if (current > 0) {
                        this.returnQuantities[detailId] = current - 1;
                    }
                },

                async handleSaveReturn(detailId) {
                    if (this.isOnline) {
                        this.$wire.saveReturn(detailId);
                        return;
                    }

                    // Lógica Offline
                    const observation = this.returnObservations[detailId] || '';
                    const qty = this.returnQuantities[detailId] || 0;

                    if (qty > 0 && !observation.trim()) {
                        Swal.fire('Error', 'La observación es obligatoria si hay devolución', 'error');
                        return;
                    }

                    try {
                        this.syncing = true;
                        
                        // Verificar si ya existe un registro pendiente para este detalle
                        const existing = await this.db.devoluciones_locales.where('detail_id').equals(detailId).first();
                        
                        if (existing) {
                            await this.db.devoluciones_locales.update(existing.id, {
                                quantity: qty,
                                observation: observation,
                                synced: 0
                            });
                        } else {
                            await this.db.devoluciones_locales.put({
                                detail_id: detailId,
                                quantity: qty,
                                observation: observation,
                                synced: 0
                            });
                        }

                        // Actualizar tabla de detalles para que se vea reflejado inmediatamente
                        const detail = await this.db.detalles.get(detailId);
                        if (detail) {
                            detail.cant_return = qty;
                            detail.observations_return = observation;
                            await this.db.detalles.put(detail);
                        }

                        Swal.fire({
                            title: 'Guardado Local',
                            text: 'La devolución se guardó en el dispositivo y se sincronizará al volver online.',
                            icon: 'info',
                            toast: true,
                            position: 'top-end',
                            timer: 3000,
                            showConfirmButton: false
                        });
                    } catch (e) {
                        console.error(e);
                        Swal.fire('Error', 'No se pudo guardar localmente', 'error');
                    } finally {
                        this.syncing = false;
                    }
                },

                handlePayOrder(id) {
                    if (this.isOnline) {
                        this.$wire.payOrder(id);
                        return;
                    }
                    this.openPaymentModal(id);
                },

                async openPaymentModal(id) {
                    try {
                        const rem = await this.db.remisiones.get(Number(id));
                        if (!rem) throw new Error("Pedido no encontrado localmente.");

                        const details = await this.db.detalles.where('remissionId').equals(Number(id)).toArray();

                        // Calcular total a pagar (Total - Devoluciones)
                        let total = 0;
                        for(let d of details) {
                             const effectiveQty = d.quantity - (d.cant_return || 0);
                             if (effectiveQty > 0) {
                                 const lineSubtotal = effectiveQty * d.value;
                                 const lineTax = lineSubtotal * ((d.tax || 0) / 100);
                                 total += lineSubtotal + lineTax;
                             }
                        }

                        this.paymentTotal = Math.round(total);
                        this.paymentPaid = 0;
                        this.paymentChange = 0;
                        this.paymentObs = '';
                        // Reiniciar métodos reset
                        this.paymentMethods = {
                            efectivo: { value: 0 },
                            nequi: { value: 0 },
                            daviplata: { value: 0 },
                            tarjeta: { value: 0 }
                        };
                        
                        // Por defecto pago completo en efectivo
                        this.paymentMethods.efectivo.value = this.paymentTotal;
                        this.calculatePayment();

                        // Guardar referencia para el modal
                        this.selectedOrderData = { // Usamos selectedOrderData para reutilizar o uno nuevo
                             id: rem.id, 
                             consecutive: rem.consecutive || rem.id, 
                             customer_name: rem.customer_name 
                        };
                        
                        this.paymentModalOpen = true;

                    } catch (e) {
                         console.error(e);
                         Swal.fire('Error Offline', 'No se pudo abrir el pago: ' + e.message, 'error');
                    }
                },

                calculatePayment() {
                     let paid = 0;
                     for (let key in this.paymentMethods) {
                         paid += Number(this.paymentMethods[key].value || 0);
                     }
                     this.paymentPaid = paid;
                     this.paymentChange = paid - this.paymentTotal;
                },

                async saveOfflinePayment() {
                     // Validaciones
                     if (this.paymentPaid < this.paymentTotal) {
                         Swal.fire('Error', 'El pago debe cubrir el total. Faltan $' + (this.paymentTotal - this.paymentPaid).toLocaleString(), 'error');
                         return;
                     }

                     try {
                         this.syncing = true;
                         
                         const paymentData = {
                             remissionId: this.selectedOrderData.id,
                             total: this.paymentTotal,
                             methods: JSON.parse(JSON.stringify(this.paymentMethods)),
                             observation: this.paymentObs,
                             timestamp: new Date().toISOString(),
                             synced: 0
                         };

                         // Guardar pago
                         await this.db.pagos_locales.put(paymentData);
                         
                         // Actualizar estado local (opcional, visual)
                         await this.db.remisiones.update(this.selectedOrderData.id, { status: 'PAGADO_LOCAL' });

                         this.paymentModalOpen = false;
                         
                         Swal.fire({
                            title: 'Pago Guardado Localmente',
                            text: 'El pago se sincronizará cuando vuelvas a estar online.',
                            icon: 'success',
                            toast: true,
                            position: 'top-end',
                            timer: 3000,
                            showConfirmButton: false
                         });
                         
                     } catch(e) {
                         console.error(e);
                         Swal.fire('Error', 'No se pudo guardar el pago localmente', 'error');
                     } finally {
                         this.syncing = false;
                     }
                },

                async syncBackOnline() {
                    // Evitar múltiples ejecuciones simultáneas
                    if (this.syncing) return;

                    // Buscar devoluciones pendientes
                    const pendingReturns = await this.db.devoluciones_locales.where('synced').equals(0).toArray();

                    if (pendingReturns.length > 0) {
                        console.log("Intentando sincronizar", pendingReturns.length, "registros pendientes...");
                        this.syncing = true;
                        this.toastMsg = 'Sincronizando devoluciones pendientes...';
                        this.showToast = true;

                        try {
                            // Enviar al backend (Livewire method)
                            const result = await @this.syncPendingReturns(pendingReturns);

                            if (result) {
                                // Borrar de local si fue exitoso
                                const ids = pendingReturns.map(r => r.id);
                                await this.db.devoluciones_locales.bulkDelete(ids);

                                this.toastMsg = 'Sincronización completada';
                                this.showToast = true;
                                setTimeout(() => { this.showToast = false; }, 3000);
                            }
                        } catch (error) {
                            console.error("Error auto-sincronizando:", error);
                            this.toastMsg = 'Error al subir devoluciones. Se reintentará luego.';
                            this.isError = true;
                            this.showToast = true;
                        } finally {
                            this.syncing = false;
                        }
                    } else {
                        console.log("No hay devoluciones pendientes de sincronizar.");
                    }
                },

                async syncOfflineData() {
                    if (!this.isOnline) {
                        Swal.fire({
                            title: 'Sin conexión',
                            text: 'Debes estar online para sincronizar los datos.',
                            icon: 'warning',
                            toast: true,
                            position: 'top-end',
                            timer: 3000,
                            showConfirmButton: false
                        });
                        return;
                    }

                    this.syncing = true;
                    try {
                        const data = await @this.getSyncData();
                        console.log("Datos de sincronización recibidos:", data);

                        // Preparar datos para inserción masiva fuera de la transacción para evitar PrematureCommit
                        const remisionesData = [];
                        const detallesData = [];

                        for (const rem of data.remissions) {
                            remisionesData.push({
                                id: rem.id,
                                delivery_id: rem.delivery_id,
                                quoteId: rem.quoteId,
                                status: rem.status,
                                consecutive: rem.consecutive,
                                customer_name: rem.quote?.customer?.businessName || (rem.quote?.customer?.firstName + ' ' + rem.quote?.customer?.lastName),
                                total_amount: rem.total_amount,
                                balance_amount: rem.balance_amount
                            });

                            if (rem.details) {
                                rem.details.forEach(d => {
                                    detallesData.push({
                                        id: d.id,
                                        remissionId: d.remissionId,
                                        itemId: d.itemId,
                                        name: d.item?.name,
                                        quantity: d.quantity,
                                        cant_return: d.cant_return,
                                        value: d.value,
                                        tax: d.tax
                                    });
                                });
                            }
                        }

                        // Ejecutar limpieza e inserción secuencial (Dexie gestiona la cola)
                        await this.db.cargues.clear();
                        await this.db.remisiones.clear();
                        await this.db.detalles.clear();

                        await this.db.cargues.bulkPut(JSON.parse(JSON.stringify(data.deliveries)));
                        await this.db.remisiones.bulkPut(remisionesData);
                        await this.db.detalles.bulkPut(detallesData);

                        await this.db.config.put({ key: 'lastSync', value: new Date().toISOString() });
                        await this.db.config.put({ key: 'paymentMethods', value: JSON.parse(JSON.stringify(data.paymentMethods)) });

                        Swal.fire({
                            title: '¡Sincronizado!',
                            text: 'Datos sincronizados correctamente para modo offline',
                            icon: 'success',
                            toast: true,
                            position: 'top-end',
                            timer: 3000,
                            showConfirmButton: false
                        });
                    } catch (e) {
                        console.error("Error en sincronización:", e);
                        Swal.fire({
                            title: 'Error',
                            text: 'Error al sincronizar datos: ' + e.message,
                            icon: 'error',
                            toast: true,
                            position: 'top-end',
                            timer: 4000,
                            showConfirmButton: false
                        });
                    } finally {
                        this.syncing = false;
                    }
                }
            }));
        });
    </script>
</div>
