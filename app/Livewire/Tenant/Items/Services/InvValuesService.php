<?php

namespace App\Livewire\Tenant\Items\Services;

use App\Models\Tenant\Items\InvValues;
use App\Services\Tenant\TenantManager;
use App\Models\Auth\Tenant;
use Carbon\Carbon;

class InvValuesService
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

    public function createValueItem(array $data){
        $this->ensureTenantConnection();

        //Crear el valor del Item
        return InvValues::create([
            'date' => Carbon::now(),
            'values' => $data['values'] ?? 0,
            'type' => $data['type'],
            'itemId' => $data['itemId'],
            'warehouseId' => $data['warehouseId'],
            'label' => $data['label'],
            'created_at' => Carbon::now(),
        ]);
    }
}
