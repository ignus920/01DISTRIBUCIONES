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

                <div class="w-full sm:w-auto flex justify-center sm:justify-start items-center" style="background:#10b981; border:2px solid #34d399; color:#fff; border-radius:0.75rem; padding:0.4rem 1rem; gap:0.5rem; flex-shrink:0; box-shadow:0 4px 12px rgba(16,185,129,.35);">
                    <span style="font-size:1.25rem; font-weight:900; line-height:1;">$</span>
                    <div style="line-height:1;">
                        <p style="font-size:0.45rem; font-weight:900; text-transform:uppercase; letter-spacing:0.12em; color:rgba(255,255,255,0.75); margin-bottom:2px;">Cargue total</p>
                        <p style="font-size:1rem; font-weight:900; white-space:nowrap; color:#fff;">
                            <span x-text="isOnline ? '{{ number_format($remissions->sum('total_amount'), 0, ',', '.') }}' : localRemisionesList.reduce((acc, curr) => acc + Number(curr.total_amount || 0), 0).toLocaleString()"></span>
                        </p>
                    </div>
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

    <!-- SELECTOR DE CARGUE: siempre primero en cualquier pantalla -->
    <div class="w-full bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-100 dark:border-slate-800 p-4 mb-2">
        @if(auth()->user()->profile_id != 13)
        <div class="flex flex-col sm:flex-row gap-4">
            <div class="flex-1">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Cargue:</label>
                <select wire:model.live="selectedDeliveryId"
                        @change="if(!isOnline){ currentDeliveryId = $event.target.value; }"
                        class="w-full rounded-lg border-gray-200 dark:border-slate-700 dark:bg-slate-800 dark:text-gray-200 focus:border-blue-500 focus:ring-blue-500 text-sm">
                    <option value="">Seleccione un cargue</option>
                    @foreach($deliveries as $del)
                        <option value="{{ $del->id }}">
                            Cargue #{{ $del->id }} ({{ $del->created_at->format('Y-m-d') }})
                            @if($del->status == 'CERRADO') [CERRADO] @endif
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1">
                <label class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Estado Pedido:</label>
                <select wire:model.live="status"
                        @change="localStatus = $event.target.value"
                        class="w-full rounded-lg border-gray-200 dark:border-slate-700 dark:bg-slate-800 dark:text-gray-200 focus:border-blue-500 focus:ring-blue-500 text-sm">
                    <option value="Todos">Todos</option>
                    <option value="EN RECORRIDO">En Recorrido</option>
                    <option value="ENTREGADO">Entregado</option>
                    <option value="DEVUELTO">Devuelto</option>
                    <option value="REGISTRADO">Registrado</option>
                </select>
            </div>
            @if($selectedDeliveryId && auth()->user()->profile_id != 13)
            <div class="flex items-end">
                <button wire:click="closeDeliveryLoad"
                        @if($this->isCurrentDeliveryClosed) disabled @endif
                        class="w-full sm:w-auto bg-emerald-600 hover:bg-emerald-700 disabled:opacity-30 disabled:grayscale disabled:cursor-not-allowed text-white font-black py-2.5 px-4 rounded-xl text-xs flex items-center justify-center gap-2 transition-all shadow-md active:scale-95 whitespace-nowrap">
                    <x-heroicon-o-check-circle class="w-4 h-4" />
                    Cerrar cargue
                </button>
            </div>
            @endif
        </div>
        @else
        <div>
            <label class="block text-[10px] font-black text-gray-400 dark:text-slate-500 uppercase mb-2 tracking-widest">Mis Cargues Asignados:</label>
            <select wire:model.live="selectedDeliveryId"
                    @change="if(!isOnline){ currentDeliveryId = $event.target.value; }"
                    class="w-full rounded-lg border-gray-200 dark:border-slate-700 dark:bg-slate-800 dark:text-gray-200 focus:border-blue-500 focus:ring-blue-500 text-sm font-bold">
                <option value="">Ver todos mis pedidos</option>
                @foreach($deliveries as $del)
                    <option value="{{ $del->id }}">
                        Cargue #{{ $del->id }} ({{ $del->created_at->format('Y-m-d') }})
                        @if($del->status == 'CERRADO') [CERRADO] @endif
                    </option>
                @endforeach
            </select>
        </div>
        @endif
    </div>

    <div class="flex flex-col lg:grid lg:grid-cols-2 gap-8 w-full items-start">
        <!-- Sidebar: solo tablas resumen (orden: 2 en móvil, 1 en desktop) -->
        @if($selectedDeliveryId)
        <aside class="order-2 lg:order-1 w-full lg:flex-shrink-0 space-y-6">
            <div class="sticky lg:top-32 space-y-6">
                    <!-- Tabla Devoluciones -->
                    <div class="bg-white dark:bg-slate-900 rounded-xl shadow-lg border border-gray-100 dark:border-slate-800 overflow-hidden">
                        <div class="bg-blue-600 px-4 py-3">
                            <h3 class="text-white font-bold text-sm flex items-center gap-2">
                                <x-heroicon-o-arrow-path class="w-4 h-4 text-white" />
                                Devoluciones (Cargue <span x-text="currentDeliveryId ? '#' + currentDeliveryId : 'General'"></span>)
                            </h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead class="bg-blue-50 dark:bg-slate-800 text-blue-600 dark:text-blue-400 font-black uppercase tracking-wider">
                                    <tr>
                                        <th class="px-3 py-2"># PEDIDO</th>
                                        <th class="px-3 py-2">ITEMS</th>
                                        <th class="px-3 py-2 text-center text-red-500">DEV</th>
                                        <th class="px-3 py-2 text-center text-orange-500">NO ENT</th>
                                        <th class="px-3 py-2 text-center">TOTAL</th>
                                        <th class="px-3 py-2 text-right">VALOR</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-slate-800 font-medium">
                                    <template x-for="ret in filteredReturns" :key="ret.id || ret.detail_id">
                                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-800/50 transition-colors">
                                            <td class="px-3 py-2">
                                                <span class="bg-blue-500 text-white px-2 py-1 rounded text-[9px] font-bold" x-text="'#' + ret.consecutive"></span>
                                            </td>
                                            <td class="px-3 py-2 font-bold uppercase text-gray-700 dark:text-gray-300 truncate max-w-[120px]" x-text="ret.item_name"></td>
                                            <td class="px-3 py-2 text-center font-bold text-red-600 dark:text-red-400" x-text="ret.dev || '-'"></td>
                                            <td class="px-3 py-2 text-center font-bold text-orange-600 dark:text-orange-400" x-text="ret.no_ent || '-'"></td>
                                            <td class="px-3 py-2 text-center font-black text-gray-900 dark:text-white bg-gray-50 dark:bg-slate-800/30" x-text="ret.total"></td>
                                            <td class="px-3 py-2 text-right font-black text-gray-900 dark:text-white" x-text="'$' + Number(ret.subtotal || 0).toLocaleString()"></td>
                                        </tr>
                                    </template>
                                    <tr x-show="localReturnsList.length === 0">
                                        <td colspan="6" class="px-4 py-8 text-center text-gray-400 font-bold uppercase text-[10px]">No hay información disponible.</td>
                                    </tr>
                                </tbody>
                                <tfoot x-show="localReturnsList.length > 0" class="bg-gray-50 dark:bg-slate-800/80 font-black text-gray-900 dark:text-white border-t-2 border-blue-100 dark:border-blue-900">
                                    <tr>
                                        <td colspan="2" class="px-3 py-2 uppercase text-[10px] tracking-widest opacity-60 text-right">TOTALES</td>
                                        <td class="px-3 py-2 text-center font-bold text-red-600" x-text="localReturnsList.reduce((acc, curr) => acc + Number(curr.dev || 0), 0)"></td>
                                        <td class="px-3 py-2 text-center font-bold text-orange-600" x-text="localReturnsList.reduce((acc, curr) => acc + Number(curr.no_ent || 0), 0)"></td>
                                        <td class="px-3 py-2 text-center font-black" x-text="localReturnsList.reduce((acc, curr) => acc + Number(curr.total || 0), 0)"></td>
                                        <td class="px-3 py-2 text-right text-green-600 dark:text-green-500" x-text="'$' + localReturnsList.reduce((acc, curr) => acc + Number(curr.subtotal || 0), 0).toLocaleString()"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    <!-- Tabla Recaudo Local -->
                    <div class="bg-white dark:bg-slate-900 rounded-xl shadow-lg border border-gray-100 dark:border-slate-800 overflow-hidden">
                        <div class="bg-blue-600 px-4 py-3">
                            <h3 class="text-white font-bold text-[10px] uppercase tracking-widest flex items-center gap-2">
                                <x-heroicon-o-banknotes class="w-4 h-4 text-white" />
                                Recaudo Local de Dinero
                            </h3>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-[11px]">
                                <thead class="bg-blue-50 dark:bg-slate-800 text-blue-600 dark:text-blue-400 font-black uppercase tracking-wider">
                                    <tr>
                                        <th class="px-3 py-2"># PEDIDO</th>
                                        <th class="px-3 py-2">CLIENTE</th>
                                        <th class="px-3 py-2">FORMA PAGO</th>
                                        <th class="px-3 py-2">OBSERVACIÓN</th>
                                        <th class="px-3 py-2 text-right">VALOR</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-slate-800 font-medium">
                                    <template x-for="pay in filteredCollections" :key="pay.id || Math.random()">
                                        <tr class="hover:bg-gray-50 dark:hover:bg-slate-800/50 transition-colors">
                                            <td class="px-3 py-2">
                                                <span class="bg-blue-500 text-white px-2 py-1 rounded text-[9px] font-bold" x-text="'#' + pay.consecutive"></span>
                                            </td>
                                            <td class="px-3 py-2 font-bold uppercase text-gray-700 dark:text-gray-300" x-text="pay.customer_name || 'N/A'"></td>
                                            <td class="px-3 py-2 font-black text-gray-900 dark:text-white uppercase text-[10px]" x-text="pay.methods_summary"></td>
                                            <td class="px-3 py-2 text-[10px] text-gray-500 dark:text-slate-400 italic" x-text="pay.observation || 'Sin observaciones'"></td>
                                            <td class="px-3 py-2 text-right font-black text-gray-900 dark:text-white" x-text="'$' + Number(pay.value || pay.total || 0).toLocaleString()"></td>
                                        </tr>
                                    </template>
                                    <tr x-show="localCollectionsList.length === 0">
                                        <td colspan="5" class="px-4 py-8 text-center text-gray-400 font-bold uppercase text-[10px]">No hay pagos locales registrados.</td>
                                    </tr>
                                </tbody>
                                <tfoot x-show="localCollectionsList.length > 0" class="bg-gray-50 dark:bg-slate-800/80 font-black text-gray-900 dark:text-white border-t-2 border-blue-100 dark:border-blue-900">
                                    <tr>
                                        <td colspan="4" class="px-3 py-2 uppercase text-[10px] tracking-widest opacity-60">TOTAL RECAUDADO LOCAL</td>
                                        <td class="px-3 py-2 text-right font-bold" x-text="'$' + localCollectionsList.reduce((acc, curr) => acc + Number(curr.value || curr.total || 0), 0).toLocaleString()"></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
            </div>
        </aside>
        @endif

        <!-- Lista de Pedidos (orden: 1 en móvil, 2 en desktop) -->
        <main class="order-1 lg:order-2 w-full space-y-6" :class="!{{ $selectedDeliveryId ? 'true' : 'false' }} ? 'lg:col-span-2' : ''">
            <!-- Barra de Búsqueda -->
            <div class="bg-white dark:bg-slate-900 p-4 rounded-xl shadow-sm border border-gray-100 dark:border-slate-800">
                <div class="relative">
                    <x-heroicon-o-magnifying-glass class="w-5 h-5 absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 dark:text-gray-500" />
                    <input type="text" wire:model.live="search"
                           @input="localSearch = $event.target.value"
                           placeholder="Buscar por cliente..."
                           class="w-full pl-10 pr-4 py-2 rounded-lg border-gray-200 dark:border-slate-700 dark:bg-slate-800 dark:text-gray-200 focus:border-blue-500 focus:ring-blue-500 text-sm">
                </div>
            </div>

            <div class="space-y-4 w-full">
                <!-- Template Unificado para Online/Offline -->
                <template x-for="rem in filteredRemisiones" :key="rem.id">
                    <div class="bg-white dark:bg-slate-900 text-gray-900 dark:text-white rounded-xl shadow-lg border border-gray-100 dark:border-slate-800 overflow-hidden transform transition hover:scale-[1.01] group">
                        <div class="p-4 sm:p-6">
                            <!-- Area Clickeable: Detalles (Header e Info) -->
                            <div>
                                <div class="flex flex-col sm:flex-row sm:items-center gap-2 w-full justify-between">
                                    <h3 class="text-base sm:text-lg font-black tracking-widest uppercase text-blue-600 dark:text-blue-400 group-hover:text-blue-700 dark:group-hover:text-blue-300 transition-colors"
                                        x-text="'PEDIDO # ' + (rem.consecutive || rem.id) + ' - ' + (rem.route_name || 'SIN RUTA')">
                                    </h3>
                                    
                                    <!-- Botón PC: Devolver por unidad -->
                                    <button class="hidden md:inline-flex items-center gap-1.5 px-4 py-2 bg-yellow-500 hover:bg-yellow-600 text-white rounded-xl text-[10px] font-black uppercase tracking-widest transition-all shadow-md shadow-yellow-500/20 active:scale-95"
                                            x-show="rem.status != 'ENTREGADO'" 
                                            @click.stop="handleViewOrder(rem.id)">
                                        <x-heroicon-o-pencil-square class="w-4 h-4" />
                                        Devolver por unidad
                                    </button>
                                </div>

                                <div class="space-y-4 text-sm sm:text-base">
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div class="space-y-2">
                                            <p class="flex flex-col">
                                                <span class="text-gray-500 dark:text-slate-400 text-xs uppercase font-bold tracking-tighter">Entregar en:</span>
                                                 <span class="text-gray-800 dark:text-slate-100 font-semibold" x-text="rem.address || 'Sin dirección'"></span>
                                            </p>
                                        </div>
                                        <div class="space-y-2">
                                             <p class="flex flex-col">
                                                <span class="text-gray-500 dark:text-slate-400 text-xs uppercase font-bold tracking-tighter">Contacto:</span>
                                                 <span class="text-gray-800 dark:text-slate-100 font-semibold uppercase" x-text="rem.contact_name || 'N/A'"></span>
                                             </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Estado del Pedido -->
                                <div class="mt-4 flex items-center gap-2">
                                    <span class="text-xs font-bold uppercase text-gray-400 dark:text-slate-500 tracking-widest">Estado:</span>
                                    <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase shadow-sm transition-all text-white"
                                          :class="rem.status == 'EN RECORRIDO' ? 'bg-blue-600' : (rem.status == 'ENTREGADO' ? 'bg-green-600' : (rem.status == 'REGISTRADO' ? 'bg-gray-500' : 'bg-red-600'))"
                                          x-text="rem.status">
                                    </span>
                                </div>
                            </div>
                            
                            <!-- FOOTER DESKTOP (md+): totales + botones en una fila -->
                            <div class="hidden md:flex pt-4 border-t border-gray-100 dark:border-slate-800 items-center justify-between gap-2">
                                <div class="text-sm font-black uppercase tracking-tighter flex gap-3 whitespace-nowrap">
                                    <span class="text-gray-500 dark:text-slate-400">TOTAL : <span class="text-gray-900 dark:text-white" x-text="'$ ' + Number(rem.total_amount || 0).toLocaleString()"></span></span>
                                    <span class="text-blue-600 dark:text-blue-400">A PAGAR : <span class="font-bold" x-text="'$ ' + Number(rem.balance_amount || 0).toLocaleString()"></span></span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <template x-if="rem.balance_amount > 0">
                                        <div class="flex gap-2">
                                            <button @click.stop="handlePayOrder(rem.id)"
                                                    class="bg-green-600 hover:bg-green-700 text-white py-2 px-3 rounded-lg text-xs font-black uppercase transition-all shadow-md active:scale-95">
                                                Pagar
                                            </button>
                                            <button @click.stop="openFullReturnModal(rem.id)"
                                                    class="bg-red-600 hover:bg-red-700 text-white py-2 px-3 rounded-lg text-xs font-black uppercase transition-all shadow-md active:scale-95">
                                                Devolver todo
                                            </button>
                                        </div>
                                    </template>
                                    <span x-show="rem.balance_amount <= 0" class="bg-green-100 text-green-700 border border-green-200 py-2 px-3 rounded-lg text-xs font-black uppercase shadow-sm flex items-center gap-1">
                                        <x-heroicon-s-check-circle class="w-4 h-4" /> Pagado
                                    </span>
                                    <template x-if="isOnline">
                                        <div class="flex gap-2">
                                            <button @click.stop="$wire.printOrder(rem.id)"
                                                    class="bg-blue-700 hover:bg-blue-800 text-white p-2 rounded-lg transition-all shadow-md active:scale-95">
                                                <x-heroicon-o-printer class="w-4 h-4" />
                                            </button>
                                            <button @click.stop="handleShareWhatsApp(rem.id)"
                                                    class="bg-green-500 hover:bg-green-600 text-white p-2 rounded-lg transition-all shadow-md active:scale-95">
                                                <x-heroicon-o-chat-bubble-left-right class="w-4 h-4" />
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <!-- FOOTER MÓVIL/TABLET (< md): layout vertical optimizado para touch -->
                            <div class="md:hidden pt-4 border-t border-gray-100 dark:border-slate-800 space-y-3">
                                <!-- Fila 1: Totales en píldoras -->
                                <div class="flex gap-2">
                                    <div class="flex-1 bg-gray-50 dark:bg-slate-800 rounded-xl px-3 py-2 text-center">
                                        <p class="text-[8px] font-black uppercase tracking-widest text-gray-400 dark:text-slate-500 mb-0.5">Total</p>
                                        <p class="text-sm font-black text-gray-900 dark:text-white leading-none" x-text="'$ ' + Number(rem.total_amount || 0).toLocaleString()"></p>
                                    </div>
                                    <div class="flex-1 bg-blue-50 dark:bg-blue-900/20 rounded-xl px-3 py-2 text-center">
                                        <p class="text-[8px] font-black uppercase tracking-widest text-blue-400 mb-0.5">A pagar</p>
                                        <p class="text-sm font-black text-blue-600 dark:text-blue-400 leading-none" x-text="'$ ' + Number(rem.balance_amount || 0).toLocaleString()"></p>
                                    </div>
                                </div>

                                <!-- Fila 2: Botones Principales (Pagar y Devolver por Unidad) -->
                                <template x-if="rem.balance_amount > 0">
                                    <div class="grid grid-cols-2 gap-2">
                                        <button @click.stop="handlePayOrder(rem.id)"
                                                class="bg-green-600 hover:bg-green-700 text-white py-3 rounded-xl text-sm font-black uppercase tracking-widest transition-all shadow-md active:scale-95 flex items-center justify-center gap-2">
                                            <x-heroicon-s-currency-dollar class="w-5 h-5" />
                                            Pagar
                                        </button>
                                        <button x-show="rem.status != 'ENTREGADO'" 
                                                @click.stop="handleViewOrder(rem.id)" 
                                                class="bg-yellow-500 hover:bg-yellow-600 text-white py-3 rounded-xl text-xs font-black uppercase tracking-widest transition-all shadow-md shadow-yellow-500/20 active:scale-95 flex items-center justify-center gap-2">
                                            <x-heroicon-o-pencil-square class="w-4 h-4" />
                                            Devolver
                                        </button>
                                    </div>
                                </template>
                                <span x-show="rem.balance_amount <= 0"
                                      class="w-full flex items-center justify-center gap-2 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 border border-green-200 dark:border-green-800 py-3 rounded-xl text-sm font-black uppercase">
                                    <x-heroicon-s-check-circle class="w-5 h-5" /> Pagado
                                </span>

                                <!-- Fila 3: Botones secundarios -->
                                <div class="flex gap-2">
                                    <template x-if="rem.balance_amount > 0">
                                        <button @click.stop="openFullReturnModal(rem.id)"
                                                class="flex-1 bg-red-600 hover:bg-red-700 text-white py-2.5 rounded-xl text-xs font-black uppercase tracking-wide transition-all shadow-md active:scale-95 flex items-center justify-center gap-1.5">
                                            <x-heroicon-s-arrow-uturn-left class="w-4 h-4" />
                                            Dev. todo
                                        </button>
                                    </template>
                                    <template x-if="isOnline">
                                        <div class="flex gap-2" :class="rem.balance_amount > 0 ? '' : 'flex-1'">
                                            <button @click.stop="$wire.printOrder(rem.id)"
                                                    class="flex-1 bg-blue-700 hover:bg-blue-800 text-white py-2.5 px-3 rounded-xl transition-all shadow-md active:scale-95 flex items-center justify-center gap-1.5 text-xs font-black uppercase">
                                                <x-heroicon-o-printer class="w-4 h-4" />
                                                <span class="hidden xs:inline">Imprimir</span>
                                            </button>
                                            <button @click.stop="handleShareWhatsApp(rem.id)"
                                                    class="flex-1 bg-green-500 hover:bg-green-600 text-white py-2.5 px-3 rounded-xl transition-all shadow-md active:scale-95 flex items-center justify-center gap-1.5 text-xs font-black uppercase">
                                                <x-heroicon-o-chat-bubble-left-right class="w-4 h-4" />
                                                <span class="hidden xs:inline">WhatsApp</span>
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>

                <div x-show="localRemisionesList.length === 0" class="bg-white dark:bg-slate-900 p-12 rounded-xl shadow-sm border border-gray-100 dark:border-slate-800 text-center w-full">
                    <x-heroicon-o-document-magnifying-glass class="w-16 h-16 text-gray-200 dark:text-slate-700 mx-auto mb-4" />
                    <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">No se encontraron pedidos</h3>
                    <p class="text-gray-500 dark:text-gray-400 uppercase text-[10px] font-black tracking-widest" 
                       x-text="isOnline ? 'Intenta cambiar los filtros o el término de búsqueda' : 'Sincroniza datos para ver pedidos offline'"></p>
                </div>
            </div>
 <!-- Cierre de la lista -->
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
                                Pedido # <span x-text="order?.consecutive || '...'"></span> - <span x-text="order?.branch_name || ''"></span>
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
                        <!-- Justificación Global por Pedido (Mantenida arriba para visibilidad) -->
                        <div class="p-6 bg-gray-50/50 dark:bg-slate-900/40 border-b border-gray-100 dark:border-slate-800">
                            <label class="text-[10px] font-black uppercase text-gray-400 mb-2 block tracking-[0.2em]">Observaciones del Pedido (Razón de Devolución)</label>
                            <textarea x-model="orderNovelty" 
                                      class="w-full px-4 py-3 rounded-2xl border-gray-200 dark:border-slate-800 dark:bg-slate-900 dark:text-gray-200 text-sm focus:ring-2 focus:ring-blue-500/50 transition-all resize-none h-24"
                                      placeholder="¿Por qué se devuelven estos productos? (Obligatorio si hay devoluciones)"></textarea>
                        </div>
                        <!-- Área de Edición de Producto (Especial Táctil) -->
                        <template x-if="order && order.details && order.details[currentItemIndex]">
                            <div class="p-6 bg-blue-50/50 dark:bg-blue-900/10 border-b border-blue-100 dark:border-blue-900/30">
                                <div class="flex flex-col items-center text-center space-y-6">
                                    <div class="space-y-1">
                                        <span class="text-xs sm:text-sm font-black uppercase tracking-[0.3em] text-red-600 dark:text-red-500 animate-pulse">Devolución de Unidades</span>
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
                            <span x-show="Object.values(returnQuantities).some(q => q > 0) && !orderNovelty.trim()" 
                                  class="text-red-600 dark:text-red-500 text-sm font-black uppercase tracking-widest mt-2 block animate-pulse text-center sm:text-left">
                                ⚠ Diligencie la observación para poder completar la acción
                            </span>
                            <div class="flex items-center gap-3 w-full sm:w-auto">
                                <button @click="handleSaveProductNovelty(order.id)"
                                        class="flex-1 sm:flex-none bg-blue-600 hover:bg-blue-700 text-white font-black py-3 px-6 rounded-xl shadow-lg shadow-blue-500/30 flex items-center justify-center gap-2 active:scale-[0.98] transition-all disabled:opacity-50 disabled:cursor-not-allowed text-xs uppercase tracking-widest"
                                        :disabled="saving || (Object.values(returnQuantities).some(q => q > 0) && !orderNovelty.trim())">
                                    <template x-if="!saving">
                                        <div class="flex items-center gap-2">
                                            <x-heroicon-o-check-circle class="w-5 h-5" />
                                            <span>GUARDAR UNIDADES</span>
                                        </div>
                                    </template>
                                    <template x-if="saving">
                                        <div class="flex items-center gap-2">
                                            <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                            </svg>
                                            <span>GUARDANDO...</span>
                                        </div>
                                    </template>
                                </button>
                                <button @click="open = false; $wire.closeOrderModal()" class="flex-1 sm:flex-none bg-red-500 hover:bg-red-600 text-white font-black py-3 px-8 rounded-xl transition-all shadow-md active:scale-95 text-xs uppercase tracking-widest">
                                    Cerrar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Modal de Devolución Total / Reporte -->
    <div x-show="fullReturnModalOpen" 
         class="fixed inset-0 z-[100] overflow-y-auto" 
         x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 text-center">
            <div x-show="fullReturnModalOpen" 
                 x-transition:enter="ease-out duration-300" 
                 x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" 
                 class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity"></div>

            <div x-show="fullReturnModalOpen" 
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
                        <textarea x-model="fullReturnObservation" 
                                class="w-full bg-gray-50 dark:bg-slate-800 border-none rounded-2xl p-4 text-sm focus:ring-2 focus:ring-red-500 transition-all text-gray-800 dark:text-white" 
                                rows="4" 
                                placeholder="Escribe aquí el motivo del reporte..."></textarea>
                    </div>

                    <div class="flex flex-col gap-2">
                        <button @click="isOnline ? $wire.confirmFullReturn() : saveFullReturnOffline()" 
                                :disabled="!fullReturnObservation || syncing"
                                class="w-full bg-red-600 hover:bg-red-700 text-white py-4 rounded-2xl text-xs font-black uppercase tracking-widest flex items-center justify-center gap-2 transition-all shadow-lg active:scale-95 disabled:opacity-50">
                            <span x-show="!syncing">Confirmar Devolución Total</span>
                            <span x-show="syncing">Procesando...</span>
                        </button>
                        <button @click="fullReturnModalOpen = false" 
                                class="w-full bg-gray-100 dark:bg-slate-800 text-gray-500 dark:text-slate-400 py-3 rounded-2xl text-[10px] font-black uppercase tracking-widest transition-all">
                            Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Scripts para Offline Mode -->
    <!-- Scripts para Offline Mode (CDNs para estabilidad en Producción) -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/dexie@4.0.1/dist/dexie.min.js"></script>
    <!-- Modal de Pagos Offline (Diseño Unificado Premium) -->
    <div x-show="paymentModalOpen" 
         x-cloak
         class="payment-modal-container fixed inset-0 z-[60] overflow-y-auto bg-gray-900/80 backdrop-blur-sm flex items-center justify-center p-4">
        
        <div x-show="paymentModalOpen" 
             @click.away="paymentModalOpen = false"
             x-transition:enter="ease-out duration-300"
             x-transition:enter-start="opacity-0 scale-95" 
             x-transition:enter-end="opacity-100 scale-100"
             x-transition:leave="ease-in duration-200" 
             x-transition:leave-start="opacity-100 scale-100" 
             x-transition:leave-end="opacity-0 scale-95"
             class="w-full max-w-7xl h-[90vh] bg-white dark:bg-slate-900 rounded-2xl shadow-2xl overflow-hidden flex flex-col border border-gray-200 dark:border-slate-700">
            
            <!-- Header -->
            <div class="bg-gray-900 dark:bg-black text-white px-6 py-4 flex-none flex justify-between items-center">
                <div class="text-left">
                    <h1 class="text-2xl font-bold tracking-tight text-white/90">CAJA REGISTRADORA (OFFLINE)</h1>
                    <div class="flex items-center gap-2 text-sm text-gray-400 justify-start">
                        <span class="font-mono bg-gray-800 px-2 py-0.5 rounded" x-text="selectedOrderData?.consecutive"></span>
                        <span>•</span>
                        <span class="font-medium truncate max-w-md" x-text="selectedOrderData?.customer_name"></span>
                    </div>
                </div>
                <!-- Indicador Offline -->
                <div class="inline-flex items-center gap-2 bg-red-500/10 text-red-400 border border-red-500/20 px-3 py-1.5 rounded-full text-sm font-medium">
                    <span class="w-2 h-2 rounded-full bg-red-500 animate-pulse"></span>
                    Modo Offline
                </div>
            </div>

            <!-- Contenido Principal -->
            <div class="flex flex-col lg:flex-row flex-1 overflow-hidden">

                <!-- Panel Izquierdo - Resumen -->
                <div class="w-full lg:w-1/3 bg-gray-50 dark:bg-slate-800/50 p-6 border-r border-gray-200 dark:border-slate-700 overflow-y-auto">
                    <div class="space-y-6">

                        <!-- Total de la Venta -->
                        <div class="bg-white dark:bg-slate-800 rounded-xl p-6 shadow-sm border border-gray-100 dark:border-slate-700">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-slate-400 mb-2">Total a Pagar</h3>
                            <div class="text-center py-2">
                                <div class="text-5xl font-extrabold text-gray-900 dark:text-white tracking-tight" x-text="'$' + paymentTotal.toLocaleString()"></div>
                            </div>
                        </div>

                        <!-- Estado del Pago -->
                        <div class="bg-white dark:bg-slate-800 rounded-xl p-6 shadow-sm border border-gray-100 dark:border-slate-700 space-y-4">
                            <h3 class="text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-slate-400">Balance</h3>

                            <div class="flex justify-between items-baseline">
                                <span class="text-base text-gray-600 dark:text-slate-300">Pagado</span>
                                <span class="text-xl font-bold text-green-600 dark:text-green-500" x-text="'$' + paymentPaid.toLocaleString()"></span>
                            </div>

                            <div class="w-full bg-gray-200 dark:bg-slate-700 rounded-full h-2.5 overflow-hidden">
                                <div class="bg-green-500 h-2.5 rounded-full transition-all duration-500 ease-out" 
                                     :style="'width: ' + (paymentTotal > 0 ? Math.min(100, (paymentPaid / paymentTotal) * 100) : 0) + '%'"></div>
                            </div>

                            <div class="flex justify-between items-baseline pt-2 border-t border-gray-100 dark:border-slate-700/50">
                                <span class="text-base text-gray-600 dark:text-slate-300 font-medium">Restante</span>
                                <span class="text-2xl font-bold" 
                                      :class="(paymentTotal - paymentPaid) > 0 ? 'text-red-600 dark:text-red-500' : 'text-green-600 dark:text-green-500'"
                                      x-text="'$' + Math.max(0, paymentTotal - paymentPaid).toLocaleString()"></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Panel Derecho - Métodos de Pago -->
                <div class="flex-1 bg-white dark:bg-slate-900 flex flex-col min-h-0">
                    
                    <div class="p-8 flex-1 overflow-y-auto">
                        <div class="mb-6">
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white">Forma de Pago</h2>
                            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Seleccione o distribuya el valor</p>
                        </div>

                        <!-- Sección de Pago del Cliente -->
                        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4 mb-6 border border-blue-200 dark:border-blue-700">
                            <h3 class="text-sm font-semibold text-blue-800 dark:text-blue-300 mb-3 flex items-center gap-2">
                                💰 Pago del Cliente (Calculadora de Vueltas)
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <!-- Input: Con cuánto paga -->
                                <div>
                                    <label class="block text-xs font-medium text-blue-700 dark:text-blue-300 mb-2">
                                        Con cuánto paga el cliente:
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <span class="text-gray-400 font-bold">$</span>
                                        </div>
                                        <input type="number"
                                               x-model.number="paymentChangeInput"
                                               @input="paymentChange = paymentChangeInput - paymentTotal"
                                               class="pl-8 block w-full rounded-lg border-0 py-3 text-gray-900 dark:text-white shadow-sm ring-1 ring-inset ring-blue-300 dark:ring-blue-600 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-600 text-lg font-bold font-mono bg-white dark:bg-slate-800"
                                               placeholder="0">
                                    </div>
                                </div>

                                <!-- Mostrar: Vueltas -->
                                <div>
                                    <label class="block text-xs font-medium text-blue-700 dark:text-blue-300 mb-2">
                                        Vueltas para el cliente:
                                    </label>
                                    <div class="bg-white dark:bg-slate-800 rounded-lg px-4 py-3 border-2 border-blue-200 dark:border-blue-600">
                                        <span class="text-lg font-bold font-mono"
                                              :class="paymentChange >= 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'"
                                              x-text="'$' + Math.abs(paymentChange).toLocaleString()">
                                        </span>
                                        <span x-show="paymentChange < 0" class="text-xs text-red-500 block">Falta dinero</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-slate-700 overflow-hidden">
                            <!-- Lista de Métodos -->
                            <div class="divide-y divide-gray-100 dark:divide-slate-700/50">
                                <template x-for="(config, key) in paymentMethods" :key="key">
                                    <div class="group sm:grid sm:grid-cols-12 gap-4 p-5 items-center transition-all duration-200 hover:bg-gray-50 dark:hover:bg-slate-800/50"
                                         :class="config.value > 0 ? 'bg-green-50/50 dark:bg-green-900/10' : ''">

                                        <!-- Nombre -->
                                        <div class="col-span-12 sm:col-span-4 flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-lg flex items-center justify-center bg-gray-100 dark:bg-slate-800 text-gray-400 dark:text-slate-500">
                                                <span class="capitalize font-bold text-xs" x-text="key.substring(0,3)"></span>
                                            </div>
                                            <span class="font-bold text-gray-700 dark:text-gray-200 capitalize" x-text="key"></span>
                                        </div>

                                        <!-- Input -->
                                        <div class="col-span-12 sm:col-span-8 relative">
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                                                    <span class="text-gray-400 font-bold">$</span>
                                                </div>
                                                <input type="number"
                                                       x-model.number="config.value"
                                                       @input="calculatePayment()"
                                                       @focus="$el.select()"
                                                       class="pl-8 block w-full rounded-lg border-0 py-3 text-gray-900 dark:text-white shadow-sm ring-1 ring-inset ring-gray-300 dark:ring-slate-600 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-600 sm:text-xl sm:font-bold font-mono bg-white dark:bg-slate-800">
                                            </div>
                                        </div>
                                    </div>
                                </template>
                                
                                <div class="p-4 bg-gray-50 dark:bg-slate-800/80 flex justify-between items-center border-t border-gray-200 dark:border-slate-700">
                                    <span class="font-bold text-gray-500 dark:text-slate-400 uppercase tracking-widest text-sm">Total Registrado</span>
                                    <span class="text-2xl font-bold text-gray-900 dark:text-white" x-text="'$' + paymentPaid.toLocaleString()"></span>
                                </div>
                            </div>
                        </div>

                        <!-- Observaciones -->
                        <div class="mt-6">
                             <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Observaciones</label>
                             <textarea x-model="paymentObs" class="w-full rounded-lg border-gray-300 dark:border-slate-700 bg-white dark:bg-slate-800 dark:text-white shadow-sm focus:border-blue-500 focus:ring focus:ring-blue-200" rows="2"></textarea>
                        </div>
                    </div>

                    <!-- Footer de Acciones -->
                    <div class="p-6 bg-white dark:bg-slate-900 border-t border-gray-100 dark:border-slate-800 flex flex-col sm:flex-row gap-4 justify-end items-center">
                        <button @click="paymentModalOpen = false"
                                class="w-full sm:w-auto px-6 py-3 border border-gray-300 dark:border-slate-600 hover:bg-gray-50 dark:hover:bg-slate-800 text-gray-700 dark:text-gray-300 font-semibold rounded-xl transition-colors">
                            Cancelar
                        </button>

                        <button @click="saveOfflinePayment()"
                                :disabled="paymentPaid < paymentTotal || syncing"
                                class="w-full sm:w-auto px-8 py-3 bg-green-600 hover:bg-green-500 text-white font-bold rounded-xl shadow-lg shadow-green-500/20 transition-all disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                            <span>CONFIRMAR PAGO OFFLINE</span>
                            <div x-show="syncing" class="animate-spin h-4 w-4 border-2 border-white border-t-transparent rounded-full"></div>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function initDeliveriesOffline() {
            if (window.Alpine && !Alpine.data('deliveriesOffline')) {
                Alpine.data('deliveriesOffline', () => ({
                    isOnline: navigator.onLine,
                    syncing: false,
                    db: null,
                    showToast: false,
                    toastMsg: '',
                    isError: false,
                    saving: false,
                    isInitialized: false,
                    isSavingToDB: false,
                    selectedOrderId: null,

                // Propiedades del Modal (Entrelazadas con Livewire)
                open: @entangle('showingOrderModal'),
                currentItemIndex: @entangle('currentItemIndex'),
                returnQuantities: @entangle('returnQuantities'),
                returnObservations: @entangle('returnObservations'),
                order: @entangle('selectedOrderData'),
                fullReturnModalOpen: @entangle('showingFullReturnModal'),
                fullReturnObservation: @entangle('fullReturnObservation'), // Entrelazar para sync
                // Estado del Modal de Pagos Offline
                paymentModalOpen: false,
                selectedOrderData: null,
                paymentTotal: 0,
                paymentPaid: 0,
                paymentChange: 0,
                paymentChangeInput: 0, 
                paymentObs: '',
                paymentMethods: {
                    efectivo: { value: 0 },
                    nequi: { value: 0 },
                    daviplata: { value: 0 },
                    tarjeta: { value: 0 }
                },
                orderNovelty: @entangle('orderNovelty'),
                searchLocal: '',      // Para búsqueda de productos DENTRO del modal de pedido
                localSearch: '',      // Para filtrar la lista de remisiones por cliente (offline)
                localStatus: @json($status), // Espeja el estado inicial de Livewire para filtrado offline

                currentDeliveryId: @entangle('selectedDeliveryId'),
                viewCollections: @entangle('showCollectionsTable'),
                viewReturns: @entangle('showReturnsTable'),
                localCollectionsList: [],
                localReturnsList: [],
                localRemisionesList: [],
                
                async toggleCollectionsView() {
                    this.viewCollections = !this.viewCollections;
                    // Mejor Práctica: Si estamos offline, cargamos de IndexedDB.
                    // Si estamos online, los datos ya llegaron por el evento 'hydrate-local-db'.
                    if(this.viewCollections && !this.isOnline) {
                        await this.loadFinancialDetails();
                    }
                },
                async toggleReturnsView() {
                    this.viewReturns = !this.viewReturns;
                    // Mejor Práctica: Si estamos offline, cargamos de IndexedDB.
                    if(this.viewReturns && !this.isOnline) {
                         await this.loadFinancialDetails();
                    }
                },
                
                async loadFinancialDetails() {
                     if (!this.db) return;
                     
                     // 1. Obtener IDs de remisiones (filtrar por cargue si existe, sino todas las locales)
                     let query = this.db.remisiones.toCollection();
                     if (this.currentDeliveryId) {
                         query = this.db.remisiones.where('delivery_id').equals(Number(this.currentDeliveryId));
                     }
                     
                     const remisionesCargue = await query.toArray();
                     const remisionIds = remisionesCargue.map(r => r.id);
                     const remisionesMap = {};
                     remisionesCargue.forEach(r => remisionesMap[r.id] = r);
                     
                      // 2. Cargar Pagos Locales
                      const payments = await this.db.pagos_locales.toArray();
                       this.localCollectionsList = payments
                           .filter(p => p.remissionId && remisionIds.includes(Number(p.remissionId)))
                           .map(p => ({
                               ...p,
                               customer_name: (p.customer_name && p.customer_name !== 'N/A') 
                                   ? p.customer_name 
                                   : (remisionesMap[p.remissionId]?.customer_name || 'N/A'),
                               consecutive: p.consecutive || remisionesMap[p.remissionId]?.consecutive || p.remissionId,
                               methods_summary: p.methods
                                   ? (Object.keys(p.methods).filter(k => (p.methods[k].value || 0) > 0).map(k => k.toUpperCase()).join(', ') || 'EFECTIVO')
                                   : (p.loading_methods || p.methods_summary || 'EFECTIVO')
                         }));
                     
                        // 3. Devoluciones / Inventario de Ruta (FUENTE: Detalles de Remisiones)
                        // Obtenemos todos los detalles que pertenecen a las remisiones de este cargue
                        const allDetails = await this.db.detalles.where('remissionId').anyOf(remisionIds).toArray();
                        
                        // Obtenemos las acciones de devolución (manuales o de servidor)
                        const returns = await this.db.devoluciones_locales.where('remission_id').anyOf(remisionIds).toArray();
                        const returnsMap = {};
                        returns.forEach(r => {
                            const dId = Number(r.detail_id);
                            // Prioridad a la acción manual local (synced:0) sobre la del servidor (synced:1)
                            if (!returnsMap[dId] || (returnsMap[dId].synced === 1 && r.synced === 0)) {
                                returnsMap[dId] = r;
                            }
                        });

                        this.localReturnsList = allDetails.map(d => {
                            // FUENTE PRIMARIA de cant_return: detalles.cant_return (sincronizado desde servidor)
                            // Este campo SIEMPRE está disponible en IndexedDB, sin importar si devoluciones_locales tiene data.
                            // Esto espeja exactamente la lógica de getReturnedItemsProperty() en PHP.
                            const cantReturnBase = Number(d.cant_return || 0);

                            // Si existe un registro offline PENDIENTE (synced=0), usar su cantidad — es más reciente
                            const ret = returnsMap[d.id] || {};
                            const qtyDevuelta = (ret.synced === 0) ? Number(ret.quantity || 0) : cantReturnBase;

                            // qty_no_ent: ítems que fueron llevados pero NO se entregaron NI devolvieron
                            // Solo aplica para pedidos EN RECORRIDO (igual que PHP)
                            const remisionStatus = remisionesMap[d.remissionId]?.status || 'EN RECORRIDO';
                            let qtyNoEnt = 0;
                            if (!['ENTREGADO', 'DEVUELTO'].includes(remisionStatus)) {
                                qtyNoEnt = Math.max(0, (d.quantity || 0) - qtyDevuelta);
                            }

                            const totalNov = qtyDevuelta + qtyNoEnt;
                            // delivery_id desde remisionesMap — siempre disponible sin importar si ret tiene el campo
                            const deliveryId = remisionesMap[d.remissionId]?.delivery_id || ret.delivery_id || 0;

                            return {
                                ...ret,
                                remission_id: d.remissionId,
                                delivery_id: deliveryId,
                                detail_id: d.id,
                                consecutive: remisionesMap[d.remissionId]?.consecutive || d.remissionId,
                                item_name: d.name || 'Producto',
                                dev: qtyDevuelta,
                                no_ent: qtyNoEnt,
                                total: totalNov,
                                qty_llevada: d.quantity,
                                subtotal: totalNov * (d.value || 0)
                            };
                        });
                      // 4. Créditos
                      if(this.db.creditos_locales) {
                          const credits = await this.db.creditos_locales.toArray();
                          this.localCreditsList = credits.filter(c => remisionIds.includes(c.remission_id));
                      }
                },

                async loadLocalRemisiones() {
                    if (!this.db) return;
                    try {
                        let query = this.db.remisiones.toCollection();
                        if (this.currentDeliveryId) {
                            query = this.db.remisiones.where('delivery_id').equals(Number(this.currentDeliveryId));
                        }
                        this.localRemisionesList = await query.toArray();
                        console.log('📦 Remisiones locales cargadas:', this.localRemisionesList.length);
                    } catch(e) {
                        console.error('Error cargando remisiones locales:', e);
                    }
                },

                // GETTERS REACTIVOS (FILTROS)
                get filteredRemisiones() {
                    let list = this.localRemisionesList;

                    // Filtrar por cargue si hay uno seleccionado
                    if (this.currentDeliveryId) {
                        list = list.filter(r => Number(r.delivery_id) === Number(this.currentDeliveryId));
                    }

                    // Filtrar por búsqueda de cliente (funciona online Y offline)
                    const search = (this.localSearch || '').trim().toLowerCase();
                    if (search) {
                        list = list.filter(r => (r.customer_name || '').toLowerCase().includes(search));
                    }

                    // Filtrar por estado (funciona online Y offline)
                    const status = this.localStatus || 'Todos';
                    if (status && status !== 'Todos') {
                        list = list.filter(r => r.status === status);
                    }

                    return list;
                },
                get filteredCollections() {
                    if (!this.currentDeliveryId) return this.localCollectionsList;
                    return this.localCollectionsList.filter(c => Number(c.delivery_id) === Number(this.currentDeliveryId));
                },
                get filteredReturns() {
                    if (!this.currentDeliveryId) return this.localReturnsList;
                    return this.localReturnsList.filter(r => Number(r.delivery_id) === Number(this.currentDeliveryId));
                },

                async init() {
                    if (this.isInitialized) return;
                    
                    // 1. Esperar a las librerías
                    const libsReady = await this.waitForLibraries();
                    if (!libsReady) return;

                    // 2. Definir Base de Datos (Solo si no existe)
                    if (!this.db) {
                        this.initDatabase();
                    }

                    // 3. Estado Inicial
                    this.isOnline = navigator.onLine;
                    this.viewCollections = @json($showCollectionsTable);
                    this.viewReturns = @json($showReturnsTable);

                    try {
                        if (!this.db.isOpen()) await this.db.open();
                        console.log("✅ IndexedDB conectada. Modo Inicial:", this.isOnline ? 'ONLINE' : 'OFFLINE');
                        
                        // CARGA INICIAL: Separar fuente según conectividad
                        // ONLINE → datos vienen del servidor via 'hydrate-local-db' (Livewire los emite en mount())
                        //          NO cargar IndexedDB para evitar mostrar datos stale
                        // OFFLINE → cargar IndexedDB porque es la única fuente disponible
                        if (!this.isOnline) {
                            await this.loadLocalRemisiones();
                            await this.loadFinancialDetails();
                        }
                        // Cuando online: localRemisionesList queda vacío hasta que 'hydrate-local-db' lo llena con datos del servidor
                        
                    } catch (err) {
                        console.error("❌ Error inicialización DB:", err);
                    }

                    // 4. Listeners de Red
                    window.addEventListener('online', async () => {
                        this.isOnline = true;
                        console.log("🌐 Conexión restaurada - Sincronizando y recargando datos del servidor");

                        // Paso 1: Sincronizar datos offline pendientes (pagos, devoluciones, estados)
                        await this.syncBackOnline();

                        // Paso 2: Forzar recarga de datos frescos del servidor
                        // Esto reemplaza cualquier dato stale de IndexedDB con datos reales de MySQL
                        if (this.isOnline) {
                            try {
                                await @this.dispatchSync();
                                console.log("✅ Datos del servidor recargados tras reconexión");
                            } catch(e) {
                                console.warn("⚠️ No se pudo recargar del servidor:", e);
                            }
                        }
                    });

                    window.addEventListener('offline', async () => { 
                        this.isOnline = false;
                        console.log("⚠️ Sin conexión - Cambiando a fuente de datos local");
                        await this.loadLocalRemisiones();
                        await this.loadFinancialDetails();
                    });

                    // 5. Watcher para cambios de cargue (Reactividad Offline)
                    this.$watch('currentDeliveryId', async (val) => {
                        console.log('🔄 Cambio de cargue detectado (Alpine):', val);
                        
                        // Resetear vistas de tablas al cambiar cargue (Requerimiento de UI)
                        this.viewCollections = false;
                        this.viewReturns = false;

                        if (!this.isOnline) {
                            await this.loadLocalRemisiones();
                        }
                        await this.loadFinancialDetails();
                    });

                    // Escuchar eventos de Livewire para hidratación proactiva (NUEVO SSoT)
                    // Escuchar eventos de Livewire para hidratación proactiva (NUEVO SSoT)
                    Livewire.on('hydrate-local-db', async (data) => {
                        const actualData = Array.isArray(data) ? (data[0] || {}) : data;
                        console.log("🌊 Hidratación detectada:", actualData);
                    
                        // PASO 1 (INMEDIATO): Actualizar la lista de remisiones
                        if (actualData.remissions && actualData.remissions.length > 0) {
                            this.localRemisionesList = actualData.remissions.map(rem => ({
                                id: Number(rem.id),
                                delivery_id: Number(rem.delivery_id),
                                quoteId: Number(rem.quoteId),
                                status: String(rem.status),
                                consecutive: String(rem.consecutive),
                                customer_name: String(rem.customer_name || 'N/A'),
                                contact_name: String(rem.contact_name || 'N/A'),
                                route_name: String(rem.route_name || 'N/A'),
                                address: String(rem.address || 'Sin dirección'),
                                total_amount: Number(rem.total_amount || 0),
                                balance_amount: Number(rem.balance_amount || 0),
                                observations_return: String(rem.observations_return || '')
                            }));
                        }

                        // PASO 1.1 (NUEVO - INMEDIATO): Poblar tablas financieras si estamos online
                        // Esto evita que las tablas financieras se vean en blanco mientras IndexedDB se actualiza.
                        if (actualData.collections) {
                            this.localCollectionsList = actualData.collections.map(p => ({
                                id: Number(p.id),
                                remissionId: Number(p.remission_id || p.invoiceId), 
                                delivery_id: Number(p.delivery_id),
                                value: Number(p.value || 0), 
                                methods_summary: p.methods_summary || p.loading_methods || 'EFECTIVO',
                                customer_name: String(p.customer_name || 'N/A'), 
                                consecutive: String(p.consecutive || 'N/A'),
                                synced: 1
                            }));
                        }

                        if (actualData.returnedItems) {
                             this.localReturnsList = actualData.returnedItems.map(r => ({
                                remission_id: Number(r.remission_id), 
                                delivery_id: Number(r.delivery_id),
                                detail_id: Number(r.detail_id),
                                dev: Number(r.dev || 0), 
                                no_ent: Number(r.no_ent || 0), 
                                item_name: String(r.item_name),
                                consecutive: String(r.consecutive),
                                total: Number(r.total || 0),
                                subtotal: Number(r.subtotal || 0),
                                synced: 1
                             }));
                        }

                    
                        // PASO 2 (BACKGROUND): Guardar en IndexedDB para uso offline
                        await this.saveDataToIndexedDB(data);
                    
                        // PASO 3: Si no hay remisiones del servidor (modo offline), cargar desde IndexedDB
                        if (!actualData.remissions || actualData.remissions.length === 0) {
                            await this.loadLocalRemisiones();
                            if (this.viewReturns || this.viewCollections) await this.loadFinancialDetails();
                        }
                    });

                    Livewire.on('pedido-actualizado', async () => {
                        this.toastMsg = 'Pedido actualizado';
                        this.isError = false;
                        this.showToast = true;
                        // Forzar una re-sincronización tras actualización en servidor
                        setTimeout(() => this.syncOfflineData(), 500); 
                    });
                    Livewire.on('notificar-error', (data) => {
                        this.toastMsg = data.msg || data[0].msg;
                        this.isError = true;
                        this.showToast = true;
                        setTimeout(() => { this.showToast = false; }, 4000);
                    });

                    // Nuevo listener para abrir enlaces (WhatsApp, Reportes, etc.)
                    Livewire.on('open-link', (data) => {
                        const url = data.url || data[0].url;
                        console.log("🔗 Abriendo enlace:", url);
                        if (url) {
                            window.open(url, '_blank');
                        }
                    });

                    // Listener para impresión de tickets
                    Livewire.on('open-print-window', (data) => {
                         const printData = data[0] || data;
                         console.log("🖨️ Abriendo ventana de impresión:", printData.url);
                         if (printData.url) {
                             const printWin = window.open(printData.url, '_blank');
                             if (printWin) {
                                  // Opcional: enfocar ventana
                              }
                         }
                    });

                    // Listener para actualizar UI tras pago/devolución offline (sin recarga completa)
                    window.addEventListener('update-local-order', async (event) => {
                        const { id, status, balance, total } = event.detail;
                        const numId = Number(id);
                        const idx = this.localRemisionesList.findIndex(r => r.id === numId);
                        if (idx !== -1) {
                            this.localRemisionesList[idx] = {
                                ...this.localRemisionesList[idx],
                                status: status,
                                balance_amount: (balance !== undefined && balance !== null) ? balance : this.localRemisionesList[idx].balance_amount,
                                total_amount: (total !== undefined && total !== null) ? total : this.localRemisionesList[idx].total_amount
                            };
                            // Forzar reactividad Alpine reemplazando la referencia del array
                            this.localRemisionesList = [...this.localRemisionesList];
                        }
                        // Actualizar tablas financieras del sidebar inmediatamente
                        await this.loadFinancialDetails();
                    });

                    this.isInitialized = true;

                    // GUARD ANTI-RACE-CONDITION:
                    // mount() de PHP dispara 'hydrate-local-db' antes de que Alpine registre el listener.
                    // Si el evento se pierde, localRemisionesList queda vacío aunque el servidor tenga datos.
                    // Solución: después de 400ms, si online y lista vacía, re-solicitar al servidor.
                    if (this.isOnline) {
                        setTimeout(async () => {
                            if (this.localRemisionesList.length === 0) {
                                console.log('⚡ Re-solicitando datos al servidor (hydrate-local-db perdido en init)');
                                try { await @this.dispatchSync(); } catch(e) {}
                            }
                        }, 400);
                    }
                },

                async handleViewOrder(orderId) {
                    if (this.isOnline) {
                        this.$wire.viewOrder(orderId);
                        return;
                    }

                    // Lógica Offline
                    try {
                        const rem = await this.db.remisiones.get(Number(orderId));
                    if (rem) {
                        const details = await this.db.detalles.where('remissionId').equals(Number(orderId)).toArray();
                        
                        // Poblar el estado serializado manualmente (mimetizando el backend)
                        this.selectedOrderData = {
                            id: rem.id,
                            consecutive: rem.consecutive || rem.id,
                            customer_name: rem.customer_name,
                            contact_name: rem.contact_name,
                            branch_name: rem.route_name,      // Ahora viene procesado desde PHP
                            delivery_id: rem.delivery_id,
                            address: rem.address,
                            status: rem.status,
                            total_amount: Number(rem.total_amount || 0),     // Necesario para VALOR FINAL NETO en modal
                            balance_amount: Number(rem.balance_amount || 0), // Necesario para cálculo de pago
                            details: details
                        };
                        this.order = this.selectedOrderData;
                        
                        this.currentItemIndex = 0;
                        
                        // Inicializar cantidades de devolución desde IndexedDB o local
                        for (const d of details) {
                            this.returnQuantities[d.id] = d.cant_return || 0;
                            this.returnObservations[d.id] = d.observations_return || '';
                        }

                        this.open = true;
                    }
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
                                remission_id: Number(this.selectedOrderId),
                                quantity: Number(qty),
                                observation: String(observation),
                                synced: 0
                            });
                        } else {
                            await this.db.devoluciones_locales.put({
                                remission_id: Number(this.selectedOrderId),
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
                            await this.db.detalles.put(detail);
                        }

                        Swal.fire({
                            title: 'Guardado Local',
                            text: 'La devolución se guardó en el dispositivo.',
                            icon: 'success',
                            toast: true,
                            position: 'top-end',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } catch (e) {
                        console.error(e);
                        Swal.fire('Error', 'No se pudo guardar localmente', 'error');
                    } finally {
                        this.syncing = false;
                    }
                },

                async handleSaveProductNovelty(id) {
                    console.log("💾 handleSaveProductNovelty llamado para ID:", id);
                    if (this.isOnline) {
                        this.saving = true;
                        this.$wire.saveProductNovelty(id).then(() => {
                            this.saving = false;
                            this.open = false;
                        });
                        return;
                    }

                    // Lógica Offline para guardado masivo de devoluciones
                    try {
                        this.syncing = true;
                        let hasReturns = false;
                        for (const detailId in this.returnQuantities) {
                            if (this.returnQuantities[detailId] > 0) {
                                hasReturns = true;
                                break;
                            }
                        }

                        if (hasReturns && !this.orderNovelty.trim()) {
                            Swal.fire('Error', 'Para registrar una devolución de unidades, debe escribir la razón en las observaciones.', 'error');
                            return;
                        }

                        // Obtener delivery_id de la remisión para que filteredReturns pueda filtrar por cargue
                        const remisionForReturn = await this.db.remisiones.get(Number(id));
                        const deliveryIdForReturn = Number(remisionForReturn?.delivery_id || 0);

                        // Guardar cada detalle que tenga cantidad > 0
                        for (const detailId in this.returnQuantities) {
                            const qty = this.returnQuantities[detailId];
                            const observation = this.orderNovelty;

                            // Actualizar IndexedDB
                            const existing = await this.db.devoluciones_locales.where('detail_id').equals(Number(detailId)).first();
                                if (existing) {
                                    await this.db.devoluciones_locales.update(existing.id, {
                                        remission_id: Number(this.selectedOrderId),
                                        quantity: Number(qty),
                                        observation: String(observation),
                                        synced: 0,
                                        delivery_id: deliveryIdForReturn
                                    });
                                } else {
                                    await this.db.devoluciones_locales.put({
                                        remission_id: Number(this.selectedOrderId),
                                        detail_id: Number(detailId),
                                        quantity: Number(qty),
                                        observation: String(observation),
                                        synced: 0,
                                        delivery_id: deliveryIdForReturn
                                    });
                                }

                            const detail = await this.db.detalles.get(Number(detailId));
                            if (detail) {
                                detail.cant_return = qty;
                                await this.db.detalles.put(detail);
                            }
                        }

                        // Recalcular totales de la remisión en IndexedDB con las devoluciones aplicadas
                        // Esto asegura que la tarjeta en la lista muestre el valor correcto inmediatamente
                        try {
                            const updatedDetails = await this.db.detalles.where('remissionId').equals(Number(id)).toArray();
                            let newTotal = 0;
                            for (const d of updatedDetails) {
                                const eff = (d.quantity || 0) - (d.cant_return || 0);
                                if (eff > 0) {
                                    newTotal += eff * (d.value || 0) * (1 + ((d.tax || 0) / 100));
                                }
                            }
                            newTotal = Math.round(newTotal);

                            const remToUpdate = await this.db.remisiones.get(Number(id));
                            if (remToUpdate) {
                                remToUpdate.total_amount = newTotal;
                                remToUpdate.balance_amount = newTotal;
                                await this.db.remisiones.put(remToUpdate);
                            }

                            // Notificar a la lista (tarjeta) para reactividad visual inmediata
                            window.dispatchEvent(new CustomEvent('update-local-order', {
                                detail: {
                                    id: Number(id),
                                    status: remToUpdate?.status || 'EN RECORRIDO',
                                    balance: newTotal,
                                    total: newTotal
                                }
                            }));

                            // Si el pedido está abierto en el panel, actualizar selectedOrderData también
                            if (Number(this.selectedOrderId) === Number(id) && this.selectedOrderData) {
                                this.selectedOrderData = {
                                    ...this.selectedOrderData,
                                    total_amount: newTotal,
                                    balance_amount: newTotal
                                };
                            }
                        } catch (recalcErr) {
                            console.warn('⚠️ No se pudo recalcular total tras devolución:', recalcErr);
                        }

                        // Recargar tabla de devoluciones para reflejar los cambios inmediatamente
                        await this.loadFinancialDetails();

                        Swal.fire({
                            title: 'Guardado Local',
                            text: 'Las devoluciones fueron guardadas en el dispositivo.',
                            icon: 'success',
                            toast: true,
                            position: 'top-end',
                            timer: 2000,
                            showConfirmButton: false
                        });

                        this.open = false;

                    } catch (e) {
                         console.error("❌ Error offline saveProductNovelty:", e);
                         Swal.fire('Error', 'No se pudo guardar localmente: ' + e.message, 'error');
                    } finally {
                        this.syncing = false;
                        this.saving = false;
                    }
                },

                handleShareWhatsApp(id) {
                    console.log("📱 handleShareWhatsApp llamado para ID:", id);
                    if (this.isOnline) {
                        console.log("🌐 Llamando a Livewire shareWhatsApp...");
                        this.$wire.shareWhatsApp(id);
                    } else {
                        Swal.fire('Info', 'El envío por WhatsApp requiere conexión a internet para generar el enlace del PDF.', 'info');
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

                        // FUENTE PRIMARIA: usar balance_amount/total_amount ya calculados y almacenados en IndexedDB
                        // Son calculados por PHP (processRemissionTotals) y representan el valor real a cobrar
                        let paymentTotal = Number(rem.balance_amount || rem.total_amount || 0);

                        // FALLBACK: si por alguna razón están en 0, recalcular desde los detalles
                        if (paymentTotal === 0 && details.length > 0) {
                            let calculated = 0;
                            for(let d of details) {
                                const effectiveQty = d.quantity - (d.cant_return || 0);
                                if (effectiveQty > 0) {
                                    const lineSubtotal = effectiveQty * d.value;
                                    const lineTax = lineSubtotal * ((d.tax || 0) / 100);
                                    calculated += lineSubtotal + lineTax;
                                }
                            }
                            if (calculated > 0) paymentTotal = calculated;
                        }

                        this.paymentTotal = Math.round(paymentTotal);
                        this.paymentPaid = 0;
                        this.paymentChange = 0;
                        this.paymentChangeInput = 0; // Nuevo campo para input del cliente
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
                        this.paymentChangeInput = this.paymentTotal; // Inicializar input con el total
                        this.calculatePayment();

                         // Guardar referencia para el modal
                         this.selectedOrderId = rem.id; // Variable directa robusta
                         this.selectedOrderData = { 
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
                          const remId = Number(this.selectedOrderId || this.selectedOrderData?.id);

                          if (!remId) {
                              throw new Error("ID de remisión no válido para el pago.");
                          }

                          // Asegurar apertura
                          if (!this.db.isOpen()) await this.db.open();

                            // Obtener remisión actual para actualizar con seguridad
                          const currentRem = await this.db.remisiones.get(remId);

                         const paymentData = {
                                remissionId: remId,
                                total: Number(this.paymentTotal),
                                methods: JSON.parse(JSON.stringify(this.paymentMethods)),
                                observation: (this.paymentObs || '') + (this.orderNovelty ? ' | Novedad: ' + this.orderNovelty : ''),
                                timestamp: new Date().toISOString(),
                                synced: 0,
                                customer_name: this.selectedOrderData?.customer_name,
                                consecutive: this.selectedOrderData?.consecutive,
                                delivery_id: Number(this.selectedOrderData?.delivery_id || currentRem?.delivery_id || 0),
                                value: Number(this.paymentTotal) // Duplicado para compatibilidad con lista
                            };
                          if (!currentRem) throw new Error("Pedido no encontrado localmente.");

                          currentRem.status = 'ENTREGADO';
                          currentRem.balance_amount = 0;

                          // Ejecutar escrituras
                          await this.db.pagos_locales.put(paymentData);
                          await this.db.remisiones.put(currentRem);
                          
                          this.paymentModalOpen = false;
                          
                          Swal.fire({
                             title: 'Pago Guardado Localmente',
                             text: 'El estado se ha actualizado a ENTREGADO.',
                             icon: 'success',
                             toast: true,
                             position: 'top-end',
                             timer: 3000,
                             showConfirmButton: false
                          });
                          
                          // Disparar evento GLOBAL para actualizar la lista
                          window.dispatchEvent(new CustomEvent('update-local-order', { 
                             detail: { 
                                 id: remId, 
                                 status: 'ENTREGADO',
                                 balance: 0
                             }
                          }));

                          // Intentar sincronizar inmediatamente si hay red
                          if (this.isOnline) this.syncBackOnline();
                         
                     } catch(e) {
                         console.error("Error al guardar pago:", e);
                         // Convertir error a texto legible si es objeto
                         const errorMsg = e.message || (e.target && e.target.error ? e.target.error.message : JSON.stringify(e));
                         Swal.fire('Error', 'No se pudo guardar el pago localmente: ' + errorMsg, 'error');
                     } finally {
                         this.syncing = false;
                     }
                },

                async refreshLocalData() {
                    console.log("🔄 Refrescando datos locales...");
                    if (this.selectedOrderData && this.selectedOrderData.id) {
                        try {
                            const rem = await this.db.remisiones.get(this.selectedOrderData.id);
                            if (rem) {
                                // Actualizar encabezado del pedido
                                this.order.status = rem.status; // Ejemplo de actualización
                            }
                        } catch (e) {
                            console.error("Error refrescando datos locales:", e);
                        }
                    }
                },

                async syncBackOnline() {
                    // Evitar múltiples ejecuciones simultáneas
                    if (this.syncing) return;

                    this.syncing = true;
                    let syncedCount = 0;

                    try {
                        // 1. Sincronizar Pagos Pendientes
                        const pendingPayments = await this.db.pagos_locales.where('synced').equals(0).toArray();
                        if (pendingPayments.length > 0) {
                            this.toastMsg = 'Sincronizando ' + pendingPayments.length + ' pagos...';
                            this.showToast = true;
                            
                            // Enviar al backend
                            const result = await @this.syncPendingPayments(pendingPayments);
                            
                            if (result) {
                                // Borrar de local
                                const ids = pendingPayments.map(p => p.id);
                                await this.db.pagos_locales.bulkDelete(ids);
                                syncedCount += pendingPayments.length;
                            }
                        }

                        // 2. Sincronizar Devoluciones Pendientes
                        const pendingReturns = await this.db.devoluciones_locales.where('synced').equals(0).toArray();
                        if (pendingReturns.length > 0) {
                            this.toastMsg = 'Sincronizando ' + pendingReturns.length + ' devoluciones...';
                            this.showToast = true;

                            const result = await @this.syncPendingReturns(pendingReturns);

                            if (result) {
                                const ids = pendingReturns.map(r => r.id);
                                await this.db.devoluciones_locales.bulkDelete(ids);
                                syncedCount += pendingReturns.length;
                            }
                        }
                        
                         // 3. Sincronizar Cambios de Estado (Full Returns)
                        const pendingStatus = await this.db.pending_status_updates.where('synced').equals(0).toArray();
                        if (pendingStatus.length > 0) {
                            this.toastMsg = 'Sincronizando ' + pendingStatus.length + ' cambios de estado...';
                            this.showToast = true;

                            // Necesitamos un método en PHP para esto. O reutilizar syncPendingReturns?
                            // Crearemos syncPendingStatusUpdates en PHP.
                            // Si no existe, usamos un loop llamando a algo existente o creamos un método mágico.
                            const result = await @this.syncStatusUpdates(pendingStatus);

                            if (result) {
                                const ids = pendingStatus.map(r => r.id);
                                await this.db.pending_status_updates.bulkDelete(ids);
                                syncedCount += pendingStatus.length;
                            }
                        }

                        // 4. Sincronizar Novedades de Pedido (Si existen registros sueltos)
                        // Por ahora se sincronizan dentro de Pagos y Full Returns.

                        if (syncedCount > 0) {
                            this.toastMsg = 'Sincronización completada (' + syncedCount + ' registros)';
                            this.isError = false;
                        } else {
                            // console.log("Nada pendiente por sincronizar.");
                        }

                    } catch (error) {
                        console.error("Error auto-sincronizando:", error);
                        this.toastMsg = 'Error al sincronizar datos.';
                        this.isError = true;
                    } finally {
                        this.showToast = true;
                        setTimeout(() => { this.showToast = false; }, 3000);
                        this.syncing = false;
                    }
                },
                
                openFullReturnModal(id) {
                    if (this.isOnline) {
                        this.$wire.openFullReturnModal(id);
                        return;
                    }
                    // Lógica Offline
                    this.openOfflineReturnModal(id);
                },

                async openOfflineReturnModal(id) {
                    try {
                        const rem = await this.db.remisiones.get(Number(id));
                        if (!rem) throw new Error("Pedido no encontrado localmente.");

                        this.selectedOrderData = {
                            id: rem.id,
                            consecutive: rem.consecutive || rem.id,
                            customer_name: rem.customer_name
                        };
                        this.selectedOrderId = rem.id; // Asegurar ID en variable directa
                        this.fullReturnObservation = '';
                        this.fullReturnModalOpen = true;

                    } catch (e) {
                        console.error(e);
                        Swal.fire('Error Offline', e.message, 'error');
                    }
                },

                async saveFullReturnOffline() {
                   if (!this.fullReturnObservation.trim()) {
                       Swal.fire('Atención', 'La justificación es obligatoria.', 'warning');
                       return;
                   }

                   try {
                       this.syncing = true;
                       // Usar selectedOrderId directa que es más confiable en Alpine
                       const remId = Number(this.selectedOrderId || this.selectedOrderData?.id);
                       
                       if (!remId) {
                           console.error("ID no encontrado. selectedOrderId:", this.selectedOrderId, "selectedOrderData:", this.selectedOrderData);
                           throw new Error("ID de remisión no válido o perdido.");
                       }

                       // Asegurar apertura antes de operar
                       if (!this.db.isOpen()) await this.db.open();

                       // PASO 1: Obtener datos fuera de cualquier transacción implícita
                       const details = await this.db.detalles.where('remissionId').equals(remId).toArray();
                       if (!details || details.length === 0) {
                           throw new Error("No se encontraron detalles para este pedido localmente.");
                       }

                       // Obtener el objeto de remisión actual para no perder otros campos al usar put
                       const currentRemission = await this.db.remisiones.get(remId);
                       if (!currentRemission) throw new Error("Pedido no encontrado en IndexedDB.");

                       // PASO 2: Preparar datos para guardado masivo (Mucho más estable que un loop de awaits)
                       
                       // 1. Modificar cabecera
                       currentRemission.status = 'DEVUELTO';
                       currentRemission.observations_return = this.fullReturnObservation;

                       // 2. Preparar detalles actualizados
                       const updatedDetails = details.map(d => ({
                           ...d,
                           cant_return: d.quantity,
                           observations_return: 'Devolución Total: ' + this.fullReturnObservation
                       }));

                       // 3. Preparar registros de devolución para sync
                       const newReturns = details.map(d => ({
                            remission_id: Number(remId),
                            detail_id: d.id,
                            quantity: Number(d.quantity),
                            observation: 'Devolución Total: ' + this.fullReturnObservation,
                            synced: 0
                        }));

                       // PASO 3: Ejecutar guardados masivos secuencialmente
                       // .put() en Dexie es más rápido y estable que .update() para cambios múltiples
                       await this.db.remisiones.put(currentRemission);
                       await this.db.detalles.bulkPut(updatedDetails);
                       await this.db.devoluciones_locales.bulkPut(newReturns);
                       
                       // Sincronización global
                       await this.db.pending_status_updates.put({
                           remission_id: remId,
                           status: 'DEVUELTO',
                           observation: this.fullReturnObservation,
                           synced: 0
                       });

                       this.fullReturnModalOpen = false;
                       
                       Swal.fire({
                            title: 'Devolución Guardada Localmente',
                            text: 'El estado se ha actualizado a DEVUELTO.',
                            icon: 'success',
                            toast: true,
                            position: 'top-end',
                            timer: 3000,
                            showConfirmButton: false
                       });

                       window.dispatchEvent(new CustomEvent('update-local-order', { 
                            detail: { 
                                id: remId, 
                                status: 'DEVUELTO',
                                balance: 0 // Devolución total = saldo 0
                            }
                       }));

                       // Intentar sincronizar inmediatamente si hay red
                       if (this.isOnline) this.syncBackOnline();

                   } catch (e) {
                       console.error("Error Devolución Full:", e);
                       Swal.fire('Error', 'No se pudo guardar: ' + (e.message || 'Error desconocido'), 'error');
                   } finally {
                       this.syncing = false;
                   }
                },

                // Métodos de soporte refactorizados
                async waitForLibraries() {
                    return new Promise((resolve) => {
                        if (typeof Dexie !== 'undefined' && typeof Swal !== 'undefined') return resolve(true);
                        let attempts = 0;
                        const interval = setInterval(() => {
                            if (++attempts >= 30) { clearInterval(interval); resolve(false); }
                            if (typeof Dexie !== 'undefined' && typeof Swal !== 'undefined') { clearInterval(interval); resolve(true); }
                        }, 100);
                    });
                },

                initDatabase() {
                    if (this.db) return;
                    this.db = new Dexie("deliveries_db");
                    this.db.version(7).stores({
                        cargues: 'id, deliveryman_id, created_at',
                        remisiones: 'id, delivery_id, quoteId, status, customer_name, consecutive, observations_return',
                        detalles: 'id, remissionId, itemId',
                        pagos_locales: '++id, remissionId, value, methodPaymentId, synced, customer_name, consecutive, observation, delivery_id',
                        devoluciones_locales: '++id, detail_id, quantity, quantity_no_ent, observation, synced, remission_id, delivery_id',
                        pending_status_updates: '++id, remission_id, status, observation, synced',
                        config: 'key'
                    });
                },

                async syncOfflineData() {
                    // Guard contra ejecución concurrente (puede ser llamado desde listener pedido-actualizado)
                    if (this.syncing) return;

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
                        await this.saveDataToIndexedDB(data);
                        
                        this.toastMsg = "SINCRONIZACIÓN EXITOSA";
                        this.isError = false;
                        this.showToast = true;
                        
                        await this.loadLocalRemisiones();
                    } catch (err) {
                        console.error("Error en sincronización manual:", err);
                        this.toastMsg = "ERROR AL SINCRONIZAR";
                        this.isError = true;
                        this.showToast = true;
                    } finally {
                        this.syncing = false;
                    }
                },

                async saveDataToIndexedDB(data) {
                    if (!data || this.isSavingToDB) return;

                    const actualData = Array.isArray(data) ? (data[0] || {}) : data;
                    if (!actualData.remissions && !actualData.deliveries) return;

                    this.isSavingToDB = true;

                    // --- PROTECCIÓN: Capturar estado local pendiente antes de sobreescribir ---
                    // Evita que datos del servidor sobreescriban cambios offline no sincronizados
                    let existingLocalRemisiones = {};
                    let allPendingIds = new Set();
                    try {
                        if (!this.db.isOpen()) await this.db.open();
                        const pendingPaymentIds = (await this.db.pagos_locales.where('synced').equals(0).toArray())
                            .map(p => Number(p.remissionId));
                        const pendingStatusIds = (await this.db.pending_status_updates.where('synced').equals(0).toArray())
                            .map(s => Number(s.remission_id));
                        allPendingIds = new Set([...pendingPaymentIds, ...pendingStatusIds]);
                        if (allPendingIds.size > 0) {
                            const existing = await this.db.remisiones.bulkGet([...allPendingIds]);
                            existing.forEach(r => { if (r) existingLocalRemisiones[r.id] = r; });
                            console.log(`🔒 Protegiendo ${allPendingIds.size} remisiones con cambios offline pendientes`);
                        }
                    } catch (pendingErr) {
                        console.warn('⚠️ No se pudo leer estado pendiente local (no crítico):', pendingErr);
                        allPendingIds = new Set();
                        existingLocalRemisiones = {};
                    }
                    // --- FIN PROTECCIÓN ---

                    try {
                        // 1. Asegurar base de datos abierta fuera de la transacción
                        if (!this.db.isOpen()) await this.db.open();

                        // 2. Pre-procesar TODO fuera de la transacción (objetos planos)
                        // Esto evita que el acceso a Proxies de Alpine rompa la transacción de Dexie
                        const remisionesData = [];
                        const detallesData = [];
                        const pagosData = [];
                        const devolucionesData = [];

                        if (actualData.remissions) {
                            actualData.remissions.forEach(rem => {
                                const remId = Number(rem.id);
                                const localState = existingLocalRemisiones[remId];
                                const hasPending = allPendingIds.has(remId);

                                remisionesData.push({
                                    id: remId,
                                    delivery_id: Number(rem.delivery_id),
                                    quoteId: Number(rem.quoteId),
                                    // Preservar status/balance local si hay cambios pendientes sin sincronizar
                                    status: (hasPending && localState) ? String(localState.status) : String(rem.status),
                                    consecutive: String(rem.consecutive),
                                    customer_name: String(rem.customer_name || 'N/A'),
                                    contact_name: String(rem.contact_name || 'N/A'),
                                    route_name: String(rem.route_name || 'N/A'),
                                    address: String(rem.address || 'Sin dirección'),
                                    total_amount: Number(rem.total_amount || 0),
                                    balance_amount: (hasPending && localState) ? Number(localState.balance_amount) : Number(rem.balance_amount || 0),
                                    observations_return: String(rem.observations_return || '')
                                });
                                
                                if (rem.details) {
                                    rem.details.forEach(d => {
                                        // Guardar detalles
                                        detallesData.push({
                                            id: Number(d.id), 
                                            remissionId: Number(d.remissionId), 
                                            itemId: Number(d.itemId),
                                            name: String(d.name), 
                                            quantity: Number(d.quantity || 0), 
                                            cant_return: Number(d.cant_return || 0),
                                            value: Number(d.value || 0), 
                                            tax: Number(d.tax || 0)
                                        });
                                        
                                    });
                                }
                            });
                        }

                        if (actualData.collections) {
                            actualData.collections.forEach(p => {
                                pagosData.push({
                                    remissionId: Number(p.remission_id || p.invoiceId), 
                                    value: Number(p.value || 0), 
                                    methodPaymentId: p.methodPaymentId, 
                                    loading_methods: p.methods_summary,
                                    synced: 1, 
                                    customer_name: String(p.customer_name || 'N/A'), 
                                    observation: String(p.observation || ''), 
                                    consecutive: String(p.consecutive || 'N/A'),
                                    delivery_id: Number(p.delivery_id || 0),
                                    timestamp: p.created_at || new Date().toISOString()
                                });
                            });
                        }

                        if (actualData.returnedItems) {
                            actualData.returnedItems.forEach(r => {
                                devolucionesData.push({
                                    remission_id: Number(r.remission_id), 
                                    detail_id: Number(r.detail_id),
                                    quantity: Number(r.dev || 0), 
                                    quantity_no_ent: Number(r.no_ent || 0), 
                                    observation: String(r.observation || ''),
                                    synced: 1, 
                                    consecutive: String(r.consecutive), 
                                    item_name: String(r.item_name), 
                                    delivery_id: Number(r.delivery_id || 0),
                                    subtotal: Number(r.subtotal || 0)
                                });
                            });
                        }


                        // 3. Transacción Dexie (Solo operaciones de DB)
                        const tableNames = ['cargues', 'remisiones', 'detalles', 'pagos_locales', 'devoluciones_locales'];
                        await this.db.transaction('rw', tableNames, async () => {
                            // Limpiar y Guardar Cargues
                            if (actualData.deliveries) {
                                await this.db.cargues.clear();
                                await this.db.cargues.bulkPut(JSON.parse(JSON.stringify(actualData.deliveries)));
                            }
                            
                            // Limpiar y Guardar Remisiones/Detalles
                            await this.db.remisiones.clear();
                            await this.db.detalles.clear();
                            if (remisionesData.length > 0) await this.db.remisiones.bulkPut(remisionesData);
                            if (detallesData.length > 0) await this.db.detalles.bulkPut(detallesData);
                            
                            // Limpiar previos sincronizados e insertar nuevos (Pagos y Devoluciones)
                            await this.db.pagos_locales.where('synced').equals(1).delete();
                            await this.db.devoluciones_locales.where('synced').equals(1).delete();
                            if (pagosData.length > 0) await this.db.pagos_locales.bulkPut(pagosData);
                            if (devolucionesData.length > 0) await this.db.devoluciones_locales.bulkPut(devolucionesData);
                            
                        });

                        console.log("✅ IndexedDB sincronizada con éxito.");

                    } catch (e) {
                        // Ignorar errores de transacción interrumpida pacíficamente
                        if (e.name === 'InvalidStateError' || e.name === 'TransactionInactiveError') {
                            console.warn("⚠️ Transacción de IndexedDB interrumpida (posible colisión manejada).");
                        } else {
                            console.error("❌ Error en saveDataToIndexedDB:", e);
                            if (this.syncing) {
                                Swal.fire({
                                    title: 'Error de Datos',
                                    text: 'No se pudieron guardar los datos locales: ' + e.message,
                                    icon: 'error',
                                    toast: true,
                                    position: 'top-end',
                                    timer: 4000,
                                    showConfirmButton: false
                                });
                            }
                        }
                    } finally {
                        this.isSavingToDB = false;
                        this.syncing = false;
                    }
                }
            }));
        }
    }

    // Ejecutar inmediatamente si Alpine ya está cargado
    if (window.Alpine) {
        initDeliveriesOffline();
    } else {
        document.addEventListener('alpine:init', initDeliveriesOffline);
    }
</script>
</div>
