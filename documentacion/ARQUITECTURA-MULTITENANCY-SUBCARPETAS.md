# 🏗️ Arquitectura Multi-tenant Laravel 12 + Livewire (CON SUBCARPETAS)

## 📋 Resumen del Proyecto

**Tecnologías:**
- Laravel 12
- Livewire tradicional (sin Volt, sin Alpine.js)
- Tailwind CSS
- Stancl Tenancy
- Base de datos: `rap` (global) + `company` (por tenant)

**Equipo:** 4 desarrolladores trabajando en módulos independientes con **subcarpetas organizadas**

---

## 🚀 Comando Principal: Generación Rápida de Módulos con Subcarpetas

### Crear un módulo completo:

```bash
# Especificando módulo manualmente
php artisan make:livewire-module customer-form --model=Customer --migration --tenant --module=Customers

# Con inferencia automática (recomendado)
php artisan make:livewire-module sales-orders --model=SalesOrder --migration --tenant
php artisan make:livewire-module product-catalog --model=Product --migration --tenant
php artisan make:livewire-module user-management --model=User --migration --tenant
php artisan make:livewire-module inventory-control --model=Inventory --migration --tenant

# Para datos globales
php artisan make:livewire-module countries-manager --model=Country --migration --module=Catalogs
```

### 🧠 Inferencia Automática de Módulos

El comando detecta automáticamente el módulo basado en palabras clave:

| Palabras clave | Módulo generado |
|----------------|----------------|
| `product`, `catalog` | **Products** |
| `sale`, `order`, `invoice` | **Sales** |
| `customer`, `client` | **Customers** |
| `inventory`, `stock` | **Inventory** |
| `user`, `auth` | **Users** |
| `config`, `setting` | **Configuration** |
| `report`, `analytics` | **Reports** |

### ¿Qué genera automáticamente?

1. **Modelo** con conexión correcta configurada
2. **Migración** de la tabla
3. **Componente Livewire** en subcarpeta del módulo
4. **Vista Blade** en subcarpeta organizada
5. **Namespace completo** con subcarpetas
6. **Ruta sugerida** con namespace completo

---

## 🗂️ Estructura de Carpetas Modular con Subcarpetas

```
app/
├── Models/
│   ├── Central/              # Datos globales (base rap)
│   │   ├── CnfCountry.php
│   │   ├── CnfCity.php
│   │   └── CnfFiscalResponsability.php
│   └── Tenant/               # Datos por empresa (base company)
│       ├── Customer.php
│       ├── Product.php
│       └── SalesOrder.php
├── Livewire/
│   ├── Tenant/               # 🎯 Componentes tenant organizados por módulo
│   │   ├── Customers/        # 👨‍💻 Desarrollador 1: Gestión de clientes
│   │   │   ├── CustomersList.php
│   │   │   ├── CustomerForm.php
│   │   │   └── CustomerProfile.php
│   │   ├── Sales/            # 👨‍💻 Desarrollador 2: Ventas
│   │   │   ├── SalesOrders.php
│   │   │   ├── SalesReports.php
│   │   │   └── InvoiceManager.php
│   │   ├── Products/         # 👨‍💻 Desarrollador 3: Productos/Inventario
│   │   │   ├── ProductsCatalog.php
│   │   │   ├── ProductForm.php
│   │   │   ├── StockControl.php
│   │   │   └── Categories.php
│   │   └── Configuration/    # 👨‍💻 Desarrollador 4: Configuración
│   │       ├── CompanySettings.php
│   │       ├── UserManagement.php
│   │       └── SystemConfig.php
│   ├── Central/              # 🌐 Componentes datos globales
│   │   ├── Catalogs/
│   │   │   ├── CountriesManager.php
│   │   │   └── FiscalManager.php
│   │   └── Configuration/
│   │       └── GlobalSettings.php
│   └── Selects/              # 🔄 Componentes reutilizables
│       ├── CountrySelect.php
│       ├── CitySelect.php
│       └── FiscalResponsibilitySelect.php
└── resources/views/livewire/
    ├── tenant/               # 🎯 Vistas organizadas por módulo
    │   ├── customers/
    │   │   ├── customers-list.blade.php
    │   │   └── customer-form.blade.php
    │   ├── sales/
    │   │   ├── sales-orders.blade.php
    │   │   └── sales-reports.blade.php
    │   ├── products/
    │   │   ├── products-catalog.blade.php
    │   │   └── stock-control.blade.php
    │   └── configuration/
    │       └── company-settings.blade.php
    ├── central/
    │   ├── catalogs/
    │   └── configuration/
    └── selects/
        ├── country-select.blade.php
        └── city-select.blade.php
```

---

## 🌐 Configuración de Rutas con Subcarpetas

### routes/web.php (rutas principales)

```php
<?php

use Stancl\Tenancy\Middleware\InitializeTenancy;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

Route::middleware([
    'web',
    InitializeTenancy::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {

    // Módulo Customers (Desarrollador 1)
    Route::prefix('customers')->name('customers.')->group(function () {
        Route::get('/', App\Livewire\Tenant\Customers\CustomersList::class)->name('index');
        Route::get('/form', App\Livewire\Tenant\Customers\CustomerForm::class)->name('form');
        Route::get('/profile', App\Livewire\Tenant\Customers\CustomerProfile::class)->name('profile');
    });

    // Módulo Sales (Desarrollador 2)
    Route::prefix('sales')->name('sales.')->group(function () {
        Route::get('/', App\Livewire\Tenant\Sales\SalesOrders::class)->name('index');
        Route::get('/reports', App\Livewire\Tenant\Sales\SalesReports::class)->name('reports');
        Route::get('/invoices', App\Livewire\Tenant\Sales\InvoiceManager::class)->name('invoices');
    });

    // Módulo Products (Desarrollador 3)
    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/', App\Livewire\Tenant\Products\ProductsCatalog::class)->name('index');
        Route::get('/form', App\Livewire\Tenant\Products\ProductForm::class)->name('form');
        Route::get('/stock', App\Livewire\Tenant\Products\StockControl::class)->name('stock');
        Route::get('/categories', App\Livewire\Tenant\Products\Categories::class)->name('categories');
    });

    // Módulo Configuration (Desarrollador 4)
    Route::prefix('config')->name('config.')->group(function () {
        Route::get('/', App\Livewire\Tenant\Configuration\CompanySettings::class)->name('index');
        Route::get('/users', App\Livewire\Tenant\Configuration\UserManagement::class)->name('users');
        Route::get('/system', App\Livewire\Tenant\Configuration\SystemConfig::class)->name('system');
    });
});
```

---

## 💾 Modelos y Conexiones (Sin cambios)

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

---

## 🔄 Componentes Reutilizables (Sin cambios)

Los componentes en `app/Livewire/Selects/` se mantienen igual y se pueden usar desde cualquier módulo:

```html
<!-- Desde cualquier vista -->
<livewire:selects.fiscal-responsibility-select
    :fiscal-responsibility-id="$customer->fiscal_responsibility_id"
    name="customer.fiscal_responsibility_id"
    label="Responsabilidad Fiscal del Cliente" />
```

---

## 📐 Estándares de Desarrollo con Subcarpetas

### 1. Nomenclatura

**Namespaces:**
- Componentes tenant: `App\Livewire\Tenant\{Module}\{Component}`
- Componentes central: `App\Livewire\Central\{Module}\{Component}`
- Ejemplo: `App\Livewire\Tenant\Sales\SalesOrders`

**Rutas de vistas:**
- Vista tenant: `livewire.tenant.{module}.{component}`
- Vista central: `livewire.central.{module}.{component}`
- Ejemplo: `livewire.tenant.sales.sales-orders`

**Archivos:**
- Componentes: `PascalCase` (ej: `SalesOrders.php`)
- Vistas: `kebab-case` (ej: `sales-orders.blade.php`)
- Carpetas: `PascalCase` para PHP, `kebab-case` para vistas

### 2. Estructura de Componente Livewire (Sin cambios)

```php
<?php

namespace App\Livewire\Tenant\Sales;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Tenant\SalesOrder;

class SalesOrders extends Component
{
    use WithPagination;

    public $search = '';
    public $showModal = false;

    public function render()
    {
        $items = SalesOrder::where('customer_name', 'like', '%' . $this->search . '%')
            ->latest()
            ->paginate(10);

        return view('livewire.tenant.sales.sales-orders', [
            'items' => $items
        ]);
    }
}
```

---

## 🚀 Flujo de Trabajo para Desarrolladores

### 1. Crear nuevo módulo:

```bash
# Desarrollador 1 (Customers)
php artisan make:livewire-module customer-profile --model=Customer --migration --tenant

# Desarrollador 2 (Sales)
php artisan make:livewire-module invoice-manager --model=Invoice --migration --tenant

# Desarrollador 3 (Products)
php artisan make:livewire-module stock-movements --model=StockMovement --migration --tenant

# Desarrollador 4 (Configuration)
php artisan make:livewire-module user-permissions --model=UserPermission --migration --tenant
```

### 2. Resultado automático:

```
✅ Desarrollador 1 obtiene:
├── app/Livewire/Tenant/Customers/CustomerProfile.php
├── app/Models/Tenant/Customer.php
├── resources/views/livewire/tenant/customers/customer-profile.blade.php
└── database/migrations/xxxx_create_customers_table.php

✅ Desarrollador 2 obtiene:
├── app/Livewire/Tenant/Sales/InvoiceManager.php
├── app/Models/Tenant/Invoice.php
├── resources/views/livewire/tenant/sales/invoice-manager.blade.php
└── database/migrations/xxxx_create_invoices_table.php
```

### 3. Ventajas de las subcarpetas:

- **✅ Separación clara**: Cada desarrollador tiene su carpeta exclusiva
- **✅ Escalabilidad**: Fácil agregar más componentes sin conflictos
- **✅ Organización**: Encuentra rápidamente componentes relacionados
- **✅ Namespaces limpios**: Estructura coherente y predecible
- **✅ Colaboración**: Sin conflictos de git entre módulos

---

## 🤝 Colaboración entre Desarrolladores

### 1. Evitar conflictos de Git

- **Desarrollador 1**: Solo trabaja en `app/Livewire/Tenant/Customers/`
- **Desarrollador 2**: Solo trabaja en `app/Livewire/Tenant/Sales/`
- **Desarrollador 3**: Solo trabaja en `app/Livewire/Tenant/Products/`
- **Desarrollador 4**: Solo trabaja en `app/Livewire/Tenant/Configuration/`

### 2. Componentes compartidos

```php
// Para crear componentes reutilizables (solo coordinador del equipo)
php artisan make:livewire selects/category-select --model=Category

// Resultado: app/Livewire/Selects/CategorySelect.php
// Uso: <livewire:selects.category-select />
```

### 3. Comunicación de cambios

- Notificar cuando se modifiquen componentes en `Selects/`
- Documentar nuevos eventos de Livewire
- Compartir nuevas validaciones o helpers

---

## 📊 Comandos Útiles

```bash
# Crear módulo completo con inferencia automática
php artisan make:livewire-module sales-dashboard --model=SalesDashboard --migration --tenant

# Crear módulo especificando carpeta manualmente
php artisan make:livewire-module reports-analytics --model=Report --migration --tenant --module=Reports

# Para datos globales
php artisan make:livewire-module fiscal-config --model=FiscalConfig --migration --module=Configuration

# Migrar todos los tenants
php artisan tenants:migrate

# Crear nuevo tenant
php artisan tenant:create acme-corp
```

---

## ✅ Checklist para Nuevos Módulos

- [ ] Ejecutar comando `make:livewire-module`
- [ ] Personalizar campos del modelo
- [ ] Actualizar migración con campos específicos
- [ ] Ejecutar `php artisan tenants:migrate`
- [ ] Agregar ruta en `routes/web.php`
- [ ] Probar CRUD completo
- [ ] Documentar nuevos eventos o componentes compartidos

---

## 🎯 Ventajas de esta Arquitectura

### ✅ **Para el Proyecto:**
- **Escalabilidad**: Soporta crecimiento sin reorganización
- **Mantenibilidad**: Código organizado y predecible
- **Rendimiento**: Autoloading eficiente con namespaces claros

### ✅ **Para los Desarrolladores:**
- **Productividad**: Módulos completos en 30 segundos
- **Autonomía**: Cada desarrollador en su carpeta
- **Consistencia**: Estructura estándar para todos

### ✅ **Para el Equipo:**
- **Colaboración**: Sin conflictos de git
- **Escalabilidad**: Fácil incorporar más desarrolladores
- **Organización**: Estructura clara y profesional

---

Con esta arquitectura de subcarpetas, tu proyecto está preparado para escalar profesionalmente manteniendo orden y eficiencia. 🚀