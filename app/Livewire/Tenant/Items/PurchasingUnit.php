<?php

namespace App\Livewire\Tenant\Items;

use Livewire\Component;
use App\Models\Tenant\Items\UnitMeasurements as UnitMeasurementsModel;
use App\Services\Tenant\TenantManager;
use App\Models\Auth\Tenant;

class PurchasingUnit extends Component
{
    public $purchaseUnitId = '';
    public $name = 'purchaseUnitId';
    public $placeholder = 'Seleccione una unidad de medida';
    public $label = 'Unidad de compra'; 
    public $required = true;
    public $showLabel = false;
    public $class = 'mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500';

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
        $this->dispatch('purchase-unit-changed', $this->purchaseUnitId);
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

    public function render()
    {
        return view('livewire.tenant.items.purchasing-unit',[
            'purchaseUnits' => $this->unitMeasurements,
            'showLabel' => $this->showLabel
        ]);
    }
}
