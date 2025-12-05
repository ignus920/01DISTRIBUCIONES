<?php

namespace App\Livewire\TAT\Quoter;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\TAT\Items\TatItems;
use App\Models\TAT\Quoter\Quote;
use App\Models\TAT\Quoter\QuoteItem;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class QuoterView extends Component
{
    use WithPagination;

    // Propiedades públicas
    public $currentSearch = '';
    public $searchResults = [];
    public $additionalSuggestions = [];
    public $cartItems = [];
    public $total = 0;
    public $clientNumber = '22222222222';
    public $companyId;

    // Propiedades para paginación
    protected $paginationTheme = 'bootstrap';

    public function mount()
    {
        // Obtener company_id del usuario autenticado
        $user = Auth::user();
        $this->companyId = $this->getUserCompanyId($user);

        if (!$this->companyId) {
            session()->flash('error', 'No se pudo determinar la empresa del usuario.');
            return redirect()->route('tenant.dashboard');
        }

        $this->loadCartFromSession();
        $this->calculateTotal();
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
        if (strlen($this->currentSearch) >= 2) {
            $results = TatItems::query()
                ->byCompany($this->companyId)
                ->active()
                ->where('stock', '>', 0)
                ->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->currentSearch . '%')
                      ->orWhere('sku', 'like', '%' . $this->currentSearch . '%');
                })
                ->take(20)
                ->get();

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
     * Seleccionar producto desde la búsqueda
     */
    public function selectProduct($productId)
    {
        $this->addToCart($productId);

        // Limpiar la búsqueda para permitir nueva búsqueda
        $this->currentSearch = '';
        $this->searchResults = [];
        $this->additionalSuggestions = [];

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


    public function render()
    {
        return view('livewire.TAT.quoter.quoter-view');
    }
}