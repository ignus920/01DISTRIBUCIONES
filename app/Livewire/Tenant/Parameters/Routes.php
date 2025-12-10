<?php

namespace App\Livewire\Tenant\Parameters;

use Livewire\Component;
use App\Models\Tat\Routes\TatRoutes;
use App\Models\Tat\Zones\TatZones;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class Routes extends Component
{
    public $routeId, $name, $zone_id, $salesman_id, $sale_day, $delivery_day, $created_at, $updated_at;

    //Propiedades para la tabla
    public $search = '';
    public $sortField = 'name';
    public $sortDirection = 'asc';
    public $showModal = false;        // Mostrar/ocultar modal
    public $perPage = 10;

     protected $listeners = ['user-changed' => 'updateVendedor'];

    public $days = [
        'lunes' => 'Lunes',
        'martes' => 'Martes',
        'miercoles' => 'Miércoles',
        'jueves' => 'Jueves',
        'viernes' => 'Viernes',
        'sabado' => 'Sábado',
        'domingo' => 'Domingo',
    ];

    // Reglas de validación
    protected $rules = [
        'name' => 'required|min:2',  // Nombre requerido, mínimo 2 caracteres, máximo 10
        'zone_id' => 'required',
        'sale_day' => 'required',
        'delivery_day' => 'required',
    ];

    protected $messages = [
        'name.required' => 'El nombre es obligatorio',
        'zone_id.required' => 'La zona es obligatoria',
        'sale_day' => 'Debe seleccionar un día de venta',
        'delivery_day' => 'Debe seleccionar un día de entrega',
    ];

    public function resetForm()
    {
        $this->name = '';
        $this->sale_day = '';
        $this->delivery_day = '';
        $this->created_at = null;
        $this->updated_at = null;
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

    public function create()
    {
        $this->resetExcept(['routes']);
        $this->showModal = true;
    }

    public function edit($id)
    {
        //$this->ensureTenantConnection();
        $route = TatRoutes::findOrFail($id);
        
        $this->name = $route->name;
        $this->zone_id = $route->zone_id;
        $this->salesman_id = $route->salesman_id;
        $this->sale_day = $route->sale_day;
        $this->delivery_day = $route->delivery_day;
        $this->routeId = $route->id;
        $this->showModal = true;
    }

    public function cancel()
    {
        $this->resetValidation();
        $this->reset([
            'name',
            'zone_id',
            'salesman_id',
            'sale_day',
            'delivery_day',
        ]);
        $this->showModal = false;
    }

    public function save(){
        $this->validate();

        $routeData = [
            'name' => $this->name,
            'zone_id' => $this->zone_id,
            'salesman_id' => $this->salesman_id,
            'sale_day' => $this->sale_day,
            'delivery_day' => $this->delivery_day,
        ];

        if($this->routeId){
            //Actualizar ruta existente
            $route=TatRoutes::findOrFail($this->routeId);
            $routeData['updated_at'] = Carbon::now();
            $route->update($routeData);
            session()->flash('message', 'Ruta actualizada correctamente.');
        }else{
            // Crear nuevo registro
            $routeData['created_at'] = Carbon::now();
            TatRoutes::create($routeData);
            session()->flash('message', 'Ruta registrada correctamente.');
        }

        $this->resetValidation();
        $this->reset([
            'name',
            'zone_id',
            'salesman_id',
            'sale_day',
            'delivery_day',
        ]);
        $this->showModal = false;
    }

    public function updateVendedor($userId)
    {
        $this->salesman_id = $userId;
    }

    public function render()
    {
        $routes = TatRoutes::query()
            ->with(['zones'])
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%');
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        // Obtener zonas ordenadas por nombre (no usar all()->get() sobre una colección)
        $zones = TatZones::orderBy('name')->get();

        return view('livewire.tenant.parameters.routes', [
            'routes' => $routes,
            'zones' => $zones,
            'days' => $this->days,
        ]);
    }
}
