<?php

namespace App\Livewire\Tenant\Items;

use Livewire\Component;
use App\Models\Tenant\Items\InvValues;
use App\Models\Tenant\Items\Items;
use App\Models\Auth\Tenant;
//Servicios
use App\Services\Tenant\TenantManager;

class ManageValues extends Component
{
    public $invValuesId;
    public $values;
    public $type = '';
    public $ItemId;
    public $itemName;
    public $warehouseId = 1;
    public $label;
    public $created_at;

    protected $listeners = ['refreshValues' => '$refresh'];

    public function getValuesItems(){
        return InvValues::where('itemId', $this->ItemId)->get();
    }

    public function updatedValuesItem(){
        $this->dispatch('invValuesItem-changed', $this->ItemId);
    }

    public function mount($ItemId){
        $this->ItemId = $ItemId;
        $this->loadValuesData();
    }

    public function render()
    {
        $this->ensureTenantConnection();
        $values = $this->getValuesItems();
        return view('livewire.tenant.items.manage-values');
    }

    public function loadValuesData()
    {
        $this->ensureTenantConnection();
        $values = InvValues::where('itemId', $this->ItemId)->first();
        
        if ($values) {
            $this->type = $values->type;
        }
        $item = Items::where('id', $this->ItemId);
        $this->itemName = $item->name;
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
