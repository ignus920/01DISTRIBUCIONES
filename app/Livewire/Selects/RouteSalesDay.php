<?php

namespace App\Livewire\Selects;

use Livewire\Component;
use App\Models\Auth\User;
use Livewire\Attributes\Computed;

class RouteSalesDay extends Component
{
    public $routeId = '';
    public $name = 'routeId';
    public $placeholder = 'Seleccionar ruta';
    public $label = 'Ruta';
    public $required = true;
    public $showLabel = true;
    public $class = 'mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-left bg-white cursor-default sm:text-sm py-2 pl-3 pr-10 relative';
    public $search = '';

    public function mount($routeId = '', $name = 'routeId', $placeholder = 'Seleccionar ruta', $label = 'Ruta', $required = true, $showLabel = true, $class = null)
    {
        $this->routeId = $routeId;
        $this->name = $name;
        $this->placeholder = $placeholder;
        $this->label = $label;
        $this->required = $required;
        $this->showLabel = $showLabel;
        if ($class) {
            $this->class = $class;
        }
    }

    public function selectRoute($id)
    {
        $this->routeId = $id;
        $this->search = '';
        $this->dispatch('route-changed', routeId: $this->routeId);
    }

    public function updatedRouteId($value)
    {
        $this->dispatch('route-changed', routeId: $value);
    }

    #[Computed]
    public function selectedRouteName()
    {
        if (!$this->routeId) return null;
        
        $route = \App\Models\TAT\Routes\TatRoutes::with(['salesman'])->find($this->routeId);
        
        if (!$route) return null;
        
        return ucfirst($route->sale_day) . ' - ' . ($route->salesman?->name ?? 'Sin vendedor') . ' - ' . $route->name;
    }

    #[Computed]
    public function routes()
    {
        $sessionTenant = $this->getTenantId();

        $query = \App\Models\TAT\Routes\TatRoutes::query()
            ->with([
                'zones', // Relación con zonas
                'salesman' => function($query) { // Relación con vendedor (User)
                    $query->select('id', 'name', 'email', 'profile_id');
                }
            ])
            // Filtrar solo rutas que tengan un vendedor con profile_id = 4 y del tenant actual
            ->whereHas('salesman', function($query) use ($sessionTenant) {
                $query->where('profile_id', 4) // Solo vendedores
                      ->whereHas('tenants', function($q) use ($sessionTenant) {
                          $q->where('tenants.id', $sessionTenant);
                      });
            });

        // Aplicar búsqueda si existe
        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('sale_day', 'like', '%' . $this->search . '%')
                  ->orWhereHas('salesman', function ($salesmanQuery) {
                      $salesmanQuery->where('name', 'like', '%' . $this->search . '%');
                  });
            });
        }

        return $query->orderBy('sale_day')
            ->orderBy('name')
            ->limit(50)
            ->get();
    }

    public function render()
    {
        return view('livewire.selects.route-sales-day');
    }

     private function getTenantId()
    {
        $tenantId = session('tenant_id');

        if (!$tenantId) {
            throw new \Exception('No tenant selected');
        }
        return $tenantId;
    }
}
