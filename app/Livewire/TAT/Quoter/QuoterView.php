<?php

namespace App\Livewire\TAT\Quoter;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\On;
use App\Models\TAT\Items\TatItems;
use App\Models\TAT\Quoter\Quote;
use App\Models\TAT\Quoter\QuoteItem;
use App\Models\TAT\Customer\Customer as TatCustomer;
use App\Models\TAT\Company\TatCompanyConfig;
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
    public $quantities = []; // Array asociativo para las cantidades por product_id
    public $total = 0;
    public $companyId;
    public $selectedIndex = -1;

    // Propiedades para configuración de empresa
    public $companyConfig = null;

    // Propiedades para cliente
    public $selectedCustomer = null;
    public $customerSearch = '';
    public $customerSearchResults = [];
    public $showClientSearch = false;
    public $showCustomerModal = false;
    public $searchedIdentification = '';
    public $editingCustomerId = null;
    public $showMobileSearch = false; // Controlar modo búsqueda en móviles
    public $modalClosing = false;

    // Propiedades para Producto Genérico
    public $showGenericProductModal = false;
    public $genericProductName = '';
    public $genericProductPrice = '';
    public $genericProductQuantity = 1;

    // Propiedades para manejo de venta
    public $currentQuoteId = null; // ID de la venta actual (para edición)
    public $editingQuoteId = null; // ID de la cotización que se está editando
    public $isEditing = false; // Flag para indicar si estamos en modo edición

    // Propiedades para paginación
    protected $paginationTheme = 'bootstrap';

    // Listeners para eventos
    protected $listeners = [
        'customer-created' => 'handleCustomerCreated',
        'customer-modal-closed' => 'handleCustomerModalClosed'
    ];

    public function mount($edit = null)
    {
        // Si no se pasa edit como parámetro, intentar obtenerlo de la query string
        if (!$edit) {
            $edit = request('edit');
        }

        // Verificar si es una nueva venta (limpiar carrito)
        $isNewSale = request('new') === 'true';
        if ($isNewSale) {
            Log::info('Nueva venta detectada - Limpiando carrito y sesión');
            $this->clearCartAndSession();
        }

        // Obtener company_id del usuario autenticado
        $user = Auth::user();
        $this->companyId = $this->getUserCompanyId($user);

        Log::info('Mount QuoterView', [
            'user_id' => $user->id,
            'user_profile_id' => $user->profile_id,
            'user_contact_id' => $user->contact_id,
            'companyId' => $this->companyId,
            'edit_parameter' => $edit,
            'request_edit' => request('edit'),
            'all_request' => request()->all()
        ]);

        if (!$this->companyId) {
            Log::error('No se pudo determinar company_id');
            session()->flash('error', 'No se pudo determinar la empresa del usuario.');
            return redirect()->route('tenant.dashboard');
        }

        // Cargar configuración de la empresa
        $this->loadCompanyConfig();

        // Si se está editando una venta existente, cargarla
        if ($edit) {
            Log::info('Iniciando carga de cotización existente', ['quote_id' => $edit]);
            $this->loadExistingQuote($edit);
        } else {
            Log::info('Iniciando nueva cotización');
            $this->loadCartFromSession();
            $this->loadRestoredData(); // Cargar datos restaurados del pago cancelado
            $this->calculateTotal();

            // Solo cargar cliente por defecto si no hay uno restaurado
            if (!$this->selectedCustomer) {
                $this->loadDefaultCustomer();
            }
        }

        Log::info('QuoterView mounted successfully', [
            'companyId' => $this->companyId,
            'cartItems' => count($this->cartItems),
            'total' => $this->total,
            'defaultCustomer' => $this->selectedCustomer ? $this->selectedCustomer['identification'] : null
        ]);

        // Verificar si hay caja abierta al cargar la vista
        if (!$this->checkActivePettyCash()) {
            $this->dispatch('swal:no-petty-cash');
        }
    }

    /**
     * Hook para detectar actualizaciones en quantities (wire:model)
     */
    public function updatedQuantities($value, $productId)
    {
        // Permitir que el usuario borre el input (valor vacío) sin forzar a 1 inmediatamente
        if ($value === '' || $value === null) {
            return;
        }

        $quantity = (int)$value;
        
        // Si el valor es 0 o negativo, ahí sí forzamos a 1 (pero solo si no está vacío, que ya validamos arriba)
        // Opcional: Podríamos permitir 0 si quisieran eliminar, pero la lógica actual es min 1
        $quantity = max(1, $quantity);

        // Buscar el producto en el carrito
        $itemIndex = collect($this->cartItems)->search(function ($item) use ($productId) {
            return $item['id'] == $productId;
        });

        if ($itemIndex === false) {
            return;
        }

        $item = $this->cartItems[$itemIndex];
        $stock = $item['stock'];

        // Validar Stock (solo si no se permite vender sin saldo)
        if (!$this->companyConfig->canSellWithoutStock() && $quantity > $stock) {
            $quantity = $stock;

            // Emitir alerta
            $this->dispatch('swal:warning', [
                'title' => 'Stock Insuficiente',
                'text' => "No puedes agregar más de {$stock} unidades (lo sentimos).",
            ]);
        }

        // Actualizar la cantidad en el array quantities y en cartItems
        $this->quantities[$productId] = $quantity;
        $this->cartItems[$itemIndex]['quantity'] = $quantity;

        // Recalcular subtotal
        $baseSubtotal = $quantity * $this->cartItems[$itemIndex]['price'];
        $taxAmount = $baseSubtotal * (($this->cartItems[$itemIndex]['tax_percentage'] ?? 0) / 100);
        $this->cartItems[$itemIndex]['subtotal'] = $baseSubtotal + $taxAmount;

        $this->calculateTotal();
        $this->saveCartToSession();

        // Log para debugging
        Log::info('Cantidad actualizada', [
            'product_id' => $productId,
            'product_name' => $this->cartItems[$itemIndex]['name'],
            'new_quantity' => $quantity,
            'cart_items_count' => count($this->cartItems)
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
     * Cargar configuración de la empresa
     */
    protected function loadCompanyConfig()
    {
        if (!$this->companyId) {
            return;
        }

        try {
            $this->companyConfig = TatCompanyConfig::getForCompany($this->companyId);

            Log::info('Configuración de empresa cargada', [
                'company_id' => $this->companyId,
                'vender_sin_saldo' => $this->companyConfig->vender_sin_saldo,
                'permitir_cambio_precio' => $this->companyConfig->permitir_cambio_precio
            ]);
        } catch (\Exception $e) {
            Log::error('Error cargando configuración de empresa', [
                'company_id' => $this->companyId,
                'error' => $e->getMessage()
            ]);

            // Crear configuración por defecto en caso de error
            $this->companyConfig = new TatCompanyConfig([
                'vender_sin_saldo' => false,
                'permitir_cambio_precio' => false
            ]);
        }
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

    public function openGenericProductModal()
    {
        $this->genericProductName = '';
        $this->genericProductPrice = '';
        $this->genericProductQuantity = 1;
        $this->showGenericProductModal = true;
    }

    public function addGenericProduct()
    {
        $this->validate([
            'genericProductName' => 'required|min:3',
            'genericProductPrice' => 'required|numeric|min:0',
            'genericProductQuantity' => 'required|integer|min:1',
        ], [
            'genericProductName.required' => 'La descripción es obligatoria',
            'genericProductPrice.required' => 'El precio es obligatorio',
            'genericProductPrice.numeric' => 'El precio debe ser un número',
            'genericProductQuantity.required' => 'La cantidad es obligatoria',
        ]);

        $price = floatval($this->genericProductPrice);
        $quantity = intval($this->genericProductQuantity);
        
        $newItem = [
            'id' => 'gen_' . time(),
            'name' => $this->genericProductName,
            'sku' => 'GENERICO',
            'price' => $price,
            'quantity' => $quantity,
            'subtotal' => $price * $quantity,
            'stock' => 99999, 
            'tax_name' => 'N/A',
            'tax_percentage' => 0,
            'img_path' => null,
            'initials' => 'GN',
            'avatar_color' => 'bg-gradient-to-br from-gray-500 to-slate-600'
        ];

        // Inicializar cantidad en el array separado
        $this->quantities[$newItem['id']] = $quantity;

        // Agregar al principio
        array_unshift($this->cartItems, $newItem);

        $this->calculateTotal();
        $this->saveCartToSession();
        $this->showGenericProductModal = false;

        $this->dispatch('swal:toast', [
            'type' => 'success',
            'message' => 'Producto genérico agregado',
            'mobile' => true
        ]);
    }

    /**
     * Actualizar resultados de búsqueda - OPTIMIZADO
     */
    public function updatedCurrentSearch()
    {
        $this->selectedIndex = -1;

        // Solo buscar si tiene mínimo 2 caracteres
        if (strlen($this->currentSearch) < 2) {
            $this->searchResults = [];
            return;
        }

        $searchTerm = trim($this->currentSearch);

        try {
            // Búsqueda FULLTEXT optimizada para MySQL
            $query = TatItems::query()
                ->select([
                    'id', 'name', 'sku', 'price', 'stock',
                    'taxId', 'categoryId', 'img_path'
                ])
                ->byCompany($this->companyId)
                ->active();

            // Si el término tiene más de 3 caracteres, usar FULLTEXT
            if (strlen($searchTerm) >= 3) {
                $query->whereRaw(
                    "MATCH(name, sku) AGAINST(? IN BOOLEAN MODE)",
                    ['+' . $searchTerm . '*']
                );
            } else {
                // Para términos cortos usar LIKE optimizado
                $query->where(function ($q) use ($searchTerm) {
                    $q->where('name', 'LIKE', $searchTerm . '%')
                      ->orWhere('sku', 'LIKE', $searchTerm . '%');
                });
            }

            // Ordenamiento optimizado: stock > 0 primero, luego por relevancia
            $results = $query
                ->orderByRaw('CASE WHEN stock > 0 THEN 0 ELSE 1 END')
                ->orderByRaw('CASE WHEN name LIKE ? THEN 0 ELSE 1 END', [$searchTerm . '%'])
                ->orderBy('stock', 'desc')
                ->limit(15) // Límite para performance
                ->get();

            // Mapear resultados con indicadores de stock e imágenes
            $this->searchResults = $results->map(function ($item) {
                $stockLevel = $this->getStockLevel($item->stock);

                return [
                    'id' => $item->id,
                    'name' => $item->display_name,
                    'sku' => $item->sku,
                    'price' => $item->price,
                    'stock' => $item->stock,
                    'stock_level' => $stockLevel,
                    'stock_color' => $this->getStockColor($stockLevel),
                    'tax_percentage' => $item->tax ? $item->tax->percentage : 0,
                    'img_path' => $item->img_path,
                    'initials' => $this->getProductInitials($item->name),
                    'avatar_color' => $this->getAvatarColorClass($item->name)
                ];
            })->toArray();

        } catch (\Exception $e) {
            Log::error('Error en búsqueda de productos', [
                'error' => $e->getMessage(),
                'search' => $searchTerm,
                'company_id' => $this->companyId
            ]);
            $this->searchResults = [];
        }
    }

    /**
     * Determinar nivel de stock
     */
    private function getStockLevel($stock)
    {
        if ($stock <= 0) return 'agotado';
        if ($stock <= 5) return 'bajo';
        return 'disponible';
    }

    /**
     * Obtener color según nivel de stock
     */
    private function getStockColor($level)
    {
        return match($level) {
            'disponible' => 'text-green-600 dark:text-green-400',
            'bajo' => 'text-yellow-600 dark:text-yellow-400',
            'agotado' => 'text-red-600 dark:text-red-400'
        };
    }

    /**
     * Obtener iniciales del nombre del producto
     */
    private function getProductInitials($name)
    {
        if (!$name) return '??';

        // Limpiar y dividir el nombre en palabras
        $words = explode(' ', trim($name));

        if (count($words) == 1) {
            // Si es una sola palabra, tomar las primeras 2 letras
            return strtoupper(substr($words[0], 0, 2));
        } else {
            // Si son múltiples palabras, tomar la primera letra de las primeras 2 palabras
            return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
        }
    }

    /**
     * Obtener color de avatar basado en el nombre del producto
     */
    private function getAvatarColorClass($name)
    {
        $colors = [
            'bg-gradient-to-br from-blue-500 to-indigo-600',
            'bg-gradient-to-br from-purple-500 to-pink-600',
            'bg-gradient-to-br from-green-500 to-teal-600',
            'bg-gradient-to-br from-yellow-500 to-orange-600',
            'bg-gradient-to-br from-red-500 to-pink-600',
            'bg-gradient-to-br from-indigo-500 to-purple-600',
            'bg-gradient-to-br from-teal-500 to-cyan-600',
            'bg-gradient-to-br from-orange-500 to-red-600',
        ];

        // Usar el primer caracter del nombre para determinar el color
        $index = ord(strtoupper($name[0])) % count($colors);
        return $colors[$index];
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
        $this->showMobileSearch = false; // También cerrar buscador móvil al limpiar
    }

    /**
     * Activar/Desactivar buscador móvil
     */
    public function toggleMobileSearch($value)
    {
        $this->showMobileSearch = $value;
        if ($value) {
            $this->dispatch('focus-fullscreen-input');
        }
    }

    /**
     * Búsqueda rápida - agregar primer resultado directamente
     */
    public function quickSearch($searchTerm)
    {
        if (strlen($searchTerm) < 2) return;

        try {
            $product = TatItems::query()
                ->select(['id', 'name', 'sku', 'stock'])
                ->byCompany($this->companyId)
                ->active()
                ->where('stock', '>', 0)
                ->where(function ($q) use ($searchTerm) {
                    $q->where('name', 'LIKE', $searchTerm . '%')
                      ->orWhere('sku', 'LIKE', $searchTerm . '%')
                      ->orWhere('name', 'LIKE', '%' . $searchTerm . '%');
                })
                ->orderByRaw('CASE WHEN name LIKE ? THEN 0 ELSE 1 END', [$searchTerm . '%'])
                ->first();

            if ($product) {
                $this->selectProduct($product->id);
            } else {
                session()->flash('warning', 'No se encontró ningún producto con ese término.');
            }
        } catch (\Exception $e) {
            Log::error('Error en quickSearch', ['error' => $e->getMessage(), 'term' => $searchTerm]);
        }
    }

    /**
     * Seleccionar producto desde la búsqueda
     */
    public function selectProduct($productId)
    {
        Log::info('INICIANDO selectProduct', [
            'product_id' => $productId,
            'cart_items_count' => count($this->cartItems),
            'current_search' => $this->currentSearch
        ]);

        $product = TatItems::find($productId);

        if (!$product) {
            Log::error('PRODUCTO NO ENCONTRADO', ['product_id' => $productId]);
            $this->dispatch('swal:toast', [
                'type' => 'error',
                'message' => 'Producto no encontrado.',
                'mobile' => true
            ]);
            return;
        }

        Log::info('PRODUCTO ENCONTRADO', [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_stock' => $product->stock,
            'company_config_exists' => $this->companyConfig ? 'YES' : 'NO'
        ]);

        // Verificar stock solo si no se permite vender sin saldo
        if ($this->companyConfig && !$this->companyConfig->canSellWithoutStock() && $product->stock <= 0) {
            Log::info('PRODUCTO SIN STOCK RECHAZADO', [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'stock' => $product->stock,
                'can_sell_without_stock' => $this->companyConfig->canSellWithoutStock()
            ]);

            $this->dispatch('swal:toast', [
                'type' => 'error',
                'message' => 'No se puede agregar un producto sin stock.',
                'mobile' => true
            ]);
            return;
        }

        $this->addToCart($productId);

        // Detectar si es móvil basado en el User-Agent
        $userAgent = request()->header('User-Agent') ?? '';
        $isMobile = preg_match('/Mobile|Android|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/', $userAgent);

        if (!$isMobile) {
            // En desktop: limpiar completamente la búsqueda
            $this->currentSearch = '';
            $this->searchResults = [];
            $this->additionalSuggestions = [];
            $this->selectedIndex = -1;
            $this->dispatch('product-selected');
        } else {
            // En móvil: Ahora también limpiamos para salir del modo búsqueda y volver al carrito
            $this->currentSearch = '';
            $this->searchResults = [];
            $this->additionalSuggestions = [];
            $this->selectedIndex = -1;
            $this->showMobileSearch = false; // OCULTAR MODO BÚSQUEDA MÓVIL
            
            // Emitir evento para cerrar pantalla completa en el frontend
            $this->dispatch('close-mobile-search');

            Log::info('MÓVIL DETECTADO - Cerrando búsqueda y volviendo al carrito');
        }
    }

    /**
     * Agregar producto al carrito
     */
    public function addToCart($productId)
    {
        Log::info('INICIANDO addToCart', [
            'product_id' => $productId,
            'cart_items_before' => count($this->cartItems)
        ]);

        $product = TatItems::with('tax')->find($productId);
        
        Log::info('Adding to cart', [
            'product_id' => $productId,
            'tax_id' => $product ? $product->taxId : null,
            'tax_relation' => $product ? $product->tax : null,
            'connection' => $product ? $product->getConnectionName() : 'unknown'
        ]);

        if (!$product) {
            $this->dispatch('swal:toast', [
                'type' => 'error',
                'message' => 'Producto no disponible.',
                'mobile' => true
            ]);
            return;
        }

        // Verificar stock solo si no se permite vender sin saldo
        if ($this->companyConfig && !$this->companyConfig->canSellWithoutStock() && $product->stock <= 0) {
            Log::info('PRODUCTO SIN STOCK EN ADDTOCART', [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'stock' => $product->stock
            ]);

            $this->dispatch('swal:toast', [
                'type' => 'error',
                'message' => 'Producto sin stock.',
                'mobile' => true
            ]);
            return;
        }

        $existingItemIndex = collect($this->cartItems)->search(function ($item) use ($productId) {
            return $item['id'] == $productId;
        });

        if ($existingItemIndex !== false) {
            // Si ya existe, obtener el item y removerlo de su posición actual
            $item = $this->cartItems[$existingItemIndex];
            unset($this->cartItems[$existingItemIndex]);
            $this->cartItems = array_values($this->cartItems); // Reindexar

            // Incrementar cantidad
            $oldQuantity = $item['quantity'];
            $newQuantity = $oldQuantity + 1;

            // Verificar stock solo si no se permite vender sin saldo
            if ($this->companyConfig && !$this->companyConfig->canSellWithoutStock() && $newQuantity > $product->stock) {
                $newQuantity = $product->stock;
                $this->dispatch('swal:warning', [
                    'title' => 'Stock Limitado',
                    'text' => "Solo hay {$product->stock} unidades disponibles. Cantidad ajustada automáticamente.",
                ]);
            }

            $item['quantity'] = $newQuantity;
            $baseSubtotal = $item['quantity'] * $item['price'];
            $taxAmount = $baseSubtotal * ($item['tax_percentage'] / 100);
            $item['subtotal'] = $baseSubtotal + $taxAmount;

            // Actualizar cantidad en el array separado
            $this->quantities[$productId] = $newQuantity;

            // Mover al principio (prepend)
            array_unshift($this->cartItems, $item);
            
            // Re-obtener el índice para mensajes posteriores
            $existingItemIndex = 0; 
        } else {
            // Agregar nuevo item
            $basePrice = $product->price;
            $taxPercentage = $product->tax ? $product->tax->percentage : 0;
            $taxAmount = $basePrice * ($taxPercentage / 100);
            $subtotalWithTax = $basePrice + $taxAmount;

            $newItem = [
                'id' => $product->id,
                'name' => $product->name,
                'sku' => $product->sku,
                'price' => $product->price,
                'quantity' => 1,
                'subtotal' => $subtotalWithTax,
                'stock' => $product->stock,
                'tax_name' => $product->tax ? $product->tax->name : 'N/A',
                'tax_percentage' => $taxPercentage,
                'img_path' => $product->img_path,
                'initials' => $this->getProductInitials($product->name),
                'avatar_color' => $this->getAvatarColorClass($product->name)
            ];

            // Inicializar cantidad en el array separado
            $this->quantities[$product->id] = 1;

            // Usar array_unshift para agregar al principio
            array_unshift($this->cartItems, $newItem);
        }

        $this->calculateTotal();
        $this->saveCartToSession();

        // Determinar si es actualización o nuevo producto para el mensaje
        $isUpdate = $existingItemIndex !== false;
        if ($isUpdate) {
            $finalQuantity = $this->cartItems[$existingItemIndex]['quantity'];
            $wasAdjusted = $finalQuantity != ($oldQuantity + 1);

            if ($wasAdjusted) {
                $message = "Cantidad ajustada por stock: {$oldQuantity} → {$finalQuantity} ({$product->name})";
            } else {
                $message = "Cantidad actualizada: {$oldQuantity} → {$finalQuantity} ({$product->name})";
            }
        } else {
            $message = "Producto agregado: {$product->name}";
        }

        $this->dispatch('swal:toast', [
            'type' => 'success',
            'message' => $message,
            'mobile' => true
        ]);
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
            $baseSubtotal = $quantity * $this->cartItems[$itemIndex]['price'];
            $taxAmount = $baseSubtotal * ($this->cartItems[$itemIndex]['tax_percentage'] / 100);
            $this->cartItems[$itemIndex]['subtotal'] = $baseSubtotal + $taxAmount;

            $this->calculateTotal();
            $this->saveCartToSession();
        }
    }

    /**
     * Actualizar precio de un producto en el carrito
     */
    public function updatePrice($productId, $newPrice)
    {
        // Verificar si se permite cambiar precios
        if (!$this->companyConfig->allowsPriceChange()) {
            session()->flash('error', 'No tiene permisos para modificar precios.');
            return;
        }

        $newPrice = max(0, (float)$newPrice);

        $itemIndex = collect($this->cartItems)->search(function ($item) use ($productId) {
            return $item['id'] == $productId;
        });

        if ($itemIndex !== false) {
            $this->cartItems[$itemIndex]['price'] = $newPrice;
            $baseSubtotal = $this->cartItems[$itemIndex]['quantity'] * $newPrice;
            $taxAmount = $baseSubtotal * ($this->cartItems[$itemIndex]['tax_percentage'] / 100);
            $this->cartItems[$itemIndex]['subtotal'] = $baseSubtotal + $taxAmount;

            $this->calculateTotal();
            $this->saveCartToSession();

            Log::info('Precio actualizado por usuario', [
                'product_id' => $productId,
                'new_price' => $newPrice,
                'user_id' => Auth::id(),
                'company_id' => $this->companyId
            ]);
        }
    }


    /**
     * Remover producto del carrito
     */
    public function removeFromCart($productId)
    {
        // Obtener el nombre del producto antes de eliminarlo
        $removedItem = collect($this->cartItems)->firstWhere('id', $productId);
        $productName = $removedItem ? $removedItem['name'] : 'Producto';

        $this->cartItems = collect($this->cartItems)
            ->reject(function ($item) use ($productId) {
                return $item['id'] == $productId;
            })->values()->toArray();

        // Limpiar cantidad del array separado
        unset($this->quantities[$productId]);

        $this->calculateTotal();
        $this->saveCartToSession();

        $this->dispatch('swal:toast', [
            'type' => 'success',
            'message' => $productName . ' removido del carrito',
            'mobile' => true
        ]);
    }

    /**
     * Limpiar todo el carrito
     */
    public function clearCart()
    {
        $itemCount = count($this->cartItems);
        $this->cartItems = [];
        $this->quantities = [];
        $this->total = 0;
        $this->saveCartToSession();

        $this->dispatch('swal:toast', [
            'type' => 'info',
            'message' => "Carrito limpiado ($itemCount productos eliminados)",
            'mobile' => true
        ]);
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

        // Inicializar quantities array
        $this->quantities = [];
        foreach ($this->cartItems as $item) {
            $this->quantities[$item['id']] = $item['quantity'];
        }
    }

    /**
     * Cargar una cotización existente para edición
     */
    protected function loadExistingQuote($quoteId)
    {
        Log::info('Ejecutando loadExistingQuote', [
            'quote_id' => $quoteId,
            'company_id' => $this->companyId
        ]);

        try {
            $quote = DB::table('tat_quotes')
                ->where('id', $quoteId)
                ->where('company_id', $this->companyId)
                ->first();

            Log::info('Resultado de búsqueda de cotización', [
                'quote_found' => $quote ? 'SI' : 'NO',
                'quote_data' => $quote
            ]);

            if (!$quote) {
                Log::error('Cotización no encontrada', ['quote_id' => $quoteId]);
                session()->flash('error', 'Cotización no encontrada.');
                return;
            }

            // Verificar que no esté pagada
            if ($quote->status === 'Pagado') {
                Log::error('Intento de editar cotización pagada', ['quote_id' => $quoteId]);
                session()->flash('error', 'No se puede editar una venta que ya está pagada.');
                return;
            }

            // Cargar cliente
            if ($quote->customerId) {
                $customer = DB::table('tat_customers')
                    ->where('id', $quote->customerId)
                    ->where(function($query) {
                        $query->where('company_id', $this->companyId)
                              ->orWhere('company_id', 0) // Para clientes generales
                              ->orWhereNull('company_id');
                    })
                    ->first();

                Log::info('Búsqueda de cliente', [
                    'customerId' => $quote->customerId,
                    'company_id' => $this->companyId,
                    'customer_found' => $customer ? 'SI' : 'NO',
                    'customer_data' => $customer
                ]);

                if ($customer) {
                    $this->selectedCustomer = [
                        'id' => $customer->id,
                        'identification' => $customer->identification,
                        'display_name' => $customer->businessName ?: ($customer->firstName . ' ' . $customer->lastName),
                        'full_name' => $customer->businessName ?: ($customer->firstName . ' ' . $customer->lastName),
                        'phone' => $customer->business_phone,
                        'email' => $customer->billingEmail,
                        'address' => $customer->address
                    ];
                }
            }

            // Cargar productos
            $quoteItems = DB::table('tat_detail_quotes as qi')
                ->join('tat_items as i', 'qi.itemId', '=', 'i.id')
                ->where('qi.quoteId', $quoteId)
                ->select([
                    'qi.*',
                    'i.id as product_id',
                    'i.name as product_name',
                    'i.sku',
                    'i.img_path',
                    'i.stock'
                ])
                ->get();

            $this->cartItems = [];
            $this->quantities = [];
            foreach ($quoteItems as $item) {
                // Generar avatar color e iniciales
                $avatarColor = $this->getAvatarColorClass($item->product_name);
                $initials = $this->getProductInitials($item->product_name);

                $this->cartItems[] = [
                    'id' => $item->product_id,
                    'name' => $item->product_name,
                    'sku' => $item->sku,
                    'price' => $item->price,
                    'quantity' => $item->quantity,
                    'subtotal' => $item->quantity * $item->price,
                    'stock' => $item->stock,
                    'img_path' => $item->img_path,
                    'avatar_color' => $avatarColor,
                    'initials' => $initials,
                    'tax_name' => '5%' // Valor por defecto temporal
                ];

                // Inicializar cantidad en el array separado
                $this->quantities[$item->product_id] = $item->quantity;
            }

            // Establecer modo edición y ID de la cotización
            $this->editingQuoteId = $quoteId;
            $this->isEditing = true;

            // Calcular total
            $this->calculateTotal();

            Log::info('Cotización cargada para edición', [
                'quote_id' => $quoteId,
                'customerId' => $quote->customerId,
                'items_count' => count($this->cartItems),
                'total' => $this->total,
                'editingQuoteId' => $this->editingQuoteId,
                'isEditing' => $this->isEditing
            ]);

        } catch (\Exception $e) {
            Log::error('Error cargando cotización para edición', [
                'quote_id' => $quoteId,
                'error' => $e->getMessage()
            ]);
            session()->flash('error', 'Error al cargar la cotización para edición.');
        }
    }

    /**
     * Método público para verificar caja desde el frontend
     */
    public function verifyPettyCash()
    {
        if (!$this->checkActivePettyCash()) {
            $this->dispatch('swal:no-petty-cash');
        }
    }

    /**
     * Verificar si hay una caja abierta para el usuario actual
     */
    protected function checkActivePettyCash()
    {
        try {
            // Verificar si hay una caja abierta (status = 1)
            $activePettyCash = DB::table('tat_petty_cash')
                ->join('tat_company_petty_cash', 'tat_petty_cash.id', '=', 'tat_company_petty_cash.petty_cash_id')
                ->where('tat_petty_cash.status', 1)
                ->where('tat_company_petty_cash.company_id', $this->companyId)
                ->select('tat_petty_cash.*')
                ->first();

            return $activePettyCash !== null;
        } catch (\Exception $e) {
            Log::error('Error verificando caja abierta', [
                'error' => $e->getMessage(),
                'company_id' => $this->companyId
            ]);
            return false;
        }
    }

    /**
     * Guardar cotización/venta (crear nueva o editar existente)
     */
    public function saveQuote()
    {
        if (empty($this->cartItems)) {
            session()->flash('error', 'No hay productos en el carrito para vender.');
            return;
        }

        // Validar cantidades y precios cero
        foreach ($this->cartItems as $item) {
            if ($item['quantity'] <= 0) {
                $this->dispatch('swal:warning', [
                    'title' => 'Cantidad Inválida',
                    'text' => "El producto {$item['name']} tiene cantidad 0 o inválida.",
                ]);
                return;
            }
            if ($item['price'] <= 0) {
                $this->dispatch('swal:warning', [
                    'title' => 'Precio Inválido',
                    'text' => "El producto {$item['name']} tiene precio 0 o inválido.",
                ]);
                return;
            }
        }

        if (!$this->selectedCustomer) {
            session()->flash('error', 'Debe seleccionar un cliente para realizar la venta.');
            return;
        }

        // Validar que haya una caja abierta
        if (!$this->checkActivePettyCash()) {
            $this->dispatch('swal:no-petty-cash');
            return;
        }

        // Validar stock antes de proceder (solo si NO está permitida la venta sin saldo)
        $allowNoStock = $this->companyConfig->vender_sin_saldo ?? false;
        $stockErrors = [];

        if (!$allowNoStock) {
            foreach ($this->cartItems as $item) {
                // Saltar validación de stock para productos genéricos
                if (str_starts_with($item['id'], 'gen_')) {
                    continue;
                }

                $product = TatItems::find($item['id']);
                if (!$product) {
                    $stockErrors[] = "Producto {$item['name']} no encontrado";
                    continue;
                }

                if ($product->stock < $item['quantity']) {
                    $stockErrors[] = "Stock insuficiente para {$item['name']} (Disponible: {$product->stock}, Requerido: {$item['quantity']})";
                }
            }
        }

        if (!empty($stockErrors)) {
            session()->flash('error', 'No se puede completar la venta: ' . implode(', ', $stockErrors));
            return;
        }

        try {
            DB::beginTransaction();

            $customerInfo = $this->selectedCustomer['display_name'] . ' (' . $this->selectedCustomer['identification'] . ')';
            $observations = 'Venta registrada para: ' . $customerInfo . ' - Total productos: ' . count($this->cartItems);

            // Verificar si estamos editando una venta existente
            Log::info('Verificando modo de edición en saveQuote', [
                'currentQuoteId' => $this->currentQuoteId,
                'editingQuoteId' => $this->editingQuoteId,
                'isEditing' => $this->isEditing
            ]);

            if ($this->currentQuoteId || $this->editingQuoteId) {
                // EDITAR VENTA EXISTENTE
                Log::info('Modo edición detectado en saveQuote');
                $quoteId = $this->currentQuoteId ?: $this->editingQuoteId;
                $quote = Quote::find($quoteId);

                if (!$quote) {
                    throw new \Exception('La venta a editar no fue encontrada.');
                }

                // Verificar que la venta no haya sido pagada
                if ($quote->status === 'Pagado') {
                    throw new \Exception('No se puede editar una venta que ya fue pagada.');
                }

                // Restaurar stock de los items anteriores (incluyendo soft deleted)
                $oldQuoteItems = QuoteItem::withTrashed()->where('quoteId', $quote->id)->get();

                Log::info('Restaurando stock de items anteriores', [
                    'quote_id' => $quote->id,
                    'items_count' => $oldQuoteItems->count(),
                    'items' => $oldQuoteItems->pluck('id', 'itemId')->toArray()
                ]);

                foreach ($oldQuoteItems as $oldItem) {
                    $product = TatItems::find($oldItem->itemId);
                    if ($product) {
                        $product->increment('stock', $oldItem->quantity);
                        Log::info('Stock restaurado', [
                            'product_id' => $product->id,
                            'quantity_restored' => $oldItem->quantity,
                            'new_stock' => $product->fresh()->stock
                        ]);
                    }
                }

                // Eliminar items antiguos FÍSICAMENTE (incluyendo soft deleted)
                $deletedCount = QuoteItem::withTrashed()->where('quoteId', $quote->id)->forceDelete();

                Log::info('Items antiguos eliminados', [
                    'quote_id' => $quote->id,
                    'deleted_count' => $deletedCount
                ]);

                // Actualizar la venta
                $quote->update([
                    'customerId' => $this->selectedCustomer['id'],
                    'observations' => $observations . ' - EDITADA',
                ]);

                $actionType = 'editada';

            } else {
                // CREAR NUEVA VENTA
                Log::info('Modo nueva venta detectado en saveQuote');
                $lastQuote = Quote::byCompany($this->companyId)->orderBy('consecutive', 'desc')->first();
                $consecutive = $lastQuote ? $lastQuote->consecutive + 1 : 1;

                $quote = Quote::create([
                    'company_id' => $this->companyId,
                    'consecutive' => $consecutive,
                    'status' => 'Registrado',
                    'customerId' => $this->selectedCustomer['id'],
                    'userId' => Auth::id(),
                    'observations' => $observations,
                ]);

                $actionType = 'registrada';
            }

            // Agregar/actualizar items de la venta
            Log::info('Agregando nuevos items a la venta', [
                'quote_id' => $quote->id,
                'new_items_count' => count($this->cartItems),
                'action_type' => $actionType ?? 'desconocido'
            ]);

            // Obtener el ID del producto genérico oficial para esta empresa
            $genericProduct = DB::table('tat_items')
                ->where('company_id', $this->companyId)
                ->where('sku', 'GENERICO')
                ->first();

            $genericId = $genericProduct ? $genericProduct->id : 0;

            foreach ($this->cartItems as $item) {
                $isGeneric = str_starts_with($item['id'], 'gen_');

                $newQuoteItem = QuoteItem::create([
                    'quoteId' => $quote->id,
                    'itemId' => $isGeneric ? $genericId : $item['id'],
                    'quantity' => $item['quantity'],
                    'tax_percentage' => $item['tax_percentage'] ?? 0,
                    'price' => $item['price'],
                    'descripcion' => $item['name'],
                ]);

                Log::info('Item agregado a la venta', [
                    'quote_item_id' => $newQuoteItem->id,
                    'quote_id' => $quote->id,
                    'product_id' => $item['id'],
                    'real_item_id' => $isGeneric ? $genericId : $item['id'],
                    'quantity' => $item['quantity'],
                    'is_generic' => $isGeneric
                ]);


                // Reducir stock del inventario (solo para productos reales)
                if (!$isGeneric) {
                    $product = TatItems::find($item['id']);
                    if ($product) {
                        $oldStock = $product->stock;
                        $product->decrement('stock', $item['quantity']);
                        Log::info('Stock reducido en venta', [
                            'product_id' => $product->id,
                            'old_stock' => $oldStock,
                            'quantity_sold' => $item['quantity'],
                            'new_stock' => $product->fresh()->stock
                        ]);
                    }
                }
            }

            DB::commit();

            // Limpiar estado después de guardar
            $this->clearCart();
            $this->currentQuoteId = null; // Reset para próxima venta
            $this->loadDefaultCustomer();

            // Log de la acción
            Log::info("Venta {$actionType}", [
                'venta_id' => $quote->id,
                'consecutive' => $quote->consecutive ?? 'N/A',
                'cliente' => $customerInfo,
                'total_productos' => count($this->cartItems),
                'total_venta' => $this->total,
                'company_id' => $this->companyId,
                'user_id' => Auth::id(),
                'action' => $actionType
            ]);

            // Redirigir a la vista de pagos
            return redirect()->route('tenant.payment.quote', ['quoteId' => $quote->id]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al procesar venta', [
                'error' => $e->getMessage(),
                'company_id' => $this->companyId,
                'user_id' => Auth::id(),
                'cliente' => $this->selectedCustomer['display_name'] ?? 'N/A',
                'currentQuoteId' => $this->currentQuoteId
            ]);
            session()->flash('error', 'Error al procesar la venta: ' . $e->getMessage());
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

            // Si no hay resultados y la búsqueda tiene al menos 2 caracteres, abrir automáticamente el modal
            // PERO solo si no está en proceso de cierre
            Log::info('🔍 Evaluando apertura automática del modal', [
                'customerSearch' => $this->customerSearch,
                'search_length' => strlen($this->customerSearch),
                'results_count' => count($this->customerSearchResults),
                'modalClosing' => $this->modalClosing,
                'selectedCustomer_exists' => $this->selectedCustomer ? 'YES' : 'NO',
                'showCustomerModal_current' => $this->showCustomerModal
            ]);

            if (empty($this->customerSearchResults) && strlen($this->customerSearch) >= 2 && !$this->modalClosing) {
                $this->searchedIdentification = $this->customerSearch;
                $this->showCustomerModal = true;

                Log::info('Modal activado automáticamente', [
                    'customerSearch' => $this->customerSearch,
                    'searchedIdentification' => $this->searchedIdentification,
                    'showCustomerModal' => $this->showCustomerModal,
                    'resultados_count' => count($this->customerSearchResults),
                    'search_length' => strlen($this->customerSearch)
                ]);

                // Enviar evento para mostrar en el frontend
                $this->dispatch('swal:toast', [
                    'type' => 'info',
                    'message' => 'Abriendo formulario de nuevo cliente para: ' . $this->customerSearch
                ]);

                // Forzar re-renderizado
                $this->dispatch('$refresh');
            }
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
        $this->editingCustomerId = null; // Modo crear
        $this->showCustomerModal = true;

        Log::info('openCustomerModal llamado', [
            'showCustomerModal' => $this->showCustomerModal,
            'searchedIdentification' => $this->searchedIdentification
        ]);
    }

    /**
     * Abrir modal para editar cliente existente
     */
    public function editCustomer()
    {
        if (!$this->selectedCustomer) {
            $this->dispatch('swal:toast', [
                'type' => 'error',
                'message' => 'No hay cliente seleccionado para editar'
            ]);
            return;
        }

        // Verificar si es el cliente genérico (222222222)
        if ($this->selectedCustomer['identification'] === '222222222') {
            $this->dispatch('swal:toast', [
                'type' => 'info',
                'message' => 'Este es un cliente genérico que no puede ser editado'
            ]);
            return;
        }

        $this->editingCustomerId = $this->selectedCustomer['id'];
        $this->searchedIdentification = $this->selectedCustomer['identification'];
        $this->showCustomerModal = true;

        Log::info('editCustomer llamado', [
            'editingCustomerId' => $this->editingCustomerId,
            'showCustomerModal' => $this->showCustomerModal
        ]);
    }

    /**
     * Cerrar modal de cliente
     */
    public function closeCustomerModal()
    {
        $this->modalClosing = true;
        $this->showCustomerModal = false;
        $this->editingCustomerId = null;
        $this->searchedIdentification = '';
        $this->customerSearch = ''; // Limpiar búsqueda para evitar reactivación
        $this->customerSearchResults = []; // Limpiar resultados
        $this->showClientSearch = false; // Salir del modo de búsqueda de cliente

        Log::info('Modal de cliente cerrado', [
            'showCustomerModal' => $this->showCustomerModal,
            'editingCustomerId' => $this->editingCustomerId,
            'showClientSearch' => $this->showClientSearch,
            'modalClosing' => $this->modalClosing
        ]);

        // Reset la flag después de un momento para permitir reapertura
        $this->modalClosing = false;

        // Forzar actualización del componente
        $this->dispatch('$refresh');
    }

    /**
     * Manejar cuando se crea/actualiza un cliente (listener)
     */
    public function handleCustomerCreated($customerId)
    {
        // Determinar el mensaje antes de limpiar las variables
        $wasEditing = !empty($this->editingCustomerId);
        $message = $wasEditing ? 'Cliente actualizado correctamente.' : 'Cliente creado y seleccionado correctamente.';

        // Cerrar modal y limpiar variables
        $this->closeCustomerModal();
        $this->selectCustomer($customerId);
        $this->showClientSearch = false; // Salir del modo búsqueda
        $this->customerSearch = ''; // Limpiar búsqueda
        $this->customerSearchResults = []; // Limpiar resultados

        $this->dispatch('swal:toast', [
            'type' => 'success',
            'message' => $message
        ]);

        // Asegurar que la búsqueda se desactive
        $this->showClientSearch = false;

        Log::info('Cliente procesado correctamente', [
            'customerId' => $customerId,
            'wasEditing' => $wasEditing,
            'showCustomerModal' => $this->showCustomerModal
        ]);
    }

    /**
     * Manejar cuando se cierra el modal de cliente (listener)
     */
    public function handleCustomerModalClosed()
    {
        Log::info('handleCustomerModalClosed ejecutado');
        $this->closeCustomerModal();

        Log::info('Modal cerrado por listener', [
            'showCustomerModal' => $this->showCustomerModal,
            'showClientSearch' => $this->showClientSearch
        ]);
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

                // Inicializar quantities array
                $this->quantities = [];
                foreach ($this->cartItems as $item) {
                    $this->quantities[$item['id']] = $item['quantity'];
                }
            }

            // Cargar cliente restaurado
            $restoredCustomer = session('quoter_customer');
            if ($restoredCustomer) {
                $this->selectedCustomer = $restoredCustomer;
            }

            // Cargar ID de la venta actual para edición
            $restoredQuoteId = session('quoter_quote_id');
            if ($restoredQuoteId) {
                $this->currentQuoteId = $restoredQuoteId;
            }

            // Limpiar las sesiones de restauración
            session()->forget(['quoter_restored', 'quoter_customer', 'quoter_quote_id']);

            Log::info('Datos restaurados desde pago cancelado', [
                'cartItems' => count($this->cartItems),
                'customer' => $this->selectedCustomer ? $this->selectedCustomer['identification'] : null,
                'quoteId' => $this->currentQuoteId
            ]);
        }
    }

    /**
     * Obtener configuración de empresa como propiedad computed
     */
    public function getCompanyConfigProperty()
    {
        return $this->companyConfig;
    }

    /**
     * Obtener texto del botón según el contexto
     */
    public function getSaveButtonTextProperty()
    {
        Log::info('getSaveButtonTextProperty llamado', [
            'currentQuoteId' => $this->currentQuoteId,
            'editingQuoteId' => $this->editingQuoteId,
            'isEditing' => $this->isEditing
        ]);

        if ($this->currentQuoteId || $this->editingQuoteId) {
            return 'Actualizar Venta';
        }
        return 'Pagar Venta';
    }

    /**
     * Obtener texto para el loading del botón
     */
    public function getSaveButtonLoadingTextProperty()
    {
        if ($this->currentQuoteId || $this->editingQuoteId) {
            return 'Actualizando...';
        }
        return 'Guardando...';
    }

    /**
     * Limpiar carrito y datos de sesión para nueva venta
     */
    private function clearCartAndSession()
    {
        // Limpiar carrito
        $this->cartItems = [];
        $this->quantities = [];
        $this->total = 0;

        // Limpiar cliente seleccionado
        $this->selectedCustomer = null;
        $this->customerSearch = '';
        $this->customerSearchResults = [];
        $this->showClientSearch = false;

        // Limpiar datos de edición
        $this->currentQuoteId = null;
        $this->editingQuoteId = null;
        $this->isEditing = false;

        // Limpiar búsqueda de productos
        $this->currentSearch = '';
        $this->searchResults = [];
        $this->additionalSuggestions = [];
        $this->selectedIndex = -1;

        // Limpiar sesiones relacionadas
        session()->forget([
            'quoter_cart',
            'quoter_customer',
            'quoter_restored',
            'quoter_total',
            'payment_customer_data'
        ]);

        Log::info('Carrito y sesión limpiados para nueva venta');
    }

    public function render()
    {
        return view('livewire.TAT.quoter.quoter-view', [
            'companyConfig' => $this->companyConfig
        ])->layout('layouts.app');
    }
}