    <div x-data="{ 
            showToast: false, 
            toastMsg: '',
            isError: false,
            init() {
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
            }
         }"
         class="p-4 sm:p-6 bg-gray-50 dark:bg-slate-950 min-h-screen transition-colors duration-300 relative">

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
                        <button class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 rounded-lg text-[10px] flex flex-col items-center justify-center transition-all">
                            <x-heroicon-o-check-circle class="w-5 h-5 mb-1" />
                            Cierre
                        </button>
                        <button wire:click="toggleCollections" class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold py-2 rounded-lg text-[10px] flex flex-col items-center justify-center transition-all {{ $showCollectionsTable ? 'ring-2 ring-white ring-offset-2 ring-offset-yellow-500' : '' }}">
                            <span class="text-base leading-none mb-1">$</span>
                            Recaudado
                        </button>
                        <button wire:click="toggleReturns" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 rounded-lg text-[10px] flex flex-col items-center justify-center transition-all {{ $showReturnsTable ? 'ring-2 ring-white ring-offset-2 ring-offset-green-600' : '' }}">
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
                        <div wire:click="viewOrder({{ $remission->id }})" class="cursor-pointer">
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
                                        @if($remission->balance_amount > 0)
                                            <button wire:click.stop="payOrder({{ $remission->id }})" 
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
                                            <button wire:click.stop="payOrder({{ $remission->id }})" 
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

                            <div class="w-full max-w-md space-y-3">
                                <div class="relative">
                                    <label class="text-[10px] font-black uppercase text-gray-400 mb-1 block text-left">Observaciones de Devolución</label>
                                    <textarea wire:model.defer="returnObservations.{{ $currentDetail->id }}" 
                                              placeholder="¿Por qué se devuelve? (Obligatorio)"
                                              class="w-full px-4 py-3 rounded-xl border-gray-200 dark:border-slate-800 dark:bg-slate-900 dark:text-gray-200 text-sm focus:ring-2 focus:ring-blue-500/50 transition-all resize-none h-20 {{ (($returnQuantities[$currentDetail->id] ?? 0) > 0 && empty($returnObservations[$currentDetail->id] ?? '')) ? 'border-red-500 ring-1 ring-red-500' : '' }}"></textarea>
                                </div>
                                
                                <button wire:click="saveReturn({{ $currentDetail->id }})" 
                                        wire:loading.attr="disabled"
                                        class="w-full bg-blue-600 hover:bg-blue-700 disabled:opacity-50 disabled:cursor-wait text-white font-black py-4 rounded-xl transition-all shadow-lg shadow-blue-500/20 active:scale-[0.98] uppercase text-xs tracking-widest flex items-center justify-center gap-3">
                                    <div wire:loading wire:target="saveReturn({{ $currentDetail->id }})">
                                        <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </div>
                                    <x-heroicon-s-check-circle wire:loading.remove wire:target="saveReturn({{ $currentDetail->id }})" class="w-5 h-5" />
                                    <span>Guardar Producto</span>
                                </button>
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
</div>
