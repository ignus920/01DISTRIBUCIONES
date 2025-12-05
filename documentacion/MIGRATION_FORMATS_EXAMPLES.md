# Ejemplos de Formatos de Campo Migration

## 🎯 Descripción

El campo `migration` en la tabla `vnt_moduls` determina qué migraciones ejecutar cuando se instala un módulo. Este documento muestra todos los formatos soportados con ejemplos reales.

## 📁 Estructura Base de Migraciones

```
database/migrations/tenants/
├── base/                           # Siempre ejecutadas
├── ventas/                         # Módulo de Ventas
├── inventario/                     # Módulo de Inventario
│   ├── productos/
│   ├── stock/
│   └── categorias/
├── produccion/                     # Módulo de Producción
├── pos/                           # Módulo POS
├── marketing/                     # Módulo Marketing
├── crm/                          # Módulo CRM
├── contabilidad/                 # Módulo Contabilidad
├── reportes/                     # Módulo Reportes
└── ecommerce/                    # Módulo E-commerce
```

## 🔧 Formatos Soportados

### 1. Formato Wildcard (/*) - Carpeta Completa

**Configuración:**
```sql
UPDATE vnt_moduls SET migration = 'ventas/*' WHERE name = 'Ventas';
UPDATE vnt_moduls SET migration = 'pos/*' WHERE name = 'POS';
UPDATE vnt_moduls SET migration = 'crm/*' WHERE name = 'CRM';
```

**Resultado:** Ejecuta todas las migraciones `.php` en la carpeta especificada.

### 2. Formato JSON Array - Múltiples Rutas Específicas

**Configuración:**
```sql
UPDATE vnt_moduls
SET migration = '["inventario/productos", "inventario/stock", "inventario/categorias"]'
WHERE name = 'Inventario';

UPDATE vnt_moduls
SET migration = '["produccion/ordenes", "produccion/materiales", "produccion/reportes"]'
WHERE name = 'Producción';

UPDATE vnt_moduls
SET migration = '["marketing/campanas", "marketing/segmentos", "marketing/analytics"]'
WHERE name = 'Marketing';
```

**Resultado:** Ejecuta migraciones específicas en el orden indicado.

### 3. Formato Separado por Comas

**Configuración:**
```sql
UPDATE vnt_moduls
SET migration = 'contabilidad/cuentas, contabilidad/asientos, contabilidad/balances'
WHERE name = 'Contabilidad';

UPDATE vnt_moduls
SET migration = 'ecommerce/tienda, ecommerce/carrito, ecommerce/pagos'
WHERE name = 'E-commerce';

UPDATE vnt_moduls
SET migration = 'reportes/ventas, reportes/inventario, reportes/financieros'
WHERE name = 'Reportes';
```

**Resultado:** Ejecuta múltiples rutas separadas por comas.

### 4. Formato Ruta Completa

**Configuración:**
```sql
UPDATE vnt_moduls
SET migration = 'database/migrations/tenants/usuarios/permisos'
WHERE name = 'Usuarios y Permisos';

UPDATE vnt_moduls
SET migration = 'database/migrations/tenants/configuracion/empresa'
WHERE name = 'Configuración';
```

**Resultado:** Ejecuta migraciones en ruta absoluta especificada.

## 📊 Ejemplos por Industria

### 🏪 Tienda de Retail

```sql
-- Módulos básicos para retail
UPDATE vnt_moduls SET migration = 'ventas/*' WHERE name = 'Ventas';
UPDATE vnt_moduls SET migration = 'inventario/*' WHERE name = 'Inventario';
UPDATE vnt_moduls SET migration = 'pos/*' WHERE name = 'POS';
UPDATE vnt_moduls SET migration = 'crm/clientes, crm/fidelizacion' WHERE name = 'CRM Básico';

-- Selección para tenant retail: [1, 2, 3, 4]
```

### 🍕 Restaurante

```sql
-- Módulos específicos para restaurantes
UPDATE vnt_moduls SET migration = 'ventas/*' WHERE name = 'Ventas';
UPDATE vnt_moduls SET migration = 'inventario/ingredientes, inventario/recetas' WHERE name = 'Inventario Cocina';
UPDATE vnt_moduls SET migration = 'pos/*' WHERE name = 'POS';
UPDATE vnt_moduls SET migration = 'cocina/*' WHERE name = 'Gestión de Cocina';
UPDATE vnt_moduls SET migration = 'delivery/*' WHERE name = 'Delivery';

-- Selección para tenant restaurante: [1, 2, 3, 5, 6]
```

### 🏭 Manufactura

```sql
-- Módulos para manufactura
UPDATE vnt_moduls SET migration = '["inventario/materias", "inventario/productos", "inventario/herramientas"]' WHERE name = 'Inventario Industrial';
UPDATE vnt_moduls SET migration = 'produccion/*' WHERE name = 'Producción';
UPDATE vnt_moduls SET migration = 'calidad/*' WHERE name = 'Control de Calidad';
UPDATE vnt_moduls SET migration = 'mantenimiento/*' WHERE name = 'Mantenimiento';

-- Selección para tenant manufactura: [7, 3, 8, 9]
```

### 📈 Agencia de Marketing

```sql
-- Módulos para agencia
UPDATE vnt_moduls SET migration = 'proyectos/*' WHERE name = 'Gestión de Proyectos';
UPDATE vnt_moduls SET migration = 'marketing/*' WHERE name = 'Marketing';
UPDATE vnt_moduls SET migration = 'crm/clientes, crm/leads, crm/propuestas' WHERE name = 'CRM Avanzado';
UPDATE vnt_moduls SET migration = 'facturacion/*' WHERE name = 'Facturación';

-- Selección para tenant agencia: [10, 4, 11, 12]
```

## 🎯 Casos Complejos

### Módulo con Dependencias

```sql
-- Módulo E-commerce que depende de Inventario y Ventas
UPDATE vnt_moduls
SET migration = '["inventario/productos", "ventas/clientes", "ecommerce/tienda", "ecommerce/carrito", "ecommerce/pagos"]'
WHERE name = 'E-commerce Completo';
```

### Módulo de Configuración Regional

```sql
-- Configuración específica por país
UPDATE vnt_moduls
SET migration = 'localizacion/colombia, impuestos/colombia, bancos/colombia'
WHERE name = 'Localización Colombia';

UPDATE vnt_moduls
SET migration = 'localizacion/mexico, impuestos/mexico, bancos/mexico'
WHERE name = 'Localización México';
```

### Módulo Modular por Características

```sql
-- CRM con características opcionales
UPDATE vnt_moduls
SET migration = '["crm/base", "crm/contactos"]'
WHERE name = 'CRM Básico';

UPDATE vnt_moduls
SET migration = '["crm/base", "crm/contactos", "crm/oportunidades", "crm/campanas"]'
WHERE name = 'CRM Avanzado';

UPDATE vnt_moduls
SET migration = '["crm/base", "crm/contactos", "crm/oportunidades", "crm/campanas", "crm/automation", "crm/analytics"]'
WHERE name = 'CRM Enterprise';
```

## 🔄 Migración de Configuraciones Existentes

### Script para Actualizar Módulos Existentes

```sql
-- Actualizar módulos que tienen nombres pero no configuración migration
UPDATE vnt_moduls SET
    migration = CASE
        WHEN name LIKE '%Ventas%' THEN 'ventas/*'
        WHEN name LIKE '%Inventario%' THEN 'inventario/*'
        WHEN name LIKE '%POS%' THEN 'pos/*'
        WHEN name LIKE '%Producción%' THEN 'produccion/*'
        WHEN name LIKE '%Marketing%' THEN 'marketing/*'
        WHEN name LIKE '%CRM%' THEN 'crm/*'
        WHEN name LIKE '%Contabilidad%' THEN 'contabilidad/*'
        WHEN name LIKE '%Reportes%' THEN 'reportes/*'
        ELSE 'base/*'
    END
WHERE migration IS NULL OR migration = '';
```

## 🧪 Testing de Configuraciones

### Función para Probar Configuraciones

```php
<?php
// Script para probar configuraciones de migration
function testMigrationConfig($moduleId) {
    $module = VntModul::find($moduleId);
    $flexibleService = new FlexibleTenantService();

    // Crear tenant de prueba
    $testTenant = $flexibleService->createBaseTenant([
        'name' => 'Test Tenant',
        'email' => 'test@example.com'
    ], 1);

    try {
        // Probar configuración
        $flexibleService->runModuleMigrations($testTenant, $module->toArray());
        echo "✅ Configuración válida para módulo: {$module->name}\n";

        // Verificar tablas creadas
        $tables = DB::select("SHOW TABLES IN {$testTenant->db_name}");
        echo "📊 Tablas creadas: " . count($tables) . "\n";

    } catch (Exception $e) {
        echo "❌ Error en configuración: {$e->getMessage()}\n";
    } finally {
        // Limpiar
        DB::statement("DROP DATABASE IF EXISTS {$testTenant->db_name}");
        $testTenant->delete();
    }
}

// Probar todos los módulos
$modules = VntModul::where('status', 1)->get();
foreach ($modules as $module) {
    testMigrationConfig($module->id);
}
```

## 📝 Validaciones Recomendadas

### Validación de Formato

```php
function validateMigrationFormat($migrationConfig) {
    // JSON válido
    if (Str::startsWith($migrationConfig, '[') || Str::startsWith($migrationConfig, '{')) {
        $decoded = json_decode($migrationConfig, true);
        return json_last_error() === JSON_ERROR_NONE && is_array($decoded);
    }

    // Formato simple válido
    return preg_match('/^[a-zA-Z0-9_\/\*\,\s\-]+$/', $migrationConfig);
}
```

### Validación de Rutas

```php
function validateMigrationPaths($migrationConfig) {
    $paths = parseMigrationConfig($migrationConfig);
    $validPaths = [];

    foreach ($paths as $path) {
        $fullPath = base_path("database/migrations/tenants/{$path}");
        if (is_dir($fullPath) || is_file($fullPath . '.php')) {
            $validPaths[] = $path;
        } else {
            Log::warning("Ruta de migración no encontrada: {$fullPath}");
        }
    }

    return $validPaths;
}
```

## 🏁 Conclusión

El sistema de formatos flexibles permite:

1. **Configuración simple** con wildcards (`/*`)
2. **Control granular** con JSON arrays
3. **Configuración legible** con comas
4. **Rutas absolutas** cuando sea necesario

Esta flexibilidad permite adaptar cada módulo a sus necesidades específicas de migración mientras mantiene un sistema coherente y mantenible.