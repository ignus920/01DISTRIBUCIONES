# Guía Completa del Sistema Modular de Tenants

## 🎯 Descripción General

El sistema modular de tenants permite crear tenants con módulos seleccionados libremente por el usuario, independientemente del tipo de merchant. Utiliza el campo `migration` de la tabla `vnt_moduls` para determinar qué migraciones ejecutar.

## 🏗️ Arquitectura

### Componentes Principales

1. **FlexibleTenantService** - Servicio principal para creación modular
2. **TenantController** - Controlador con endpoints en español
3. **Base de datos modular** - Tabla `tenant_modules` para relaciones
4. **Sistema de migraciones flexible** - Soporte para múltiples formatos

## 📋 Flujo Completo de Creación

### 1. Preparación de Módulos

Los módulos deben tener configurado el campo `migration` en la tabla `vnt_moduls`:

```sql
-- Ejemplos de configuraciones de migración
UPDATE vnt_moduls SET migration = 'ventas/*' WHERE name = 'Ventas';
UPDATE vnt_moduls SET migration = '["inventario/productos", "inventario/stock"]' WHERE name = 'Inventario';
UPDATE vnt_moduls SET migration = 'produccion/ordenes, produccion/materiales' WHERE name = 'Producción';
UPDATE vnt_moduls SET migration = 'database/migrations/tenants/marketing' WHERE name = 'Marketing';
```

### 2. Frontend - Selector de Módulos

```javascript
// Obtener módulos disponibles
async function cargarModulosDisponibles() {
    try {
        const response = await fetch('/modulos-disponibles');
        const data = await response.json();

        if (data.exito) {
            const modulos = data.datos;
            mostrarSelectorModulos(modulos);
        }
    } catch (error) {
        console.error('Error cargando módulos:', error);
    }
}

// Crear tenant con módulos seleccionados
async function crearTenantConModulos(formData) {
    try {
        const response = await fetch('/crear-con-modulos', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                nombre: formData.nombre,
                email: formData.email,
                telefono: formData.telefono,
                direccion: formData.direccion,
                tipo_merchant_id: formData.tipoMerchant,
                modulos_seleccionados: formData.modulosSeleccionados // Array de IDs
            })
        });

        const data = await response.json();

        if (data.exito) {
            console.log('Tenant creado exitosamente:', data.datos.tenant);
            console.log('Módulos instalados:', data.datos.modulos);
        } else {
            console.error('Error:', data.mensaje);
        }
    } catch (error) {
        console.error('Error creando tenant:', error);
    }
}
```

### 3. Backend - Procesamiento

El `FlexibleTenantService` ejecuta estos pasos:

1. **Crear tenant base** - Estructura básica y BD
2. **Ejecutar migraciones base** - Tablas siempre requeridas
3. **Procesar módulos seleccionados** - Parsear campo `migration`
4. **Ejecutar migraciones modulares** - Según configuración
5. **Guardar relaciones** - Tabla `tenant_modules`
6. **Configurar defaults** - Valores iniciales

## 🔧 Formatos Soportados en Campo Migration

### 1. Formato Simple
```sql
migration = 'ventas/*'
```
Ejecuta todas las migraciones en `database/migrations/tenants/ventas/`

### 2. Formato JSON Array
```sql
migration = '["inventario/productos", "inventario/stock", "inventario/categorias"]'
```
Ejecuta migraciones específicas en orden

### 3. Formato Separado por Comas
```sql
migration = 'produccion/ordenes, produccion/materiales, produccion/reportes'
```
Ejecuta múltiples rutas separadas por comas

### 4. Formato Ruta Completa
```sql
migration = 'database/migrations/tenants/marketing/campanas'
```
Ejecuta migraciones en ruta específica completa

## 💾 Estructura de Base de Datos

### Tabla tenant_modules
```sql
CREATE TABLE tenant_modules (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    tenant_id VARCHAR(255) NOT NULL,
    module_id BIGINT UNSIGNED NOT NULL,
    installed_at TIMESTAMP NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,

    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (module_id) REFERENCES vnt_moduls(id) ON DELETE CASCADE,
    UNIQUE KEY unique_tenant_module (tenant_id, module_id)
);
```

## 🎨 Ejemplos de Uso

### Ejemplo 1: Tienda POS
```php
$tenant = $flexibleTenantService->createTenantWithSelectedModules(
    [
        'name' => 'Tienda Central',
        'email' => 'admin@tiendacentral.com',
        'phone' => '+1234567890'
    ],
    1, // Tipo merchant POS
    [1, 2, 3], // Ventas, Inventario, POS
    $usuario
);
```

### Ejemplo 2: Agencia de Publicidad
```php
$tenant = $flexibleTenantService->createTenantWithSelectedModules(
    [
        'name' => 'Agencia Creativa',
        'email' => 'info@agenciacreativa.com',
        'phone' => '+0987654321'
    ],
    2, // Tipo merchant diferente
    [2, 4, 5], // Inventario, Producción, Marketing
    $usuario
);
```

### Ejemplo 3: Agregar Módulo a Tenant Existente
```php
$exito = $flexibleTenantService->addModuleToTenant('tenant-uuid', 6);
if ($exito) {
    echo "Módulo agregado exitosamente";
}
```

## ⚙️ Configuración de Migraciones

### Estructura de Carpetas Recomendada
```
database/migrations/tenants/
├── base/                    # Migraciones siempre ejecutadas
│   ├── create_users_table.php
│   └── create_settings_table.php
├── ventas/                  # Módulo Ventas
│   ├── create_sales_table.php
│   ├── create_customers_table.php
│   └── create_invoices_table.php
├── inventario/              # Módulo Inventario
│   ├── productos/
│   │   └── create_products_table.php
│   ├── stock/
│   │   └── create_stock_table.php
│   └── categorias/
│       └── create_categories_table.php
├── produccion/              # Módulo Producción
│   ├── ordenes/
│   └── materiales/
└── marketing/               # Módulo Marketing
    └── campanas/
```

## 🚀 Mejores Prácticas

### 1. Configuración de Módulos
- **Documentar dependencias** entre módulos
- **Usar nombres descriptivos** en campo migration
- **Probar todas las combinaciones** de módulos
- **Mantener migraciones atómicas** y reversibles

### 2. Manejo de Errores
- **Rollback automático** en caso de error
- **Logs detallados** para debugging
- **Validación previa** de módulos compatibles
- **Cleanup automático** en fallos

### 3. Performance
- **Ejecutar migraciones en background** para tenants grandes
- **Cache de módulos disponibles**
- **Índices apropiados** en tenant_modules
- **Monitoreo de tiempos** de creación

### 4. Seguridad
- **Validar permisos** antes de crear tenants
- **Sanitizar nombres** de bases de datos
- **Logs de auditoría** para cambios de módulos
- **Respaldos automáticos** antes de modificaciones

## 🔍 Debugging y Monitoreo

### Logs Importantes
```php
// En FlexibleTenantService
Log::info('🏗️ Creando tenant con módulos seleccionados', [
    'tenant_name' => $tenantData['name'],
    'selected_modules' => $selectedModuleIds
]);

Log::info('🔧 Ejecutando migraciones del módulo: ' . $module['name'], [
    'module_id' => $module['id'],
    'migration_config' => $module['migration']
]);
```

### Verificación Manual
```sql
-- Verificar módulos de un tenant
SELECT t.name as tenant_name, m.name as module_name, tm.installed_at
FROM tenant_modules tm
JOIN tenants t ON tm.tenant_id = t.id
JOIN vnt_moduls m ON tm.module_id = m.id
WHERE tm.tenant_id = 'tenant-uuid'
AND tm.is_active = 1;

-- Verificar tablas creadas en BD tenant
SHOW TABLES IN tenant_nombre_uuid;
```

## 🎯 Casos de Uso Avanzados

### 1. Migración de Tenants Existentes
```php
// Agregar módulos a tenants creados con sistema anterior
foreach ($tenantsAntiguos as $tenant) {
    $modulosNecesarios = determinarModulosSegunTipo($tenant->merchant_type_id);
    foreach ($modulosNecesarios as $moduloId) {
        $flexibleTenantService->addModuleToTenant($tenant->id, $moduloId);
    }
}
```

### 2. Actualización Masiva de Módulos
```php
// Actualizar módulos en todos los tenants de un tipo
$tenants = Tenant::where('merchant_type_id', 1)->get();
foreach ($tenants as $tenant) {
    $flexibleTenantService->addModuleToTenant($tenant->id, $nuevoModuloId);
}
```

### 3. Sistema de Plantillas
```php
// Crear plantillas de módulos por industria
$plantillas = [
    'restaurante' => [1, 2, 7], // Ventas, Inventario, Cocina
    'retail' => [1, 2, 3],      // Ventas, Inventario, POS
    'servicios' => [1, 8, 9],   // Ventas, CRM, Citas
];

$tenant = $flexibleTenantService->createTenantWithSelectedModules(
    $datosTenant,
    $tipoMerchant,
    $plantillas[$industria],
    $usuario
);
```

## 📞 Soporte y Mantenimiento

### Comandos Artisan Útiles
```bash
# Verificar estado de migraciones tenant
php artisan tenants:list

# Re-ejecutar migraciones específicas
php artisan tenants:artisan "migrate --path=database/migrations/tenants/ventas" --tenant=tenant-uuid

# Backup antes de cambios
php artisan tenant:backup tenant-uuid
```

Esta guía proporciona una base sólida para implementar y mantener el sistema modular de tenants. El enfoque flexible permite adaptarse a diferentes necesidades de negocio sin estar limitado por configuraciones predefinidas de tipos de merchant.