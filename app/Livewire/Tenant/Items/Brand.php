<?php

namespace App\Livewire\Tenant\Items;

use Livewire\Component;
use App\Models\Tenant\Items\Brand as BrandModel;
use App\Services\Tenant\Inventory\BrandsService;
use Livewire\Attributes\On;
use App\Services\Tenant\TenantManager;
use App\Models\Auth\Tenant;
use Livewire\Attributes\Computed;

class Brand extends Component
{
    public $brandId = '';
    public $name = 'brandId';
    public $placeholder = 'Seleccione una marca';
    public $label = 'Marca';
    public $required = true;
    public $showLabel = true;
    public $class = 'mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500';
    public $index = null;
    public $search = '';

    public $newBrandName = '';
    public $showBrandForm = false;

    protected $listeners = ['refreshBrands' => '$refresh'];

    public function mount($brandId = '', $name = 'brandId', $placeholder = 'Seleccione una marca', $label = 'Marca', $required = true, $showLabel = true, $class = null)
    {
        $this->brandId = $brandId;
        $this->name = $name;
        $this->placeholder = $placeholder;
        $this->label = $label;
        $this->required = $required;
        $this->showLabel = $showLabel;
        if ($class) {
            $this->class = $class;
        }
    }

    public function updatedBrandId(){
        \Illuminate\Support\Facades\Log::info('BrandSelect: updatedBrandId hook triggered', [
            'brandId' => $this->brandId,
            'index' => $this->index,
            'name' => $this->name
        ]);
        if($this->index !== null){
            $this->dispatch('brand-changed', brandId: $this->brandId, index: $this->index);
        }else{
            $this->dispatch('brand-changed', $this->brandId);
        }

        \Illuminate\Support\Facades\Log::info('BrandSelect: brand-changed event dispatched', [
            'brandId' => $this->brandId,
            'index' => $this->index
        ]);
    }

    #[On('validate-brand')]
    public function validateBrand()
    {
        $this->validate([
            'brandId' => 'required',
        ]);
        // Notificar al padre que el hijo pasó la validación
        $this->dispatch('brand-valid', index: $this->index, brandId: $this->brandId);
    }

    public function selectBrand($id){
        \Illuminate\Support\Facades\Log::info('BrandSelect: selectBrand called', [
            'id' => $id,
            'index' => $this->index,
            'name' => $this->name
        ]);

        $this->brandId = $id;
        $this->search = '';

        if($this->index !== null){
            $this->dispatch('brand-changed', brandId: $this->brandId, index: $this->index);
        }else{
            $this->dispatch('brand-changed', $this->brandId);
        }
    }

    #[Computed]
    public function selectedBrandName(){
        if (!$this->brandId) return null;
        return BrandModel::find($this->brandId)?->name;
    }

    public function toggleBrandForm()
    {
        $this->showBrandForm = !$this->showBrandForm;
        if ($this->showBrandForm) {
            $this->newBrandName = '';
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

    public function createBrand()
    {   
        
         $this->validate([
             'newBrandName' => 'required'
         ]);
        
        try {

            $brandService = app(BrandsService::class);
            $this->ensureTenantConnection();
            $brand = $brandService->createBrand([
                'name' => $this->newBrandName,
                'status' => 1,
            ]);

            // Resetear el formulario
            $this->showBrandForm = false;
            $this->newBrandName = '';

            // Emitir eventos
            $this->dispatch('brand-created', brandId: $brand->id);
            $this->dispatch('refreshBrands'); // Refrescar este componente
            
            // Opcional: Seleccionar automáticamente la nueva categoría
            $this->brandId = $brand->id;
            $this->updatedBrandId();

        } catch (\Exception $e) {
            $this->addError('newBrandName', 'Error al crear la marca: ' . $e->getMessage());
        }
    }

    #[Computed]
    public function brands(){
        $query = BrandModel::where('status', 1);

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
        return view('livewire.tenant.items.brand',[
            'showLabel' => $this->showLabel
        ]);
    }


}
