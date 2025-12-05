# 📋 Sistema de Configuración por Empresa - Documentación

## 🎯 Propósito

Este sistema permite que los formularios CRUD se comporten dinámicamente según la configuración de cada empresa y su plan contratado (Básico, Avanzado, Superior). Los campos se muestran u ocultan automáticamente según la configuración almacenada en la base de datos central.

---

## 🏗️ Arquitectura del Sistema

### 1. **CompanyConfigurationService**
📁 `app/Services/Configuration/CompanyConfigurationService.php`

**Responsabilidad:** Servicio principal que maneja la lectura de configuraciones desde la base de datos central.

**Características:**
- ✅ Caché automático (1 hora TTL)
- ✅ Consultas a base de datos central `rap`
- ✅ Separación de caché por tenant
- ✅ Métodos para validar campos específicos

### 2. **HasCompanyConfiguration**
📁 `app/Traits/HasCompanyConfiguration.php`

**Responsabilidad:** Trait reutilizable que facilita el uso del sistema en cualquier componente.

**Características:**
- ✅ Inicialización automática de empresa y plan
- ✅ Métodos helper fáciles de usar
- ✅ Caché a nivel de instancia para rendimiento
- ✅ Validación dinámica de campos

### 3. **ConfigurationServiceProvider**
📁 `app/Providers/ConfigurationServiceProvider.php`

**Responsabilidad:** Registra el servicio en el contenedor de Laravel.

**Registrado en:** `bootstrap/providers.php`

---

## 🔧 Estructura de Base de Datos

El sistema lee configuraciones de las siguientes tablas en la base de datos central **`rap`**:

```sql
-- Módulos disponibles (VENTAS, INVENTARIO, etc.)
rap.vnt_moduls

-- Planes de empresa (Básico, Avanzado, Superior)
rap.vnt_plains

-- Opciones/parámetros por módulo
rap.vnt_options_params

-- Relación opciones-planes
rap.vnt_options_plains

-- Configuración específica por empresa
rap.vnt_company_options

-- Empresas
rap.vnt_companies
```

---

## 🚀 Cómo Usar en tus Componentes

### Paso 1: Agregar el Trait

```php
<?php

namespace App\Livewire\TuModulo;

use App\Traits\HasCompanyConfiguration;
use Livewire\Component;

class TuFormulario extends Component
{
    use HasCompanyConfiguration;

    // Propiedades del formulario
    public string $nombre = '';
    public string $email = '';
    public string $telefono = '';

    // Configurar el módulo que vas a validar
    protected string $moduleName = 'VENTAS'; // O 'INVENTARIO', 'CAJA', etc.

    public function mount()
    {
        // OBLIGATORIO: Inicializar el sistema de configuración
        $this->initializeCompanyConfiguration();
    }
}
```

### Paso 2: Validación Dinámica

```php
public function save()
{
    // Reglas base para todos los campos
    $baseRules = [
        'nombre' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email'],
        'telefono' => ['nullable', 'string'],
        'direccion' => ['nullable', 'string'],
    ];

    // 🎯 Filtrar reglas según configuración de empresa
    $validatedRules = $this->validateFormFields($this->moduleName, $baseRules);

    // Validar solo campos que deben mostrarse
    $this->validate($validatedRules);

    // 🎯 Filtrar datos según configuración
    $dataToSave = $this->filterDataByConfiguration($this->moduleName, [
        'nombre' => $this->nombre,
        'email' => $this->email,
        'telefono' => $this->telefono,
        'direccion' => $this->direccion,
    ]);

    // Guardar solo los campos configurados
    MiModelo::create($dataToSave);
}
```

### Paso 3: Vista Dinámica

```blade
{{-- resources/views/livewire/tu-modulo/tu-formulario.blade.php --}}

<form wire:submit.prevent="save" class="space-y-4">

    {{-- Campo Nombre - Solo se muestra si está configurado --}}
    @if($this->shouldShowField($moduleName, 'nombre'))
        <div class="form-group">
            <label for="nombre">Nombre *</label>
            <input type="text" wire:model="nombre" id="nombre" required>
            @error('nombre')
                <span class="error">{{ $message }}</span>
            @enderror
        </div>
    @endif

    {{-- Campo Email --}}
    @if($this->shouldShowField($moduleName, 'email'))
        <div class="form-group">
            <label for="email">Email</label>
            <input type="email" wire:model="email" id="email">
            @error('email')
                <span class="error">{{ $message }}</span>
            @enderror
        </div>
    @endif

    {{-- Campo Teléfono --}}
    @if($this->shouldShowField($moduleName, 'telefono'))
        <div class="form-group">
            <label for="telefono">Teléfono</label>
            <input type="tel" wire:model="telefono" id="telefono">
        </div>
    @endif

    <button type="submit">Guardar</button>
</form>
```

---

## 📚 Métodos Disponibles

### 🔍 Verificación de Campos

```php
// ¿Debe mostrarse este campo?
$this->shouldShowField('VENTAS', 'nombre') // true/false

// ¿Puede editarse este campo?
$this->canEditField('nombre') // true/false
```

### 📊 Obtener Configuraciones

```php
// Obtener valor específico de configuración
$valor = $this->getConfigValue('VENTAS', 'limite_credito', 1000);

// Obtener toda la configuración del módulo
$config = $this->getModuleConfig('VENTAS');

// Obtener etiqueta personalizada
$label = $this->getFieldLabel('nombre'); // Retorna etiqueta o 'Nombre'
```

### 🎛️ Utilidades de Formulario

```php
// Validar solo campos configurados
$rules = $this->validateFormFields('VENTAS', $allRules);

// Filtrar datos según configuración
$data = $this->filterDataByConfiguration('VENTAS', $allData);
```

---

## 🎨 Ejemplo Completo

```php
<?php

namespace App\Livewire\Ventas;

use App\Traits\HasCompanyConfiguration;
use App\Models\Cliente;
use Livewire\Component;

class ClienteForm extends Component
{
    use HasCompanyConfiguration;

    public string $nombre = '';
    public string $email = '';
    public string $telefono = '';
    public string $direccion = '';
    public bool $activo = true;

    protected string $moduleName = 'VENTAS';

    public function mount()
    {
        $this->initializeCompanyConfiguration();
    }

    public function getVisibleFields(): array
    {
        $allFields = ['nombre', 'email', 'telefono', 'direccion', 'activo'];
        $visibleFields = [];

        foreach ($allFields as $field) {
            if ($this->shouldShowField($this->moduleName, $field)) {
                $visibleFields[] = $field;
            }
        }

        return $visibleFields;
    }

    public function save()
    {
        $baseRules = [
            'nombre' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:clientes'],
            'telefono' => ['nullable', 'string', 'max:20'],
            'direccion' => ['nullable', 'string'],
            'activo' => ['boolean'],
        ];

        // Solo validar campos visibles
        $validatedRules = $this->validateFormFields($this->moduleName, $baseRules);
        $this->validate($validatedRules);

        // Solo guardar campos configurados
        $dataToSave = $this->filterDataByConfiguration($this->moduleName, [
            'nombre' => $this->nombre,
            'email' => $this->email,
            'telefono' => $this->telefono,
            'direccion' => $this->direccion,
            'activo' => $this->activo,
        ]);

        Cliente::create($dataToSave);

        session()->flash('message', 'Cliente creado exitosamente.');
        $this->reset();
    }

    public function render()
    {
        return view('livewire.ventas.cliente-form', [
            'visibleFields' => $this->getVisibleFields(),
        ]);
    }
}
```

---

## ⚙️ Configuración en Base de Datos

### Para Activar un Campo en un Módulo:

```sql
-- 1. Crear opción en vnt_options_params
INSERT INTO rap.vnt_options_params (name, modul_id, description) VALUES
('nombre', 1, 'Campo nombre en formularios');

-- 2. Relacionar con plan en vnt_options_plains
INSERT INTO rap.vnt_options_plains (option_id, plain_id) VALUES
(LAST_INSERT_ID(), 2); -- Plan Avanzado

-- 3. Configurar para empresa específica
INSERT INTO rap.vnt_company_options (company_id, option_id, value) VALUES
(8, LAST_INSERT_ID(), 1); -- Activado para empresa ID 8
```

---

## 🔄 Caché y Rendimiento

- **Caché automático:** 1 hora TTL por defecto
- **Separación por tenant:** Cada tenant tiene su propio caché
- **Limpieza manual:** `$this->clearConfigurationCache()`
- **Precarga:** Se precargan configuraciones comunes automáticamente

---

## 🚨 Errores Comunes y Soluciones

### ❌ Error: "currentCompanyId is null"
```php
// SOLUCIÓN: Verificar que se inicialice correctamente
public function mount()
{
    $this->initializeCompanyConfiguration(); // ⚠️ OBLIGATORIO
}
```

### ❌ Error: "Cannot use object as array"
```php
// INCORRECTO:
if ($option['nombre'] === 'campo')

// CORRECTO:
if ($option->nombre === 'campo')
```

### ❌ Campos no se muestran
- ✅ Verificar que existe configuración en `vnt_company_options`
- ✅ Verificar que el módulo existe en `vnt_moduls`
- ✅ Verificar que la opción está relacionada con el plan en `vnt_options_plains`

---

## 📋 Lista de Verificación para Implementar

- [ ] Agregar `use HasCompanyConfiguration;` al componente
- [ ] Llamar `$this->initializeCompanyConfiguration();` en `mount()`
- [ ] Definir `$moduleName` con el nombre correcto del módulo
- [ ] Usar `@if($this->shouldShowField())` en la vista
- [ ] Implementar validación dinámica con `validateFormFields()`
- [ ] Filtrar datos con `filterDataByConfiguration()`
- [ ] Verificar configuración en base de datos

---

## 🎯 Beneficios del Sistema

1. **Reutilizable:** Un trait que todos pueden usar
2. **Performante:** Caché automático y precarga
3. **Flexible:** Fácil configuración por empresa y plan
4. **Mantenible:** Código centralizado y estructurado
5. **Escalable:** Funciona con cualquier módulo nuevo

---

## 📞 Soporte

Si tienes dudas sobre la implementación:
1. Revisa los logs en `storage/logs/laravel.log` (buscar "DEBUG")
2. Verifica la configuración en las tablas de la base de datos `rap`
3. Prueba con el ejemplo en `/ejemplo-configuracion`

**¡Happy coding!** 🚀