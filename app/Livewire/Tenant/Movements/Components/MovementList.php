<?php

namespace App\Livewire\Tenant\Movements\Components;

use Livewire\Component;
use Livewire\WithPagination;
use App\Traits\Livewire\WithExport;
use App\Models\Tenant\Movements\InvInventoryAdjustment;
use App\Models\Tenant\Items\InvItemsStore;
use App\Models\Tenant\Items\UnitMeasurements;
use App\Services\Tenant\TenantManager;
use App\Models\Auth\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MovementList extends Component
{
    use WithPagination, WithExport;

    public $type = 'entrada'; // 'entrada' or 'salida'
    public $search = '';
    public $perPage = 10;
    public $sortField = 'date';
    public $sortDirection = 'desc';

    protected $listeners = ['refreshMovements' => 'refreshList'];

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
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    public function refreshList($type = null)
    {
        // If a type is provided and it matches current type, refresh the list
        if ($type && $type === $this->type) {
            $this->resetPage();
        }
        $this->dispatch('$refresh');
    }

    public function getMovementsProperty()
    {
        $this->ensureTenantConnection();
        return InvInventoryAdjustment::query()
            ->byType($this->type)
            ->with('supplierContact') // Cargar relación del proveedor
            ->withCount('details') // Conteo de items diferentes
            ->withSum('details', 'quantity') // Suma total de cantidades
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('consecutive', 'like', '%' . $this->search . '%')
                        ->orWhere('observations', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);
    }

    public function openDetailsModal($movementId)
    {
        // Emit event to parent component to show details
        $this->dispatch('showMovementDetails', movementId: $movementId);
    }

    public function annulMovement($movementId)
    {
        try {
            $this->ensureTenantConnection();
            
            \Illuminate\Support\Facades\DB::connection('tenant')->beginTransaction();
            
            $movement = InvInventoryAdjustment::with('details')->find($movementId);
            
            if (!$movement) {
                $this->dispatch('notify', type: 'error', message: 'Movimiento no encontrado');
                return;
            }

            if ($movement->status === 0) {
                $this->dispatch('notify', type: 'warning', message: 'Este movimiento ya está anulado');
                return;
            }

            // Revertir el inventario según el tipo de movimiento
            foreach ($movement->details as $detail) {
                $itemStore = \App\Models\Tenant\Items\InvItemsStore::where('itemId', $detail->itemId)
                    ->where('storeId', $movement->storeId)
                    ->first();
                
                if (!$itemStore) {
                    \Illuminate\Support\Facades\DB::connection('tenant')->rollBack();
                    $this->dispatch('notify', type: 'error', message: 'No se encontró el registro de inventario para el item');
                    return;
                }

                // Obtener la unidad de medida para calcular la cantidad en unidad de consumo
                $unitMeasurement = \App\Models\Tenant\Items\UnitMeasurements::find($detail->unitMeasurementId);
                $quantityInConsumptionUnit = $detail->quantity * ($unitMeasurement ? $unitMeasurement->quantity : 1);

                // Si es ENTRADA, al anular debemos RESTAR del inventario
                // Si es SALIDA, al anular debemos SUMAR al inventario
                if ($movement->type === 'entrada') {
                    // Verificar que hay suficiente stock para restar
                    if ($itemStore->stock_items_store < $quantityInConsumptionUnit) {
                        \Illuminate\Support\Facades\DB::connection('tenant')->rollBack();
                        $this->dispatch('notify', type: 'error', message: 'No hay suficiente stock para anular este movimiento de entrada');
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
            
            \Illuminate\Support\Facades\DB::connection('tenant')->commit();
            
            $this->dispatch('notify', type: 'success', message: 'Movimiento anulado correctamente y el inventario ha sido actualizado');
            $this->resetPage();
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::connection('tenant')->rollBack();
            \Illuminate\Support\Facades\Log::error('Error al anular movimiento', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->dispatch('notify', type: 'error', message: 'Error al anular el movimiento: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.tenant.movements.components.movement-list', [
            'movements' => $this->movements,
        ]);
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

    public function getExportData()
    {
        $this->ensureTenantConnection();
        return InvInventoryAdjustment::query()
            ->byType($this->type)
            ->with(['warehouse', 'user', 'reason', 'supplierContact'])
            ->withCount('details')
            ->withSum('details', 'quantity')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('consecutive', 'like', '%' . $this->search . '%')
                        ->orWhere('observations', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->get();
    }

    public function getExportHeadings(): array
    {
        return [
            'Consecutivo',
            'Fecha',
            'Bodega',
            'Sucursal',
            'Usuario',
            'Razón',
            'Proveedor',
            'Cant. Items',
            'Cantidad Total',
            'Estado',
            'Observaciones'
        ];
    }

    public function getExportMapping($movement): array
    {
        return [
            $movement->formatted_consecutive,
            $movement->date->format('d/m/Y'),
            $movement->warehouse->name ?? 'N/A',
            $movement->warehouse_name ?? 'N/A',
            $movement->user->name ?? 'N/A',
            $movement->reason->name ?? 'N/A',
            $movement->supplierContact->firstName ?? '-',
            $movement->details_count ?? 0,
            number_format($movement->details_sum_quantity ?? 0, 2),
            $movement->status === 1 ? 'Registrado' : 'Anulado',
            $movement->observations
        ];
    }

    public function getExportFilename(): string
    {
        return ($this->type === 'entrada' ? 'entradas_inventario_' : 'salidas_inventario_') . date('Y-m-d_His');
    }
}
