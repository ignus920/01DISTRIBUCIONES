<?php

namespace App\Livewire\Tenant\VntCompany;

use Livewire\Component;
use App\Models\Tenant\Customer\VntCompany;
use App\Models\Tenant\Customer\VntWarehouse;
use App\Services\Tenant\TenantManager;
use App\Models\Auth\Tenant;
use Illuminate\Support\Facades\Log;

class WarehouseManagementModal extends Component
{
    public $companyId;
    public $companyName = '';
    
    public $formMode = null; // null, 'create', 'edit'
    public $editingWarehouseId = null;
    public $warehouseForm = [
        'name' => '',
        'address' => '',
        'cityId' => '',
        'postcode' => '',
        'branch_type' => 'FIJA'
    ];
    
    public $successMessage = '';
    public $errorMessage = '';
    
    protected $listeners = [
        'city-changed' => 'updateCity'
    ];
    
    public function mount($companyId)
    {
        $this->companyId = $companyId;
        $this->loadCompanyData();
    }
    
    public function render()
    {
        $this->ensureTenantConnection();
        $warehouses = $this->getWarehouses();
        return view('livewire.tenant.vnt-company.components.warehouse-management-modal', [
            'warehouses' => $warehouses
        ]);
    }
    
    public function loadCompanyData()
    {
        $this->ensureTenantConnection();
        $company = VntCompany::findOrFail($this->companyId);
        $this->companyName = $company->businessName ?: trim($company->firstName . ' ' . $company->lastName);
    }
    
    public function getWarehouses()
    {
        return VntWarehouse::where('companyId', $this->companyId)
            ->with('city')
            ->orderBy('main', 'desc')
            ->orderBy('name', 'asc')
            ->get();
    }
    
    public function startCreateWarehouse()
    {
        $this->formMode = 'create';
        $this->editingWarehouseId = null;
        $this->warehouseForm = [
            'name' => '',
            'address' => '',
            'cityId' => '',
            'postcode' => '',
            'branch_type' => 'FIJA'
        ];
        $this->resetErrorBag();
        $this->successMessage = '';
        $this->errorMessage = '';
    }
    
    public function editWarehouse($warehouseId)
    {
         $this->ensureTenantConnection();
        $warehouse = VntWarehouse::findOrFail($warehouseId);
        
        // No permitir editar sucursal principal
        if ($warehouse->main) {
            $this->errorMessage = 'La sucursal principal se edita desde el formulario de compañía';
            return;
        }
        
        $this->formMode = 'edit';
        $this->editingWarehouseId = $warehouseId;
        $this->warehouseForm = [
            'name' => $warehouse->name,
            'address' => $warehouse->address,
            'cityId' => $warehouse->cityId,
            'postcode' => $warehouse->postcode ?? '',
            'branch_type' => $warehouse->branch_type ?? 'FIJA'
        ];
        $this->resetErrorBag();
        $this->successMessage = '';
        $this->errorMessage = '';
    }
    
    public function saveWarehouse()
    {
        $this->validate([
            'warehouseForm.name' => 'required|string|max:255',
            'warehouseForm.address' => 'required|string|max:500',
            'warehouseForm.cityId' => 'required|exists:cities,id',
            'warehouseForm.postcode' => 'nullable|string|max:20',
            'warehouseForm.branch_type' => 'required|in:FIJA,DESPACHO',
        ], [
            'warehouseForm.name.required' => 'El nombre es obligatorio',
            'warehouseForm.name.max' => 'El nombre no puede exceder 255 caracteres',
            'warehouseForm.address.required' => 'La dirección es obligatoria',
            'warehouseForm.address.max' => 'La dirección no puede exceder 500 caracteres',
            'warehouseForm.cityId.required' => 'La ciudad es obligatoria',
            'warehouseForm.cityId.exists' => 'La ciudad seleccionada no es válida',
            'warehouseForm.postcode.max' => 'El código postal no puede exceder 20 caracteres',
            'warehouseForm.branch_type.required' => 'El tipo de sucursal es obligatorio',
            'warehouseForm.branch_type.in' => 'El tipo de sucursal debe ser FIJA o DESPACHO',
        ]);
        
        try {
            if ($this->formMode === 'create') {
                 $this->ensureTenantConnection();
                VntWarehouse::create([
                    'companyId' => $this->companyId,
                    'name' => $this->warehouseForm['name'],
                    'address' => $this->warehouseForm['address'],
                    'cityId' => $this->warehouseForm['cityId'],
                    'postcode' => $this->warehouseForm['postcode'],
                    'branch_type' => $this->warehouseForm['branch_type'],
                    'district' => '',
                    'main' => 0,
                    'status' => 1
                ]);
                
                $this->successMessage = 'Sucursal agregada exitosamente';
            } else {
                 $this->ensureTenantConnection();
                $warehouse = VntWarehouse::findOrFail($this->editingWarehouseId);
                
                // Verificar que no sea sucursal principal
                if ($warehouse->main) {
                    $this->errorMessage = 'No se puede editar la sucursal principal desde este modal';
                    return;
                }
                
                $warehouse->update([
                    'name' => $this->warehouseForm['name'],
                    'address' => $this->warehouseForm['address'],
                    'cityId' => $this->warehouseForm['cityId'],
                    'postcode' => $this->warehouseForm['postcode'],
                    'branch_type' => $this->warehouseForm['branch_type'],
                    'district' => '',
                    'main' => 0
                ]);
                
                $this->successMessage = 'Sucursal actualizada exitosamente';
            }
            
            $this->cancelForm();
            
        } catch (\Exception $e) {
            Log::error('Error saving warehouse', [
                'company_id' => $this->companyId,
                'mode' => $this->formMode,
                'error' => $e->getMessage()
            ]);
            
            $this->errorMessage = 'Error al guardar la sucursal: ' . $e->getMessage();
        }
    }
    
    public function deleteWarehouse($warehouseId)
    {
        try {
             $this->ensureTenantConnection();
            $warehouse = VntWarehouse::findOrFail($warehouseId);
            
            // No permitir eliminar sucursal principal
            if ($warehouse->main) {
                $this->errorMessage = 'No se puede eliminar la sucursal principal';
                return;
            }
            
            $warehouse->delete();
            
            $this->successMessage = 'Sucursal eliminada exitosamente';
            
        } catch (\Exception $e) {
            Log::error('Error deleting warehouse', [
                'warehouse_id' => $warehouseId,
                'error' => $e->getMessage()
            ]);
            
            $this->errorMessage = 'Error al eliminar la sucursal: ' . $e->getMessage();
        }
    }
    
    public function cancelForm()
    {
        $this->formMode = null;
        $this->editingWarehouseId = null;
        $this->warehouseForm = [
            'name' => '',
            'address' => '',
            'cityId' => '',
            'postcode' => '',
            'branch_type' => 'FIJA'
        ];
        $this->resetErrorBag();
    }
    
    public function updateCity($cityId, $index = null)
    {
        $this->warehouseForm['cityId'] = $cityId;
    }
    
    public function toggleWarehouseStatus($warehouseId)
    {
        try {
            $this->ensureTenantConnection();
            $warehouse = VntWarehouse::findOrFail($warehouseId);
            
            // Toggle warehouse status
            $newStatus = $warehouse->status ? 0 : 1;
            $warehouse->update(['status' => $newStatus]);
            
            $this->successMessage = 'Estado de sucursal actualizado exitosamente';
            
            Log::info('Warehouse status toggled', [
                'warehouse_id' => $warehouseId,
                'new_status' => $newStatus
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error toggling warehouse status', [
                'warehouse_id' => $warehouseId,
                'error' => $e->getMessage()
            ]);
            
            $this->errorMessage = 'Error al actualizar el estado: ' . $e->getMessage();
        }
    }
    
    public function closeModal()
    {
        $this->dispatch('warehouse-modal-closed');
    }

    private function ensureTenantConnection(): void
    {
        $tenantId = session('tenant_id');

        if (!$tenantId) {
            throw new \Exception('No tenant selected');
        }

        $tenant = Tenant::find($tenantId);

        if (!$tenant) {
            session()->forget('tenant_id');
            throw new \Exception('Invalid tenant');
        }

        // Establecer conexión tenant
        $tenantManager = app(TenantManager::class);
        $tenantManager->setConnection($tenant);

        // Inicializar tenancy
        tenancy()->initialize($tenant);
    }
}
