# Sistema de Catálogo de Permisos

Este documento describe el sistema completo de gestión de permisos y roles implementado para la aplicación multitenant.

## Estructura del Sistema

### Tablas de Base de Datos

1. **usr_profiles** - Perfiles/Roles de usuario
2. **usr_permissions** - Permisos disponibles en el sistema
3. **usr_permissions_profiles** - Tabla pivot que relaciona perfiles con permisos y define niveles de acceso

### Niveles de Acceso

Cada permiso asignado a un perfil tiene 4 niveles de acceso:

- **creater** (boolean) - Puede crear registros
- **deleter** (boolean) - Puede eliminar registros
- **editer** (boolean) - Puede editar registros
- **show** (boolean) - Puede ver/consultar registros

## Componentes Implementados

### 1. Modelos Eloquent

#### `App\Models\Central\UsrProfile`
- Gestiona los perfiles/roles de usuario
- Relaciones con usuarios y permisos
- Scopes para filtrar perfiles activos

#### `App\Models\Central\UsrPermission`
- Gestiona los permisos disponibles
- Relación con perfiles a través de tabla pivot

#### `App\Models\Central\UsrPermissionProfile`
- Modelo pivot para la asignación de permisos a perfiles
- Incluye métodos helper para verificar accesos específicos

### 2. Controlador de API

#### `App\Http\Controllers\Auth\PermissionController`

**Endpoints disponibles:**

```php
GET /api/permissions/catalog
POST /api/permissions/assign
POST /api/permissions/assign-multiple
DELETE /api/permissions/remove
GET /api/permissions/profile/{profileId}
POST /api/permissions/clone
```

### 3. Servicio de Catálogo

#### `App\Services\PermissionCatalogService`

**Características principales:**
- Construye el catálogo completo de permisos
- Configura perfiles automáticamente con presets
- Compara perfiles y sus diferencias
- Genera presets predefinidos por tipo de perfil

**Presets disponibles:**
- `super_admin` - Acceso total a todos los permisos
- `admin` - Permisos administrativos sin eliminación
- `employee` - Permisos básicos de empleado
- `pos_seller` - Permisos específicos para vendedores POS
- `warehouse` - Permisos para gestión de almacén
- `accounting` - Permisos para área contable

### 4. Middleware de Verificación

#### `App\Http\Middleware\CheckProfilePermission`

**Uso en rutas:**
```php
Route::get('/ventas', [VentasController::class, 'index'])
    ->middleware('profile.permission:Ventas,show');

Route::post('/ventas', [VentasController::class, 'store'])
    ->middleware('profile.permission:Ventas,create');

Route::put('/ventas/{id}', [VentasController::class, 'update'])
    ->middleware('profile.permission:Ventas,edit');

Route::delete('/ventas/{id}', [VentasController::class, 'destroy'])
    ->middleware('profile.permission:Ventas,delete');
```

### 5. Helper de Permisos

#### `App\Helpers\PermissionHelper`

**Métodos disponibles:**
```php
// Verificar permiso específico
PermissionHelper::userCan('Ventas', 'create');

// Verificar múltiples permisos (OR)
PermissionHelper::userCanAny(['Ventas', 'Caja'], 'show');

// Verificar múltiples permisos (AND)
PermissionHelper::userCanAll(['Ventas', 'Inventario'], 'edit');

// Obtener todos los permisos del usuario
PermissionHelper::getUserPermissions();

// Verificar si es super admin
PermissionHelper::isSuperAdmin();
```

**Directivas Blade:**
```blade
@userCan('Ventas', 'create')
    <button>Crear Venta</button>
@enduserCan

@isSuperAdmin
    <a href="/super-admin">Panel Super Admin</a>
@endisSuperAdmin

@userCanAny(['Ventas', 'Caja'], 'show')
    <div>Panel de Ventas</div>
@enduserCanAny
```

### 6. Comando Artisan

#### `php artisan permissions:configure-profile`

**Ejemplos de uso:**

```bash
# Mostrar catálogo completo
php artisan permissions:configure-profile 1 --show-catalog

# Aplicar preset de super admin
php artisan permissions:configure-profile 1 --preset=super_admin

# Aplicar preset de vendedor POS
php artisan permissions:configure-profile 4 --preset=pos_seller

# Configuración interactiva
php artisan permissions:configure-profile 2
```

## Flujo de Uso del Sistema

### 1. Configuración Inicial

1. **Crear/Verificar Permisos**
   ```sql
   INSERT INTO usr_permissions (name, status, created_at)
   VALUES ('Ventas', 1, NOW());
   ```

2. **Crear/Verificar Perfiles**
   ```sql
   INSERT INTO usr_profiles (name, alias, status, created_at)
   VALUES ('Vendedor', 'Vendedor', 1, NOW());
   ```

### 2. Asignación de Permisos

**Opción A: Via API**
```javascript
fetch('/api/permissions/assign-multiple', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        profileId: 4,
        permissions: [
            {
                permissionId: 1,
                creater: true,
                editer: true,
                deleter: false,
                show: true
            }
        ]
    })
});
```

**Opción B: Via Comando**
```bash
php artisan permissions:configure-profile 4 --preset=pos_seller
```

**Opción C: Via Servicio**
```php
$service = app(PermissionCatalogService::class);
$service->configureProfileWithPermissions($profileId, $permissionsConfig);
```

### 3. Asignación de Perfil a Usuario

```php
// Al crear o actualizar usuario
$user->profile_id = $selectedProfileId;
$user->save();
```

### 4. Verificación de Permisos

**En Controladores:**
```php
public function create()
{
    if (!PermissionHelper::userCan('Ventas', 'create')) {
        abort(403, 'Sin permisos para crear ventas');
    }

    // Lógica del controlador
}
```

**En Vistas:**
```blade
@userCan('Ventas', 'create')
    <button class="btn btn-primary">Nueva Venta</button>
@enduserCan

@userCan('Inventario', 'show')
    <a href="/inventario">Gestión de Inventario</a>
@enduserCan
```

**En Rutas:**
```php
Route::group(['middleware' => 'profile.permission:Ventas,show'], function() {
    Route::get('/ventas', [VentasController::class, 'index']);
    Route::get('/ventas/{id}', [VentasController::class, 'show']);
});

Route::group(['middleware' => 'profile.permission:Ventas,create'], function() {
    Route::post('/ventas', [VentasController::class, 'store']);
});
```

## Ejemplos de Configuración por Perfil

### Super Administrador
- **Todos los permisos**: create, edit, delete, show
- **Modules**: Todos los módulos del sistema

### Administrador
- **Permisos limitados**: create, edit, show (sin delete en módulos críticos)
- **Modules**: Ventas, Inventario, Reportes, Compras, Cartera, Usuarios, Parámetros

### Vendedor POS
- **Permisos específicos**:
  - Ventas: create, edit, show
  - Caja: create, show
  - Inventario: show
- **Modules**: Ventas, Caja, Inventario (consulta)

### Almacén
- **Permisos específicos**:
  - Inventario: create, edit, show
  - Despachos: create, edit, show
  - Compras: show
- **Modules**: Inventario, Despachos, Compras

## Ventajas del Sistema

1. **Flexibilidad**: Configuración granular por permiso y acción
2. **Escalabilidad**: Fácil adición de nuevos permisos y perfiles
3. **Reutilización**: Presets predefinidos para configuración rápida
4. **Seguridad**: Middleware automático y verificaciones en múltiples capas
5. **Facilidad de uso**: Helpers y directivas Blade simplifican la implementación
6. **Auditoría**: Registro completo de asignaciones con timestamps
7. **Multitenancy**: Compatible con la arquitectura multitenant existente

## Próximos Pasos

1. **Registro de Auditoría**: Implementar logs de acciones por usuario
2. **Cache de Permisos**: Optimizar consultas con caché Redis/Memcached
3. **UI de Gestión**: Crear interfaz web para gestión visual de permisos
4. **Permisos Temporales**: Sistema de permisos con expiración
5. **Permisos por Tenant**: Extender para permisos específicos por tenant
6. **Backup/Restore**: Herramientas para respaldar configuraciones de permisos