<?php

namespace App\Services;

use App\Models\Central\UsrProfile;
use App\Models\Central\UsrPermission;
use App\Models\Central\UsrPermissionProfile;
use Exception;

class PermissionCatalogService
{
    /**
     * Estructura el catálogo de permisos completo
     */
    public function buildCatalog(): array
    {
        $profiles = $this->getActiveProfiles();
        $permissions = $this->getActivePermissions();
        $permissionMatrix = $this->buildPermissionMatrix();

        return [
            'profiles' => $profiles,
            'permissions' => $permissions,
            'permission_matrix' => $permissionMatrix,
            'summary' => $this->buildSummary($profiles, $permissions, $permissionMatrix)
        ];
    }

    /**
     * Configura un perfil con sus permisos automáticamente
     */
    public function configureProfileWithPermissions(int $profileId, array $permissionsConfig): array
    {
        try {
            $profile = UsrProfile::findOrFail($profileId);

            // Limpiar permisos existentes
            UsrPermissionProfile::where('profileId', $profileId)->delete();

            $assignedCount = 0;
            foreach ($permissionsConfig as $permissionConfig) {
                $this->assignPermissionToProfile($profileId, $permissionConfig);
                $assignedCount++;
            }

            return [
                'success' => true,
                'message' => "Perfil '{$profile->name}' configurado con {$assignedCount} permisos",
                'profile' => $profile,
                'assigned_permissions' => $assignedCount
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al configurar el perfil: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtiene la configuración de permisos de un perfil para ser usado al asignar a un usuario
     */
    public function getUserPermissionConfiguration(int $profileId): array
    {
        $profile = UsrProfile::with(['permissionProfiles.permission'])->findOrFail($profileId);

        $permissions = [];
        foreach ($profile->permissionProfiles as $permissionProfile) {
            $permissionName = $permissionProfile->permission->name;
            $permissions[$permissionName] = [
                'id' => $permissionProfile->permission->id,
                'name' => $permissionName,
                'access_levels' => [
                    'create' => $permissionProfile->canCreate(),
                    'delete' => $permissionProfile->canDelete(),
                    'edit' => $permissionProfile->canEdit(),
                    'show' => $permissionProfile->canShow()
                ]
            ];
        }

        return [
            'profile' => [
                'id' => $profile->id,
                'name' => $profile->name,
                'alias' => $profile->alias
            ],
            'permissions' => $permissions,
            'total_permissions' => count($permissions)
        ];
    }

    /**
     * Compara dos perfiles y sus diferencias en permisos
     */
    public function compareProfiles(int $profileId1, int $profileId2): array
    {
        $profile1Config = $this->getUserPermissionConfiguration($profileId1);
        $profile2Config = $this->getUserPermissionConfiguration($profileId2);

        $comparison = [
            'profile1' => $profile1Config['profile'],
            'profile2' => $profile2Config['profile'],
            'common_permissions' => [],
            'profile1_only' => [],
            'profile2_only' => [],
            'different_access_levels' => []
        ];

        $permissions1 = array_keys($profile1Config['permissions']);
        $permissions2 = array_keys($profile2Config['permissions']);

        // Permisos comunes
        $common = array_intersect($permissions1, $permissions2);
        foreach ($common as $permission) {
            $access1 = $profile1Config['permissions'][$permission]['access_levels'];
            $access2 = $profile2Config['permissions'][$permission]['access_levels'];

            if ($access1 == $access2) {
                $comparison['common_permissions'][] = [
                    'permission' => $permission,
                    'access_levels' => $access1
                ];
            } else {
                $comparison['different_access_levels'][] = [
                    'permission' => $permission,
                    'profile1_access' => $access1,
                    'profile2_access' => $access2
                ];
            }
        }

        // Permisos únicos del perfil 1
        $unique1 = array_diff($permissions1, $permissions2);
        foreach ($unique1 as $permission) {
            $comparison['profile1_only'][] = [
                'permission' => $permission,
                'access_levels' => $profile1Config['permissions'][$permission]['access_levels']
            ];
        }

        // Permisos únicos del perfil 2
        $unique2 = array_diff($permissions2, $permissions1);
        foreach ($unique2 as $permission) {
            $comparison['profile2_only'][] = [
                'permission' => $permission,
                'access_levels' => $profile2Config['permissions'][$permission]['access_levels']
            ];
        }

        return $comparison;
    }

    /**
     * Genera un preset de permisos basado en el tipo de perfil
     */
    public function generatePermissionPreset(string $profileType): array
    {
        $presets = [
            'super_admin' => $this->getSuperAdminPreset(),
            'admin' => $this->getAdminPreset(),
            'employee' => $this->getEmployeePreset(),
            'pos_seller' => $this->getPosSeller(),
            'warehouse' => $this->getWarehousePreset(),
            'accounting' => $this->getAccountingPreset()
        ];

        return $presets[$profileType] ?? [];
    }

    /**
     * Métodos privados de apoyo
     */
    private function getActiveProfiles(): array
    {
        return UsrProfile::where('status', 1)
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    private function getActivePermissions(): array
    {
        return UsrPermission::where('status', 1)
            ->orderBy('name')
            ->get()
            ->toArray();
    }

    private function buildPermissionMatrix(): array
    {
        $matrix = [];
        $assignments = UsrPermissionProfile::with(['profile', 'permission'])
            ->whereHas('profile', function($query) {
                $query->where('status', 1);
            })
            ->whereHas('permission', function($query) {
                $query->where('status', 1);
            })
            ->get();

        foreach ($assignments as $assignment) {
            $profileName = $assignment->profile->name;
            $permissionName = $assignment->permission->name;

            if (!isset($matrix[$profileName])) {
                $matrix[$profileName] = [];
            }

            $matrix[$profileName][$permissionName] = [
                'create' => $assignment->canCreate(),
                'delete' => $assignment->canDelete(),
                'edit' => $assignment->canEdit(),
                'show' => $assignment->canShow()
            ];
        }

        return $matrix;
    }

    private function buildSummary(array $profiles, array $permissions, array $permissionMatrix): array
    {
        $summary = [
            'total_profiles' => count($profiles),
            'total_permissions' => count($permissions),
            'profile_stats' => []
        ];

        foreach ($profiles as $profile) {
            $profileName = $profile['name'];
            $profilePermissions = $permissionMatrix[$profileName] ?? [];

            $summary['profile_stats'][] = [
                'profile' => $profileName,
                'assigned_permissions' => count($profilePermissions),
                'permission_coverage' => count($permissions) > 0 ? round((count($profilePermissions) / count($permissions)) * 100, 2) : 0
            ];
        }

        return $summary;
    }

    private function assignPermissionToProfile(int $profileId, array $permissionConfig): void
    {
        UsrPermissionProfile::create([
            'profileId' => $profileId,
            'permissionId' => $permissionConfig['permissionId'],
            'creater' => $permissionConfig['create'] ?? false,
            'deleter' => $permissionConfig['delete'] ?? false,
            'editer' => $permissionConfig['edit'] ?? false,
            'show' => $permissionConfig['show'] ?? false
        ]);
    }

    // Presets de permisos por tipo de perfil
    private function getSuperAdminPreset(): array
    {
        $permissions = UsrPermission::where('status', 1)->get();
        $preset = [];

        foreach ($permissions as $permission) {
            $preset[] = [
                'permissionId' => $permission->id,
                'create' => true,
                'delete' => true,
                'edit' => true,
                'show' => true
            ];
        }

        return $preset;
    }

    private function getAdminPreset(): array
    {
        $permissions = UsrPermission::whereIn('name', [
            'Ventas', 'Inventario', 'Reportes', 'Compras', 'Cartera', 'Usuarios', 'Parametros'
        ])->get();

        $preset = [];
        foreach ($permissions as $permission) {
            $preset[] = [
                'permissionId' => $permission->id,
                'create' => true,
                'delete' => false, // Limitado delete para admin
                'edit' => true,
                'show' => true
            ];
        }

        return $preset;
    }

    private function getEmployeePreset(): array
    {
        $permissions = UsrPermission::whereIn('name', [
            'Ventas', 'Inventario', 'Reportes'
        ])->get();

        $preset = [];
        foreach ($permissions as $permission) {
            $preset[] = [
                'permissionId' => $permission->id,
                'create' => in_array($permission->name, ['Ventas']),
                'delete' => false,
                'edit' => in_array($permission->name, ['Ventas']),
                'show' => true
            ];
        }

        return $preset;
    }

    private function getPosSeller(): array
    {
        $permissions = UsrPermission::whereIn('name', [
            'Ventas', 'Caja', 'Inventario'
        ])->get();

        $preset = [];
        foreach ($permissions as $permission) {
            $preset[] = [
                'permissionId' => $permission->id,
                'create' => in_array($permission->name, ['Ventas', 'Caja']),
                'delete' => false,
                'edit' => in_array($permission->name, ['Ventas']),
                'show' => true
            ];
        }

        return $preset;
    }

    private function getWarehousePreset(): array
    {
        $permissions = UsrPermission::whereIn('name', [
            'Inventario', 'Despachos', 'Compras'
        ])->get();

        $preset = [];
        foreach ($permissions as $permission) {
            $preset[] = [
                'permissionId' => $permission->id,
                'create' => true,
                'delete' => false,
                'edit' => true,
                'show' => true
            ];
        }

        return $preset;
    }

    private function getAccountingPreset(): array
    {
        $permissions = UsrPermission::whereIn('name', [
            'Cartera', 'Reportes', 'Compras'
        ])->get();

        $preset = [];
        foreach ($permissions as $permission) {
            $preset[] = [
                'permissionId' => $permission->id,
                'create' => in_array($permission->name, ['Cartera']),
                'delete' => false,
                'edit' => true,
                'show' => true
            ];
        }

        return $preset;
    }
}