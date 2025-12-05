# 🏗️ Arquitectura Multi-tenant Laravel 12 + Livewire

## 📋 Resumen del Proyecto

**Tecnologías:**
- Laravel 12
- Livewire tradicional (sin Volt, sin Alpine.js)
- Tailwind CSS
- Stancl Tenancy
- Base de datos: `rap` (global) + `company` (por tenant)

**Equipo:** 4 desarrolladores trabajando en módulos independientes

---

## 🚀 Comando Principal: Generación Rápida de Módulos

### Crear un módulo completo:

```bash
# Para modelos tenant (datos de empresa)
php artisan make:livewire-module create-customer --model=Customer --migration --tenant

# Para modelos centrales (datos globales)
php artisan make:livewire-module manage-countries --model=Country --migration
```

### ¿Qué genera automáticamente?

1. **Modelo** con conexión correcta configurada
2. **Migración** de la tabla
3. **Componente Livewire** con CRUD completo
4. **Vista Blade** con tabla, formulario modal y búsqueda
5. **Ruta sugerida** para agregar

### Ejemplo de uso:

```bash
php artisan make:livewire-module sales-orders --model=SalesOrder --migration --tenant
```

**Resultado:**
- `app/Models/Tenant/SalesOrder.php`
- `database/migrations/xxxx_create_sales_orders_table.php`
- `app/Livewire/SalesOrders.php`
- `resources/views/livewire/sales-orders.blade.php`

---

## 🗂️ Estructura de Carpetas por Desarrollador

```
app/
├── Models/
│   ├── Central/           # Datos globales (base rap)
│   │   ├── CnfCountry.php
│   │   ├── CnfCity.php
│   │   └── CnfFiscalResponsability.php
│   └── Tenant/            # Datos por empresa (base company)
│       ├── Customer.php
│       ├── Product.php
│       └── SalesOrder.php
├── Livewire/
│   ├── Users/            # 👨‍💻 Desarrollador 1
│   ├── Sales/            # 👨‍💻 Desarrollador 2
│   ├── Inventory/        # 👨‍💻 Desarrollador 3
│   ├── Configuration/    # 👨‍💻 Desarrollador 4
│   └── Selects/          # 🔄 Componentes reutilizables
│       ├── CountrySelect.php
│       ├── CitySelect.php
│       └── FiscalResponsibilitySelect.php
```

---

## 🌐 Configuración de Rutas

### routes/tenant.php (rutas principales)

```php
<?php

use Stancl\Tenancy\Middleware\InitializeTenancy;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

Route::middleware([
    'web',
    InitializeTenancy::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {

    // Módulo Users (Desarrollador 1)
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/', App\Livewire\Users\UsersList::class)->name('index');
        Route::get('/create', App\Livewire\Users\CreateUser::class)->name('create');
    });

    // Módulo Sales (Desarrollador 2)
    Route::prefix('sales')->name('sales.')->group(function () {
        Route::get('/', App\Livewire\Sales\SalesList::class)->name('index');
        Route::get('/orders', App\Livewire\Sales\SalesOrders::class)->name('orders');
    });

    // Módulo Inventory (Desarrollador 3)
    Route::prefix('inventory')->name('inventory.')->group(function () {
        Route::get('/', App\Livewire\Inventory\ProductsList::class)->name('index');
        Route::get('/categories', App\Livewire\Inventory\Categories::class)->name('categories');
    });

    // Módulo Configuration (Desarrollador 4)
    Route::prefix('config')->name('config.')->group(function () {
        Route::get('/', App\Livewire\Configuration\Settings::class)->name('index');
        Route::get('/company', App\Livewire\Configuration\CompanyData::class)->name('company');
    });
});
```

---

## 💾 Modelos y Conexiones

### Modelos Tenant (datos de empresa)

```php
<?php

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $connection = 'tenant';  // ✅ Siempre tenant
    protected $table = 'customers';

    protected $fillable = [
        'name',
        'email',
        'phone',
        'fiscal_responsibility_id', // FK a tabla global
    ];
}
```

### Modelos Central (datos globales)

```php
<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;

class CnfFiscalResponsability extends Model
{
    protected $connection = 'central';  // ✅ Siempre central
    protected $table = 'cnf_fiscal_responsabilities';

    protected $fillable = [
        'description',
        'integrationDataId',
    ];
}
```

---

## 🔄 Componentes Reutilizables

### Ejemplo: Select de Responsabilidades Fiscales

```php
<?php

namespace App\Livewire\Selects;

use Livewire\Component;
use App\Models\Central\CnfFiscalResponsability;

class FiscalResponsibilitySelect extends Component
{
    public $fiscalResponsibilityId = '';
    public $name = 'fiscalResponsibilityId';
    public $placeholder = 'Seleccionar responsabilidad fiscal';
    public $label = 'Responsabilidad Fiscal';
    public $required = true;

    public function mount($fiscalResponsibilityId = '', $name = 'fiscalResponsibilityId', $placeholder = 'Seleccionar responsabilidad fiscal', $label = 'Responsabilidad Fiscal', $required = true)
    {
        $this->fiscalResponsibilityId = $fiscalResponsibilityId;
        $this->name = $name;
        $this->placeholder = $placeholder;
        $this->label = $label;
        $this->required = $required;
    }

    public function updatedFiscalResponsibilityId()
    {
        $this->dispatch('fiscal-responsibility-changed', $this->fiscalResponsibilityId);
    }

    public function getFiscalResponsibilitiesProperty()
    {
        return CnfFiscalResponsability::orderBy('description')->get(['id', 'description']);
    }

    public function render()
    {
        return view('livewire.selects.fiscal-responsibility-select', [
            'fiscalResponsibilities' => $this->fiscalResponsibilities
        ]);
    }
}
```

### Uso en otros componentes:

```html
<livewire:selects.fiscal-responsibility-select
    :fiscal-responsibility-id="$customer->fiscal_responsibility_id"
    name="customer.fiscal_responsibility_id"
    label="Responsabilidad Fiscal del Cliente" />
```

---

## 📐 Estándares de Desarrollo

### 1. Nomenclatura

**Archivos:**
- Componentes: `PascalCase` (ej: `CreateCustomer.php`)
- Vistas: `kebab-case` (ej: `create-customer.blade.php`)
- Rutas: `kebab-case` (ej: `/create-customer`)

**Base de datos:**
- Tablas tenant: `customers`, `sales_orders`, `products`
- Tablas globales: `cnf_countries`, `cnf_cities`

### 2. Estructura de Componente Livewire

```php
class CreateCustomer extends Component
{
    use WithPagination;

    // 1. Propiedades de estado
    public $search = '';
    public $showModal = false;
    public $editingId = null;

    // 2. Propiedades del formulario
    public $name = '';
    public $email = '';

    // 3. Reglas de validación
    protected $rules = [
        'name' => 'required|string|max:255',
        'email' => 'required|email|unique:customers,email',
    ];

    // 4. Métodos principales
    public function render() { }
    public function create() { }
    public function edit($id) { }
    public function save() { }
    public function delete($id) { }

    // 5. Métodos auxiliares
    private function resetForm() { }
}
```

### 3. Comunicación entre Componentes

```php
// Disparar evento
$this->dispatch('customer-created', $customer->id);

// Escuchar evento en otro componente
#[On('customer-created')]
public function refreshCustomerList($customerId)
{
    $this->resetPage();
    session()->flash('message', 'Cliente creado exitosamente.');
}
```

---

## 🚀 Flujo de Trabajo para Desarrolladores

### 1. Crear nuevo módulo:

```bash
php artisan make:livewire-module inventory-products --model=Product --migration --tenant
```

### 2. Personalizar campos del modelo:

```php
// En app/Models/Tenant/Product.php
protected $fillable = [
    'name',
    'description',
    'price',
    'stock',
    'category_id',
];
```

### 3. Actualizar migración:

```php
// En database/migrations/xxxx_create_products_table.php
Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->string('name');
    $table->text('description')->nullable();
    $table->decimal('price', 10, 2);
    $table->integer('stock')->default(0);
    $table->unsignedBigInteger('category_id');
    $table->timestamps();
});
```

### 4. Ejecutar migración:

```bash
# Para tenant actual
php artisan migrate

# Para todos los tenants
php artisan tenants:migrate
```

### 5. Agregar ruta:

```php
// En routes/tenant.php
Route::get('/inventory-products', App\Livewire\InventoryProducts::class)->name('inventory.products');
```

---

## 🔐 Buenas Prácticas de Seguridad

### 1. Validación siempre presente

```php
protected $rules = [
    'name' => 'required|string|max:255',
    'email' => 'required|email|unique:customers,email',
];

public function save()
{
    $this->validate(); // ✅ SIEMPRE validar antes de guardar

    Customer::create([
        'name' => $this->name,
        'email' => $this->email,
    ]);
}
```

### 2. Uso correcto de conexiones

```php
// ✅ Correcto - especificar conexión en modelo
class Customer extends Model
{
    protected $connection = 'tenant';
}

// ❌ Incorrecto - cambiar conexión manualmente
DB::connection('tenant')->table('customers')->get();
```

### 3. Autorización en componentes

```php
public function mount()
{
    $this->authorize('viewAny', Customer::class);
}

public function delete($id)
{
    $customer = Customer::findOrFail($id);
    $this->authorize('delete', $customer);
    $customer->delete();
}
```

---

## 🤝 Colaboración entre Desarrolladores

### 1. Evitar conflictos de Git

- Cada desarrollador trabaja en su módulo (`app/Livewire/Users/`, `app/Livewire/Sales/`, etc.)
- Componentes compartidos se coordinan en equipo
- Migraciones se nombran con prefijo del módulo: `2024_11_02_create_sales_orders_table.php`

### 2. Componentes compartidos

```php
// Crear en app/Livewire/Selects/ para reutilizar
php artisan make:livewire-module category-select --model=Category
```

### 3. Comunicación de cambios

- Notificar cuando se modifiquen componentes en `Selects/`
- Documentar nuevos eventos de Livewire
- Compartir nuevas validaciones o helpers

---

## 📊 Comandos Útiles

```bash
# Crear módulo completo
php artisan make:livewire-module create-invoice --model=Invoice --migration --tenant

# Crear solo select reutilizable
php artisan make:livewire selects/status-select

# Migrar todos los tenants
php artisan tenants:migrate

# Crear nuevo tenant
php artisan tenant:create acme-corp

# Listar tenants
php artisan tenants:list
```

---

## ✅ Checklist para Nuevos Módulos

- [ ] Ejecutar comando `make:livewire-module`
- [ ] Personalizar campos del modelo
- [ ] Actualizar migración con campos específicos
- [ ] Ejecutar `php artisan tenants:migrate`
- [ ] Agregar ruta en `routes/tenant.php`
- [ ] Probar CRUD completo
- [ ] Documentar nuevos eventos o componentes compartidos

---

Con esta arquitectura, los 4 desarrolladores pueden trabajar de forma independiente, rápida y organizada, generando módulos completos en menos de 1 minuto y manteniendo el código estandarizado. 🚀