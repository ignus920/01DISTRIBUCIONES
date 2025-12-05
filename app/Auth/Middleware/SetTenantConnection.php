<?php

namespace App\Auth\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Auth\Tenant;
use App\Models\Auth\UserTenant;
use App\Services\Tenant\TenantManager;
use Illuminate\Support\Facades\Auth;

class SetTenantConnection
{
    protected $tenantManager;

    public function __construct(TenantManager $tenantManager)
    {
        $this->tenantManager = $tenantManager;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // MODO SINGLE-TENANT: Usar tenant por defecto automáticamente
        $tenantId = session('tenant_id');

        // Si no hay tenant en sesión, usar el tenant por defecto
        if (!$tenantId) {
            $defaultTenant = Tenant::where('is_active', true)->first();

            if (!$defaultTenant) {
                // Crear tenant por defecto si no existe
                $defaultTenant = Tenant::create([
                    'id' => 'default',
                    'name' => 'Distribuidora Principal',
                    'domain' => 'distribuidora.local',
                    'db_name' => 'company_1_default',
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            $tenantId = $defaultTenant->id;
            session(['tenant_id' => $tenantId]);
        }

        // Buscar el tenant
        $tenant = Tenant::find($tenantId);

        if (!$tenant || !$tenant->is_active) {
            // En modo single-tenant, usar el primer tenant disponible
            $tenant = Tenant::where('is_active', true)->first();
            if ($tenant) {
                session(['tenant_id' => $tenant->id]);
            } else {
                return redirect()->route('tenant.select')->withErrors(['tenant' => 'No hay tenants disponibles']);
            }
        }

        // EN MODO SINGLE-TENANT, SALTAMOS LA VERIFICACIÓN DE ACCESO
        // CÓDIGO ORIGINAL COMENTADO:
        // // Verificar que el usuario autenticado tenga acceso al tenant
        // $user = Auth::user();
        // if ($user && !$user->hasAccessToTenant($tenantId)) {
        //     session()->forget('tenant_id');
        //     return redirect()->route('tenant.select')->withErrors(['tenant' => 'No tiene acceso a este tenant']);
        // }

        // Establecer la conexión al tenant
        $this->tenantManager->setConnection($tenant);

        // Inicializar tenancy usando Stancl
        tenancy()->initialize($tenant);

        // Actualizar último acceso (opcional en modo single-tenant)
        $user = Auth::user();
        if ($user) {
            UserTenant::where('user_id', $user->id)
                ->where('tenant_id', $tenantId)
                ->first()
                ?->touchLastAccessed();
        }

        return $next($request);
    }
}