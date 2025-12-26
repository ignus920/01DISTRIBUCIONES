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

class ProductQuoter extends Component
{
    use WithPagination;

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

    protected $listeners = [
        'customer-created' => 'onCustomerCreated',
        'vnt-company-saved' => 'onCustomerCreated',
        'customer-updated' => 'onCustomerUpdated',
        'customer-form-cancelled' => 'cancelCreateCustomer'
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

    public function mount($quoteId = null, $restockOrder = null)
    {
        // Obtener viewType de la ruta o usar desktop por defecto
        $this->viewType = request()->route('viewType', 'desktop');
        $this->ensureTenantConnection();

        // 📝 LOG DEBUG: Inicio del Mount
        Log::info('ProductQuoter Mount', [
            'quoteId' => $quoteId,
            'restockOrder_param' => $restockOrder,
            'restockOrder_query' => request()->query('restockOrder'),
            'editPreliminary_query' => request()->query('editPreliminary'),
            'user_id' => Auth::id()
        ]);

        // Si se pasa un quoteId, estamos editando
        if ($quoteId) {
            $this->loadQuoteForEditing($quoteId);
        } elseif ($restockOrder || request()->query('restockOrder')) {
            $orderToLoad = $restockOrder ?: request()->query('restockOrder');
            $this->loadRestockForEditing($orderToLoad);
        } elseif (request()->query('editPreliminary') === 'true') {
            $this->loadPreliminaryRestockForEditing();
        } else {
            $this->quoterItems = session('quoter_items', []);
        }

        $this->calculateTotal();
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

        $viewName = $this->viewType === 'mobile'
            ? 'livewire.tenant.quoter.components.mobile-product-quoter'
            : 'livewire.tenant.quoter.components.desktop-product-quoter';

        return view($viewName, [
            'products' => $products
        ])->layout('layouts.app');
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

            $this->cachedSaleDays = array_map(function($day) {
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
            // Si existe en una orden confirmada, ofrecer cargar esa orden
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

    private function performAddToQuoter($productId, $selectedPrice, $priceLabel)
    {
        // Verificar si el producto ya está en el cotizador (sin consulta DB)
        $existingIndex = $this->findProductInQuoter($productId);

        if ($existingIndex !== false) {
            // Si ya existe, incrementar la cantidad
            $this->quoterItems[$existingIndex]['quantity']++;
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
                'quantity' => 1,
                'description' => $product->description,
            ];
        }

        // Optimización: Solo guardar en sesión si realmente cambió
        session(['quoter_items' => $this->quoterItems]);

        // Calcular total de forma más eficiente
        $this->calculateTotal();

        // Toast más rápido sin información innecesaria
        $this->dispatch('show-toast', [
            'type' => 'success',
            'message' => 'Agregado al carrito'
        ]);
    }

    public function updateQuantity($index, $quantity)
    {
        if ($quantity <= 0) {
            $this->removeFromQuoter($index);
            return;
        }

        $this->quoterItems[$index]['quantity'] = $quantity;
        session(['quoter_items' => $this->quoterItems]);
        $this->calculateTotal();
    }

    public function removeFromQuoter($index)
    {
        unset($this->quoterItems[$index]);
        $this->quoterItems = array_values($this->quoterItems); // Reindexar array
        session(['quoter_items' => $this->quoterItems]);
        $this->calculateTotal();

        $this->dispatch('show-toast', [
            'type' => 'info',
            'message' => 'Producto removido del cotizador'
        ]);
    }





    //funcion limpiar cotizacion completa con cliente y carrito de compras
    public function clearQuoter()
    {
        $this->selectedCustomer = null;
        $this->customerSearch = '';
        $this->showCreateCustomerForm = false;
        $this->showCreateCustomerButton = false;
        $this->quoterItems = [];
        session()->forget('quoter_items');
        $this->calculateTotal();
        $this->showCartModal = false;

        $this->dispatch('show-toast', [
            'type' => 'info',
            'message' => 'Cotizador limpiado'
        ]);
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
    }





    
    // funcion para guardar una cotizacion 
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
                'message' => 'Debe seleccionar un cliente para la cotización'
            ]);
            return;
        }

        $this->ensureTenantConnection();

        try {
            // Obtener el siguiente consecutivo
            $lastQuote = VntQuote::orderBy('consecutive', 'desc')->first();
            $nextConsecutive = $lastQuote ? $lastQuote->consecutive + 1 : 1;

            // Crear la cotización
            $quote = VntQuote::create([
                'consecutive' => $nextConsecutive,
                'status' => 'REGISTRADO',
                'typeQuote' => 'POS',
                'customerId' => $this->selectedCustomer['id'],
                'warehouseId' => session('warehouse_id', 1), // Si tienes warehouse en sesión
                'userId' => auth()->id(),
                'observations' => $this->observaciones,
                'branchId' => session('branch_id', 1) // Si tienes branch en sesión
            ]);

            // Crear los detalles de la cotización
            foreach ($this->quoterItems as $item) {
                VntDetailQuote::create([
                    'quantity' => $item['quantity'],
                    'tax_percentage' => 0, // Puedes ajustar esto según tus necesidades
                    'price' => $item['price'],
                    'quoteId' => $quote->id,
                    'itemId' => $item['id'],
                    'description' => $item['name'],
                    'priceList' => $item['price'] // O el ID de la lista de precios si lo tienes
                ]);
            }

            // Limpiar el cotizador y campos del formulario
            $this->quoterItems = [];
            $this->selectedCustomer = null;              // Limpiar cliente seleccionado
            $this->customerSearch = '';                  // Limpiar campo de búsqueda de cliente
            $this->showCreateCustomerForm = false;      // Ocultar formulario de creación
            $this->showCreateCustomerButton = false;    // Ocultar botón de creación
            session()->forget('quoter_items');
            $this->calculateTotal();
            $this->showCartModal = false;

            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => 'Cotización #' . $nextConsecutive . ' guardada exitosamente'
            ]);

            // Redirigir a la página de cotizaciones según el tipo de vista
            $routeName = $this->viewType === 'mobile'
                ? 'tenant.quoter.mobile'
                : 'tenant.quoter.desktop';

            return redirect()->route($routeName);

        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Error al guardar la cotización: ' . $e->getMessage()
            ]);
        }
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

    // Actualizar sesión
    session(['quoter_items' => $this->quoterItems]);

    // Recalcular total si existe el método
    $this->calculateTotal ?? false ? $this->calculateTotal() : null;

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
    }

    /**
     * Editar el cliente actualmente seleccionado
     */
    public function editCustomer()
    {
        Log::info('🔧 editCustomer() llamado', [
            'selectedCustomer' => $this->selectedCustomer,
            'showCreateCustomerForm_antes' => $this->showCreateCustomerForm,
            'editingCustomerId_antes' => $this->editingCustomerId
        ]);

        if ($this->selectedCustomer) {
            $this->editingCustomerId = $this->selectedCustomer['id'];
            $this->showCreateCustomerForm = true;
            $this->showCreateCustomerButton = false;

            Log::info('✅ Cliente configurado para edición', [
                'editingCustomerId' => $this->editingCustomerId,
                'showCreateCustomerForm' => $this->showCreateCustomerForm,
                'showCreateCustomerButton' => $this->showCreateCustomerButton
            ]);
        } else {
            Log::warning('⚠️ No hay cliente seleccionado para editar');
        }
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

    public function getQuoterCountProperty()
    {
        return collect($this->quoterItems)->sum('quantity');
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

    public function increaseQuantity($productId)
    {
        $this->ensureTenantConnection();

        // Verificar si el producto ya está en el cotizador
        $existingIndex = $this->findProductInQuoter($productId);

        if ($existingIndex !== false) {
            // Si ya existe, incrementar la cantidad
            $this->quoterItems[$existingIndex]['quantity']++;

            // Guardar en sesión
            session(['quoter_items' => $this->quoterItems]);
            $this->calculateTotal();

            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => 'Cantidad aumentada'
            ]);
        }
    }

    public function loadQuoteForEditing($quoteId)
    {
        $this->ensureTenantConnection();

        try {
            $quote = VntQuote::with('detalles')->findOrFail($quoteId);

            $this->editingQuoteId = $quoteId;
            $this->isEditing = true;

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
                }
            }

            // Cargar productos de la cotización
            $this->quoterItems = [];
            foreach ($quote->detalles as $detalle) {
                $product = Items::find($detalle->itemId);
                if ($product) {
                    $this->quoterItems[] = [
                        'id' => $product->id,
                        'name' => $product->display_name,
                        'sku' => $product->sku,
                        'price' => $detalle->value,
                        'price_label' => 'Precio seleccionado', // Podrías mejorarlo para detectar el label correcto
                        'quantity' => $detalle->quantity,
                        'description' => $product->description,
                    ];
                }
            }

            // Guardar en sesión
            session(['quoter_items' => $this->quoterItems]);

            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => 'Cotización #' . $quote->consecutive . ' cargada para edición'
            ]);

        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Error al cargar la cotización: ' . $e->getMessage()
            ]);
        }
    }

    public function updateQuote()
    {
        if (!$this->isEditing || !$this->editingQuoteId) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'No hay cotización en modo edición'
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

        if (!$this->selectedCustomer) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Debe seleccionar un cliente para la cotización'
            ]);
            return;
        }

        $this->ensureTenantConnection();

        try {
            $quote = VntQuote::findOrFail($this->editingQuoteId);

            // Actualizar la cotización
            $quote->update([
                'customerId' => $this->selectedCustomer['id'],
                'observations' => $this->observaciones,
            ]);

            // Eliminar detalles existentes
            VntDetailQuote::where('quoteId', $quote->id)->delete();

            // Crear los nuevos detalles
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

            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => 'Cotización #' . $quote->consecutive . ' actualizada exitosamente'
            ]);

            // Redirigir a la página de cotizaciones según el tipo de vista
            $routeName = $this->viewType === 'mobile'
                ? 'tenant.quoter.mobile'
                : 'tenant.quoter.desktop';

            return redirect()->route($routeName);

        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Error al actualizar la cotización: ' . $e->getMessage()
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
                    $message = $this->isEditingRestock ?
                        "Solicitud #{$orderNumber} actualizada y migrada a cotización #{$quote->consecutive}" :
                        "Solicitud #{$orderNumber} confirmada y migrada a cotización #{$quote->consecutive}";
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

        if(!$companyId) {
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
                'status' => 'REGISTRADO',
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

                if ($quote) {
                    $this->dispatch('show-toast', [
                        'type' => 'success',
                        'message' => "Solicitud confirmada y migrada a cotización #{$quote->consecutive}"
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

        if(!$companyId) {
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
        ", $params);
    }

    $this->customerSearchResults = array_map(function($customer) {
        return [
            'id' => $customer->id,
            'identification' => $customer->identification,
            'display_name' => $customer->businessName ?: ($customer->firstName . ' ' . $customer->lastName),
            'sale_day' => $customer->sale_day
        ];
    }, $customers);

    // Auto-open modal if no results and sufficient length
    if (empty($this->customerSearchResults) && strlen($this->customerSearch) >= 3) {
        $this->openCustomerModal();
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
        try {
            if (empty($this->quoterItems) && !$quoteId) {
                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => 'No hay productos en el cotizador'
                ]);
                return;
            }

            $this->ensureTenantConnection();
            
            // Si se proporciona un quoteId, cargar la cotización
            if ($quoteId) {
                $this->loadQuoteForEditing($quoteId);
            }
            
            // Cargar razones disponibles
            $this->availableReasons = InvReason::active()->get()->toArray();
            
            // Abrir modal
            $this->showConfirmationModal = true;
        } catch (\Exception $e) {
            Log::error('Error en confirmarPedido: ' . $e->getMessage());
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Error al abrir modal: ' . $e->getMessage()
            ]);
        }
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
     * Confirmar y procesar el pedido
     */
    public function processOrderConfirmation()
    {
        if (empty($this->quoterItems)) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'No hay productos en el cotizador'
            ]);
            return;
        }

        if (!$this->selectedReason) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Debe seleccionar una razón para el pedido'
            ]);
            return;
        }

        $this->confirmationLoading = true;
        $this->ensureTenantConnection();

        try {
            // Obtener la razón seleccionada
            $reason = InvReason::find($this->selectedReason);
            
            if (!$reason) {
                throw new \Exception('Razón no encontrada');
            }

            // Crear remisión
            $remission = InvRemissions::create([
                'consecutive' => $this->generateRemissionConsecutive(),
                'reasonId' => $reason->id,
                'userId' => auth()->id(),
                'status' => 'REGISTRADO',
                'total_value' => $this->totalAmount,
                'observations' => $this->observaciones ?? null,
            ]);

            // Crear detalles de remisión para cada producto
            $detailsCreated = 0;
            foreach ($this->quoterItems as $item) {
                InvDetailRemissions::create([
                    'remissionId' => $remission->id,
                    'itemId' => $item['id'],
                    'quantity' => $item['quantity'],
                    'value' => $item['price'],
                    'discount' => 0,
                    'tax' => 0,
                ]);
                $detailsCreated++;
            }

            Log::info('Remisión creada exitosamente', [
                'remission_id' => $remission->id,
                'consecutive' => $remission->consecutive,
                'details_created' => $detailsCreated,
                'user_id' => auth()->id()
            ]);

            // Limpiar cotizador
            $this->quoterItems = [];
            $this->selectedCustomer = null;
            $this->customerSearch = '';
            $this->observaciones = null;
            session()->forget('quoter_items');
            $this->calculateTotal();

            // Cerrar modal
            $this->closeConfirmationModal();
            $this->showCartModal = false;

            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => 'Remisión #' . $remission->consecutive . ' creada exitosamente'
            ]);

            // Redirigir a la página de remisiones o cotizaciones
            return redirect()->route('tenant.quoter');

        } catch (\Exception $e) {
            Log::error('Error al procesar confirmación de pedido: ' . $e->getMessage());
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Error al confirmar pedido: ' . $e->getMessage()
            ]);
        } finally {
            $this->confirmationLoading = false;
        }
    }

    /**
     * Generar consecutivo para remisión
     */
    private function generateRemissionConsecutive()
    {
        $lastRemission = InvRemissions::orderBy('consecutive', 'desc')->first();
        return $lastRemission ? $lastRemission->consecutive + 1 : 1;
    }

}