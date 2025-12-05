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
        // Verificar si hay un tenant activo en la sesión
        $tenantId = session('tenant_id');

        if (!$tenantId) {
            // Si no hay tenant en sesión, redirigir a selección de tenant
            return redirect()->route('tenant.select');
        }

        // Buscar el tenant
        $tenant = Tenant::find($tenantId);

        if (!$tenant || !$tenant->is_active) {
            session()->forget('tenant_id');
            return redirect()->route('tenant.select')->withErrors(['tenant' => 'Tenant no disponible']);
        }

        // Verificar que el usuario autenticado tenga acceso al tenant
        $user = Auth::user();
        if ($user && !$user->hasAccessToTenant($tenantId)) {
            session()->forget('tenant_id');
            return redirect()->route('tenant.select')->withErrors(['tenant' => 'No tiene acceso a este tenant']);
        }

        // Establecer la conexión al tenant
        $this->tenantManager->setConnection($tenant);

        // Inicializar tenancy usando Stancl
        tenancy()->initialize($tenant);

        // Actualizar último acceso
        if ($user) {
            UserTenant::where('user_id', $user->id)
                ->where('tenant_id', $tenantId)
                ->first()
                ?->touchLastAccessed();
        }

        return $next($request);
    }
}