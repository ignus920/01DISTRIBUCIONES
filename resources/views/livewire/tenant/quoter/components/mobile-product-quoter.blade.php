@push('scripts')
<script>
    /**
     * Componente Alpine.js para gestionar el estado Offline y la Sincronización
     */
    document.addEventListener('alpine:init', () => {
        Alpine.data('quoterOffline', () => ({
            isOnline: navigator.onLine,
            syncing: false,
            showCart: false, // Control manual (Principalmente Offline)
            showCartModal: @entangle('showCartModal'), // Sincronizado con Livewire
            displayProducts: [], // Lista única para visualización (Online y Offline)
            localSearch: '',
            localCart: @json(array_values($quoterItems)), // Inicializar con datos del servidor
            localCustomers: [], // Caché de clientes
            selectedLocalCustomer: null,

            /**
             * Obtiene la base de datos global con reintentos
             */
            async getDb() {
                let attempts = 0;
                while (!window.db && attempts < 20) { 
                    await new Promise(resolve => setTimeout(resolve, 100));
                    attempts++;
                }
                return window.db;
            },

            async init() {
                window.addEventListener('online', () => {
                    this.isOnline = true;
                    // Limpiar búsquedas al volver a online para restaurar el catálogo completo
                    this.localSearch = '';
                    @this.set('search', '', true); 
                    this.syncProducts(); 
                });
                window.addEventListener('offline', () => {
                    this.isOnline = false;
                    this.loadLocalProducts(); 
                });

                // --- NUEVA SINCRONIZACIÓN SEGMENTADA ---
                
                // 1. Inicio: Limpiar base de datos para nueva carga
                window.addEventListener('sync-started', async (event) => {
                    const db = await this.getDb();
                    if (!db) return;
                    this.syncing = true;
                    await db.productos.clear();
                    await db.clientes.clear();
                    console.log('🔄 Sincronización iniciada...');
                });

                // 2. Paquete de Clientes
                window.addEventListener('sync-customers-chunk', async (event) => {
                    const { customers } = event.detail[0];
                    console.log('👥 Recibiendo paquete de clientes:', customers.length);
                    const db = await this.getDb();
                    if (db && customers) {
                        try {
                            await db.clientes.bulkPut(customers);
                            console.log('✅ Clientes guardados en IDB');
                        } catch (e) { console.error('Error clientes:', e); }
                    }
                });

                // 3. Paquete de Productos
                window.addEventListener('sync-products-chunk', async (event) => {
                    const { products, chunkIndex, totalChunks } = event.detail[0];
                    const db = await this.getDb();
                    if (db && products) {
                        try {
                            await db.productos.bulkPut(products);
                            console.log(`📦 Paquete ${chunkIndex}/${totalChunks} procesado`);
                        } catch (e) { console.error('Error productos:', e); }
                    }
                });

                // 4. Fin: Cargar datos locales
                window.addEventListener('sync-finished', async (event) => {
                    this.syncing = false;
                    await this.loadLocalProducts();
                    console.log('✅ Catálogo completo sincronizado');
                });

                // Escuchar actualizaciones de productos desde Livewire (Online)
                window.addEventListener('products-updated', async (event) => {
                    if (this.isOnline) {
                        const products = event.detail[0]?.products || [];
                        this.displayProducts = products;
                        await this.saveToLocalCache(products);
                    }
                });

                // --- MIRRORING: Sincronizar Carrito Online -> Offline ---
                window.addEventListener('cart-updated', async (event) => {
                    if (this.isOnline) {
                        const items = event.detail.items || event.detail[0]?.items || [];
                        
                        // Mapear exactamente la estructura del servidor a local
                        this.localCart = items.map(item => ({
                            id: item.id,
                            name: item.name,
                            sku: item.sku,
                            price: item.price,
                            price_label: item.price_label,
                            quantity: item.quantity
                        }));

                        console.log('✅ Carrito sincronizado (Mirroring)', this.localCart);
                    }
                });
                
                if (!this.isOnline) await this.loadLocalProducts();
            },

            /**
             * Obtiene la cantidad de un producto (Unificado)
             */
            getProductQuantity(productId) {
                if (this.isOnline) {
                    const prod = this.displayProducts.find(p => p.id === productId);
                    return prod ? (prod.quantity || 0) : 0;
                } else {
                    const item = this.localCart.find(item => item.id === productId);
                    return item ? item.quantity : 0;
                }
            },

            /**
             * Actualiza la cantidad localmente (Modo Offline)
             */
            updateLocalQuantity(productId, delta) {
                const item = this.localCart.find(item => item.id === productId);
                if (item) {
                    item.quantity += delta;
                    if (item.quantity <= 0) {
                        this.localCart = this.localCart.filter(i => i.id !== productId);
                    }
                }
            },

            /**
             * Añade producto al carrito local (Modo Offline)
             */
            addToLocalCart(product, price, priceLabel) {
                const existing = this.localCart.find(item => item.id === product.id);
                if (existing) {
                    existing.quantity++;
                } else {
                    this.localCart.push({
                        id: product.id,
                        name: product.display_name || product.name,
                        sku: product.sku,
                        price: price,
                        price_label: priceLabel,
                        quantity: 1
                    });
                }
                // Eliminado el alert por solicitud del usuario
            },

            /**
             * Carga productos desde IndexedDB aplicando filtros
             */
            async loadLocalProducts() {
                const db = await this.getDb();
                if (!db) return;

                try {
                    let query = db.productos;
                    if (this.localSearch) {
                        const searchLower = this.localSearch.toLowerCase();
                        this.displayProducts = await query
                            .filter(p => {
                                const name = (p.name || '').toLowerCase();
                                const dispName = (p.display_name || '').toLowerCase();
                                const sku = (p.sku || '').toLowerCase();
                                return name.includes(searchLower) || 
                                       dispName.includes(searchLower) || 
                                       sku.includes(searchLower);
                            })
                            .toArray();
                    } else {
                        // Cargar una muestra inicial (por defecto los primeros 50 para rendimiento)
                        this.displayProducts = await query.limit(50).toArray();
                    }
                } catch (error) {
                    console.error('❌ Error al cargar productos locales:', error);
                }
            },

            /**
             * Guarda productos individuales en caché (Carga parcial/incremental)
             */
            async saveToLocalCache(products) {
                if (!products || products.length === 0) return;
                const db = await this.getDb();
                if (!db) return;

                try {
                    // bulkPut actualiza si existe el ID o lo crea si no. NO borra lo anterior.
                    await db.productos.bulkPut(products);
                } catch (error) {
                    console.error('❌ Error al guardar en caché incremental:', error);
                }
            },

            /**
             * Busca clientes en IndexedDB (Modo Offline)
             */
            async searchLocalCustomer(query) {
                console.log('🔍 Buscando cliente local:', query);
                const db = await this.getDb();
                if (!db || !query) {
                    this.localCustomers = [];
                    return;
                }

                try {
                    const count = await db.clientes.count();
                    console.log('📊 Total clientes en DB:', count);

                    const searchLower = query.toLowerCase();
                    this.localCustomers = await db.clientes
                        .filter(c => {
                            const bName = (c.businessName || '').toLowerCase();
                            const fName = (c.firstName || '').toLowerCase();
                            const lName = (c.lastName || '').toLowerCase();
                            const ident = (c.identification || '').toLowerCase();
                            return bName.includes(searchLower) || 
                                   fName.includes(searchLower) || 
                                   lName.includes(searchLower) || 
                                   ident.includes(searchLower);
                        })
                        .limit(10)
                        .toArray();
                    
                    console.log('✅ Resultados encontrados:', this.localCustomers.length);
                } catch (e) {
                    console.error('❌ Error buscando cliente:', e);
                }
            },

            /**
             * Guarda el pedido en IndexedDB (Modo Offline)
             */
            async saveLocalOrder() {
                if (this.localCart.length === 0) {
                    alert('El carrito está vacío');
                    return;
                }
                if (!this.selectedLocalCustomer) {
                    alert('Debe seleccionar un cliente');
                    return;
                }

                const db = await this.getDb();
                if (!db) return;

                try {
                    const order = {
                        cliente_id: this.selectedLocalCustomer.id,
                        cliente_nombre: this.selectedLocalCustomer.businessName || 
                                        (this.selectedLocalCustomer.firstName + ' ' + this.selectedLocalCustomer.lastName),
                        fecha: new Date().toISOString(),
                        items: JSON.parse(JSON.stringify(this.localCart)),
                        total: this.localCart.reduce((sum, item) => sum + (item.price * item.quantity), 0),
                        sincronizado: 0
                    };

                    await db.pedidos.add(order);
                    
                    // Notificación tipo Toast elegante
                    Swal.fire({
                        icon: 'success',
                        title: '✅ Pedido guardado',
                        text: 'Se sincronizará automáticamente al recuperar internet.',
                        timer: 3000,
                        showConfirmButton: false
                    });

                    this.localCart = [];
                    this.selectedLocalCustomer = null;
                    this.showCart = false;
                    if(this.isOnline) $wire.toggleCartModal();
                } catch (error) {
                    console.error('❌ Error al guardar pedido:', error);
                    Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo guardar el pedido localmente' });
                }
            },

            async syncProducts() {
                this.syncing = true;
                this.syncing = false;
            }
        }));
    });
</script>
@endpush

{{-- Contenedor con lógica offline --}}
<div x-data="quoterOffline" class="fixed inset-0 z-[35] bg-gray-50 dark:bg-gray-900 flex flex-col overflow-hidden">
    
    <!-- Banner de estado Offline -->
    <div x-show="!isOnline" 
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="-translate-y-full"
         x-transition:enter-end="translate-y-0"
         class="bg-red-600 text-white text-xs py-1 text-center font-bold sticky top-0 z-[60]">
        ⚠️ Modo Sin Conexión - Usando datos locales
    </div>

    <!-- Header fijo con búsqueda y categorías -->
    <div class="flex-shrink-0 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shadow-md">
        <div class="px-4 py-3">
            <!-- Barra superior con botón regresar y menú -->
            <div class="flex items-center justify-between mb-3">
                @if(auth()->check() && auth()->user()->profile_id == 17)
                <a
                    href="{{ route('tenant.tat.restock.list') }}"
                    class="p-2 text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200
                        flex items-center gap-2"
                    wire:navigate.hover>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>

                    <span class="text-sm">Regresar</span>
                </a>
                @else
                <a
                    href="{{ route('tenant.quoter') }}"
                    class="p-2 text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200
                        flex items-center gap-2"
                    wire:navigate.hover>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>

                    <span class="text-sm font-medium">Regresar</span>
                </a>
                @endif

                <div class="flex items-center gap-2">
                    <!-- Botón Sincronizar (Solo Online) -->
                    <button 
                        x-show="isOnline"
                        wire:click="syncFullCatalog"
                        wire:loading.attr="disabled"
                        class="p-2 text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30 rounded-lg text-[10px] font-bold uppercase flex items-center gap-1 shadow-sm">
                        <svg wire:loading.remove wire:target="syncFullCatalog" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        <svg wire:loading wire:target="syncFullCatalog" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>Sincronizar</span>
                    </button>

                    <!-- Botón del Carrito en Header -->
                    <button
                        @click="if(isOnline) { showCartModal = true; } else { showCart = true; }"
                        class="relative p-3 text-white bg-indigo-600 dark:bg-indigo-500 rounded-2xl hover:bg-indigo-700 dark:hover:bg-indigo-600 transition-all duration-200 shadow-md active:scale-95">
                        
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17M17 16a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>

                        <!-- Indicador Online -->
                        <template x-if="isOnline">
                            @if($this->quoterCount > 0)
                            <span class="absolute -top-2 -right-1.5 bg-red-600 text-white text-[11px] font-black rounded-full min-w-[22px] h-[22px] flex items-center justify-center border-2 border-white dark:border-gray-800 shadow-sm animate-pulse">
                                {{ $this->quoterCount }}
                            </span>
                            @endif
                        </template>

                        <!-- Indicador Offline -->
                        <template x-if="!isOnline">
                            <span x-show="localCart.length > 0" 
                                  class="absolute -top-2 -right-1.5 bg-orange-600 text-white text-[11px] font-black rounded-full min-w-[22px] h-[22px] flex items-center justify-center border-2 border-white dark:border-gray-800 shadow-sm"
                                  x-text="localCart.reduce((sum, item) => sum + item.quantity, 0)">
                            </span>
                        </template>
                    </button>

                    <!-- Mobile menu button -->
                    <button type="button" class="p-2.5 text-gray-700 dark:text-gray-300 lg:hidden" @click="sidebarOpen = true">
                        <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Contenedor principal con elementos en grid de 2 columnas -->
            <div class="grid grid-cols-2 gap-2">
                <!-- Búsqueda -->
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-2.5 flex items-center pointer-events-none">
                        <!-- Icono de búsqueda normal -->
                        <svg wire:loading.remove wire:target="search" class="h-4 w-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>

                        <!-- Spinner de carga -->
                        <svg wire:loading wire:target="search" class="h-3 w-3 text-indigo-500 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                    </div>
                    
                    <!-- Input con triple unión: Livewire (Online), Alpine (Estado) y Evento (Offline) -->
                    <input 
                        x-model="localSearch"
                        @input="if(!isOnline) { localSearch = $event.target.value; loadLocalProducts(); }"
                        wire:model.live.debounce.500ms="search"
                        wire:key="mobile-search-input-grid"
                        type="search"
                        placeholder="Buscar..."
                        class="block w-full pl-8 pr-2 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm"
                        autocomplete="off"
                        id="productSearchInput">
                </div>

                <!-- Filtro de Categorías -->
                <div class="w-full">
                    <select wire:model.live="selectedCategory"
                        class="block w-full px-2 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500 truncate">
                        <option value="">Categorías</option>
                        @foreach($this->getCategories() as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

        </div>
    </div>

    <!-- Lista de productos UNIFICADA -->
    <div class="flex-1 overflow-y-auto pb-20">
        <div class="px-3 py-4 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3">
            <template x-for="product in displayProducts" :key="product.id">
                <div class="relative flex flex-col bg-white dark:bg-gray-800 rounded-xl border-2 transition-all duration-300 overflow-hidden"
                     :class="getProductQuantity(product.id) > 0 
                                ? 'border-indigo-500 ring-2 ring-indigo-200 dark:ring-indigo-900/40 shadow-md scale-[1.02]' 
                                : 'border-transparent shadow-sm hover:border-gray-200 dark:hover:border-gray-700'">

                    <!-- Badge de cantidad (Flotante) -->
                    <template x-if="getProductQuantity(product.id) > 0">
                        <div class="absolute top-2 right-2 z-10">
                            <span class="flex items-center justify-center w-7 h-7 bg-indigo-600 dark:bg-indigo-500 text-white text-xs font-bold rounded-full shadow-lg border-2 border-white dark:border-gray-800 animate-in zoom-in duration-300"
                                  x-text="getProductQuantity(product.id)">
                            </span>
                        </div>
                    </template>

                    <!-- Imagen del producto -->
                    <div class="aspect-square bg-gray-50 dark:bg-gray-700/50 flex items-center justify-center p-2 relative group">
                        <template x-if="product.image_url">
                            <img class="w-full h-full object-contain rounded-lg transition-transform duration-300 group-hover:scale-110"
                                :src="product.image_url"
                                :alt="product.display_name">
                        </template>
                        <template x-if="!product.image_url">
                            <div class="w-16 h-16 bg-gray-200 dark:bg-gray-600 rounded-2xl flex items-center justify-center shadow-inner">
                                <span class="text-2xl font-bold text-gray-400 dark:text-gray-500" x-text="(product.display_name || product.name || 'P').charAt(0).toUpperCase()"></span>
                            </div>
                        </template>
                        
                        <div x-show="getProductQuantity(product.id) > 0" class="absolute inset-0 bg-indigo-500/5 transition-opacity"></div>
                    </div>

                    <!-- Información del producto -->
                    <div class="p-3 flex-1 flex flex-col">
                        <!-- SKU y Nombre -->
                        <div class="mb-3">
                            <div class="text-[10px] uppercase tracking-wider text-gray-400 dark:text-gray-500 font-semibold truncate mb-1" x-text="product.sku || 'Sin SKU'"></div>
                            <div class="font-bold text-gray-900 dark:text-white text-xs line-clamp-2 leading-tight min-h-[2rem]" x-text="product.display_name"></div>
                        </div>

                        <!-- Precios / Selector de cantidad -->
                        <div class="mt-auto pt-2">
                            <!-- Selector si ya está seleccionado -->
                            <template x-if="getProductQuantity(product.id) > 0">
                                <div class="flex flex-col gap-2">
                                    <div class="px-2 py-1 bg-green-50 dark:bg-green-900/10 border border-green-100 dark:border-green-800 rounded-lg flex flex-col items-center justify-center">
                                        <span class="text-[8px] uppercase font-bold text-green-700 dark:text-green-400 opacity-70">Seleccionado</span>
                                        <span class="text-[11px] font-black text-green-700 dark:text-green-400 tracking-tight" x-text="'$' + (product.selected_price ? Number(product.selected_price).toLocaleString() : Number(product.price).toLocaleString())"></span>
                                    </div>
                                    <div class="flex items-center justify-between bg-indigo-50 dark:bg-indigo-900/20 rounded-lg p-1 border border-indigo-100 dark:border-indigo-800">
                                        <button @click="isOnline ? $wire.decreaseQuantity(product.id) : updateLocalQuantity(product.id, -1)"
                                                class="w-8 h-8 flex items-center justify-center bg-white dark:bg-gray-800 text-indigo-600 dark:text-indigo-400 rounded-md shadow-sm border border-indigo-100 dark:border-indigo-800 active:scale-90 transition-transform">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M20 12H4"></path></svg>
                                        </button>
                                        <span class="w-12 text-center font-black text-sm text-gray-900 dark:text-white" x-text="getProductQuantity(product.id)"></span>
                                        <button @click="isOnline ? $wire.increaseQuantity(product.id) : updateLocalQuantity(product.id, 1)"
                                                class="w-8 h-8 flex items-center justify-center bg-indigo-600 text-white rounded-md shadow-sm active:scale-95 transition-transform">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                                        </button>
                                    </div>
                                </div>
                            </template>

                            <!-- Precios para seleccionar -->
                            <template x-if="getProductQuantity(product.id) === 0">
                                <div class="space-y-1.5">
                                    <template x-for="(price, label) in (product.all_prices || {})" :key="label">
                                        <button @click="isOnline ? $wire.addToQuoter(product.id, price, label) : addToLocalCart(product, price, label)"
                                                class="w-full py-2 px-2 text-center rounded-lg border-2 border-green-100 dark:border-green-800 bg-green-50 dark:bg-green-900/10 text-green-700 dark:text-green-400 hover:bg-green-100 active:scale-95 transition-all flex flex-col items-center justify-center">
                                            <span class="text-[9px] uppercase font-bold opacity-60" x-text="label == 'Precio Regular' ? 'Precio' : label"></span>
                                            <span class="text-sm font-black tracking-tight" x-text="'$' + Number(price).toLocaleString()"></span>
                                        </button>
                                    </template>
                                    <template x-if="!product.all_prices || Object.keys(product.all_prices).length === 0">
                                        <div class="text-[10px] font-bold text-gray-400 italic text-center py-2 border border-dashed border-gray-200 rounded-lg">Sin precio</div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <!-- Estado Vacío -->
        <template x-if="displayProducts.length === 0">
            <div class="text-center py-20 px-4">
                <svg class="mx-auto h-16 w-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                </svg>
                <h3 class="text-lg font-medium text-gray-400">Sin productos disponibles</h3>
                <p class="text-sm text-gray-500 max-w-xs mx-auto">Sincroniza o revisa tu conexión para ver el catálogo.</p>
            </div>
        </template>
    </div>


    <!-- Paginación -->
    @if($products->hasPages())
    <div class="px-4 py-4" x-show="isOnline">
        {{ $products->links('livewire.tenant.quoter.components.simple-pagination') }}
    </div>
    @endif

    <!-- Modal del carrito (Unificado Offline/Online) -->
    <div x-show="showCart || showCartModal" 
         x-cloak
         class="fixed inset-0 z-50 flex items-stretch overflow-hidden"
         style="display: none;">
        
        <!-- Overlay -->
        <div class="fixed inset-0 bg-black bg-opacity-50" @click="showCart = false; showCartModal = false;"></div>

        <!-- Modal - Pantalla completa -->
        <div class="relative w-full h-full bg-white dark:bg-gray-800 flex flex-col shadow-2xl animate-in slide-in-from-bottom duration-300">
            <div class="flex flex-col h-full">

                <!-- Header del modal -->
                <div class="px-4 py-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between flex-shrink-0 bg-white dark:bg-gray-800">
                    
                    <!-- Izquierda: Botón Seguir Comprando (Regresar) -->
                    <button @click="showCart = false; showCartModal = false;" 
                        class="p-2 text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200 flex items-center gap-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                        <span>Seguir Comprando</span>
                    </button>

                    <!-- Derecha: Cantidad y Limpiar -->
                    <div class="flex items-center gap-3">
                        <template x-if="isOnline">
                            <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $this->quoterCount }} Productos</h2>
                        </template>
                        <template x-if="!isOnline">
                            <h2 class="text-sm font-semibold text-orange-600 dark:text-orange-400" x-text="localCart.reduce((sum, item) => sum + item.quantity, 0) + ' Productos (Local)'"></h2>
                        </template>
                        
                        <!-- Botón limpiar carrito (Online) -->
                        @if(!empty($quoterItems))
                        <template x-if="isOnline">
                            <div class="flex flex-col items-center justify-center">
                                <span class="text-[10px] text-red-500 font-bold uppercase leading-none mb-0.5">Limpiar</span>
                                <button
                                    onclick="confirmClearCart()"
                                    title="Limpiar carrito"
                                    class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 p-1 rounded-full hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        </template>
                        @endif

                        <!-- Botón limpiar carrito (Offline) -->
                        <template x-if="!isOnline && localCart.length > 0">
                            <button @click="if(confirm('¿Limpiar carrito local?')) localCart = []" class="text-red-500 text-[10px] font-bold uppercase underline">
                                Limpiar
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Búsqueda de clientes -->
                <div class="px-4 py-1 border-b border-gray-200 dark:border-gray-700 flex-shrink-0 bg-white dark:bg-gray-800">
                    @if($selectedCustomer)
                    <!-- Cliente seleccionado -->
                    <div wire:key="customer-selected-box"
                        x-data="{ show: true }"
                        x-show="show"
                        x-transition.opacity
                        class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-lg p-2 mb-2 mt-2">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h4 class="font-semibold text-green-800 dark:text-green-200 text-sm">
                                    {{ $selectedCustomer['businessName'] ?: $selectedCustomer['firstName'] . ' ' . $selectedCustomer['lastName'] }}
                                </h4>

                                <p class="text-xs text-green-600 dark:text-green-300">
                                    Identificación: {{ $selectedCustomer['identification'] }}
                                </p>
                            </div>
                            @if(auth()->user()->profile_id != 17)
                            <div class="flex items-center ml-2">
                                <!-- Botón Editar -->
                                <button
                                    wire:click="editCustomer"
                                    wire:loading.attr="disabled"
                                    wire:loading.class="opacity-50 cursor-wait"
                                    class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-200 mr-4"
                                    title="Editar cliente">

                                    <!-- Ícono normal -->
                                    <svg wire:loading.remove wire:target="editCustomer" class="w-7 h-7 mr-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>

                                    <!-- Ícono de loading -->
                                    <svg wire:loading wire:target="editCustomer" class="w-6 h-6 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 714 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                    </svg>
                                </button>

                                <!-- Botón Limpiar -->
                                <button
                                    x-on:click="show = false"
                                    wire:click="clearCustomer"
                                    class="text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-200"
                                    title="Limpiar cliente">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif

                    @if(($showCreateCustomerButton || $showCreateCustomerForm) && auth()->user()->profile_id != 17)
                    <!-- Formulario para crear/editar cliente -->

                    @if (!$editingCustomerId)
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-medium text-gray-700 dark:text-gray-300">Crear Cliente</label>
                        <button
                            x-on:click="show = false"
                            wire:click="clearCustomer"
                            class="text-green-600 hover:text-green-800 dark:text-green-400 dark:hover:text-green-200 ml-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    @endif

                    @if (!$editingCustomerId)
                    <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-2">
                        @endif
                        <livewire:tenant.vnt-company.vnt-company-form
                            :reusable="true"
                            :companyId="$editingCustomerId"
                            key="customer-form-{{ $editingCustomerId ?? 'new' }}" />
                        @if (!$editingCustomerId)
                    </div>
                    @endif


                    @endif

                    @if(!$selectedCustomer && !$showCreateCustomerForm && !$showCreateCustomerButton && auth()->user()->profile_id != 17)
                    <!-- Formulario de búsqueda (ONLINE/OFFLINE) -->
                    <div class="space-y-2 relative">
                        <label class="text-xs font-medium text-gray-700 dark:text-gray-300">Buscar Cliente</label>
                        
                        <!-- Input único para ambos modos -->
                        <template x-if="isOnline">
                            <input
                                wire:model.live.debounce.150ms="customerSearch"
                                type="text"
                                placeholder="Escribe nombre o cédula..."
                                class="w-full px-3 py-2 text-sm border rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-green-500"
                                autocomplete="off"
                                id="customerSearchInputMobile">
                        </template>

                        <template x-if="!isOnline">
                            <input
                                @input.debounce.200ms="searchLocalCustomer($event.target.value)"
                                type="text"
                                placeholder="Buscar en memoria del celular..."
                                class="w-full px-3 py-2 text-sm border border-orange-300 rounded-lg bg-orange-50/50 dark:bg-gray-700 dark:border-orange-900/50 text-gray-900 dark:text-white focus:ring-2 focus:ring-orange-500"
                                autocomplete="off">
                        </template>

                        <!-- Resultados Offline -->
                        <template x-if="!isOnline && localCustomers.length > 0">
                            <div class="absolute z-[60] left-0 right-0 w-full max-h-60 overflow-y-auto border border-orange-200 dark:border-orange-900/30 rounded-lg bg-white dark:bg-gray-800 mt-1 shadow-xl">
                                <template x-for="customer in localCustomers" :key="customer.id">
                                    <div 
                                        @click="selectedLocalCustomer = customer; localCustomers = []"
                                        class="px-4 py-3 text-xs hover:bg-orange-50 dark:hover:bg-orange-900/20 cursor-pointer border-b border-gray-100 dark:border-gray-700 last:border-b-0 flex flex-col gap-1">
                                        <div class="font-mono font-bold text-gray-900 dark:text-white text-sm" x-text="customer.identification"></div>
                                        <div class="text-gray-600 dark:text-gray-300 capitalize" x-text="(customer.businessName || (customer.firstName + ' ' + customer.lastName)).toLowerCase()"></div>
                                    </div>
                                </template>
                            </div>
                        </template>

                        <!-- Caja de Cliente seleccionado Offline -->
                        <template x-if="!isOnline && selectedLocalCustomer">
                            <div class="bg-orange-100 dark:bg-orange-900/30 border border-orange-200 dark:border-orange-800 rounded-lg p-2 mt-2 flex justify-between items-center animate-in zoom-in duration-200">
                                <div class="flex-1">
                                    <div class="text-[10px] font-bold text-orange-600 uppercase">Cliente Local</div>
                                    <div class="text-xs font-black" x-text="selectedLocalCustomer.businessName || (selectedLocalCustomer.firstName + ' ' + selectedLocalCustomer.lastName)"></div>
                                    <div class="text-[10px]" x-text="'ID: ' + selectedLocalCustomer.identification"></div>
                                </div>
                                <button @click="selectedLocalCustomer = null" class="text-red-500 p-1">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                </button>
                            </div>
                        </template>

                        <!-- Resultados Online (Livewire) -->
                        <template x-if="isOnline">
                            <div>
                                @if(count($customerSearchResults) > 0)
                                <div id="customerSearchResultsMobile" class="max-h-60 overflow-y-auto border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 mt-2 shadow-md">
                                    @foreach($customerSearchResults as $index => $customer)
                                    <div
                                        wire:click="selectCustomer({{ $customer['id'] }})"
                                        class="customer-result-mobile px-3 py-2 text-xs hover:bg-gray-50 dark:hover:bg-gray-600 cursor-pointer border-b border-gray-100 dark:border-gray-600 last:border-b-0 transition-colors duration-150">
                                        <div class="font-mono font-bold text-gray-900 dark:text-white">{{ $customer['identification'] }}</div>
                                        <div class="text-gray-600 dark:text-gray-300">{{ $customer['display_name'] }}</div>
                                    </div>
                                    @endforeach
                                </div>
                                @elseif(strlen($customerSearch) >= 1)
                                <div class="border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 mt-2">
                                    <div class="p-3 text-sm text-gray-500 dark:text-gray-400">
                                        <div class="mb-2">No se encontraron clientes</div>
                                        <button wire:click="openCustomerModal" class="px-3 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded text-xs">Crear nuevo cliente</button>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </template>
                    </div>
                    @endif

                    {{-- Información para usuarios de tienda (profile_id 17) cuando no hay cliente seleccionado --}}
                    @if(!$selectedCustomer && auth()->user()->profile_id == 17)
                    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg p-2 mb-4">
                        <div class="flex items-center">
                            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            <div class="flex-1">
                                <h4 class="font-medium text-blue-800 dark:text-blue-200 text-sm">{{ auth()->user()->name }}</h4>
                                <p class="text-xs text-blue-600 dark:text-blue-300">{{ auth()->user()->email }}</p>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Contenido del carrito (UNIFICADO) -->
                <div class="flex-1 overflow-y-auto px-4 py-4 min-h-0">
                    
                    <!-- Estado Vacío -->
                    <template x-if="localCart.length === 0">
                        <div class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <p class="text-gray-500 dark:text-gray-400">Carrito vacío</p>
                            <p x-show="!isOnline" class="text-xs text-orange-500 mt-2">Modo Offline</p>
                        </div>
                    </template>
                    
                    <!-- Lista de Items (ÚNICA para Online y Offline) -->
                    <div class="space-y-4">
                        <template x-for="(item, index) in localCart" :key="item.id + '-' + index">
                            <div class="relative overflow-hidden rounded-xl mb-3 touch-pan-y shadow-sm border"
                                 :class="!isOnline ? 'border-orange-200 dark:border-orange-900/30 bg-white dark:bg-gray-700' : 'border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700'"
                                 x-data="{
                                    startX: 0,
                                    currentX: 0,
                                    handleStart(e) {
                                        this.startX = e.touches[0].clientX;
                                    },
                                    handleMove(e) {
                                        const touchX = e.touches[0].clientX;
                                        // Permitir deslizamiento bidireccional (-80 a 80)
                                        let diff = touchX - this.startX;
                                        this.currentX = diff > 0 ? Math.min(80, diff) : Math.max(-80, diff);
                                    },
                                    handleEnd() {
                                        if (Math.abs(this.currentX) > 40) {
                                            // Confirmar eliminación (en cualquier dirección)
                                            if(this.isOnline) {
                                                $wire.removeFromQuoter(index);
                                            } else {
                                                this.localCart.splice(index, 1);
                                            }
                                            this.currentX = 0;
                                        } else {
                                            this.currentX = 0;
                                        }
                                    }
                                 }"
                                 @touchstart="handleStart($event)"
                                 @touchmove="handleMove($event)"
                                 @touchend="handleEnd()"
                                 @touchcancel="currentX = 0">

                                <!-- Fondo Rojo (Swipe Bidireccional) -->
                                <div class="absolute inset-0 bg-red-500 flex items-center justify-between px-4 text-white"
                                     :style="`opacity: ${Math.abs(currentX) / 60}`">
                                    <svg class="w-6 h-6 transform rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </div>

                                <!-- Contenido de la Tarjeta -->
                                <div class="relative p-3 flex items-center justify-between bg-inherit transition-transform duration-100 ease-out"
                                     :style="`transform: translateX(${currentX}px)`">
                                    
                                    <div class="flex-1 min-w-0 pr-4">
                                        <div class="text-[10px] font-bold uppercase tracking-tighter" 
                                             :class="!isOnline ? 'text-orange-600' : 'text-gray-500'" 
                                             x-text="item.sku || 'S/S'"></div>
                                        <div class="text-xs font-black text-gray-900 dark:text-white line-clamp-1" x-text="item.name"></div>
                                        <div class="text-[10px] text-gray-500 font-medium" x-text="(item.price_label || 'Precio') + ': $' + Number(item.price).toLocaleString()"></div>
                                    </div>
                                    
                                    <div class="flex items-center gap-2">
                                        <!-- Controles de Cantidad -->
                                        <div class="flex items-center bg-gray-50 dark:bg-gray-800 rounded-lg border border-gray-200 dark:border-gray-600">
                                            
                                            <!-- Botón Menos -->
                                            <button 
                                                @click.stop="isOnline ? $wire.updateQuantity(index, item.quantity - 1) : (item.quantity > 1 ? item.quantity-- : localCart.splice(index, 1))" 
                                                class="p-2 px-3 text-gray-500 hover:text-gray-700 active:bg-gray-200 rounded-l-lg transition-colors">
                                                -
                                            </button>
                                            
                                            <!-- Cantidad -->
                                            <span class="px-2 text-sm font-black text-gray-900 dark:text-white min-w-[1.5rem] text-center" x-text="item.quantity"></span>
                                            
                                            <!-- Botón Más -->
                                            <button 
                                                @click.stop="isOnline ? $wire.updateQuantity(index, item.quantity + 1) : item.quantity++" 
                                                class="p-2 px-3 text-gray-500 hover:text-gray-700 active:bg-gray-200 rounded-r-lg transition-colors">
                                                +
                                            </button>
                                        </div>

                                        <!-- Eliminar (Botón explícito también) -->
                                        <button 
                                            @click.stop="isOnline ? $wire.removeFromQuoter(index) : localCart.splice(index, 1)" 
                                            class="text-red-500 p-2 hover:bg-red-50 rounded-full transition-colors">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                </div>

                <!-- Footer del modal -->
                @if(!empty($quoterItems))
                <div class="px-4 py-4 border-t border-gray-200 dark:border-gray-700 space-y-4 flex-shrink-0 bg-white dark:bg-gray-800">

                    <!-- Observaciones -->
                    @if(auth()->user()->profile_id != 17)
                    <div x-data="{ open: @entangle('showObservations') }" class="w-full">

                        <button @click="open = !open"
                            class="w-full flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">

                            <span class="text-sm font-bold text-gray-900 dark:text-white flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                Observaciones:
                            </span>

                            <svg class="w-5 h-5 text-gray-600 dark:text-gray-400 transform transition-transform"
                                :class="{ 'rotate-180': open }"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div x-show="open" x-transition class="mt-3">
                            <textarea
                                wire:model="observaciones"
                                rows="4"
                                placeholder="Escribe observaciones adicionales..."
                                class="block w-full p-2 text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300
                               dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                        </div>
                    </div>
                    @endif

                    <!-- Total -->
                    <div class="flex justify-between items-center text-lg font-bold text-gray-900 dark:text-white">
                        <span>Total:</span>
                        <template x-if="isOnline">
                            <span>${{ number_format($totalAmount) }}</span>
                        </template>
                        <template x-if="!isOnline">
                            <span x-text="'$' + localCart.reduce((sum, item) => sum + (item.price * item.quantity), 0).toLocaleString()"></span>
                        </template>
                    </div>

                    <!-- Botones ---->
                    @if($isEditing)
                    <div class="flex flex-col gap-3">
                        <!-- Fila superior: Actualizar / Cancelar -->
                        <div class="flex gap-2">
                            <button wire:click="updateQuote"
                                wire:loading.attr="disabled"
                                wire:target="updateQuote"
                                class="flex-1 bg-green-600 hover:bg-green-700 text-white font-medium py-3 px-4 rounded-lg flex items-center justify-center disabled:opacity-50 text-sm whitespace-nowrap">

                                <svg wire:loading.remove wire:target="updateQuote" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                
                                <svg wire:loading wire:target="updateQuote" class="w-5 h-5 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>

                                <span wire:loading.remove wire:target="updateQuote">
                                    @if($editingRemissionId)
                                        Editar Remisión
                                    @else
                                        Actualizar
                                    @endif
                                </span>
                                <span wire:loading wire:target="updateQuote">...</span>
                            </button>

                            <button wire:click="cancelEditing"
                                wire:loading.attr="disabled"
                                wire:target="cancelEditing"
                                class="flex-1 bg-gray-500 hover:bg-gray-600 text-white font-medium py-3 px-4 rounded-lg flex items-center justify-center disabled:opacity-50 text-sm whitespace-nowrap">

                                <svg wire:loading.remove wire:target="cancelEditing" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M6 18L18 6M6 6l12 12" />
                                </svg>
                                
                                <svg wire:loading wire:target="cancelEditing" class="w-5 h-5 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>

                                <span wire:loading.remove wire:target="cancelEditing">Cancelar</span>
                                <span wire:loading wire:target="cancelEditing">...</span>
                            </button>
                        </div>

                        <!-- Botón inferior: Confirmar pedido (Solo para distribuidores) -->
                        @if(auth()->user()->profile_id != 17)
                            @if($quoteHasRemission)
                                <div class="w-full bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 font-medium py-3 px-4 rounded-lg flex items-center justify-center text-sm border border-gray-200 dark:border-gray-600">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Remisión ya generada
                                </div>
                            @else
                                <button wire:click="confirmarPedido"
                                    wire:loading.attr="disabled"
                                    wire:target="confirmarPedido"
                                    class="w-full font-medium py-3 px-4 rounded-lg transition-colors flex items-center justify-center text-sm disabled:opacity-50 bg-blue-600 hover:bg-blue-700 text-white">
                                    
                                    <svg wire:loading.remove wire:target="confirmarPedido" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12l2 2 4-4" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z" />
                                    </svg>
                                    
                                    <svg wire:loading wire:target="confirmarPedido" class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    
                                    <span wire:loading.remove wire:target="confirmarPedido">Confirmar pedido</span>
                                    <span wire:loading wire:target="confirmarPedido">Confirmando...</span>
                                </button>
                            @endif
                        @endif
                    </div>


                    @else

                    <div class="space-y-2">

                        @if(!$selectedCustomer && auth()->user()->profile_id != 17)
                        <button disabled
                            class="w-full bg-gray-400 dark:bg-gray-600 text-gray-200 dark:text-gray-400 font-medium py-3 px-4 rounded-lg cursor-not-allowed flex items-center justify-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.802-.833-2.572 0L4.242 16.5c-.77.833.192 2.5 1.732 2.5z" />
                            </svg>
                            Seleccione un Cliente
                        </button>
                        @elseif($selectedCustomer || auth()->user()->profile_id == 17)

                        @if(auth()->user()->profile_id != 17)
                        
                        <!-- BOTÓN GUARDAR (ONLINE) -->
                        <template x-if="isOnline">
                            <button wire:click="saveQuote"
                                wire:loading.attr="disabled"
                                wire:target="saveQuote"
                                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-3 px-4 rounded-lg flex items-center justify-center disabled:opacity-50">

                                <svg wire:loading.remove wire:target="saveQuote" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1" />
                                </svg>

                                <svg wire:loading wire:target="saveQuote" class="w-5 h-5 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                                    <path class="opacity-75" fill="currentColor"
                                        d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" />
                                </svg>

                                <span wire:loading.remove wire:target="saveQuote">Guardar Cotización</span>
                                <span wire:loading wire:target="saveQuote">Guardando...</span>
                            </button>
                        </template>

                        <!-- BOTÓN GUARDAR (OFFLINE) -->
                        <template x-if="!isOnline">
                            <button @click="saveLocalOrder()"
                                class="w-full bg-orange-600 hover:bg-orange-700 text-white font-medium py-3 px-4 rounded-lg flex items-center justify-center shadow-lg active:scale-95 transition-all">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 2m6-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <span>Finalizar Venta Local</span>
                            </button>
                        </template>
                        @endif

                        @if(auth()->user()->profile_id == 17)
                        <!-- Botones TAT (Perfil 17) -->
                        <div class="flex flex-col gap-2 w-full">
                            <!-- Botón Favoritos -->
                            @if(!$isEditingRestock)
                            <button wire:click="saveRestockRequest(false)"
                                wire:loading.attr="disabled"
                                wire:target="saveRestockRequest"
                                class="w-full bg-orange-600 hover:bg-orange-700 dark:bg-orange-500 dark:hover:bg-orange-600 text-white font-medium py-3 px-4 rounded-lg flex items-center justify-center disabled:opacity-50 text-sm">

                                <svg wire:loading.remove wire:target="saveRestockRequest" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                
                                <svg wire:loading wire:target="saveRestockRequest" class="w-5 h-5 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>

                                <span wire:loading.remove wire:target="saveRestockRequest">Favoritos</span>
                                <span wire:loading wire:target="saveRestockRequest">...</span>
                            </button>
                            @endif

                            <!-- Botón Confirmar -->
                            <button wire:click="saveRestockRequest(true)"
                                wire:loading.attr="disabled"
                                wire:target="saveRestockRequest"
                                class="w-full bg-green-600 hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600 text-white font-medium py-3 px-4 rounded-lg flex items-center justify-center disabled:opacity-50 text-sm">

                                <svg wire:loading.remove wire:target="saveRestockRequest" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                
                                <svg wire:loading wire:target="saveRestockRequest" class="w-5 h-5 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>

                                <span wire:loading.remove wire:target="saveRestockRequest">Confirmar pedido</span>
                                <span wire:loading wire:target="saveRestockRequest">...</span>
                            </button>
                        </div>
                        @if($isEditingRestock)
                        <button wire:click="saveRestockRequest"
                            wire:loading.attr="disabled"
                            wire:target="saveRestockRequest"
                            class="mt-2 w-full bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white font-medium py-3 px-4 rounded-lg transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed">

                            <svg wire:loading.remove wire:target="saveRestockRequest" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            
                            <svg wire:loading wire:target="saveRestockRequest" class="w-5 h-5 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>

                            <span wire:loading.remove wire:target="saveRestockRequest">Actualizar Carrito</span>
                            <span wire:loading wire:target="saveRestockRequest">Actualizando...</span>
                        </button>
                        @endif
                </div>
                @endif
            @endif
        @endif
    </div>
</div>
@endif
</div>
</div>
</div>


@push('scripts')
<script>
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('show-toast', (data) => {
            const payload = Array.isArray(data) ? data[0] : data;
            console.log('Mobile Toast triggered:', payload); // Debug
            Swal.fire({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 2000,
                timerProgressBar: true,
                icon: payload.type || 'info',
                title: payload.message,
                background: '#ffffff',
                color: '#111827'
            });
        });

        Livewire.on('confirm-add-duplicate', (data) => {
            const payload = Array.isArray(data) ? data[0] : data;
            Swal.fire({
                title: 'Producto ya confirmado',
                text: payload.message + "\n¿Deseas agregarlo de todas formas?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, agregar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    console.log('Mobile Calling forceAddToQuoter directly:', payload);
                    Livewire.find('{{ $this->getId() }}').call('forceAddToQuoter',
                        payload.productId,
                        payload.selectedPrice,
                        payload.priceLabel
                    );
                }
            });
        });
    });

    // Función para confirmar limpiar carrito
    function confirmClearCart() {
        Swal.fire({
            title: '¿Limpiar carrito?',
            text: 'Se eliminarán todos los productos del carrito. El cliente seleccionado se mantendrá.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc2626',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Sí, limpiar',
            cancelButtonText: 'Cancelar',
            background: '#ffffff',
            color: '#111827'
        }).then((result) => {
            if (result.isConfirmed) {
                @this.call('clearCart');
            }
        });
    }

    // Función para manejar Enter en búsqueda de productos
    function handleProductSearchKeydown(event) {
        if (event.key === 'Enter') {
            event.preventDefault();

            // Buscar el primer producto visible con precio disponible
            const products = document.querySelectorAll('[wire\\:click*="addToQuoter"]');

            if (products.length > 0) {
                // Buscar el primer botón de precio que no esté deshabilitado
                for (let product of products) {
                    if (!product.disabled && !product.hasAttribute('disabled')) {
                        // Hacer scroll al producto
                        product.scrollIntoView({
                            behavior: 'smooth',
                            block: 'center'
                        });

                        // Simular click después del scroll
                        setTimeout(() => {
                            product.click();
                        }, 300);
                        break;
                    }
                }
            }
        }
    }

    // Keyboard navigation for mobile customer search
    var selectedCustomerIndexMobile = -1;

    function handleCustomerSearchKeydownMobile(event) {
        const resultsContainer = document.getElementById('customerSearchResultsMobile');
        const results = resultsContainer ? resultsContainer.querySelectorAll('.customer-result-mobile') : [];

        if (results.length === 0) return;

        switch (event.key) {
            case 'ArrowDown':
                event.preventDefault();
                selectedCustomerIndexMobile = Math.min(selectedCustomerIndexMobile + 1, results.length - 1);
                updateCustomerSelectionMobile(results);
                break;
            case 'ArrowUp':
                event.preventDefault();
                selectedCustomerIndexMobile = Math.max(selectedCustomerIndexMobile - 1, -1);
                updateCustomerSelectionMobile(results);
                break;
            case 'Enter':
                event.preventDefault();
                if (selectedCustomerIndexMobile >= 0 && results[selectedCustomerIndexMobile]) {
                    const customerId = results[selectedCustomerIndexMobile].getAttribute('data-customer-id');
                    if (customerId) {
                        results[selectedCustomerIndexMobile].click();
                    }
                }
                break;
            case 'Escape':
                event.preventDefault();
                selectedCustomerIndexMobile = -1;
                updateCustomerSelectionMobile(results);
                document.getElementById('customerSearchInputMobile').value = '';
                // Clear Livewire model
                Livewire.find(document.querySelector('[wire\\:id]').getAttribute('wire:id')).set('customerSearch', '');
                break;
        }
    }

    function updateCustomerSelectionMobile(results) {
        // Remove previous highlights
        results.forEach(result => {
            result.classList.remove('bg-blue-100', 'dark:bg-blue-700');
        });

        // Add highlight to current selection
        if (selectedCustomerIndexMobile >= 0 && results[selectedCustomerIndexMobile]) {
            const selected = results[selectedCustomerIndexMobile];
            selected.classList.add('bg-blue-100', 'dark:bg-blue-700');

            // Scroll into view if needed
            const container = document.getElementById('customerSearchResultsMobile');
            if (container) {
                const containerRect = container.getBoundingClientRect();
                const selectedRect = selected.getBoundingClientRect();

                if (selectedRect.bottom > containerRect.bottom) {
                    selected.scrollIntoView({
                        block: 'end',
                        behavior: 'smooth'
                    });
                } else if (selectedRect.top < containerRect.top) {
                    selected.scrollIntoView({
                        block: 'start',
                        behavior: 'smooth'
                    });
                }
            }
        }
    }

    // Reset selection when customer search results change
    document.addEventListener('livewire:updated', function() {
        selectedCustomerIndexMobile = -1;
    });
</script>
@endpush