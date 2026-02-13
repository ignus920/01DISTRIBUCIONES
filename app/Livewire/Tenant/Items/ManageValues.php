<?php

namespace App\Livewire\Tenant\Items;

use Livewire\Component;
use App\Models\Tenant\Items\InvValues;
use App\Models\Tenant\Items\Items;
use App\Models\Auth\Tenant;
//Servicios
use App\Services\Tenant\TenantManager;

class ManageValues extends Component
{
    public $invValuesId;
    public $type = '';
    public $ItemId;
    public $itemName;
    public $warehouseId = 0;
    public $label;
    public $created_at;
    public $successMessage = null;
    public $warningMessage = null;
    public $newValues = []; // Array para almacenar valores nuevos

    protected $listeners = ['refreshValues' => '$refresh'];

    public function getValuesItems()
    {
        if (!$this->ItemId) {
            return collect();
        }
        return InvValues::where('itemId', $this->ItemId)->get();
    }

    public function updatedValuesItem()
    {
        $this->dispatch('invValuesItem-changed', $this->ItemId);
    }

    public function mount($ItemId)
    {
        $this->ItemId = $ItemId;
        $this->loadValuesData();
    }

    public function render()
    {
        $this->ensureTenantConnection();
        $values = $this->getValuesItems();
        return view('livewire.tenant.items.manage-values', [
            'values' => $values
        ]);
    }

    public function loadValuesData()
    {
        $this->ensureTenantConnection();
        $values = InvValues::where('itemId', $this->ItemId)->first();

        if ($values) {
            $this->type = $values->type;
        }
        $item = Items::find($this->ItemId);
        $this->itemName = $item ? $item->name : '';
    }

    public function deleteValue($valueId)
    {
        $this->ensureTenantConnection();
        $value = InvValues::find($valueId);

        if ($value && $value->itemId == $this->ItemId) {
            $value->delete();
            $this->warningMessage = "Valor eliminado exitosamente";
        }
    }

    public function updateValue($valueId, $newValue)
    {
        $this->ensureTenantConnection();
        $this->successMessage = null;

        // Validar que el valor sea numérico y mayor o igual a 0
        if (!is_numeric($newValue) || $newValue < 0) {
            $this->addError('value', 'El valor debe ser un número mayor o igual a 0');
            return;
        }

        $value = InvValues::find($valueId);

        if ($value && $value->itemId == $this->ItemId) {
            $oldValue = $value->values;
            $value->update(['values' => $newValue]);

            $this->successMessage = "Valor actualizado exitosamente de $" . number_format($oldValue, 2) . " a $" . number_format($newValue, 2);
        } else {
            $this->addError('value', 'No se pudo actualizar el valor. Verifique que el registro sea válido.');
        }
    }

    /**
     * Agregar un nuevo valor al array temporal
     */
    public function addNewValue($label, $value)
    {
        // Validar que el valor sea numérico y mayor que 0
        if (!is_numeric($value) || $value <= 0) {
            $this->warningMessage = "El valor debe ser un número mayor a 0";
            return;
        }

        // Definir el tipo basado en la etiqueta
        $typeMap = [
            'Costo Inicial' => 'costo',
            'Costo' => 'costo',
            'Precio Base' => 'precio',
            'Precio Regular' => 'precio',
            'Precio Crédito' => 'precio',
        ];

        $type = $typeMap[$label] ?? 'costo';

        try {
            // Guardar directamente en la base de datos
            $exists = InvValues::where('itemId', $this->ItemId)
                ->where('label', $label)
                ->exists();

            if (!$exists) {
                InvValues::create([
                    'itemId' => $this->ItemId,
                    'label' => $label,
                    'type' => $type,
                    'values' => (float)$value,
                    'date' => \Carbon\Carbon::now(),
                    'warehouseId' => 0,
                ]);

                $this->successMessage = "Valor '{$label}' registrado correctamente.";
                // Refrescar la vista para mostrar el nuevo valor
                $this->dispatch('refreshValues');
            } else {
                $this->warningMessage = "El valor '{$label}' ya existe.";
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error al guardar valor: ' . $e->getMessage());
            $this->warningMessage = "Error al guardar el valor: " . $e->getMessage();
        }
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
