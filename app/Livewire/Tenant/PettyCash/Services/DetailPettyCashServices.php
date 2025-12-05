<?php

namespace App\Livewire\Tenant\PettyCash\Services;

use App\Models\Tenant\PettyCash\VntDetailPettyCash;
use App\Services\Tenant\TenantManager;
use App\Models\Auth\Tenant;
use Carbon\Carbon;

class DetailPettyCashServices
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

    public function createMovement(array $data){

        $this->ensureTenantConnection();

        // Crear el movimiento
        return VntDetailPettyCash::create([
            'status' => 1,
            'value' => $data['value'],
            'created_at' => Carbon::now(),
            'pettyCashId' => $data['pettyCashId'],
            'reasonPettyCashId' => $data['reasonPettyCashId'],
            'methodPaymentId' => $data['methodPaymentId'],
            'observations' => $data['observations'],
        ]);
    }
}