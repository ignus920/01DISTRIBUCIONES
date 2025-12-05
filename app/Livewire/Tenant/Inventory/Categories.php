<?php

namespace App\Livewire\Tenant\Inventory;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Tenant\Items\Category;
use App\Services\Tenant\TenantManager;
use App\Models\Auth\Tenant;
use Carbon\Carbon;

class Categories extends Component
{   
    use WithPagination;
    
    public $category_id,$name, $status, $created_at;

    //Propiedades para la tabla
    public $search = '';
    public $sortField = 'name';
    public $sortDirection = 'asc';
    public $showModal = false;
    public $confirmingItemDeletion = false;
    public $categorieIdToDelete;
    public $perPage = 10;

    protected $rules =[
        'name' => 'required|min:3',
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
        $this->resetExcept(['categories', 'types']);
        $this->showModal = true;
    }

    public function edit($id)
    {
        $this->ensureTenantConnection();
        $category = Category::findOrFail($id);
        $this->name = $category->name;
        $this->category_id=$category->id;
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

        $categorieData = [
            'name' => $this->name,
        ];

        if($this->category_id){
            $category=Category::findOrFail($this->category_id);
            $category->update($categorieData);
            session()->flash('message', 'Categoría actualizada correctamente.');
        }else{
            Category::create($categorieData);
            session()->flash('message', 'Categoría creada correctamente.');
        }

        $this->resetValidation();
        $this->reset([
            'name',
        ]);
        $this->showModal = false;
    }

    public function toggleCategoryStatus($id)
    {
        $this->ensureTenantConnection();
        $item=Category::findOrFail($id);

        $newStatus = $item->status ? 0 : 1;
        $item->update([
            'status'=>$newStatus, 
        ]);
        
        session()->flash('message', 'Estado actualizado correctamente');
    }

    public function confirmItemDeletion($id)
    {
        $this->confirmingItemDeletion = true;
        $this->categorieIdToDelete = $id;
    }

    public function deleteItem()
    {
        $this->ensureTenantConnection();

        $categorieData=[
            'status'=>0,
            'deleted_at'=>Carbon::now(),
        ];

        $category=Category::findOrFail($this->categorieIdToDelete);
        //$category->delete();
        $category->update($categorieData);
        $this->confirmingItemDeletion = false;
        $this->reset(['categorieIdToDelete']);
        session()->flash('message','Item eliminado correctamente');
    }


    public function render()
    {
        $this->ensureTenantConnection();
        $categories= Category::query()
            //->where('status', 1)
            ->when($this->search, function($query){
                $query->where('name', 'like', '%' . $this->search . '%');
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);
        return view('livewire.tenant.inventory.categories',[
            'categories' => $categories
        ]);
    }
}
