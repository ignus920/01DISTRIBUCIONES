<?php

namespace App\Exports;

use App\Models\Tenant\PettyCash\VntDetailPettyCash;
use App\Models\Auth\Tenant;
use App\Services\Tenant\TenantManager;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class PettyCashDetailExport implements FromView, ShouldAutoSize
{
    protected $pettyCashId;
    protected $search;

    public function __construct($pettyCashId, $search)
    {
        $this->pettyCashId = $pettyCashId;
        $this->search = $search;
    }

    public function view(): View
    {
        $this->ensureTenantConnection();
        
        $details = VntDetailPettyCash::where('pettyCashId', $this->pettyCashId)
            ->where('status', 1)
            ->with('methodPayments', 'reasonsPettyCash')
            ->when($this->search, function ($query) {
                $query->where('invoiceId', 'like', '%' . $this->search . '%')
                      ->orWhere('id', 'like', '%' . $this->search . '%');
            })
            ->orderBy('created_at', 'desc')
            ->get();
            
        return view('livewire.tenant.petty-cash.petty-cash-excel', [
            'details' => $details
        ]);
    }

    private function ensureTenantConnection(): void
    {
        $tenantId = session('tenant_id');

        if (!$tenantId) {
            throw new \Exception('No tenant selected');
        }

        $tenant = Tenant::find($tenantId);

        if (!$tenant) {
            session()->forget('tenant_id');
            throw new \Exception('Invalid tenant');
        }

        // Establecer conexión tenant
        $tenantManager = app(TenantManager::class);
        $tenantManager->setConnection($tenant);

        // Inicializar tenancy
        tenancy()->initialize($tenant);
    }
}