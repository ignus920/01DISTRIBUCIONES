<?php

namespace App\Livewire\Tenant\Inventory;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Tenant\Items\House as HouseModel;
use App\Services\Tenant\TenantManager;
use App\Models\Auth\Tenant;
use Carbon\Carbon;


class House extends Component
{
    use WithPagination;
    
    public $house_id,$name, $status, $created_at;

    //Propiedades para la tabla
    public $search = '';
    public $sortField = 'name';
    public $sortDirection = 'asc';
    public $showModal = false;
    public $confirmingHouseDeletion = false;
    public $houseIdToDelete;
    public $perPage = 10;

    protected $rules =[
        'name' => 'required|min:3|regex:/^\pL+(\s+\pL+)*$/u',
    ];

    protected $messages = [
        'name.required' => 'El nombre de la casa es obligatorio',
        'name.min' => 'El nombre de la casa debe tener al menos 3 caracteres',
        'name.regex' => 'El nombre de la casa solo debe contener letras y espacios',
    ];

    public function resetForm()
    {
        $this->name = '';
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
        $this->resetExcept(['houses', 'types']);
        $this->showModal = true;
    }

    public function edit($id)
    {
        $this->ensureTenantConnection();
        $house = HouseModel::findOrFail($id);
        $this->name = $house->name;
        $this->house_id=$house->id;
        $this->showModal = true;
    }

    public function cancel()
    {
        $this->resetValidation();
        $this->reset([
            'name'
        ]);
        $this->showModal = false;
        $this->confirmingHouseDeletion = false;
    }

    public function save()
    {
        $this->ensureTenantConnection();
        $this->validate();

        $houseData = [
            'name' => $this->name,
        ];

        if($this->house_id){
            $house=HouseModel::findOrFail($this->house_id);
            $house->update($houseData);
            session()->flash('message', 'Casa actualizada correctamente.');
        }else{
            HouseModel::create($houseData);
            session()->flash('message', 'Casa creada correctamente.');
        }

        $this->resetValidation();
        $this->reset([
            'name',
        ]);
        $this->showModal = false;
    }

    public function toggleHouseStatus($id)
    {
        $this->ensureTenantConnection();
        $item=HouseModel::findOrFail($id);

        $newStatus = $item->status ? 0 : 1;
        $item->update([
            'status'=>$newStatus, 
        ]);
        
        session()->flash('message', 'Estado actualizado correctamente');
    }

    public function confirmHouseDeletion($id)
    {
        $this->confirmingHouseDeletion = true;
        $this->houseIdToDelete = $id;
    }

    public function deleteHouse()
    {
        $this->ensureTenantConnection();

        $houseData=[
            'status'=>0,
            'deleted_at'=>Carbon::now(),
        ];

        $house=HouseModel::findOrFail($this->houseIdToDelete);
        //$category->delete();
        $house->update($houseData);
        $this->confirmingHouseDeletion = false;
        $this->reset(['houseIdToDelete']);
        session()->flash('message','Casa eliminada correctamente');
    }

    public function render()
    {   
        $this->ensureTenantConnection();
        $houses=HouseModel::query()
         //->where('status', 1)
            ->when($this->search, function($query){
                $query->where('name', 'like', '%' . $this->search . '%');
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);
        return view('livewire.tenant.inventory.house',[
            'houses' => $houses
        ]);
    }
}
