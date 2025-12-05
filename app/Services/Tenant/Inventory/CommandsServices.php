<?php

namespace App\Services\Tenant\Inventory;

use App\Models\Tenant\Items\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use App\Services\Tenant\TenantManager;
use App\Models\Auth\Tenant;

class CommandsServices
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

    public function createCommand(array $data){

        $this->ensureTenantConnection();

        // Crear la comanda
        return Command::create([
            'name' => $data['name'],
            'print_path' => $data['print_path'],
            'status' => $data['status'] ?? 1,
        ]);
    }

    public function getActiveCommands()
    {
        return Command::where('status', 1)
            ->orderBy('name')
            ->get();
    }

    public function commandExists($name)
    {
        return Command::where('name', $name)->exists();
    }
}
