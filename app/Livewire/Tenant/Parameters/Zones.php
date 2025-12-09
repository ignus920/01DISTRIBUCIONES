<?php

namespace App\Livewire\Tenant\Parameters;

use Livewire\Component;
use App\Models\TAT\Zones\TatZones;
use Carbon\Carbon;

class Zones extends Component
{
    public $zoneId, $name, $created_at, $updated_at;

    //Propiedades para la tabla
    public $search = '';
    public $sortField = 'name';
    public $sortDirection = 'asc';
    public $showModal = false;        // Mostrar/ocultar modal
    public $perPage = 10;

    // Reglas de validación
    protected $rules = [
        'name' => 'required|min:2|max:10',  // Nombre requerido, mínimo 2 caracteres, máximo 10
    ];

    // Mensajes de validación personalizados
    protected $messages = [
        'name.required' => 'El nombre es obligatorio',
    ];

    /**
     * Resetear el formulario a sus valores iniciales
     */
    public function resetForm()
    {
        $this->name = '';
        $this->created_at = null;
        $this->updated_at = null;
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
     * Abrir modal para crear un nuevo registro
     */
    public function create()
    {
        $this->resetExcept(['zones']);
        $this->showModal = true;
    }

    /**
     * Editar un registro existente
     * Carga los datos del registro en el formulario
     */
    public function edit($id)
    {
        //$this->ensureTenantConnection();
        $zone = TatZones::findOrFail($id);
        
        $this->name = $zone->name;
        $this->zoneId = $zone->id;
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
            'name'
        ]);
        $this->showModal = false;
    }

    /**
     * Guardar un registro (crear o actualizar)
     * Valida los datos y los guarda en la base de datos
     */
    public function save(){
        $this->validate();

        $zoneData = [
            'name' => $this->name,
        ];

        if($this->zoneId){
            //Actualizar zona existente
            $zone=TatZones::findOrFail($this->zoneId);
            $zoneData['updated_at'] = Carbon::now();
            $zone->update($zoneData);
            session()->flash('message', 'Zona actualizada correctamente.');
        }else{
            // Crear nuevo registro
            $zoneData['created_at'] = Carbon::now();
            TatZones::create($zoneData);
            session()->flash('message', 'Zona registrada correctamente.');
        }

        $this->resetValidation();
        $this->reset([
            'name',
        ]);
        $this->showModal = false;
    }

    /**
     * Cambiar el estado de una lista de precios (activo/inactivo)
     * Alterna entre 1 (activo) y 0 (inactivo)
     */

    public function render()
    {
        $zones = TatZones::query()
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%');
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);
        return view('livewire.tenant.parameters.zones', [
            'zones' => $zones
        ]);
    }
}
