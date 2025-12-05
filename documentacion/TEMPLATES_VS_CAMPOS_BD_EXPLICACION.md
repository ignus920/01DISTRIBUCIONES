# TEMPLATES vs CAMPOS DE BASE DE DATOS
## EXPLICACIÓN COMPLETA: Cómo Funcionan los Campos Diferentes por Template

---

## TU PREGUNTA

> "Si en el POS tengo poquitos campos pero en el POS Institucional tengo los campos del POS MÁS otros más por ser institucional, ¿eso tiene alguna influencia con los templates?"

## RESPUESTA CORTA

**SÍ, tiene influencia, pero NO de la manera que piensas.**

Los templates **NO cambian la estructura de la base de datos**, sino que:
1. ✅ **Activan/Desactivan CAMPOS** en la interfaz
2. ✅ **Muestran/Ocultan FUNCIONALIDADES**
3. ✅ **Configuran VALIDACIONES**
4. ✅ **Cambian COMPORTAMIENTO** del sistema

**La base de datos SIEMPRE tiene TODOS los campos posibles**, pero:
- En POS Básico → Solo se usan los campos básicos
- En POS Institucional → Se usan TODOS los campos

---

## ESTRATEGIA: BASE DE DATOS ÚNICA CON CAMPOS OPCIONALES

### Concepto

```
┌─────────────────────────────────────────────────────┐
│         BASE DE DATOS (SIEMPRE LA MISMA)            │
├─────────────────────────────────────────────────────┤
│                                                     │
│  Tabla: sales                                       │
│  ├─ id                          [TODOS]            │
│  ├─ code                        [TODOS]            │
│  ├─ customer_id                 [TODOS]            │
│  ├─ date                        [TODOS]            │
│  ├─ subtotal                    [TODOS]            │
│  ├─ total                       [TODOS]            │
│  │                                                  │
│  ├─ quotation_id                [INSTITUCIONAL]    │
│  ├─ remission_id                [INSTITUCIONAL]    │
│  ├─ credit_days                 [INSTITUCIONAL]    │
│  ├─ payment_status              [INSTITUCIONAL]    │
│  ├─ approved_by                 [INSTITUCIONAL]    │
│  ├─ branch_id                   [INSTITUCIONAL]    │
│  └─ warehouse_id                [INSTITUCIONAL]    │
│                                                     │
└─────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────┐
│              TEMPLATE: POS BÁSICO                   │
├─────────────────────────────────────────────────────┤
│  Solo MUESTRA y USA estos campos:                  │
│  ✓ id, code, customer_id, date                     │
│  ✓ subtotal, total                                 │
│                                                     │
│  Los demás campos están en NULL o valores default  │
└─────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────┐
│           TEMPLATE: POS INSTITUCIONAL               │
├─────────────────────────────────────────────────────┤
│  MUESTRA y USA TODOS los campos:                   │
│  ✓ Campos básicos (heredados)                      │
│  ✓ quotation_id, remission_id                      │
│  ✓ credit_days, payment_status                     │
│  ✓ approved_by, branch_id, warehouse_id            │
└─────────────────────────────────────────────────────┘
```

---

## EJEMPLO PRÁCTICO: Tabla SALES

### Migración de la Tabla (ÚNICA para todos)

```php
<?php

// database/migrations/tenant/2025_create_sales_table.php

Schema::create('sales', function (Blueprint $table) {
    $table->id();
    $table->string('code', 50)->unique();

    // ===== CAMPOS BÁSICOS (TODOS los templates) =====
    $table->foreignId('customer_id')->nullable()->constrained();
    $table->timestamp('date')->default(now());
    $table->foreignId('seller_id')->constrained('users');

    // Montos
    $table->decimal('subtotal', 12, 2);
    $table->decimal('discount', 12, 2)->default(0);
    $table->decimal('tax', 12, 2)->default(0);
    $table->decimal('total', 12, 2);

    // ===== CAMPOS INSTITUCIONALES (solo POS Institucional) =====
    // Estos campos SIEMPRE existen, pero solo se usan si el template lo permite

    // Relaciones con otros documentos
    $table->foreignId('quotation_id')->nullable()->constrained();
    $table->foreignId('remission_id')->nullable()->constrained();

    // Multi-sucursales
    $table->foreignId('branch_id')->nullable()->constrained();
    $table->foreignId('warehouse_id')->nullable()->constrained();

    // Crédito
    $table->enum('payment_status', ['pending', 'partial', 'paid'])->default('paid');
    $table->decimal('amount_paid', 12, 2)->default(0);
    $table->integer('credit_days')->nullable();
    $table->date('due_date')->nullable();

    // Aprobaciones
    $table->foreignId('approved_by')->nullable()->constrained('users');
    $table->timestamp('approved_at')->nullable();

    // ===== CAMPOS COMUNES =====
    $table->foreignId('cash_register_id')->nullable()->constrained();
    $table->enum('status', ['completed', 'cancelled', 'refunded'])->default('completed');
    $table->text('notes')->nullable();

    $table->timestamps();

    // Índices
    $table->index('code');
    $table->index('customer_id');
    $table->index('date');
    $table->index('branch_id');
    $table->index('status');
    $table->index('payment_status');
});
```

**CLAVE:** La tabla tiene TODOS los campos, pero:
- Campos institucionales están como `nullable()` (pueden ser NULL)
- Solo se llenan si el template lo permite

---

## CÓMO EL TEMPLATE CONTROLA QUÉ CAMPOS SE USAN

### 1. Configuración del Template

**POS Básico:**
```json
{
  "id": "pos_basico",
  "pre_configuration": {
    "pos": {
      "credit_sales_enabled": false,              // ❌ No crédito
      "quotation_enabled": false,                 // ❌ No cotizaciones
      "remission_enabled": false,                 // ❌ No remisiones
      "require_approval": false,                  // ❌ No aprobaciones
      "multi_branch": false,                      // ❌ No multi-sucursales
      "multi_warehouse": false                    // ❌ No multi-bodegas
    }
  }
}
```

**POS Institucional:**
```json
{
  "id": "pos_institucional",
  "pre_configuration": {
    "pos": {
      "credit_sales_enabled": true,               // ✅ Con crédito
      "quotation_enabled": true,                  // ✅ Con cotizaciones
      "remission_enabled": true,                  // ✅ Con remisiones
      "require_approval": true,                   // ✅ Con aprobaciones
      "approval_amount_threshold": 5000000,       // Aprobación si > $5M
      "multi_branch": true,                       // ✅ Multi-sucursales
      "multi_warehouse": true                     // ✅ Multi-bodegas
    }
  }
}
```

### 2. El Componente Livewire Lee la Configuración

```php
<?php

namespace App\Modules\POS\Livewire;

use Livewire\Component;
use App\Modules\POS\Services\POSConfigService;

class CreateSale extends Component
{
    // Campos básicos (siempre visibles)
    public $customer_id = null;
    public $subtotal = 0;
    public $total = 0;

    // Campos institucionales (condicionales)
    public $quotation_id = null;
    public $remission_id = null;
    public $credit_days = null;
    public $branch_id = null;
    public $warehouse_id = null;

    // Flags de configuración
    public $showCreditOptions = false;
    public $showQuotationSelect = false;
    public $showRemissionSelect = false;
    public $showBranchSelect = false;
    public $showWarehouseSelect = false;
    public $requireApproval = false;

    public function mount()
    {
        $config = app(POSConfigService::class);

        // Leer configuración del template aplicado
        $this->showCreditOptions = $config->get('credit_sales_enabled', false);
        $this->showQuotationSelect = $config->get('quotation_enabled', false);
        $this->showRemissionSelect = $config->get('remission_enabled', false);
        $this->showBranchSelect = $config->get('multi_branch', false);
        $this->showWarehouseSelect = $config->get('multi_warehouse', false);
        $this->requireApproval = $config->get('require_approval', false);
    }

    public function save()
    {
        $data = [
            // Campos básicos (siempre)
            'customer_id' => $this->customer_id,
            'subtotal' => $this->subtotal,
            'total' => $this->total,
        ];

        // Campos institucionales (condicionales)
        if ($this->showQuotationSelect) {
            $data['quotation_id'] = $this->quotation_id;
        }

        if ($this->showRemissionSelect) {
            $data['remission_id'] = $this->remission_id;
        }

        if ($this->showCreditOptions) {
            $data['credit_days'] = $this->credit_days;
            $data['payment_status'] = $this->credit_days > 0 ? 'pending' : 'paid';
        }

        if ($this->showBranchSelect) {
            $data['branch_id'] = $this->branch_id;
        }

        if ($this->showWarehouseSelect) {
            $data['warehouse_id'] = $this->warehouse_id;
        }

        // Crear venta
        $sale = Sale::create($data);

        // Si requiere aprobación
        if ($this->requireApproval && $sale->total > config('pos.approval_amount_threshold')) {
            $sale->update(['status' => 'pending_approval']);
            // Notificar a aprobador
        }
    }

    public function render()
    {
        return view('modules.pos.livewire.create-sale');
    }
}
```

### 3. La Vista Muestra/Oculta Campos

```blade
{{-- resources/views/modules/pos/livewire/create-sale.blade.php --}}

<div class="create-sale">

    {{-- CAMPOS BÁSICOS (siempre visibles) --}}
    <div class="form-group">
        <label>Cliente</label>
        <livewire:customer-selector wire:model="customer_id" />
    </div>

    {{-- CAMPOS INSTITUCIONALES (condicionales) --}}

    @if($showQuotationSelect)
        <div class="form-group">
            <label>Cotización (opcional)</label>
            <select wire:model="quotation_id">
                <option value="">Sin cotización</option>
                @foreach($quotations as $quotation)
                    <option value="{{ $quotation->id }}">
                        {{ $quotation->code }} - {{ $quotation->customer->name }}
                    </option>
                @endforeach
            </select>
        </div>
    @endif

    @if($showRemissionSelect)
        <div class="form-group">
            <label>Remisión (opcional)</label>
            <select wire:model="remission_id">
                <option value="">Sin remisión</option>
                {{-- ... opciones ... --}}
            </select>
        </div>
    @endif

    @if($showBranchSelect)
        <div class="form-group">
            <label>Sucursal *</label>
            <select wire:model="branch_id" required>
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                @endforeach
            </select>
        </div>
    @endif

    @if($showWarehouseSelect)
        <div class="form-group">
            <label>Bodega *</label>
            <select wire:model="warehouse_id" required>
                @foreach($warehouses as $warehouse)
                    <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                @endforeach
            </select>
        </div>
    @endif

    @if($showCreditOptions)
        <div class="form-group">
            <label>
                <input type="checkbox" wire:model="enable_credit">
                Venta a Crédito
            </label>

            @if($enable_credit)
                <div class="credit-fields">
                    <label>Días de Crédito</label>
                    <input type="number" wire:model="credit_days" min="1" max="120">

                    <p>Fecha de vencimiento: {{ now()->addDays($credit_days)->format('d/m/Y') }}</p>
                </div>
            @endif
        </div>
    @endif

    {{-- CARRITO DE PRODUCTOS --}}
    <livewire:shopping-cart />

    {{-- TOTALES (siempre visible) --}}
    <div class="totals">
        <p>Subtotal: ${{ number_format($subtotal, 2) }}</p>
        <p>Total: ${{ number_format($total, 2) }}</p>
    </div>

    {{-- BOTÓN GUARDAR --}}
    <button wire:click="save" class="btn-primary">
        @if($requireApproval && $total > 5000000)
            Enviar a Aprobación
        @else
            Completar Venta
        @endif
    </button>

</div>
```

---

## COMPARACIÓN VISUAL

### POS Básico - Formulario Venta

```
┌─────────────────────────────────────┐
│     NUEVA VENTA - POS BÁSICO        │
├─────────────────────────────────────┤
│                                     │
│  Cliente: [Seleccionar ▼]          │
│                                     │
│  ┌───────────────────────────────┐ │
│  │   CARRITO DE PRODUCTOS        │ │
│  │   - Producto 1    $10.00      │ │
│  │   - Producto 2    $15.00      │ │
│  └───────────────────────────────┘ │
│                                     │
│  Subtotal:           $25.00        │
│  Total:              $25.00        │
│                                     │
│  [  COMPLETAR VENTA  ]             │
│                                     │
└─────────────────────────────────────┘

Solo 5 campos visibles ✓
```

### POS Institucional - Formulario Venta

```
┌─────────────────────────────────────┐
│  NUEVA VENTA - POS INSTITUCIONAL    │
├─────────────────────────────────────┤
│                                     │
│  Cliente: [Seleccionar ▼] *        │
│                                     │
│  Cotización: [Seleccionar ▼]       │  ← NUEVO
│  Remisión: [Seleccionar ▼]         │  ← NUEVO
│                                     │
│  Sucursal: [Principal ▼] *         │  ← NUEVO
│  Bodega: [Bodega 1 ▼] *            │  ← NUEVO
│                                     │
│  ┌───────────────────────────────┐ │
│  │   CARRITO DE PRODUCTOS        │ │
│  │   - Producto 1    $10.00      │ │
│  │   - Producto 2    $15.00      │ │
│  └───────────────────────────────┘ │
│                                     │
│  ☐ Venta a Crédito                 │  ← NUEVO
│    └─ Días: [30 ▼]                 │  ← NUEVO
│                                     │
│  Subtotal:           $25.00        │
│  Total:              $25.00        │
│                                     │
│  [  ENVIAR A APROBACIÓN  ]         │  ← DIFERENTE
│                                     │
└─────────────────────────────────────┘

15+ campos visibles ✓
```

---

## ESTRATEGIAS DE BASE DE DATOS

### Opción 1: TABLA ÚNICA CON CAMPOS NULLABLE ⭐ RECOMENDADA

**Ventajas:**
- ✅ Simple de mantener
- ✅ No requiere cambios de BD al cambiar template
- ✅ Migración fácil entre templates
- ✅ Reportes unificados

**Desventajas:**
- ❌ Algunos campos siempre NULL en templates básicos
- ❌ Tabla puede tener muchos campos

**Cuándo usar:** 99% de los casos

```sql
CREATE TABLE sales (
    id BIGINT PRIMARY KEY,
    code VARCHAR(50),
    customer_id BIGINT,
    total DECIMAL(12,2),

    -- Campos institucionales (nullable)
    quotation_id BIGINT NULL,           -- Solo POS Institucional
    remission_id BIGINT NULL,           -- Solo POS Institucional
    branch_id BIGINT NULL,              -- Solo POS Institucional
    approved_by BIGINT NULL             -- Solo POS Institucional
);
```

### Opción 2: TABLA BASE + TABLA EXTENDIDA

**Ventajas:**
- ✅ Tabla principal más limpia
- ✅ Campos institucionales separados

**Desventajas:**
- ❌ Más complejo (JOINs)
- ❌ Relaciones más complejas
- ❌ Queries más lentas

**Cuándo usar:** Solo si tienes MUCHOS campos institucionales (30+)

```sql
-- Tabla base (todos los templates)
CREATE TABLE sales (
    id BIGINT PRIMARY KEY,
    code VARCHAR(50),
    customer_id BIGINT,
    total DECIMAL(12,2)
);

-- Tabla extendida (solo POS Institucional)
CREATE TABLE sale_institutional_data (
    id BIGINT PRIMARY KEY,
    sale_id BIGINT REFERENCES sales(id),
    quotation_id BIGINT NULL,
    remission_id BIGINT NULL,
    branch_id BIGINT NULL,
    approved_by BIGINT NULL
);
```

### Opción 3: COLUMNAS JSON (NO RECOMENDADA)

**Ventajas:**
- ✅ Flexible

**Desventajas:**
- ❌ No se puede indexar
- ❌ Queries complejas
- ❌ Validaciones difíciles

```sql
CREATE TABLE sales (
    id BIGINT PRIMARY KEY,
    code VARCHAR(50),
    customer_id BIGINT,
    total DECIMAL(12,2),

    -- NO RECOMENDADO
    extra_data JSON  -- {"quotation_id": 123, "branch_id": 5}
);
```

---

## PATRÓN COMPLETO: DE EXCEL A CÓDIGO

### Tu Excel (ejemplo)

```
Tabla: VENTAS

POS Básico:
- id
- code
- customer_id
- date
- total

POS Institucional (campos adicionales):
- quotation_id
- remission_id
- branch_id
- warehouse_id
- credit_days
- approved_by
```

### 1. Migración (TODO incluido)

```php
<?php

Schema::create('sales', function (Blueprint $table) {
    $table->id();
    $table->string('code', 50);

    // POS Básico (siempre)
    $table->foreignId('customer_id')->nullable();
    $table->timestamp('date')->default(now());
    $table->decimal('total', 12, 2);

    // POS Institucional (condicional - nullable)
    $table->foreignId('quotation_id')->nullable();
    $table->foreignId('remission_id')->nullable();
    $table->foreignId('branch_id')->nullable();
    $table->foreignId('warehouse_id')->nullable();
    $table->integer('credit_days')->nullable();
    $table->foreignId('approved_by')->nullable();

    $table->timestamps();
});
```

### 2. Modelo (TODO incluido)

```php
<?php

namespace App\Models;

class Sale extends Model
{
    protected $fillable = [
        // Básicos
        'code',
        'customer_id',
        'date',
        'total',

        // Institucionales (opcionales)
        'quotation_id',
        'remission_id',
        'branch_id',
        'warehouse_id',
        'credit_days',
        'approved_by',
    ];

    // Relaciones básicas
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    // Relaciones institucionales
    public function quotation()
    {
        return $this->belongsTo(Quotation::class);
    }

    public function remission()
    {
        return $this->belongsTo(Remission::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
```

### 3. Template POS Básico

```json
{
  "id": "pos_basico",
  "pre_configuration": {
    "pos": {
      "fields_enabled": {
        "customer_id": true,
        "date": true,
        "total": true,
        "quotation_id": false,          // ❌ Oculto
        "remission_id": false,          // ❌ Oculto
        "branch_id": false,             // ❌ Oculto
        "warehouse_id": false,          // ❌ Oculto
        "credit_days": false,           // ❌ Oculto
        "approved_by": false            // ❌ Oculto
      }
    }
  }
}
```

### 4. Template POS Institucional

```json
{
  "id": "pos_institucional",
  "pre_configuration": {
    "pos": {
      "fields_enabled": {
        "customer_id": true,
        "date": true,
        "total": true,
        "quotation_id": true,           // ✅ Visible
        "remission_id": true,           // ✅ Visible
        "branch_id": true,              // ✅ Visible y requerido
        "warehouse_id": true,           // ✅ Visible y requerido
        "credit_days": true,            // ✅ Visible
        "approved_by": true             // ✅ Visible si monto > threshold
      },
      "required_fields": ["branch_id", "warehouse_id"]
    }
  }
}
```

### 5. Service que Respeta la Configuración

```php
<?php

namespace App\Modules\POS\Services;

class SaleService
{
    protected $config;

    public function __construct(POSConfigService $config)
    {
        $this->config = $config;
    }

    public function create(array $data): Sale
    {
        // Obtener campos habilitados del template
        $enabledFields = $this->config->get('fields_enabled', []);
        $requiredFields = $this->config->get('required_fields', []);

        // Validar campos requeridos
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                throw new \Exception("El campo {$field} es requerido");
            }
        }

        // Filtrar solo campos habilitados
        $allowedData = [];
        foreach ($data as $key => $value) {
            if ($enabledFields[$key] ?? false) {
                $allowedData[$key] = $value;
            } elseif (in_array($key, ['customer_id', 'date', 'total'])) {
                // Campos básicos siempre permitidos
                $allowedData[$key] = $value;
            }
        }

        // Crear venta
        $sale = Sale::create($allowedData);

        return $sale;
    }
}
```

---

## MIGRACIÓN ENTRE TEMPLATES

### Escenario: Cliente empieza con POS Básico, luego upgrade a Institucional

**¿Qué pasa con sus ventas anteriores?**

```php
// Ventas creadas con POS Básico
Sales antes del upgrade:
- customer_id: 123
- total: 50000
- quotation_id: NULL          ← No se usaba
- branch_id: NULL             ← No se usaba
- warehouse_id: NULL          ← No se usaba

// Después del upgrade a Institucional
Sales nuevas:
- customer_id: 456
- total: 100000
- quotation_id: 789           ← Ahora se usa
- branch_id: 1                ← Ahora se usa
- warehouse_id: 2             ← Ahora se usa
```

**Ventaja:** Las ventas antiguas siguen funcionando, solo tienen NULL en campos que no usaban.

**Script de Migración Opcional:**

```php
<?php

// Actualizar ventas antiguas con valores por defecto
DB::table('sales')
    ->whereNull('branch_id')
    ->update([
        'branch_id' => 1, // Sucursal principal
        'warehouse_id' => 1 // Bodega principal
    ]);
```

---

## RESUMEN FINAL

### ✅ LO QUE DEBES HACER

1. **Base de Datos:**
   - Crea TODAS las tablas con TODOS los campos posibles
   - Marca campos institucionales como `nullable()`

2. **Templates:**
   - Define qué campos están habilitados en cada template
   - Define qué campos son requeridos

3. **Código:**
   - Lee la configuración del template aplicado
   - Muestra/oculta campos según configuración
   - Valida solo campos habilitados

4. **Beneficios:**
   - ✅ Cliente puede hacer upgrade fácilmente
   - ✅ No requieres cambios de BD
   - ✅ Datos históricos preservados
   - ✅ Un solo código para todos los templates

### ❌ LO QUE NO DEBES HACER

- ❌ NO crear tablas diferentes por template
- ❌ NO cambiar estructura de BD al cambiar template
- ❌ NO hardcodear campos en vistas (leer de config)

---

## DIAGRAMA FINAL

```
┌──────────────────────────────────────────────────────────┐
│                  USUARIO SE REGISTRA                     │
└────────────────────┬─────────────────────────────────────┘
                     │
                     ▼
┌──────────────────────────────────────────────────────────┐
│          SELECCIONA TEMPLATE (POS Básico)                │
└────────────────────┬─────────────────────────────────────┘
                     │
                     ▼
┌──────────────────────────────────────────────────────────┐
│        SISTEMA CREA TENANT CON BD COMPLETA               │
│  Tabla sales tiene TODOS los campos (básicos + insti)   │
└────────────────────┬─────────────────────────────────────┘
                     │
                     ▼
┌──────────────────────────────────────────────────────────┐
│      TEMPLATE APLICA CONFIGURACIÓN                       │
│  {                                                       │
│    "fields_enabled": {                                   │
│      "quotation_id": false,    ← Deshabilitado          │
│      "branch_id": false         ← Deshabilitado          │
│    }                                                     │
│  }                                                       │
└────────────────────┬─────────────────────────────────────┘
                     │
                     ▼
┌──────────────────────────────────────────────────────────┐
│         USUARIO USA SISTEMA (POS Básico)                 │
│  - Solo ve campos básicos                               │
│  - Ventas se guardan con campos insti en NULL           │
└────────────────────┬─────────────────────────────────────┘
                     │
                     ▼
┌──────────────────────────────────────────────────────────┐
│         3 MESES DESPUÉS: HACE UPGRADE                    │
│           Template → POS Institucional                   │
└────────────────────┬─────────────────────────────────────┘
                     │
                     ▼
┌──────────────────────────────────────────────────────────┐
│      SISTEMA ACTUALIZA CONFIGURACIÓN                     │
│  {                                                       │
│    "fields_enabled": {                                   │
│      "quotation_id": true,     ← Ahora habilitado       │
│      "branch_id": true          ← Ahora habilitado       │
│    }                                                     │
│  }                                                       │
└────────────────────┬─────────────────────────────────────┘
                     │
                     ▼
┌──────────────────────────────────────────────────────────┐
│      USUARIO AHORA VE Y USA CAMPOS INSTITUCIONALES       │
│  - Ventas antiguas: campos insti en NULL (OK)           │
│  - Ventas nuevas: campos insti con valores              │
│  - TODO FUNCIONA ✅                                      │
└──────────────────────────────────────────────────────────┘
```

---

**¿ENTIENDES AHORA CÓMO FUNCIONA?**

La base de datos es la MISMA para todos, pero los templates controlan:
- ✅ Qué campos se MUESTRAN
- ✅ Qué campos son REQUERIDOS
- ✅ Qué funcionalidades están HABILITADAS
- ✅ Cómo se COMPORTA el sistema

**Todo sin cambiar la estructura de la base de datos.**