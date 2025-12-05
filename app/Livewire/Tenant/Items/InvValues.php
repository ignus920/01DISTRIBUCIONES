<?php

namespace App\Livewire\Tenant\Items;

use Livewire\Component;
//Modelos
use App\Models\Tenant\Items\InvValues as InvValuesModel;
use App\Models\Auth\Tenant;
//Servicios
use App\Services\Tenant\TenantManager;
use App\Livewire\Tenant\Items\Services\InvValuesService;

class InvValues extends Component
{
    public $invValuesId;
    public $values;
    public $type = '';
    public $ItemId;
    public $warehouseId = 1;
    public $label;
    public $created_at;

    protected $listeners = ['refreshValues' => '$refresh'];
    
    public function getValuesItems(){
        return InvValuesModel::where('itemId', $this->ItemId)->get();
    }

    public function mount($ItemId){
        $this->ItemId = $ItemId;
        $this->loadValuesData();
    }

    public function updatedValuesItem(){
        $this->dispatch('invValuesItem-changed', $this->ItemId);
    }

    public function render()
    {
        $this->ensureTenantConnection();
        $values = $this->getValuesItems();
        return view('livewire.tenant.items.inv-values', [
            'inv_values' => $values
        ]);
    }

    public function loadValuesData()
    {
        $this->ensureTenantConnection();
        $values = InvValuesModel::where('itemId', $this->ItemId)->first();
        if ($values) {
            $this->type = $values->type;
        }
    }

    public function createValueItem()
    {
        $this->validate([
            'typeValue' => 'required',
            'valueItem' => 'required',
            'labelValue' => 'required',
        ]);

        try{
            $invValueService=app(InvValuesService::class);
            $this->ensureTenantConnection();

            $invValues = $invValueService->createValueItem([
                'type' => $this->type,
                'values' => $this->values,
                'itemId' => $this->ItemId,
                'warehouseId' => $this->warehouseId ?? 0,
                'label' => $this->label,
            ]);

            //Resetear formulario
            $this->type = '';
            $this->values = '';
            $this->ItemId = '';
            $this->warehouseId = '';
            $this->label = '';

            // Emitir eventos
            $this->dispatch('invValuesItem-created', ItemId: $invValues->itemId);
            $this->dispatch('refreshValues'); // Refrescar este componente

        }catch(\Exception $e){
            $this->addError('typeValue', 'Error al crear un nuevo valor: ' . $e->getMessage());
        }
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
