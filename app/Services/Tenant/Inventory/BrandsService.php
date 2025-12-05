<?php

namespace App\Services\Tenant\Inventory;

use App\Models\Tenant\Items\Brand;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Services\Tenant\TenantManager;
use App\Models\Auth\Tenant;

class BrandsService
{
    private function ensureTenantConnection()
    {
        $tenantId = session('tenant_id');

        if (!$tenantId) {
            return redirect()->route('tenant.select');
        }

        $tenant = Tenant::find($tenantId);

        if (!$tenant) {
            session()->forget('tenant_id');
            return redirect()->route('tenant.select');
        }

        // Establecer conexión tenant
        $tenantManager = app(TenantManager::class);
        $tenantManager->setConnection($tenant);

        // Inicializar tenancy
        tenancy()->initialize($tenant);
    }

    public function createBrand(array $data){

        $this->ensureTenantConnection();

        // Crear la marca
        return Brand::create([
            'name' => $data['name'],
            'status' => $data['status'] ?? 1,
        ]);
    }

    public function getActiveBrands()
    {
        return Brand::where('status', 1)
            ->orderBy('name')
            ->get();
    }

    public function BrandExists($name)
    {
        return Brand::where('name', $name)->exists();
    }
}
