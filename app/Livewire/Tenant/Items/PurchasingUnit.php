<?php

namespace App\Livewire\Tenant\Items;

use Livewire\Component;
use App\Models\Tenant\Items\UnitMeasurements as UnitMeasurementsModel;
use App\Services\Tenant\TenantManager;
use App\Models\Auth\Tenant;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;

class PurchasingUnit extends Component
{
    public $purchaseUnitId = '';
    public $name = 'purchaseUnitId';
    public $placeholder = 'Seleccione una unidad de medida';
    public $label = 'Unidad de compra'; 
    public $required = true;
    public $showLabel = false;
    public $class = 'mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500';
    public $index = null;
    public $search = '';

    public function mount($purchaseUnitId = '', $name = 'purchaseUnitId', $placeholder = 'Seleccione una unidad de medida', $label = 'Unidad de compra', $required = true, $showLabel = true, $class = null)
    {
        $this->purchaseUnitId = $purchaseUnitId;
        $this->name = $name;
        $this->placeholder = $placeholder;
        $this->label = $label;
        $this->required = $required;
        $this->showLabel = $showLabel;
        if ($class) {
            $this->class = $class;
        }

        $this->purchaseUnitId = $purchaseUnitId ?: 35;
        $this->dispatch('purchase-unit-changed', $this->purchaseUnitId);
    }

    public function updatedPurchaseUnitId(){
        \Illuminate\Support\Facades\Log::info('PurchaseUnit: updatedPurchaseUnitId hook triggered', [
            'purchaseUnitId' => $this->purchaseUnitId,
            'index' => $this->index,
            'name' => $this->name
        ]);
        if($this->index !== null){
            $this->dispatch('purchase-unit-changed', purchaseUnitId: $this->purchaseUnitId, index: $this->index);
        }else{
            $this->dispatch('purchase-unit-changed', $this->purchaseUnitId);
        }

        \Illuminate\Support\Facades\Log::info('PurchaseUnit: purchase-unit-changed event dispatched', [
            'purchaseUnitId' => $this->purchaseUnitId,
            'index' => $this->index
        ]);
    }

    #[On('validate-purchase-unit')]
    public function validatePurchaseUnit()
    {
        $this->validate([
            'purchaseUnitId' => 'required|exists:unit_measurements,id',
        ]);
        $this->dispatch('purchase-unit-validated', $this->purchaseUnitId);
    }

    public function selectPurchaseUnit($unitId)
    {
        \Illuminate\Support\Facades\Log::info('PurchaseUnit: selectPurchaseUnit called', [
            'id' => $unitId,
            'index' => $this->index,
            'name' => $this->name
        ]);

        $this->purchaseUnitId = $unitId;
        $this->search = '';

        if($this->index !== null){
            $this->dispatch('purchase-unit-changed', purchaseUnitId: $this->purchaseUnitId, index: $this->index);
        }else{
            $this->dispatch('purchase-unit-changed', $this->purchaseUnitId);
        }
    }

    #[Computed]
    public function selectedPurchaseUnitName(){
        if (!$this->purchaseUnitId) return null;
        return UnitMeasurementsModel::find($this->purchaseUnitId)?->description;
    }

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

    #[Computed]
    public function purchaseUnits(){
        $query = UnitMeasurementsModel::where('status', 1);

        if(!empty($this->search)){
            $query->where('description', 'like', '%'.$this->search.'%');
        }

        return $query->select('id', 'description')
            ->orderBy('description')
            ->limit(50)
            ->get();
    }

    public function render()
    {
        return view('livewire.tenant.items.purchasing-unit',[
            'showLabel' => $this->showLabel
        ]);
    }
}
