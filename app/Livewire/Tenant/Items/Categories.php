<?php

namespace App\Livewire\Tenant\Items;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Tenant\Items\Category;
use App\Services\Tenant\Inventory\CategoriesService; 
use App\Services\Tenant\TenantManager;
use App\Models\Auth\Tenant;

class Categories extends Component
{
    use WithPagination;

    public $showcreateCategory = false;
    public $categoryId = '';
    public $name = 'categoryId';
    public $placeholder = 'Seleccione una categoría';
    public $label = 'Categoría';
    public $required = true;
    public $showLabel = true;
    public $class = 'mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500';
    
    public $newCategoryName = '';
    public $showCategoryForm = false;

    protected $listeners = ['refreshCategories' => '$refresh'];
    
    public function mount($categoryId = '', $name = 'categoryId', $placeholder = 'Seleccione una categoría', $label = 'Categoría', $required = true, $showLabel = true, $class = null)
    {
        $this->categoryId = $categoryId;
        $this->name = $name;
        $this->placeholder = $placeholder;
        $this->label = $label;
        $this->required = $required;
        $this->showLabel = $showLabel;
        if ($class) {
            $this->class = $class;
        }

        if ($this->categoryId) {
            $this->dispatch('category-changed', $this->categoryId);
        }
    }

    public function updatedCategoryId(){
        $this->dispatch('category-changed', $this->categoryId);
    }

    public function toggleCategoryForm()
    {
        $this->showCategoryForm = !$this->showCategoryForm;
        if ($this->showCategoryForm) {
            $this->newCategoryName = '';
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

    public function createCategory()
    {   
        
         $this->validate([
            'newCategoryName' => 'required'
         ]);
        
        try {

            $categoryService = app(CategoriesService::class);
            $this->ensureTenantConnection();
            $category = $categoryService->createCategory([
                'name' => $this->newCategoryName,
                'status' => 1,
            ]);

            // Resetear el formulario
            $this->showCategoryForm = false;
            $this->newCategoryName = '';

            // Emitir eventos
            $this->dispatch('category-created', categoryId: $category->id);
            $this->dispatch('refreshCategories'); // Refrescar este componente
            
            // Opcional: Seleccionar automáticamente la nueva categoría
            $this->categoryId = $category->id;
            $this->updatedCategoryId();

        } catch (\Exception $e) {
            $this->addError('newCategoryName', 'Error al crear la categoría: ' . $e->getMessage());
        }
    }


    public function getCategoriesProperty()
    {
        $this->ensureTenantConnection();
        // Cargar todas las categorías desde la base de datos
        return Category::where('status', 1)->get(['id', 'name']);
    }

    public function render()
    {
        $this->ensureTenantConnection(); // ← Agregar esto
        return view('livewire.tenant.items.categories',[
            'categories' => $this->categories,
            'showLabel' => $this->showLabel
        ]);
    }

}
