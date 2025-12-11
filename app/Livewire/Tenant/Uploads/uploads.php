<?php

namespace App\Livewire\Tenant\Uploads;

use App\Models\TAT\Categories\TatCategories;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
//Modelos
use App\Models\TAT\Routes;
use App\Models\TAT\Routes\TatRoutes;
use App\Models\Tenant\Remissions\InvRemissions;
use App\Models\Tenant\DeliveriesList\DisDeliveriesList;
//Services
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class Uploads extends Component
{
    //Propiedades para la tabla
    public $showModal = false;
    public $search = '';
    public $sortField = 'consecutive';
    public $sortDirection = 'desc';
    public $perPage = 10;
    public $selectedDate = '';
    public $remissions = [];
    public $selectedRoute = '';

    public function updatedSelectedDate($value)
    {
        // Solo hacer la consulta si hay fecha válida
        if ($value) {
            try {
                $this->remissions = $this->getRemissions($value);
            } catch (\Exception $e) {
                session()->flash('error', 'Error al cargar las remisiones: ' . $e->getMessage());
                $this->remissions = [];
            }
        } else {
            $this->remissions = [];
        }
    }

    public function updatedSelectedRoute($value)
    {
        if ($value && $this->selectedDate) {
            $this->remissions = $this->getRemissions($this->selectedDate, $value);
        }
    }

    public function getRemissions($date, $routeId = null){
        $query = DB::table('inv_remissions as r')
                        ->join('users as u', 'r.userId', '=', 'u.id')
                        ->select(
                            'u.name',
                            'r.userId',
                            DB::raw('COUNT(r.userId) as total_registros'),
                            DB::raw('DATE(r.deliveryDate) as fecha')
                        )
                        ->whereDate('r.deliveryDate', $date);
        
        if ($routeId) {
            $query->where('r.routeId', $routeId);
        }
        
        return $query->groupBy('u.id', 'u.name', DB::raw('DATE(r.deliveryDate)'))
                     ->get();
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

    public function clearDate()
    {
        $this->selectedDate = '';
        $this->remissions = [];
    }

    public function cargar($userId)
    {
        if (!$this->selectedDate) {
            session()->flash('error', 'Por favor selecciona una fecha primero');
            return;
        }
    
        // Tu lógica de carga aquí
        try{
            $uploadData=[
                'sale_date' => $this->selectedDate,
                'salesman_id' => $userId,
                'user_id' => Auth::id(),
                'created_at' => Carbon::now()
            ];
            DisDeliveriesList::create($uploadData);
            
            session()->flash('message', "Cargando datos para el usuario ID: $userId - Fecha: {$this->selectedDate}");
        }catch(\Exception $e){
            // Para debug, muestra un mensaje
            session()->flash('error', "Error al registrar el cargue".$e->getMessage());
        }
    
    }

    public function render()
    {
        $users = DB::table('users')->select('id', 'name')->where('profile_id', 13)->get();
        return view('livewire.tenant.uploads.uploads', [
            'users' => $users,
            'remissions' => $this->remissions,
        ]);
    }
}
