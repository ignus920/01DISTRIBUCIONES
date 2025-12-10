<?php

namespace App\Livewire\Selects;

use Livewire\Component;
use App\Models\Auth\User;

class RouteSalesDay extends Component
{
    public $routeId = '';
    public $name = 'routeId';
    public $placeholder = 'Seleccionar ruta';
    public $label = 'Ruta';
    public $required = true;
    public $showLabel = true;
    public $class = 'mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500';

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

    public function updatedRouteId($value)
    {
        $this->dispatch('route-changed', routeId: $value);
    }

    public function getRoutesProperty()
    {
        $sessionTenant = $this->getTenantId();

        return \App\Models\TAT\Routes\TatRoutes::query()
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
            })
            ->orderBy('sale_day')
            ->orderBy('name')
            ->get();
    }

    public function render()
    {
        return view('livewire.selects.route-sales-day', [
            'routes' => $this->routes,
        ]);
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
