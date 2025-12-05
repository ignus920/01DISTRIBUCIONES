# 🔋 Guía de Livewire Volt - Tienda Multitenancy

## 🎯 ¿Qué es Volt?

**Volt** es una extensión oficial de Livewire que permite escribir componentes **en un solo archivo** en lugar de separar la clase PHP y la vista Blade. Es una forma moderna y simplificada de crear componentes Livewire.

## 📊 Comparación: Livewire Tradicional vs Volt

### ❌ Livewire Tradicional (2 archivos)

#### Archivo 1: `app/Livewire/CreateCustomer.php`
```php
<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Customer;

class CreateCustomer extends Component
{
    public $name = '';
    public $email = '';
    public $phone = '';

    protected $rules = [
        'name' => 'required|min:3',
        'email' => 'required|email',
        'phone' => 'required'
    ];

    public function save()
    {
        $this->validate();

        Customer::create([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone
        ]);

        $this->reset();
        session()->flash('message', 'Cliente creado!');
    }

    public function render()
    {
        return view('livewire.create-customer');
    }
}
```

#### Archivo 2: `resources/views/livewire/create-customer.blade.php`
```html
<div class="max-w-md mx-auto bg-white p-6 rounded-lg shadow-lg">
    <h2 class="text-2xl font-bold mb-4">Crear Cliente</h2>

    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('message') }}
        </div>
    @endif

    <form wire:submit.prevent="save">
        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2">Nombre</label>
            <input wire:model="name" type="text" class="w-full px-3 py-2 border rounded">
            @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2">Email</label>
            <input wire:model="email" type="email" class="w-full px-3 py-2 border rounded">
            @error('email') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="mb-6">
            <label class="block text-gray-700 text-sm font-bold mb-2">Teléfono</label>
            <input wire:model="phone" type="text" class="w-full px-3 py-2 border rounded">
            @error('phone') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <button type="submit" wire:loading.attr="disabled"
                class="w-full bg-blue-500 text-white py-2 px-4 rounded hover:bg-blue-600">
            <span wire:loading.remove>Guardar Cliente</span>
            <span wire:loading>Guardando...</span>
        </button>
    </form>
</div>
```

### ✅ Volt (1 solo archivo)

#### Archivo único: `resources/views/livewire/create-customer.blade.php`
```php
<?php

use Livewire\Volt\Component;
use App\Models\Customer;

new class extends Component {
    public $name = '';
    public $email = '';
    public $phone = '';

    protected $rules = [
        'name' => 'required|min:3',
        'email' => 'required|email',
        'phone' => 'required'
    ];

    public function save()
    {
        $this->validate();

        Customer::create([
            'name' => $this->name,
            'email' => $this->email,
            'phone' => $this->phone
        ]);

        $this->reset();
        session()->flash('message', 'Cliente creado!');
    }
}; ?>

<div class="max-w-md mx-auto bg-white p-6 rounded-lg shadow-lg">
    <h2 class="text-2xl font-bold mb-4">Crear Cliente</h2>

    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('message') }}
        </div>
    @endif

    <form wire:submit.prevent="save">
        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2">Nombre</label>
            <input wire:model="name" type="text" class="w-full px-3 py-2 border rounded">
            @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2">Email</label>
            <input wire:model="email" type="email" class="w-full px-3 py-2 border rounded">
            @error('email') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <div class="mb-6">
            <label class="block text-gray-700 text-sm font-bold mb-2">Teléfono</label>
            <input wire:model="phone" type="text" class="w-full px-3 py-2 border rounded">
            @error('phone') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
        </div>

        <button type="submit" wire:loading.attr="disabled"
                class="w-full bg-blue-500 text-white py-2 px-4 rounded hover:bg-blue-600">
            <span wire:loading.remove>Guardar Cliente</span>
            <span wire:loading>Guardando...</span>
        </button>
    </form>
</div>
```

## 🎯 Ventajas de Volt

### 1. **📁 Menos archivos**
- ✅ **1 archivo** en lugar de 2
- ✅ Todo está **en un lugar**
- ✅ **Más fácil de mantener**
- ✅ Menos navegación entre archivos

### 2. **🚀 Menos código repetitivo**
- ✅ **No necesitas** método `render()`
- ✅ **No necesitas** importar vistas
- ✅ **Sintaxis más limpia**
- ✅ Menos boilerplate code

### 3. **⚡ Desarrollo más rápido**
- ✅ **Prototipado rápido**
- ✅ **Menos decisiones** sobre estructura
- ✅ **Ideal para formularios** simples
- ✅ **Perfecto para componentes pequeños**

### 4. **🧩 Mejor para equipos pequeños**
- ✅ **Menos complejidad**
- ✅ **Fácil de entender** de un vistazo
- ✅ **Menos archivos** que versionar

## 🤔 ¿Cuándo usar cada uno?

### 📝 **Usar Volt cuando:**

✅ **Componentes simples/medianos**
- Formularios de contacto
- Modales simples
- Widgets de dashboard
- Componentes de una sola responsabilidad

✅ **Desarrollo rápido**
- Prototipos
- MVPs
- Componentes internos
- Herramientas administrativas

✅ **Lógica contenida**
- Todo está relacionado
- No necesitas reutilizar la lógica
- Componente autocontenido

### 🏢 **Usar Livewire tradicional cuando:**

❌ **Componentes complejos**
- Lógica de negocio extensa
- Múltiples responsabilidades
- Interacciones complejas

❌ **Reutilización**
- Necesitas extender la clase
- Múltiples vistas para una clase
- Herencia de componentes

❌ **Equipos grandes**
- Múltiples desarrolladores
- Estructura organizacional estricta
- Tests unitarios extensos

## 🏗️ Estructura de Volt

### Sintaxis básica:
```php
<?php
use Livewire\Volt\Component;

new class extends Component {
    // Propiedades públicas
    public $name = '';

    // Métodos del componente
    public function save() {
        // Lógica aquí
    }

    // Hooks de lifecycle
    public function mount() {
        // Inicialización
    }
}; ?>

<!-- HTML del componente -->
<div>
    <!-- Tu vista aquí -->
</div>
```

### Imports y dependencias:
```php
<?php
use Livewire\Volt\Component;
use App\Models\User;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;

new class extends Component {
    #[Validate('required|min:3')]
    public $name = '';

    #[On('user-updated')]
    public function handleUserUpdate($userId) {
        // Maneja el evento
    }
}; ?>
```

## 🛠️ Comandos de Volt

### Crear componente Volt:
```bash
# Crear componente Volt
php artisan make:volt create-customer

# Crear en subcarpeta
php artisan make:volt customer/create-customer

# Ver todos los componentes Volt
php artisan volt:list
```

### Publicar Volt:
```bash
# Publicar configuración de Volt
php artisan volt:publish
```

## 🎨 Ejemplos en nuestro proyecto

### 1. **simple-setup.blade.php** (Formulario complejo con pasos)
```php
<?php
use Livewire\Volt\Component;
use App\Models\Central\VntCompany;

new class extends Component {
    public int $currentStep = 1;
    public string $identification = '';

    public function nextStep() {
        $this->validateCurrentStep();
        $this->currentStep++;
    }

    #[On('type-identification-changed')]
    public function updateTypeIdentification($typeId) {
        $this->typeIdentificationId = $typeId;
        $this->identification = '';
    }
}; ?>

<div class="wizard-container">
    <!-- Formulario multipaso -->
</div>
```

### 2. **Modal simple** (Ejemplo)
```php
<?php
use Livewire\Volt\Component;

new class extends Component {
    public $showModal = false;
    public $title = '';

    public function openModal() {
        $this->showModal = true;
    }

    public function closeModal() {
        $this->showModal = false;
        $this->reset('title');
    }
}; ?>

<div>
    <button wire:click="openModal">Abrir Modal</button>

    @if($showModal)
        <div class="modal">
            <!-- Contenido del modal -->
        </div>
    @endif
</div>
```

## 🔄 Migración de Livewire a Volt

### Paso 1: Combinar archivos
```php
// Tomar la clase PHP y ponerla al inicio del archivo Blade
<?php
use Livewire\Volt\Component;

new class extends Component {
    // Contenido de tu clase aquí (sin el método render)
}; ?>

<!-- Tu vista Blade aquí -->
```

### Paso 2: Eliminar método render
```php
// ❌ Eliminar esto:
public function render()
{
    return view('livewire.component-name');
}

// ✅ Volt maneja esto automáticamente
```

### Paso 3: Mantener imports
```php
// ✅ Mantener todos los imports necesarios
use App\Models\User;
use Livewire\Attributes\On;
```

## 📚 Mejores prácticas con Volt

### 1. **Organización**
```
resources/views/livewire/
├── customer/
│   ├── create-customer.blade.php (Volt)
│   ├── edit-customer.blade.php (Volt)
│   └── customer-list.blade.php (Volt)
├── product/
│   └── product-form.blade.php (Volt)
└── dashboard/
    └── stats-widget.blade.php (Volt)
```

### 2. **Nomenclatura consistente**
- Usar kebab-case: `create-customer.blade.php`
- Carpetas por módulo: `customer/`, `product/`
- Nombres descriptivos: `user-profile-form.blade.php`

### 3. **Límites de complejidad**
- Máximo 200-300 líneas de PHP
- Si crece mucho, considerar Livewire tradicional
- Mantener una sola responsabilidad

## 🚀 En tu proyecto actual

### Tu `simple-setup.blade.php` usa Volt porque:

✅ **Es perfecto para un formulario de configuración:**
- Todo está en un lugar
- Fácil de entender y mantener
- Lógica de pasos bien definida
- No necesita reutilización externa

✅ **Estructura clara:**
```php
<?php
// Lógica del wizard
use Livewire\Volt\Component;
new class extends Component {
    // Propiedades y métodos
}; ?>

<!-- Vista del formulario multipaso -->
<div class="setup-wizard">
    <!-- Steps y formularios -->
</div>
```

## 🎯 Conclusión

**Volt** es perfecto para:
- 🚀 **Desarrollo rápido**
- 📝 **Formularios y componentes simples**
- 🧩 **Prototipado**
- 🎯 **Componentes autocontenidos**

**Livewire tradicional** es mejor para:
- 🏢 **Aplicaciones complejas**
- 🔄 **Reutilización de código**
- 👥 **Equipos grandes**
- 🧪 **Testing extensivo**

¡Volt hace que Livewire sea aún más fácil y productivo! 🎉

---

**💡 Tip:** Puedes mezclar ambos enfoques en el mismo proyecto. Usa Volt para componentes simples y Livewire tradicional para los complejos.