<?php

namespace App\Livewire\Tenant\Inventory;

use App\Livewire\Tenant\Items\Brand as ItemsBrand;
use Livewire\Component;
use App\Models\Tenant\Items\Brand as BrandModel;
use App\Services\Tenant\TenantManager;
use App\Models\Auth\Tenant;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

class Brand extends Component
{
    use \App\Traits\Livewire\WithExport;
    public $brand_id,$name,$status, $created_at;

    //Propiedades para la tabla
    public $search = '';
    public $sortField = 'name';
    public $sortDirection = 'asc';
    public $showModal = false;
    public $confirmingItemDeletion = false;
    public $brandIdToDelete;
    public $perPage = 10;

    protected $rules =[
        'name' => 'required|min:3|regex:/^\pL+(\s+\pL+)*$/u',
    ];

    protected $messages = [
        'name.required' => 'El nombre de la marca es obligatorio',
        'name.min' => 'El nombre de la marca debe tener al menos 3 caracteres',
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
        $this->resetExcept(['brands', 'types']);
        $this->showModal = true;
    }

    public function edit($id)
    {
        $this->ensureTenantConnection();
        $brand = BrandModel::findOrFail($id);
        $this->name = $brand->name;
        $this->brand_id=$brand->id;
        $this->showModal = true;
    }

    public function cancel()
    {
        $this->resetValidation();
        $this->reset([
            'name'
        ]);
        $this->showModal = false;
        $this->confirmingItemDeletion = false;
    }

    public function save()
    {
        $this->ensureTenantConnection();
        $this->validate();

        $brandData = [
            'name' => $this->name,
        ];

        if($this->brand_id){
            $brand=BrandModel::findOrFail($this->brand_id);
            $brand->update($brandData);
            session()->flash('message', 'Marca actualizada correctamente.');
        }else{
            BrandModel::create($brandData);
            session()->flash('message', 'Marca creada correctamente.');
        }

        $this->resetValidation();
        $this->reset([
            'name',
        ]);
        $this->showModal = false;
    }

    public function toggleBrandStatus($id)
    {
        $this->ensureTenantConnection();
        $item=BrandModel::findOrFail($id);

        $newStatus = $item->status ? 0 : 1;
        $item->update([
            'status'=>$newStatus, 
        ]);
        
        session()->flash('message', 'Estado actualizado correctamente');
    }

    public function confirmItemDeletion($id)
    {
        $this->confirmingItemDeletion = true;
        $this->brandIdToDelete = $id;
    }

    public function deleteBrand()
    {
        $this->ensureTenantConnection();

        $brandData=[
            'status'=>0,
            'deleted_at'=>Carbon::now(),
        ];

        $brand=BrandModel::findOrFail($this->brandIdToDelete);
        $brand->update($brandData);
        $this->confirmingItemDeletion = false;
        $this->reset(['brandIdToDelete']);
        session()->flash('message','Marca eliminada correctamente');
    }

    public function render()
    {
        $this->ensureTenantConnection();
        $brands=BrandModel::query()
            //->where('status', 1)
            ->when($this->search, function($query){
                $query->where('name', 'like', '%' . $this->search . '%');
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);
        return view('livewire.tenant.inventory.brand',[
            'brands' => $brands
        ]);
    }

    /**
     * Métodos para Exportación
     */

    protected function getExportData()
    {
        $this->ensureTenantConnection(); 
        return BrandModel::query()
            ->when($this->search, function($query){
                $query->where('name', 'like', '%' . $this->search . '%');
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->get();
    }

    protected function getExportHeadings(): array
    {
        return ['ID', 'Nombre', 'Estado', 'Fecha Registro'];
    }

    protected function getExportMapping()
    {
        return function($brand) {
            return [
                $brand->id,
                $brand->name,
                $brand->status ? 'Activo' : 'Inactivo',
                $brand->created_at ? Carbon::parse($brand->created_at)->format('Y-m-d H:i:s') : 'N/A',
            ];
        };
    }

    protected function getExportFilename(): string
    {
        return 'marcas_' . now()->format('Y-m-d_His');
    }
}
