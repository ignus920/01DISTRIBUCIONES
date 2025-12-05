<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PermissionCatalogService;
use App\Models\Central\UsrProfile;
use App\Models\Central\UsrPermission;

class ConfigureProfilePermissionsCommand extends Command
{
    protected $signature = 'permissions:configure-profile
                            {profile_id : ID del perfil a configurar}
                            {--preset= : Preset de permisos (super_admin, admin, employee, pos_seller, warehouse, accounting)}
                            {--show-catalog : Muestra el catálogo de permisos actual}';

    protected $description = 'Configura los permisos de un perfil usando presets o el catálogo de permisos';

    public function handle(PermissionCatalogService $permissionService)
    {
        if ($this->option('show-catalog')) {
            $this->showPermissionsCatalog($permissionService);
            return;
        }

        $profileId = $this->argument('profile_id');
        $preset = $this->option('preset');

        // Verificar que el perfil existe
        $profile = UsrProfile::find($profileId);
        if (!$profile) {
            $this->error("No se encontró el perfil con ID: {$profileId}");
            return Command::FAILURE;
        }

        $this->info("Configurando permisos para el perfil: {$profile->name}");

        if ($preset) {
            $this->configureWithPreset($permissionService, $profileId, $preset);
        } else {
            $this->configureInteractively($permissionService, $profileId);
        }

        return Command::SUCCESS;
    }

    private function configureWithPreset(PermissionCatalogService $service, int $profileId, string $preset): void
    {
        $this->info("Aplicando preset: {$preset}");

        $permissionsConfig = $service->generatePermissionPreset($preset);

        if (empty($permissionsConfig)) {
            $this->error("Preset '{$preset}' no encontrado. Presets disponibles: super_admin, admin, employee, pos_seller, warehouse, accounting");
            return;
        }

        $result = $service->configureProfileWithPermissions($profileId, $permissionsConfig);

        if ($result['success']) {
            $this->info($result['message']);
            $this->showProfileConfiguration($service, $profileId);
        } else {
            $this->error($result['message']);
        }
    }

    private function configureInteractively(PermissionCatalogService $service, int $profileId): void
    {
        $this->info("Configuración interactiva de permisos");

        $permissions = UsrPermission::where('status', 1)->get();
        $permissionsConfig = [];

        foreach ($permissions as $permission) {
            $this->line("\n--- Configurando permiso: {$permission->name} ---");

            if ($this->confirm("¿Asignar el permiso '{$permission->name}' a este perfil?")) {
                $config = [
                    'permissionId' => $permission->id,
                    'create' => $this->confirm('¿Permitir crear?', false),
                    'edit' => $this->confirm('¿Permitir editar?', false),
                    'delete' => $this->confirm('¿Permitir eliminar?', false),
                    'show' => $this->confirm('¿Permitir ver?', true)
                ];

                $permissionsConfig[] = $config;

                $this->info("Configuración guardada: " . json_encode($config));
            }
        }

        if (empty($permissionsConfig)) {
            $this->warn("No se configuró ningún permiso.");
            return;
        }

        $result = $service->configureProfileWithPermissions($profileId, $permissionsConfig);

        if ($result['success']) {
            $this->info($result['message']);
            $this->showProfileConfiguration($service, $profileId);
        } else {
            $this->error($result['message']);
        }
    }

    private function showPermissionsCatalog(PermissionCatalogService $service): void
    {
        $this->info("=== CATÁLOGO DE PERMISOS ===\n");

        $catalog = $service->buildCatalog();

        // Mostrar perfiles
        $this->line("--- PERFILES DISPONIBLES ---");
        $headers = ['ID', 'Nombre', 'Alias', 'Estado'];
        $rows = [];

        foreach ($catalog['profiles'] as $profile) {
            $rows[] = [
                $profile['id'],
                $profile['name'],
                $profile['alias'] ?? 'N/A',
                $profile['status'] ? 'Activo' : 'Inactivo'
            ];
        }

        $this->table($headers, $rows);

        // Mostrar permisos
        $this->line("\n--- PERMISOS DISPONIBLES ---");
        $headers = ['ID', 'Nombre', 'Estado'];
        $rows = [];

        foreach ($catalog['permissions'] as $permission) {
            $rows[] = [
                $permission['id'],
                $permission['name'],
                $permission['status'] ? 'Activo' : 'Inactivo'
            ];
        }

        $this->table($headers, $rows);

        // Mostrar resumen
        $this->line("\n--- RESUMEN ---");
        $summary = $catalog['summary'];
        $this->info("Total de perfiles: {$summary['total_profiles']}");
        $this->info("Total de permisos: {$summary['total_permissions']}");

        $this->line("\n--- COBERTURA POR PERFIL ---");
        $headers = ['Perfil', 'Permisos Asignados', 'Cobertura %'];
        $rows = [];

        foreach ($summary['profile_stats'] as $stat) {
            $rows[] = [
                $stat['profile'],
                $stat['assigned_permissions'],
                $stat['permission_coverage'] . '%'
            ];
        }

        $this->table($headers, $rows);
    }

    private function showProfileConfiguration(PermissionCatalogService $service, int $profileId): void
    {
        $this->line("\n=== CONFIGURACIÓN ACTUAL DEL PERFIL ===");

        $config = $service->getUserPermissionConfiguration($profileId);

        $this->info("Perfil: {$config['profile']['name']}");
        $this->info("Total de permisos: {$config['total_permissions']}");

        if (!empty($config['permissions'])) {
            $this->line("\n--- PERMISOS ASIGNADOS ---");
            $headers = ['Permiso', 'Crear', 'Editar', 'Eliminar', 'Ver'];
            $rows = [];

            foreach ($config['permissions'] as $permission) {
                $access = $permission['access_levels'];
                $rows[] = [
                    $permission['name'],
                    $access['create'] ? '✓' : '✗',
                    $access['edit'] ? '✓' : '✗',
                    $access['delete'] ? '✓' : '✗',
                    $access['show'] ? '✓' : '✗'
                ];
            }

            $this->table($headers, $rows);
        } else {
            $this->warn("No se encontraron permisos asignados a este perfil.");
        }
    }
}