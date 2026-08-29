<div>

@script
<script>
    /**
     * Componente Alpine.js para gestionar el estado Offline y la Sincronización
     */
    Alpine.data('quoterOffline', () => ({
        isOnline: navigator.onLine,
        forceOffline: false, // Permitir forzar modo offline para pruebas
        syncing: false,
        showCart: false, // Control manual (Principalmente Offline)
        showCartModal: $wire.entangle('showCartModal'), // Sincronizado con Livewire
        showObservations: $wire.entangle('showObservations'), // Sincronizado para observaciones
        observaciones: $wire.entangle('observaciones'), // Sincronizado para notas del pedido
        displayProducts: @js($mappedProducts), // Inyectar datos iniciales de forma segura con Livewire 3
        localSearch: '',
        showOfflineCreateForm: false, // Control del formulario offline
        showConfirmSave: false, // Control del modal de confirmación nativo
        showSuccessSave: false, // Control del modal de éxito nativo
        showConfirmClear: false, // Control del modal de confirmación de limpieza
        newOfflineCustomer: { // Datos para el nuevo cliente offline
            id: null,
            typeIdentificationId: 1,
            identification: '',
            businessName: '',
            firstName: '',
            lastName: '',
            phone: '',
            address: '',
            billingEmail: '',
            createUser: false,
            route_id: @js($newCustomerRouteId)
        },
        serverCustomerSearch: $wire.entangle('customerSearch'),
        serverCustomerResults: $wire.entangle('customerSearchResults'),
        serverSelectedCustomer: $wire.entangle('selectedCustomer'),
        localCart: @json(array_values($quoterItems)), // Inicializar con datos del servidor
        localCustomers: [], // Caché de clientes
        selectedLocalCustomer: null,
        currentQuoteUuid: null, // UUID de la cotización actual (para edición)
        lastSync: null, // Marca de tiempo de la última sincronización completa
        isPushingToStore: false, // BLOQUEO: Evita que el servidor sobreescriba cambios mientras estamos subiendo datos local -> server
        lockTimeout: null,
        
        // Estados para el swipe de los items del carrito
        swipeStates: {}, 
        
        // Cola de tareas secuencial para evitar bloqueos en IndexedDB
        syncQueue: Promise.resolve(),
        runInQueue(task) {
            this.syncQueue = this.syncQueue.then(() => task());
            return this.syncQueue;
        },

        setLock() {
            this.isPushingToStore = true;
            if (this.lockTimeout) clearTimeout(this.lockTimeout);
            this.lockTimeout = setTimeout(() => {
                this.isPushingToStore = false;
                console.log('🔓 Bloqueo de sincronización liberado (Interaction timeout)');
            }, 2000);
        },

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
            // 0. Limpieza forzada vía señal local (Ej: desde Sidebar)
            if (localStorage.getItem('quoter_clear') === '1') {
                console.log('🧹 Detectada señal de limpieza local, vaciando estado...');
                localStorage.removeItem('quoter_clear'); // Consumir la señal
                
                const db = await this.getDb();
                if (db) {
                    await db.estado_quoter.delete('actual');
                    // Limpiar sesión en servidor
                    await $wire.clearQuoter();
                    
                    // Limpiar variables reactivas en memoria
                    this.localCart = [];
                    this.selectedLocalCustomer = null;
                    this.currentQuoteUuid = null;
                }
            }

            // Escuchar evento de carga de datos para edición
            window.addEventListener('load-customer-data', (event) => {
                const data = event.detail.customer || event.detail[0]?.customer || event.detail;
                console.log('[Mobile] Cargando para edición:', data);
                this.newOfflineCustomer = {
                    id: data.id || null,
                    typeIdentificationId: data.typeIdentificationId || 1,
                    identification: data.identification || '',
                    businessName: data.businessName || '',
                    firstName: data.firstName || '',
                    lastName: data.lastName || '',
                    phone: data.phone || data.business_phone || '',
                    address: data.address || '',
                    billingEmail: data.billingEmail || '',
                    createUser: false,
                    route_id: data.route_id || @js($newCustomerRouteId)
                };
                this.showOfflineCreateForm = true;
            });

            // 0. Cargar estado persistido (Carrito y Cliente) desde IndexedDB al arrancar
            await this.loadPersistedState();

            // Manejar evento de recuperación de conexión (Online)
            window.addEventListener('online', async () => {
                this.isOnline = true;
                console.log('🌐 Conexión recuperada - Iniciando sincronización de carrito');
                
                // Activar el bloqueo para que el evento 'cart-updated' de Livewire sea ignorado
                // hasta que hayamos terminado de subir nuestra versión local.
                this.isPushingToStore = true;

                try {
                    // Sincronización ESPEJO: Forzar que el servidor tenga exactamente lo mismo que local
                    this.serverCustomerSearch = this.localSearch;
                    await $wire.set('customerSearch', this.localSearch);
                    
                    // Prioridad 1: Sincronizar CLIENTE seleccionado localmente al servidor
                    if (this.selectedLocalCustomer && !String(this.selectedLocalCustomer.id).startsWith('temp-')) {
                        console.log('👤 Sincronizando cliente al volver online:', this.selectedLocalCustomer.id);
                        await $wire.selectCustomer(this.selectedLocalCustomer.company_id || this.selectedLocalCustomer.id);
                    }

                    // Prioridad 2: Si tenemos items locales, subirlos al servidor inmediatamente
                    if (this.localCart.length > 0) {
                        console.log('🛒 Subiendo carrito local al servidor:', this.localCart.length, 'items');
                        await $wire.syncLocalCart(JSON.parse(JSON.stringify(this.localCart)));
                        console.log('✅ Carrito local sincronizado con éxito');
                    }
                } catch (e) { 
                    console.error('❌ Error al sincronizar datos al volver online:', e); 
                } finally {
                    setTimeout(() => {
                        this.isPushingToStore = false;
                        console.log('🔓 Bloqueo de sincronización liberado');
                    }, 500);
                }

                await this.persistState();
            });

            window.addEventListener('customer-selected', async (event) => {
                const customer = event.detail.customer || event.detail[0]?.customer || null;
                console.log('👤 Evento customer-selected recibido:', customer?.businessName || customer?.firstName || 'NULL');

                // Sincronizar selección del servidor al motor local
                this.selectedLocalCustomer = customer ? JSON.parse(JSON.stringify(customer)) : null;

                // forzar limpieza del servidor para que no nos devuelva los items "fantasmas".
                if (this.localCart.length === 0 && this.isOnline) {
                        await $wire.syncLocalCart([]);
                }
                
                // Refrescar lista de productos desde el servidor (esto actualiza logos/precios específicos)
                await this.syncFullCatalogAuto(); 
                await this.persistState();
            });

            // Listener para redirección segura (evita errores de conexión de PHP en móvil)
            window.addEventListener('quote-saved-redirect', (event) => {
                const url = event.detail.url || event.detail[0]?.url || '/tenant/quoter/mobile';
                console.log('🚀 Redirección local solicitada por el servidor:', url);
                window.location.href = url;
            });

            // Manejar evento de pérdida de conexión (Offline)
            window.addEventListener('offline', () => {
                this.handleOffline();
            });

            // Polling de seguridad: Verificar conexión cada 3 segundos
            setInterval(() => {
                if (!navigator.onLine && this.isOnline) {
                    this.handleOffline();
                } else if (navigator.onLine && !this.isOnline && !this.forceOffline) {
                    this.isOnline = true;
                }
            }, 3000);

            // --- SINCRONIZACIÓN SEGMENTADA ---
            window.addEventListener('sync-started', async (event) => {
                this.runInQueue(async () => {
                    console.log('🔄 [SYNC] Iniciando limpieza...');
                    const db = await this.getDb();
                    if (!db) return;
                    this.syncing = true;
                    try {
                        await db.productos.clear();
                        await db.clientes.clear();
                    } catch (e) { console.error('❌ Error limpiando:', e); }
                });
            });

            window.addEventListener('sync-customers-chunk', async (event) => {
                this.runInQueue(async () => {
                    const data = event.detail[0] || event.detail;
                    const customers = data.customers;
                    const db = await this.getDb();
                    if (db && customers) {
                        try {
                            await db.clientes.bulkPut(customers);
                        } catch (e) { console.error('❌ Error guardando clientes:', e); }
                    }
                });
            });

            window.addEventListener('sync-products-chunk', async (event) => {
                this.runInQueue(async () => {
                    const data = event.detail[0] || event.detail;
                    const { products } = data;
                    const db = await this.getDb();
                    if (db && products) {
                        try {
                            await db.productos.bulkPut(products);
                        } catch (e) { console.error('❌ Error guardando productos:', e); }
                    }
                });
            });

            window.addEventListener('sync-finished', async (event) => {
                this.runInQueue(async () => {
                    this.syncing = false;
                    this.lastSync = new Date().toISOString();

                    // Guardar versión del catálogo para evitar re-sync innecesario
                    const syncData = event.detail || (Array.isArray(event.detail) ? event.detail[0] : {});
                    const newVersion = syncData.version || null;
                    if (newVersion) {
                        localStorage.setItem('catalog_version', newVersion);
                        console.log('💾 Versión de catálogo guardada:', newVersion);
                    }

                    try {
                        await this.persistState();
                        await this.syncPendingOrders();
                    } finally {
                        try { console.timeEnd('🧪 [Sync Full]'); } catch(e) {}
                    }

                    // Solo recargar productos locales si estamos realmente offline o forzando offline
                    if (!this.isOnline || this.forceOffline) {
                        await this.loadLocalProducts();
                    }

                    await this.persistState();

                    // Si está online, refrescar Livewire para asegurar que la sesión del servidor
                    // y el estado de Alpine sean idénticos
                    if (this.isOnline) {
                        $wire.$refresh();
                    }
                });
            });

            // Si el servidor confirma que el catálogo no cambió, no hacer nada
            window.addEventListener('sync-not-needed', (event) => {
                const data = event.detail || (Array.isArray(event.detail) ? event.detail[0] : {});
                console.log('✅ [Sync] Catálogo actualizado, omitiendo sincronización.', data.version || '');
                this.syncing = false;
            });

            window.addEventListener('products-updated', async (event) => {
                if (this.isOnline) {
                    const products = event.detail[0]?.products || [];
                    // Merge: preservar cantidades locales para productos que ya están en el carrito
                    this.displayProducts = products.map(p => {
                        const inCart = this.localCart.find(c => c.id === p.id);
                        return inCart
                            ? { ...p, quantity: inCart.quantity, selected_price: inCart.price, price_label: inCart.price_label }
                            : p;
                    });
                    await this.saveToLocalCache(products);
                }
            });

            window.addEventListener('cart-updated', async (event) => {
                // EXTREMADAMENTE IMPORTANTE: Si estamos en proceso de subir nuestros datos locales,
                // IGNORAMOS lo que venga del servidor, porque viene con el estado "viejo".
                if (this.isPushingToStore) {
                    console.warn('⚠️ Ignorando cart-updated del servidor (Bloqueo activo por Sincronización Upstream)');
                    return;
                }

                if (this.isOnline) {
                    const items = event.detail.items || event.detail[0]?.items || [];
                    console.log('📥 Sincronizando carrito desde el servidor:', items.length, 'items');
                    
                    this.localCart = items.map(item => ({
                        id: item.id,
                        name: item.name,
                        sku: item.sku,
                        price: item.price,
                        price_label: item.price_label,
                        quantity: item.quantity
                    }));
                    await this.persistState();
                    
                    // IMPORTANTE: Sincronizar también displayProducts si estamos online
                    // para que la UI se actualice inmediatamente sin esperar loadLocalProducts
                    this.displayProducts.forEach(p => {
                        const cartItem = this.localCart.find(item => item.id === p.id);
                        if (cartItem) {
                            p.quantity = cartItem.quantity;
                            p.selected_price = cartItem.price;
                            p.price_label = cartItem.price_label;
                        } else {
                            p.quantity = 0;
                        }
                    });
                }
            });

            window.addEventListener('customer-selected', async (event) => {
                const customer = event.detail.customer || event.detail[0]?.customer || null;
                if (this.isOnline) {
                    this.selectedLocalCustomer = customer ? JSON.parse(JSON.stringify(customer)) : null;
                    await this.persistState();
                }
            });
            
            // Carga inicial
            if (!this.isOnline || this.forceOffline) {
                await this.loadLocalProducts();
            } else if (this.displayProducts.length === 0) {
                await this.loadLocalProducts();
            }

            // Sincronización diferida (Ahora mucho más rápida)
            setTimeout(async () => {
                if (navigator.onLine && !this.syncing) {
                    console.time('🧪 [Sync Full]');
                    await this.syncFullCatalogAuto();
                    await this.syncPendingOrders();
                }
            }, 500);

            // Sincronización periódica (cada 10 min está bien para no saturar)
            setInterval(async () => {
                if (this.isOnline && !this.syncing && !this.forceOffline) {
                    const timeSinceLast = new Date() - new Date(this.lastSync);
                    if (timeSinceLast > (15 * 60 * 1000)) {
                        await this.syncFullCatalogAuto();
                    }
                }
            }, 60000);

            this.$watch('localCart', async () => { 
                console.log('🛒 Carrito local cambiado, persistiendo...');
                await this.persistState(); 
            }, { deep: true });
            this.$watch('selectedLocalCustomer', async () => { await this.persistState(); });
            this.$watch('currentQuoteUuid', (val) => { console.log('🆔 UUID Actual:', val); });

            // Sincronizar búsqueda al cambiar de modo (Online <-> Offline) - ESPEJO TOTAL
            this.$watch('isOnline', (val) => {
                if (val) {
                    // Al pasar a ONLINE: Asegurar que el servidor reciba lo que se escribió offline
                    this.serverCustomerSearch = this.localSearch;
                    $wire.set('customerSearch', this.localSearch);
                } else {
                    // Al pasar a OFFLINE: Asegurar que el motor local reciba lo que se escribió online
                    this.localSearch = this.serverCustomerSearch;
                    this.searchLocalCustomer(this.localSearch);
                }
            });
        },

        handleOffline() {
            this.isOnline = false;
            
            // CAPTURA CRÍTICA: Si el servidor tiene un cliente seleccionado, pasarlo a local inmediatamente
            if (this.serverSelectedCustomer && !this.selectedLocalCustomer) {
                console.log('🔌 Capturando selección del servidor para modo offline:', this.serverSelectedCustomer.businessName || this.serverSelectedCustomer.firstName);
                this.selectedLocalCustomer = JSON.parse(JSON.stringify(this.serverSelectedCustomer));
                this.persistState();
            }

            this.loadLocalProducts();
            console.log('🔌 Modo Offline activado');
        },

        toggleForceOffline() {
            this.forceOffline = !this.forceOffline;
            if (this.forceOffline) {
                // Al forzar offline, llevar la búsqueda actual al motor local
                if (this.serverCustomerSearch) {
                    this.localSearch = this.serverCustomerSearch;
                    this.searchLocalCustomer(this.localSearch);
                }
                this.handleOffline();
                this.isOnline = false;
            } else if (navigator.onLine) {
                // Al volver online, sincronizar búsqueda hacia el servidor - ESPEJO
                this.serverCustomerSearch = this.localSearch;
                this.isOnline = true;
                window.dispatchEvent(new Event('online'));
            }
        },

        async loadPersistedState() {
            const db = await this.getDb();
            if (!db) return;
            try {
                const state = await db.estado_quoter.get('actual');
                if (state) {
                    // CRÍTICO: Si estamos Online y el servidor ya nos mandó items en localCart,
                    // NO debemos sobrescribirlos con la caché local de IndexedDB (que podría estar vacía o vieja).
                    // Solo sobrescribimos si el carrito actual está vacío o si estamos Offline.
                    if (!this.isOnline || this.forceOffline || this.localCart.length === 0) {
                        if (state.cart && state.cart.length > 0) {
                            this.localCart = state.cart;
                            console.log('📦 Carrito restaurado desde memoria local (Session vacía o Offline):', this.localCart.length);
                        }
                    } else {
                        console.log('ℹ️ Conservando carrito de sesión (Prioridad Online):', this.localCart.length);
                    }
                    
                    this.lastSync = state.lastSync || null;
                    
                    // El cliente y el UUID sí los restauramos siempre desde el estado persistido
                    this.selectedLocalCustomer = state.customer || this.selectedLocalCustomer || null;
                    this.currentQuoteUuid = state.uuid || null;
                    console.log('🆔 UUID de cotización cargado:', this.currentQuoteUuid);
                }
            } catch (e) { console.error('❌ Error persistencia:', e); }
        },

        async persistState() {
            const db = await this.getDb();
            if (!db) return;
            try {
                await db.estado_quoter.put({
                    id: 'actual',
                    cart: JSON.parse(JSON.stringify(this.localCart)),
                    customer: JSON.parse(JSON.stringify(this.selectedLocalCustomer)),
                    uuid: this.currentQuoteUuid,
                    lastSync: this.lastSync,
                    timestamp: new Date().toISOString()
                });
            } catch (e) { console.error('❌ Error persistiendo:', e); }
        },

        async syncPendingOrders() {
            if (!this.isOnline) return;
            const db = await this.getDb();
            if (!db) return;
            const pending = await db.pedidos.where('sincronizado').equals(0).toArray();
            if (pending.length === 0) return;
            this.syncing = true;
            for (const order of pending) {
                try {
                    const response = await $wire.processOfflineOrder(order);
                    if (response && response.success) {
                        await db.pedidos.update(order.id || order.uuid, { sincronizado: 1 });
                    }
                } catch (e) { console.error('❌ Error sincronizando:', e); }
            }
            this.syncing = false;
        },

        async saveLocalOrder() {
            console.log('🚀 [OfflineSave] Iniciando proceso de guardado local...');
            
            if (this.localCart.length === 0) {
                console.warn('⚠️ [OfflineSave] Carrito vacío');
                alert('Carrito vacío: Agrega productos antes de finalizar.');
                return;
            }

            if (!this.selectedLocalCustomer && @json(auth()->user()->profile_id) != 17) {
                console.warn('⚠️ [OfflineSave] Cliente no seleccionado');
                alert('Cliente requerido: Selecciona un cliente para continuar.');
                return;
            }

            // 1. Mostrar modal de confirmación nativo
            this.showConfirmSave = true;
        },

        async doSaveLocalOrder() {
            this.showConfirmSave = false;
            console.log('🚀 [OfflineSave] Procesando guardado local definitivo...');

            console.log('📂 [OfflineSave] Obteniendo base de datos...');
            const db = await this.getDb();
            if (!db) {
                console.error('❌ [OfflineSave] No se pudo obtener la base de datos (window.db)');
                Swal.fire('Error de sistema', 'La base de datos local no está disponible.', 'error');
                return;
            }

            const orderUuid = this.currentQuoteUuid || ('local-' + Date.now() + '-' + Math.random().toString(36).substr(2, 9));
            const orderData = {
                uuid: orderUuid,
                fecha: new Date().toISOString(),
                items: JSON.parse(JSON.stringify(this.localCart)),
                customer: this.selectedLocalCustomer ? JSON.parse(JSON.stringify(this.selectedLocalCustomer)) : null,
                total: this.localCart.reduce((sum, item) => sum + (item.price * item.quantity), 0),
                sincronizado: 0,
                observaciones: this.observaciones || '',
                estado: 'edited'
            };

            console.log('💾 [OfflineSave] Intentando guardar en IndexedDB...', orderData);
            
            try {
                const id = await db.pedidos.put(orderData);
                console.log('✅ [OfflineSave] Guardado exitoso con ID:', id);

                // Mostramos el modal de éxito nativo
                this.showSuccessSave = true;
                
                // Limpieza de estado
                this.localCart = [];
                this.selectedLocalCustomer = null; 
                this.currentQuoteUuid = null;
                await this.persistState();

                // Redirección después de 2 segundos (tiempo para ver el éxito)
                setTimeout(() => {
                    window.location.href = "/tenant/quoter/mobile";
                }, 2000);

            } catch (err) {
                console.error('❌ [OfflineSave] Error crítico al escribir en IndexedDB:', err);
                alert('Error al guardar: ' + err.message);
            }
        },

        getProductQuantity(productId) {
            if (this.isOnline) {
                const prod = this.displayProducts.find(p => p.id === productId);
                return prod ? (prod.quantity || 0) : 0;
            } else {
                const item = this.localCart.find(item => item.id === productId);
                return item ? item.quantity : 0;
            }
        },

        updateLocalQuantity(productId, delta) {
            const item = this.localCart.find(item => item.id === productId);
            if (item) {
                item.quantity += delta;
                if (item.quantity <= 0) {
                    this.localCart = this.localCart.filter(i => i.id !== productId);
                }
            }
        },

        setLocalQuantity(productId, value) {
            const qty = parseInt(value);
            if (isNaN(qty) || qty <= 0) {
                this.localCart = this.localCart.filter(i => i.id !== productId);
            } else {
                const item = this.localCart.find(item => item.id === productId);
                if (item) item.quantity = qty;
            }
            this.persistState();
        },

        async addToLocalCart(product, price, priceLabel) {
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
            await this.persistState();
        },

        async loadLocalProducts() {
            const db = await this.getDb();
            if (!db) return;
            try {
                let collection = db.productos.orderBy('id').reverse();
                let result = [];
                if (this.localSearch) {
                    const searchLower = this.localSearch.toLowerCase();
                    result = await collection.filter(p => {
                        const name = (p.name || '').toLowerCase();
                        const dispName = (p.display_name || '').toLowerCase();
                        const sku = (p.sku || '').toLowerCase();
                        return name.includes(searchLower) || dispName.includes(searchLower) || sku.includes(searchLower);
                    }).limit(50).toArray();
                } else {
                    result = await collection.limit(50).toArray();
                }
                
                // Mapear productos inyectando cantidades desde el carrito local para evitar parpadeos
                this.displayProducts = result.map(p => {
                    const cartItem = this.localCart.find(item => item.id === p.id);
                    return { 
                        ...p, 
                        quantity: cartItem ? cartItem.quantity : 0, 
                        selected_price: cartItem ? cartItem.price : null,
                        price_label: cartItem ? cartItem.price_label : null
                    };
                });
            } catch (error) { console.error('❌ Error local products:', error); }
        },

        // --- Lógica de Carrito Instantáneo (Optimista) ---
        addItemInstant(product, price, label) {
            this.setLock();
            // 1. Actualizar localCart (Persistencia local)
            const existing = this.localCart.find(item => item.id === product.id);
            if (existing) {
                existing.quantity++;
            } else {
                this.localCart.push({
                    id: product.id,
                    name: product.display_name || product.name,
                    sku: product.sku,
                    price: price,
                    price_label: label,
                    quantity: 1
                });
            }

            // 2. Actualizar displayProducts (Interfaz inmediata)
            const prod = this.displayProducts.find(p => p.id === product.id);
            if (prod) {
                prod.quantity = (prod.quantity || 0) + 1;
                prod.selected_price = price;
                prod.price_label = label;
            }

            this.persistState();

            // 3. Sincronizar con servidor si hay red
            if (this.isOnline && !this.forceOffline) {
                // Usamos addToQuoter para la primera vez, el servidor ya tiene lógica defensiva
                $wire.addToQuoter(product.id, price, label);
            }
        },

        updateQuantityInstant(productId, newQty) {
            this.setLock();
            newQty = parseInt(newQty);
            if (isNaN(newQty) || newQty < 0) newQty = 0;

            // 1. Actualizar localCart
            const itemIndex = this.localCart.findIndex(item => item.id === productId);
            if (itemIndex !== -1) {
                if (newQty === 0) {
                    this.localCart.splice(itemIndex, 1);
                } else {
                    this.localCart[itemIndex].quantity = newQty;
                }
            }

            // 2. Actualizar displayProducts (UI del catálogo)
            const prod = this.displayProducts.find(p => p.id === productId);
            if (prod) {
                prod.quantity = newQty;
                if (newQty === 0) {
                    prod.selected_price = null;
                    prod.price_label = null;
                }
            }

            this.persistState();

            // 3. Sincronizar con servidor
            if (this.isOnline && !this.forceOffline) {
                // Enviamos valor absoluto para máxima precisión
                $wire.updateQuantity(productId, newQty);
            }
        },

        clearCartInstant() {
            this.setLock();
            // 1. Vaciar localCart
            this.localCart = [];

            // 2. Resetear displayProducts (UI del catálogo)
            this.displayProducts.forEach(p => {
                p.quantity = 0;
                p.selected_price = null;
                p.price_label = null;
            });

            this.persistState();

            // 3. Sincronizar con servidor
            if (this.isOnline && !this.forceOffline) {
                $wire.call('clearCart');
            }
        },

        removeItemInstant(productId) {
            this.setLock();
            // 1. Quitar de localCart
            const index = this.localCart.findIndex(i => i.id === productId);
            if (index !== -1) {
                this.localCart.splice(index, 1);
            }

            // 2. Resetear en displayProducts
            const prod = this.displayProducts.find(p => p.id === productId);
            if (prod) {
                prod.quantity = 0;
                prod.selected_price = null;
                prod.price_label = null;
            }

            this.persistState();

            // 3. Sincronizar con servidor
            if (this.isOnline && !this.forceOffline) {
                $wire.call('removeFromQuoter', productId);
            }
        },

        async saveToLocalCache(products) {
            if (!products || products.length === 0) return;
            const db = await this.getDb();
            if (!db) return;
            try {
                const cleanProducts = products.map(p => {
                    const clean = { ...p };
                    delete clean.quantity;
                    delete clean.selected_price;
                    delete clean.price_label;
                    return clean;
                });
                await db.productos.bulkPut(cleanProducts);
            } catch (error) { console.error('❌ Error incremental cache:', error); }
        },


        getVisiblePrices(allPrices) {
            if (!allPrices) return {};
            const profileId = @json(auth()->user()->profile_id);
            if (profileId == 17) {
                const filtered = {};
                if (allPrices['Precio Regular']) filtered['Precio Regular'] = allPrices['Precio Regular'];
                return filtered;
            }
            if (profileId == 4) {
                const filtered = {};
                for (const [label, price] of Object.entries(allPrices)) {
                    const lowerLabel = label.toLowerCase();
                    if (lowerLabel === 'p1' || lowerLabel === 'precio base') filtered[label] = price;
                }
                return filtered;
            }
            return allPrices;
        },

        editOfflineCustomer(customerData = null) {
            const data = customerData || this.selectedLocalCustomer;
            if (!data) return;
            
            console.log('✏️ Editando cliente (Híbrido):', data);
            
            // Mapeo inteligente de campos (servidor vs local)
            this.newOfflineCustomer = {
                id: data.id || data.company_id,
                typeIdentificationId: data.typeIdentificationId || 1,
                identification: data.identification || '',
                businessName: data.businessName || '',
                firstName: data.firstName || '',
                lastName: data.lastName || '',
                phone: data.business_phone || data.phone || '',
                address: data.address || '',
                billingEmail: data.billingEmail || '',
                createUser: data.createUser === 1,
                route_id: data.route_id || @js($newCustomerRouteId)
            };
            this.showOfflineCreateForm = true;
        },

        async saveOfflineCustomer() {
            // Validación dinámica según tipo de identificación
            const isCC = this.newOfflineCustomer.typeIdentificationId == 1;
            const hasIdentification = !!this.newOfflineCustomer.identification;
            const hasName = isCC 
                ? (this.newOfflineCustomer.firstName && this.newOfflineCustomer.lastName)
                : !!this.newOfflineCustomer.businessName;

            if (!hasIdentification || !hasName) {
                const message = isCC 
                    ? 'Identificación, Nombre y Apellido son obligatorios' 
                    : 'Identificación y Razón Social son obligatorios';
                Swal.fire('Error', message, 'error');
                return;
            }

            // Concatenar nombre si es C.C. para mantener consistencia en businessName
            if (isCC) {
                this.newOfflineCustomer.businessName = (this.newOfflineCustomer.firstName + ' ' + (this.newOfflineCustomer.lastName || '')).trim();
            }

            // MODAL LOADING
            Swal.fire({
                title: 'Procesando...',
                text: 'Guardando datos del cliente',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });

            try {
                // CASO 1: ONLINE - Guardar en el servidor via Livewire
                if (this.isOnline && !this.forceOffline) {
                    console.log('🌐 Guardando cliente en modo ONLINE...');
                    try {
                        const result = await $wire.saveSimplifiedCustomer(this.newOfflineCustomer);
                        
                        if (result && result.id) {
                            Swal.close();
                            console.log('✅ Cliente guardado en servidor:', result);
                            
                            this.showOfflineCreateForm = false;
                            this.newOfflineCustomer = { id: null, typeIdentificationId: 1, identification: '', businessName: '', firstName: '', lastName: '', phone: '', address: '', billingEmail: '', createUser: false, route_id: @js($newCustomerRouteId) };
                            return;
                        } else {
                            // Si el servidor retorna null, es un error controlado (validación) 
                            // El servidor ya disparó el toast de error.
                            Swal.close();
                            return;
                        }
                    } catch (netError) {
                        console.warn('❌ Fallo de red en guardado online, intentando offline como respaldo...', netError);
                        // No retornamos, dejamos que fluya al Caso 2 (Offline)
                    }
                }

                // CASO 2: OFFLINE - Guardar en IndexedDB
                console.log('📴 Guardando cliente en modo OFFLINE...');
                const db = await this.getDb();
                if (!db) {
                     Swal.close();
                     Swal.fire('Error', 'No hay conexión a la base de datos local.', 'error');
                     return;
                }

                const isEdit = this.newOfflineCustomer.id && String(this.newOfflineCustomer.id).startsWith('temp-');
                const tempId = isEdit ? this.newOfflineCustomer.id : 'temp-' + Date.now(); 
                
                const cleanCustomer = {
                    id: tempId,
                    identification: this.newOfflineCustomer.identification,
                    businessName: this.newOfflineCustomer.businessName.toUpperCase(),
                    firstName: (this.newOfflineCustomer.firstName || this.newOfflineCustomer.businessName).toUpperCase(),
                    lastName: (this.newOfflineCustomer.lastName || '').toUpperCase(),
                    address: this.newOfflineCustomer.address || 'Sin dirección',
                    business_phone: this.newOfflineCustomer.phone || '0000000',
                    typeIdentificationId: parseInt(this.newOfflineCustomer.typeIdentificationId),
                    billingEmail: this.newOfflineCustomer.billingEmail || '',
                    createUser: this.newOfflineCustomer.createUser ? 1 : 0,
                    isTemporary: true,
                    sincronizado: 0,
                    term_id: 1,
                    price_list_id: 1,
                    route_id: this.newOfflineCustomer.route_id || null
                };
                
                // Usar put para actualizar si existe
                await db.clientes.put(cleanCustomer);
                
                // Asegurar que el cliente se agregue a la lista local y se seleccione
                this.localCustomers = [cleanCustomer];
                this.selectedLocalCustomer = cleanCustomer;
                
                // Limpiar formulario y cerrar modal
                this.showOfflineCreateForm = false;
                this.newOfflineCustomer = { id: null, typeIdentificationId: 1, identification: '', businessName: '', firstName: '', lastName: '', phone: '', address: '', billingEmail: '', createUser: false, route_id: @js($newCustomerRouteId) };
                
                await this.persistState();
                Swal.close();
                Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: isEdit ? 'Cliente actualizado localmente' : 'Cliente guardado localmente', showConfirmButton: false, timer: 2000 });

            } catch (e) { 
                console.error('❌ Error crítico en saveOfflineCustomer:', e);
                Swal.close();
                Swal.fire('Error', 'Ocurrió un error inesperado al guardar.', 'error');
            }
        },

        openManualRegistration() {
            const query = this.isOnline ? this.serverCustomerSearch : this.localSearch;
            this.newOfflineCustomer = { 
                id: null, 
                typeIdentificationId: 1, 
                identification: query, 
                businessName: '', 
                firstName: '', 
                lastName: '', 
                phone: '', 
                address: '', 
                billingEmail: '', 
                createUser: false, 
                route_id: @js($newCustomerRouteId) 
            };
            this.showOfflineCreateForm = true;
            console.log('📝 Abriendo registro manual para:', query);
        },

        async searchLocalCustomer(query) {
            const cleanQuery = query ? query.trim() : '';
            const db = await this.getDb();
            if (!db || cleanQuery.length < 3) { this.localCustomers = []; return; }
            try {
                const searchLower = cleanQuery.toLowerCase();
                this.localCustomers = await db.clientes.filter(c => {
                    const bName = (c.businessName || '').toLowerCase();
                    const fName = (c.firstName || '').toLowerCase();
                    const lName = (c.lastName || '').toLowerCase();
                    const ident = (c.identification || '').toLowerCase();
                    return bName.includes(searchLower) || fName.includes(searchLower) || lName.includes(searchLower) || ident.includes(searchLower);
                }).limit(20).toArray();
            } catch (e) { console.error('❌ Error search customer:', e); }
        },

        async syncFullCatalogAuto() {
            if (!this.isOnline || this.syncing) return;
            // Pasar la versión local al servidor; si coincide, el servidor omite el sync
            const localVersion = localStorage.getItem('catalog_version') || null;
            await $wire.syncIfNeeded(localVersion);
        }
    }));
</script>
@endscript


    {{-- Contenedor con lógica offline --}}
    <div wire:key="mobile-quoter-root-container" id="mobile-quoter-root-container" x-data="quoterOffline" 
         class="fixed inset-0 bg-gray-50 dark:bg-gray-900 flex flex-col overflow-hidden transition-all duration-300"
         :class="showOfflineCreateForm ? 'z-[9999]' : 'z-[35]'">
        
        <!-- Banner de estado Offline -->
        <div wire:key="mobile-offline-banner" x-show="!isOnline || forceOffline" 
             style="display: none;"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="-translate-y-full"
             x-transition:enter-end="translate-y-0"
             class="bg-red-600 text-white text-[10px] py-0.5 text-center font-bold sticky top-0 z-[60] flex items-center justify-center gap-2">
            <span>⚠️ MODO OFFLINE ACTIVADO</span>
            <button @click="forceOffline = false" x-show="forceOffline" class="bg-white/20 px-2 rounded">Volver a Online</button>
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
                    wire:navigate.false>
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>

                    <span class="text-sm">Regresar</span>
                </a>
                @else
                <a
                    href="{{ route('tenant.remissions', ['clear' => 1]) }}"
                    class="p-2 text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200
                        flex items-center gap-2"
                    wire:navigate.false>
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

                        <!-- Indicador Online (Alpine-driven: instantáneo, sin esperar servidor) -->
                        <div x-show="isOnline" class="contents">
                            <span x-show="localCart.reduce((s,i) => s + i.quantity, 0) > 0"
                                  x-text="localCart.reduce((s,i) => s + i.quantity, 0)"
                                  class="absolute -top-2 -right-1.5 bg-red-600 text-white text-[11px] font-black rounded-full min-w-[22px] h-[22px] flex items-center justify-center border-2 border-white dark:border-gray-800 shadow-sm animate-pulse">
                            </span>
                        </div>

                        <!-- Indicador Offline -->
                        <div x-show="!isOnline" class="contents" wire:key="hdr-offline-indicator">
                            <span wire:key="offline-badge-items" x-show="localCart.length > 0" 
                                  class="absolute -top-2 -right-1.5 bg-orange-600 text-white text-[11px] font-black rounded-full min-w-[22px] h-[22px] flex items-center justify-center border-2 border-white dark:border-gray-800 shadow-sm"
                                  x-text="localCart.reduce((sum, item) => sum + item.quantity, 0)">
                            </span>
                        </div>
                    </button>

                    <!-- Mobile menu button -->
                    <div class="flex items-center gap-1">
                        <!-- Indicador de señal / Force Offline -->


                        <button type="button" class="p-2.5 text-gray-700 dark:text-gray-300 lg:hidden" @click="sidebarOpen = true">
                            <svg class="h-7 w-7" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Boton solo para ruteros (Reposicionado) -->
            @if(auth()->check() && auth()->user()->profile_id == 4)
                <div class="px-1 mb-4">
                    <button wire:click="openRoutes"
                        class="w-full inline-flex items-center justify-center px-4 py-3 rounded-xl font-bold text-sm uppercase transition-all duration-200 bg-cyan-600 hover:bg-cyan-700 dark:bg-cyan-500 dark:hover:bg-cyan-600 text-white shadow-lg active:scale-95 focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                        <svg class="w-6 h-6 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7">
                            </path>
                        </svg>
                        Ruteros
                    </button>
                </div>
            @endif

            <!-- Banner Offline -->
            <!-- <div x-show="!isOnline" 
                 style="display: none;"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="-translate-y-full"
                 x-transition:enter-end="translate-y-0"
                 class="bg-red-600 text-white text-[10px] py-1 text-center font-bold sticky top-0 z-[60] flex items-center justify-center gap-2 mb-2 rounded shadow-sm">
                <span>⚠️ MODO OFFLINE ACTIVADO</span>
            </div> -->

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
            
            <!-- Toggle Offline (Debajo del buscador) -->
            <!-- Toggle Offline (Visible siempre para control manual) -->
            <!-- <div class="px-3 pb-2">
                <button @click="toggleForceOffline()" 
                        :class="forceOffline ? 'bg-red-100 text-red-700 border-red-300' : 'bg-gray-100/50 text-gray-500 border-gray-200'"
                        class="w-full py-2 px-3 rounded-xl text-xs font-bold uppercase flex items-center justify-between border transition-all shadow-sm active:scale-95">
                    
                    <div class="flex items-center gap-2">
                        <span :class="forceOffline ? 'bg-red-500 animate-pulse' : 'bg-green-500'" class="w-2.5 h-2.5 rounded-full"></span>
                        <span x-text="forceOffline ? 'FORZADO OFFLINE' : 'CONEXIÓN AUTOMÁTICA'"></span>
                    </div>

                    <div class="flex items-center gap-1">
                        <span x-show="!forceOffline" class="text-[10px] bg-green-200 text-green-800 px-1.5 py-0.5 rounded">Auto</span>
                        <svg x-show="forceOffline" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                    </div>
                </button>
            </div> -->


    <!-- Lista de productos UNIFICADA -->
    <div class="flex-1 overflow-y-auto pb-20">
       <div wire:ignore wire:key="mobile-product-grid-container" class="px-3 py-4 grid
    grid-cols-3
    min-[640px]:grid-cols-3
    md:grid-cols-4
    lg:grid-cols-5
    xl:grid-cols-6
    gap-3">
            <template x-for="product in displayProducts" :key="product.id">
                <div class="relative flex flex-col bg-white dark:bg-gray-800 rounded-xl border-2 transition-all duration-300 overflow-hidden"
                     :class="getProductQuantity(product.id) > 0 
                                ? 'border-indigo-500 ring-2 ring-indigo-200 dark:ring-indigo-900/40 shadow-md scale-[1.02]' 
                                : 'border-transparent shadow-sm hover:border-gray-200 dark:hover:border-gray-700'">

                    <!-- Badge de cantidad (Flotante) -->
                    <!-- Badge de cantidad (Flotante) -->
                    <div x-show="getProductQuantity(product.id) > 0" class="absolute top-2 right-2 z-10">
                        <span class="flex items-center justify-center w-7 h-7 bg-indigo-600 dark:bg-indigo-500 text-white text-xs font-bold rounded-full shadow-lg border-2 border-white dark:border-gray-800 animate-in zoom-in duration-300"
                              x-text="getProductQuantity(product.id)">
                        </span>
                    </div>

                    <!-- Imagen del producto -->
                    <div wire:key="product-img-{{ $p->id ?? 'idx' }}" class="aspect-square bg-gray-50 dark:bg-gray-700/50 flex items-center justify-center p-2 relative group">
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
                            <!-- Selector si ya está seleccionado (Tarjeta Verde Unificada) -->
                            <div wire:key="product-active-box-real" x-show="getProductQuantity(product.id) > 0" class="w-full bg-green-600 dark:bg-green-700/90 text-white rounded-lg p-2 shadow-lg animate-in zoom-in duration-200">
                                    
                                    <!-- Header: Label P1/P2 y Precio (Más compacto) -->
                                    <div class="flex justify-between items-center mb-1.5 px-0.5">
                                         <div class="flex flex-col leading-none">
                                            <span class="text-[8px] uppercase font-bold opacity-80" x-text="product.price_label || 'Precio'"></span>
                                            <span class="text-xs font-black tracking-tight" x-text="'$' + (product.selected_price ? Number(product.selected_price).toLocaleString() : Number(product.price).toLocaleString())"></span>
                                         </div>
                                    </div>

                                    <!-- Flex Columna para Cantidad y Botones -->
                                    <div class="flex flex-col gap-1.5">
                                        <!-- Cantidad arriba (Fondo oscuro sutil) -->
                                        <div class="flex justify-center items-center bg-black/10 rounded-md py-0.5">
                                             <input type="tel" 
                                                   :value="getProductQuantity(product.id)"
                                                   @change="updateQuantityInstant(product.id, $event.target.value)"
                                                   @click.stop
                                                   class="w-full text-center font-black text-xl text-white bg-transparent border-none focus:ring-0 p-0 appearance-none placeholder-white/50"
                                                   inputmode="numeric">
                                        </div>

                                        <!-- Botones abajo (Distribuidos) -->
                                        <div class="flex gap-1.5">
                                            <button @click="updateQuantityInstant(product.id, getProductQuantity(product.id) - 1)"
                                                    class="flex-1 h-9 flex items-center justify-center bg-black/20 text-white hover:bg-black/30 rounded-md transition-colors active:scale-95 shadow-sm">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M20 12H4"></path></svg>
                                            </button>
                                            
                                            <button @click="updateQuantityInstant(product.id, getProductQuantity(product.id) + 1)"
                                                    class="flex-1 h-9 flex items-center justify-center bg-white text-green-700 rounded-md shadow-md active:scale-95 transition-transform hover:bg-gray-100">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Precios para seleccionar -->
                            <div wire:key="product-prices-box-real" x-show="getProductQuantity(product.id) === 0" class="space-y-1.5">
                                    <template x-for="(price, label) in getVisiblePrices(product.all_prices || {})" :key="label">
                                        <button @click="addItemInstant(product, price, label)"
                                                class="w-full py-2 px-2 text-center rounded-lg border-2 border-green-100 dark:border-green-800 bg-green-50 dark:bg-green-900/10 text-green-700 dark:text-green-400 hover:bg-green-100 active:scale-95 transition-all flex flex-col items-center justify-center">
                                            <span class="text-[9px] uppercase font-bold opacity-60" x-text="label == 'Precio Regular' ? 'Precio' : label"></span>
                                            <span class="text-sm font-black tracking-tight" x-text="'$' + Number(price).toLocaleString()"></span>
                                        </button>
                                    </template>
                                    <template x-if="!product.all_prices || Object.keys(product.all_prices).length === 0">
                                        <div class="text-[10px] font-bold text-gray-400 italic text-center py-2 border border-dashed border-gray-200 rounded-lg">Sin precio</div>
                                    </template>
                                </div>
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
    <div wire:key="mobile-cart-modal-container" x-show="showCart || showCartModal" 
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
                        <div x-show="isOnline && (localCart.length > 0 || quoterCount > 0)" class="flex flex-col items-center justify-center">
                            <span class="text-[10px] text-red-500 font-bold uppercase leading-none mb-0.5">Limpiar</span>
                            <button
                                @click="showConfirmClear = true"
                                title="Limpiar carrito"
                                class="text-red-500 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 p-1 rounded-full hover:bg-red-50 dark:hover:bg-red-900/20 transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                </svg>
                            </button>
                        </div>

                        <!-- Botón limpiar carrito (Offline) -->
                        <template x-if="!isOnline && localCart.length > 0">
                            <button @click="showConfirmClear = true" class="text-red-500 text-[10px] font-bold uppercase underline">
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
                                <!-- Botón Editar (Híbrido) -->
                                <button
                                    @click="isOnline ? $wire.editCustomer() : editOfflineCustomer(serverSelectedCustomer)"
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
                                        <path class="opacity-75" fill="currentColor" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                </button>

                                <!-- Botón Limpiar -->
                                <button
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
                        
                        <div x-show="isOnline && !forceOffline && !serverSelectedCustomer && !selectedLocalCustomer">
                            <input
                                wire:model.live.debounce.300ms="customerSearch"
                                type="text"
                                placeholder="Escribe nombre o NIT..."
                                class="w-full px-3 py-2 text-sm border border-gray-300 rounded-lg dark:bg-gray-700 dark:border-gray-600 text-gray-900 dark:text-white focus:ring-2 focus:ring-indigo-500"
                                autocomplete="off"
                                id="customerSearchInputMobile">
                        </div>

                        <div x-show="(!isOnline || forceOffline) && !selectedLocalCustomer">
                            <input
                                x-model="localSearch"
                                @input="searchLocalCustomer($event.target.value)"
                                type="text"
                                placeholder="Buscar en memoria del celular..."
                                class="w-full px-3 py-2 text-sm border border-orange-300 rounded-lg bg-orange-50/50 dark:bg-gray-700 dark:border-orange-900/50 text-gray-900 dark:text-white focus:ring-2 focus:ring-orange-500"
                                autocomplete="off">
                        </div>

                        <!-- Resultados Offline (Solo si hay coincidencias) -->
                        <div wire:ignore wire:key="mobile-offline-customer-list" x-show="(!isOnline || forceOffline) && localCustomers.length > 0" 
                             class="absolute z-[60] left-0 right-0 w-full max-h-60 overflow-y-auto border border-orange-200 dark:border-orange-900/30 rounded-lg bg-white dark:bg-gray-800 mt-1 shadow-xl">
                            <template x-for="customer in localCustomers" :key="customer.id">
                                <div 
                                    @click="selectedLocalCustomer = customer; localCustomers = []; localSearch = ''"
                                    class="px-4 py-3 text-xs hover:bg-orange-50 dark:hover:bg-orange-900/20 cursor-pointer border-b border-gray-100 dark:border-gray-700 last:border-b-0 flex flex-col gap-1">
                                    <div class="font-mono font-bold text-gray-900 dark:text-white text-sm" x-text="customer.identification"></div>
                                    <div class="text-gray-600 dark:text-gray-300 capitalize" x-text="(customer.businessName || (customer.firstName + ' ' + customer.lastName)).toLowerCase()"></div>
                                </div>
                            </template>
                        </div>

                        <!-- Botón para Registro Manual cuando no hay resultados -->
                        <div x-show="((isOnline && !forceOffline && serverCustomerSearch.length >= 3 && serverCustomerResults.length === 0 && !serverSelectedCustomer) || 
                                     ((!isOnline || forceOffline) && localSearch.length >= 3 && localCustomers.length === 0 && !selectedLocalCustomer))"
                             class="mt-2 animate-in fade-in zoom-in duration-300">
                            
                            <button @click="openManualRegistration()"
                                    class="w-full flex items-center justify-between p-4 bg-orange-50 dark:bg-orange-900/20 border-2 border-dashed border-orange-300 dark:border-orange-800 rounded-xl text-orange-700 dark:text-orange-400 hover:bg-orange-100 dark:hover:bg-orange-800/30 transition-all active:scale-95 group">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-orange-600 text-white rounded-lg shadow-md group-hover:scale-110 transition-transform">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                                        </svg>
                                    </div>
                                    <div class="text-left">
                                        <p class="text-xs font-bold uppercase tracking-wider">No se encontró el cliente por favor registrarlo</p>
                                        <p class="text-sm font-black" x-text="'Registrar: ' + (isOnline ? serverCustomerSearch : localSearch)"></p>
                                    </div>
                                </div>
                                <svg class="w-5 h-5 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        </div>


                        <!-- Caja de Cliente seleccionado (Fallback Local / Offline) -->
                        <div x-show="((!isOnline || forceOffline) || (isOnline && !serverSelectedCustomer)) && selectedLocalCustomer" class="contents" wire:key="selected-local-cust-box">
                            <div class="bg-green-100 dark:bg-green-900/40 border border-green-200 dark:border-green-800 rounded-lg p-3 mt-1 flex justify-between items-center animate-in zoom-in duration-200"
                                :class="{ 'opacity-70 animate-pulse': isOnline && !serverSelectedCustomer }">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                        </svg>
                                        <span class="text-[10px] font-bold text-green-700 dark:text-green-300 uppercase tracking-wider">Cliente Offline</span>
                                    </div>
                                    <div class="text-gray-900 dark:text-white font-black text-sm" x-text="selectedLocalCustomer?.businessName ? (selectedLocalCustomer?.businessName || (selectedLocalCustomer?.firstName + ' ' + selectedLocalCustomer?.lastName)) : (selectedLocalCustomer ? (selectedLocalCustomer.firstName + ' ' + selectedLocalCustomer.lastName) : '')"></div>
                                    <div class="text-[10px] text-gray-500 dark:text-gray-400 font-mono" x-text="selectedLocalCustomer?.identification || ''"></div>
                                </div>
                                <div class="flex items-center gap-1">
                                    <!-- Botón Editar Offline -->
                                    <button @click="editOfflineCustomer()" class="p-2 text-blue-600 dark:text-blue-400 hover:bg-blue-100 dark:hover:bg-blue-800/30 rounded-full transition-colors" title="Editar datos locales">
                                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </button>
                                    
                                    <!-- Botón Quitar -->
                                    <button @click="selectedLocalCustomer = null" class="p-2 text-green-600 dark:text-green-400 hover:bg-green-200 dark:hover:bg-green-800/50 rounded-full transition-colors">
                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Resultados Online (Livewire) -->
                        <div x-show="isOnline" class="contents" wire:key="online-client-results-box">
                            <div>
                                @if(count($customerSearchResults) > 0)
                                <div id="customerSearchResultsMobile" class="max-h-60 overflow-y-auto border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 mt-2 shadow-md">
                                    @foreach($customerSearchResults as $customer)
                                    <div wire:click="selectCustomer({{ $customer['id'] }})" 
                                         wire:key="customer-item-{{ $customer['id'] }}"
                                         class="px-4 py-3 text-xs hover:bg-gray-50 dark:hover:bg-gray-600 cursor-pointer border-b border-gray-100 dark:border-gray-600 last:border-b-0 flex flex-col gap-1">
                                        <div class="font-mono font-bold text-gray-900 dark:text-white text-sm">{{ $customer['identification'] }}</div>
                                        <div class="text-gray-600 dark:text-gray-300 capitalize">{{ strtolower($customer['display_name']) }}</div>
                                    </div>
                                    @endforeach
                                </div>
                                @endif
                                
                                @if(strlen($customerSearch) >= 2 && count($customerSearchResults) === 0)
                                    <div class="text-center py-4 text-gray-500 text-xs">No se encontraron clientes por favor registrarlo</div>
                                @endif
                            </div>
                        </div>
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
                    <div x-show="localCart.length === 0" class="contents" wire:key="cart-empty-msg">
                        <div class="text-center py-8">
                            <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <p class="text-gray-500 dark:text-gray-400">Carrito vacío</p>
                            <p x-show="!isOnline" class="text-xs text-orange-500 mt-2">Modo Offline</p>
                        </div>
                    </div>
                    
                    <!-- Lista de Items (ÚNICA para Online y Offline) -->
                    <div class="space-y-4" wire:ignore>
                        <template x-for="(item, index) in localCart" :key="item.id">
                           <div wire:key="cart-item-{{ $item['id'] ?? 'idx' }}" class="relative overflow-hidden rounded-xl mb-3 touch-pan-y shadow-sm border"
                                 :class="!isOnline ? 'border-orange-200 dark:border-orange-900/30 bg-white dark:bg-gray-700' : 'border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-700'"
                                 @touchstart="if(!swipeStates[item.id]) swipeStates[item.id] = {startX: 0, currentX: 0}; swipeStates[item.id].startX = $event.touches[0].clientX"
                                 @touchmove="let diff = $event.touches[0].clientX - swipeStates[item.id].startX; swipeStates[item.id].currentX = diff > 0 ? Math.min(80, diff) : Math.max(-80, diff)"
                                 @touchend="if (Math.abs(swipeStates[item.id].currentX) > 40) { removeItemInstant(item.id); } swipeStates[item.id].currentX = 0"
                                 @touchcancel="if(swipeStates[item.id]) swipeStates[item.id].currentX = 0">

                                <!-- Fondo Rojo (Swipe Bidireccional) -->
                                <div class="absolute inset-0 bg-red-500 flex items-center justify-between px-4 text-white"
                                     x-show="swipeStates[item.id] && Math.abs(swipeStates[item.id].currentX) > 5"
                                     :style="`opacity: ${swipeStates[item.id] ? Math.abs(swipeStates[item.id].currentX) / 60 : 0}`">
                                    <svg class="w-6 h-6 transform rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </div>

                                <!-- Contenido de la Tarjeta -->
                                <div class="relative p-3 flex items-center justify-between bg-inherit transition-transform duration-100 ease-out"
                                     x-init="if (!swipeStates[item.id]) swipeStates[item.id] = {startX: 0, currentX: 0}"
                                     :style="`transform: translateX(${swipeStates[item.id] ? swipeStates[item.id].currentX : 0}px)`">
                                    
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
                                                @click.stop="updateQuantityInstant(item.id, item.quantity - 1)" 
                                                class="p-2 px-3 text-gray-500 hover:text-gray-700 active:bg-gray-200 rounded-l-lg transition-colors">
                                                -
                                            </button>
                                            
                                            <!-- Cantidad -->
                                            <span class="px-2 text-sm font-black text-gray-900 dark:text-white min-w-[1.5rem] text-center" x-text="item.quantity"></span>
                                            
                                            <!-- Botón Más -->
                                            <button 
                                                @click.stop="updateQuantityInstant(item.id, item.quantity + 1)" 
                                                class="p-2 px-3 text-gray-500 hover:text-gray-700 active:bg-gray-200 rounded-r-lg transition-colors">
                                                +
                                            </button>
                                        </div>

                                        <!-- Eliminar (Botón explícito también) -->
                                        <button 
                                            @click.stop="removeItemInstant(item.id)" 
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
                <div wire:key="cart-modal-footer-box" x-show="localCart.length > 0" class="px-4 py-4 border-t border-gray-200 dark:border-gray-700 space-y-4 flex-shrink-0 bg-white dark:bg-gray-800">

                    @if($showRemisionModal)
                    {{-- ===== FORMULARIO CONFIRMAR PEDIDO (INLINE DENTRO DEL CARRITO) ===== --}}
                    <div class="space-y-4">
                        <div class="flex items-center gap-3 pb-3 border-b border-gray-200 dark:border-gray-700">
                            <div class="w-8 h-8 rounded-lg bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white">Confirmar Pedido</h3>
                                <p class="text-xs text-gray-500 dark:text-slate-400">Seleccione entrega y método de pago</p>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-600 dark:text-slate-300 uppercase tracking-wider mb-2">Tipo de Entrega</label>
                            <select wire:model="selectedDeliveryTypeId"
                                class="w-full px-3 py-3 text-sm border border-gray-300 dark:border-slate-600 rounded-xl bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">— Seleccionar tipo de entrega —</option>
                                @foreach($availableDeliveryTypes as $type)
                                    <option value="{{ $type['id'] }}">{{ $type['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-600 dark:text-slate-300 uppercase tracking-wider mb-2">Método de Pago</label>
                            <select wire:model="selectedMethodPaymentId"
                                class="w-full px-3 py-3 text-sm border border-gray-300 dark:border-slate-600 rounded-xl bg-white dark:bg-slate-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                <option value="">— Seleccionar método de pago —</option>
                                @foreach($availableMethodPayments as $method)
                                    <option value="{{ $method['id'] }}">{{ $method['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="flex gap-3 pt-2">
                            <button wire:click="$set('showRemisionModal', false)"
                                class="flex-1 py-3 text-sm font-medium text-gray-700 dark:text-slate-300 bg-gray-100 dark:bg-slate-700 rounded-xl hover:bg-gray-200 dark:hover:bg-slate-600 transition-colors">
                                Cancelar
                            </button>
                            <button wire:click="confirmarPedido"
                                wire:loading.attr="disabled"
                                class="flex-1 py-3 text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 disabled:opacity-60 rounded-xl flex items-center justify-center gap-2 transition-colors">
                                <svg wire:loading.remove wire:target="confirmarPedido" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                <svg wire:loading wire:target="confirmarPedido" class="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                                Confirmar
                            </button>
                        </div>
                    </div>
                    @else

                    <!-- Observaciones -->
                    @if(auth()->user()->profile_id != 17)
                    <div class="w-full">

                        <button @click="showObservations = !showObservations"
                            class="w-full flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors">

                            <span class="text-sm font-bold text-gray-900 dark:text-white flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                Observaciones:
                            </span>

                            <svg class="w-5 h-5 text-gray-600 dark:text-gray-400 transform transition-transform"
                                :class="{ 'rotate-180': showObservations }"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div wire:key="cart-observations-input-box" x-show="showObservations" x-transition class="mt-3">
                            <textarea
                                x-model="observaciones"
                                rows="4"
                                placeholder="Escribe observaciones adicionales..."
                                class="block w-full p-2 text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300
                               dark:bg-gray-700 dark:border-gray-600 dark:text-white"></textarea>
                        </div>
                    </div>
                    @endif

                    <!-- Total -->
                    <div wire:key="cart-modal-total-display" class="flex justify-between items-center text-lg font-bold text-gray-900 dark:text-white">
                        <span>Total:</span>
                        <div x-show="isOnline" class="contents" wire:key="total-online-box">
                            <span wire:key="total-val-online">${{ number_format($totalAmount) }}</span>
                        </div>
                        <div x-show="!isOnline" class="contents" wire:key="total-offline-box">
                            <span wire:key="total-val-offline" x-text="'$' + localCart.reduce((sum, item) => sum + (item.price * item.quantity), 0).toLocaleString()"></span>
                        </div>
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
                                        Editar Pedido
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
                                    Pedido ya generado
                                </div>
                            @else
                                <button wire:click="abrirModalRemision"
                                    wire:loading.attr="disabled"
                                    wire:target="abrirModalRemision"
                                    class="w-full font-medium py-3 px-4 rounded-lg transition-colors flex items-center justify-center text-sm disabled:opacity-50 bg-blue-600 hover:bg-blue-700 text-white">
                                    <svg wire:loading.remove wire:target="abrirModalRemision" class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/>
                                    </svg>
                                    <svg wire:loading wire:target="abrirModalRemision" class="w-4 h-4 mr-2 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                                        <path class="opacity-75" fill="currentColor" d="m4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/>
                                    </svg>
                                    <span wire:loading.remove wire:target="abrirModalRemision">Confirmar pedido</span>
                                    <span wire:loading wire:target="abrirModalRemision">Verificando...</span>
                                </button>
                            @endif
                        @endif
                    </div>


                    @else

                    <div wire:key="checkout-actions-container" class="space-y-2">

                        <div x-show="isOnline ? !serverSelectedCustomer : !selectedLocalCustomer" class="contents" wire:key="checkout-warn-box">
                            <button disabled
                                class="w-full bg-gray-400 dark:bg-gray-600 text-gray-200 dark:text-gray-400 font-medium py-3 px-4 rounded-lg cursor-not-allowed flex items-center justify-center">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.802-.833-2.572 0L4.242 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                </svg>
                                Seleccione un Cliente
                            </button>
                        </div>

                        <!-- MODO ONLINE (CON CLIENTE) -->
                        <div x-show="isOnline && serverSelectedCustomer" class="contents" wire:key="save-online-btn-box">
                            <div class="w-full">
                                <!-- BOTÓN GUARDAR (ONLINE) -->
                                <button wire:click="{{ $isEditing ? 'updateQuote' : 'saveQuote' }}"
                                    wire:loading.attr="disabled"
                                    class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-3 px-4 rounded-lg flex items-center justify-center shadow-lg active:scale-95 transition-all">
                                    <span wire:loading.remove wire:target="{{ $isEditing ? 'updateQuote' : 'saveQuote' }}" class="flex items-center">
                                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                        {{ $isEditing ? 'Actualizar Orden' : 'Finalizar Pedido' }}
                                    </span>
                                    <span wire:loading wire:target="saveQuote" class="flex items-center">
                                        <svg class="animate-spin h-5 w-5 mr-3 text-white" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                        Guardando...
                                    </span>
                                </button>
                            </div>
                        </div>

                        <!-- MODO OFFLINE (CON CLIENTE) -->
                        <div x-show="(!isOnline || forceOffline) && selectedLocalCustomer" class="contents" wire:key="save-offline-btn-box">
                            <button @click="saveLocalOrder()"
                                class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-3 px-4 rounded-lg flex items-center justify-center shadow-lg active:scale-95 transition-all">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                                </svg>
                                Guardar Orden Offline
                            </button>
                        </div>

                        @if(auth()->user()->profile_id == 17)
                        <!-- Botones TAT (Perfil 17 - SOLO ONLINE) -->
                        <div x-show="isOnline" class="contents" wire:key="tat-profile-btns">
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
                    {{-- FIN @if($showRemisionModal) --}}
    </div>
</div>
</div>

{{-- MODAL NATIVO DE CONFIRMACIÓN OFFLINE --}}
<div x-show="showConfirmSave" 
     style="display: none;"
     class="fixed inset-0 z-[10000] flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 scale-95"
     x-transition:enter-end="opacity-100 scale-100"
     x-transition:leave="transition ease-in duration-200"
     x-transition:leave-start="opacity-100 scale-100"
     x-transition:leave-end="opacity-0 scale-95">
    
    <div @click.away="showConfirmSave = false" 
         class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden transform transition-all">
        
        <div class="p-6 text-center">
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 dark:bg-green-900/30 mb-4">
                <svg class="h-8 w-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">¿Confirmar pedido?</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                ¿Deseas finalizar este pedido y guardarlo localmente en el dispositivo?
            </p>
        </div>
        
        <div class="bg-gray-50 dark:bg-gray-700/50 px-6 py-4 flex flex-col gap-3">
            <button @click="doSaveLocalOrder()" 
                    class="w-full bg-green-600 hover:bg-green-700 text-white font-black py-4 px-4 rounded-xl shadow-md flex items-center justify-center gap-2 text-base transition-all active:scale-95 ring-2 ring-green-500 ring-offset-2 dark:ring-offset-gray-800">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                </svg>
                <span>SÍ, FINALIZAR PEDIDO</span>
            </button>
            
            <button @click="showConfirmSave = false" 
                    class="w-full bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-semibold py-3 px-4 rounded-xl border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-750 transition-all active:scale-95">
                Regresar
            </button>
        </div>
    </div>
</div>

{{-- MODAL NATIVO DE ÉXITO OFFLINE --}}

<div x-show="showSuccessSave" 
     style="display: none;"
     class="fixed inset-0 z-[10001] flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100">
    
    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden transform text-center p-8">
        <div class="mx-auto flex items-center justify-center h-20 w-20 rounded-full bg-green-100 dark:bg-green-900/30 mb-6">
            <svg class="h-10 w-10 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path>
            </svg>
        </div>
        <h3 class="text-2xl font-bold text-gray-900 dark:text-white mb-2">¡Pedido Guardado!</h3>
        <p class="text-sm text-gray-500 dark:text-gray-400">El pedido quedó registrado en el celular con éxito.</p>
    </div>
</div>

{{-- MODAL NATIVO DE LIMPIEZA DE CARRITO --}}
<div x-show="showConfirmClear" 
     style="display: none;"
     class="fixed inset-0 z-[10000] flex items-center justify-center p-4 bg-gray-900/60 backdrop-blur-sm"
     x-transition:enter="transition ease-out duration-300"
     x-transition:enter-start="opacity-0 scale-95"
     x-transition:enter-end="opacity-100 scale-100">
    
    <div @click.away="showConfirmClear = false" class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden transform">
        <div class="p-6 text-center">
            <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-red-100 dark:bg-red-900/30 mb-4">
                <svg class="h-8 w-8 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
            </div>
            <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">¿Limpiar carrito?</h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">Se eliminarán todos los productos. El cliente seleccionado se mantendrá.</p>
        </div>
        <div class="bg-gray-50 dark:bg-gray-700/50 px-6 py-4 flex flex-col gap-3">
            <button @click="showConfirmClear = false; clearCartInstant()" 
                class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-3 px-4 rounded-xl shadow-lg transition-all active:scale-95">
                Sí, limpiar carrito
            </button>
            <button @click="showConfirmClear = false" 
                class="w-full bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-semibold py-3 px-4 rounded-xl border border-gray-200 dark:border-gray-600 hover:bg-gray-50 dark:hover:bg-gray-750 transition-all active:scale-95">
                Cancelar
            </button>
        </div>
    </div>
</div>
</div>
    @include('livewire.tenant.quoter.components.customer-quick-form')


@script
<script>
    window.addEventListener('show-toast', (event) => {
        const data = event.detail;
        const payload = Array.isArray(data) ? data[0] : data;
        console.log('Mobile Toast triggered:', payload);
        
        // Usar un Mixin para evitar cerrar modales abiertos si es posible, 
        // o simplemente no disparar el toast si hay un modal de confirmación crítico.
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 1500,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        });

        // Verificamos si hay un modal abierto que no sea un toast
        if (Swal.isVisible() && !Swal.isTimerRunning()) {
            console.warn('⚠️ [Toast] Saltando toast para no cerrar modal abierto');
            return;
        }

        Toast.fire({
            icon: payload.type || 'info',
            title: payload.message,
            background: '#ffffff',
            color: '#111827'
        });
    });

    $wire.on('confirm-add-duplicate', (data) => {
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
                $wire.call('forceAddToQuoter',
                    payload.productId,
                    payload.selectedPrice,
                    payload.priceLabel
                );
            }
        });
    });

    // Función global para confirmar limpiar carrito (llamada desde Alpine/HTML)
    window.confirmClearCart = function() {
        // Obtenemos una referencia al alcance de Alpine para activar el modal nativo
        const el = document.querySelector('[x-data="quoterOffline"]');
        if (el && el.__x && el.__x.$data) {
            el.__x.$data.showConfirmClear = true;
        } else {
            // Fallback si Alpine no está listo
            if (confirm('¿Deseas limpiar el carrito?')) {
                Livewire.dispatch('clearCart');
            }
        }
    }

    // Función global para manejar Enter en búsqueda de productos
    window.handleProductSearchKeydown = function(event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            const products = document.querySelectorAll('[wire\\:click*="addToQuoter"]');
            if (products.length > 0) {
                for (let product of products) {
                    if (!product.disabled && !product.hasAttribute('disabled')) {
                        product.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        setTimeout(() => { product.click(); }, 300);
                        break;
                    }
                }
            }
        }
    }

    // Keyboard navigation for mobile customer search
    var selectedCustomerIndexMobile = -1;

    window.handleCustomerSearchKeydownMobile = function(event) {
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
                    results[selectedCustomerIndexMobile].click();
                }
                break;
            case 'Escape':
                event.preventDefault();
                selectedCustomerIndexMobile = -1;
                updateCustomerSelectionMobile(results);
                document.getElementById('customerSearchInputMobile').value = '';
                $wire.set('customerSearch', '');
                break;
        }
    }

    function updateCustomerSelectionMobile(results) {
        results.forEach(result => { result.classList.remove('bg-blue-100', 'dark:bg-blue-700'); });
        if (selectedCustomerIndexMobile >= 0 && results[selectedCustomerIndexMobile]) {
            const selected = results[selectedCustomerIndexMobile];
            selected.classList.add('bg-blue-100', 'dark:bg-blue-700');
            const container = document.getElementById('customerSearchResultsMobile');
            if (container) {
                const containerRect = container.getBoundingClientRect();
                const selectedRect = selected.getBoundingClientRect();
                if (selectedRect.bottom > containerRect.bottom) {
                    selected.scrollIntoView({ block: 'end', behavior: 'smooth' });
                } else if (selectedRect.top < containerRect.top) {
                    selected.scrollIntoView({ block: 'start', behavior: 'smooth' });
                }
            }
        }
    }

    // Reset selection when customer search results change
    document.addEventListener('livewire:updated', function() {
        selectedCustomerIndexMobile = -1;
    });
</script>
@endscript

<!-- Routes Modal -->
@if($showRoutesModal)
    @livewire('tenant.vnt-company.company-routes-modal', ['showModal' => true], key('routes-modal-mobile'))
@endif

</div>
