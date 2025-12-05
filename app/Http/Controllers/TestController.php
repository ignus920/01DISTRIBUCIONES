<?php

namespace App\Http\Controllers;

use App\Models\Auth\Tenant;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TestController extends Controller
{
    /**
     * Establece un tenant en la sesión para pruebas
     */
    public function setTenant(Request $request): JsonResponse
    {
        $tenantId = $request->get('tenant_id');

        if (!$tenantId) {
            return response()->json([
                'error' => 'tenant_id requerido'
            ], 400);
        }

        $tenant = Tenant::find($tenantId);

        if (!$tenant) {
            return response()->json([
                'error' => 'Tenant no encontrado'
            ], 404);
        }

        session(['tenant_id' => $tenantId]);

        return response()->json([
            'success' => true,
            'message' => 'Tenant establecido en sesión',
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'db_name' => $tenant->db_name
            ]
        ]);
    }

    /**
     * Ver tenants disponibles
     */
    public function listTenants(): JsonResponse
    {
        $tenants = Tenant::active()->select('id', 'name', 'email', 'db_name')->get();

        return response()->json([
            'success' => true,
            'tenants' => $tenants
        ]);
    }

    /**
     * Ver información de la sesión actual
     */
    public function sessionInfo(): JsonResponse
    {
        return response()->json([
            'tenant_id' => session('tenant_id'),
            'session_id' => session()->getId(),
            'all_session' => session()->all()
        ]);
    }
}