<?php

namespace App\Livewire\Tenant\Parameters;

use Livewire\Component;
use App\Models\Tenant\Parameters\PriceList as PriceListModel;
use App\Services\Tenant\TenantManager;
use App\Models\Auth\Tenant;
use Carbon\Carbon;

/**
 * Componente Livewire para gestionar las listas de precios
 * Permite crear, editar, eliminar y cambiar el estado de las listas
 */
class PriceList extends Component
{
    use \App\Traits\Livewire\WithExport;

    // Propiedades del modelo
    public $pricelist_id, $title, $value, $status, $createAd;

    // Propiedades para la tabla
    public $search = '';              // Búsqueda
    public $sortField = 'title';      // Campo de ordenamiento
    public $sortDirection = 'asc';    // Dirección de ordenamiento
    public $showModal = false;        // Mostrar/ocultar modal
    public $confirmingItemDeletion = false;  // Confirmación de eliminación
    public $pricelistIdToDelete;      // ID del elemento a eliminar
    public $perPage = 10;             // Registros por página

    // Reglas de validación
    protected $rules = [
        'title' => 'required|min:2|max:10',  // Título requerido, mínimo 2 caracteres, máximo 10
        'value' => 'required|numeric|min:0', // Valor requerido, numérico y positivo
    ];

    // Mensajes de validación personalizados
    protected $messages = [
        'title.required' => 'El título es obligatorio',
        'title.min' => 'El título debe tener al menos 2 caracteres',
        'title.max' => 'El título no puede exceder 10 caracteres',
        'value.required' => 'El valor es obligatorio',
        'value.numeric' => 'El valor debe ser numérico',
        'value.min' => 'El valor debe ser mayor o igual a 0',
    ];

    /**
     * Resetear el formulario a sus valores iniciales
     */
    public function resetForm()
    {
        $this->title = '';
        $this->value = '';
        $this->status = '';
        $this->createAd = null;
    }

    /**
     * Asegurar que la conexión del tenant esté establecida
     * Verifica que exista un tenant en sesión y lo inicializa
     */
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

    /**
     * Ordenar la tabla por un campo específico
     * Alterna entre ascendente y descendente
     */
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

    /**
     * Inicializar el componente
     * Se ejecuta al montar el componente
     */
    public function mount()
    {
        $this->ensureTenantConnection();
    }

    /**
     * Abrir modal para crear un nuevo registro
     */
    public function create()
    {
        $this->resetExcept(['pricelists']);
        $this->showModal = true;
    }

    /**
     * Editar un registro existente
     * Carga los datos del registro en el formulario
     */
    public function edit($id)
    {
        $this->ensureTenantConnection();
        $pricelist = PriceListModel::findOrFail($id);
        
        $this->title = $pricelist->title;
        $this->value = $pricelist->value;
        $this->pricelist_id = $pricelist->id;
        $this->showModal = true;
    }

    /**
     * Cancelar la operación actual
     * Cierra el modal y resetea el formulario
     */
    public function cancel()
    {
        $this->resetValidation();
        $this->reset([
            'title',
            'value'
        ]);
        $this->showModal = false;
        $this->confirmingItemDeletion = false;
    }

    /**
     * Guardar un registro (crear o actualizar)
     * Valida los datos y los guarda en la base de datos
     */
    public function save()
    {
        $this->ensureTenantConnection();
        $this->validate();

        $pricelistData = [
            'title' => $this->title,
            'value' => $this->value,
        ];

        if ($this->pricelist_id) {
            // Actualizar registro existente
            $pricelist = PriceListModel::findOrFail($this->pricelist_id);
            $pricelistData['updateAd'] = Carbon::now();
            $pricelist->update($pricelistData);
            session()->flash('message', 'Lista de precios actualizada correctamente.');
        } else {
            // Crear nuevo registro
            $pricelistData['createAd'] = Carbon::now();
            $pricelistData['status'] = 1; // Por defecto activo
            PriceListModel::create($pricelistData);
            session()->flash('message', 'Lista de precios creada correctamente.');
        }

        $this->resetValidation();
        $this->reset([
            'title',
            'value',
        ]);
        $this->showModal = false;
    }

    /**
     * Cambiar el estado de una lista de precios (activo/inactivo)
     * Alterna entre 1 (activo) y 0 (inactivo)
     */
    public function togglePriceListStatus($id)
    {
        $this->ensureTenantConnection();
        $item = PriceListModel::findOrFail($id);

        $newStatus = $item->status ? 0 : 1;
        $item->update([
            'status' => $newStatus,
            'updateAd' => Carbon::now(),
        ]);

        session()->flash('message', 'Estado actualizado correctamente');
    }

    /**
     * Confirmar la eliminación de un registro
     * Muestra el modal de confirmación
     */
    public function confirmItemDeletion($id)
    {
        $this->confirmingItemDeletion = true;
        $this->pricelistIdToDelete = $id;
    }

    /**
     * Eliminar (desactivar) una lista de precios
     * Marca el registro como eliminado y lo desactiva
     */
    public function deletePriceList()
    {
        $this->ensureTenantConnection();

        $pricelistData = [
            'status' => 0,
            'updateAd' => Carbon::now(),
        ];

        $pricelist = PriceListModel::findOrFail($this->pricelistIdToDelete);
        $pricelist->update($pricelistData);
        $this->confirmingItemDeletion = false;
        $this->reset(['pricelistIdToDelete']);
        session()->flash('message', 'Lista de precios eliminada correctamente');
    }

    /**
     * Renderizar el componente
     * Obtiene los datos y los pasa a la vista
     */
    public function render()
    {
        $this->ensureTenantConnection();
        
        // Consultar las listas de precios con filtros y ordenamiento
        $pricelists = PriceListModel::query()
            ->when($this->search, function ($query) {
                $query->where('title', 'like', '%' . $this->search . '%');
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);
            
        return view('livewire.tenant.parameters.price-list', [
            'pricelists' => $pricelists
        ]);
    }

    /**
     * Métodos para Exportación
     */

    protected function getExportData()
    {
        $this->ensureTenantConnection();
        return PriceListModel::query()
            ->when($this->search, function ($query) {
                $query->where('title', 'like', '%' . $this->search . '%');
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->get();
    }

    protected function getExportHeadings(): array
    {
        return ['ID', 'Título', 'Valor', 'Estado', 'Fecha Registro'];
    }

    protected function getExportMapping()
    {
        return function($pricelist) {
            return [
                $pricelist->id,
                $pricelist->title,
                $pricelist->value,
                $pricelist->status ? 'Activo' : 'Inactivo',
                $pricelist->createAd ? Carbon::parse($pricelist->createAd)->format('Y-m-d H:i:s') : 'N/A',
            ];
        };
    }

    protected function getExportFilename(): string
    {
        return 'listas_precios_' . now()->format('Y-m-d_His');
    }
}
