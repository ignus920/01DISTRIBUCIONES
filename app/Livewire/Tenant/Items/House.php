<?php

namespace App\Livewire\Tenant\Items;

use Livewire\Component;
use App\Models\Tenant\Items\House as HouseModel;
use App\Services\Tenant\Inventory\HouseService;
use App\Services\Tenant\TenantManager;
use App\Models\Auth\Tenant;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;

class House extends Component
{
    public $houseId = '';
    public $name = 'houseId';
    public $placeholder = 'Seleccione una casa';
    public $label = 'Casa';
    public $required = true;
    public $showLabel = true;
    public $class = 'mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500';
    public $index = null;
    public $search = '';

    public $newHouseName = '';
    public $showHouseForm = false;

    protected $listeners = ['refreshHouse' => '$refresh'];

    public function mount($houseId = '', $name = 'houseId', $placeholder = 'Seleccione una casa', $label = 'Casa', $required = true, $showLabel = true, $class = null)
    {
        $this->houseId = $houseId;
        $this->name = $name;
        $this->placeholder = $placeholder;
        $this->label = $label;
        $this->required = $required;
        $this->showLabel = $showLabel;
        if ($class) {
            $this->class = $class;
        }
    }

    public function updatedHouseId(){
        \Illuminate\Support\Facades\Log::info('HouseSelect: updatedHouseId hook triggered', [
            'houseId' => $this->houseId,
            'index' => $this->index,
            'name' => $this->name
        ]);

        if($this->index !== null){
            $this->dispatch('house-changed', houseId: $this->houseId, index: $this->index);
        }else{
            $this->dispatch('house-changed', $this->houseId);
        }

        \Illuminate\Support\Facades\Log::info('HouseSelect: house-changed event dispatched', [
            'houseId' => $this->houseId,
            'index' => $this->index
        ]);
    }

    #[On('validate-house')]
    public function validateHouse(){
        $this->validate([
            'houseId' => 'required',
        ]);
        // Notificar al padre que el hijo pasó la validación
        $this->dispatch('house-valid', index: $this->index, houseId: $this->houseId);
    }

    public function selectHouse($id){
        \Illuminate\Support\Facades\Log::info('HouseSelect: selectHouse called', [
            'id' => $id,
            'index' => $this->index,
            'name' => $this->name
        ]);

        $this->houseId = $id;
        $this->search = '';

        if($this->index !== null){
            $this->dispatch('house-changed', houseId: $this->houseId, index: $this->index);
        }else{
            $this->dispatch('house-changed', $this->houseId);
        }
    }

    #[Computed]
    public function selectedHouseName(){
        if (!$this->houseId) return null;
        return HouseModel::find($this->houseId)?->name;
    }

    public function toggleHouseForm()
    {
        $this->showHouseForm = !$this->showHouseForm;
        if ($this->showHouseForm) {
            $this->newHouseName = '';
            $this->resetErrorBag();
        }
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

    public function createHouse()
    {   
        
         $this->validate([
            'newHouseName' => 'required'
         ]);
        
        try {

            $houseService = app(HouseService::class);
            $this->ensureTenantConnection();
            $house = $houseService->createHouse([
                'name' => $this->newHouseName,
                'status' => 1,
            ]);

            // Resetear el formulario
            $this->showHouseForm = false;
            $this->newHouseName = '';

            // Emitir eventos
            $this->dispatch('house-created', houseId: $house->id);
            $this->dispatch('refreshHouses'); // Refrescar este componente
            
            // Opcional: Seleccionar automáticamente la nueva categoría
            $this->houseId = $house->id;
            $this->updatedHouseId();

        } catch (\Exception $e) {
            $this->addError('newHouseName', 'Error al crear la casa: ' . $e->getMessage());
        }
    }

    #[Computed]
    public function houses(){
        $query = HouseModel::where('status', 1);
        if(!empty($this->search)){
            $query->where('name', 'like', '%'.$this->search.'%');
        }

        return $query->select('id', 'name')
            ->orderBy('name')
            ->limit(50)
            ->get();
    }

    public function render()
    {
        return view('livewire.tenant.items.house', [
            'houses' => $this->houses,
            'showLabel' => $this->showLabel
        ]);
    }
}
