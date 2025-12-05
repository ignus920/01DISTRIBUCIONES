# DOCUMENTACIÓN COMPLETA - PARTE 4 FINAL
## COMPONENTES LIVEWIRE, BASE DE DATOS Y CRONOGRAMA

---

## 9. COMPONENTES LIVEWIRE DETALLADOS

### 9.1 Componente: POSScreen (Pantalla Principal POS)

**Archivo:** `app/Modules/POS/Livewire/POSScreen.php`

```php
<?php

namespace App\Modules\POS\Livewire;

use Livewire\Component;
use App\Modules\Inventory\Models\Product;
use App\Modules\POS\Services\SaleService;

class POSScreen extends Component
{
    // Propiedades públicas
    public $search = '';
    public $selectedCategory = null;
    public $cart = [];
    public $customer = null;
    public $paymentMethods = [];
    public $notes = '';

    // Totales calculados
    public $subtotal = 0;
    public $tax = 0;
    public $discount = 0;
    public $total = 0;

    // UI State
    public $showPaymentModal = false;
    public $showCustomerModal = false;

    protected $listeners = [
        'product-selected' => 'addToCart',
        'customer-selected' => 'setCustomer',
        'payment-completed' => 'completeSale'
    ];

    public function mount()
    {
        $this->resetCart();
        $this->loadPaymentMethods();
    }

    /**
     * Agregar producto al carrito.
     */
    public function addToCart($productId, $quantity = 1)
    {
        $product = Product::with(['category', 'tax'])->find($productId);

        if (!$product) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Producto no encontrado'
            ]);
            return;
        }

        // Verificar stock
        if ($product->stock < $quantity) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => "Stock insuficiente. Disponible: {$product->stock}"
            ]);
            return;
        }

        $cartKey = "product_{$productId}";

        if (isset($this->cart[$cartKey])) {
            // Ya existe en el carrito, incrementar cantidad
            $this->cart[$cartKey]['quantity'] += $quantity;
        } else {
            // Agregar nuevo al carrito
            $this->cart[$cartKey] = [
                'product' => $product->toArray(),
                'quantity' => $quantity,
                'price' => $product->price,
                'discount_percent' => 0,
                'discount_amount' => 0,
                'tax_percent' => $product->tax_percent ?? config('pos.default_tax', 0),
                'tax_amount' => 0,
                'subtotal' => 0,
                'total' => 0,
            ];
        }

        $this->calculateItem($cartKey);
        $this->calculateTotals();

        // Notificar éxito
        $this->dispatch('notify', [
            'type' => 'success',
            'message' => "{$product->name} agregado al carrito"
        ]);
    }

    /**
     * Actualizar cantidad de un item.
     */
    public function updateQuantity($cartKey, $quantity)
    {
        if ($quantity <= 0) {
            $this->removeFromCart($cartKey);
            return;
        }

        // Verificar stock disponible
        $product = $this->cart[$cartKey]['product'];
        if ($quantity > $product['stock']) {
            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => "Stock máximo disponible: {$product['stock']}"
            ]);
            $quantity = $product['stock'];
        }

        $this->cart[$cartKey]['quantity'] = $quantity;
        $this->calculateItem($cartKey);
        $this->calculateTotals();
    }

    /**
     * Aplicar descuento a un item.
     */
    public function applyDiscount($cartKey, $discountPercent)
    {
        // Verificar permiso de descuento
        $maxDiscount = auth()->user()->max_discount_percent ?? 0;

        if ($discountPercent > $maxDiscount) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => "Descuento máximo permitido: {$maxDiscount}%"
            ]);
            return;
        }

        $this->cart[$cartKey]['discount_percent'] = $discountPercent;
        $this->calculateItem($cartKey);
        $this->calculateTotals();
    }

    /**
     * Remover item del carrito.
     */
    public function removeFromCart($cartKey)
    {
        unset($this->cart[$cartKey]);
        $this->calculateTotals();
    }

    /**
     * Calcular totales de un item.
     */
    protected function calculateItem($cartKey)
    {
        $item = &$this->cart[$cartKey];

        $itemSubtotal = $item['quantity'] * $item['price'];
        $discountAmount = $itemSubtotal * ($item['discount_percent'] / 100);
        $subtotalAfterDiscount = $itemSubtotal - $discountAmount;
        $taxAmount = $subtotalAfterDiscount * ($item['tax_percent'] / 100);
        $itemTotal = $subtotalAfterDiscount + $taxAmount;

        $item['subtotal'] = $itemSubtotal;
        $item['discount_amount'] = $discountAmount;
        $item['tax_amount'] = $taxAmount;
        $item['total'] = $itemTotal;
    }

    /**
     * Calcular totales generales.
     */
    protected function calculateTotals()
    {
        $this->subtotal = collect($this->cart)->sum('subtotal');
        $this->discount = collect($this->cart)->sum('discount_amount');
        $this->tax = collect($this->cart)->sum('tax_amount');
        $this->total = collect($this->cart)->sum('total');
    }

    /**
     * Establecer cliente.
     */
    public function setCustomer($customerId)
    {
        $this->customer = \App\Modules\CRM\Models\Customer::find($customerId);
        $this->showCustomerModal = false;
    }

    /**
     * Abrir modal de pago.
     */
    public function openPaymentModal()
    {
        if (empty($this->cart)) {
            $this->dispatch('notify', [
                'type' => 'warning',
                'message' => 'El carrito está vacío'
            ]);
            return;
        }

        $this->showPaymentModal = true;
        $this->dispatch('payment-modal-opened', total: $this->total);
    }

    /**
     * Completar venta.
     */
    public function completeSale($paymentData)
    {
        try {
            DB::beginTransaction();

            $saleData = [
                'customer_id' => $this->customer?->id,
                'items' => $this->cart,
                'subtotal' => $this->subtotal,
                'discount' => $this->discount,
                'tax' => $this->tax,
                'total' => $this->total,
                'payment_methods' => $paymentData['methods'],
                'notes' => $this->notes,
            ];

            $sale = app(SaleService::class)->create($saleData);

            DB::commit();

            // Limpiar carrito
            $this->resetCart();
            $this->showPaymentModal = false;

            // Notificar éxito
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => '¡Venta completada exitosamente!'
            ]);

            // Preguntar si desea imprimir
            $this->dispatch('sale-completed', [
                'saleId' => $sale->id,
                'showPrintDialog' => true
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Error completing sale: ' . $e->getMessage());

            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Error al completar la venta: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Resetear carrito.
     */
    public function resetCart()
    {
        $this->cart = [];
        $this->customer = null;
        $this->notes = '';
        $this->calculateTotals();
    }

    /**
     * Cargar métodos de pago disponibles.
     */
    protected function loadPaymentMethods()
    {
        $this->paymentMethods = \App\Models\PaymentMethod::active()
            ->orderBy('order')
            ->get()
            ->toArray();
    }

    /**
     * Renderizar componente.
     */
    public function render()
    {
        $products = Product::query()
            ->when($this->search, fn($q) => $q->where('name', 'like', "%{$this->search}%")
                                              ->orWhere('code', 'like', "%{$this->search}%"))
            ->when($this->selectedCategory, fn($q) => $q->where('category_id', $this->selectedCategory))
            ->where('active', true)
            ->where('stock', '>', 0)
            ->with('category')
            ->take(20)
            ->get();

        $categories = \App\Modules\Inventory\Models\Category::active()
            ->withCount('products')
            ->get();

        return view('modules.pos.livewire.pos-screen', [
            'products' => $products,
            'categories' => $categories,
        ]);
    }
}
```

### 9.2 Componente: MultiPaymentForm

**Archivo:** `app/Modules/POS/Livewire/MultiPaymentForm.php`

```php
<?php

namespace App\Modules\POS\Livewire;

use Livewire\Component;

class MultiPaymentForm extends Component
{
    public $totalToPay = 0;
    public $payments = [];
    public $balance = 0;
    public $change = 0;

    public $availableMethods = [];

    protected $listeners = ['payment-modal-opened' => 'initialize'];

    public function initialize($total)
    {
        $this->totalToPay = $total;
        $this->balance = $total;
        $this->payments = [];
        $this->change = 0;

        $this->loadAvailableMethods();
    }

    protected function loadAvailableMethods()
    {
        $this->availableMethods = \App\Models\PaymentMethod::active()
            ->orderBy('order')
            ->get();
    }

    /**
     * Agregar forma de pago.
     */
    public function addPayment($methodId)
    {
        $method = $this->availableMethods->firstWhere('id', $methodId);

        if (!$method) {
            return;
        }

        $this->payments[] = [
            'id' => uniqid(),
            'method_id' => $methodId,
            'method_name' => $method->name,
            'amount' => 0,
            'reference' => '',
            'requires_reference' => $method->requires_reference,
        ];
    }

    /**
     * Actualizar monto de un pago.
     */
    public function updatePaymentAmount($paymentId, $amount)
    {
        $index = collect($this->payments)->search(fn($p) => $p['id'] === $paymentId);

        if ($index !== false) {
            $this->payments[$index]['amount'] = (float) $amount;
            $this->calculateBalance();
        }
    }

    /**
     * Actualizar referencia de un pago.
     */
    public function updatePaymentReference($paymentId, $reference)
    {
        $index = collect($this->payments)->search(fn($p) => $p['id'] === $paymentId);

        if ($index !== false) {
            $this->payments[$index]['reference'] = $reference;
        }
    }

    /**
     * Remover pago.
     */
    public function removePayment($paymentId)
    {
        $this->payments = collect($this->payments)
            ->reject(fn($p) => $p['id'] === $paymentId)
            ->values()
            ->toArray();

        $this->calculateBalance();
    }

    /**
     * Calcular balance y cambio.
     */
    protected function calculateBalance()
    {
        $totalPaid = collect($this->payments)->sum('amount');

        $this->balance = $this->totalToPay - $totalPaid;
        $this->change = $this->balance < 0 ? abs($this->balance) : 0;
    }

    /**
     * Completar pago.
     */
    public function completePayment()
    {
        // Validar que el monto sea suficiente
        if ($this->balance > 0) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'El monto pagado es insuficiente'
            ]);
            return;
        }

        // Validar referencias requeridas
        foreach ($this->payments as $payment) {
            if ($payment['requires_reference'] && empty($payment['reference'])) {
                $this->dispatch('notify', [
                    'type' => 'error',
                    'message' => "Falta referencia para {$payment['method_name']}"
                ]);
                return;
            }
        }

        // Emitir evento de pago completado
        $this->dispatch('payment-completed', [
            'methods' => $this->payments,
            'change' => $this->change
        ]);
    }

    /**
     * Pago rápido con efectivo.
     */
    public function quickCashPayment()
    {
        $cashMethod = $this->availableMethods->firstWhere('slug', 'cash');

        if (!$cashMethod) {
            return;
        }

        $this->payments = [[
            'id' => uniqid(),
            'method_id' => $cashMethod->id,
            'method_name' => 'Efectivo',
            'amount' => $this->totalToPay,
            'reference' => '',
            'requires_reference' => false,
        ]];

        $this->calculateBalance();
        $this->completePayment();
    }

    public function render()
    {
        return view('modules.pos.livewire.multi-payment-form');
    }
}
```

### 9.3 Componente: CustomerQuickCreate

**Archivo:** `app/Modules/CRM/Livewire/CustomerQuickCreate.php`

```php
<?php

namespace App\Modules\CRM\Livewire;

use Livewire\Component;
use App\Modules\CRM\Models\Customer;

class CustomerQuickCreate extends Component
{
    public $mode = 'basic'; // basic | full

    // Campos básicos
    public $name = '';
    public $phone = '';
    public $email = '';

    // Campos completos (facturación electrónica)
    public $tipo_identificacion = 'CC';
    public $numero_identificacion = '';
    public $dv = null;
    public $tipo_persona = 'natural';
    public $apellido = '';
    public $razon_social = '';
    public $regimen = 'simplificado';

    // Ubicación geográfica
    public $pais = 'CO';
    public $departamento = '';
    public $ciudad = '';
    public $direccion = '';

    // Otros
    public $sede_despacho = '';
    public $registro_habil_fiscal = false;

    // Datos de ubicación
    public $countries = [];
    public $states = [];
    public $cities = [];

    protected $listeners = ['country-changed' => 'loadStates'];

    public function mount($initialMode = 'basic')
    {
        $this->mode = $initialMode;
        $this->loadCountries();
    }

    /**
     * Cargar países.
     */
    protected function loadCountries()
    {
        $this->countries = \Nnjeim\World\Models\Country::select('id', 'name', 'iso2')
            ->orderBy('name')
            ->get();
    }

    /**
     * Cuando cambia el número de identificación (calcular DV).
     */
    public function updatedNumeroIdentificacion($value)
    {
        if ($this->tipo_identificacion === 'NIT' && $this->pais === 'CO') {
            $this->dv = $this->calculateDV($value);
        }
    }

    /**
     * Calcular dígito de verificación (Colombia).
     */
    protected function calculateDV($nit)
    {
        $weights = [71, 67, 59, 53, 47, 43, 41, 37, 29, 23, 19, 17, 13, 7, 3];
        $nit = str_pad($nit, 15, '0', STR_PAD_LEFT);
        $sum = 0;

        for ($i = 0; $i < 15; $i++) {
            $sum += intval($nit[$i]) * $weights[$i];
        }

        $remainder = $sum % 11;

        return $remainder >= 2 ? 11 - $remainder : $remainder;
    }

    /**
     * Cuando cambia el país.
     */
    public function updatedPais($countryId)
    {
        $this->states = \Nnjeim\World\Models\State::where('country_id', $countryId)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $this->departamento = '';
        $this->ciudad = '';
        $this->cities = [];
    }

    /**
     * Cuando cambia el departamento.
     */
    public function updatedDepartamento($stateId)
    {
        $this->cities = \Nnjeim\World\Models\City::where('state_id', $stateId)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $this->ciudad = '';
    }

    /**
     * Cuando cambia tipo de persona.
     */
    public function updatedTipoPersona($value)
    {
        if ($value === 'juridica') {
            $this->razon_social = trim("{$this->name} {$this->apellido}");
            $this->name = '';
            $this->apellido = '';
        }
    }

    /**
     * Cambiar modo de creación.
     */
    public function switchMode($mode)
    {
        $this->mode = $mode;
    }

    /**
     * Guardar cliente.
     */
    public function save()
    {
        $rules = $this->mode === 'basic'
            ? [
                'name' => 'required|string|max:200',
                'phone' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:200',
            ]
            : [
                'tipo_identificacion' => 'required|string',
                'numero_identificacion' => 'required|string|unique:customers',
                'tipo_persona' => 'required|in:natural,juridica',
                'razon_social' => 'required_if:tipo_persona,juridica',
                'name' => 'required_if:tipo_persona,natural',
                'regimen' => 'required|in:simplificado,comun',
                'pais' => 'required',
                'direccion' => 'required',
            ];

        $this->validate($rules);

        $customer = Customer::create([
            'tipo_identificacion' => $this->tipo_identificacion,
            'numero_identificacion' => $this->numero_identificacion,
            'dv' => $this->dv,
            'tipo_persona' => $this->tipo_persona,
            'nombre' => $this->name,
            'apellido' => $this->apellido,
            'razon_social' => $this->razon_social,
            'regimen' => $this->regimen,
            'pais_id' => $this->pais,
            'departamento_id' => $this->departamento,
            'ciudad_id' => $this->ciudad,
            'direccion' => $this->direccion,
            'telefono' => $this->phone,
            'email' => $this->email,
            'sede_despacho' => $this->sede_despacho,
            'registro_habil_fiscal' => $this->registro_habil_fiscal,
            'active' => true,
        ]);

        $this->dispatch('customer-created', customerId: $customer->id);

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Cliente creado exitosamente'
        ]);

        $this->reset();
    }

    public function render()
    {
        return view('modules.crm.livewire.customer-quick-create');
    }
}
```

---

## 10. BASE DE DATOS COMPLETA

### 10.1 Base de Datos Central (RAP)

```sql
-- =============================================
-- BASE DE DATOS CENTRAL: rap
-- =============================================

-- Usuarios del sistema
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NULL,
    avatar VARCHAR(255) NULL,

    -- 2FA
    two_factor_enabled BOOLEAN DEFAULT FALSE,
    two_factor_type ENUM('email', 'whatsapp', 'google_authenticator') DEFAULT 'email',
    two_factor_secret VARCHAR(255) NULL,
    two_factor_failed_attempts INT DEFAULT 0,
    two_factor_locked_until TIMESTAMP NULL,

    remember_token VARCHAR(100) NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    INDEX idx_email (email),
    INDEX idx_two_factor (two_factor_enabled, two_factor_locked_until)
);

-- Tenants (empresas)
CREATE TABLE tenants (
    id CHAR(36) PRIMARY KEY,  -- UUID
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NULL,
    address TEXT NULL,

    -- Configuración de BD
    db_name VARCHAR(100) NOT NULL UNIQUE,
    db_user VARCHAR(100) NOT NULL,
    db_password VARCHAR(255) NOT NULL,
    db_host VARCHAR(100) DEFAULT '127.0.0.1',
    db_port INT DEFAULT 3306,

    -- Estado y configuración
    is_active BOOLEAN DEFAULT TRUE,
    settings JSON NULL,

    -- Plan y suscripción
    plan VARCHAR(50) DEFAULT 'basic',
    trial_ends_at TIMESTAMP NULL,
    subscription_ends_at TIMESTAMP NULL,

    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    INDEX idx_db_name (db_name),
    INDEX idx_is_active (is_active),
    INDEX idx_plan (plan)
);

-- Dominios de los tenants
CREATE TABLE domains (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id CHAR(36) NOT NULL,
    domain VARCHAR(255) NOT NULL UNIQUE,
    is_primary BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    INDEX idx_domain (domain),
    INDEX idx_tenant_id (tenant_id)
);

-- Relación usuarios-tenants
CREATE TABLE user_tenants (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    tenant_id CHAR(36) NOT NULL,
    role VARCHAR(50) DEFAULT 'user',
    is_active BOOLEAN DEFAULT TRUE,
    last_accessed_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_tenant (user_id, tenant_id),
    INDEX idx_tenant_id (tenant_id),
    INDEX idx_is_active (is_active)
);

-- Códigos 2FA
CREATE TABLE two_factor_codes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    code VARCHAR(10) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    verified_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,

    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_code (user_id, code),
    INDEX idx_expires_at (expires_at)
);

-- Módulos disponibles
CREATE TABLE modules (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(100) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    icon VARCHAR(100) NULL,
    category VARCHAR(50) NULL,
    provider_class VARCHAR(255) NOT NULL,
    is_core BOOLEAN DEFAULT FALSE,
    order INT DEFAULT 0,
    metadata JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    INDEX idx_slug (slug),
    INDEX idx_is_core (is_core)
);

-- Relación tenants-módulos
CREATE TABLE tenant_modules (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id CHAR(36) NOT NULL,
    module_id BIGINT UNSIGNED NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    settings JSON NULL,
    installed_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE,
    UNIQUE KEY unique_tenant_module (tenant_id, module_id),
    INDEX idx_is_active (is_active)
);

-- Plugins disponibles
CREATE TABLE plugins (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(100) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    version VARCHAR(20) NOT NULL,
    description TEXT NULL,
    author VARCHAR(255) NULL,
    category VARCHAR(50) NULL,
    metadata JSON NOT NULL,  -- Almacena plugin.json completo
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    INDEX idx_slug (slug),
    INDEX idx_category (category)
);

-- Relación tenants-plugins
CREATE TABLE tenant_plugins (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id CHAR(36) NOT NULL,
    plugin_id BIGINT UNSIGNED NOT NULL,
    version VARCHAR(20) NOT NULL,
    status ENUM('installing', 'installed', 'uninstalling', 'error') DEFAULT 'installing',
    config JSON NULL,  -- Configuración y credenciales (encriptadas)
    installed_at TIMESTAMP NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (plugin_id) REFERENCES plugins(id) ON DELETE CASCADE,
    UNIQUE KEY unique_tenant_plugin (tenant_id, plugin_id),
    INDEX idx_status (status)
);

-- Suscripciones
CREATE TABLE subscriptions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id CHAR(36) NOT NULL,
    plan VARCHAR(50) NOT NULL,
    status ENUM('active', 'cancelled', 'expired', 'past_due') DEFAULT 'active',
    starts_at TIMESTAMP NOT NULL,
    ends_at TIMESTAMP NULL,
    trial_ends_at TIMESTAMP NULL,
    cancelled_at TIMESTAMP NULL,
    amount DECIMAL(10,2) NOT NULL,
    currency VARCHAR(3) DEFAULT 'USD',
    payment_method VARCHAR(50) NULL,
    metadata JSON NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    INDEX idx_tenant_id (tenant_id),
    INDEX idx_status (status),
    INDEX idx_ends_at (ends_at)
);
```

### 10.2 Base de Datos Tenant (Cada Empresa)

```sql
-- =============================================
-- BASE DE DATOS TENANT: tenant_{uuid}
-- =============================================

-- ============== MÓDULO: INVENTARIO ==============

-- Categorías
CREATE TABLE categories (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    parent_id BIGINT UNSIGNED NULL,
    icon VARCHAR(100) NULL,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL,
    INDEX idx_parent_id (parent_id),
    INDEX idx_active (active)
);

-- Marcas
CREATE TABLE brands (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    logo VARCHAR(255) NULL,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- Bodegas
CREATE TABLE warehouses (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    branch_id BIGINT UNSIGNED NULL,  -- Si hay sucursales
    address TEXT NULL,
    manager_name VARCHAR(255) NULL,
    phone VARCHAR(20) NULL,
    is_main BOOLEAN DEFAULT FALSE,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    INDEX idx_code (code),
    INDEX idx_branch_id (branch_id),
    INDEX idx_active (active)
);

-- Productos
CREATE TABLE products (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(100) NOT NULL UNIQUE,
    barcode VARCHAR(100) NULL,
    name VARCHAR(255) NOT NULL,
    description TEXT NULL,
    category_id BIGINT UNSIGNED NULL,
    brand_id BIGINT UNSIGNED NULL,

    -- Precios
    price DECIMAL(12,2) NOT NULL DEFAULT 0,
    cost DECIMAL(12,2) NOT NULL DEFAULT 0,
    wholesale_price DECIMAL(12,2) NULL,

    -- Inventario
    stock DECIMAL(12,3) DEFAULT 0,
    min_stock DECIMAL(12,3) DEFAULT 0,
    max_stock DECIMAL(12,3) NULL,

    -- Unidades
    unit ENUM('unit', 'kg', 'lb', 'liter', 'meter') DEFAULT 'unit',

    -- Control
    track_serial BOOLEAN DEFAULT FALSE,
    track_batch BOOLEAN DEFAULT FALSE,
    track_expiration BOOLEAN DEFAULT FALSE,

    -- Impuestos
    tax_percent DECIMAL(5,2) DEFAULT 0,
    tax_included BOOLEAN DEFAULT FALSE,

    -- Imágenes
    image VARCHAR(255) NULL,
    images JSON NULL,

    -- Estado
    active BOOLEAN DEFAULT TRUE,
    is_service BOOLEAN DEFAULT FALSE,

    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,

    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
    FOREIGN KEY (brand_id) REFERENCES brands(id) ON DELETE SET NULL,

    INDEX idx_code (code),
    INDEX idx_barcode (barcode),
    INDEX idx_name (name),
    INDEX idx_category_id (category_id),
    INDEX idx_active (active),
    FULLTEXT idx_search (name, description)
);

-- Stock por bodega
CREATE TABLE product_warehouse (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    warehouse_id BIGINT UNSIGNED NOT NULL,
    stock DECIMAL(12,3) DEFAULT 0,
    min_stock DECIMAL(12,3) DEFAULT 0,
    location VARCHAR(100) NULL,  -- Ubicación física

    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE CASCADE,
    UNIQUE KEY unique_product_warehouse (product_id, warehouse_id),
    INDEX idx_stock (stock)
);

-- Movimientos de inventario
CREATE TABLE stock_movements (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_id BIGINT UNSIGNED NOT NULL,
    warehouse_id BIGINT UNSIGNED NOT NULL,
    type ENUM('entrada', 'salida', 'ajuste', 'traslado', 'compra', 'venta', 'devolucion') NOT NULL,
    quantity DECIMAL(12,3) NOT NULL,
    cost DECIMAL(12,2) NULL,
    reference_type VARCHAR(100) NULL,  -- Sale, Purchase, Transfer
    reference_id BIGINT UNSIGNED NULL,
    notes TEXT NULL,
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,

    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (warehouse_id) REFERENCES warehouses(id) ON DELETE CASCADE,
    INDEX idx_product_id (product_id),
    INDEX idx_warehouse_id (warehouse_id),
    INDEX idx_type (type),
    INDEX idx_created_at (created_at)
);

-- Traslados entre bodegas
CREATE TABLE transfers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    from_warehouse_id BIGINT UNSIGNED NOT NULL,
    to_warehouse_id BIGINT UNSIGNED NOT NULL,
    status ENUM('pending', 'in_transit', 'received', 'cancelled') DEFAULT 'pending',
    requested_by BIGINT UNSIGNED NOT NULL,
    approved_by BIGINT UNSIGNED NULL,
    received_by BIGINT UNSIGNED NULL,
    requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approved_at TIMESTAMP NULL,
    received_at TIMESTAMP NULL,
    notes TEXT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (from_warehouse_id) REFERENCES warehouses(id),
    FOREIGN KEY (to_warehouse_id) REFERENCES warehouses(id),
    INDEX idx_status (status),
    INDEX idx_code (code)
);

-- Detalles de traslados
CREATE TABLE transfer_details (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transfer_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    quantity_requested DECIMAL(12,3) NOT NULL,
    quantity_sent DECIMAL(12,3) NULL,
    quantity_received DECIMAL(12,3) NULL,
    notes TEXT NULL,

    FOREIGN KEY (transfer_id) REFERENCES transfers(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id),
    INDEX idx_transfer_id (transfer_id)
);

-- ============== MÓDULO: CRM ==============

-- Clientes
CREATE TABLE customers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,

    -- Identificación
    tipo_identificacion ENUM('CC', 'NIT', 'CE', 'Pasaporte', 'Otro') DEFAULT 'CC',
    numero_identificacion VARCHAR(50) UNIQUE NOT NULL,
    dv INT NULL,  -- Dígito de verificación

    -- Tipo de persona
    tipo_persona ENUM('natural', 'juridica') DEFAULT 'natural',

    -- Datos persona natural
    nombre VARCHAR(200) NULL,
    apellido VARCHAR(200) NULL,

    -- Datos persona jurídica
    razon_social VARCHAR(255) NULL,
    regimen ENUM('simplificado', 'comun') DEFAULT 'simplificado',

    -- Contacto
    email VARCHAR(200) UNIQUE NULL,
    telefono VARCHAR(20) NULL,
    celular VARCHAR(20) NULL,

    -- Ubicación
    pais_id BIGINT UNSIGNED NULL,
    departamento_id BIGINT UNSIGNED NULL,
    ciudad_id BIGINT UNSIGNED NULL,
    direccion TEXT NULL,
    sede_despacho VARCHAR(255) NULL,

    -- Facturación electrónica
    registro_habil_fiscal BOOLEAN DEFAULT FALSE,
    email_facturacion VARCHAR(200) NULL,

    -- Comercial
    tipo_cliente ENUM('retail', 'wholesale', 'vip') DEFAULT 'retail',
    categoria VARCHAR(50) NULL,
    vendedor_asignado_id BIGINT UNSIGNED NULL,

    -- Crédito
    credito_permitido BOOLEAN DEFAULT FALSE,
    limite_credito DECIMAL(12,2) DEFAULT 0,
    dias_credito INT DEFAULT 0,

    -- Estado
    active BOOLEAN DEFAULT TRUE,

    -- Auditoría
    created_by BIGINT UNSIGNED NULL,
    updated_by BIGINT UNSIGNED NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,
    deleted_at TIMESTAMP NULL,

    INDEX idx_numero_identificacion (numero_identificacion),
    INDEX idx_tipo_persona (tipo_persona),
    INDEX idx_email (email),
    INDEX idx_active (active),
    FULLTEXT idx_search (nombre, apellido, razon_social, email)
);

-- Proveedores
CREATE TABLE suppliers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tax_id VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    legal_name VARCHAR(255) NULL,
    contact_name VARCHAR(255) NULL,
    email VARCHAR(200) NULL,
    phone VARCHAR(20) NULL,
    address TEXT NULL,
    city VARCHAR(100) NULL,
    country VARCHAR(100) DEFAULT 'Colombia',
    payment_terms_days INT DEFAULT 0,
    credit_limit DECIMAL(12,2) DEFAULT 0,
    rating DECIMAL(3,2) NULL,  -- Calificación 0-5
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    INDEX idx_tax_id (tax_id),
    INDEX idx_active (active)
);

-- ============== MÓDULO: POS ==============

-- Cajas registradoras
CREATE TABLE cash_registers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    code VARCHAR(50) NOT NULL UNIQUE,
    branch_id BIGINT UNSIGNED NULL,

    -- Balance
    opening_balance DECIMAL(12,2) DEFAULT 0,
    current_balance DECIMAL(12,2) DEFAULT 0,
    expected_balance DECIMAL(12,2) DEFAULT 0,

    -- Fechas
    opening_date TIMESTAMP NULL,
    closing_date TIMESTAMP NULL,

    -- Usuario
    opened_by BIGINT UNSIGNED NULL,
    closed_by BIGINT UNSIGNED NULL,

    -- Estado
    status ENUM('open', 'closed', 'suspended') DEFAULT 'closed',
    notes TEXT NULL,

    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    INDEX idx_code (code),
    INDEX idx_status (status),
    INDEX idx_opening_date (opening_date)
);

-- Movimientos de caja
CREATE TABLE cash_movements (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    cash_register_id BIGINT UNSIGNED NOT NULL,
    type ENUM('ingreso', 'egreso', 'apertura', 'cierre') NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    description VARCHAR(255) NOT NULL,
    reference_type VARCHAR(100) NULL,  -- Sale, Expense
    reference_id BIGINT UNSIGNED NULL,
    payment_method VARCHAR(50) DEFAULT 'cash',
    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,

    FOREIGN KEY (cash_register_id) REFERENCES cash_registers(id) ON DELETE CASCADE,
    INDEX idx_cash_register_id (cash_register_id),
    INDEX idx_type (type),
    INDEX idx_created_at (created_at)
);

-- Ventas
CREATE TABLE sales (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,

    -- Relaciones
    customer_id BIGINT UNSIGNED NULL,
    cash_register_id BIGINT UNSIGNED NULL,
    quotation_id BIGINT UNSIGNED NULL,  -- Si viene de cotización

    -- Fechas
    date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    -- Montos
    subtotal DECIMAL(12,2) NOT NULL,
    discount DECIMAL(12,2) DEFAULT 0,
    tax DECIMAL(12,2) DEFAULT 0,
    total DECIMAL(12,2) NOT NULL,

    -- Pago
    payment_status ENUM('pending', 'partial', 'paid') DEFAULT 'paid',
    amount_paid DECIMAL(12,2) DEFAULT 0,
    change_given DECIMAL(12,2) DEFAULT 0,

    -- Estado
    status ENUM('completed', 'cancelled', 'refunded') DEFAULT 'completed',

    -- Usuario
    seller_id BIGINT UNSIGNED NOT NULL,

    -- Notas
    notes TEXT NULL,

    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    FOREIGN KEY (cash_register_id) REFERENCES cash_registers(id),

    INDEX idx_code (code),
    INDEX idx_customer_id (customer_id),
    INDEX idx_date (date),
    INDEX idx_status (status)
);

-- Detalles de ventas
CREATE TABLE sale_details (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sale_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,

    -- Cantidades
    quantity DECIMAL(12,3) NOT NULL,

    -- Precios
    price DECIMAL(12,2) NOT NULL,
    cost DECIMAL(12,2) NULL,  -- Para cálculo de utilidad

    -- Descuentos
    discount_percent DECIMAL(5,2) DEFAULT 0,
    discount_amount DECIMAL(12,2) DEFAULT 0,

    -- Impuestos
    tax_percent DECIMAL(5,2) DEFAULT 0,
    tax_amount DECIMAL(12,2) DEFAULT 0,

    -- Totales
    subtotal DECIMAL(12,2) NOT NULL,
    total DECIMAL(12,2) NOT NULL,

    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id),

    INDEX idx_sale_id (sale_id),
    INDEX idx_product_id (product_id)
);

-- Métodos de pago por venta
CREATE TABLE sale_payments (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sale_id BIGINT UNSIGNED NOT NULL,
    payment_method VARCHAR(50) NOT NULL,  -- cash, card, transfer, etc
    amount DECIMAL(12,2) NOT NULL,
    reference VARCHAR(100) NULL,  -- Número de transacción
    created_at TIMESTAMP NULL,

    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE CASCADE,
    INDEX idx_sale_id (sale_id)
);

-- Cotizaciones
CREATE TABLE quotations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    customer_id BIGINT UNSIGNED NULL,

    -- Validez
    valid_until DATE NULL,

    -- Montos
    subtotal DECIMAL(12,2) NOT NULL,
    discount DECIMAL(12,2) DEFAULT 0,
    tax DECIMAL(12,2) DEFAULT 0,
    total DECIMAL(12,2) NOT NULL,

    -- Estado
    status ENUM('pending', 'approved', 'rejected', 'converted') DEFAULT 'pending',
    converted_to_sale_id BIGINT UNSIGNED NULL,

    -- Usuario
    created_by BIGINT UNSIGNED NOT NULL,

    notes TEXT NULL,

    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL,
    INDEX idx_code (code),
    INDEX idx_status (status)
);

-- Detalles de cotizaciones (similar a sale_details)
CREATE TABLE quotation_details (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    quotation_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    quantity DECIMAL(12,3) NOT NULL,
    price DECIMAL(12,2) NOT NULL,
    discount_percent DECIMAL(5,2) DEFAULT 0,
    discount_amount DECIMAL(12,2) DEFAULT 0,
    tax_percent DECIMAL(5,2) DEFAULT 0,
    tax_amount DECIMAL(12,2) DEFAULT 0,
    subtotal DECIMAL(12,2) NOT NULL,
    total DECIMAL(12,2) NOT NULL,

    FOREIGN KEY (quotation_id) REFERENCES quotations(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id),
    INDEX idx_quotation_id (quotation_id)
);

-- ============== MÓDULO: FACTURACIÓN ==============

-- Facturas
CREATE TABLE invoices (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,

    -- Relaciones
    customer_id BIGINT UNSIGNED NOT NULL,
    sale_id BIGINT UNSIGNED NULL,

    -- Numeración oficial
    resolution_number VARCHAR(100) NULL,
    prefix VARCHAR(20) NULL,
    consecutive INT NOT NULL,

    -- Fechas
    issue_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    due_date DATE NULL,

    -- Montos
    subtotal DECIMAL(12,2) NOT NULL,
    discount DECIMAL(12,2) DEFAULT 0,
    tax DECIMAL(12,2) DEFAULT 0,
    total DECIMAL(12,2) NOT NULL,

    -- Facturación electrónica
    is_electronic BOOLEAN DEFAULT FALSE,
    cufe VARCHAR(255) NULL,  -- Código único FE
    qr_code TEXT NULL,
    xml_file VARCHAR(255) NULL,
    pdf_file VARCHAR(255) NULL,
    sent_to_dian BOOLEAN DEFAULT FALSE,
    dian_response JSON NULL,

    -- Estado
    status ENUM('draft', 'sent', 'paid', 'cancelled', 'credited') DEFAULT 'draft',

    notes TEXT NULL,

    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (sale_id) REFERENCES sales(id) ON DELETE SET NULL,

    INDEX idx_code (code),
    INDEX idx_customer_id (customer_id),
    INDEX idx_consecutive (prefix, consecutive),
    INDEX idx_issue_date (issue_date),
    INDEX idx_status (status)
);

-- Detalles de facturas
CREATE TABLE invoice_details (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    invoice_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NULL,
    description VARCHAR(255) NOT NULL,
    quantity DECIMAL(12,3) NOT NULL,
    price DECIMAL(12,2) NOT NULL,
    discount_percent DECIMAL(5,2) DEFAULT 0,
    discount_amount DECIMAL(12,2) DEFAULT 0,
    tax_percent DECIMAL(5,2) DEFAULT 0,
    tax_amount DECIMAL(12,2) DEFAULT 0,
    subtotal DECIMAL(12,2) NOT NULL,
    total DECIMAL(12,2) NOT NULL,

    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL,
    INDEX idx_invoice_id (invoice_id)
);

-- Notas crédito
CREATE TABLE credit_notes (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    invoice_id BIGINT UNSIGNED NOT NULL,

    -- Tipo
    type ENUM('total', 'partial') DEFAULT 'total',
    reason VARCHAR(255) NOT NULL,

    -- Montos
    subtotal DECIMAL(12,2) NOT NULL,
    tax DECIMAL(12,2) DEFAULT 0,
    total DECIMAL(12,2) NOT NULL,

    -- FE
    is_electronic BOOLEAN DEFAULT FALSE,
    cufe VARCHAR(255) NULL,

    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (invoice_id) REFERENCES invoices(id),
    INDEX idx_code (code),
    INDEX idx_invoice_id (invoice_id)
);

-- ============== MÓDULO: COMPRAS ==============

-- Órdenes de compra
CREATE TABLE purchase_orders (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    supplier_id BIGINT UNSIGNED NOT NULL,

    -- Fechas
    order_date DATE NOT NULL,
    expected_delivery_date DATE NULL,

    -- Montos
    subtotal DECIMAL(12,2) NOT NULL,
    tax DECIMAL(12,2) DEFAULT 0,
    total DECIMAL(12,2) NOT NULL,

    -- Estado
    status ENUM('draft', 'pending_approval', 'approved', 'received', 'cancelled') DEFAULT 'draft',

    -- Aprobación
    requires_approval BOOLEAN DEFAULT FALSE,
    approved_by BIGINT UNSIGNED NULL,
    approved_at TIMESTAMP NULL,

    notes TEXT NULL,

    created_by BIGINT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL,

    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    INDEX idx_code (code),
    INDEX idx_status (status)
);

-- Detalles de órdenes de compra
CREATE TABLE purchase_order_details (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    purchase_order_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    quantity DECIMAL(12,3) NOT NULL,
    price DECIMAL(12,2) NOT NULL,
    tax_percent DECIMAL(5,2) DEFAULT 0,
    tax_amount DECIMAL(12,2) DEFAULT 0,
    subtotal DECIMAL(12,2) NOT NULL,
    total DECIMAL(12,2) NOT NULL,

    FOREIGN KEY (purchase_order_id) REFERENCES purchase_orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id),
    INDEX idx_purchase_order_id (purchase_order_id)
);

-- ============== MÓDULO: CONFIGURACIÓN ==============

-- Configuración de módulos
CREATE TABLE module_settings (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    module VARCHAR(100) NOT NULL UNIQUE,
    config JSON NOT NULL,
    updated_at TIMESTAMP NULL,

    INDEX idx_module (module)
);

-- Widgets del dashboard
CREATE TABLE dashboard_widgets (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    widget_id VARCHAR(100) NOT NULL,
    name VARCHAR(255) NOT NULL,
    component VARCHAR(255) NOT NULL,
    position JSON NOT NULL,  -- {row, col}
    size JSON NOT NULL,  -- {width, height}
    config JSON NULL,
    refresh_interval INT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- Métodos de pago configurados
CREATE TABLE payment_methods (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(50) NOT NULL UNIQUE,
    icon VARCHAR(100) NULL,
    requires_reference BOOLEAN DEFAULT FALSE,
    is_online BOOLEAN DEFAULT FALSE,
    order INT DEFAULT 0,
    active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP NULL,
    updated_at TIMESTAMP NULL
);

-- Permisos personalizados (Spatie Permission)
-- Ya incluido en las migraciones de Spatie
```

---

## 11. CRONOGRAMA DETALLADO DE DESARROLLO

### Sprint 1 (Semanas 1-2): Fundación Multi-tenant

**Equipo:**
- Backend Lead: Sistema multi-tenancy
- Backend Dev: Estructura modular
- Frontend/Livewire: Layouts y auth
- Fullstack: Docker y testing

**Tareas Específicas:**

**Backend Lead:**
- [ ] Configurar `stancl/tenancy`
- [ ] Crear modelo `Tenant` personalizado
- [ ] Implementar `TenantManager`
- [ ] Middleware `SetTenantConnection`
- [ ] Comando `tenant:create`
- [ ] Seeders para BD master

**Backend Dev:**
- [ ] Estructura de carpetas modular
- [ ] Clases base (BaseModel, BaseController, BaseService)
- [ ] Traits compartidos
- [ ] Migraciones de tenant base
- [ ] Sistema de roles con Spatie Permission

**Frontend/Livewire:**
- [ ] Layouts con Tailwind
- [ ] Componente `Login`
- [ ] Componente `Register`
- [ ] Componente `SelectTenant`
- [ ] Componente `Verify2FA`
- [ ] Sistema de notificaciones Toast

**Fullstack:**
- [ ] Docker Compose (MySQL, Redis, Mailhog)
- [ ] Configuración PHPUnit/Pest
- [ ] Tests de multi-tenancy
- [ ] CI/CD básico con GitHub Actions
- [ ] Documentación inicial

**Entregable:** ✅ Usuario puede registrarse → Se crea tenant → BD creada → Login funcional

---

### Sprint 2 (Semanas 3-4): Módulo Inventario Completo

**Backend Lead:**
- [ ] API interna de Inventario
- [ ] Modelo `Warehouse`
- [ ] Lógica de stock (FIFO/Promedio)
- [ ] Sistema de transferencias

**Backend Dev:**
- [ ] CRUD Productos (modelo, servicio)
- [ ] CRUD Categorías
- [ ] CRUD Marcas
- [ ] CRUD Bodegas
- [ ] Movimientos de inventario
- [ ] Validaciones de stock

**Frontend/Livewire:**
- [ ] Componente `ProductList`
- [ ] Componente `ProductCreate`
- [ ] Componente `ProductEdit`
- [ ] Componente `StockMovements`
- [ ] Búsqueda en tiempo real
- [ ] Integración barcode scanner

**Fullstack:**
- [ ] Importación masiva Excel
- [ ] Generador códigos de barras
- [ ] Tests módulo Inventario
- [ ] Seeders productos demo

**Entregable:** ✅ CRUD completo productos con stock, categorías, bodegas

---

### Sprint 3 (Semanas 5-6): Módulo Clientes Completo

**Backend Lead:**
- [ ] Modelo `Customer` completo
- [ ] Validación NIT + cálculo DV
- [ ] Integración Laravel World

**Backend Dev:**
- [ ] CRUD Clientes
- [ ] CRUD Proveedores
- [ ] Sistema de categorías de clientes
- [ ] Límites de crédito

**Frontend/Livewire:**
- [ ] Componente `CustomerList`
- [ ] Componente `CustomerCreate` (modo básico/completo)
- [ ] Componente `CustomerQuickCreate`
- [ ] Componente `CustomerSelector` (para POS)
- [ ] Selector geográfico (país/depto/ciudad)

**Fullstack:**
- [ ] Importación clientes Excel
- [ ] Exportación a Excel
- [ ] Tests módulo CRM
- [ ] Seeders clientes demo

**Entregable:** ✅ Gestión completa de clientes con facturación electrónica

---

### Sprint 4 (Semanas 7-8): Módulo POS Básico

**Backend Lead:**
- [ ] Arquitectura de ventas
- [ ] Sistema de cajas
- [ ] Lógica transacciones
- [ ] Integración Inventario ↔ POS

**Backend Dev:**
- [ ] Modelo `Sale`, `SaleDetail`
- [ ] Modelo `CashRegister`, `CashMovement`
- [ ] Service `SaleService`
- [ ] Service `CashRegisterService`
- [ ] Reducción automática stock

**Frontend/Livewire:**
- [ ] ⭐ Componente `POSScreen` (pantalla principal)
- [ ] Componente `ProductSelector` (táctil)
- [ ] Componente `ShoppingCart`
- [ ] Componente `MultiPaymentForm`
- [ ] Componente `CashRegisterManagement`
- [ ] Shortcuts teclado

**Fullstack:**
- [ ] Generador tickets (PDF/Térmica)
- [ ] Templates impresión
- [ ] Tests de ventas
- [ ] Optimización performance

**Entregable:** ✅ POS funcional: Buscar → Agregar → Pagar → Imprimir → Stock actualizado

---

### Sprint 5 (Semanas 9-10): Módulo Facturación

**Backend Lead:**
- [ ] Modelo `Invoice`, `InvoiceDetail`
- [ ] Sistema de numeración
- [ ] Resoluciones DIAN
- [ ] Modelo `CreditNote`

**Backend Dev:**
- [ ] Service `InvoiceService`
- [ ] Generador PDF facturas
- [ ] Envío email
- [ ] Notas crédito (parcial/total)

**Frontend/Livewire:**
- [ ] Componente `InvoiceCreate`
- [ ] Componente `InvoiceList`
- [ ] Componente `InvoicePreview`
- [ ] Componente `CreditNoteForm`

**Fullstack:**
- [ ] Plantillas PDF personalizables
- [ ] Queue para envío masivo
- [ ] Tests facturación
- [ ] Reportes de facturación

**Entregable:** ✅ Sistema facturación: Crear → PDF → Email

---

### Sprint 6 (Semanas 11-12): Sistema de Plugins + Facturación Electrónica

**Backend Lead:**
- [ ] `PluginManager` completo
- [ ] `PluginInstaller`
- [ ] Sistema de hooks
- [ ] Metadata `plugin.json`

**Backend Dev:**
- [ ] Plugin Facturación Electrónica
- [ ] Conector DIAN Colombia
- [ ] Generador XML UBL
- [ ] Firmado XML
- [ ] Cliente SOAP DIAN

**Frontend/Livewire:**
- [ ] Marketplace de plugins
- [ ] Wizard instalación plugins
- [ ] Wizard configuración DIAN
- [ ] Formulario credenciales dinámico

**Fullstack:**
- [ ] Tests sistema plugins
- [ ] Tests conector DIAN
- [ ] Documentación plugins
- [ ] Ambiente pruebas DIAN

**Entregable:** ✅ Facturación electrónica DIAN funcional

---

### Sprint 7 (Semanas 13-14): Sistema de Templates

**Backend Lead:**
- [ ] `TemplateManager`
- [ ] `TemplateApplicator`
- [ ] Validación templates

**Backend Dev:**
- [ ] Templates JSON (POS Básico, Restaurante, Institucional)
- [ ] Aplicación automática de configuración
- [ ] Creación datos de ejemplo
- [ ] Configuración widgets

**Frontend/Livewire:**
- [ ] Selector de templates al registrar
- [ ] Wizard onboarding personalizado
- [ ] Dashboard widgets dinámicos

**Fullstack:**
- [ ] Tests sistema templates
- [ ] Validación JSON schemas
- [ ] Documentación templates

**Entregable:** ✅ Templates funcionales con onboarding

---

### Sprint 8 (Semanas 15-16): POS Institucional + Reportes

**Backend Lead:**
- [ ] Multi-sucursales
- [ ] Multi-bodegas
- [ ] Sistema de aprobaciones

**Backend Dev:**
- [ ] Remisiones
- [ ] Cotizaciones
- [ ] Órdenes de compra
- [ ] Compras y proveedores
- [ ] Cuentas por cobrar

**Frontend/Livewire:**
- [ ] Dashboard institucional
- [ ] Reportes avanzados
- [ ] Gráficas (ApexCharts)
- [ ] Exportación Excel

**Fullstack:**
- [ ] Laravel Excel
- [ ] Reportes personalizables
- [ ] Tests integración
- [ ] Performance optimization

**Entregable:** ✅ POS Institucional completo

---

### Sprint 9 (Semanas 17-18): Pulido y Deploy

**Todo el equipo:**
- [ ] Bug fixing
- [ ] Optimización performance
- [ ] Testing E2E
- [ ] Documentación usuario
- [ ] Setup producción
- [ ] CI/CD completo
- [ ] Beta testing

**Entregable:** ✅ Sistema en producción

---

## 12. ESTADO ACTUAL DEL PROYECTO

### ✅ Implementado (60%)

**Multi-Tenancy:**
- ✅ Stancl Tenancy configurado
- ✅ Modelo Tenant personalizado
- ✅ TenantManager
- ✅ Middleware SetTenantConnection
- ✅ Comando tenant:create

**Autenticación:**
- ✅ Laravel Breeze
- ✅ 2FA con email/WhatsApp
- ✅ Selección de tenant
- ✅ Roles con Spatie

**Módulos Básicos:**
- ✅ Dashboard básico
- ✅ Usuarios
- ✅ Clientes (básico, falta campos FE)
- ✅ Productos (completo)
- ✅ Categorías
- ✅ Ventas (modelo creado, falta UI)
- ✅ Caja (modelo creado, falta UI)

### ❌ Pendiente (40%)

**Módulos:**
- ❌ POS (pantalla táctil)
- ❌ Inventario (multi-bodega, traslados)
- ❌ Facturación
- ❌ Compras
- ❌ Reportes

**Sistemas:**
- ❌ Plugins
- ❌ Templates
- ❌ Onboarding wizard
- ❌ Dashboard widgets dinámicos

**Integraciones:**
- ❌ Facturación electrónica
- ❌ Pasarelas de pago
- ❌ WhatsApp (código configurado)
- ❌ Email (envío facturas)

---

## CONCLUSIÓN

Este documento contiene:
✅ Arquitectura completa del sistema
✅ Estructura detallada de archivos
✅ Sistema de plugins con ejemplos
✅ Sistema de templates con 3 ejemplos completos
✅ Componentes Livewire críticos
✅ Base de datos completa (master + tenant)
✅ Cronograma sprint por sprint
✅ Estado actual vs pendiente

**Siguiente paso:** Implementar Sprint por Sprint según el cronograma.

---

**Fin de la Documentación**