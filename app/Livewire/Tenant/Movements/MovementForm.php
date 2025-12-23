<?php

namespace App\Livewire\Tenant\Movements;

use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use App\Models\Tenant\Movements\InvInventoryAdjustment;
use App\Models\Tenant\Movements\InvDetailInventoryAdjustment;
use App\Models\Tenant\Movements\InvReason;
use App\Models\Tenant\Movements\InvStore;
use App\Models\Tenant\Items\Items;
use App\Models\Tenant\Items\UnitMeasurements;
use App\Models\Tenant\Items\InvItemsStore;
use App\Services\Tenant\TenantManager;
use App\Models\Auth\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class MovementForm extends Component
{
    //  Properties of component
    public $reusable = false;

    // Modal state
    public $showModal = false;
    public $showDetailsModal = false;
    public $movementDetails = []; // Store movement details as array instead of model
    public $movementType = 'entrada'; // Para el filtro de la lista
    public $warehouseId = null; // ID of the warehouse from central
    public $selectedStoreId = null; // ID of the selected store (bodega)
    public $showSelectStore = false;
    
    // Form data with warehouse form structure
    public $warehouseForm = [
        'movementType' => '', // ENTRADA o SALIDA
    ];
    
    public $movementForm = [
        'date' => '',
        'observations' => '',
        'reasonId' => '',
    ];
    // Details management
    public $details = [];
    public $detailForm = [
        'itemId' => '',
        'quantity' => '',
        'unitMeasurementId' => '',
    ];
    
    // Messages
    public $successMessage = '';
    public $errorMessage = '';
    
    // Processing state
    public $isProcessing = false;
    
    protected $listeners = [
        'showMovementDetails',
        'storeSelected',
        'reasonSelected',
        'itemSelected',
        'unitMeasurementSelected'
    ];

    public function mount()
    {
        $this->warehouseForm['movementType'] = '';
        $this->movementForm['date'] = now()->format('Y-m-d');
        $this->setDefaultUnitMeasurement();
    }
    
    /**
     * Set default unit measurement to "Unidad"
     */
    private function setDefaultUnitMeasurement()
    {
        $this->ensureTenantConnection();
        $defaultUnit = UnitMeasurements::where('description', 'Unidad')
            ->where('status', 1)
            ->first();
        
        if ($defaultUnit) {
            $this->detailForm['unitMeasurementId'] = $defaultUnit->id;
        }
    }
    
    /**
     * Handle store selection from GenericSelect
     */
    public function storeSelected($value)
    {
        $this->selectedStoreId = $value;
    }
    
    /**
     * Handle reason selection from GenericSelect
     */
    public function reasonSelected($value)
    {
        $this->movementForm['reasonId'] = $value;
    }
    
    /**
     * Handle item selection from GenericSelect
     */
    public function itemSelected($value)
    {
        $this->detailForm['itemId'] = $value;
    }
    
    /**
     * Handle unit measurement selection from GenericSelect
     */
    public function unitMeasurementSelected($value)
    {
        $this->detailForm['unitMeasurementId'] = $value;
    }

    /**
     * Show movement details modal
     */
    public function showMovementDetails($movementId)
    {
        $this->ensureTenantConnection();
        $movement = InvInventoryAdjustment::with([
            'details.item',
            'details.unitMeasurement',
            'store',
            'reason'
        ])->find($movementId);
        
        if ($movement) {
            // Convert model to array to avoid serialization issues
            $this->movementDetails = [
                'id' => $movement->id,
                'consecutive' => $movement->formatted_consecutive,
                'date' => $movement->date->format('d/m/Y H:i'),
                'type' => $movement->type === 'entrada' ? 'Entrada' : 'Salida',
                'store_name' => $movement->store->name ?? 'N/A',
                'reason_name' => $movement->reason->name ?? 'N/A',
                'user_name' => $movement->user->name ?? 'N/A',
                'status' => $movement->status,
                'observations' => $movement->observations,
                'details' => $movement->details->map(function ($detail) {
                    return [
                        'item_name' => $detail->item->name ?? 'N/A',
                        'quantity' => number_format($detail->quantity, 2),
                        'unit_name' => $detail->unitMeasurement->description ?? 'N/A',
                    ];
                })->toArray()
            ];
            
            $this->showDetailsModal = true;
        }
    }

    /**
     * Close details modal
     */
    public function closeDetailsModal()
    {
        $this->showDetailsModal = false;
        $this->movementDetails = [];
        $this->clearMessages();
    }

    /**
     * Annul a movement and revert inventory
     */
    public function annulMovement($movementId)
    {
        try {
            $this->ensureTenantConnection();
            
            DB::connection('tenant')->beginTransaction();
            
            $movement = InvInventoryAdjustment::with('details')->find($movementId);
            
            if (!$movement) {
                $this->errorMessage = 'Movimiento no encontrado';
                $this->dispatch('annulMovementFailed', message: $this->errorMessage);
                return;
            }

            if ($movement->status === 0) {
                $this->errorMessage = 'Este movimiento ya está anulado';
                $this->dispatch('annulMovementFailed', message: $this->errorMessage);
                return;
            }

            // Revertir el inventario según el tipo de movimiento
            foreach ($movement->details as $detail) {
                $itemStore = InvItemsStore::where('itemId', $detail->itemId)
                    ->where('storeId', $movement->storeId)
                    ->first();
                
                if (!$itemStore) {
                    DB::connection('tenant')->rollBack();
                    $this->errorMessage = 'No se encontró el registro de inventario para el item';
                    $this->dispatch('annulMovementFailed', message: $this->errorMessage);
                    return;
                }

                // Obtener la unidad de medida para calcular la cantidad en unidad de consumo
                $unitMeasurement = UnitMeasurements::find($detail->unitMeasurementId);
                $quantityInConsumptionUnit = $detail->quantity * ($unitMeasurement ? $unitMeasurement->quantity : 1);

                // Si es ENTRADA, al anular debemos RESTAR del inventario
                // Si es SALIDA, al anular debemos SUMAR al inventario
                if ($movement->type === 'entrada') {
                    // Verificar que hay suficiente stock para restar
                    if ($itemStore->stock_items_store < $quantityInConsumptionUnit) {
                        DB::connection('tenant')->rollBack();
                        $this->errorMessage = 'No hay suficiente stock para anular este movimiento de entrada';
                        $this->dispatch('annulMovementFailed', message: $this->errorMessage);
                        return;
                    }
                    
                    // Restar del inventario (revertir la entrada)
                    $itemStore->stock_items_store -= $quantityInConsumptionUnit;
                } else {
                    // Sumar al inventario (revertir la salida)
                    $itemStore->stock_items_store += $quantityInConsumptionUnit;
                }
                
                $itemStore->save();
            }

            // Marcar el movimiento como anulado
            $movement->update(['status' => 0]);
            
            DB::connection('tenant')->commit();
            
            $this->successMessage = 'Movimiento anulado correctamente y el inventario ha sido actualizado';
            
            // Dispatch success event
            $this->dispatch('annulMovementSuccess', message: $this->successMessage);
            
            // Close details modal and refresh list
            $this->closeDetailsModal();
            $this->dispatch('refreshMovements', type: $this->movementType);
            
        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            Log::error('Error al anular movimiento', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->errorMessage = 'Error al anular el movimiento: ' . $e->getMessage();
            $this->dispatch('annulMovementFailed', message: $this->errorMessage);
        }
    }
    
    public function render()
    {
        $this->ensureTenantConnection();
        
        return view('livewire.tenant.movements.components.movement-form');
    }
    
    /**
     * Computed property for reasons based on movement type
     */
    #[Computed]
    public function reasons()
    {
        if (empty($this->warehouseForm['movementType'])) {
            return collect([]);
        }
        
        $movementType = $this->warehouseForm['movementType'] === 'ENTRADA' ? 'e' : 's';
        return InvReason::active()->byType($movementType)->get();
    }
    
    /**
     * Computed property for items
     */
    #[Computed]
    public function items()
    {
        return Items::where('status', 1)->get();
    }
    
    /**
     * Computed property for unit measurements
     */
    #[Computed]
    public function unitMeasurements()
    {
        return UnitMeasurements::where('status', 1)->get();
    }
    /**
     * Open modal to create new movement
     */
    #[On('openMovementForm')]
    public function create()
    {
        $this->showModal = true;
        $this->warehouseForm['movementType'] = ''; // Reset to force selection
        $this->movementForm['date'] = now()->format('Y-m-d');
        $this->clearMessages();
    }
    
    /**
     * Computed property to get warehouse name
     */
    #[Computed]
    public function warehouseMovement()
    {
        $this->ensureTenantConnection();
        
        // Get warehouse from user's contact
        $user = Auth::user();
        
        if (!$user || !$user->contact_id) {
            return 'Sin bodega asignada';
        }
        // Load contact with warehouse relationship
        $contact = \App\Models\Central\VntContact::with('warehouse')
            ->find($user->contact_id);
        
        if (!$contact || !$contact->warehouseId) {
            return 'Sin bodega asignada';
        }
        $warehouse = $contact->warehouse;
        
        // Set the warehouse ID for use in stores
        if ($warehouse && $warehouse->status) {
            $this->warehouseId = $warehouse->id;
            
            // Check stores count to determine if select should be shown
            $warehouseStores = InvStore::where('warehouseId', $this->warehouseId)->where('status', 1)->get();
            
            if ($warehouseStores->count() == 1) {
                $this->showSelectStore = false;
                $this->selectedStoreId = $warehouseStores->first()->id;
            } elseif ($warehouseStores->count() > 1) {
                $this->showSelectStore = true;
            } else {
                $this->showSelectStore = false;
                $this->selectedStoreId = null;
            }

            return $warehouse->name;
        }
        
        return 'Sin bodega asignada';
    }

    /**
     * Computed property for stores based on warehouse
     */
    #[Computed]
    public function stores()
    {
        if (!$this->warehouseId) {
            return collect([]);
        }
        
        $this->ensureTenantConnection();
        return InvStore::where('warehouseId', $this->warehouseId)->where('status', 1)->get();
    }
    /**
     * Add item detail to the table
     */
    public function addDetail()
    {
        // Prevenir múltiples clicks
        if ($this->isProcessing) {
            return;
        }
        
        $this->isProcessing = true;
        
        try {
            // Validate detail form
            $this->ensureTenantConnection();
            
            // Validación básica para evitar errores con clicks múltiples
            if (empty($this->detailForm['itemId'])) {
                $this->errorMessage = 'Debe seleccionar un item';
                $this->isProcessing = false;
                return;
            }
            
            if (empty($this->detailForm['quantity']) || $this->detailForm['quantity'] <= 0) {
                $this->errorMessage = 'La cantidad debe ser mayor a 0';
                $this->isProcessing = false;
                return;
            }
            
            if (empty($this->detailForm['unitMeasurementId'])) {
                $this->errorMessage = 'Debe seleccionar una unidad de medida';
                $this->isProcessing = false;
                return;
            }
            
            // Get item and unit measurement info with all relationships
            $item = Items::with(['invValues', 'purchasingUnit', 'consumptionUnit'])
                ->find($this->detailForm['itemId']);
            
            if (!$item) {
                $this->errorMessage = 'El item seleccionado no existe';
                $this->isProcessing = false;
                return;
            }
            
            $unitMeasurement = UnitMeasurements::find($this->detailForm['unitMeasurementId']);
            
            if (!$unitMeasurement) {
                $this->errorMessage = 'La unidad de medida seleccionada no existe';
                $this->isProcessing = false;
                return;
            }
            
            // Get price from inv_values
            $price = $item->invValues->first()->values ?? 0;
            $quantity = $this->detailForm['quantity'];
            
            // Check if item already exists in details
            $existingIndex = collect($this->details)->search(function ($detail) {
                return $detail['itemId'] == $this->detailForm['itemId'];
            });
            
           
            if ($existingIndex !== false) {
                // Update quantity if item already exists


                $oldQuantity = $this->details[$existingIndex]['quantity'];
                $newQuantity = $oldQuantity + $quantity;
                $this->details[$existingIndex]['quantity'] = $newQuantity;
                $this->details[$existingIndex]['total'] = $price * $newQuantity;
                // Recalculate adjusted quantity
                $currentQty = $this->details[$existingIndex]['currentQuantity'];
                $this->details[$existingIndex]['adjustedQuantity'] = $this->warehouseForm['movementType'] === 'ENTRADA' 
                    ? $currentQty + $newQuantity 
                    : $currentQty - $newQuantity;
            } else {
                // Get current quantity from inv_items_store
                $itemStore = InvItemsStore::where('itemId', $this->detailForm['itemId'])
                    ->where('storeId', $this->selectedStoreId)
                    ->first();
                
                // dd($itemStore->stock_items_store);
                $currentQuantity = $itemStore ? $itemStore->stock_items_store : 0;
                
                // Calculate adjusted quantity based on movement type
                $adjustedQuantity = $this->warehouseForm['movementType'] === 'ENTRADA' 
                    ? $currentQuantity + ($unitMeasurement->quantity * $quantity) 
                    : $currentQuantity - ($unitMeasurement->quantity * $quantity);
            

                 // for exit             
                if($adjustedQuantity < 0){
                    $this->errorMessage = 'La bodega no tiene stock suficiente para realizar el movimiento';
                    $this->isProcessing = false;
                    return;
                }

                // // for entry movement
                // if( floatval($adjustedQuantity) > floatval($itemStore->stock_max)){
                //     $this->errorMessage = 'La bodega no tiene stock suficiente para realizar el movimiento';
                //     return;
                // }
                // Add new detail
                $this->details[] = [
                    'itemId' => $this->detailForm['itemId'],
                    'itemName' => $item->name,
                    'sku' => $item->sku ?? 'N/A',
                    'quantity' => $quantity,
                    'unitMeasurementId' => $this->detailForm['unitMeasurementId'],
                    'unitMeasurementName' => $unitMeasurement->description,
                    'consumptionUnitName' => $unitMeasurement->quantity * $quantity,
                    'currentQuantity' => $currentQuantity,
                    'adjustedQuantity' => $adjustedQuantity,
                    'price' => $price,
                    'total' => $price * $quantity,
                ];
            }

            // Reset detail form
            $this->resetDetailForm();
            $this->clearMessages();
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->isProcessing = false;
            throw $e;
        } catch (\Exception $e) {
            $this->errorMessage = 'Error al agregar el item: ' . $e->getMessage();
            Log::error('Error adding detail to movement', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        } finally {
            $this->isProcessing = false;
        }
    }
    
    /**
     * Remove detail from table
     */
    public function removeDetail($index)
    {
        if (isset($this->details[$index])) {
            unset($this->details[$index]);
            $this->details = array_values($this->details); // Re-index array
        }
    }
    
    /**
     * Save movement with details
     */
    public function saveMovement()
    {
        try {
            // Validate movement form
            // $this->validate([
            //     'warehouseForm.movementType' => 'required|in:ENTRADA,SALIDA',
            //     'movementForm.date' => 'required|date',
            //     'movementForm.reasonId' => 'required|exists:tenant.inv_reasons,id',
            //     'movementForm.observations' => 'nullable|string|max:500',
            // ], [
            //     'warehouseForm.movementType.required' => 'Debe seleccionar el tipo de movimiento',
            //     'warehouseForm.movementType.in' => 'El tipo de movimiento no es válido',
            //     'movementForm.date.required' => 'La fecha es obligatoria',
            //     'movementForm.date.date' => 'La fecha no es válida',
            //     'movementForm.reasonId.required' => 'El motivo es obligatorio',
            //     'movementForm.reasonId.exists' => 'El motivo seleccionado no es válido',
            //     'movementForm.observations.max' => 'Las observaciones no pueden exceder 500 caracteres',
            // ]);
            
            // Validate that there are details
            if (empty($this->details)) {
                $this->errorMessage = 'Debe agregar al menos un item al movimiento';
                return;
            }
            
            $this->ensureTenantConnection();
            
            DB::connection('tenant')->beginTransaction();
            
            try {
                // Get next consecutive by warehouse, store and type
                $selectedType = strtolower($this->warehouseForm['movementType']);
                
                // Get the store to access warehouseId
                $store = InvStore::find($this->selectedStoreId);
                $warehouseId = $store ? $store->warehouseId : null;
                
                // Query for last consecutive filtering by warehouse (through store relationship), store, and type
                $lastMovement = InvInventoryAdjustment::byType($selectedType)
                    ->byStore($this->selectedStoreId)
                    ->whereHas('store', function($query) use ($warehouseId) {
                        $query->where('warehouseId', $warehouseId);
                    })
                    ->orderBy('consecutive', 'desc')
                    ->first();
                $consecutive = $lastMovement ? $lastMovement->consecutive + 1 : 1;
                

            //    dd([
            //         'date'        => $this->movementForm['date'],
            //         'observations'=> $this->movementForm['observations'],
            //         'type'        => $selectedType,
            //         'status'      => 1,
            //         'warehouseId' => $this->warehouseId,
            //         'reasonId'    => $this->movementForm['reasonId'],
            //         'consecutive' => $consecutive,
            //         'storeId'    => $this->selectedStoreId,
            //         'userId'      => Auth::id(),
            //     ]);
                // Create movement
             
                $movement = InvInventoryAdjustment::create([
                    'date' => $this->movementForm['date'],
                    'observations' => $this->movementForm['observations'],
                    'type' => $selectedType,
                    'status' => 1,
                    'storeId' => $this->selectedStoreId,
                    'reasonId' => $this->movementForm['reasonId'],
                    'consecutive' => $consecutive,
                    'userId' => Auth::id()
                ]);
                
                // Create details
                // 
                foreach ($this->details as $detail) {
                    // dd($this->details);
                    InvDetailInventoryAdjustment::create([
                        'inventoryAdjustmentId' => $movement->id,
                        'itemId' => $detail['itemId'],
                        'quantity' => $detail['quantity'],
                        'unitMeasurementId' => $detail['unitMeasurementId'],
                    ]);

                    // Update or create stock
                    $itemStore = InvItemsStore::where('itemId', $detail['itemId'])
                        ->where('storeId', $this->selectedStoreId)
                        ->first();
                    
                    if ($itemStore) {
                        $itemStore->stock_items_store = $detail['adjustedQuantity'];
                        $itemStore->save();
                    } else {
                        // Create new item store record if it doesn't exist
                        InvItemsStore::create([
                            'itemId' => $detail['itemId'],
                            'storeId' => $this->selectedStoreId,
                            'stock_items_store' => $detail['adjustedQuantity'],
                        ]);
                    }
                }
                
                DB::connection('tenant')->commit();
                
                Log::info('Movement created successfully', [
                    'movementId' => $movement->id,
                    'type' => $this->warehouseForm['movementType'],
                    'detailsCount' => count($this->details)
                ]);
                
                // Close modal first
                $this->showModal = false;
                
                // Reset form
                $this->resetForm();
                
                // Cambiar automáticamente a la vista del tipo de movimiento creado
                $this->movementType = $selectedType;
                
                // Refresh the movement list with the movement type
                $this->dispatch('refreshMovements', type: $selectedType);
                
                // Dispatch success event with browser alert (después de cerrar el modal)
                $this->dispatch('movementCreated', [
                    'type' => $selectedType,
                    'message' => 'Movimiento de ' . ($selectedType === 'entrada' ? 'entrada' : 'salida') . ' creado exitosamente'
                ]);
                
            } catch (\Exception $e) {
                DB::connection('tenant')->rollBack();
                throw $e;
            }
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            $this->errorMessage = 'Error al guardar el movimiento: ' . $e->getMessage();
            
            Log::error('Error saving movement', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    /**
     * Close modal and reset form
     */
    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
        $this->clearMessages();
        $this->resetValidation();
    }
    
    /**
     * Reset movement form
     */
    private function resetForm()
    {
        $this->warehouseForm = [
            'movementType' => '',
        ];
        $this->movementForm = [
            'date' => now()->format('Y-m-d'),
            'observations' => '',
            'reasonId' => '',
        ];
        $this->selectedStoreId = null;
        $this->details = [];
        $this->resetDetailForm();
    }
    
    /**
     * Reset detail form
     */
    private function resetDetailForm()
    {
        $this->detailForm = [
            'itemId' => '',
            'quantity' => '',
            'unitMeasurementId' => '',
        ];
        $this->setDefaultUnitMeasurement();
    }
    
    /**
     * Clear messages
     */
    private function clearMessages()
    {
        $this->successMessage = '';
        $this->errorMessage = '';
    }
    
    /**
     * Ensure tenant connection is established
     */
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
        
        // Set tenant connection
        $tenantManager = app(TenantManager::class);
        $tenantManager->setConnection($tenant);
        
        // Initialize tenancy
        tenancy()->initialize($tenant);
    }
}
