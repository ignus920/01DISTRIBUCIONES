<?php

namespace App\Services\Tenant;

use App\Models\Auth\Tenant;
use App\Models\Auth\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class TenantManager
{
    /**
     * Crea un nuevo tenant solo con el registro en la tabla (sin BD física).
     * Para usar en el registro inicial de usuarios.
     */
    public function createTenantRecord(array $data, ?User $owner = null): Tenant
    {
        Log::info('📝 Creando registro de tenant (sin BD física)', $data);

        $tenantId = $data['id'] ?? Str::uuid()->toString();
        $companyPrefix = isset($data['company_id']) ? 'company_' . $data['company_id'] : $data['name'];
        $dbName = $data['db_name'] ?? $companyPrefix . '_' . str_replace('-', '_', $tenantId);

        $tenant = Tenant::create([
            'id' => $tenantId,
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'db_name' => $dbName,
            'db_user' => $data['db_user'] ?? config('database.connections.mysql.username'),
            'db_password' => $data['db_password'] ?? config('database.connections.mysql.password'),
            'db_host' => $data['db_host'] ?? config('database.connections.mysql.host'),
            'db_port' => $data['db_port'] ?? config('database.connections.mysql.port'),
            'is_active' => $data['is_active'] ?? true,
            'merchant_type_id' => $data['merchant_type_id'] ?? null,
            'settings' => $data['settings'] ?? [],
            'database_setup' => false, // Marcar que la BD no está configurada
        ]);

        if ($owner) {
            $this->assignUser($tenant, $owner, 'admin');
        }

        Log::info('✅ Registro de tenant creado exitosamente', ['tenant_id' => $tenant->id, 'db_name' => $dbName]);
        return $tenant;
    }

    /**
     * Configura la base de datos física y ejecuta migraciones para un tenant existente.
     * Para usar cuando el usuario complete sus datos en updateCompany.
     */
    public function setupTenantDatabase(Tenant $tenant): void
    {
        Log::info('🏗️ Configurando base de datos física para tenant', ['tenant_id' => $tenant->id, 'db_name' => $tenant->db_name]);

        // Aumentar tiempo de ejecución temporalmente para migraciones
        $originalTimeLimit = ini_get('max_execution_time');
        set_time_limit(300); // 5 minutos para las migraciones

        try {
            $this->createDatabase($tenant);
            $this->runMigrations($tenant);

            // Marcar como configurado
            $tenant->update(['database_setup' => true]);

            Log::info('✅ Base de datos del tenant configurada exitosamente', ['tenant_id' => $tenant->id]);

        } catch (\Exception $e) {
            Log::error('❌ Error configurando BD del tenant', [
                'tenant_id' => $tenant->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Limpiar BD parcialmente creada en caso de error
            try {
                DB::statement("DROP DATABASE IF EXISTS `{$tenant->db_name}`");
            } catch (\Throwable $dropError) {
                Log::warning("⚠️ No se pudo eliminar la BD tras el error", ['db' => $tenant->db_name]);
            }

            throw $e;
        } finally {
            // Restaurar límite de tiempo original
            set_time_limit($originalTimeLimit ?: 30);
        }
    }

    /**
     * Crea un nuevo tenant con su base de datos y migraciones.
     * Mantiene compatibilidad con el método original.
     */
    public function create(array $data, ?User $owner = null): Tenant
    {
        // Aumentar tiempo de ejecución temporalmente para migraciones
        $originalTimeLimit = ini_get('max_execution_time');
        set_time_limit(300); // 5 minutos para las migraciones

        try {
            Log::info('🏗️ Creando nuevo tenant', $data);

            $tenantId = $data['id'] ?? Str::uuid()->toString();
            $companyPrefix = isset($data['company_id']) ? 'company_' . $data['company_id'] : $data['name'];
            $dbName = $data['db_name'] ?? $companyPrefix . '_' . str_replace('-', '_', $tenantId);

            $tenant = Tenant::create([
                'id' => $tenantId,
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'db_name' => $dbName,
                'db_user' => $data['db_user'] ?? config('database.connections.mysql.username'),
                'db_password' => $data['db_password'] ?? config('database.connections.mysql.password'),
                'db_host' => $data['db_host'] ?? config('database.connections.mysql.host'),
                'db_port' => $data['db_port'] ?? config('database.connections.mysql.port'),
                'is_active' => $data['is_active'] ?? true,
                'merchant_type_id' => $data['merchant_type_id'] ?? null,
                'settings' => $data['settings'] ?? [],
            ]);

            $this->createDatabase($tenant);
            $this->runMigrations($tenant);

            if ($owner) {
                $this->assignUser($tenant, $owner, 'admin');
            }

            return $tenant;

        } catch (\Exception $e) {
            if (isset($tenant) && isset($dbName)) {
                try {
                    DB::statement("DROP DATABASE IF EXISTS `{$dbName}`");
                } catch (\Throwable $dropError) {
                    Log::warning("⚠️ No se pudo eliminar la BD tras el error", ['db' => $dbName]);
                }
            }
            throw $e;
        } finally {
            // Restaurar límite de tiempo original
            set_time_limit($originalTimeLimit ?: 30);
        }
    }

    /**
     * Crea la base de datos física del tenant.
     */
    protected function createDatabase(Tenant $tenant): void
    {
        $connection = config('database.default');
        $charset = config("database.connections.{$connection}.charset", 'utf8mb4');
        $collation = config("database.connections.{$connection}.collation", 'utf8mb4_unicode_ci');

        DB::statement("CREATE DATABASE IF NOT EXISTS `{$tenant->db_name}` CHARACTER SET {$charset} COLLATE {$collation}");
        Log::info("✅ Base de datos creada o existente: {$tenant->db_name}");
    }











    /**
     * Ejecuta las migraciones del tenant según sus módulos activos.
     */
    protected function runMigrations(Tenant $tenant): void
{
    Log::info('🚀 Iniciando migraciones para tenant', ['db_name' => $tenant->db_name]);

    $originalConnection = config('database.default');

    // Configurar conexión dinámica para el tenant
    config([
        'database.connections.tenant_migrations' => [
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
        ],
    ]);

    config(['database.default' => 'tenant_migrations']);
    DB::purge('tenant_migrations');
    DB::reconnect('tenant_migrations');

    try {
        // Optimizaciones para acelerar las migraciones
        DB::connection('tenant_migrations')->statement('SET FOREIGN_KEY_CHECKS = 0');
        DB::connection('tenant_migrations')->statement('SET AUTOCOMMIT = 0');
        DB::connection('tenant_migrations')->beginTransaction();
        // Consultar módulos activos desde la base de datos principal
        $modules = DB::connection('mysql')->table('vnt_merchant_moduls')
            ->join('vnt_moduls', 'vnt_merchant_moduls.modulId', '=', 'vnt_moduls.id')
            ->where('vnt_merchant_moduls.merchantId', $tenant->merchant_type_id)
            ->where('vnt_moduls.status', 1)
            ->get(['vnt_moduls.name', 'vnt_moduls.migration']);

        Log::info('📦 Módulos encontrados', [
            'merchant_type_id' => $tenant->merchant_type_id,
            'modules' => $modules->pluck('name')->toArray()
        ]);

        if ($modules->isEmpty()) {
            Log::warning('⚠️ No hay módulos activos para este merchant type');
            return;
        }

        // Ejecutar migraciones base comunes (si existen)
        $baseMigrationPath = 'tenants/base';
        $baseFullPath = base_path("database/migrations/{$baseMigrationPath}");
        if (File::isDirectory($baseFullPath)) {
            Log::info("📦 Ejecutando migraciones base: {$baseMigrationPath}");
            Artisan::call('migrate', [
                '--database' => 'tenant_migrations',
                '--path' => $baseMigrationPath,
                '--force' => true,
            ]);
            Log::info('✅ Migraciones base ejecutadas', ['output' => Artisan::output()]);
        }

        // Ejecutar migraciones específicas por módulo
        foreach ($modules as $module) {
            if (empty($module->migration)) {
                Log::warning("⚠️ El módulo {$module->name} no tiene ruta de migración definida");
                continue;
            }

            $migrationPath = trim($module->migration, '/');
            $fullPath = base_path("database/migrations/tenants/{$migrationPath}");

            Log::info("🔍 Verificando módulo", [
                'module' => $module->name,
                'migration_config' => $module->migration,
                'full_path' => $fullPath
            ]);

            if (!File::isDirectory($fullPath)) {
                Log::warning("⚠️ Carpeta de migraciones no encontrada", [
                    'path' => $fullPath,
                    'module' => $module->name
                ]);
                continue;
            }

            // Verificar si hay archivos .php en la carpeta
            $migrationFiles = File::glob($fullPath . '/*.php');

            Log::info("📁 Archivos de migración encontrados", [
                'module' => $module->name,
                'path' => $fullPath,
                'files_count' => count($migrationFiles),
                'files' => array_map('basename', $migrationFiles)
            ]);

            if (empty($migrationFiles)) {
                Log::warning("⚠️ No hay archivos de migración en la carpeta", [
                    'path' => $fullPath,
                    'module' => $module->name
                ]);
                continue;
            }

            // Ruta relativa para Artisan (desde database/migrations)
            $relativePath = "tenants/{$migrationPath}";

            Log::info("🔄 Ejecutando migraciones", [
                'module' => $module->name,
                'relative_path' => $relativePath,
                'files_to_migrate' => count($migrationFiles)
            ]);

            try {
                // Crear tabla migrations si no existe
                $this->ensureMigrationsTable('tenant_migrations');

                // Ejecutar cada archivo de migración manualmente
                $migrationsExecuted = 0;
                foreach ($migrationFiles as $migrationFile) {
                    $migrationName = basename($migrationFile, '.php');

                    // Verificar si ya fue ejecutada
                    $exists = DB::connection('tenant_migrations')
                        ->table('migrations')
                        ->where('migration', $migrationName)
                        ->exists();

                    if ($exists) {
                        Log::info("⚠️ Migración ya ejecutada, saltando", [
                            'module' => $module->name,
                            'migration' => $migrationName
                        ]);
                        continue;
                    }

                    // Ejecutar la migración manualmente
                    Log::info("🔄 Ejecutando migración individual", [
                        'module' => $module->name,
                        'migration' => $migrationName,
                        'file' => $migrationFile
                    ]);

                    try {
                        // Ejecutar archivo de migración (maneja tanto clases anónimas como con nombre)
                        $migration = require $migrationFile;

                        if ($migration instanceof \Illuminate\Database\Migrations\Migration) {
                            // Es una clase anónima, ejecutar directamente
                            $migration->up();

                            // Registrar en tabla migrations
                            DB::connection('tenant_migrations')
                                ->table('migrations')
                                ->insert([
                                    'migration' => $migrationName,
                                    'batch' => 1
                                ]);

                            $migrationsExecuted++;
                            Log::info("✅ Migración ejecutada exitosamente", [
                                'module' => $module->name,
                                'migration' => $migrationName,
                                'type' => 'anonymous_class'
                            ]);
                        } else {
                            // Intentar con clase con nombre (método anterior)
                            require_once $migrationFile;
                            $className = $this->getMigrationClassName($migrationFile);

                            if (class_exists($className)) {
                                $namedMigration = new $className();
                                $namedMigration->up();

                                // Registrar en tabla migrations
                                DB::connection('tenant_migrations')
                                    ->table('migrations')
                                    ->insert([
                                        'migration' => $migrationName,
                                        'batch' => 1
                                    ]);

                                $migrationsExecuted++;
                                Log::info("✅ Migración ejecutada exitosamente", [
                                    'module' => $module->name,
                                    'migration' => $migrationName,
                                    'type' => 'named_class',
                                    'class' => $className
                                ]);
                            } else {
                                Log::warning("⚠️ No se pudo ejecutar migración", [
                                    'module' => $module->name,
                                    'migration' => $migrationName,
                                    'reason' => 'Ni clase anónima ni clase con nombre válida'
                                ]);
                            }
                        }
                    } catch (\Exception $migrationError) {
                        Log::error("❌ Error en migración individual", [
                            'module' => $module->name,
                            'migration' => $migrationName,
                            'error' => $migrationError->getMessage(),
                            'trace' => $migrationError->getTraceAsString()
                        ]);
                        // Continuar con la siguiente migración
                    }
                }

                Log::info('✅ Módulo procesado', [
                    'module' => $module->name,
                    'migrations_executed' => $migrationsExecuted,
                    'total_files' => count($migrationFiles)
                ]);

                // Verificar tablas creadas
                $tables = DB::connection('tenant_migrations')->select("SHOW TABLES");
                Log::info('📊 Tablas en BD tenant después del módulo', [
                    'module' => $module->name,
                    'tables_count' => count($tables)
                ]);

            } catch (\Exception $e) {
                Log::error('❌ Error ejecutando migraciones del módulo', [
                    'module' => $module->name,
                    'path' => $relativePath,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }

        // Commit de todas las migraciones y restaurar configuraciones
        DB::connection('tenant_migrations')->commit();
        DB::connection('tenant_migrations')->statement('SET FOREIGN_KEY_CHECKS = 1');
        DB::connection('tenant_migrations')->statement('SET AUTOCOMMIT = 1');

        Log::info('✅ Todas las migraciones completadas exitosamente');

    } catch (\Exception $e) {
        // Rollback en caso de error
        try {
            DB::connection('tenant_migrations')->rollback();
            DB::connection('tenant_migrations')->statement('SET FOREIGN_KEY_CHECKS = 1');
            DB::connection('tenant_migrations')->statement('SET AUTOCOMMIT = 1');
        } catch (\Exception $rollbackError) {
            Log::warning('⚠️ Error durante rollback', ['error' => $rollbackError->getMessage()]);
        }

        Log::error('❌ Error ejecutando migraciones', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);
        throw $e;
    } finally {
        // Restaurar conexión original
        config(['database.default' => $originalConnection]);
        DB::purge('tenant_migrations');
        DB::reconnect($originalConnection);
    }
}















    /**
     * Asegura que existe la tabla migrations en la BD del tenant
     */
    protected function ensureMigrationsTable(string $connection): void
    {
        try {
            DB::connection($connection)->select('SELECT 1 FROM migrations LIMIT 1');
        } catch (\Exception $e) {
            // Crear tabla migrations
            DB::connection($connection)->statement('
                CREATE TABLE migrations (
                    id int unsigned NOT NULL AUTO_INCREMENT,
                    migration varchar(255) NOT NULL,
                    batch int NOT NULL,
                    PRIMARY KEY (id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ');
            Log::info('📋 Tabla migrations creada en tenant');
        }
    }

    /**
     * Extrae el nombre de la clase de migración del archivo
     */
    protected function getMigrationClassName(string $filePath): string
    {
        $content = file_get_contents($filePath);

        // Buscar la declaración de clase
        if (preg_match('/class\s+(\w+)\s+extends/', $content, $matches)) {
            return $matches[1];
        }

        // Si no encuentra, generar nombre basado en el archivo
        $filename = basename($filePath, '.php');

        // Remover timestamp y convertir a PascalCase
        $parts = explode('_', $filename);
        if (count($parts) >= 5) {
            // Remover las primeras 4 partes (timestamp)
            $parts = array_slice($parts, 4);
        }

        return implode('', array_map('ucfirst', $parts));
    }

    /**
     * Asigna un usuario al tenant.
     */
    public function assignUser(Tenant $tenant, User $user, string $role = 'user'): void
    {
        $tenant->users()->attach($user->id, [
            'role' => $role,
            'is_active' => true,
            'last_accessed_at' => now(),
        ]);
    }

    public function removeUser(Tenant $tenant, User $user): void
    {
        $tenant->users()->detach($user->id);
    }

    public function setConnection(Tenant $tenant): void
    {
        // En modo single-tenant centralizado: usar BD "distribuidora" con credenciales principales
        Config::set('database.connections.tenant', [
            'driver' => 'mysql',
            'host' => config('database.connections.mysql.host'),
            'port' => config('database.connections.mysql.port'),
            'database' => 'distribuidora',
            'username' => config('database.connections.mysql.username'),
            'password' => config('database.connections.mysql.password'),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ]);

        DB::purge('tenant');
        DB::reconnect('tenant');
    }

    public function delete(Tenant $tenant, bool $deleteDatabase = false): void
    {
        DB::beginTransaction();
        try {
            if ($deleteDatabase) {
                $this->dropDatabase($tenant);
            }
            $tenant->delete();
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    protected function dropDatabase(Tenant $tenant): void
    {
        DB::statement("DROP DATABASE IF EXISTS `{$tenant->db_name}`");
        Log::info("🗑️ Base de datos eliminada: {$tenant->db_name}");
    }

    public function deactivate(Tenant $tenant): void
    {
        $tenant->update(['is_active' => false]);
    }

    public function activate(Tenant $tenant): void
    {
        $tenant->update(['is_active' => true]);
    }
}
