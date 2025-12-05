# Guía de Estandarización Laravel - Timestamps y Nomenclatura

## 🎯 Objetivo

Estandarizar tu proyecto para usar las convenciones de Laravel en lugar de nomenclatura personalizada.

## 📋 Problemas Identificados

### 1. **Timestamps Inconsistentes**
```php
// ❌ Tu proyecto (inconsistente):
$table->dateTime('createdAt')->default('now()');
$table->dateTime('updatedAt')->nullable();
$table->dateTime('deletedAt')->nullable();

// ✅ Laravel estándar:
$table->timestamps();       // Crea created_at y updated_at
$table->softDeletes();      // Crea deleted_at
```

### 2. **Nomenclatura de Campos**
```php
// ❌ Tu proyecto:
'businessName', 'firstName', 'typeIdentificationId'

// ✅ Laravel estándar:
'business_name', 'first_name', 'type_identification_id'
```

### 3. **Primary Keys**
```php
// ❌ Tu proyecto:
$table->integer('id', true);

// ✅ Laravel estándar:
$table->id();
```

## 🛠 Comando de Estandarización

He creado un comando personalizado que automatiza las correcciones:

### Uso del Comando

```bash
# Ver qué cambios se harían (sin aplicar)
php artisan migrate:standardize --dry-run

# Aplicar cambios reales
php artisan migrate:standardize
```

### Cambios que Automatiza

1. **Timestamps:**
   - `createdAt` → `created_at`
   - `updatedAt` → `updated_at`
   - `deletedAt` → `deleted_at`
   - Reemplaza definiciones manuales con `$table->timestamps()` y `$table->softDeletes()`

2. **Campos Comunes:**
   - `businessName` → `business_name`
   - `firstName` → `first_name`
   - `typeIdentificationId` → `type_identification_id`
   - Y 20+ campos más

3. **Primary Keys:**
   - `$table->integer('id', true)` → `$table->id()`

## 📝 Ejemplo de Transformación

### Antes (vnt_terms):
```php
Schema::create('vnt_terms', function (Blueprint $table) {
    $table->integer('id', true);
    $table->string('name', 50);
    $table->integer('days');
    $table->dateTime('createdAt')->default('now()');
    $table->dateTime('updatedAt')->nullable();
    $table->dateTime('deletedAt')->nullable();
});
```

### Después (estandarizado):
```php
Schema::create('vnt_terms', function (Blueprint $table) {
    $table->id();
    $table->string('name', 50);
    $table->integer('days');
    $table->timestamps();
    $table->softDeletes();
});
```

## 🔄 Actualización de Modelos

Después de estandarizar migraciones, actualiza los modelos:

### Ejemplo: VntWarehouse

#### Antes:
```php
class VntWarehouse extends Model
{
    protected $connection = 'central';

    // Timestamps personalizados
    protected $fillable = [
        'companyId',
        'businessName',
        'firstName',
        // ...
    ];

    protected function casts(): array
    {
        return [
            'createdAt' => 'datetime',
            'updatedAt' => 'datetime',
            'deletedAt' => 'datetime',
        ];
    }
}
```

#### Después (estandarizado):
```php
class VntWarehouse extends Model
{
    use SoftDeletes; // Para deleted_at

    protected $connection = 'central';

    // Laravel automáticamente maneja created_at, updated_at
    protected $fillable = [
        'company_id',
        'business_name',
        'first_name',
        // ...
    ];

    protected function casts(): array
    {
        return [
            // Laravel maneja timestamps automáticamente
            'id' => 'integer',
            'company_id' => 'integer',
            // ...
        ];
    }
}
```

## 🗂️ Migraciones que Necesitan Estandarización

Basándome en tu proyecto, estas migraciones tienen nomenclatura inconsistente:

### ✅ **Ya Estandarizadas:**
- `create_vnt_companies_table.php` (usa `created_at`, `updated_at`)
- `create_users_table.php` (estándar Laravel)

### ❌ **Necesitan Estandarización:**
- `create_vnt_terms_table.php` (usa `createdAt`, `updatedAt`)
- `create_vnt_contacts_table.php` (probable)
- `create_vnt_warehouses_table.php` (probable)
- `create_cfg_positions_table.php` (probable)

## 🚀 Plan de Ejecución

### Paso 1: Backup
```bash
# Crear backup de las migraciones
cp -r database/migrations database/migrations_backup
```

### Paso 2: Análisis
```bash
# Ver qué cambios se harían
php artisan migrate:standardize --dry-run
```

### Paso 3: Aplicar Cambios
```bash
# Aplicar estandarización
php artisan migrate:standardize
```

### Paso 4: Actualizar Modelos
Revisar y actualizar modelos uno por uno para usar nomenclatura estándar.

### Paso 5: Actualizar Código de Aplicación
```php
// Cambiar en controladores, requests, etc:
// ❌ Antes:
'businessName' => $request->businessName,

// ✅ Después:
'business_name' => $request->business_name,
```

### Paso 6: Testing
```bash
# Probar migraciones en base limpia
php artisan migrate:fresh
```

## ⚡ Beneficios de la Estandarización

### 1. **Convenciones Laravel**
```php
// Automático con Laravel:
$model->created_at  // Funciona automáticamente
$model->updated_at  // Funciona automáticamente
$model->deleted_at  // Con SoftDeletes trait
```

### 2. **Compatibilidad con Paquetes**
```php
// Muchos paquetes esperan nomenclatura estándar:
$model->whereDate('created_at', today())
$model->onlyTrashed() // SoftDeletes
```

### 3. **Mejor Integración con InfyOm**
```php
// InfyOm funciona mejor con nombres estándar
php artisan infyom:scaffold VntTerm --fromTable
```

### 4. **IDE Support**
```php
// Mejor autocompletado y sugerencias
$company->created_at->format('Y-m-d')
```

## 🔧 Personalización del Comando

Si necesitas mapeos adicionales, edita el archivo:
`app/Console/Commands/StandardizeMigrations.php`

```php
// Agregar más mapeos en $fieldMappings:
$fieldMappings = [
    'tuCampoPersonalizado' => 'tu_campo_personalizado',
    // ...
];
```

## ⚠️ Consideraciones Importantes

### 1. **Backup Obligatorio**
Siempre haz backup antes de ejecutar cambios masivos.

### 2. **Entorno de Prueba**
Prueba primero en un entorno de desarrollo.

### 3. **Código Existente**
Revisa y actualiza todo el código que use los nombres antiguos.

### 4. **Base de Datos Existente**
Si ya tienes datos en producción, necesitarás migraciones para renombrar columnas:

```php
Schema::table('vnt_companies', function (Blueprint $table) {
    $table->renameColumn('businessName', 'business_name');
    $table->renameColumn('firstName', 'first_name');
});
```

## 📋 Checklist Post-Estandarización

- [ ] Migraciones estandarizadas
- [ ] Modelos actualizados con SoftDeletes trait
- [ ] Fillable arrays actualizados
- [ ] Controladores actualizados
- [ ] Requests de validación actualizados
- [ ] Vistas Blade actualizadas
- [ ] Tests actualizados
- [ ] Pruebas de migración exitosas

## 🎯 Resultado Final

Después de la estandarización tendrás:

✅ **Nomenclatura consistente** con Laravel
✅ **Mejor compatibilidad** con paquetes
✅ **Código más mantenible**
✅ **InfyOm funcionando** correctamente
✅ **Mejor experiencia** de desarrollo

---

## 🚀 Ejecutar Ahora

```bash
# 1. Hacer backup
cp -r database/migrations database/migrations_backup

# 2. Ver cambios propuestos
php artisan migrate:standardize --dry-run

# 3. Aplicar cambios
php artisan migrate:standardize
```