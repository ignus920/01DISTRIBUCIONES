<?php

namespace App\Livewire\Tenant\PettyCash;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Auth\Tenant;
use App\Models\Tenant\PettyCash\VntReconciliations;
use App\Models\Tenant\PettyCash\VntDetailReconciliations;
use App\Services\Tenant\TenantManager;
use App\Traits\HasCompanyConfiguration;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class UnreconciledReconciliations extends Component
{
    use WithPagination, HasCompanyConfiguration;

    public $pettyCash_id;
    public $showDetail = false;
    public $selectedReconciliation = null;
    public $search = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    public $perPage = 10;

    protected $listeners = ['refreshReconciliations' => '$refresh'];

    public function boot()
    {
        $this->ensureTenantConnection();
        $this->initializeCompanyConfiguration();
    }

    public function mount($pettyCash_id)
    {
        $this->pettyCash_id = $pettyCash_id;
        $this->clearConfigurationCache();
    }

    public function render()
    {
        $this->ensureTenantConnection();

        $reconciliations = VntReconciliations::query()
            ->where('pettyCashId', $this->pettyCash_id)
            ->where('reconciliation', 0)
            ->with('pettyCash')
            ->when($this->search, function ($query) {
                $query->where('id', 'like', '%' . $this->search . '%')
                    ->orWhere('observations', 'like', '%' . $this->search . '%');
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.tenant.petty-cash.unreconciled-reconciliations', [
            'reconciliations' => $reconciliations
        ]);
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirection = 'asc';
        }

        $this->sortField = $field;
        $this->resetPage();
    }

    public function viewDetail($reconciliationId)
    {
        $this->ensureTenantConnection();
        $this->selectedReconciliation = $reconciliationId;
        $this->showDetail = true;
    }

    public function closeDetail()
    {
        $this->showDetail = false;
        $this->selectedReconciliation = null;
    }

    public function markAsReconciled($reconciliationId)
    {
        try {
            $this->ensureTenantConnection();

            $reconciliation = VntReconciliations::findOrFail($reconciliationId);
            $reconciliation->update([
                'reconciliation' => 1,
                'updated_at' => Carbon::now()
            ]);

            session()->flash('message', 'Reconciliación marcada como completada exitosamente');
            $this->dispatch('refreshReconciliations');
            $this->closeDetail();
        } catch (\Exception $e) {
            Log::error('Error marking reconciliation as complete: ' . $e->getMessage());
            session()->flash('error', 'Error al marcar la reconciliación como completada: ' . $e->getMessage());
        }
    }

    public function getReconciliationDetails($reconciliationId)
    {
        $this->ensureTenantConnection();

        return VntDetailReconciliations::where('reconciliationId', $reconciliationId)
            ->with('methodPayments')
            ->get();
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

        $tenantManager = app(TenantManager::class);
        $tenantManager->setConnection($tenant);

        tenancy()->initialize($tenant);
    }
}
