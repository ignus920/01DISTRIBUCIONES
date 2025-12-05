# DOCUMENTACIÓN COMPLETA - PARTE 2
## SISTEMA DE PLUGINS, TEMPLATES Y MÓDULOS

---

## 4. SISTEMA DE MULTI-TENANCY

### 4.1 Configuración de Tenancy

**Archivo:** `config/tenancy.php`

```php
<?php

return [
    'tenant_model' => \App\Models\Tenant::class,
    'id_generator' => Stancl\Tenancy\UUIDGenerator::class,

    'central_domains' => [
        '127.0.0.1',
        'localhost',
        'app.tuempresa.com', // Dominio central
    ],

    'bootstrappers' => [
        Stancl\Tenancy\Bootstrappers\DatabaseTenancyBootstrapper::class,
        Stancl\Tenancy\Bootstrappers\CacheTenancyBootstrapper::class,
        Stancl\Tenancy\Bootstrappers\FilesystemTenancyBootstrapper::class,
        Stancl\Tenancy\Bootstrappers\QueueTenancyBootstrapper::class,
    ],

    'database' => [
        'central_connection' => 'mysql',
        'prefix' => 'tenant',
        'suffix' => '',

        'managers' => [
            'mysql' => Stancl\Tenancy\TenantDatabaseManagers\MySQLDatabaseManager::class,
        ],
    ],

    'migration_parameters' => [
        '--force' => true,
        '--path' => [database_path('migrations/tenant')],
        '--realpath' => true,
    ],
];
```

### 4.2 Modelo Tenant Personalizado

**Archivo:** `app/Models/Tenant.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Stancl\Tenancy\Contracts\TenantWithDatabase;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;

class Tenant extends Model implements TenantWithDatabase
{
    use HasDatabase, HasDomains, HasUuids;

    protected $fillable = [
        'id',
        'name',
        'email',
        'phone',
        'address',
        'db_name',
        'db_user',
        'db_password',
        'db_host',
        'db_port',
        'is_active',
        'settings',
        'plan',
        'trial_ends_at',
        'subscription_ends_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
        'trial_ends_at' => 'datetime',
        'subscription_ends_at' => 'datetime',
    ];

    // Relación con usuarios
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_tenants')
            ->withPivot('role', 'is_active', 'last_accessed_at')
            ->withTimestamps();
    }

    // Relación con módulos activos
    public function modules()
    {
        return $this->belongsToMany(Module::class, 'tenant_modules')
            ->withPivot('settings', 'is_active')
            ->withTimestamps();
    }

    // Relación con plugins instalados
    public function plugins()
    {
        return $this->belongsToMany(Plugin::class, 'tenant_plugins')
            ->withPivot('version', 'config', 'status')
            ->withTimestamps();
    }

    // Verificar si tiene un módulo activo
    public function hasModule(string $moduleSlug): bool
    {
        return $this->modules()
            ->where('slug', $moduleSlug)
            ->wherePivot('is_active', true)
            ->exists();
    }

    // Verificar si tiene un plugin instalado
    public function hasPlugin(string $pluginSlug): bool
    {
        return $this->plugins()
            ->where('slug', $pluginSlug)
            ->wherePivot('status', 'active')
            ->exists();
    }

    // Obtener configuración de un módulo
    public function getModuleSettings(string $moduleSlug): ?array
    {
        $module = $this->modules()->where('slug', $moduleSlug)->first();
        return $module?->pivot->settings;
    }

    // Scope para tenants activos
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope para tenants en trial
    public function scopeOnTrial($query)
    {
        return $query->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '>', now());
    }

    // Verificar si está en trial
    public function onTrial(): bool
    {
        return $this->trial_ends_at && $this->trial_ends_at->isFuture();
    }

    // Verificar si la suscripción está activa
    public function subscriptionActive(): bool
    {
        return $this->subscription_ends_at && $this->subscription_ends_at->isFuture();
    }
}
```

### 4.3 TenantManager - Servicio Principal

**Archivo:** `app/Core/Tenant/TenantManager.php`

```php
<?php

namespace App\Core\Tenant;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

class TenantManager
{
    /**
     * Crear un nuevo tenant con su base de datos.
     */
    public function create(array $data, ?User $owner = null): Tenant
    {
        DB::beginTransaction();

        try {
            // Generar UUID si no existe
            $tenantId = $data['id'] ?? Str::uuid()->toString();

            // Generar nombre de BD
            $dbName = $data['db_name'] ?? 'tenant_' . str_replace('-', '_', $tenantId);

            // Crear el tenant
            $tenant = Tenant::create([
                'id' => $tenantId,
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'db_name' => $dbName,
                'db_user' => config('database.connections.mysql.username'),
                'db_password' => config('database.connections.mysql.password'),
                'db_host' => config('database.connections.mysql.host'),
                'db_port' => config('database.connections.mysql.port'),
                'is_active' => true,
                'settings' => $data['settings'] ?? [],
                'plan' => $data['plan'] ?? 'basic',
                'trial_ends_at' => now()->addDays(15), // 15 días de trial
            ]);

            // Crear base de datos física
            $this->createDatabase($tenant);

            // Ejecutar migraciones
            $this->runMigrations($tenant);

            // Instalar módulos por defecto
            $this->installDefaultModules($tenant);

            // Asignar propietario si existe
            if ($owner) {
                $this->assignUser($tenant, $owner, 'admin');
            }

            DB::commit();

            event(new \App\Core\Tenant\Events\TenantCreated($tenant));

            return $tenant;

        } catch (\Exception $e) {
            DB::rollBack();

            // Intentar limpiar la BD si fue creada
            if (isset($dbName)) {
                try {
                    DB::statement("DROP DATABASE IF EXISTS `{$dbName}`");
                } catch (\Exception $dropEx) {
                    // Silenciar
                }
            }

            throw $e;
        }
    }

    /**
     * Crear base de datos física.
     */
    protected function createDatabase(Tenant $tenant): void
    {
        $dbName = $tenant->db_name;
        $charset = config('database.connections.mysql.charset', 'utf8mb4');
        $collation = config('database.connections.mysql.collation', 'utf8mb4_unicode_ci');

        DB::statement("CREATE DATABASE IF NOT EXISTS `{$dbName}`
                       CHARACTER SET {$charset}
                       COLLATE {$collation}");
    }

    /**
     * Ejecutar migraciones del tenant.
     */
    protected function runMigrations(Tenant $tenant): void
    {
        $tenant->run(function () {
            Artisan::call('migrate', [
                '--database' => 'tenant',
                '--path' => 'database/migrations/tenant',
                '--force' => true,
            ]);
        });
    }

    /**
     * Instalar módulos por defecto.
     */
    protected function installDefaultModules(Tenant $tenant): void
    {
        $defaultModules = config('modules.default', ['dashboard', 'users', 'settings']);

        foreach ($defaultModules as $moduleSlug) {
            app(\App\Core\Module\ModuleManager::class)
                ->enableForTenant($tenant, $moduleSlug);
        }
    }

    /**
     * Asignar usuario a tenant.
     */
    public function assignUser(Tenant $tenant, User $user, string $role = 'user'): void
    {
        $tenant->users()->syncWithoutDetaching([
            $user->id => [
                'role' => $role,
                'is_active' => true,
                'last_accessed_at' => now(),
            ]
        ]);
    }

    /**
     * Aplicar template de negocio.
     */
    public function applyTemplate(Tenant $tenant, string $templateName): void
    {
        $templateManager = app(\App\Core\Template\TemplateManager::class);
        $template = $templateManager->load($templateName);

        if ($template) {
            $templateManager->apply($template, $tenant);
        }
    }

    /**
     * Establecer conexión al tenant.
     */
    public function setConnection(Tenant $tenant): void
    {
        config([
            'database.connections.tenant' => [
                'driver' => 'mysql',
                'host' => $tenant->db_host,
                'port' => $tenant->db_port,
                'database' => $tenant->db_name,
                'username' => $tenant->db_user,
                'password' => $tenant->db_password,
                'charset' => 'utf8mb4',
                'collation' => 'utf8mb4_unicode_ci',
                'prefix' => '',
                'strict' => true,
            ]
        ]);

        DB::purge('tenant');
        DB::reconnect('tenant');
    }

    /**
     * Eliminar tenant y su BD.
     */
    public function delete(Tenant $tenant, bool $deleteDatabase = false): void
    {
        DB::beginTransaction();

        try {
            if ($deleteDatabase) {
                DB::statement("DROP DATABASE IF EXISTS `{$tenant->db_name}`");
            }

            $tenant->delete();

            DB::commit();

            event(new \App\Core\Tenant\Events\TenantDeleted($tenant));

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
```

### 4.4 Middleware SetTenantConnection

**Archivo:** `app/Http/Middleware/SetTenantConnection.php`

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Tenant;
use App\Core\Tenant\TenantManager;
use Illuminate\Support\Facades\Auth;

class SetTenantConnection
{
    protected $tenantManager;

    public function __construct(TenantManager $tenantManager)
    {
        $this->tenantManager = $tenantManager;
    }

    public function handle(Request $request, Closure $next)
    {
        // Obtener tenant_id de la sesión
        $tenantId = session('tenant_id');

        if (!$tenantId) {
            return redirect()->route('tenant.select');
        }

        // Buscar tenant
        $tenant = Tenant::find($tenantId);

        if (!$tenant || !$tenant->is_active) {
            session()->forget('tenant_id');
            return redirect()->route('tenant.select')
                ->withErrors(['tenant' => 'Tenant no disponible']);
        }

        // Verificar acceso del usuario
        $user = Auth::user();
        if ($user && !$user->hasAccessToTenant($tenantId)) {
            session()->forget('tenant_id');
            return redirect()->route('tenant.select')
                ->withErrors(['tenant' => 'No tiene acceso a este tenant']);
        }

        // Establecer conexión
        $this->tenantManager->setConnection($tenant);

        // Inicializar tenancy
        tenancy()->initialize($tenant);

        // Compartir tenant con las vistas
        view()->share('currentTenant', $tenant);

        // Actualizar último acceso
        if ($user) {
            $user->tenants()
                ->updateExistingPivot($tenantId, [
                    'last_accessed_at' => now()
                ]);
        }

        return $next($request);
    }
}
```

---

## 5. SISTEMA DE PLUGINS

### 5.1 Estructura de un Plugin

Cada plugin es un paquete autocontenido con la siguiente estructura:

```
Plugins/NombrePlugin/
├── plugin.json                    # ⭐ Metadata del plugin
├── PluginServiceProvider.php      # Service Provider
├── Connectors/                    # Conectores externos
│   ├── Connector1/
│   │   ├── Connector1.php
│   │   ├── config.php
│   │   └── views/
│   └── Connector2/
├── Controllers/                   # Controladores
├── Models/                        # Modelos
├── Services/                      # Servicios
├── Hooks/                         # Hooks de eventos
├── database/migrations/           # Migraciones
├── routes/plugin.php              # Rutas
├── views/                         # Vistas
└── config/nombre-plugin.php       # Configuración
```

### 5.2 Archivo plugin.json - Metadata

**Ejemplo:** `Plugins/BillingElectronic/plugin.json`

```json
{
  "name": "billing-electronic",
  "display_name": "Facturación Electrónica",
  "version": "1.0.0",
  "author": "Tu Empresa",
  "description": "Conexión con entidades gubernamentales para facturación electrónica",
  "icon": "plugins/billing-electronic/icon.svg",
  "category": "billing",
  "price_tier": "premium",
  "price_monthly": 15.00,
  "price_annual": 150.00,

  "connectors": [
    {
      "id": "dian_colombia",
      "name": "DIAN Colombia",
      "country": "CO",
      "requires_credentials": true,
      "credentials_fields": [
        {
          "name": "nit",
          "type": "text",
          "label": "NIT de la empresa",
          "placeholder": "Sin dígito de verificación",
          "required": true,
          "validation": "required|numeric|digits_between:9,10"
        },
        {
          "name": "software_id",
          "type": "text",
          "label": "Software ID (proporcionado por DIAN)",
          "required": true
        },
        {
          "name": "pin",
          "type": "password",
          "label": "PIN de Seguridad",
          "required": true
        },
        {
          "name": "certificate",
          "type": "file",
          "label": "Certificado Digital (.pfx)",
          "accept": ".pfx,.p12",
          "required": true
        },
        {
          "name": "certificate_password",
          "type": "password",
          "label": "Contraseña del Certificado",
          "required": true
        },
        {
          "name": "test_set_id",
          "type": "text",
          "label": "Test Set ID (ambiente de pruebas)",
          "required": false
        },
        {
          "name": "environment",
          "type": "select",
          "label": "Ambiente",
          "options": [
            {"value": "test", "label": "Pruebas"},
            {"value": "production", "label": "Producción"}
          ],
          "default": "test",
          "required": true
        }
      ],
      "test_connection": true,
      "webhook_url": "/webhooks/dian/status"
    },
    {
      "id": "sunat_peru",
      "name": "SUNAT Perú",
      "country": "PE",
      "requires_credentials": true,
      "credentials_fields": [
        {
          "name": "ruc",
          "type": "text",
          "label": "RUC",
          "required": true,
          "validation": "required|numeric|digits:11"
        },
        {
          "name": "usuario_sol",
          "type": "text",
          "label": "Usuario SOL",
          "required": true
        },
        {
          "name": "clave_sol",
          "type": "password",
          "label": "Clave SOL",
          "required": true
        },
        {
          "name": "certificate",
          "type": "file",
          "label": "Certificado Digital",
          "accept": ".pfx",
          "required": true
        }
      ],
      "test_connection": true
    },
    {
      "id": "sat_mexico",
      "name": "SAT México",
      "country": "MX",
      "requires_credentials": true,
      "credentials_fields": [
        {
          "name": "rfc",
          "type": "text",
          "label": "RFC",
          "required": true
        },
        {
          "name": "certificate",
          "type": "file",
          "label": "Certificado (.cer)",
          "accept": ".cer",
          "required": true
        },
        {
          "name": "private_key",
          "type": "file",
          "label": "Llave Privada (.key)",
          "accept": ".key",
          "required": true
        },
        {
          "name": "password",
          "type": "password",
          "label": "Contraseña de la llave privada",
          "required": true
        }
      ],
      "test_connection": true
    }
  ],

  "dependencies": {
    "modules": ["billing", "inventory"],
    "plugins": [],
    "php_extensions": ["soap", "openssl", "dom", "xmlwriter"],
    "composer_packages": {
      "greenter/greenter": "^4.0",
      "robrichards/xmlseclibs": "^3.1"
    }
  },

  "auto_install_migrations": true,
  "routes_file": "routes/plugin.php",
  "views_namespace": "billing-electronic",
  "config_file": "config/billing-electronic.php",

  "hooks": {
    "on_install": "App\\Plugins\\BillingElectronic\\Hooks\\InstallHook",
    "on_uninstall": "App\\Plugins\\BillingElectronic\\Hooks\\UninstallHook",
    "on_invoice_created": "App\\Plugins\\BillingElectronic\\Hooks\\SendToGovernment",
    "on_invoice_cancelled": "App\\Plugins\\BillingElectronic\\Hooks\\CancelInGovernment"
  },

  "settings_page": {
    "route": "plugin.billing-electronic.settings",
    "menu_position": "billing",
    "icon": "document-check",
    "permissions": ["admin", "billing_manager"]
  },

  "screenshots": [
    "plugins/billing-electronic/screenshots/1.png",
    "plugins/billing-electronic/screenshots/2.png"
  ],

  "changelog": [
    {
      "version": "1.0.0",
      "date": "2025-10-20",
      "changes": [
        "Versión inicial",
        "Soporte para DIAN Colombia",
        "Soporte para SUNAT Perú"
      ]
    }
  ]
}
```

### 5.3 PluginManager - Gestor de Plugins

**Archivo:** `app/Core/Plugin/PluginManager.php`

```php
<?php

namespace App\Core\Plugin;

use App\Models\Tenant;
use App\Models\Plugin;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PluginManager
{
    private $pluginsPath;

    public function __construct()
    {
        $this->pluginsPath = app_path('Plugins');
    }

    /**
     * Descubrir todos los plugins disponibles.
     */
    public function discover(): array
    {
        $plugins = [];

        if (!File::exists($this->pluginsPath)) {
            return $plugins;
        }

        foreach (File::directories($this->pluginsPath) as $dir) {
            $metadataFile = $dir . '/plugin.json';

            if (File::exists($metadataFile)) {
                $metadata = json_decode(File::get($metadataFile), true);

                if ($metadata && isset($metadata['name'])) {
                    $plugins[] = $metadata;
                }
            }
        }

        return $plugins;
    }

    /**
     * Obtener metadata de un plugin.
     */
    public function getMetadata(string $pluginSlug): ?array
    {
        $pluginPath = $this->pluginsPath . '/' . Str::studly($pluginSlug);
        $metadataFile = $pluginPath . '/plugin.json';

        if (!File::exists($metadataFile)) {
            return null;
        }

        return json_decode(File::get($metadataFile), true);
    }

    /**
     * Instalar plugin para un tenant.
     */
    public function install(string $pluginSlug, Tenant $tenant): bool
    {
        $metadata = $this->getMetadata($pluginSlug);

        if (!$metadata) {
            throw new Exceptions\PluginNotFoundException("Plugin {$pluginSlug} not found");
        }

        DB::beginTransaction();

        try {
            // Verificar dependencias
            $this->checkDependencies($metadata, $tenant);

            // Registrar plugin en BD central
            $plugin = Plugin::firstOrCreate(
                ['slug' => $pluginSlug],
                [
                    'name' => $metadata['display_name'],
                    'version' => $metadata['version'],
                    'metadata' => $metadata,
                ]
            );

            // Asociar con tenant
            $tenant->plugins()->syncWithoutDetaching([
                $plugin->id => [
                    'version' => $metadata['version'],
                    'status' => 'installing',
                    'installed_at' => now(),
                ]
            ]);

            // Ejecutar migraciones si es necesario
            if ($metadata['auto_install_migrations']) {
                $this->runMigrations($pluginSlug, $tenant);
            }

            // Copiar assets
            $this->publishAssets($pluginSlug);

            // Ejecutar hook de instalación
            if (isset($metadata['hooks']['on_install'])) {
                app($metadata['hooks']['on_install'])->handle($tenant);
            }

            // Actualizar estado
            $tenant->plugins()->updateExistingPivot($plugin->id, [
                'status' => 'installed'
            ]);

            DB::commit();

            event(new \App\Core\Plugin\Events\PluginInstalled($tenant, $plugin));

            return true;

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error("Plugin installation failed: " . $e->getMessage());

            throw $e;
        }
    }

    /**
     * Verificar dependencias del plugin.
     */
    protected function checkDependencies(array $metadata, Tenant $tenant): void
    {
        // Verificar módulos requeridos
        if (isset($metadata['dependencies']['modules'])) {
            foreach ($metadata['dependencies']['modules'] as $moduleSlug) {
                if (!$tenant->hasModule($moduleSlug)) {
                    throw new \Exception("El plugin requiere el módulo '{$moduleSlug}' que no está instalado.");
                }
            }
        }

        // Verificar plugins requeridos
        if (isset($metadata['dependencies']['plugins'])) {
            foreach ($metadata['dependencies']['plugins'] as $requiredPlugin) {
                if (!$tenant->hasPlugin($requiredPlugin)) {
                    throw new \Exception("El plugin requiere el plugin '{$requiredPlugin}' que no está instalado.");
                }
            }
        }

        // Verificar extensiones PHP
        if (isset($metadata['dependencies']['php_extensions'])) {
            foreach ($metadata['dependencies']['php_extensions'] as $extension) {
                if (!extension_loaded($extension)) {
                    throw new \Exception("El plugin requiere la extensión PHP '{$extension}' que no está instalada.");
                }
            }
        }
    }

    /**
     * Ejecutar migraciones del plugin.
     */
    protected function runMigrations(string $pluginSlug, Tenant $tenant): void
    {
        $pluginPath = $this->pluginsPath . '/' . Str::studly($pluginSlug);
        $migrationsPath = $pluginPath . '/database/migrations';

        if (!File::exists($migrationsPath)) {
            return;
        }

        $tenant->run(function () use ($migrationsPath) {
            Artisan::call('migrate', [
                '--database' => 'tenant',
                '--path' => $migrationsPath,
                '--force' => true,
            ]);
        });
    }

    /**
     * Publicar assets del plugin.
     */
    protected function publishAssets(string $pluginSlug): void
    {
        $pluginPath = $this->pluginsPath . '/' . Str::studly($pluginSlug);
        $assetsPath = $pluginPath . '/public';

        if (File::exists($assetsPath)) {
            $targetPath = public_path('plugins/' . $pluginSlug);

            File::ensureDirectoryExists($targetPath);
            File::copyDirectory($assetsPath, $targetPath);
        }
    }

    /**
     * Activar un conector específico.
     */
    public function activateConnector(
        string $pluginSlug,
        string $connectorId,
        Tenant $tenant,
        array $credentials
    ): bool {
        $plugin = $tenant->plugins()->where('slug', $pluginSlug)->first();

        if (!$plugin) {
            throw new Exceptions\PluginNotInstalledException();
        }

        $metadata = $this->getMetadata($pluginSlug);
        $connector = collect($metadata['connectors'])->firstWhere('id', $connectorId);

        if (!$connector) {
            throw new \Exception("Connector '{$connectorId}' not found in plugin '{$pluginSlug}'");
        }

        // Validar credenciales
        $this->validateCredentials($connector['credentials_fields'], $credentials);

        // Probar conexión si está configurado
        if ($connector['test_connection']) {
            $connectorClass = $this->resolveConnectorClass($pluginSlug, $connectorId);
            $instance = new $connectorClass($credentials);

            if (!$instance->testConnection()) {
                throw new Exceptions\ConnectorTestFailedException(
                    "No se pudo conectar con " . $connector['name']
                );
            }
        }

        // Guardar configuración
        $config = $plugin->pivot->config ?? [];
        $config['connectors'][$connectorId] = [
            'enabled' => true,
            'credentials' => encrypt($credentials), // Encriptar credenciales
            'activated_at' => now(),
            'last_test' => now(),
        ];

        $tenant->plugins()->updateExistingPivot($plugin->id, [
            'config' => $config
        ]);

        event(new \App\Core\Plugin\Events\ConnectorActivated($tenant, $pluginSlug, $connectorId));

        return true;
    }

    /**
     * Validar credenciales del conector.
     */
    protected function validateCredentials(array $fields, array $credentials): void
    {
        $validator = \Validator::make($credentials,
            collect($fields)
                ->filter(fn($field) => $field['required'])
                ->mapWithKeys(fn($field) => [
                    $field['name'] => $field['validation'] ?? 'required'
                ])
                ->toArray()
        );

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }
    }

    /**
     * Resolver clase del conector.
     */
    protected function resolveConnectorClass(string $pluginSlug, string $connectorId): string
    {
        $pluginStudly = Str::studly($pluginSlug);
        $connectorStudly = Str::studly($connectorId);

        return "App\\Plugins\\{$pluginStudly}\\Connectors\\{$connectorStudly}\\{$connectorStudly}Connector";
    }

    /**
     * Desinstalar plugin.
     */
    public function uninstall(string $pluginSlug, Tenant $tenant, bool $deleteMigrations = false): bool
    {
        $plugin = $tenant->plugins()->where('slug', $pluginSlug)->first();

        if (!$plugin) {
            throw new Exceptions\PluginNotInstalledException();
        }

        DB::beginTransaction();

        try {
            $metadata = $this->getMetadata($pluginSlug);

            // Ejecutar hook de desinstalación
            if (isset($metadata['hooks']['on_uninstall'])) {
                app($metadata['hooks']['on_uninstall'])->handle($tenant);
            }

            // Revertir migraciones si se solicita
            if ($deleteMigrations) {
                $this->rollbackMigrations($pluginSlug, $tenant);
            }

            // Remover de tenant
            $tenant->plugins()->detach($plugin->id);

            DB::commit();

            event(new \App\Core\Plugin\Events\PluginUninstalled($tenant, $plugin));

            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Obtener plugins instalados de un tenant.
     */
    public function installed(Tenant $tenant): \Illuminate\Support\Collection
    {
        return $tenant->plugins()
            ->where('status', 'installed')
            ->get()
            ->map(function ($plugin) {
                $metadata = $this->getMetadata($plugin->slug);

                return array_merge($metadata ?? [], [
                    'installed_at' => $plugin->pivot->installed_at,
                    'config' => $plugin->pivot->config,
                    'status' => $plugin->pivot->status,
                ]);
            });
    }

    /**
     * Verificar si un tenant tiene un plugin activo.
     */
    public function hasPlugin(Tenant $tenant, string $pluginSlug): bool
    {
        return $tenant->plugins()
            ->where('slug', $pluginSlug)
            ->wherePivot('status', 'installed')
            ->exists();
    }

    /**
     * Verificar si un conector está activo.
     */
    public function hasConnector(Tenant $tenant, string $pluginSlug, string $connectorId): bool
    {
        $plugin = $tenant->plugins()->where('slug', $pluginSlug)->first();

        if (!$plugin) {
            return false;
        }

        $config = $plugin->pivot->config ?? [];

        return $config['connectors'][$connectorId]['enabled'] ?? false;
    }
}
```

---

*Continúa en PARTE 3...*
