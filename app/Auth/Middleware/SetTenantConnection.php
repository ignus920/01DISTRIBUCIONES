<?php

namespace App\Auth\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Auth\Tenant;
use App\Models\Auth\UserTenant;
use App\Services\Tenant\TenantManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
        // MODO SINGLE-TENANT: Usar tenant basado en el company_id del usuario
        $user = Auth::user();
        $tenantId = session('tenant_id');

        // Si hay un usuario autenticado, determinar el tenant por su company_id
        if ($user && !$tenantId) {
            $userCompanyId = $this->getUserCompanyId($user);

            if ($userCompanyId) {
                // Buscar o crear tenant para esta empresa
                $tenant = Tenant::where('company_id', $userCompanyId)->first();

                if (!$tenant) {
                    // Crear tenant específico para la empresa del usuario
                    $tenant = Tenant::create([
                        'id' => 'company_' . $userCompanyId,
                        'name' => 'Empresa ID: ' . $userCompanyId,
                        'email' => $user->email,
                        'domain' => 'empresa' . $userCompanyId . '.local',
                        'db_name' => 'company_' . $userCompanyId . '_tenant',
                        'company_id' => $userCompanyId,
                        'is_active' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $tenantId = $tenant->id;
                session(['tenant_id' => $tenantId]);
            } else {
                // Fallback: usar tenant por defecto si no se puede determinar company_id
                $defaultTenant = Tenant::where('is_active', true)->first();

                if (!$defaultTenant) {
                    $defaultTenant = Tenant::create([
                        'id' => 'default',
                        'name' => 'Distribuidora Principal',
                        'email' => $user ? $user->email : 'admin@distribuidora.local',
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

    /**
     * Obtener el company_id del usuario autenticado
     */
    protected function getUserCompanyId($user)
    {
        // Si el usuario tiene un contact_id, obtener el company_id desde ahí
        if ($user->contact_id) {
            $contact = DB::table('vnt_contacts')
                ->where('id', $user->contact_id)
                ->first();

            if ($contact && isset($contact->warehouseId)) {
                $warehouse = DB::table('vnt_warehouses')
                    ->where('id', $contact->warehouseId)
                    ->first();

                return $warehouse ? $warehouse->companyId : null;
            }
        }

        // Método alternativo: buscar en vnt_companies por email
        $company = DB::table('vnt_companies')
            ->join('vnt_warehouses', 'vnt_companies.id', '=', 'vnt_warehouses.companyId')
            ->join('vnt_contacts', 'vnt_warehouses.id', '=', 'vnt_contacts.warehouseId')
            ->where('vnt_contacts.email', $user->email)
            ->select('vnt_companies.id as company_id')
            ->first();

        return $company ? $company->company_id : null;
    }
}