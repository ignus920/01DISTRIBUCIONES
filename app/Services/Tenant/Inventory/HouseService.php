<?php

namespace App\Services\Tenant\Inventory;

use App\Models\Tenant\Items\House;
use App\Services\Tenant\TenantManager;
use App\Models\Auth\Tenant;

class HouseService
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

    public function createHouse(array $data){

        $this->ensureTenantConnection();

        // Crear la categoría
        return House::create([
            'name' => $data['name'],
            'status' => $data['status'] ?? 1,
            // Agregar otros campos si es necesario
        ]);
    }

    public function getActiveCategories()
    {
        return House::where('status', 1)
            ->orderBy('name')
            ->get();
    }

    public function houseExists($name)
    {
        return House::where('name', $name)->exists();
    }

    public function getCategory($id)
    {
        return House::findOrFail($id);
    }
}
