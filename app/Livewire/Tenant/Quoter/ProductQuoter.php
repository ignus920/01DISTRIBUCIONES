<?php

namespace App\Livewire\Tenant\Quoter;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Tenant\Items\Items;
use App\Services\Tenant\TenantManager;
use App\Models\Auth\Tenant;
use App\Models\Tenant\Customer\VntCompany;
use App\Models\Tenant\Quoter\VntQuote;
use App\Models\Tenant\Quoter\VntDetailQuote;
use \App\Models\Tenant\Items\Category;
use Illuminate\Support\Facades\Log;
use App\Models\Tenant\Quoter\TatRestockList;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Tenant\Movements\InvReason;
use App\Models\Tenant\Remissions\InvRemissions;
use App\Models\Tenant\Remissions\InvDetailRemissions;
use Illuminate\Support\Facades\Hash;
use App\Models\Auth\User;
use App\Models\Auth\UserTenant;
use App\Livewire\Tenant\VntCompany\Services\CompanyService;
use App\Models\TAT\Routes\TatRoutes;
use App\Models\Tenant\Customer\TatCompanyRoute;

class ProductQuoter extends Component
{
    use WithPagination;

    // Routes modal properties
    public $showRoutesModal = false;

    public $search = '';
    public $perPage = 12;
    public $sortField = 'id';
    public $sortDirection = 'desc';
    public $selectedProducts = [];
    public $quoterItems = [];
    public $totalAmount = 0;
    public $showCartModal = false;
    public $viewType = 'desktop'; // 'desktop' o 'mobile'
    public $customerSearch = '';
    public $customerSearchResults = [];
    public $selectedCustomer = null;
    public $observaciones = null;
    public $searchingCustomer = false;
    public $showCreateCustomerForm = false;
    public $showCreateCustomerButton = false;
    public $editingCustomerId = null;
    public $editingQuoteId = null;
    public $editingRemissionId = null;
    public $isEditing = false;
    public $showObservations = false;
    // Nueva propiedad para la categoría seleccionada
    public $selectedCategory = '';
    // Propiedad para filtrar por día de venta
    public $selectedSaleDay = '';

    // Propiedades para edición de restock (TAT)
    public $editingRestockOrder = null;
    public $isEditingRestock = false;

    // Propiedades para el modal de confirmación
    public $showConfirmationModal = false;
    public $selectedReason = null;
    public $availableReasons = [];
    public $confirmationLoading = false;

    public $quoteHasRemission = false;
    public $cartHasChanges = false; // Nueva propiedad para rastrear cambios en el carrito
    public $mappedProducts = []; // Lista de productos mapeada para la vista
    public $availableRoutes = []; // Rutas disponibles para administradores
    public $newCustomerRouteId = null; // Ruta seleccionada para el nuevo cliente
    protected $listeners = [
        'customer-created' => 'onCustomerCreated',
        'vnt-company-saved' => 'onCustomerCreated',
        'customer-updated' => 'onCustomerUpdated',
        'customer-form-cancelled' => 'cancelCreateCustomer',
        'routes-modal-closed' => 'closeRoutesModal'
    ];

    protected $queryString = [
        'search' => ['except' => ''],
        'perPage' => ['except' => 12],
    ];

    // Propiedades para optimización
    protected $cachedCategories = null;
    protected $cachedSaleDays = null;

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingPerPage()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirection = 'asc';
        }

        $this->sortField = $field;
        $this->resetPage();
    }

    public function mount($quoteId = null, $restockOrder = null, $remissionId = null)
    {
        // Obtener viewType de la ruta o usar desktop por defecto
        $this->viewType = request()->route('viewType', 'desktop');
        $this->ensureTenantConnection();

        // Si se solicita limpiar explícitamente (desde botón '+' nueva cotización)
        if (request()->query('clear')) {
            session()->forget('quoter_items');
        }

        // Inicializar quoteHasRemission como false por defecto
        $this->quoteHasRemission = false;

        // 📝 LOG DEBUG: Parámetros del Mount
        Log::info('ProductQuoter Mount DEBUG', [
            'quoteId' => $quoteId,
            'remissionId' => $remissionId,
            'restockOrder' => $restockOrder,
            'viewType' => $this->viewType,
            'clear_param' => request()->query('clear')
        ]);

        // Si se pasa un quoteId, estamos editando una cotización
        if ($quoteId) {
            $this->loadQuoteForEditing($quoteId);
        }
        // Si se pasa un remissionId, estamos editando una remisión
        elseif ($remissionId || request()->route('remissionId')) {
             $id = $remissionId ?: request()->route('remissionId');
             $this->loadRemissionForEditing($id);
        }
        elseif ($restockOrder || request()->query('restockOrder')) {
            $orderToLoad = $restockOrder ?: request()->query('restockOrder');
            $this->loadRestockForEditing($orderToLoad);
        } elseif (request()->query('editPreliminary') === 'true') {
            $this->loadPreliminaryRestockForEditing();
        } else {
            $this->quoterItems = session('quoter_items', []);
            Log::info('Cargando items desde sesión', ['count' => count($this->quoterItems)]);
        }

        // Cargar rutas según perfil
        if (auth()->check()) {
            if (auth()->user()->profile_id == 2) {
                // Administrador: Cargar todas las rutas con su vendedor
                $this->availableRoutes = TatRoutes::with('salesman')->orderBy('name')->get()->toArray();
            } elseif (auth()->user()->profile_id == 4) {
                // Vendedor: Obtener su ruta principal (primera encontrada)
                // Asegurar consulta a la base de datos central
                $sellerRoute = DB::connection('central')->table('tat_routes')
                    ->where('salesman_id', auth()->id())
                    ->whereNull('deleted_at')
                    ->first();
                
                $this->newCustomerRouteId = $sellerRoute ? $sellerRoute->id : null;
                Log::info('Ruta automática para vendedor cargada', [
                    'seller_id' => auth()->id(), 
                    'route_id' => $this->newCustomerRouteId,
                    'route_name' => $sellerRoute ? $sellerRoute->name : 'N/A'
                ]);
            }
        }

        $this->calculateTotal();
        Log::info('Mount fin - Total calculado', ['total' => $this->totalAmount]);
    }

    /**
     * Carga una remisión existente para su edición en el cotizador
     */
    public function loadRemissionForEditing($remissionId)
    {
        Log::info('🔄 Cargando remisión para edición', ['remissionId' => $remissionId]);

        $this->ensureTenantConnection();
        
        try {
            $remission = InvRemissions::with(['quote.customer', 'details.item'])->findOrFail($remissionId);
            
            // 1. Cargar Cliente
            if ($remission->quote && $remission->quote->customer) {
                $customer = $remission->quote->customer;
                $this->selectedCustomer = [
                    'id' => $customer->id,
                    'businessName' => $customer->businessName,
                    'firstName' => $customer->firstName,
                    'lastName' => $customer->lastName,
                    'identification' => $customer->identification,
                    'billingEmail' => $customer->billingEmail,
                ];
                $this->customerSearch = $customer->identification;
            }

            // 2. Cargar Items
            $this->quoterItems = [];
            foreach ($remission->details as $detail) {
                if ($detail->item) {
                    $this->quoterItems[] = [
                        'id' => $detail->item->id,
                        'name' => $detail->item->name ?? $detail->description,
                        'sku' => $detail->item->sku ?? '',
                        'price' => $detail->value, // Precio unitario guardado en el detalle
                        'price_label' => 'Precio Registrado',
                        'quantity' => $detail->quantity,
                        'description' => $detail->description,
                    ];
                }
            }

            // 3. Configurar estado de edición
            $this->editingRemissionId = $remissionId;
            $this->isEditing = true;

            session(['quoter_items' => $this->quoterItems]);
            $this->cartHasChanges = false;
            
            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => 'Remisión #' . $remission->consecutive . ' cargada para edición'
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error cargando remisión: ' . $e->getMessage());
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Error al cargar la remisión: ' . $e->getMessage()
            ]);
        }
    }

    private function ensureTenantConnection()
    {
        $tenantId = session('tenant_id');

        if (!$tenantId) {
            return redirect()->route('tenant.select');
        }

        $tenant = Tenant::find($tenantId);

        if (!$tenant) {
            session()->forget('tenant_id');
            return redirect()->route('tenant.select');
        }

        // Establecer conexión tenant
        $tenantManager = app(TenantManager::class);
        $tenantManager->setConnection($tenant);

        // Inicializar tenancy
        tenancy()->initialize($tenant);
    }


    //funcion para renderizar los productos en la vista
    public function render()
    {
        $this->ensureTenantConnection();

        $products = Items::query()
            ->active()
            ->with('principalImage')
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('internal_code', 'like', '%' . $this->search . '%')
                    ->orWhere('sku', 'like', '%' . $this->search . '%')
                    ->orWhere('description', 'like', '%' . $this->search . '%');
            })
            ->when($this->selectedCategory, function ($query) {
                $query->where('categoryId', $this->selectedCategory);
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        $this->mappedProducts = collect($products->items())->map(function($p) {
            $allPrices = $p->all_prices;
            
            // FILTRADO DE PRECIOS POR PERFIL
            if (auth()->user()->profile_id == 17) {
                // Perfil Tienda (TAT): Solo Precio Regular
                $allPrices = collect($allPrices)->filter(fn($val, $label) => $label === 'Precio Regular')->toArray();
            } elseif (auth()->user()->profile_id == 4) {
                // Perfil Vendedor: Solo Precio Base (P1)
                $allPrices = collect($allPrices)->filter(fn($val, $label) => 
                    strtolower($label) === 'p1' || strtolower($label) === 'precio base'
                )->toArray();
            }

            // Buscar si el producto ya está en el carrito para inyectar su estado
            $index = $this->findProductInQuoter($p->id);
            $quantity = 0;
            $selectedPrice = null;
            $priceLabel = null;

            if ($index !== false) {
                $item = $this->quoterItems[$index];
                $quantity = $item['quantity'];
                $selectedPrice = $item['price'];
                $priceLabel = $item['price_label'];
            }

            return [
                'id' => $p->id,
                'name' => $p->display_name,
                'sku' => $p->sku,
                'display_name' => $p->display_name,
                'category_id' => $p->categoryId,
                'price' => $p->price,
                'all_prices' => $allPrices,
                'image_url' => $p->principalImage ? $p->principalImage->getImageUrl() : null,
                'quantity' => $quantity,
                'selected_price' => $selectedPrice,
                'price_label' => $priceLabel,
            ];
        })->toArray();

        $viewName = $this->viewType === 'mobile'
            ? 'livewire.tenant.quoter.components.mobile-product-quoter'
            : 'livewire.tenant.quoter.components.desktop-product-quoter';

        // Despachar evento para actualizaciones reactivas
        $this->dispatch('products-updated', [
            'products' => $this->mappedProducts
        ]);

        // Asegurar que el carrito esté sincronizado con Alpine.js en cada renderizado
        $this->dispatch('cart-updated', [
            'items' => $this->quoterItems
        ]);

        return view($viewName, [
            'products' => $products,
            'mappedProducts' => $this->mappedProducts
        ])->layout('layouts.app');
    }

    /**
     * Sincroniza TODO el catálogo de productos y clientes para uso Offline.
     * Envía los datos en paquetes (chunks) para asegurar la descarga completa de los 1000+ items.
     */
    public function syncFullCatalog()
    {
        $this->ensureTenantConnection();
        Log::info('📦 Iniciando sincronización segmentada de catálogo');

        try {
            // 1. Obtener todos los productos activos
            $allProducts = Items::query()
                ->active()
                ->with(['principalImage', 'invValues'])
                ->get()
                ->map(function($p) {
                    $allPrices = $p->all_prices;
                    
                    // FILTRADO DE PRECIOS POR PERFIL (Para Offline)
                    if (auth()->user()->profile_id == 17) {
                        $allPrices = collect($allPrices)->filter(fn($val, $label) => $label === 'Precio Regular')->toArray();
                    } elseif (auth()->user()->profile_id == 4) {
                        $allPrices = collect($allPrices)->filter(fn($val, $label) => 
                            strtolower($label) === 'p1' || strtolower($label) === 'precio base'
                        )->toArray();
                    }

                    return [
                        'id' => $p->id,
                        'name' => $p->display_name,
                        'sku' => $p->sku,
                        'display_name' => $p->display_name,
                        'category_id' => $p->categoryId,
                        'price' => $p->price,
                        'all_prices' => $allPrices,
                        'image_url' => $p->principalImage ? $p->principalImage->getImageUrl() : null
                    ];
                });

            // 2. Obtener lista de clientes con datos extendidos para selección completa offline
            $allCustomers = VntCompany::query()
                ->select('id', 'businessName', 'firstName', 'lastName', 'identification', 'billingEmail', 'typeIdentificationId')
                ->with([
                    'mainWarehouse' => function($q) {
                        $q->select('id', 'companyId', 'address', 'district', 'cityId');
                    },
                    'activeContacts' => function($q) {
                        $q->select('vnt_contacts.id', 'vnt_contacts.warehouseId', 'vnt_contacts.business_phone', 'vnt_contacts.personal_phone');
                    }
                ])
                ->get()
                ->map(fn($c) => [
                    'id' => $c->id,
                    'businessName' => $c->businessName,
                    'firstName' => $c->firstName,
                    'lastName' => $c->lastName,
                    'identification' => $c->identification,
                    'billingEmail' => $c->billingEmail,
                    'typeIdentificationId' => $c->typeIdentificationId,
                    'phone' => $c->activeContacts->first()?->business_phone 
                             ?? $c->activeContacts->first()?->personal_phone ?? '',
                    'address' => $c->mainWarehouse->address ?? '',
                    'district' => $c->mainWarehouse->district ?? '',
                    'cityId' => $c->mainWarehouse->cityId ?? null,
                ]);

            $totalProducts = $allProducts->count();
            
            // 3. SECUENCIA DE SINCRONIZACIÓN POR PAQUETES
            // A. Inicio: Avisar al celular que limpie su base de datos local
            $this->dispatch('sync-started', [
                'total' => $totalProducts,
                'customersCount' => $allCustomers->count()
            ]);

            $customersCount = $allCustomers->count();
            Log::info("👥 Despachando {$customersCount} clientes para sincronización");

            // B. Enviar Clientes (en un paquete único)
            $this->dispatch('sync-customers-chunk', [
                'customers' => $allCustomers->toArray()
            ]);

            // C. Enviar Productos en paquetes de 300
            $chunks = $allProducts->chunk(300);
            foreach ($chunks as $index => $chunk) {
                $this->dispatch('sync-products-chunk', [
                    'products' => $chunk->values()->toArray(),
                    'chunkIndex' => $index + 1,
                    'totalChunks' => $chunks->count()
                ]);
            }

            // D. Fin: Avisar que todo terminó correctamente
            $this->dispatch('sync-finished', [
                'total' => $totalProducts
            ]);

            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => 'Sincronizacion finalizada'
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error en sincronización segmentada: ' . $e->getMessage());
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Fallo al sincronizar: ' . $e->getMessage()
            ]);
        }
    }

    // Método para obtener las categorías (con caché)
    public function getCategories()
    {
        if ($this->cachedCategories === null) {
            $this->cachedCategories = Category::where('status', 1)->get();
        }
        return $this->cachedCategories;
    }

    // Método para obtener los días de venta disponibles del vendedor (con caché)
    public function getSaleDays()
    {
        if ($this->cachedSaleDays === null) {
            $this->ensureTenantConnection();

            $days = DB::select("
                SELECT DISTINCT tr.sale_day
                FROM tat_routes tr
                WHERE tr.salesman_id = ?
                ORDER BY tr.sale_day
            ", [auth()->id()]);

            $this->cachedSaleDays = array_map(function ($day) {
                return $day->sale_day;
            }, $days);
        }

        return $this->cachedSaleDays;
    }

    public function addToQuoter($productId, $selectedPrice, $priceLabel)
    {
        // Validar si el producto ya está confirmado (Perfil 17)
        $confirmedItem = $this->checkConfirmedProductStatus($productId);

        if ($confirmedItem) {
            $this->dispatch('confirm-load-order', [
                'orderNumber' => $confirmedItem->order_number,
                'message' => "Este producto ya se encuentra en la orden confirmada #{$confirmedItem->order_number}. ¿Desea cargar esa orden para editarla?"
            ]);
            return;
        }

        $this->performAddToQuoter($productId, $selectedPrice, $priceLabel);
    }

    #[On('force-add-to-quoter')]
    public function forceAddToQuoter($productId, $selectedPrice, $priceLabel)
    {
        Log::info('forceAddToQuoter triggered (Direct Call or Event)', [
            'productId' => $productId,
            'selectedPrice' => $selectedPrice,
            'priceLabel' => $priceLabel
        ]);

        if ($productId) {
            $this->performAddToQuoter($productId, $selectedPrice, $priceLabel);
        } else {
            Log::warning('forceAddToQuoter: Missing productId');
        }
    }


    private function performAddToQuoter($productId, $selectedPrice, $priceLabel, $quantity = 1)
    {
        Log::info('🛒 performAddToQuoter iniciado', [
            'productId' => $productId,
            'isEditing' => $this->isEditing,
            'editingQuoteId' => $this->editingQuoteId,
            'quoteHasRemission_antes' => $this->quoteHasRemission
        ]);

        // Verificar si el producto ya está en el cotizador (sin consulta DB)
        $existingIndex = $this->findProductInQuoter($productId);

        if ($existingIndex !== false) {
            // Si ya existe, sumar la cantidad nueva
            $this->quoterItems[$existingIndex]['quantity'] += $quantity;
        } else {
            // Obtener el producto solo cuando es necesario
            $this->ensureTenantConnection();
            $product = Items::find($productId);

            if (!$product) {
                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => 'Producto no encontrado'
                ]);
                return;
            }

            // Si no existe, agregarlo con el precio seleccionado
            $this->quoterItems[] = [
                'id' => $product->id,
                'name' => $product->display_name,
                'sku' => $product->sku,
                'price' => $selectedPrice,
                'price_label' => $priceLabel,
                'quantity' => $quantity,
                'description' => $product->description,
            ];
        }

        // Marcar que el carrito tiene cambios
        $this->cartHasChanges = true;

        // Optimización: Solo guardar en sesión si realmente cambió
        session(['quoter_items' => $this->quoterItems]);

        // Calcular total de forma más eficiente
        $this->calculateTotal();

        // Si estamos editando una cotización que tiene remisión, deshabilitar el botón
        $this->checkAndDisableIfHasRemission();

        Log::info('🛒 performAddToQuoter finalizado', [
            'productId' => $productId,
            'isEditing' => $this->isEditing,
            'editingQuoteId' => $this->editingQuoteId,
            'quoteHasRemission_despues' => $this->quoteHasRemission,
            'cartHasChanges' => $this->cartHasChanges
        ]);

        // Emitir evento para mirroring offline
        $this->dispatch('cart-updated', [
            'items' => $this->quoterItems
        ]);

        // Toast más rápido sin información innecesaria
        $this->dispatch('show-toast', [
            'type' => 'success',
            'message' => 'Agregado al carrito'
        ]);
    }

    public function updateQuantity($productId, $quantity)
    {
        $this->ensureTenantConnection();
        $index = $this->findProductInQuoter($productId);

        if ($index === false) return;

        if ($quantity <= 0) {
            $this->removeFromQuoter($productId);
            return;
        }

        $this->quoterItems[$index]['quantity'] = $quantity;

        // Marcar que el carrito tiene cambios
        $this->cartHasChanges = true;

        session(['quoter_items' => $this->quoterItems]);
        $this->calculateTotal();

        // Si estamos editando una cotización que tiene remisión, deshabilitar el botón
        $this->checkAndDisableIfHasRemission();

        // Emitir evento para mirroring offline
        $this->dispatch('cart-updated', [
            'items' => $this->quoterItems
        ]);
    }

    public function removeFromQuoter($productId)
    {
        $index = $this->findProductInQuoter($productId);
        if ($index !== false) {
            unset($this->quoterItems[$index]);
            $this->quoterItems = array_values($this->quoterItems); // Reindexar array

            // Marcar que el carrito tiene cambios
            $this->cartHasChanges = true;

            session(['quoter_items' => $this->quoterItems]);
            $this->calculateTotal();

            // Si estamos editando una cotización que tiene remisión, deshabilitar el botón
            $this->checkAndDisableIfHasRemission();

            // Emitir evento para mirroring offline
            $this->dispatch('cart-updated', [
                'items' => $this->quoterItems
            ]);

            $this->dispatch('show-toast', [
                'type' => 'info',
                'message' => 'Producto removido del cotizador'
            ]);
        }
    }





    //funcion limpiar cotizacion completa con cliente y carrito de compras
    public function clearQuoter()
    {
        $this->selectedCustomer = null;
        $this->customerSearch = '';
        $this->showCreateCustomerForm = false;
        $this->showCreateCustomerButton = false;
        $this->quoterItems = [];
        $this->quoteHasRemission = false; // Resetear el estado de remisión
        $this->editingQuoteId = null; // Limpiar ID de cotización en edición
        $this->isEditing = false; // Limpiar estado de edición
        session()->forget('quoter_items');
        $this->calculateTotal();
        $this->showCartModal = false;

        $this->dispatch('show-toast', [
            'type' => 'info',
            'message' => 'Cotizador limpiado'
        ]);

        // Sincronizar con Alpine.js/IndexedDB
        $this->dispatch('cart-updated', ['items' => []]);
        $this->dispatch('customer-selected', ['customer' => null]);
    }

    //funcion para limpiar solo los productos del carrito (mantiene cliente)
    public function clearCart()
    {
        $this->quoterItems = [];
        session()->forget('quoter_items');
        $this->calculateTotal();

        $this->dispatch('show-toast', [
            'type' => 'info',
            'message' => 'Carrito limpiado. Cliente mantenido.'
        ]);

        // Sincronizar con Alpine.js/IndexedDB para limpiar localmente
        $this->dispatch('cart-updated', ['items' => []]);
    }

    /**
     * Sincronizar el carrito local (Alpine) con el servidor (Livewire)
     * Útil al recuperar conexión o tras cambios offline masivos.
     */
    public function syncLocalCart($items, $actionAfterSync = null)
    {
        error_log("🛒 [CRITICAL] syncLocalCart llamado con " . count($items) . " items");
        Log::info('🛒 [SYNC] syncLocalCart INVOCADO', ['count' => count($items)]);

        $this->ensureTenantConnection();

        
        // Validar estructura mínima y filtrar items inválidos
        $validItems = [];
        foreach ($items as $item) {
            if (isset($item['id']) && isset($item['quantity']) && $item['quantity'] > 0) {
                $validItems[] = [
                    'id' => $item['id'],
                    'name' => $item['name'] ?? 'Producto',
                    'sku' => $item['sku'] ?? '',
                    'price' => $item['price'] ?? 0,
                    'price_label' => $item['price_label'] ?? 'Precio',
                    'quantity' => $item['quantity'],
                    'description' => $item['description'] ?? '',
                ];
            }
        }

        $this->quoterItems = $validItems;
        $this->cartHasChanges = true;
        session(['quoter_items' => $this->quoterItems]);
        $this->calculateTotal();
        $this->checkAndDisableIfHasRemission();

        // Si se solicitó una acción específica tras la sincronización, ejecutarla
        if ($actionAfterSync) {
            if (method_exists($this, $actionAfterSync)) {
                return $this->{$actionAfterSync}();
            }
        }

        // Si no hay acción, emitimos evento de confirmación de fin de sync masiva
        $this->dispatch('cart-sync-complete');
    }






    /**
     * Guardar una cotización sin crear remisiones
     * 
     * IMPORTANTE: Esta función SOLO crea la cotización y sus detalles.
     * NO crea remisiones de inventario. Las remisiones se crean SOLO cuando
     * el usuario hace clic en "Confirmar Pedido" (confirmarPedido()).
     * 
     * Esto permite que los vendedores creen múltiples cotizaciones sin
     * afectar el inventario disponible.
     */
    public function saveQuote()
    {
        if (empty($this->quoterItems)) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'No hay productos en el cotizador'
            ]);
            return;
        }

        if (!$this->selectedCustomer) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Debe seleccionar un cliente para crear la cotización'
            ]);
            return;
        }

        $this->ensureTenantConnection();

        try {
            DB::beginTransaction();

            // 1. Obtener consecutivo
            $lastQuote = VntQuote::orderBy('consecutive', 'desc')->first();
            $nextConsecutive = $lastQuote ? $lastQuote->consecutive + 1 : 1;

            // 2. Crear la cotización
            $quote = VntQuote::create([
                'consecutive' => $nextConsecutive,
                'status' => 'REGISTRADO',
                'typeQuote' => 'POS', // Valor por defecto para este flujo
                'customerId' => $this->selectedCustomer['id'],
                'warehouseId' => session('warehouse_id', 1),
                'userId' => auth()->id(),
                'observations' => $this->observaciones,
                'branchId' => session('branch_id', 1)
            ]);

            // 3. Crear detalles
            foreach ($this->quoterItems as $item) {
                VntDetailQuote::create([
                    'quantity' => $item['quantity'],
                    'tax_percentage' => 0,
                    'price' => $item['price'],
                    'quoteId' => $quote->id,
                    'itemId' => $item['id'],
                    'description' => $item['name'],
                    'priceList' => $item['price']
                ]);
            }

            DB::commit();

            // Limpiar
            $this->clearQuoter();

            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => 'Cotización #' . $quote->consecutive . ' creada exitosamente'
            ]);

            // Redirigir
            $routeName = $this->viewType === 'mobile'
                ? 'tenant.quoter.mobile'
                : 'tenant.quoter.desktop';

            return redirect()->to(route($routeName));

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creando cotización: ' . $e->getMessage());
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Error al crear la cotización: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Lógica interna para guardar cambios sin redireccionar ni limpiar el estado
     * Útil para autoguardado antes de confirmar un pedido
     */
    private function saveChangesInternal()
    {
        if (empty($this->quoterItems)) {
            throw new \Exception('No hay productos en el cotizador');
        }

        $this->ensureTenantConnection();

        try {
            DB::beginTransaction();

            if ($this->editingRemissionId) {
                // Lógica de actualización de Remisión
                $remission = InvRemissions::findOrFail($this->editingRemissionId);
                InvDetailRemissions::where('remissionId', $remission->id)->delete();

                foreach ($this->quoterItems as $item) {
                    InvDetailRemissions::create([
                        'quantity' => $item['quantity'],
                        'value' => $item['price'],
                        'remissionId' => $remission->id,
                        'itemId' => $item['id'],
                        'tax' => 0,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                }
                
                Log::info('🔄 Remisión guardada internamente (Autoguardado)', ['remission_id' => $remission->id]);
                
            } elseif ($this->editingQuoteId) {
                // Lógica de actualización de Cotización
                $quote = VntQuote::findOrFail($this->editingQuoteId);
                $quote->update([
                    'customerId' => $this->selectedCustomer['id'],
                    'observations' => $this->observaciones,
                ]);

                VntDetailQuote::where('quoteId', $quote->id)->delete();

                foreach ($this->quoterItems as $item) {
                    VntDetailQuote::create([
                        'quantity' => $item['quantity'],
                        'tax_percentage' => 0,
                        'price' => $item['price'],
                        'quoteId' => $quote->id,
                        'itemId' => $item['id'],
                        'description' => $item['name'],
                        'priceList' => $item['price']
                    ]);
                }
                Log::info('🔄 Cotización guardada internamente (Autoguardado)', ['quote_id' => $quote->id]);
            }

            DB::commit();
            $this->cartHasChanges = false;
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Error en saveChangesInternal: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Actualiza una cotización existente
     */
    public function updateQuote()
    {
        try {
            $this->saveChangesInternal();

            // Limpiar
            $this->clearQuoter();

            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => 'Cambios guardados exitosamente'
            ]);

            // Redirigir
            $routeName = $this->viewType === 'mobile' ? 'tenant.quoter.mobile' : 'tenant.quoter.desktop';
            return redirect()->route($routeName);

        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Error al actualizar: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Actualiza una remisión existente y su cotización base
     */
    public function updateRemission()
    {
        $this->updateQuote(); // Reutiliza la lógica de redirección y limpieza
    }


    //funcion para validar la cantidad ingresada
    public function validateQuantity($index)
    {
        // Verificar que el índice exista
        if (!isset($this->quoterItems[$index])) {
            return;
        }

        // Obtener la cantidad asegurando que nunca sea null ni vacío
        $quantity = trim((string) ($this->quoterItems[$index]['quantity'] ?? ''));

        // Si está vacío, no es numérico o es menor que 1, lo dejamos en 1
        if ($quantity === '' || !ctype_digit($quantity) || intval($quantity) < 1) {
            $this->quoterItems[$index]['quantity'] = 1;
        } else {
            // Convertimos a entero limpio
            $this->quoterItems[$index]['quantity'] = intval($quantity);
        }

        // Marcar que el carrito tiene cambios
        $this->cartHasChanges = true;

        // Actualizar sesión
        session(['quoter_items' => $this->quoterItems]);

        // Recalcular total
        $this->calculateTotal();

        // Si estamos editando una cotización que tiene remisión, deshabilitar el botón
        $this->checkAndDisableIfHasRemission();

        // Notificación opcional
        $this->dispatch('show-toast', [
            'type' => 'info',
            'message' => 'Cantidad actualizada'
        ]);
    }






    //metodo cerrar modal de carrito
    public function toggleCartModal()
    {
        $this->showCartModal = !$this->showCartModal;
    }


    //funcion para buscar cliente
    public function searchCustomer()
    {
        $this->searchingCustomer = true;
        $this->ensureTenantConnection();

        if (empty($this->customerSearch)) {
            $this->searchingCustomer = false;
            $this->dispatch('show-toast', [
                'type' => 'warning',
                'message' => 'Por favor ingrese un NIT o cédula'
            ]);
            return;
        }

        $search = '%' . $this->customerSearch . '%';
        $params = [auth()->id(), $search, $search, $search, $search, $search];

        $customer = DB::select("
        SELECT DISTINCT tr.salesman_id, tr.sale_day, tcr.company_id, vc.businessName, vc.billingEmail, vc.firstName, vc.lastName, vc.identification, vc.id
        FROM tat_routes tr
        INNER JOIN tat_companies_routes tcr ON tcr.route_id = tr.id
        INNER JOIN vnt_companies vc ON vc.id = tcr.company_id
        WHERE tr.salesman_id = ? AND (
            vc.identification LIKE ? OR 
            vc.businessName LIKE ? OR 
            vc.firstName LIKE ? OR 
            vc.lastName LIKE ? OR
            tr.sale_day LIKE ?
        )
        LIMIT 1
    ", $params);

        if (!empty($customer)) {
            $customer = (array) $customer[0];
            $this->selectedCustomer = [
                'id' => $customer['id'],
                'businessName' => $customer['businessName'],
                'firstName' => $customer['firstName'],
                'lastName' => $customer['lastName'],
                'identification' => $customer['identification'],
                'billingEmail' => $customer['billingEmail'],
                'sale_day' => $customer['sale_day'],
            ];

            $name = $customer['businessName'] ?: ($customer['firstName'] . ' ' . $customer['lastName']);

            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => 'Cliente encontrado: ' . $name
            ]);
        } else {
            $this->selectedCustomer = null;
            $this->showCreateCustomerButton = true;

            $this->dispatch('show-toast', [
                'type' => 'info',
                'message' => 'Cliente no encontrado. Puedes crear uno nuevo'
            ]);
        }

        $this->searchingCustomer = false;
    }






    public function clearCustomer()
    {
        $this->selectedCustomer = null;
        $this->customerSearch = '';
        $this->showCreateCustomerForm = false;
        $this->showCreateCustomerButton = false;
        $this->editingCustomerId = null;
        
        // Notificar a Alpine.js para limpiar estado local
        $this->dispatch('customer-selected', ['customer' => null]);
    }


    public function openCustomerModal()
    {
        $this->showCreateCustomerForm = true;
        $this->showCreateCustomerButton = false;
        // $this->isEditingCustomer = false; 
    }

    public function closeCustomerModal()
    {
        $this->showCreateCustomerForm = false;
        $this->showCreateCustomerButton = true;
    }

    public function showCreateCustomerForm()
    {
        $this->showCreateCustomerForm = true;
        $this->showCreateCustomerButton = false;
    }

    public function hideCreateCustomerForm()
    {
        $this->showCreateCustomerForm = false;
        $this->showCreateCustomerButton = true;
        $this->editingCustomerId = null;
    }

    public function cancelCreateCustomer()
    {
        $this->showCreateCustomerButton = false;
        $this->showCreateCustomerForm = false;
        $this->customerSearch = '';
        $this->editingCustomerId = null;
    }

    /**
     * Guardar un cliente de forma simplificada desde el cotizador móvil
     * 
     * @param array $data Datos enviados desde Alpine.js
     * @return array|null Datos del cliente creado para ser seleccionados
     */
    public function saveSimplifiedCustomer($data)
    {
        Log::info('📦 saveSimplifiedCustomer recibido', ['data' => $data]);
        
        $this->ensureTenantConnection();

        try {
            // Validaciones básicas antes de procesar
            if (empty($data['identification'])) throw new \Exception('La Identificación es obligatoria');
            if (empty($data['businessName'])) throw new \Exception('El Nombre/Razón Social es obligatorio');
            if (empty($data['phone'])) throw new \Exception('El Teléfono es obligatorio');

            Log::info('🏗️ Iniciando creación de cliente simplificado', ['identification' => $data['identification']]);

            // Preparar datos para el CompanyService
            $companyData = [
                'typeIdentificationId' => (int) ($data['typeIdentificationId'] ?: 1),
                'identification' => $data['identification'],
                'checkDigit' => $data['verification_digit'] ?? null,
                'businessName' => $data['businessName'],
                'firstName' => $data['businessName'], // Usado como fallback si es natural
                'billingEmail' => $data['billingEmail'] ?? null,
                'business_phone' => $data['phone'],
                'personal_phone' => $data['phone'],
                'typePerson' => ($data['typeIdentificationId'] == 2) ? 'Juridica' : 'Natural',
                'status' => 1,
                'type' => 'CLIENTE',
                'routeId' => (isset($data['routeId']) && $data['routeId']) ? $data['routeId'] : null,
                'regimeId' => 2,
                'fiscalResponsabilityId' => 1,
                // Campos adicionales para propagar a vnt_customers y vnt_warehouses
                'address' => $data['address'] ?? null,
                'cityId' => $data['cityId'] ?? null,
                'district' => $data['district'] ?? null,
            ];

            // Preparar sucursal (sucursal principal)
            $warehouses = [
                [
                    'name' => 'Sucursal Principal',
                    'address' => $data['address'] ?? 'Sin dirección',
                    'district' => $data['district'] ?? null,
                    'cityId' => $data['cityId'] ?: 1,
                    'main' => 1,
                    'status' => 1
                ]
            ];

            // Inyectar servicio y ejecutar creación (3 TABLAS: Company, Warehouse, Customer)
            $companyService = app(\App\Livewire\Tenant\VntCompany\Services\CompanyService::class);
            $company = $companyService->create($companyData, $warehouses);

            Log::info('✅ Cliente Procesado (Empresa + Sucursal + Cliente)', ['id' => $company->id]);

            // Formatear respuesta para el frontend
            $result = [
                'id' => $company->id,
                'identification' => $company->identification,
                'businessName' => $company->businessName,
                'display_name' => $company->businessName
            ];

            // Seleccionar automáticamente en el cotizador
            $this->selectCustomer($company->id);

            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => 'Cliente procesado y seleccionado correctamente'
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('❌ Error en saveSimplifiedCustomer: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'No se pudo procesar el cliente: ' . $e->getMessage()
            ]);
            return null;
        }
    }

    public function onCustomerCreated($customerId = null)
    {
        Log::info('🎯 onCustomerCreated llamado', [
            'customerId' => $customerId,
            'user_id' => auth()->id()
        ]);

        $this->ensureTenantConnection();

        // Buscar el cliente recién creado aplicando la misma lógica que searchCustomersLive()
        $customer = null;

        // Si es vendedor (perfil 4), buscar solo en sus rutas asignadas
        if (auth()->user()->profile_id == 4) {
            $customers = DB::select("
                SELECT tr.salesman_id, tr.sale_day, tcr.company_id, vc.businessName, vc.billingEmail, vc.firstName, vc.lastName, vc.identification, vc.id
                FROM tat_routes tr
                INNER JOIN tat_companies_routes tcr ON tcr.route_id = tr.id
                INNER JOIN vnt_companies vc ON vc.id = tcr.company_id
                WHERE tr.salesman_id = ? AND vc.id = ?
                LIMIT 1
            ", [auth()->id(), $customerId]);
        } else {
            // Para administradores u otros perfiles, buscar directamente en vnt_companies
            $customers = DB::select("
                SELECT
                    NULL as salesman_id,
                    NULL as sale_day,
                    vc.id as company_id,
                    vc.businessName,
                    vc.billingEmail,
                    vc.firstName,
                    vc.lastName,
                    vc.identification,
                    vc.id
                FROM vnt_companies vc
                WHERE vc.id = ? AND vc.status = 1 AND vc.deleted_at IS NULL
                LIMIT 1
            ", [$customerId]);
        }

        Log::info('🔍 Búsqueda de cliente recién creado', [
            'perfil_usuario' => auth()->user()->profile_id,
            'vendedor_id' => auth()->id(),
            'cliente_id' => $customerId,
            'clientes_encontrados' => count($customers ?? []),
            'datos_cliente' => !empty($customers) ? (array) $customers[0] : null
        ]);

        if (!empty($customers)) {
            $customer = (array) $customers[0];
            // Seleccionar el cliente recién creado
            $this->selectedCustomer = [
                'id' => $customer['id'],
                'businessName' => $customer['businessName'],
                'firstName' => $customer['firstName'],
                'lastName' => $customer['lastName'],
                'identification' => $customer['identification'],
                'billingEmail' => $customer['billingEmail'],
            ];

            // Limpiar estados del formulario de creación/edición
            $this->showCreateCustomerForm = false;
            $this->showCreateCustomerButton = false;
            $this->customerSearch = '';
            $this->editingCustomerId = null;

            // Determinar el nombre a mostrar
            $customerName = $customer['businessName'] ?: $customer['firstName'] . ' ' . $customer['lastName'];

            Log::info('✅ Cliente seleccionado exitosamente y modal cerrado', [
                'cliente_id' => $customer['id'],
                'cliente_nombre' => $customerName,
                'modal_cerrado' => $this->showCreateCustomerForm,
                'campos_limpiados' => [
                    'showCreateCustomerForm' => $this->showCreateCustomerForm,
                    'showCreateCustomerButton' => $this->showCreateCustomerButton,
                    'customerSearch' => $this->customerSearch,
                    'editingCustomerId' => $this->editingCustomerId
                ]
            ]);

            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => 'Cliente creado y seleccionado: ' . $customerName
            ]);
        } else {
            Log::warning('❌ Cliente recién creado NO encontrado', [
                'cliente_id' => $customerId,
                'perfil_usuario' => auth()->user()->profile_id,
                'vendedor_id' => auth()->id()
            ]);
        }
    }

    public function onCustomerUpdated($customerId = null)
    {
        $this->ensureTenantConnection();

        // Verificar si es el cliente que está actualmente seleccionado
        if ($this->selectedCustomer && $this->selectedCustomer['id'] == $customerId) {
            // Buscar el cliente actualizado validando que pertenece a las rutas del vendedor
            $customer = DB::select("
                SELECT tr.salesman_id, tcr.company_id, vc.businessName, vc.billingEmail, vc.firstName, vc.lastName, vc.identification, vc.id
                FROM tat_routes tr
                INNER JOIN tat_companies_routes tcr ON tcr.route_id = tr.id
                INNER JOIN vnt_companies vc ON vc.id = tcr.company_id
                WHERE tr.salesman_id = ? AND vc.id = ?
                LIMIT 1
            ", [auth()->id(), $customerId]);

            if (!empty($customer)) {
                $customer = (array) $customer[0];
                // Actualizar los datos del cliente seleccionado
                $this->selectedCustomer = [
                    'id' => $customer['id'],
                    'businessName' => $customer['businessName'],
                    'firstName' => $customer['firstName'],
                    'lastName' => $customer['lastName'],
                    'identification' => $customer['identification'],
                    'billingEmail' => $customer['billingEmail'],
                ];

                // Limpiar estados del formulario de edición
                $this->showCreateCustomerForm = false;
                $this->editingCustomerId = null;

                // Determinar el nombre a mostrar
                $customerName = $customer['businessName'] ?: $customer['firstName'] . ' ' . $customer['lastName'];

                $this->dispatch('show-toast', [
                    'type' => 'success',
                    'message' => 'Cliente actualizado: ' . $customerName
                ]);
            }
        }
    }

    private function findProductInQuoter($productId)
    {
        foreach ($this->quoterItems as $index => $item) {
            if ($item['id'] == $productId) {
                return $index;
            }
        }
        return false;
    }

    private function calculateTotal()
    {
        $this->totalAmount = collect($this->quoterItems)->sum(function ($item) {
            return $item['price'] * $item['quantity'];
        });
    }

    /**
     * Verifica si la cotización en edición tiene remisión y deshabilita el botón si es necesario
     */
    private function checkAndDisableIfHasRemission()
    {
        if ($this->isEditing && $this->editingQuoteId) {
            $this->ensureTenantConnection();
            $hasRemission = InvRemissions::where('quoteId', $this->editingQuoteId)->exists();

            // Actualizar el estado
            $this->quoteHasRemission = $hasRemission;

            Log::info('🔍 Verificación de remisión', [
                'isEditing' => $this->isEditing,
                'editingQuoteId' => $this->editingQuoteId,
                'quoteHasRemission' => $this->quoteHasRemission,
                'hasRemission_query' => $hasRemission
            ]);

            if ($this->quoteHasRemission) {
                Log::warning('⚠️ Cotización tiene remisión - Botón deshabilitado', [
                    'quote_id' => $this->editingQuoteId,
                    'has_remission' => $this->quoteHasRemission
                ]);

                // Dispatch para notificar al usuario
                $this->dispatch('show-toast', [
                    'type' => 'warning',
                    'message' => 'Esta cotización ya tiene una remisión. No se puede confirmar nuevamente.'
                ]);
            }
        } else {
            Log::info('ℹ️ No se verifica remisión', [
                'isEditing' => $this->isEditing,
                'editingQuoteId' => $this->editingQuoteId
            ]);
        }
    }

    public function getQuoterCountProperty()
    {
        return collect($this->quoterItems)->sum('quantity');
    }

    public function updatedQuoterItems()
    {
        session(['quoter_items' => $this->quoterItems]);
        $this->calculateTotal();
    }

    public function getProductQuantity($productId)
    {
        foreach ($this->quoterItems as $item) {
            if ($item['id'] == $productId) {
                return $item['quantity'];
            }
        }
        return 0;
    }

    /**
     * Obtener el precio seleccionado para un producto en el cotizador
     * 
     * @param int $productId
     * @return array|null
     */
    public function getSelectedPriceInfo($productId)
    {
        foreach ($this->quoterItems as $item) {
            if ($item['id'] == $productId) {
                return [
                    'price' => $item['price'],
                    'label' => $item['price_label']
                ];
            }
        }
        return null;
    }

    public function increaseQuantity($productId)
    {
        $this->ensureTenantConnection();
        $index = $this->findProductInQuoter($productId);

        if ($index !== false) {
            $newQuantity = $this->quoterItems[$index]['quantity'] + 1;
            $this->updateQuantity($productId, $newQuantity);
        }
    }

    public function decreaseQuantity($productId)
    {
        $this->ensureTenantConnection();
        $index = $this->findProductInQuoter($productId);

        if ($index !== false) {
            $newQuantity = $this->quoterItems[$index]['quantity'] - 1;
            $this->updateQuantity($productId, $newQuantity);
        }
    }

    public function updateQuantityById($productId, $quantity)
    {
        $this->ensureTenantConnection();
        $existingIndex = $this->findProductInQuoter($productId);

        if ($existingIndex !== false) {
            $quantity = intval($quantity);

            if ($quantity <= 0) {
                $this->removeFromQuoter($productId);
            } else {
                $this->quoterItems[$existingIndex]['quantity'] = $quantity;

                // Marcar que el carrito tiene cambios
                $this->cartHasChanges = true;

                session(['quoter_items' => $this->quoterItems]);
                $this->calculateTotal();

                // Si estamos editando una cotización que tiene remisión, deshabilitar el botón
                $this->checkAndDisableIfHasRemission();

                $this->dispatch('show-toast', [
                    'type' => 'info',
                    'message' => 'Cantidad actualizada'
                ]);
            }
        }
    }

    public function loadQuoteForEditing($quoteId)
    {
        Log::info('🔍 INICIO loadQuoteForEditing', ['quoteId' => $quoteId]);
        $this->ensureTenantConnection();

        try {
            // Carga ávida de detalles e items para mayor eficiencia
            $quote = VntQuote::with('detalles.item')->findOrFail($quoteId);
            Log::info('📄 Cotización encontrada', [
                'consecutive' => $quote->consecutive,
                'detalles_count' => $quote->detalles->count()
            ]);

            $this->editingQuoteId = $quoteId;
            $this->isEditing = true;

            // Verificar si la cotización tiene remisión
            $this->quoteHasRemission = InvRemissions::where('quoteId', $quoteId)
                ->where('status', '!=', 'ANULADO')
                ->exists();

            // Cargar observaciones de la cotización
            $this->observaciones = $quote->observations;

            // Inicializar estado del acordeón de observaciones
            $this->showObservations = !empty($quote->observations);

            // Cargar información del cliente
            if ($quote->customerId) {
                $customer = VntCompany::find($quote->customerId);
                if ($customer) {
                    $this->selectedCustomer = [
                        'id' => $customer->id,
                        'businessName' => $customer->businessName,
                        'firstName' => $customer->firstName,
                        'lastName' => $customer->lastName,
                        'identification' => $customer->identification,
                        'billingEmail' => $customer->billingEmail,
                    ];
                    Log::info('👤 Cliente cargado', ['customer_id' => $customer->id]);
                }
            }

            // Cargar productos de la cotización
            $this->quoterItems = [];
            foreach ($quote->detalles as $detalle) {
                $item = $detalle->item;
                $this->quoterItems[] = [
                    'id' => $detalle->itemId,
                    'name' => $detalle->description ?: ($item ? $item->display_name : 'Producto sin nombre'),
                    'sku' => $item ? $item->sku : '',
                    'price' => $detalle->price,
                    'price_label' => 'Precio Registrado',
                    'quantity' => $detalle->quantity,
                    'description' => $detalle->description ?: ($item ? $item->description : ''),
                ];
                Log::info('📦 Item agregado', ['id' => $detalle->itemId, 'qty' => $detalle->quantity]);
            }

            session(['quoter_items' => $this->quoterItems]);
            $this->cartHasChanges = false;

            Log::info('✅ FIN loadQuoteForEditing', ['final_count' => count($this->quoterItems)]);

            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => 'Cotización #' . $quote->consecutive . ' cargada para edición'
            ]);
        } catch (\Exception $e) {
            Log::error('❌ ERROR en loadQuoteForEditing: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Error al cargar la cotización: ' . $e->getMessage()
            ]);
        }
    }










    /**
     * Función unificada para guardar solicitudes de reabastecimiento TAT
     * Maneja tanto lista preliminar como confirmación directa
     * @param bool $confirmDirectly - true: confirma y migra de una vez, false: guarda como lista preliminar
     */
    public function saveRestockRequest($confirmDirectly = false)
    {
        // Solo para perfil TAT
        if (auth()->user()->profile_id != 17) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Función exclusiva para tiendas TAT'
            ]);
            return;
        }

        if (empty($this->quoterItems)) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'No hay productos en el cotizador'
            ]);
            return;
        }

        $this->ensureTenantConnection();
        $companyId = $this->getUserCompanyId(auth()->user());

        if (!$companyId) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'No se pudo identificar su empresa asignada'
            ]);
            return;
        }

        try {
            $orderNumber = null;
            $status = 'Registrado'; // Por defecto para lista preliminar

            // Si estamos editando una lista preliminar, borrar todos los registros preliminares
            if ($this->isEditingRestock && !$this->editingRestockOrder) {
                // Edición de lista preliminar: borrar todos los items preliminares existentes
                TatRestockList::where('company_id', $companyId)
                    ->where('status', 'Registrado')
                    ->whereNull('order_number')
                    ->delete();
            }

            // Si se confirma directamente o estamos editando una orden confirmada, necesitamos order_number
            if ($confirmDirectly || ($this->isEditingRestock && $this->editingRestockOrder)) {
                if ($this->isEditingRestock && $this->editingRestockOrder) {
                    // Usar order_number existente para edición de orden confirmada
                    $orderNumber = $this->editingRestockOrder;

                    // Borrar items anteriores para reemplazarlos
                    TatRestockList::where('order_number', $orderNumber)
                        ->where('company_id', $companyId)
                        ->delete();
                } else {
                    // Generar nuevo order_number
                    $lastOrder = TatRestockList::where('company_id', $companyId)->max('order_number');
                    $orderNumber = $lastOrder ? $lastOrder + 1 : 1;
                }

                $status = 'Confirmado';
            }

            $addedCount = 0;
            $updatedCount = 0;

            foreach ($this->quoterItems as $item) {
                // Para lista preliminar, verificar si ya existe el producto
                if (!$confirmDirectly && !$this->isEditingRestock) {
                    $existing = TatRestockList::where('itemId', $item['id'])
                        ->where('company_id', $companyId)
                        ->where('status', 'Registrado')
                        ->whereNull('order_number')
                        ->first();

                    if ($existing) {
                        // Sumar cantidades al registro existente
                        $existing->quantity_request += $item['quantity'];
                        $existing->save();
                        $updatedCount++;
                        continue;
                    }
                }

                // Crear nuevo registro
                TatRestockList::create([
                    'itemId' => $item['id'],
                    'company_id' => $companyId,
                    'quantity_request' => $item['quantity'],
                    'quantity_recive' => 0,
                    'status' => $status,
                    'order_number' => $orderNumber // NULL para lista preliminar
                ]);
                $addedCount++;
            }

            // Si se confirma directamente, migrar a cotizaciones
            if ($confirmDirectly || ($this->isEditingRestock && $status === 'Confirmado')) {
                $quote = $this->migrateRestockToQuotes($orderNumber, $companyId);

                if ($quote) {
                    // Crear remisión automáticamente
                    $remission = $this->createRemissionFromRestock($orderNumber, $companyId, $quote);

                    $message = $this->isEditingRestock ?
                        "Solicitud #{$orderNumber} actualizada y migrada a cotización #{$quote->consecutive}" :
                        "Solicitud #{$orderNumber} confirmada y migrada a cotización #{$quote->consecutive}";

                    if ($remission) {
                        $message .= " y remisión #{$remission->consecutive} creada";
                    }
                } else {
                    $message = "Solicitud #{$orderNumber} confirmada, pero no se pudo migrar a cotizaciones";
                }
            } else {
                // Mensaje para lista preliminar
                if ($this->isEditingRestock && !$this->editingRestockOrder) {
                    $message = "Lista preliminar actualizada: {$addedCount} productos";
                } else {
                    $message = "Lista preliminar actualizada: {$addedCount} productos agregados";
                    if ($updatedCount > 0) {
                        $message .= ", {$updatedCount} cantidades actualizadas";
                    }
                }
            }

            // Limpiar cotizador y resetear estados
            $this->quoterItems = [];
            session()->forget('quoter_items');
            $this->calculateTotal();
            $this->showCartModal = false;
            $this->isEditingRestock = false;
            $this->editingRestockOrder = null;

            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => $message
            ]);

            // Sincronizar con Alpine.js/IndexedDB
            $this->dispatch('cart-updated', items: []);
            $this->dispatch('customer-selected', customer: null);
        } catch (\Exception $e) {
            Log::error('Error saving restock request: ' . $e->getMessage());
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Error al guardar solicitud: ' . $e->getMessage()
            ]);
        }
    }

    public function cancelEditing()
    {
        // Limpiar estados de edición
        $this->isEditing = false;
        $this->editingQuoteId = null;

        // Limpiar todos los campos del formulario
        $this->selectedCustomer = null;              // Limpiar cliente seleccionado
        $this->customerSearch = '';                  // Limpiar campo de búsqueda de cliente
        $this->observaciones = null;                 // Limpiar observaciones
        $this->showCreateCustomerForm = false;      // Ocultar formulario de creación
        $this->showCreateCustomerButton = false;    // Ocultar botón de creación

        // Limpiar cotizador
        $this->clearQuoter();

        $this->dispatch('show-toast', [
            'type' => 'info',
            'message' => 'Edición cancelada'
        ]);
    }

    protected function getUserCompanyId($user)
    {
        if (!$user) return null;

        // Opción 1: Si el usuario tiene contact_id asociado
        if ($user->contact_id) {
            $contact = DB::table('vnt_contacts')
                ->where('id', $user->contact_id)
                ->first();

            if ($contact && isset($contact->warehouseId)) {
                $warehouse = DB::table('vnt_warehouses')
                    ->where('id', $contact->warehouseId)
                    ->first();

                // Intentar obtener companyId del warehouse
                if ($warehouse && isset($warehouse->companyId)) {
                    return $warehouse->companyId;
                }
            }
        }
        return null;
    }

    public function loadRestockForEditing($orderNumber)
    {
        $this->ensureTenantConnection();
        $user = Auth::user();
        $userId = $user ? $user->id : 'guest';
        $companyId = $this->getUserCompanyId($user);

        // 📝 LOG DEBUG: Intentando cargar Restock
        Log::info('loadRestockForEditing', [
            'orderNumber' => $orderNumber,
            'userId' => $userId,
            'companyId' => $companyId
        ]);

        if (!$companyId) {
            Log::error("No se pudo cargar la orden $orderNumber para edición: Company ID no encontrado para usuario $userId");
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Error: No se pudo verificar su empresa. Por favor contacte soporte.'
            ]);
            return;
        }

        $items = TatRestockList::where('order_number', $orderNumber)
            ->where('company_id', $companyId)
            ->with(['item'])
            ->get();

        Log::info('Restock Items Search Result', [
            'count' => $items->count(),
            'first_item' => $items->first()
        ]);

        if ($items->isEmpty()) {
            $this->dispatch('show-toast', [
                'type' => 'warning',
                'message' => 'No se encontraron productos para la orden #' . $orderNumber
            ]);
            return;
        }

        $this->editingRestockOrder = $orderNumber;
        $this->isEditingRestock = true;

        // Resetear items actuales
        $this->quoterItems = [];

        foreach ($items as $restockItem) {
            // Verificar si el item existe (puede haber sido borrado SoftDeletes o no cargado)
            if ($restockItem->item) {
                $product = $restockItem->item;
                $price = $product->price ?? 0;

                // Agregar al array local
                $this->quoterItems[] = [
                    'id' => $product->id,
                    'name' => $product->display_name,
                    'sku' => $product->sku,
                    'price' => $price,
                    'price_label' => 'Precio Lista',
                    'quantity' => $restockItem->quantity_request,
                    'description' => $product->description ?? '',
                ];
            } else {
                Log::warning("Item no encontrado para restock ID: " . $restockItem->id);
            }
        }

        // **IMPORTANTE**: Actualizar la sesión inmediatamente
        session(['quoter_items' => $this->quoterItems]);

        Log::info('Session Updated with Restock Items', [
            'items_count' => count($this->quoterItems)
        ]);

        $this->calculateTotal();

        // Forzar renderizado
        $this->showCartModal = false;

        $this->dispatch('show-toast', [
            'type' => 'info',
            'message' => 'Orden #' . $orderNumber . ' cargada. ' . count($this->quoterItems) . ' productos.'
        ]);
    }

    // ============================================
    // NUEVAS FUNCIONES PARA EL FLUJO TAT COMPLETO
    // ============================================

    /**
     * Función de migración a vnt_quotes - reutiliza lógica existente adaptada para TAT
     */
    protected function migrateRestockToQuotes($orderNumber, $companyId)
    {
        try {
            Log::info("Iniciando migración a cotizaciones", [
                'order_number' => $orderNumber,
                'company_id' => $companyId
            ]);

            // Primero verificar QUE registros existen en tat_restock_list
            $allRestockItems = TatRestockList::where('order_number', $orderNumber)
                ->where('company_id', $companyId)
                ->get();

            Log::info("Registros encontrados en tat_restock_list", [
                'total_records' => $allRestockItems->count(),
                'records_data' => $allRestockItems->toArray()
            ]);

            // Obtener items confirmados del pedido
            $restockItems = TatRestockList::where('order_number', $orderNumber)
                ->where('company_id', $companyId)
                ->where('status', 'Confirmado')
                ->with(['item']) // Cargar relación con productos
                ->get();

            Log::info("Registros confirmados encontrados", [
                'confirmed_records' => $restockItems->count(),
                'confirmed_data' => $restockItems->toArray()
            ]);

            if ($restockItems->isEmpty()) {
                Log::warning("No se encontraron items confirmados para migrar", [
                    'order_number' => $orderNumber,
                    'company_id' => $companyId,
                    'total_records_found' => $allRestockItems->count()
                ]);
                return null;
            }

            // Usar lógica existente para generar consecutivo
            $lastQuote = VntQuote::orderBy('consecutive', 'desc')->first();
            $nextConsecutive = $lastQuote ? $lastQuote->consecutive + 1 : 1;

            // Obtener warehouseId correcto del contacto del usuario
            $userId = auth()->id();
            $warehouseId = session('warehouse_id', 1); // Valor por defecto (fallback)
            $contactName = '';

            if (auth()->check() && auth()->user()->contact_id) {
                $contact = DB::table('vnt_contacts')
                    ->where('id', auth()->user()->contact_id)
                    ->first();

                if ($contact) {
                    if (isset($contact->warehouseId)) {
                        $warehouseId = $contact->warehouseId;
                        Log::info("Warehouse ID obtenido del contacto", ['userId' => $userId, 'warehouseId' => $warehouseId]);
                    }

                    // Construir nombre completo
                    $names = array_filter([
                        $contact->firstName,
                        $contact->secondName,
                        $contact->lastName,
                        $contact->secondLastName
                    ]);
                    $contactName = implode(' ', $names);
                }
            }

            $observations = "Solicitud de reabastecimiento TAT #{$orderNumber}";
            if (!empty($contactName)) {
                $observations .= " - " . $contactName;
            }

            // Crear cotización (adaptando saveQuote() existente para TAT)
            $quote = VntQuote::create([
                'consecutive' => $nextConsecutive,
                'status' => auth()->user()->profile_id == 17 ? 'REMISIÓN' : 'REGISTRADO',
                'typeQuote' => 'POS', // Para TAT es institucional/POS
                'customerId' => $companyId, // La tienda TAT como cliente
                'warehouseId' => $warehouseId,
                'userId' => $userId,
                'observations' => $observations,
                'branchId' => session('branch_id', 1)
            ]);

            // **PASO 1: Actualizar el order_number con el ID de la cotización recién creada**
            $quote->update(['order_number' => $quote->id]);

            Log::info("🎯 PASO 1 COMPLETADO - Quote actualizada", [
                'quote_id' => $quote->id,
                'consecutive' => $quote->consecutive,
                'order_number_updated' => $quote->id
            ]);

            // **PASO 2: Buscar registros tat_restock_list que ya están confirmados con el order_number anterior**
            $recordsToUpdate = TatRestockList::where('order_number', $orderNumber)
                ->where('company_id', $companyId)
                ->where('status', 'Confirmado')
                ->get();

            Log::info("🔍 PASO 2 - Registros confirmados encontrados para actualizar", [
                'order_number' => $orderNumber,
                'company_id' => $companyId,
                'records_count' => $recordsToUpdate->count(),
                'records_ids' => $recordsToUpdate->pluck('id')->toArray()
            ]);

            // **PASO 3: Actualizar los registros confirmados para que tengan el ID de la cotización**
            if ($recordsToUpdate->count() > 0) {
                $updatedRecords = TatRestockList::where('order_number', $orderNumber)
                    ->where('company_id', $companyId)
                    ->where('status', 'Confirmado')
                    ->update([
                        'order_number' => $quote->id, // El ID de la cotización (reemplazar el order_number anterior)
                    ]);

                Log::info("✅ PASO 3 COMPLETADO - Registros tat_restock_list actualizados con quote ID", [
                    'old_order_number' => $orderNumber,
                    'new_quote_id_assigned' => $quote->id,
                    'company_id' => $companyId,
                    'records_updated' => $updatedRecords
                ]);
            } else {
                Log::warning("⚠️ PASO 3 OMITIDO - No se encontraron registros confirmados para actualizar", [
                    'order_number' => $orderNumber,
                    'company_id' => $companyId
                ]);
            }

            // Crear detalles usando getProductData()
            $detailsCreated = 0;
            foreach ($restockItems as $restockItem) {
                Log::info("Procesando item para migrar", [
                    'itemId' => $restockItem->itemId,
                    'quantity_request' => $restockItem->quantity_request
                ]);

                $productData = $this->getProductData($restockItem->itemId);

                Log::info("Datos del producto obtenidos", [
                    'itemId' => $restockItem->itemId,
                    'productData' => $productData
                ]);

                try {
                    $detail = VntDetailQuote::create([
                        'quantity' => $restockItem->quantity_request,
                        'tax_percentage' => $productData['tax'] ?? 0,
                        'price' => $productData['price'] ?? 0,
                        'quoteId' => $quote->id,
                        'itemId' => $restockItem->itemId,
                        'description' => $productData['name'] ?? 'Producto TAT',
                        'priceList' => $productData['price'] ?? 0
                    ]);

                    $detailsCreated++;
                    Log::info("Detalle creado exitosamente", [
                        'detail_id' => $detail->id,
                        'quoteId' => $quote->id,
                        'itemId' => $restockItem->itemId
                    ]);
                } catch (\Exception $detailError) {
                    Log::error('Error creando detalle de cotización: ' . $detailError->getMessage(), [
                        'itemId' => $restockItem->itemId,
                        'quoteId' => $quote->id,
                        'productData' => $productData
                    ]);
                }
            }

            Log::info("Detalles de cotización procesados", [
                'total_items' => $restockItems->count(),
                'details_created' => $detailsCreated
            ]);

            Log::info("Solicitud TAT migrada exitosamente", [
                'order_number' => $orderNumber,
                'quote_id' => $quote->id,
                'consecutive' => $quote->consecutive,
                'items_count' => $restockItems->count()
            ]);

            return $quote;
        } catch (\Exception $e) {
            Log::error('Error migrando restock a quotes: ' . $e->getMessage(), [
                'order_number' => $orderNumber,
                'company_id' => $companyId
            ]);
            return null;
        }
    }

    /**
     * Crear una remisión automáticamente a partir de un pedido de reabastecimiento TAT
     * Reutiliza los items vinculados al pedido que ya fue migrado a cotización
     */
    protected function createRemissionFromRestock($orderNumber, $companyId, $quote)
    {
        if (!$quote) return null;

        try {
            Log::info("Iniciando creación de remisión desde Restock", [
                'order_number' => $orderNumber,
                'quote_id' => $quote->id,
                'quote_consecutive' => $quote->consecutive
            ]);

            // IMPORTANTE: Tras migrateRestockToQuotes, el order_number en DB es quote->id
            // Pero el $orderNumber que recibimos puede ser el original.
            // Buscamos los items usando el ID de la cotización que es el nuevo order_number
            $items = TatRestockList::where('order_number', $quote->id)
                ->where('company_id', $companyId)
                ->where('status', 'Confirmado')
                ->get();

            if ($items->isEmpty()) {
                Log::warning("No se encontraron items para crear remisión", [
                    'quote_id' => $quote->id,
                    'company_id' => $companyId
                ]);
                return null;
            }

            // 1. Generar consecutivo
            $consecutive = $this->generateRemissionConsecutive();

            // 2. Crear cabecera de Remisión (inv_remissions)
            $remission = InvRemissions::create([
                'consecutive'    => $consecutive,
                'status'         => 'REGISTRADO',
                'created_at'     => now(),
                'updated_at'     => now(),
                'quoteId'        => $quote->id,
                'userId'         => auth()->id(),
                'warehouseId'    => $quote->warehouseId ?? session('warehouse_id', 1),
            ]);

            // 3. Crear detalles (inv_detail_remissions)
            $detailsCount = 0;
            foreach ($items as $item) {
                // Obtener datos de precio/impuesto del item TAT
                $productData = $this->getProductData($item->itemId);

                InvDetailRemissions::create([
                    'remissionId' => $remission->id,
                    'itemId'      => $item->itemId,
                    'quantity'    => $item->quantity_request,
                    'value'       => $productData['price'] ?? 0,
                    'tax'         => 0, // Ajustar si se manejan impuestos en remisiones
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);
                $detailsCount++;
            }

            Log::info("Remisión #{$consecutive} creada exitosamente para Quote #{$quote->consecutive}", [
                'remission_id' => $remission->id,
                'details_count' => $detailsCount
            ]);

            return $remission;
        } catch (\Exception $e) {
            Log::error('Error creando remisión desde Restock: ' . $e->getMessage(), [
                'quote_id' => $quote->id,
                'order_number' => $orderNumber
            ]);
            return null;
        }
    }

    /**
     * Obtener datos del producto TAT para migración a cotizaciones
     * Usa el modelo correcto TatItems específico para tiendas TAT
     */
    protected function getProductData($itemId)
    {
        try {
            // Buscar el producto en la tabla TAT específica
            $product = \App\Models\TAT\Items\TatItems::find($itemId);

            if (!$product) {
                Log::warning("Producto TAT no encontrado para migración", ['item_id' => $itemId]);
                return [
                    'name' => 'Producto TAT no encontrado',
                    'price' => 0,
                    'tax' => 0
                ];
            }

            // Obtener información de impuestos si existe la relación
            $taxValue = 0;
            if ($product->tax) {
                $taxValue = $product->tax->percentage ?? 0;
            }

            return [
                'name' => $product->display_name ?? $product->name ?? 'Sin nombre',
                'description' => $product->name ?? '', // TatItems no tiene description, usamos name
                'price' => $product->price ?? 0,
                'tax' => $taxValue,
                'sku' => $product->sku ?? ''
            ];
        } catch (\Exception $e) {
            Log::error('Error obteniendo datos del producto TAT: ' . $e->getMessage(), [
                'item_id' => $itemId
            ]);

            return [
                'name' => 'Error al cargar producto TAT',
                'price' => 0,
                'tax' => 0
            ];
        }
    }

    /**
     * Función para "Actualizar Solicitud" - edición específica de productos TAT
     * Permite editar cantidades o confirmar solicitudes existentes
     */
    public function updateRestockRequest($itemId, $newQuantity = null, $confirmOrder = false)
    {
        // Solo para perfil TAT
        if (auth()->user()->profile_id != 17) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Función exclusiva para tiendas TAT'
            ]);
            return;
        }

        $this->ensureTenantConnection();
        $companyId = $this->getUserCompanyId(auth()->user());

        if (!$companyId) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'No se pudo identificar su empresa asignada'
            ]);
            return;
        }

        try {
            $existing = TatRestockList::where('itemId', $itemId)
                ->where('company_id', $companyId)
                ->where('status', '!=', 'Anulado')
                ->first();

            if (!$existing) {
                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => 'No hay solicitud previa para actualizar'
                ]);
                return;
            }

            if ($existing->status == 'Confirmado' && !$confirmOrder) {
                $this->dispatch('show-toast', [
                    'type' => 'warning',
                    'message' => 'La solicitud ya está confirmada. No se puede editar.'
                ]);
                return;
            }

            // Actualizar cantidad si se proporciona
            if ($newQuantity !== null && $newQuantity > 0) {
                $existing->quantity_request = $newQuantity;
                $existing->save();

                $this->dispatch('show-toast', [
                    'type' => 'success',
                    'message' => 'Cantidad actualizada exitosamente'
                ]);
            }

            // Confirmar orden si se solicita
            if ($confirmOrder && $existing->status == 'Registrado') {
                // Generar order_number si no tiene
                if (!$existing->order_number) {
                    $lastOrder = TatRestockList::where('company_id', $companyId)->max('order_number');
                    $nextOrderNumber = $lastOrder ? $lastOrder + 1 : 1;
                    $existing->order_number = $nextOrderNumber;
                }

                $existing->status = 'Confirmado';
                $existing->save();

                // Migrar a cotizaciones
                $quote = $this->migrateRestockToQuotes($existing->order_number, $companyId);

                // Crear remisión automáticamente
                $remission = $this->createRemissionFromRestock($existing->order_number, $companyId, $quote);

                if ($quote) {
                    $message = "Solicitud confirmada y migrada a cotización #{$quote->consecutive}";
                    if ($remission) {
                        $message .= " y remisión #{$remission->consecutive} creada";
                    }

                    $this->dispatch('show-toast', [
                        'type' => 'success',
                        'message' => $message
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error updating restock request: ' . $e->getMessage());
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Error al actualizar solicitud: ' . $e->getMessage()
            ]);
        }
    }



    //carga preliminar  de TAT
    public function loadPreliminaryRestockForEditing()
    {
        $this->ensureTenantConnection();
        $user = Auth::user();
        $userId = $user ? $user->id : 'guest';
        $companyId = $this->getUserCompanyId($user);

        Log::info('loadPreliminaryRestockForEditing', [
            'userId' => $userId,
            'companyId' => $companyId
        ]);

        if (!$companyId) {
            Log::error("No se pudo cargar la lista preliminar para edición: Company ID no encontrado para usuario $userId");
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Error: No se pudo verificar su empresa. Por favor contacte soporte.'
            ]);
            return;
        }

        // Obtener la query para debug
        $query = TatRestockList::where('company_id', $companyId)
            ->where('status', 'Registrado')
            ->whereNull('order_number');

        Log::info('Preliminary Restock Query Debug', [
            'sql' => $query->toSql(),
            'bindings' => $query->getBindings(),
            'company_id_used' => $companyId
        ]);

        $items = $query->with(['item'])->get();

        Log::info('Preliminary Restock Items Search Result', [
            'count' => $items->count()
        ]);

        if ($items->isEmpty()) {
            $this->dispatch('show-toast', [
                'type' => 'info',
                'message' => 'No hay productos en lista preliminar para editar'
            ]);
            return;
        }

        // Marcar que estamos editando lista preliminar
        $this->isEditingRestock = true;
        $this->editingRestockOrder = null; // No hay order_number para preliminares

        // Resetear items actuales
        $this->quoterItems = [];

        foreach ($items as $restockItem) {
            if ($restockItem->item) {
                $product = $restockItem->item;
                $price = $product->price ?? 0;

                // Agregar al array local
                $this->quoterItems[] = [
                    'id' => $product->id,
                    'name' => $product->display_name,
                    'sku' => $product->sku,
                    'price' => $price,
                    'price_label' => 'Precio Lista',
                    'quantity' => $restockItem->quantity_request,
                    'description' => $product->description ?? '',
                ];
            } else {
                Log::warning("Item no encontrado para restock preliminar ID: " . $restockItem->id);
            }
        }

        // Actualizar la sesión inmediatamente
        session(['quoter_items' => $this->quoterItems]);

        Log::info('Session Updated with Preliminary Restock Items', [
            'items_count' => count($this->quoterItems)
        ]);

        $this->calculateTotal();

        $this->dispatch('show-toast', [
            'type' => 'info',
            'message' => 'Lista preliminar cargada. ' . count($this->quoterItems) . ' productos.'
        ]);
    }






    /**
     * Valida si un producto ya se encuentra en estado 'Confirmado' para el usuario actual (Perfil 17)
     * Retorna mensaje de advertencia si existe, o null si pasa la validación.
     */
    /**
     * Valida si un producto ya se encuentra en estado 'Confirmado' para el usuario actual (Perfil 17)
     * Retorna el objeto TatRestockList si existe, o null si pasa la validación.
     */
    protected function checkConfirmedProductStatus($productId)
    {
        // Solo aplica para perfil 17
        if (!auth()->check() || auth()->user()->profile_id != 17) {
            return null; // Pasa la validación
        }

        $this->ensureTenantConnection();
        $companyId = $this->getUserCompanyId(auth()->user());

        if (!$companyId) {
            return null; // No podemos validar sin compañía
        }

        // Buscar en la tabla de restock si existe confirmado
        return TatRestockList::where('company_id', $companyId)
            ->where('itemId', $productId)
            ->where('status', 'Confirmado')
            ->first();
    }

    /**
     * Método para búsqueda en tiempo real de clientes (como en quoter-view)
     */
    public function updatedCustomerSearch()
    {
        if (strlen($this->customerSearch) >= 1) {
            $this->searchCustomersLive();
        } else {
            $this->customerSearchResults = [];
        }
    }

    /**
     * Buscar clientes en tiempo real
     */
    public function searchCustomersLive()
    {
        $this->ensureTenantConnection();

        if (strlen($this->customerSearch) < 1) {
            $this->customerSearchResults = [];
            return;
        }

        $search = '%' . $this->customerSearch . '%';

        // Si es vendedor (perfil 4), buscar solo en sus rutas asignadas
        if (auth()->user()->profile_id == 4) {
            $params = [auth()->id(), $search, $search, $search, $search, $search];

            $customers = DB::select("
            SELECT DISTINCT tr.salesman_id, tr.sale_day, tcr.company_id, vc.businessName, vc.billingEmail, vc.firstName, vc.lastName, vc.identification, vc.id
            FROM tat_routes tr
            INNER JOIN tat_companies_routes tcr ON tcr.route_id = tr.id
            INNER JOIN vnt_companies vc ON vc.id = tcr.company_id
            WHERE tr.salesman_id = ? AND (
                vc.identification LIKE ? OR
                vc.businessName LIKE ? OR
                vc.firstName LIKE ? OR
                vc.lastName LIKE ? OR
                tr.sale_day LIKE ?
            )
            AND vc.type != 'PROVEEDOR'
        ", $params);
        } else {
            // Para administradores u otros perfiles, buscar en todos los clientes
            $params = [$search, $search, $search, $search];

            $customers = DB::select("
            SELECT DISTINCT
                NULL as salesman_id,
                NULL as sale_day,
                vc.id as company_id,
                vc.businessName,
                vc.billingEmail,
                vc.firstName,
                vc.lastName,
                vc.identification,
                vc.id
            FROM vnt_companies vc
            WHERE (
                TRIM(vc.identification) LIKE ?
                OR vc.businessName LIKE ?
                OR vc.firstName LIKE ?
                OR vc.lastName LIKE ?
            )
            AND vc.status = 1
            AND vc.deleted_at IS NULL
            AND vc.type != 'PROVEEDOR'
            LIMIT 15
        ", $params);
        }

        $this->customerSearchResults = array_map(function ($customer) {
            return [
                'id' => $customer->id,
                'identification' => $customer->identification,
                'display_name' => $customer->businessName ?: ($customer->firstName . ' ' . $customer->lastName),
                'sale_day' => $customer->sale_day
            ];
        }, $customers);

        $this->dispatch('customers-found', [
            'customers' => $this->customerSearchResults
        ]);

        // Auto-open modal if no results and sufficient length
        // Solo para escritorio se abre el formulario completo automáticamente
        if (empty($this->customerSearchResults) && strlen($this->customerSearch) >= 3) {
            if ($this->viewType === 'desktop') {
                $this->openCustomerModal();
            }
        }
    }

    /**
     * Seleccionar un cliente de los resultados
     */
    public function selectCustomer($customerId)
    {
        $this->ensureTenantConnection();

        // Si es vendedor (perfil 4), validar que el cliente pertenece a sus rutas
        if (auth()->user()->profile_id == 4) {
            $params = [auth()->id(), $customerId];
            $whereSaleDay = '';

            // Si hay un día seleccionado, agregar filtro
            if (!empty($this->selectedSaleDay)) {
                $whereSaleDay = 'AND tr.sale_day = ?';
                $params[] = $this->selectedSaleDay;
            }

            $customer = DB::select("
                SELECT tr.salesman_id, tr.sale_day, tcr.company_id, vc.businessName, vc.billingEmail, vc.firstName, vc.lastName, vc.identification, vc.id
                FROM tat_routes tr
                INNER JOIN tat_companies_routes tcr ON tcr.route_id = tr.id
                INNER JOIN vnt_companies vc ON vc.id = tcr.company_id
                WHERE tr.salesman_id = ? AND vc.id = ? $whereSaleDay
                LIMIT 1
            ", $params);
        } else {
            // Para administradores, buscar directamente en vnt_companies
            $customer = DB::select("
                SELECT vc.businessName, vc.billingEmail, vc.firstName, vc.lastName, vc.identification, vc.id
                FROM vnt_companies vc
                WHERE vc.id = ? AND vc.status = 1 AND vc.deleted_at IS NULL
                LIMIT 1
            ", [$customerId]);
        }

        if (!empty($customer)) {
            $customer = (array) $customer[0];
            $this->selectedCustomer = [
                'id' => $customer['id'],
                'businessName' => $customer['businessName'],
                'firstName' => $customer['firstName'],
                'lastName' => $customer['lastName'],
                'identification' => $customer['identification'],
                'billingEmail' => $customer['billingEmail'],
                'sale_day' => $customer['sale_day'] ?? null,
            ];
            $this->customerSearch = '';
            $this->customerSearchResults = [];

            $name = $customer['businessName'] ?: ($customer['firstName'] . ' ' . $customer['lastName']);

            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => 'Cliente seleccionado: ' . $name
            ]);

            // Notificar a Alpine.js para persistencia offline
            $this->dispatch('customer-selected', customer: $this->selectedCustomer);
        } else {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => auth()->user()->profile_id == 4 ? 'Cliente no disponible en tus rutas asignadas' : 'Cliente no encontrado'
            ]);
        }
    }

    /**
     * Cancelar búsqueda de cliente
     */
    public function cancelClientSearch()
    {
        $this->customerSearch = '';
        $this->customerSearchResults = [];
    }

    /**
     * Abrir modal de confirmación de pedido
     * 
     * @param int|null $quoteId ID de la cotización a confirmar (opcional)
     */

    public function confirmarPedido($quoteId = null)
    {
        $this->ensureTenantConnection();
        $this->confirmationLoading = true;

        try {
            // --- AUTOGUARDADO AUTOMÁTICO ANTES DE CONFIRMAR ---
            // Si hay cambios sin guardar, los guardamos primero de forma silenciosa
            if ($this->cartHasChanges && ($this->editingQuoteId || $this->editingRemissionId)) {
                Log::info('🚀 Autoguardado activado en confirmarPedido');
                $this->saveChangesInternal();
            }

            // Fix: Usar editingQuoteId si no se pasa quoteId explícitamente y estamos editando
            $quoteIdToCheck = $quoteId ?? $this->editingQuoteId;

            if ($quoteIdToCheck) {
                $quoteId = $quoteIdToCheck;
                // Verificar si YA existe una remisión para esta cotización
                if (InvRemissions::where('quoteId', $quoteId)->exists()) {
                    $this->quoteHasRemission = true; // Actualizar estado visual
                    $this->confirmationLoading = false;
                    $this->dispatch('show-toast', [
                        'type' => 'error',
                        'message' => 'Esta cotización YA tiene una remisión generada.'
                    ]);
                    return;
                }
            }

            // Si se proporciona un quoteId, cargar la cotización primero
            if ($quoteId) {
                // Usamos la lógica existente para cargar los items en $this->quoterItems
                $this->loadQuoteForEditing($quoteId);
            }

            if (empty($this->quoterItems)) {
                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => 'No hay productos para confirmar'
                ]);
                $this->confirmationLoading = false;
                return;
            }

            // VALIDACIÓN: Verificar disponibilidad de inventario antes de confirmar
            $inventoryErrors = $this->validateInventoryAvailability();
            if (!empty($inventoryErrors)) {
                $this->confirmationLoading = false;
                $errorMessage = "Inventario insuficiente:\n" . implode("\n", $inventoryErrors);
                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => $errorMessage
                ]);
                return;
            }

            // Obtener datos del cliente de la cotización o del cliente seleccionado actualmente
            // Si se cargó la cotización, loadQuoteForEditing ya estableció selectedCustomer
            if (!$this->selectedCustomer && $quoteId) {
                $quote = VntQuote::find($quoteId);
                if ($quote && $quote->customerId) {
                    $customer = VntCompany::find($quote->customerId);
                    if ($customer) {
                        $this->selectedCustomer = [
                            'id' => $customer->id,
                            'businessName' => $customer->businessName,
                            // ... otros campos si son necesarios para la remisión
                        ];
                    }
                }
            }

            // --- Lógica de creación de Remisión AUTOMÁTICA ---

            // 1. Obtener consecutivo
            $consecutive = $this->generateRemissionConsecutive();

            // 2. Obtener Razón por defecto (ID 1 o la primera encontrada)
            // Se asume que existe al menos una razón. Si no, esto podría fallar, validar si es necesario crear una por defecto.

            // 3. Crear cabecera de Remisión
            // Nota: Se asume que 'reasonId' y otros campos fueron agregados al fillable como se planeó.
            $remission = InvRemissions::create([
                'consecutive'    => $consecutive,
                'status'         => 'REGISTRADO',
                'userId'         => auth()->id(),
                'warehouseId'    => session('warehouse_id', 1), // Asumiendo warehouse en sesión o default 1
                'quoteId'        => $quoteId, // Vincular con la cotización original si existe
            ]);

            // 4. Crear detalles
            $detailsCreated = 0;
            foreach ($this->quoterItems as $item) {
                InvDetailRemissions::create([
                    'remissionId' => $remission->id,
                    'itemId'      => $item['id'],
                    'quantity'    => $item['quantity'],
                    'value'       => $item['price'],
                    'tax'         => 0, // Por defecto 0 o implementar lógica de impuestos si existe en el item
                ]);
                $detailsCreated++;
            }

            Log::info('Remisión creada automáticamente desde confirmarPedido', [
                'remission_id' => $remission->id,
                'quote_id'     => $quoteId,
                'items_count'  => $detailsCreated
            ]);

            // 5. Actualizar status de la cotización a "REMISIÓN"
            if ($quoteId) {
                $quote = VntQuote::find($quoteId);
                if ($quote) {
                    $quote->status = 'REMISIÓN';
                    $quote->save();

                    Log::info('Status de cotización actualizado a REMISIÓN', [
                        'quote_id' => $quoteId,
                        'consecutive' => $quote->consecutive
                    ]);
                }
            }

            // 6. Limpiar y Redirigir
            $this->quoterItems = [];
            $this->selectedCustomer = null;
            $this->observaciones = null;
            $this->cartHasChanges = false; // Resetear bandera de cambios
            session()->forget('quoter_items');
            $this->confirmationLoading = false;

            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => 'Pedido confirmado. Remisión #' . $consecutive . ' creada.'
            ]);

            // Sincronizar con Alpine.js/IndexedDB para limpiar memoria local
            $this->dispatch('cart-updated', items: []);
            $this->dispatch('customer-selected', customer: null);

            // Redirigir a la lista de cotizaciones (o remisiones si se prefiere)
            return redirect()->route('tenant.quoter');
        } catch (\Exception $e) {
            Log::error('Error en confirmarPedido (Automático): ' . $e->getMessage());
            $this->confirmationLoading = false;
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Error al confirmar pedido: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Validar disponibilidad de inventario antes de confirmar orden
     * 
     * @return array Array de errores de inventario (vacío si todo está bien)
     */
    private function validateInventoryAvailability()
    {
        $errors = [];

        try {
            foreach ($this->quoterItems as $item) {
                // Aquí iría la lógica para verificar inventario disponible
                // Por ahora, retornamos array vacío (sin errores)
                // Implementar según tu estructura de inventario
            }
        } catch (\Exception $e) {
            Log::warning('Error validando inventario: ' . $e->getMessage());
        }

        return $errors;
    }

    /**
     * Cerrar modal de confirmación
     */
    public function closeConfirmationModal()
    {
        $this->showConfirmationModal = false;
        $this->selectedReason = null;
        $this->confirmationLoading = false;
    }


    /**
     * Generar consecutivo para remisión
     */
    private function generateRemissionConsecutive()
    {
        $lastRemission = InvRemissions::orderBy('consecutive', 'desc')->first();
        return $lastRemission ? $lastRemission->consecutive + 1 : 1;
    }
    /**
     * Procesa un pedido realizado en modo offline cuando se recupera la conexión.
     * Maneja la creación de clientes temporales si es necesario.
     */
    /**
     * Procesa un pedido realizado en modo offline despachando un Job para procesamiento asíncrono.
     */
    /**
     * Sincroniza un cliente creado offline sin necesidad de un pedido.
     */
    public function syncOfflineCustomer($customerData)
    {
        error_log("👤 [CRITICAL] syncOfflineCustomer para " . ($customerData['identification'] ?? 'N/A'));
        Log::info('👤 [SYNC] syncOfflineCustomer INVOCADO', ['identification' => $customerData['identification'] ?? 'N/A']);



        
        try {
            $job = new \App\Jobs\Tenant\Quoter\ProcessOfflineOrderJob(
                [
                    'uuid' => $customerData['uuid'] ?? 'CUST-' . uniqid(),
                    'cliente' => array_merge($customerData, ['isTemporary' => true]),
                    'items' => [], // Sin items, solo queremos crear el cliente
                    'fecha' => $customerData['created_at'] ?? now(),
                    'observations' => 'Registro de cliente offline solo'
                ],
                auth()->id(),
                session('warehouse_id', 1),
                session('branch_id', 1),
                tenant('id')
            );


            dispatch($job);

            return [
                'success' => true,
                'message' => 'Cliente encolado para creación'
            ];
        } catch (\Exception $e) {
            Log::error('❌ [DEBUG] Error al sincronizar cliente offline: ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function processOfflineOrder($orderData)

    {
        error_log("📥 [CRITICAL] processOfflineOrder para UUID " . ($orderData['uuid'] ?? 'N/A'));
        Log::info('📥 [SYNC] processOfflineOrder INVOCADO', [

            'uuid' => $orderData['uuid'] ?? 'SIN_UUID',
            'order_keys' => array_keys($orderData),
            'auth_id' => auth()->id()
        ]);



        try {
            // Despachar el Job para procesar el pedido en segundo plano con Redis
            $job = new \App\Jobs\Tenant\Quoter\ProcessOfflineOrderJob(
                $orderData,
                auth()->id(),
                session('warehouse_id', 1),
                session('branch_id', 1),
                tenant('id')
            );


            Log::info('🚀 [SYNC] Despachando Job...', ['job_class' => get_class($job)]);
            
            dispatch($job);

            Log::info('✅ [SYNC] Job despachado exitosamente');


            return [
                'success' => true,
                'message' => 'Pedido encolado para sincronización',
                'uuid' => $orderData['uuid'] ?? null
            ];
        } catch (\Exception $e) {
            Log::error('❌ [DEBUG] Error al despachar pedido offline: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return [
                'success' => false,
                'message' => 'Error al procesar la sincronización local: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Crea un cliente y opcionalmente un usuario de forma rápida (Unificado Online)
     */
    public function saveQuickCustomer($customerData)
    {
        $this->ensureTenantConnection();

        try {
            DB::beginTransaction();

            $customerId = $customerData['id'] ?? null;
            $newCompany = null;
            $companyService = app(CompanyService::class);

            // 1. Preparar datos para el CompanyService
            $companyData = [
                'typeIdentificationId' => $customerData['typeIdentificationId'] ?: 1,
                'identification' => $customerData['identification'],
                'businessName' => $customerData['businessName'],
                'firstName' => $customerData['businessName'],
                'billingEmail' => $customerData['billingEmail'] ?? null,
                'business_phone' => $customerData['phone'] ?? null,
                'typePerson' => ($customerData['typeIdentificationId'] == 2) ? 'Juridica' : 'Natural',
                'status' => 1,
                'type' => 'CLIENTE',
                'regimeId' => 2,
                'fiscalResponsabilityId' => 1
            ];

            $warehouses = [
                [
                    'name' => 'Sucursal Principal',
                    'address' => $customerData['address'] ?? 'Sin dirección',
                    'district' => 'Sin Barrio',
                    'cityId' => 1, // Bogotá por defecto
                    'main' => 1,
                    'status' => 1
                ]
            ];

            if ($customerId) {
                // Actualizar cliente existente
                $newCompany = VntCompany::find($customerId);
                if ($newCompany) {
                    $newCompany->update($companyData);
                    
                    // Actualizar sucursal principal
                    $mainWarehouse = $newCompany->mainWarehouse;
                    if ($mainWarehouse) {
                        $mainWarehouse->update($warehouses[0]);
                        
                        // Actualizar teléfono en contactos del almacén
                        $contact = $mainWarehouse->activeContacts->first();
                        if ($contact) {
                            $contact->update(['business_phone' => $customerData['phone'] ?? null]);
                        }
                    }
                    Log::info("✅ [EDICIÓN] Cliente ID: {$customerId} actualizado");
                }
            } else {
                // Crear nuevo cliente
                $newCompany = $companyService->create($companyData, $warehouses);
                Log::info("✅ [CREACIÓN] Nuevo cliente creado");
            }

            if (!$newCompany) {
                throw new \Exception("No se pudo procesar el cliente");
            }

            // 2. Crear usuario si se solicitó
            if (!empty($customerData['createUser']) && !empty($customerData['billingEmail'])) {
                $existingUser = User::where('email', $customerData['billingEmail'])->first();
                
                if (!$existingUser) {
                    $newUser = User::create([
                        'name' => $customerData['businessName'],
                        'email' => $customerData['billingEmail'],
                        'password' => Hash::make('12345678'),
                        'profile_id' => 17,
                        'contact_id' => $newCompany->mainWarehouse?->contacts->first()?->id,
                        'phone' => $customerData['phone'] ?? null,
                    ]);

                    UserTenant::create([
                        'user_id' => $newUser->id,
                        'tenant_id' => session('tenant_id'),
                        'is_active' => 1,
                    ]);

                    // Copiar productos
                    \App\Jobs\CopyProductsToClientJob::dispatch($newCompany->id);
                }
            }

            DB::commit();

            $routeId = null;
            if (auth()->user()->profile_id == 2) {
                $routeId = $customerData['route_id'] ?? null;
            } elseif (auth()->user()->profile_id == 4) {
                // Para vendedor, buscar su ruta en la central
                $sellerRoute = DB::connection('central')->table('tat_routes')
                    ->where('salesman_id', auth()->id())
                    ->whereNull('deleted_at')
                    ->first();
                $routeId = $sellerRoute ? $sellerRoute->id : null;
                Log::info('Asignando ruta automática a vendedor en guardado', [
                    'user_id' => auth()->id(),
                    'route_id' => $routeId
                ]);
            }

            if ($routeId) {
                // Calcular el siguiente orden secuencial para esta ruta
                $maxOrders = TatCompanyRoute::where('route_id', $routeId)
                    ->selectRaw('MAX(sales_order) as max_sales, MAX(delivery_order) as max_delivery')
                    ->first();

                $nextSalesOrder = ($maxOrders->max_sales ?? 0) + 1;
                $nextDeliveryOrder = ($maxOrders->max_delivery ?? 0) + 1;

                TatCompanyRoute::updateOrCreate(
                    ['company_id' => $newCompany->id],
                    [
                        'route_id' => $routeId,
                        'sales_order' => $nextSalesOrder,
                        'delivery_order' => $nextDeliveryOrder
                    ]
                );
                Log::info('Cliente asociado a ruta con orden secuencial', [
                    'company_id' => $newCompany->id, 
                    'route_id' => $routeId,
                    'sales_order' => $nextSalesOrder,
                    'delivery_order' => $nextDeliveryOrder
                ]);
            }

            // 3. Seleccionar el nuevo cliente en el cotizador
            $this->selectedCustomer = [
                'id' => $newCompany->id,
                'businessName' => $newCompany->businessName,
                'firstName' => $newCompany->firstName,
                'lastName' => $newCompany->lastName,
                'identification' => $newCompany->identification,
                'billingEmail' => $newCompany->billingEmail,
            ];

            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => $customerId ? 'Cliente actualizado exitosamente' : 'Cliente creado y seleccionado exitosamente'
            ]);

            // Notificar a Alpine
            $this->dispatch('customer-selected', customer: $this->selectedCustomer);

            return ['success' => true, 'customerId' => $newCompany->id];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error en saveQuickCustomer: ' . $e->getMessage());
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Error al procesar cliente: ' . $e->getMessage()
            ]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Carga los datos del cliente seleccionado para edición
     */
    public function editCustomer()
    {
        if (!$this->selectedCustomer || !isset($this->selectedCustomer['id'])) {
            return;
        }

        $this->ensureTenantConnection();

        try {
            $company = VntCompany::with(['mainWarehouse.activeContacts'])->find($this->selectedCustomer['id']);
            
            if (!$company) {
                $this->dispatch('show-toast', ['type' => 'error', 'message' => 'No se encontró el cliente']);
                return;
            }

            $mainWarehouse = $company->mainWarehouse;
            $phone = $mainWarehouse?->activeContacts->first()?->business_phone;
            
            // Obtener ruta asignada si existe
            $routeInfo = TatCompanyRoute::where('company_id', $company->id)->first();

            $customerData = [
                'id' => $company->id,
                'typeIdentificationId' => $company->typeIdentificationId,
                'identification' => $company->identification,
                'businessName' => $company->businessName,
                'phone' => $phone,
                'address' => $mainWarehouse?->address,
                'billingEmail' => $company->billingEmail,
                'route_id' => $routeInfo?->route_id,
            ];

            // Despachar evento para que Alpine cargue los datos
            $this->dispatch('load-customer-data', customer: $customerData);
            
            Log::info("💼 [EDICIÓN] Datos cargados para edición", ['id' => $company->id]);

        } catch (\Exception $e) {
            Log::error('Error en editCustomer: ' . $e->getMessage());
            $this->dispatch('show-toast', ['type' => 'error', 'message' => 'Error al cargar datos']);
        }
    }



    public function openRoutes()
    {
        $this->showRoutesModal = true;
    }

    public function closeRoutesModal()
    {
        $this->showRoutesModal = false;
    }
}
