<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Central\UsrProfile;
use App\Models\Central\UsrPermission;
use App\Models\Central\UsrPermissionProfile;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class PermissionController extends Controller
{
    /**
     * Obtiene el catálogo completo de permisos organizados por perfil
     */
    public function getCatalog(): JsonResponse
    {
        try {
            $profiles = UsrProfile::with(['permissions' => function($query) {
                $query->where('status', 1);
            }])->where('status', 1)->get();

            $permissions = UsrPermission::where('status', 1)->get();

            $catalog = [
                'profiles' => $profiles,
                'permissions' => $permissions,
                'assignments' => $this->getPermissionMatrix()
            ];

            return response()->json([
                'success' => true,
                'data' => $catalog
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el catálogo de permisos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Asigna un permiso específico a un perfil con los niveles de acceso especificados
     */
    public function assignPermissionToProfile(Request $request): JsonResponse
    {
        $request->validate([
            'profileId' => 'required|integer|exists:usr_profiles,id',
            'permissionId' => 'required|integer|exists:usr_permissions,id',
            'creater' => 'boolean',
            'deleter' => 'boolean',
            'editer' => 'boolean',
            'show' => 'boolean'
        ]);

        try {
            // Verificar si ya existe la asignación
            $existing = UsrPermissionProfile::where('profileId', $request->profileId)
                ->where('permissionId', $request->permissionId)
                ->first();

            if ($existing) {
                // Actualizar permisos existentes
                $existing->update([
                    'creater' => $request->get('creater', false),
                    'deleter' => $request->get('deleter', false),
                    'editer' => $request->get('editer', false),
                    'show' => $request->get('show', false)
                ]);

                $message = 'Permisos actualizados correctamente';
            } else {
                // Crear nueva asignación
                UsrPermissionProfile::create([
                    'profileId' => $request->profileId,
                    'permissionId' => $request->permissionId,
                    'creater' => $request->get('creater', false),
                    'deleter' => $request->get('deleter', false),
                    'editer' => $request->get('editer', false),
                    'show' => $request->get('show', false)
                ]);

                $message = 'Permiso asignado correctamente';
            }

            return response()->json([
                'success' => true,
                'message' => $message
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al asignar el permiso',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Asigna múltiples permisos a un perfil
     */
    public function assignMultiplePermissions(Request $request): JsonResponse
    {
        $request->validate([
            'profileId' => 'required|integer|exists:usr_profiles,id',
            'permissions' => 'required|array',
            'permissions.*.permissionId' => 'required|integer|exists:usr_permissions,id',
            'permissions.*.creater' => 'boolean',
            'permissions.*.deleter' => 'boolean',
            'permissions.*.editer' => 'boolean',
            'permissions.*.show' => 'boolean'
        ]);

        try {
            // Eliminar permisos existentes para este perfil
            UsrPermissionProfile::where('profileId', $request->profileId)->delete();

            // Asignar nuevos permisos
            foreach ($request->permissions as $permission) {
                UsrPermissionProfile::create([
                    'profileId' => $request->profileId,
                    'permissionId' => $permission['permissionId'],
                    'creater' => $permission['creater'] ?? false,
                    'deleter' => $permission['deleter'] ?? false,
                    'editer' => $permission['editer'] ?? false,
                    'show' => $permission['show'] ?? false
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Permisos asignados correctamente al perfil'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al asignar los permisos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remueve un permiso específico de un perfil
     */
    public function removePermissionFromProfile(Request $request): JsonResponse
    {
        $request->validate([
            'profileId' => 'required|integer|exists:usr_profiles,id',
            'permissionId' => 'required|integer|exists:usr_permissions,id'
        ]);

        try {
            UsrPermissionProfile::where('profileId', $request->profileId)
                ->where('permissionId', $request->permissionId)
                ->delete();

            return response()->json([
                'success' => true,
                'message' => 'Permiso removido correctamente'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al remover el permiso',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene los permisos de un perfil específico
     */
    public function getProfilePermissions($profileId): JsonResponse
    {
        try {
            $profile = UsrProfile::with(['permissions' => function($query) {
                $query->where('status', 1);
            }])->findOrFail($profileId);

            return response()->json([
                'success' => true,
                'data' => [
                    'profile' => $profile,
                    'permissions' => $profile->permissions
                ]
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los permisos del perfil',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clona los permisos de un perfil a otro
     */
    public function cloneProfilePermissions(Request $request): JsonResponse
    {
        $request->validate([
            'sourceProfileId' => 'required|integer|exists:usr_profiles,id',
            'targetProfileId' => 'required|integer|exists:usr_profiles,id'
        ]);

        try {
            // Obtener permisos del perfil origen
            $sourcePermissions = UsrPermissionProfile::where('profileId', $request->sourceProfileId)->get();

            // Eliminar permisos existentes del perfil destino
            UsrPermissionProfile::where('profileId', $request->targetProfileId)->delete();

            // Copiar permisos al perfil destino
            foreach ($sourcePermissions as $permission) {
                UsrPermissionProfile::create([
                    'profileId' => $request->targetProfileId,
                    'permissionId' => $permission->permissionId,
                    'creater' => $permission->creater,
                    'deleter' => $permission->deleter,
                    'editer' => $permission->editer,
                    'show' => $permission->show
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Permisos clonados correctamente'
            ]);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al clonar los permisos',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene una matriz de permisos (perfiles vs permisos)
     */
    private function getPermissionMatrix(): array
    {
        $matrix = [];
        $assignments = UsrPermissionProfile::with(['profile', 'permission'])->get();

        foreach ($assignments as $assignment) {
            $profileId = $assignment->profileId;
            $permissionId = $assignment->permissionId;

            if (!isset($matrix[$profileId])) {
                $matrix[$profileId] = [
                    'profile' => $assignment->profile,
                    'permissions' => []
                ];
            }

            $matrix[$profileId]['permissions'][$permissionId] = [
                'permission' => $assignment->permission,
                'access' => [
                    'creater' => $assignment->creater,
                    'deleter' => $assignment->deleter,
                    'editer' => $assignment->editer,
                    'show' => $assignment->show
                ]
            ];
        }

        return $matrix;
    }
}