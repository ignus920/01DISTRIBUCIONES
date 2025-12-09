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

    // Propiedades para paginación
    protected $paginationTheme = 'bootstrap';

    // Listeners para eventos
    protected $listeners = [
        'customer-created' => 'handleCustomerCreated'
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

        if (strlen($this->currentSearch) >= 2) {
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

            $results = $query->take(20)->get();

            Log::info('Resultados encontrados', [
                'total_results' => $results->count(),
                'results' => $results->take(3)->pluck('name')->toArray()
            ]);

            // Primeros 3 resultados en dropdown principal
            $this->searchResults = $results->take(3)->toArray();

            // Resultados adicionales como sugerencias
            $this->additionalSuggestions = $results->skip(3)->take(4)->toArray();
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
        $product = TatItems::find($productId);

        if (!$product || !$product->hasStock()) {
            session()->flash('error', 'Producto no disponible o sin stock.');
            return;
        }

        $existingItemIndex = collect($this->cartItems)->search(function ($item) use ($productId) {
            return $item['id'] == $productId;
        });

        if ($existingItemIndex !== false) {
            // Si ya existe, incrementar cantidad
            $this->cartItems[$existingItemIndex]['quantity']++;
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
                'stock' => $product->stock
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
            if ($quantity == 0) {
                // Remover item si cantidad es 0
                unset($this->cartItems[$itemIndex]);
                $this->cartItems = array_values($this->cartItems);
            } else {
                // Verificar stock disponible
                if ($quantity > $this->cartItems[$itemIndex]['stock']) {
                    session()->flash('error', 'Cantidad excede el stock disponible.');
                    return;
                }

                $this->cartItems[$itemIndex]['quantity'] = $quantity;
                $this->cartItems[$itemIndex]['subtotal'] =
                    $quantity * $this->cartItems[$itemIndex]['price'];
            }

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
     * Guardar cotización
     */
    public function saveQuote()
    {
        if (empty($this->cartItems)) {
            session()->flash('error', 'No hay productos en el carrito para cotizar.');
            return;
        }

        try {
            DB::beginTransaction();

            // Generar consecutivo para la cotización
            $lastQuote = Quote::byCompany($this->companyId)->orderBy('consecutive', 'desc')->first();
            $consecutive = $lastQuote ? $lastQuote->consecutive + 1 : 1;

            // Crear la cotización con la estructura real de la BD
            $quote = Quote::create([
                'company_id' => $this->companyId,
                'consecutive' => $consecutive,
                'status' => 'Registrado',
                'customerId' => 1, // Por ahora consumidor final, después se puede implementar clientes
                'userId' => Auth::id(),
                'observations' => 'Cotización generada desde el sistema',
            ]);

            // Agregar items a la cotización con la estructura real de la BD
            foreach ($this->cartItems as $item) {
                QuoteItem::create([
                    'quoteId' => $quote->id,
                    'itemId' => $item['id'],
                    'quantity' => $item['quantity'],
                    'tax_percentage' => 19, // IVA del 19%
                    'price' => $item['price'],
                    'descripcion' => $item['name'],
                ]);
            }

            DB::commit();

            $this->clearCart();
            session()->flash('success', "Cotización #{$consecutive} guardada exitosamente.");

        } catch (\Exception $e) {
            DB::rollBack();
            session()->flash('error', 'Error al guardar la cotización: ' . $e->getMessage());
        }
    }

    /**
     * Generar factura (placeholder)
     */
    public function generateInvoice()
    {
        if (empty($this->cartItems)) {
            session()->flash('error', 'No hay productos en el carrito para facturar.');
            return;
        }

        // Aquí se implementaría la lógica de facturación
        session()->flash('info', 'Función de facturación en desarrollo.');
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
        if (strlen($this->customerSearch) >= 2) {
            $this->customerSearchResults = TatCustomer::where('company_id', $this->companyId)
                ->where(function ($query) {
                    $query->where('identification', 'like', '%' . $this->customerSearch . '%')
                          ->orWhere('businessName', 'like', '%' . $this->customerSearch . '%')
                          ->orWhere('firstName', 'like', '%' . $this->customerSearch . '%')
                          ->orWhere('lastName', 'like', '%' . $this->customerSearch . '%');
                })
                ->take(5)
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

    public function render()
    {
        return view('livewire.TAT.quoter.quoter-view');
    }
}