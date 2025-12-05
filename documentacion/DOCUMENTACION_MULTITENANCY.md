# Documentación de Multitenancy - Sistema RAP

## Resumen del Proyecto

Este proyecto utiliza el patrón de **Database per Tenant** con Laravel y el paquete **Stancl/Tenancy** para crear un sistema multiempresa donde cada tenant (empresa) tiene su propia base de datos independiente.

## Arquitectura de Tenancy

### 1. Base de Datos Central
- **Base de datos**: `rap` (configurada como conexión 'central')
- **Función**: Almacena información global del sistema
- **Ubicación**: [config/database.php:66-84](config/database.php#L66-L84)

#### Tablas principales:
- `tenants` - Información de las empresas
- `users` - Usuarios del sistema
- `user_tenants` - Relación usuarios-empresas
- `domains` - Dominios asociados a tenants
- `two_factor_codes` - Códigos 2FA

### 2. Bases de Datos de Tenant
- **Patrón de nomenclatura**: `tenant_{tenant_id}`
- **Configuración dinámica**: Se crean automáticamente por tenant
- **Ubicación de migraciones**: [database/migrations/tenant/](database/migrations/tenant/)

#### Tablas del tenant (negocio):
- `categorias` - Categorías de productos
- `productos` - Inventario de productos
- `clientes` - Base de clientes
- `ventas` - Registro de ventas
- `detalle_ventas` - Detalles de cada venta
- `cajas` - Control de cajas
- `movimiento_cajas` - Movimientos de caja
- `movimiento_inventarios` - Control de inventario
- `permission_tables` - Roles y permisos (Spatie)

## Flujo de Conexión y Autenticación

### 1. Proceso de Login
```
Usuario ingresa credenciales → Login exitoso → 2FA (si está habilitado) → Selección de Tenant
```

**Archivos involucrados:**
- [app/Livewire/Auth/SelectTenant.php](app/Livewire/Auth/SelectTenant.php) - Componente de selección
- [app/Models/User.php:129-140](app/Models/User.php#L129-L140) - Métodos de verificación de acceso

### 2. Selección de Tenant
**Ruta**: `/select-tenant`
**Middleware**: `auth`

**Proceso:**
1. Usuario autenticado accede a la pantalla de selección
2. Se muestran solo los tenants activos donde el usuario tiene acceso
3. Al seleccionar un tenant, se guarda en sesión (`tenant_id`)
4. Redirección al dashboard del tenant

**Código relevante:**
```php
// Guardar tenant en sesión
Session::put('tenant_id', $tenantId);

// Verificar acceso
if (!Auth::user()->hasAccessToTenant($tenantId)) {
    // Error de acceso
}
```

### 3. Middleware de Conexión de Tenant

**Archivo**: [app/Http/Middleware/SetTenantConnection.php](app/Http/Middleware/SetTenantConnection.php)

**Función**: Se ejecuta en cada request que requiere acceso al tenant

**Proceso:**
1. **Verificación de sesión**: Comprueba si existe `tenant_id` en sesión
2. **Validación de tenant**: Verifica que el tenant existe y está activo
3. **Autorización**: Confirma que el usuario tiene acceso al tenant
4. **Configuración de conexión**: Establece la conexión a la base de datos del tenant
5. **Inicialización de tenancy**: Usa Stancl para inicializar el contexto
6. **Actualización de acceso**: Registra el último acceso del usuario

```php
// Configurar conexión dinámicamente
$this->tenantManager->setConnection($tenant);

// Inicializar tenancy
tenancy()->initialize($tenant);
```

## Configuración de Tenancy

### Archivo principal: [config/tenancy.php](config/tenancy.php)

**Configuraciones clave:**

1. **Modelo de Tenant**: `App\Models\Tenant`
2. **Conexión central**: `central` (línea 42)
3. **Prefijo de BD**: `tenant` (línea 54)
4. **Bootstrappers activos**:
   - DatabaseTenancyBootstrapper
   - CacheTenancyBootstrapper
   - FilesystemTenancyBootstrapper
   - QueueTenancyBootstrapper

5. **Dominios centrales**:
   ```php
   'central_domains' => [
       '127.0.0.1',
       'localhost',
   ]
   ```

## Modelos Principales

### 1. Tenant Model ([app/Models/Tenant.php](app/Models/Tenant.php))
```php
class Tenant extends Model implements TenantWithDatabase
{
    use HasDatabase, HasDomains, HasUuids;

    // Campos de configuración de BD
    protected $fillable = [
        'id', 'name', 'email', 'phone', 'address',
        'db_name', 'db_user', 'db_password', 'db_host', 'db_port',
        'is_active', 'settings'
    ];
}
```

### 2. User Model ([app/Models/User.php](app/Models/User.php))
```php
// Relación con tenants
public function tenants(): BelongsToMany
{
    return $this->belongsToMany(Tenant::class, 'user_tenants')
        ->withPivot('role', 'is_active', 'last_accessed_at');
}

// Verificar acceso a tenant
public function hasAccessToTenant(string $tenantId): bool
{
    return $this->activeTenants()->where('tenants.id', $tenantId)->exists();
}
```

### 3. UserTenant Model ([app/Models/UserTenant.php](app/Models/UserTenant.php))
Tabla pivot que maneja la relación usuarios-tenants con campos adicionales:
- `role` - Rol del usuario en el tenant
- `is_active` - Estado del acceso
- `last_accessed_at` - Último acceso

## Servicio TenantManager

**Archivo**: [app/Services/TenantManager.php](app/Services/TenantManager.php)

### Funciones principales:

1. **Crear Tenant**:
   ```php
   public function create(array $data, ?User $owner = null): Tenant
   ```
   - Crea registro en tabla central
   - Crea base de datos física
   - Ejecuta migraciones
   - Instala Spatie Permission
   - Asigna usuario propietario

2. **Establecer Conexión**:
   ```php
   public function setConnection(Tenant $tenant): void
   ```
   - Configura conexión dinámica
   - Limpia y reconecta la conexión

3. **Gestión de Usuarios**:
   ```php
   public function assignUser(Tenant $tenant, User $user, string $role = 'user'): void
   public function removeUser(Tenant $tenant, User $user): void
   ```

## Rutas y Middleware

### Rutas principales ([routes/web.php](routes/web.php)):

```php
// Selección de tenant (requiere autenticación)
Route::get('/select-tenant', SelectTenant::class)
    ->middleware(['auth'])
    ->name('tenant.select');

// Dashboard del tenant (requiere tenant activo)
Route::get('/tenant/dashboard', TenantDashboard::class)
    ->middleware(['auth', SetTenantConnection::class])
    ->name('tenant.dashboard');
```

### Aplicación de Middleware:
- **`auth`**: Verificación de autenticación
- **`SetTenantConnection`**: Establecimiento de conexión al tenant

## Comandos Artisan

### 1. Crear Tenant
```bash
php artisan tenant:create {name} {email} --phone={phone} --address={address}
```
**Archivo**: [app/Console/Commands/CreateTenantCommand.php](app/Console/Commands/CreateTenantCommand.php)

### 2. Asignar Usuario a Tenant
```bash
php artisan tenant:assign-user {userId} {tenantId} --role={role}
```
**Archivo**: [app/Console/Commands/AssignUserToTenantCommand.php](app/Console/Commands/AssignUserToTenantCommand.php)

## Flujo Completo de Usuario

### 1. Registro/Login
1. Usuario se registra o hace login en el sistema central
2. Si tiene 2FA habilitado, debe verificar el código
3. Sistema verifica credenciales contra la base central

### 2. Selección de Empresa
1. Usuario es redirigido a `/select-tenant`
2. Se muestran todas las empresas donde tiene acceso activo
3. Si solo tiene una empresa, se selecciona automáticamente
4. Al seleccionar, se guarda `tenant_id` en sesión

### 3. Acceso al Dashboard
1. Usuario accede a `/tenant/dashboard`
2. Middleware `SetTenantConnection` intercepta la petición:
   - Verifica `tenant_id` en sesión
   - Valida que el tenant existe y está activo
   - Confirma que el usuario tiene acceso
   - Establece conexión a la BD del tenant
   - Inicializa contexto de tenancy
3. Usuario puede operar dentro del contexto del tenant seleccionado

### 4. Cambio de Empresa
- Usuario puede regresar a `/select-tenant` para cambiar de empresa
- Se limpia la sesión actual y se reinicia el proceso

## Consideraciones de Seguridad

1. **Aislamiento de Datos**: Cada tenant tiene su propia base de datos
2. **Verificación de Acceso**: Doble verificación (sesión + base de datos)
3. **Autenticación 2FA**: Sistema opcional de doble factor
4. **Middleware de Protección**: Cada request valida permisos
5. **Conexiones Dinámicas**: No hay conexiones persistentes entre tenants

## Ventajas de esta Implementación

1. **Aislamiento Total**: Datos completamente separados
2. **Escalabilidad**: Cada tenant puede tener su propia infraestructura
3. **Personalización**: Configuraciones independientes por tenant
4. **Seguridad**: Imposibilidad de acceso cruzado accidental
5. **Backup/Restore**: Posibilidad de backup individual por empresa

## Estructura de Directorios

```
├── config/
│   ├── tenancy.php         # Configuración principal
│   └── database.php        # Conexiones de BD
├── database/
│   ├── migrations/         # Migraciones centrales
│   └── migrations/tenant/  # Migraciones de tenant
├── app/
│   ├── Models/
│   │   ├── Tenant.php
│   │   ├── User.php
│   │   └── UserTenant.php
│   ├── Services/
│   │   └── TenantManager.php
│   ├── Http/Middleware/
│   │   └── SetTenantConnection.php
│   └── Livewire/Auth/
│       └── SelectTenant.php
```

Esta arquitectura proporciona un sistema robusto y escalable para manejar múltiples empresas con total aislamiento de datos y una experiencia de usuario fluida.