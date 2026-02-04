
<div class="min-h-screen bg-gray-50 dark:bg-gray-900 " x-data="quoterListOffline">
    <div class="max-w-md mx-auto">
        <!-- Header -->
        <div class="flex items-center justify-between mb-6">
            
        </div>

        <!-- Search Input and Add Button - Sticky -->
        <div class="sticky top-0  bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shadow-sm">
            <div class="px-4 py-4">
                <h1 class="text-2xl font-bold text-gray-800 dark:text-gray-200 p-2">Cotizaciones</h1>
                <div class="flex gap-3 mb-4">
                    <input
                        type="text"
                        wire:model.live="search"
                        placeholder="Buscar cotización"
                        class="flex-1 p-3 border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-500 dark:placeholder-gray-400 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:ring-blue-400 dark:focus:border-blue-400"
                    >
                    <button
                        @click="startNewQuote"
                        class="bg-green-500 hover:bg-green-600 text-white px-4 py-3 rounded-lg shadow-sm flex items-center justify-center min-w-[52px] transition-all duration-200"
                        title="Nueva Cotización"
                    >
                        <!-- Ícono normal -->
                        <div>
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                            </svg>
                        </div>
                    </button>
                </div>

                <!-- Cotizaciones Title -->
                <div class="flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-800 dark:text-gray-200">Cotizaciones</h2>
                    <span class="text-sm text-gray-500 dark:text-gray-400">Desliza para ver opciones</span>
                </div>
            </div>
        </div>

        <!-- Content Area -->
        <div class="px-4 py-4">
            <!-- Success Message -->
            @if (session()->has('message'))
                <div class="bg-green-100 dark:bg-green-800 border border-green-400 dark:border-green-600 text-green-700 dark:text-green-200 px-4 py-3 rounded mb-4">
                    {{ session('message') }}
                </div>
            @endif

            <!-- Quotes List -->
            <div class="space-y-4">
            <!-- Offline Quotes List (Alpine) -->
            <template x-if="offlineQuotes.length > 0">
                <div class="space-y-4 mb-4">
                    <div class="flex items-center gap-2 px-1">
                        <span class="w-2 h-2 rounded-full bg-orange-500 animate-pulse"></span>
                        <h3 class="text-xs font-bold text-gray-500 uppercase tracking-wider">Pendientes de Sincronización</h3>
                    </div>

                    <template x-for="quote in offlineQuotes" :key="quote.uuid">
                        <div class="bg-orange-50 dark:bg-orange-900/10 rounded-lg shadow-sm border border-orange-200 dark:border-orange-800/50">
                            <!-- Quote Header -->
                            <div class="bg-orange-600 text-white p-3 rounded-t-lg">
                                <div class="flex justify-between items-center">
                                    <div>
                                        <span class="font-semibold">Cotización Offline</span>
                                        <br><span class="text-xs text-orange-200" x-text="quote.uuid.substring(0,8) + '...'"></span>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-sm" x-text="new Date(quote.fecha).toLocaleDateString()"></span>
                                        <br><span class="text-xs text-orange-200" x-text="new Date(quote.fecha).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})"></span>
                                    </div>
                                </div>
                            </div>

                            <!-- Quote Content -->
                            <div class="p-4">
                                <div class="mb-3">
                                    <template x-if="quote.customer">
                                        <div>
                                            <p class="text-base font-semibold text-gray-800 dark:text-gray-200" x-text="quote.customer.businessName || quote.customer.firstName"></p>
                                            <p class="text-sm text-gray-600 dark:text-gray-400" x-text="quote.customer.identification"></p>
                                        </div>
                                    </template>
                                    <template x-if="!quote.customer">
                                        <p class="text-base text-gray-400">Sin cliente asignado</p>
                                    </template>
                                </div>
                                
                                <div class="mb-3">
                                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Total Estimado</span>
                                    <p class="text-lg font-bold text-gray-800 dark:text-gray-200" x-text="'$' + new Intl.NumberFormat('es-CO').format(quote.total)"></p>
                                    <p class="text-xs text-orange-600 dark:text-orange-400 mt-1">⚠️ Guardado en celular</p>
                                </div>

                                <!-- Actions -->
                                <div class="flex items-center gap-2">
                                    <button
                                        @click="editOfflineQuote(quote)"
                                        class="flex-1 bg-orange-500 hover:bg-orange-600 text-white px-3 py-2 rounded-lg text-sm font-medium flex items-center justify-center gap-2 transition-all duration-200">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
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
                <div class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700">
                    <!-- Quote Header -->
                    <div class="bg-gray-800 dark:bg-gray-700 text-white p-3 rounded-t-lg">
                        <div class="flex justify-between items-center">
                            <div>
                                <span class="font-semibold">Cotización #{{ $quote->consecutive }}</span>
                                @if($quote->customer)
                                    <br><span class="text-sm text-gray-300">{{ $quote->customer->short_name }}</span>
                                @endif
                            </div>
                            <div class="text-right">
                                <span class="text-sm">{{ $quote->created_at->format('d/m/Y') }}</span>
                                <br><span class="text-xs text-gray-300">{{ $quote->created_at->format('H:i') }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Quote Content -->
                    <div class="p-4">
                        <!-- Cliente Information -->
                        <div class="mb-3">
                            <div class="flex items-center justify-between mb-2">
                                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{{ $quote->customer_name }}
                                @if($quote->customer->billingEmail)
                                        <br><small class="text-gray-500">{{ $quote->customer->billingEmail }}</small>
                                    @endif
                                </p>
                                
                                <span class="px-2 py-1 text-xs font-semibold rounded-full
                                    @if($quote->typeQuote === 'POS') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                    @else bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200 @endif">
                                    {{ $quote->typeQuote }}
                                    
                                </span>
                            </div>

                            @if($quote->customer)
                                <p class="text-base font-semibold text-gray-800 dark:text-gray-200">
                                    {{ $quote->customer->full_name }}
                                </p>
                                @if($quote->customer->email)
                                    <p class="text-sm text-gray-600 dark:text-gray-400">{{ $quote->customer->email }}</p>
                                @endif
                                @if($quote->customer->business_phone)
                                    <p class="text-sm text-gray-600 dark:text-gray-400">📞 {{ $quote->customer->business_phone }}</p>
                                @endif
                            @else
                                <p class="text-base text-gray-400">Sin cliente asignado</p>
                            @endif
                        </div>

                        <!-- Status and Warehouse -->
                        <div class="mb-3 space-y-2">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Estado</span>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full
                                    @if($quote->status === 'REGISTRADO') bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200
                                    @elseif($quote->status === 'ANULADO') bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200
                                    @elseif($quote->status === 'FACTURADO') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200
                                    @else bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200 @endif">
                                    {{ $quote->status }}
                                </span>
                            </div>

                            @if($quote->warehouse)
                                <div class="mb-2">
                                    <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Sucursal</span>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">
                                        🏢 {{ $quote->warehouse->name }}
                                        @if($quote->warehouse->address)
                                            <br>📍 {{ $quote->warehouse->address }}
                                        @endif
                                    </p>
                                </div>
                            @endif

                            <div>
                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Vendedor</span>
                                <p class="text-sm text-gray-600 dark:text-gray-400">
                                    👤 {{ $quote->user->name ?? 'N/A' }}
                                </p>
                            </div>
                        </div>

                        <!-- Observations if any -->
                        @if($quote->observations)
                            <div class="mb-3">
                                <span class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Observaciones</span>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $quote->observations }}</p>
                            </div>
                        @endif

                        <!-- Actions - Botones de acción para cada cotización -->
                        <div class="flex items-center gap-2">
                            <!-- Botón Ir al Carrito -->
                            @if($quote->status != 'REMISIÓN')
                            <button
                                wire:click="irAlCarrito({{ $quote->id }})"
                                wire:loading.attr="disabled"
                                wire:target="irAlCarrito({{ $quote->id }})"
                                class="flex-1 bg-blue-500 hover:bg-blue-600 disabled:bg-blue-300 disabled:cursor-not-allowed text-white px-3 py-2 rounded-lg text-sm font-medium flex items-center justify-center gap-2 transition-all duration-200">

                                <!-- Spinner de loading (se muestra cuando está cargando) -->
                                <div wire:loading wire:target="irAlCarrito({{ $quote->id }})" class="flex items-center gap-2">
                                    <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span>Abriendo...</span>
                                </div>

                                <!-- Contenido normal (se oculta cuando está cargando) -->
                                <div wire:loading.remove wire:target="irAlCarrito({{ $quote->id }})" class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.5 5M7 13l2.5 5m4.5-5a2 2 0 11-4 0 2 2 0 014 0zm6 0a2 2 0 11-4 0 2 2 0 014 0z"></path>
                                    </svg>
                                    <span>Carrito</span>
                                </div>
                            </button>
                            @endif
                            <!-- Botón Imprimir -->
                            <button
                                wire:click="printQuote({{ $quote->id }})"
                                wire:loading.attr="disabled"
                                wire:target="printQuote({{ $quote->id }})"
                                class="bg-blue-500 hover:bg-blue-600 disabled:bg-blue-300 disabled:cursor-not-allowed text-white p-2 rounded-lg transition-all duration-200 min-w-[44px] min-h-[44px] flex items-center justify-center"
                                title="Imprimir cotización"
                            >
                                <!-- Spinner de loading (se muestra cuando está cargando) -->
                                <div wire:loading wire:target="printQuote({{ $quote->id }})">
                                    <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </div>

                                <!-- Ícono normal (se oculta cuando está cargando) -->
                                <div wire:loading.remove wire:target="printQuote({{ $quote->id }})">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                                    </svg>
                                </div>
                            </button>

                            <!-- Botón Eliminar -->
                            <button
                                wire:click="eliminar({{ $quote->id }})"
                                wire:loading.attr="disabled"
                                wire:target="eliminar({{ $quote->id }})"
                                onclick="return confirm('¿Está seguro de eliminar esta cotización?')"
                                class="bg-red-500 hover:bg-red-600 disabled:bg-red-300 disabled:cursor-not-allowed text-white p-2 rounded-lg transition-all duration-200 min-w-[44px] min-h-[44px] flex items-center justify-center"
                                title="Eliminar cotización"
                            >
                                <!-- Spinner de loading (se muestra cuando está cargando) -->
                                <div wire:loading wire:target="eliminar({{ $quote->id }})">
                                    <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                </div>

                                <!-- Ícono normal (se oculta cuando está cargando) -->
                                <div wire:loading.remove wire:target="eliminar({{ $quote->id }})">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </div>
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
                                wire:click="nuevaCotizacion"
                                wire:loading.attr="disabled"
                                wire:target="nuevaCotizacion"
                                class="bg-green-500 hover:bg-green-600 disabled:bg-green-300 disabled:cursor-not-allowed text-white px-6 py-3 rounded-lg font-medium transition-all duration-200 flex items-center gap-2"
                            >
                                <!-- Spinner de loading -->
                                <div wire:loading wire:target="nuevaCotizacion" class="flex items-center gap-2">
                                    <svg class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span>Creando...</span>
                                </div>

                                <!-- Texto normal -->
                                <span wire:loading.remove wire:target="nuevaCotizacion">Crear Primera Cotización</span>
                            </button>
                        @endif
                    </div>
                </div>
            @endforelse
        </div>

            <!-- Pagination -->
            @if($quotes->hasPages())
                <div class="mt-6">
                    {{ $quotes->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('quoterListOffline', () => ({
            offlineQuotes: [],
            isOnline: navigator.onLine,
            filteredQuotes: [], // Para búsqueda local

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
                        const response = await @this.processOfflineOrder(order);
                        
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
                @this.dispatch('refresh-component'); 
                @this.call('$refresh');
                
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
    });
</script>


