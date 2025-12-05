# 🚀 Guía de Comandos Livewire - Tienda Multitenancy

## 📋 Comandos Básicos de Livewire

### Crear Componentes

```bash
# Crear componente completo (clase + vista)
php artisan make:livewire create-customer

# Crear componente en subcarpeta
php artisan make:livewire customer/create-customer
php artisan make:livewire forms/customer-form

# Crear solo la clase
php artisan make:livewire CreateCustomer --class-only

# Crear solo la vista
php artisan make:livewire CreateCustomer --view-only
```

## 🗂️ Estructura de Archivos

Cuando ejecutas `php artisan make:livewire create-customer`, se crean:

```
app/
└── Livewire/
    └── CreateCustomer.php          # Clase del componente

resources/
└── views/
    └── livewire/
        └── create-customer.blade.php   # Vista del componente
```

## 🛠️ Ejemplos Prácticos para Nuestro Proyecto

### 1. Crear Formulario de Clientes
```bash
php artisan make:livewire customer/create-customer
```

**Genera:**
- `app/Livewire/Customer/CreateCustomer.php`
- `resources/views/livewire/customer/create-customer.blade.php`

### 2. Crear Lista de Productos
```bash
php artisan make:livewire product/product-list
```

**Genera:**
- `app/Livewire/Product/ProductList.php`
- `resources/views/livewire/product/product-list.blade.php`

### 3. Crear Formulario de Configuración
```bash
php artisan make:livewire config/company-settings
```

**Genera:**
- `app/Livewire/Config/CompanySettings.php`
- `resources/views/livewire/config/company-settings.blade.php`

## 🎯 Estructura Recomendada para el Equipo

```
app/Livewire/
├── Customer/
│   ├── CreateCustomer.php
│   ├── EditCustomer.php
│   └── CustomerList.php
├── Product/
│   ├── CreateProduct.php
│   ├── EditProduct.php
│   └── ProductList.php
├── Forms/
│   ├── CompanySetup.php
│   └── UserRegistration.php
└── Config/
    ├── CompanySettings.php
    └── SystemConfig.php
```

## 📝 Plantilla Básica de Componente Livewire

### Clase PHP
```php
<?php

namespace App\Livewire\Customer;

use Livewire\Component;
use App\Models\Customer;

class CreateCustomer extends Component
{
    // Propiedades del formulario
    public $name = '';
    public $email = '';
    public $phone = '';

    // Reglas de validación
    protected $rules = [
        'name' => 'required|min:3',
        'email' => 'required|email|unique:customers',
        'phone' => 'required|min:10'
    ];

    // Método para guardar
    public function save()
    {
        $this->validate();

        Customer::create([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone
        ]);

        $this->reset();
        session()->flash('message', 'Cliente creado exitosamente!');
    }

    public function render()
    {
        return view('livewire.customer.create-customer');
    }
}
```

### Vista Blade
```html
<div class="max-w-md mx-auto bg-white p-6 rounded-lg shadow-lg">
    <h2 class="text-2xl font-bold mb-4">Crear Cliente</h2>

    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('message') }}
        </div>
    @endif

    <form wire:submit.prevent="save">
        <!-- Nombre -->
        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2">
                Nombre
            </label>
            <input wire:model="name" type="text"
                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
            @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <!-- Email -->
        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2">
                Email
            </label>
            <input wire:model="email" type="email"
                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
            @error('email') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <!-- Teléfono -->
        <div class="mb-6">
            <label class="block text-gray-700 text-sm font-bold mb-2">
                Teléfono
            </label>
            <input wire:model="phone" type="text"
                   class="w-full px-3 py-2 border rounded-lg focus:outline-none focus:border-blue-500">
            @error('phone') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <!-- Botón -->
        <button type="submit"
                wire:loading.attr="disabled"
                class="w-full bg-blue-500 text-white py-2 px-4 rounded-lg hover:bg-blue-600 disabled:opacity-50">
            <span wire:loading.remove>Guardar Cliente</span>
            <span wire:loading>Guardando...</span>
        </button>
    </form>
</div>
```

## 🔄 Cómo Usar los Componentes

### En Rutas
```php
// routes/web.php
Route::get('/customers/create', App\Livewire\Customer\CreateCustomer::class)
    ->name('customers.create');
```

### En Vistas Blade
```html
<!-- Incluir el componente -->
@livewire('customer.create-customer')

<!-- O con parámetros -->
@livewire('customer.edit-customer', ['customerId' => $customer->id])
```

### Con Volt (Alternativa más simple)
```php
// routes/web.php
use function Laravel\Folio\{middleware, name};
use function Livewire\Volt\{route};

Volt::route('/customers/create', 'customer.create-customer')
    ->name('customers.create');
```

## ⚡ Comandos Útiles Adicionales

```bash
# Ver todos los componentes Livewire
php artisan livewire:list

# Publicar assets de Livewire
php artisan livewire:publish

# Crear componente con namespace específico
php artisan make:livewire "Admin\Users\CreateUser"

# Eliminar componente (manual)
# Debes borrar manualmente los archivos PHP y Blade
```

## ⚡ Componentes Livewire Disponibles

Ya tenemos componentes **Livewire reactivos** listos para usar:

### Selects Reactivos Disponibles

```bash
# Estos ya están creados y funcionando
app/Livewire/Selects/CitySelect.php
app/Livewire/Selects/CountrySelect.php
app/Livewire/Selects/TypeIdentificationSelect.php
```

### Uso de los Componentes Livewire

```html
<!-- En cualquier vista Livewire -->
<form wire:submit.prevent="save">
    <!-- Tipo de Identificación -->
    @livewire('selects.type-identification-select', [
        'typeIdentificationId' => $typeIdentificationId,
        'name' => 'typeIdentificationId'
    ])

    <!-- País (se comunica con ciudad automáticamente) -->
    @livewire('selects.country-select', [
        'countryId' => $countryId,
        'name' => 'countryId'
    ])

    <!-- Ciudad (se actualiza cuando cambia el país) -->
    @livewire('selects.city-select', [
        'cityId' => $cityId,
        'countryId' => $countryId,
        'name' => 'cityId'
    ])

    <button type="submit">Guardar</button>
</form>
```

### Características Reactivas ⚡

✅ **Country Select**:
- Búsqueda en tiempo real
- Loading states
- Envía evento `country-changed`

✅ **City Select**:
- Se actualiza automáticamente cuando cambia el país
- Búsqueda en tiempo real cuando hay +20 ciudades
- Loading states
- Filtra por país seleccionado

✅ **Type Identification Select**:
- Simple y rápido
- Envía evento `type-identification-changed`

### Eventos Disponibles

```php
// En tu componente Livewire principal
class CreateCustomer extends Component
{
    public $countryId = 48;
    public $cityId = '';
    public $typeIdentificationId = '';

    #[On('country-changed')]
    public function updateCountry($countryId)
    {
        $this->countryId = $countryId;
        $this->cityId = ''; // Reset city
    }

    #[On('city-changed')]
    public function updateCity($cityId)
    {
        $this->cityId = $cityId;
    }

    #[On('type-identification-changed')]
    public function updateTypeIdentification($typeId)
    {
        $this->typeIdentificationId = $typeId;
        // Lógica adicional como mostrar/ocultar campo DV
    }
}
```

## 🎨 Integración con Formularios Existentes

### Reemplazar Selects Antiguos

```html
<!-- ANTES (Blade + Alpine) -->
<x-selects.country wire:model="countryId" />

<!-- AHORA (Livewire Reactivo) -->
@livewire('selects.country-select', [
    'countryId' => $countryId,
    'name' => 'countryId'
])
```

## 🚀 Flujo de Trabajo Recomendado

1. **Crear el componente:**
   ```bash
   php artisan make:livewire customer/create-customer
   ```

2. **Definir propiedades y validaciones** en la clase PHP

3. **Crear el formulario** en la vista Blade

4. **Integrar componentes reutilizables** (selects, etc.)

5. **Agregar la ruta** en `web.php`

6. **Probar el componente**

## 💡 Tips para el Equipo

- **Usar nombres descriptivos**: `customer/create-customer` en lugar de solo `customer`
- **Organizar por módulos**: Agrupar componentes relacionados en carpetas
- **Reutilizar componentes**: Usar nuestros `<x-selects.*>` siempre que sea posible
- **Validación consistente**: Definir reglas claras en cada componente
- **Estados de carga**: Siempre usar `wire:loading` para mejor UX

## 🔧 Comandos de Desarrollo

```bash
# Limpiar cache de vistas
php artisan view:clear

# Recompilar assets
npm run dev

# Ejecutar tests
php artisan test
```

---

**¡Con esta guía tu equipo puede crear componentes Livewire fácilmente y mantener consistencia en el proyecto!** 🎉