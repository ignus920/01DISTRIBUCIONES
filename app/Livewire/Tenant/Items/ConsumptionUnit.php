<?php

namespace App\Livewire\Tenant\Items;

use Livewire\Component;
use App\Models\Tenant\Items\UnitMeasurements as UnitMeasurementsModel;
use App\Services\Tenant\TenantManager;
use App\Models\Auth\Tenant;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;

class ConsumptionUnit extends Component
{
    public $consumptionUnitId = '';
    public $name = 'consumptionUnitId';
    public $placeholder = 'Seleccione una unidad de medida';
    public $label = 'Unidad de consumo';
    public $required = true;
    public $showLabel = false;
    public $class = 'mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500';
    public $index = null;
    public $search = '';

    public function mount($consumptionUnitId = '', $name = 'consumptionUnitId', $placeholder = 'Seleccione una unidad de medida', $label = 'Unidad de consumo', $required = true, $showLabel = true, $class = null)
    {
        $this->consumptionUnitId = $consumptionUnitId;
        $this->name = $name;
        $this->placeholder = $placeholder;
        $this->label = $label;
        $this->required = $required;
        $this->showLabel = $showLabel;
        if ($class) {
            $this->class = $class;
        }

        $this->consumptionUnitId = $consumptionUnitId ?: 35;
        $this->dispatch('consumption-unit-changed', $this->consumptionUnitId);
    }

    public function updatedConsumptionUnitId(){
        \Illuminate\Support\Facades\Log::info('ConsumptionUnit: updatedConsumptionUnitId hook triggered', [
            'consumptionUnitId' => $this->consumptionUnitId,
            'index' => $this->index,
            'name' => $this->name
        ]);

        if($this->index !== null){
            $this->dispatch('consumption-unit-changed', consumptionUnitId: $this->consumptionUnitId, index: $this->index);
        }else{
            $this->dispatch('consumption-unit-changed', $this->consumptionUnitId);
        }

        \Illuminate\Support\Facades\Log::info('ConsumptionUnit: consumption-unit-changed event dispatched', [
            'consumptionUnitId' => $this->consumptionUnitId,
            'index' => $this->index
        ]);
    }

    #[On('validate-consumption-unit')]
    public function validateConsumptionUnit(){
        $this->validate([
            'consumptionUnitId' => 'required',
        ]);
        // Notificar al padre que el hijo pasó la validación
        $this->dispatch('consumption-unit-valid', index: $this->index, consumptionUnitId: $this->consumptionUnitId);
    }

    public function selectConsumptionUnit($id)
    {
        \Illuminate\Support\Facades\Log::info('ConsumptionUnit: selectConsumptionUnit called', [
            'id' => $id,
            'index' => $this->index,
            'name' => $this->name
        ]);

        $this->consumptionUnitId = $id;
        $this->search = '';

        if($this->index !== null){
            $this->dispatch('consumption-unit-changed', consumptionUnitId: $this->consumptionUnitId, index: $this->index);
        }else{
            $this->dispatch('consumption-unit-changed', $this->consumptionUnitId);
        }
    }

    #[Computed]
    public function selectedConsumptionUnitName(){
        if (!$this->consumptionUnitId) return null;
        return UnitMeasurementsModel::find($this->consumptionUnitId)?->description;
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

    public function getUnitMeasurementsProperty()
    {
        $this->ensureTenantConnection();
        // Cargar todas las unidades de medida desde la base de datos
        return UnitMeasurementsModel::where('status', 1)->get(['id', 'description']);
    }

    #[Computed]
    public function consumptionUnits(){
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
        return view('livewire.tenant.items.consumption-unit', [
            'consumptionUnits' => $this->unitMeasurements,
            'showLabel' => $this->showLabel
        ]);
    }
}
