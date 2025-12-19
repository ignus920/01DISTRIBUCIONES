<?php

namespace App\Livewire\TAT\Quoter;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\TAT\Items\TatItems;
use App\Models\TAT\Quoter\Quote;
use App\Models\TAT\Quoter\QuoteItem;
use App\Models\TAT\Customer\Customer as TatCustomer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QuoterView extends Component
{
    use WithPagination;

    // Propiedades públicas
    public $currentSearch = '';
    public $searchResults = [];
    public $additionalSuggestions = [];
    public $cartItems = [];
    public $total = 0;
    public $companyId;
    public $selectedIndex = -1;

    // Propiedades para cliente
    public $selectedCustomer = null;
    public $customerSearch = '';
    public $customerSearchResults = [];
    public $showClientSearch = false;
    public $showCustomerModal = false;
    public $searchedIdentification = '';

    // Propiedades para paginación
    protected $paginationTheme = 'bootstrap';

    // Listeners para eventos
    protected $listeners = [
        'customer-created' => 'handleCustomerCreated',
        'customer-modal-closed' => 'handleCustomerModalClosed'
    ];

    public function mount()
    {
        // Obtener company_id del usuario autenticado
        $user = Auth::user();
        $this->companyId = $this->getUserCompanyId($user);

        Log::info('Mount QuoterView', [
            'user_id' => $user->id,
            'user_profile_id' => $user->profile_id,
            'user_contact_id' => $user->contact_id,
            'companyId' => $this->companyId
        ]);

        if (!$this->companyId) {
            Log::error('No se pudo determinar company_id');
            session()->flash('error', 'No se pudo determinar la empresa del usuario.');
            return redirect()->route('tenant.dashboard');
        }

        $this->loadCartFromSession();
        $this->loadRestoredData(); // Cargar datos restaurados del pago cancelado
        $this->calculateTotal();
        $this->loadDefaultCustomer();

        Log::info('QuoterView mounted successfully', [
            'companyId' => $this->companyId,
            'cartItems' => count($this->cartItems),
            'total' => $this->total,
            'defaultCustomer' => $this->selectedCustomer ? $this->selectedCustomer['identification'] : null
        ]);
    }

    /**
     * Hook para detectar actualizaciones en cartItems (wire:model)
     */
    public function updatedCartItems($value, $key)
    {
        // El key viene en formato "0.quantity" o "0.price"
        $parts = explode('.', $key);
        
        if (count($parts) === 2 && $parts[1] === 'quantity') {
            $index = $parts[0];
            $quantity = $value;
            
            // 1. Forzar entero
            $quantity = (int)$quantity;
            $quantity = max(1, $quantity); // Mínimo 1

            // 2. Validar Stock
            $item = $this->cartItems[$index];
            $stock = $item['stock'];

            if ($quantity > $stock) {
                $quantity = $stock;
                
                // Emitir alerta
                $this->dispatch('swal:warning', [
                    'title' => 'Stock Insuficiente',
                    'text' => "No puedes agregar más de {$stock} unidades (lo sentimos).",
                ]);
            }

            // 3. Aplicar valor corregido
            $this->cartItems[$index]['quantity'] = $quantity;
            $this->cartItems[$index]['subtotal'] = $quantity * $this->cartItems[$index]['price'];
            
            $this->calculateTotal();
            $this->saveCartToSession();
        }
    }

    /**
     * Obtener el company_id del usuario autenticado
     */
    protected function getUserCompanyId($user)
    {
        if ($user->contact_id) {
            $contact = DB::table('vnt_contacts')
                ->where('id', $user->contact_id)
                ->first();

            if ($contact && isset($contact->warehouseId)) {
                $warehouse = DB::table('vnt_warehouses')
                    ->where('id', $contact->warehouseId)
                    ->first();

                return $warehouse ? $warehouse->companyId : null;
            }
        }

        return null;
    }

    /**
     * Cargar productos disponibles con filtro de búsqueda
     */
    public function getAvailableProductsProperty()
    {
        $query = TatItems::query()
            ->byCompany($this->companyId)
            ->active()
            ->where('stock', '>', 0);

        return $query->take(5)->get();
    }

    /**
     * Obtener productos sugeridos
     */
    public function getSuggestedProductsProperty()
    {
        return TatItems::query()
            ->byCompany($this->companyId)
            ->active()
            ->where('stock', '>', 0)
            ->take(4)
            ->get();
    }

    /**
     * Actualizar resultados de búsqueda
     */
    public function updatedCurrentSearch()
    {
        $this->selectedIndex = -1; // Reset selected index when search changes

        Log::info('Búsqueda actualizada', [
            'search' => $this->currentSearch,
            'length' => strlen($this->currentSearch),
            'companyId' => $this->companyId
        ]);

        // Debug: Ver algunos productos disponibles
        $allProducts = TatItems::query()
            ->byCompany($this->companyId)
            ->active()
            ->where('stock', '>', 0)
            ->take(5)
            ->get(['name', 'sku', 'stock']);

        Log::info('Productos disponibles (muestra)', [
            'products' => $allProducts->toArray()
        ]);

        if (strlen($this->currentSearch) >= 1) {
            $query = TatItems::query()
                ->byCompany($this->companyId)
                ->active()
                ->where(function ($q) {
                    $q->whereRaw('LOWER(name) like ?', ['%' . strtolower($this->currentSearch) . '%'])
                      ->orWhereRaw('LOWER(sku) like ?', ['%' . strtolower($this->currentSearch) . '%']);
                })
                ->orderByRaw('CASE WHEN stock > 0 THEN 0 ELSE 1 END') // Productos con stock primero
                ->orderBy('stock', 'desc'); // Ordenar por stock descendente

            // Obtener el SQL y parámetros para debug
            $sql = $query->toSql();
            $bindings = $query->getBindings();

            Log::info('Query SQL', [
                'sql' => $sql,
                'bindings' => $bindings
            ]);

            $results = $query->get(); // SIN LÍMITE - Obtener TODOS los resultados

            Log::info('Resultados encontrados', [
                'total_results' => $results->count(),
                'results' => $results->take(5)->pluck('name')->toArray()
            ]);

            // TODOS los resultados en dropdown principal
            $this->searchResults = $results->toArray();

            // No usar resultados adicionales
            $this->additionalSuggestions = [];
        } else {
            $this->searchResults = [];
            $this->additionalSuggestions = [];
        }
    }

    /**
     * Manejar navegación con flechas del teclado
     */
    public function navigateResults($direction)
    {
        $totalResults = count($this->searchResults);

        if ($totalResults === 0) {
            return;
        }

        if ($direction === 'down') {
            // Buscar el próximo producto con stock
            $newIndex = $this->selectedIndex + 1;
            while ($newIndex < $totalResults && $this->searchResults[$newIndex]['stock'] <= 0) {
                $newIndex++;
            }
            $this->selectedIndex = min($newIndex, $totalResults - 1);

            // Si llegamos al final y no hay stock, volver al último con stock
            if ($this->selectedIndex < $totalResults && $this->searchResults[$this->selectedIndex]['stock'] <= 0) {
                $this->selectedIndex = $this->findLastWithStock();
            }
        } else if ($direction === 'up') {
            // Buscar el anterior producto con stock
            $newIndex = $this->selectedIndex - 1;
            while ($newIndex >= 0 && $this->searchResults[$newIndex]['stock'] <= 0) {
                $newIndex--;
            }
            $this->selectedIndex = max($newIndex, -1);
        }
    }

    /**
     * Encontrar el último producto con stock
     */
    private function findLastWithStock()
    {
        for ($i = count($this->searchResults) - 1; $i >= 0; $i--) {
            if ($this->searchResults[$i]['stock'] > 0) {
                return $i;
            }
        }
        return -1;
    }

    /**
     * Seleccionar el producto actualmente resaltado con Enter
     */
    public function selectCurrentProduct()
    {
        if ($this->selectedIndex >= 0 && $this->selectedIndex < count($this->searchResults)) {
            $selectedProduct = $this->searchResults[$this->selectedIndex];
            $this->selectProduct($selectedProduct['id']);
        }
    }

    /**
     * Limpiar búsqueda con Escape
     */
    public function clearSearch()
    {
        $this->currentSearch = '';
        $this->searchResults = [];
        $this->additionalSuggestions = [];
        $this->selectedIndex = -1;
    }

    /**
     * Seleccionar producto desde la búsqueda
     */
    public function selectProduct($productId)
    {
        // Verificar que el producto tenga stock antes de agregarlo
        $product = TatItems::find($productId);

        if (!$product || $product->stock <= 0) {
            session()->flash('error', 'No se puede agregar un producto sin stock.');
            return;
        }

        $this->addToCart($productId);

        // Limpiar la búsqueda para permitir nueva búsqueda
        $this->currentSearch = '';
        $this->searchResults = [];
        $this->additionalSuggestions = [];
        $this->selectedIndex = -1;

        // Emitir evento para limpiar el input en el frontend
        $this->dispatch('product-selected');
    }

    /**
     * Agregar producto al carrito
     */
    public function addToCart($productId)
    {
        $product = TatItems::with('tax')->find($productId);
        
        Log::info('Adding to cart', [
            'product_id' => $productId,
            'tax_id' => $product ? $product->taxId : null,
            'tax_relation' => $product ? $product->tax : null,
            'connection' => $product ? $product->getConnectionName() : 'unknown'
        ]);

        if (!$product || !$product->hasStock()) {
            session()->flash('error', 'Producto no disponible o sin stock.');
            return;
        }

        $existingItemIndex = collect($this->cartItems)->search(function ($item) use ($productId) {
            return $item['id'] == $productId;
        });

        if ($existingItemIndex !== false) {
            // Si ya existe, incrementar cantidad
            // Verificamos stock antes de incrementar
            $newQuantity = $this->cartItems[$existingItemIndex]['quantity'] + 1;
             if ($newQuantity > $product->stock) {
                 $this->dispatch('swal:warning', [
                    'title' => 'Stock Insuficiente',
                    'text' => "No puedes agregar más de {$product->stock} unidades.",
                ]);
                return;
             }

            $this->cartItems[$existingItemIndex]['quantity'] = $newQuantity;
            $this->cartItems[$existingItemIndex]['subtotal'] =
                $this->cartItems[$existingItemIndex]['quantity'] * $this->cartItems[$existingItemIndex]['price'];
        } else {
            // Agregar nuevo item
            $this->cartItems[] = [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'price' => $product->price,
                'quantity' => 1,
                'subtotal' => $product->price,
                'stock' => $product->stock,
                'tax_name' => $product->tax ? $product->tax->name : 'N/A',
                'tax_percentage' => $product->tax ? $product->tax->percentage : 0
            ];
        }

        $this->calculateTotal();
        $this->saveCartToSession();

        session()->flash('success', 'Producto agregado al carrito.');
    }

    /**
     * Actualizar cantidad de un producto en el carrito
     */
    public function updateQuantity($productId, $quantity)
    {
        $quantity = max(0, (int)$quantity);

        $itemIndex = collect($this->cartItems)->search(function ($item) use ($productId) {
            return $item['id'] == $productId;
        });

        if ($itemIndex !== false) {
            $stock = $this->cartItems[$itemIndex]['stock'];

            // Verificar si la cantidad excede el stock disponible
            if ($quantity > $stock) {
                // Ajustar al máximo disponible
                $quantity = $stock;
                
                // Emitir alerta al frontend
                $this->dispatch('swal:warning', [
                    'title' => 'Stock Insuficiente',
                    'text' => "Solo hay {$stock} unidades disponibles de este producto.",
                ]);
            }

            // Actualizar cantidad y subtotal
            $this->cartItems[$itemIndex]['quantity'] = $quantity;
            $this->cartItems[$itemIndex]['subtotal'] = $quantity * $this->cartItems[$itemIndex]['price'];

            $this->calculateTotal();
            $this->saveCartToSession();
        }
    }

    /**
     * Actualizar precio de un producto en el carrito
     */
    public function updatePrice($productId, $newPrice)
    {
        $newPrice = max(0, (float)$newPrice);

        $itemIndex = collect($this->cartItems)->search(function ($item) use ($productId) {
            return $item['id'] == $productId;
        });

        if ($itemIndex !== false) {
            $this->cartItems[$itemIndex]['price'] = $newPrice;
            $this->cartItems[$itemIndex]['subtotal'] =
                $this->cartItems[$itemIndex]['quantity'] * $newPrice;

            $this->calculateTotal();
            $this->saveCartToSession();
        }
    }


    /**
     * Remover producto del carrito
     */
    public function removeFromCart($productId)
    {
        $this->cartItems = collect($this->cartItems)
            ->reject(function ($item) use ($productId) {
                return $item['id'] == $productId;
            })->values()->toArray();

        $this->calculateTotal();
        $this->saveCartToSession();

        session()->flash('success', 'Producto removido del carrito.');
    }

    /**
     * Limpiar todo el carrito
     */
    public function clearCart()
    {
        $this->cartItems = [];
        $this->total = 0;
        $this->saveCartToSession();

        session()->flash('success', 'Carrito limpiado.');
    }

    /**
     * Calcular total del carrito
     */
    protected function calculateTotal()
    {
        $this->total = collect($this->cartItems)->sum('subtotal');
    }

    /**
     * Guardar carrito en sesión
     */
    protected function saveCartToSession()
    {
        session(['quoter_cart' => $this->cartItems]);
    }

    /**
     * Cargar carrito desde sesión
     */
    protected function loadCartFromSession()
    {
        $this->cartItems = session('quoter_cart', []);
    }

    /**
     * Guardar cotización/venta
     */
    public function saveQuote()
    {
        if (empty($this->cartItems)) {
            session()->flash('error', 'No hay productos en el carrito para vender.');
            return;
        }

        if (!$this->selectedCustomer) {
            session()->flash('error', 'Debe seleccionar un cliente para realizar la venta.');
            return;
        }

        // Validar stock antes de proceder
        $stockErrors = [];
        foreach ($this->cartItems as $item) {
            $product = TatItems::find($item['id']);
            if (!$product) {
                $stockErrors[] = "Producto {$item['name']} no encontrado";
                continue;
            }

            if ($product->stock < $item['quantity']) {
                $stockErrors[] = "Stock insuficiente para {$item['name']} (Disponible: {$product->stock}, Requerido: {$item['quantity']})";
            }
        }

        if (!empty($stockErrors)) {
            session()->flash('error', 'No se puede completar la venta: ' . implode(', ', $stockErrors));
            return;
        }

        try {
            DB::beginTransaction();

            // Generar consecutivo para la venta
            $lastQuote = Quote::byCompany($this->companyId)->orderBy('consecutive', 'desc')->first();
            $consecutive = $lastQuote ? $lastQuote->consecutive + 1 : 1;

            // Crear observaciones con información del cliente
            $customerInfo = $this->selectedCustomer['display_name'] . ' (' . $this->selectedCustomer['identification'] . ')';
            $observations = 'Venta registrada para: ' . $customerInfo . ' - Total productos: ' . count($this->cartItems);

            // Crear la venta
            $quote = Quote::create([
                'company_id' => $this->companyId,
                'consecutive' => $consecutive,
                'status' => 'Registrado',
                'customerId' => $this->selectedCustomer['id'],
                'userId' => Auth::id(),
                'observations' => $observations,
            ]);

            // Agregar items a la venta
            foreach ($this->cartItems as $item) {
                QuoteItem::create([
                    'quoteId' => $quote->id,
                    'itemId' => $item['id'],
                    'quantity' => $item['quantity'],
                    'tax_percentage' => $item['tax_percentage'] ?? 0, // Usar el porcentaje real del producto
                    'price' => $item['price'],
                    'descripcion' => $item['name'],
                ]);

                // Opcional: Reducir stock del inventario
                $product = TatItems::find($item['id']);
                if ($product) {
                    $product->decrement('stock', $item['quantity']);
                }
            }

            DB::commit();

            $this->clearCart();
            $this->loadDefaultCustomer(); // Resetear al cliente por defecto

            // Log de la venta
            Log::info('Venta registrada', [
                'venta_id' => $quote->id,
                'consecutive' => $consecutive,
                'cliente' => $customerInfo,
                'total_productos' => count($this->cartItems),
                'total_venta' => $this->total,
                'company_id' => $this->companyId,
                'user_id' => Auth::id()
            ]);

            // Redirigir a la vista de pagos
            return redirect()->route('tenant.payment.quote', ['quoteId' => $quote->id]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al registrar venta', [
                'error' => $e->getMessage(),
                'company_id' => $this->companyId,
                'user_id' => Auth::id(),
                'cliente' => $this->selectedCustomer['display_name'] ?? 'N/A'
            ]);
            session()->flash('error', 'Error al registrar la venta: ' . $e->getMessage());
        }
    }

    /**
     * Generar factura (igual que venta pero con status diferente)
     */
    public function generateInvoice()
    {
        if (empty($this->cartItems)) {
            session()->flash('error', 'No hay productos en el carrito para facturar.');
            return;
        }

        if (!$this->selectedCustomer) {
            session()->flash('error', 'Debe seleccionar un cliente para generar la factura.');
            return;
        }

        // Reutilizar la misma lógica de venta
        $this->saveQuote();
    }

    /**
     * Cargar cliente predefinido (company_id = 0)
     */
    protected function loadDefaultCustomer()
    {
        $defaultCustomer = TatCustomer::where('company_id', 0)->first();

        if ($defaultCustomer) {
            $this->selectedCustomer = [
                'id' => $defaultCustomer->id,
                'identification' => $defaultCustomer->identification,
                'display_name' => $defaultCustomer->display_name,
                'typePerson' => $defaultCustomer->typePerson
            ];
        }
    }

    /**
     * Habilitar búsqueda de cliente
     */
    public function enableClientSearch()
    {
        $this->showClientSearch = true;
        $this->customerSearch = '';
        $this->customerSearchResults = [];
    }

    /**
     * Cancelar búsqueda de cliente y volver al predefinido
     */
    public function cancelClientSearch()
    {
        $this->showClientSearch = false;
        $this->customerSearch = '';
        $this->customerSearchResults = [];
        $this->loadDefaultCustomer();
    }

    /**
     * Buscar clientes en tiempo real
     */
    public function updatedCustomerSearch()
    {
        if (strlen($this->customerSearch) >= 1) {
            $this->customerSearchResults = TatCustomer::where(function ($companyQuery) {
                    // Buscar en la empresa actual O en clientes globales (company_id = 0)
                    $companyQuery->where('company_id', $this->companyId)
                                 ->orWhere('company_id', 0);
                })
                ->where(function ($query) {
                    $query->where('identification', 'like', '%' . $this->customerSearch . '%')
                          ->orWhere('businessName', 'like', '%' . $this->customerSearch . '%')
                          ->orWhere('firstName', 'like', '%' . $this->customerSearch . '%')
                          ->orWhere('lastName', 'like', '%' . $this->customerSearch . '%');
                })
                ->orderBy('company_id', 'desc') // Primero los de la empresa, luego los globales
                ->take(10) // Aumenté el límite para mostrar más resultados
                ->get()
                ->map(function ($customer) {
                    return [
                        'id' => $customer->id,
                        'identification' => $customer->identification,
                        'display_name' => $customer->display_name,
                        'typePerson' => $customer->typePerson
                    ];
                })
                ->toArray();
        } else {
            $this->customerSearchResults = [];
        }
    }

    /**
     * Seleccionar cliente encontrado
     */
    public function selectCustomer($customerId)
    {
        $customer = TatCustomer::find($customerId);

        if ($customer) {
            $this->selectedCustomer = [
                'id' => $customer->id,
                'identification' => $customer->identification,
                'display_name' => $customer->display_name,
                'typePerson' => $customer->typePerson
            ];

            $this->showClientSearch = false;
            $this->customerSearch = '';
            $this->customerSearchResults = [];

            session()->flash('success', 'Cliente seleccionado: ' . $customer->display_name);
        }
    }

    /**
     * Abrir modal para crear nuevo cliente
     */
    public function openCustomerModal()
    {
        $this->searchedIdentification = $this->customerSearch;
        $this->showCustomerModal = true;
    }

    /**
     * Cerrar modal de cliente
     */
    public function closeCustomerModal()
    {
        $this->showCustomerModal = false;
    }

    /**
     * Manejar cuando se crea un cliente nuevo (listener)
     */
    public function handleCustomerCreated($customerId)
    {
        $this->closeCustomerModal();
        $this->selectCustomer($customerId);
        session()->flash('success', 'Cliente creado y seleccionado correctamente.');
    }

    /**
     * Manejar cuando se cierra el modal de cliente (listener)
     */
    public function handleCustomerModalClosed()
    {
        $this->closeCustomerModal();
    }

    /**
     * Cargar datos restaurados desde un pago cancelado
     */
    protected function loadRestoredData()
    {
        if (session('quoter_restored')) {
            // Cargar carrito restaurado
            $restoredCart = session('quoter_cart', []);
            if (!empty($restoredCart)) {
                $this->cartItems = $restoredCart;
            }

            // Cargar cliente restaurado
            $restoredCustomer = session('quoter_customer');
            if ($restoredCustomer) {
                $this->selectedCustomer = $restoredCustomer;
            }

            // Limpiar las sesiones de restauración
            session()->forget(['quoter_restored', 'quoter_customer']);

            Log::info('Datos restaurados desde pago cancelado', [
                'cartItems' => count($this->cartItems),
                'customer' => $this->selectedCustomer ? $this->selectedCustomer['identification'] : null
            ]);
        }
    }

    public function render()
    {
        return view('livewire.TAT.quoter.quoter-view')
            ->layout('layouts.app');
    }
}