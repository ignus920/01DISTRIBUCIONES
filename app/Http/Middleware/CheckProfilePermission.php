<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\PermissionCatalogService;
use Symfony\Component\HttpFoundation\Response;

class CheckProfilePermission
{
    public function __construct(
        private PermissionCatalogService $permissionService
    ) {}

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission, string $action = 'show'): Response
    {
        $user = $request->user();

        if (!$user) {
            abort(401, 'Usuario no autenticado');
        }

        // Obtener el perfil del usuario
        if (!$user->profile_id) {
            abort(403, 'Usuario sin perfil asignado');
        }

        $hasPermission = $this->checkUserPermission($user->profile_id, $permission, $action);

        if (!$hasPermission) {
            abort(403, "No tienes permisos para realizar esta acción ({$action}) en el módulo {$permission}");
        }

        return $next($request);
    }

    private function checkUserPermission(int $profileId, string $permissionName, string $action): bool
    {
        try {
            $config = $this->permissionService->getUserPermissionConfiguration($profileId);

            if (!isset($config['permissions'][$permissionName])) {
                return false;
            }

            $accessLevels = $config['permissions'][$permissionName]['access_levels'];

            return match ($action) {
                'create' => $accessLevels['create'],
                'edit' => $accessLevels['edit'],
                'delete' => $accessLevels['delete'],
                'show' => $accessLevels['show'],
                default => false
            };

        } catch (\Exception $e) {
            return false;
        }
    }
}