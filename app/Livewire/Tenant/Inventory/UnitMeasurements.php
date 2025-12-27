<?php

namespace App\Livewire\Tenant\Inventory;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Tenant\Items\UnitMeasurements as UnitMeasurementsModel;
use App\Services\Tenant\TenantManager;
use App\Models\Auth\Tenant;
use Carbon\Carbon;

class UnitMeasurements extends Component
{
    use WithPagination, \App\Traits\Livewire\WithExport;

    public $unit_id, $description, $status, $quantity, $created_at;

    //Propiedades para la tabla
    public $search = '';
    public $sortField = 'description';
    public $sortDirection = 'asc';
    public $showModal = false;
    public $confirmingUnitDeletion = false;
    public $unitIdToDelete;
    public $perPage = 10;

    protected $rules =[
        'description' => 'required|min:3',
        'quantity' => 'required|min:1',
    ];

    public function resetForm()
    {
        $this->description = '';
        $this->quantity = '';
        $this->status = '';
        $this->created_at = null;
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

    public function mount()
    {
        $this->ensureTenantConnection();
    }

    public function create()
    {
        $this->resetExcept(['units', 'types']);
        $this->showModal = true;
    }

    public function edit($id)
    {
        $this->ensureTenantConnection();
        $unit = UnitMeasurementsModel::findOrFail($id);
        $this->description = $unit->description;
        $this->quantity = $unit->quantity;
        $this->unit_id=$unit->id;
        $this->showModal = true;
    }

    public function cancel()
    {
        $this->resetValidation();
        $this->reset([
            'description',
            'quantity'
        ]);
        $this->showModal = false;
        $this->confirmingUnitDeletion = false;
    }

    public function save()
    {
        $this->ensureTenantConnection();
        $this->validate();

        $unitData = [
            'description' => $this->description,
            'quantity' => $this->quantity,
        ];

        if($this->unit_id){
            $unit=UnitMeasurementsModel::findOrFail($this->unit_id);
            $unit->update($unitData);
            session()->flash('message', 'Unidad de medida actualizada correctamente.');
        }else{
            UnitMeasurementsModel::create($unitData);
            session()->flash('message', 'Unidad de medida creada correctamente.');
        }

        $this->resetValidation();
        $this->reset([
            'description',
            'quantity',
        ]);
        $this->showModal = false;
    }

    public function toggleUnitStatus($id)
    {
        $this->ensureTenantConnection();
        $item=UnitMeasurementsModel::findOrFail($id);

        $newStatus = $item->status ? 0 : 1;
        $item->update([
            'status'=>$newStatus, 
        ]);
        
        session()->flash('message', 'Estado actualizado correctamente');
    }

    public function confirmUnitDeletion($id)
    {
        $this->confirmingUnitDeletion = true;
        $this->unitIdToDelete = $id;
    }

    public function deleteUnit()
    {
        $this->ensureTenantConnection();

        $unitData=[
            'status'=>0,
            'deleted_at'=>Carbon::now(),
        ];

        $unit=UnitMeasurementsModel::findOrFail($this->unitIdToDelete);
        //$category->delete();
        $unit->update($unitData);
        $this->confirmingUnitDeletion = false;
        $this->reset(['unitIdToDelete']);
        session()->flash('message','Unidad de medida eliminada correctamente');
    }

    public function render()
    {
        $this->ensureTenantConnection();
        $units=UnitMeasurementsModel::query()
            // ->where('status', 1)
            ->when($this->search, function($query){
                $query->where('description', 'like', '%' . $this->search . '%');
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);
        return view('livewire.tenant.inventory.unit-measurements',[
            'units' => $units
        ]);
    }

    /**
     * Métodos para Exportación
     */

    protected function getExportData()
    {
        $this->ensureTenantConnection(); 
        return UnitMeasurementsModel::query()
            ->when($this->search, function($query){
                $query->where('description', 'like', '%' . $this->search . '%');
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->get();
    }

    protected function getExportHeadings(): array
    {
        return ['ID', 'Descripción', 'Cantidad', 'Estado', 'Fecha Registro'];
    }

    protected function getExportMapping()
    {
        return function($unit) {
            return [
                $unit->id,
                $unit->description,
                $unit->quantity,
                $unit->status ? 'Activo' : 'Inactivo',
                $unit->created_at ? Carbon::parse($unit->created_at)->format('Y-m-d H:i:s') : 'N/A',
            ];
        };
    }

    protected function getExportFilename(): string
    {
        return 'unidades_medida_' . now()->format('Y-m-d_His');
    }
}
