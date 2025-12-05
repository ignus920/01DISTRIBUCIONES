<?php

namespace App\Livewire\Tenant\Items;

use Livewire\Component;
use Livewire\WithPagination;
//Modelos
use App\Models\Tenant\Items\Items;
use App\Models\Tenant\Items\Category;
use App\Models\Tenant\Items\InvValues;
use App\Models\Auth\UserTenant;
use App\Models\Auth\Tenant;
use App\Models\Central\VntWarehouse;
use App\Models\Central\CnfTaxes;
//Servicios
use App\Services\Tenant\TenantManager;
use App\Services\Tenant\Inventory\CategoriesService; 
use App\Services\Tenant\Inventory\CommandsServices;
use App\Services\Tenant\Inventory\BrandsService;
use App\Services\Tenant\Inventory\HouseService;
use App\Livewire\Tenant\Items\Services\InvValuesService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ManageItems extends Component
{

    use WithPagination;

    protected $listeners = [
        'command-changed' => 'onCommandSelected',
        'command-created' => 'refreshCommands',
        'brand-changed' => 'onBrandSelected',
        'brand-created' => 'refreshBrands',
        'house-changed' => 'onHouseSelected',
        'purchase-unit-changed' => 'onPurchaseUnitSelected',
        'consumption-unit-changed' => 'onConsumptionUnitSelected',
        'category-changed' => 'onCategorySelected',
        'category-created' => 'refreshCategories',
        //'invValuesItem-created' => 'refreshValuesItems',
    ];

    // Propiedades para el formulario
    public $item_id;
    public $category_id;
    public $name;
    public $internal_code;
    public $sku;
    public $description;
    public $type;
    public $commandId;
    public $brandId;
    public $houseId;
    public $purchase_unit;
    public $consumption_unit;
    public $generic=1;
    public $inv_values = [];
    public $warehouses = [];
    public $warehouseIdValue;
    public $tax;
    public $disabled = false;
    
    // Propiedades para la tabla
    public $search = '';
    public $sortField = 'name';
    public $sortDirection = 'asc';
    public $showModal = false;
    public $confirmingItemDeletion = false;
    public $perPage = 10;
    
    //Información para categorias
    public $showCategoryInput = false;
    public $newCategoryName = '';

    //Información para comandas
    public $showCommandInput = false;
    public $newCommandName = '';

    //Información precios
    public $showValuesSection = false;
    public $valueItem = 0;
    public $typeValue;
    public $labelValue;
    public $messageValues = '';
    public $temporaryErrorMessage;
    public $showValuesModal = false;


    // tipos disponibles (puedes externalizarlo si lo prefieres)
    public $types = [
        'COMBO' => 'Combo',
        'COMPRA NACIONAL' => 'Compra nacional',
        'IMPORTADO' => 'Importado',
        'PRODUCIDO' => 'Producido',
    ];

    public $allLabelsValues = [
        'costo' => [
            'Costo Inicial' => 'Costo Inicial',
            'Costo' => 'Costo',
        ],
        'precio' => [
            'Precio Base' => 'Precio Base',
            'Precio Regular' => 'Precio Regular',
            'Precio Crédito' => 'Precio Crédito',
        ],
    ];

    public function getLabelsValuesProperty()
    {
        return $this->allLabelsValues[$this->typeValue] ?? [];
    }

    public function updatedTypeValue($value)
    {
        $this->labelValue = null; // Reset labelValue when typeValue changes
    }

    protected $rules =[
        'category_id' => 'required',
        'name' => 'required|min:3',
        'type' => 'required',
        'internal_code' => 'nullable|string',
        'brandId' => 'nullable|integer',
        'houseId' => 'nullable|integer',
        'purchase_unit' => 'nullable|integer',
        'consumption_unit' => 'nullable|integer',
    ];
    

    protected $queryString = [
        'search' => ['except' => ''],
        'perPage' => ['except' => 10],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingPerPage()
    {
        $this->resetPage();
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
        /*$this->sortDirection = $this->sortField === $field 
            ? $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc'
            : 'asc';

        $this->sortField = $field;*/
    }

    public function mount()
    {
        $this->ensureTenantConnection();
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

    public function edit($idItem)
    {
        $this->ensureTenantConnection();
        $item = Items::with('invValues')->findOrFail($idItem);
        $this->item_id = $item->id;
        $this->category_id = $item->categoryId;
        $this->name = $item->name;
        $this->internal_code = $item->internal_code;
        $this->sku = $item->sku ?? null;
        $this->description = $item->description;
        $this->type = $item->type;
        $this->commandId = $item->commandId;
        $this->brandId = $item->brandId;
        $this->houseId = $item->houseId;
        $this->purchase_unit = $item->purchasing_unit;
        $this->consumption_unit = $item->consumption_unit;
        $this->generic = $item->generic ?? 1;
        $this->tax = $item->taxId;
        $this->disabled = true;

        $this->showModal = true;
    }

    public function render()
    {
        $this->ensureTenantConnection();
        $this->loadWarehouses();

        $items = Items::query()
            ->with(['brand', 'principalImage', 'tax', 'purchasingUnit', 'consumptionUnit'])
            ->when($this->search, function($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('sku', 'like', '%' . $this->search . '%')
                      ->orWhere('internal_code', 'like', '%' . $this->search . '%');
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.tenant.items.manage-items', [
            'items' => $items,
            'categories' => Category::where('status', 1)->get(),
            'types' => $this->types
        ]);
    }

    public function toggleGeneric()
    {
        $this->generic = $this->generic ? 0 : 1;
    }

    public function create()
    {
        $this->resetExcept(['categories', 'types', 'allLabelsValues']); // No reseteamos las listas de opciones
        $this->showModal = true;
        
        // Emitir eventos para inicializar los componentes hijos
        $this->dispatch('initializeCommand');
        $this->dispatch('initializeBrand');
        $this->dispatch('initializeHouse');
        $this->dispatch('initializePurchaseUnit');
        $this->dispatch('initializeConsumptionUnit');
    }

    public function save()
    {
        $this->ensureTenantConnection();
        $this->validate();

        $itemData = [
            'categoryId' => $this->category_id,
            'name' => $this->name,
            'internal_code' => $this->internal_code,
            'sku' => $this->sku,
            'description' => $this->description,
            'type' => $this->type,
            'commandId' => $this->commandId,
            'brandId' => $this->brandId,
            'houseId' => $this->houseId,
            'inventoriable' => 1,
            'purchasing_unit' => $this->purchase_unit,
            'consumption_unit' => $this->consumption_unit,
            'status' => 1,
            'generic' => $this->generic,
            'taxId' => $this->tax,
        ];
        
        try{
            if ($this->item_id) {
                $item = Items::findOrFail($this->item_id);
                $item->update($itemData);
                session()->flash('message', 'Item actualizado correctamente.');
                $this->showModal = false;
            } else {
                $newItem=Items::create($itemData);
                $item_id=$newItem->id;
                session()->flash('message', 'Item creado correctamente.');
            }

            // Mantener la paginación y filtros, limpiar solo el formulario
            $this->resetValidation();
            $this->reset([
                'item_id',
                'category_id',
                'name',
                'internal_code',
                'sku',
                'description',
                'type',
                'brandId',
                'houseId',
                'commandId',
                'purchase_unit',
                'consumption_unit'
            ]);
            $this->edit($item_id);
            $this->disabled = false;
        }catch(\Exception $e){
            session()->flash('error', 'Error al guardar: ' . $e->getMessage());
            return;
        }
    }


    public function toggleItemStatus($id)
    {
        $this->ensureTenantConnection();
        $item=Items::findOrFail($id);

        $newStatus = $item->status ? 0 : 1;
        $item->update([
            'status'=>$newStatus, 
        ]);
        
        session()->flash('message', 'Estado actualizado correctamente');
    }

    public function openValuesModal(){
        $this->showValuesModal=true;
    }

    public function cancel()
    {
        $this->resetValidation();
        $this->reset([
            'item_id',
            'category_id',
            'name',
            'internal_code',
            'sku',
            'description',
            'type',
            'brandId',
            'houseId',
            'commandId',
            'purchase_unit',
            'consumption_unit',
            'inv_values'
        ]);
        $this->showModal = false;
        $this->confirmingItemDeletion = false;
    }

    public function onCategorySelected($value)
    {
        $this->category_id = $value;
    }

    public function onCommandSelected($value)
    {
        $this->commandId = $value;
    }

    public function onBrandSelected($value)
    {
        $this->brandId = $value;
    }

    public function onHouseSelected($value)
    {
        $this->houseId = $value;
    }

    public function onPurchaseUnitSelected($value)
    {
        $this->purchase_unit = $value;
    }

    public function onConsumptionUnitSelected($value)
    {
        $this->consumption_unit = $value;
    }

    public function exportExcel()
    {
        // TODO: Implementar exportación a Excel
        $this->dispatch('show-toast', [
            'type' => 'info',
            'message' => 'Exportación a Excel - En desarrollo'
        ]);
        //dd('Exportación PDF ejecutada');
    }

    public function exportPdf()
    {
        // TODO: Implementar exportación a PDF
        $this->dispatch('show-toast', [
            'type' => 'info',
            'message' => 'Exportación a PDF - En desarrollo'
        ]);
    }

    public function exportCsv()
    {
        // TODO: Implementar exportación a CSV
        /*$this->dispatch('show-toast', [
            'type' => 'info',
            'message' => 'Exportación a CSV - En desarrollo'
        ]);*/
        //$this->ensureTenantConnection();
        //return Items::disk('invoices')->download('invoice.csv');
        return response()->download( 
            $this->item_id->file_path, 'items.cvs'
        );
    }

    public function getTaxesProperty(){
        $this->ensureTenantConnection();
    
        return CnfTaxes::all();
    }

    private function loadWarehouses(): void
    {
        $sessionTenant = $this->getTenantId();
        
        // 1. Obtener los IDs de las bodegas que cumplen el criterio
        $warehouseIds = UserTenant::query()
            ->select('vc.warehouseId')
            ->join('users as u', 'u.id', '=', 'user_tenants.user_id')
            ->join('vnt_contacts as vc', 'vc.id', '=', 'u.contact_id')
            ->where('user_tenants.tenant_id', $sessionTenant)
            ->pluck('warehouseId') // Obtener solo los IDs de las bodegas
            ->unique(); // Evitar IDs duplicados

        // 2. Cargar las bodegas usando los IDs obtenidos
        $this->warehouses = VntWarehouse::query()
            ->whereIn('id', $warehouseIds) // Usamos el array de IDs
            ->where('vnt_warehouses.status', true)
            ->with('company')
            ->orderBy('vnt_warehouses.name')
            ->get();
    }

    private function getTenantId()
    {
        $tenantId = session('tenant_id');

        if (!$tenantId) {
            throw new \Exception('No tenant selected');
        }
        return $tenantId;
    }

    //============CATEGORIAS========================//
    public function toggleCategoryInput()
    {
        $this->showCategoryInput = ! $this->showCategoryInput;
        if ($this->showCategoryInput) {
            $this->resetValidation();
            $this->newCategoryName = '';
        }
    }

    public function saveCategory(){
        $this->ensureTenantConnection();
        try {
            // Usar el servicio para crear la categoría
            $commandService = app(CommandsServices::class);
            $command = $commandService->createCommand([
                'name' => $this->newCommandName,
                'print_path' => 'http://127.0.0.1:8000/inventory/commands',
                'status' => 1,
            ]);

            // Actualizar la lista de categorías y seleccionar la nueva
            $this->commandId = $command->id;
            
            // Resetear el formulario de categoría
            $this->showCommandInput = false;
            $this->newCommandName = '';
            
            // Emitir evento para actualizar componentes
            $this->dispatch('command-created', commandId: $command->id);
            
            // Mostrar mensaje de éxito
            session()->flash('command_message', 'Comanda creada exitosamente!');
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Pasar los errores de validación al componente
            $this->addError('newCommandName', $e->validator->errors()->first('name'));
        } catch (\Exception $e) {
            session()->flash('error', 'Error al crear la comanda: ' . $e->getMessage());
        }
    }

    // Método para refrescar categorías
    public function refreshCategories($categoryId = null)
    {
        // Forzar la recarga de categorías en el próximo render
        $this->dispatch('$refresh');
        
        if ($categoryId) {
            $this->category_id = $categoryId;
            // También emitir el cambio para sincronizar
            $this->dispatch('category-changed', $categoryId);
        }
    }

    // Método para verificar si una categoría existe
    public function checkCategoryExists()
    {
        if ($this->newCategoryName) {
            $categoryService = app(CategoriesService::class);
            $exists = $categoryService->categoryExists($this->newCategoryName);
            
            if ($exists) {
                $this->addError('newCategoryName', 'Esta categoría ya existe.');
            } else {
                $this->resetErrorBag('newCategoryName');
            }
        }
    }

    //============COMANDAS========================//
    public function toggleCommandInput()
    {
        $this->showCommandInput = ! $this->showCommandInput;
        if ($this->showCommandInput) {
            $this->resetValidation();
            $this->newCommandName = '';
        }
    }

    public function saveCommand(){
        $this->ensureTenantConnection();
        try {
            // Usar el servicio para crear la categoría
            $categoryService = app(CategoriesService::class);
            $category = $categoryService->createCategory([
                'name' => $this->newCommandName,
                'status' => 1,
            ]);

            // Actualizar la lista de categorías y seleccionar la nueva
            $this->category_id = $category->id;
            
            // Resetear el formulario de categoría
            $this->showCommandInput = false;
            $this->newCommandName = '';
            
            // Emitir evento para actualizar componentes
            $this->dispatch('category-created', categoryId: $category->id);
            
            // Mostrar mensaje de éxito
            session()->flash('category_message', 'Categoría creada exitosamente!');
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Pasar los errores de validación al componente
            $this->addError('newCommandName', $e->validator->errors()->first('name'));
        } catch (\Exception $e) {
            session()->flash('error', 'Error al crear la categoría: ' . $e->getMessage());
        }
    }

    // Método para refrescar categorías
    public function refreshCommands($commandId = null)
    {
        // Forzar la recarga de categorías en el próximo render
        $this->dispatch('$refresh');
        
        if ($commandId) {
            $this->commandId = $commandId;
            // También emitir el cambio para sincronizar
            $this->dispatch('command-changed', $commandId);
        }
    }

    // Método para verificar si una categoría existe
    public function checkCommandExists()
    {
        if ($this->newCommandName) {
            $commandService = app(CommandsServices::class);
            $exists = $commandService->commandExists($this->newCommandName);
            
            if ($exists) {
                $this->addError('newCommandName', 'Esta comanda ya existe.');
            } else {
                $this->resetErrorBag('newCommandName');
            }
        }
    }

    //============VALORES ITEMS========================//
    public function toggleValuesForm()
    {
        $this->showValuesSection=true;
        $this->messageValues = '';
    }

    public function SaveValueItem()
    {
        $this->ensureTenantConnection();
        $exitsValue = InvValues::where('itemId', $this->item_id)->where('label', $this->labelValue)->exists();
        if ($exitsValue) {
            $this->temporaryErrorMessage = 'Este Item ya tiene registrado un costo inicial.';
            
        } else {
            $this->temporaryErrorMessage = null;
            $this->resetErrorBag('labelValue');
            // Validar solo los campos del formulario de valores
            $this->validate([
                'valueItem' => 'required|numeric',
                'typeValue' => 'required|string',
                'labelValue' => 'required|string',
            ]);
    
            try {
                $invValueService = app(InvValuesService::class);
                
                $invValueService->createValueItem([
                    'date' => Carbon::now(),
                    'values' => $this->valueItem,
                    'type' => $this->typeValue,
                    'itemId' => $this->item_id, // Usar la propiedad del componente
                    'warehouseId' => $this->warehouseIdValue ?? 0,
                    'label' => $this->labelValue
                ]);
    
                // Resetear y ocultar el formulario
                $this->reset(['valueItem', 'typeValue', 'labelValue']);
                $this->showValuesSection = false;
    
                $this->messageValues = 'Valor agregado exitosamente';
    
                // Refrescar para mostrar el nuevo valor
                $this->dispatch('refreshValues');
    
            } catch (\Exception $e) {
                session()->flash('error', 'Error al crear el valor: ' . $e->getMessage());
            }
        }
    }

    // Método para limpiar el mensaje temporal
    public function clearTemporaryMessage()
    {
        $this->temporaryErrorMessage = null;
    }
}
