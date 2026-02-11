<?php

namespace App\Livewire\Selects;

use Livewire\Component;
use App\Models\TAT\Routes\TatRoutes;
use Livewire\Attributes\Computed;

class RouteSelect extends Component
{
    public $selectedValue = '';
    public $eventName = 'route-changed';
    public $placeholder = 'Seleccionar ruta';
    public $label = 'Ruta';
    public $required = true;
    public $showLabel = true;
    public $district = null;
    public $class = 'mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-left bg-white cursor-default sm:text-sm py-2 pl-3 pr-10 relative';
    public $search = '';
    public $name = 'routeId';
    public $salesmanId = null;

    public function mount($selectedValue = '', $eventName = 'route-changed', $placeholder = 'Seleccionar ruta', $label = 'Ruta', $required = true, $showLabel = true, $district = null, $class = null, $name = 'routeId', $salesmanId = null)
    {
        $this->selectedValue = $selectedValue;
        $this->eventName = $eventName;
        $this->placeholder = $placeholder;
        $this->label = $label;
        $this->required = $required;
        $this->showLabel = $showLabel;
        $this->district = $district;
        $this->name = $name;
        $this->salesmanId = $salesmanId;
        
        if ($class) {
            $this->class = $class;
        }
    }

    public function selectRoute($id)
    {
        $this->selectedValue = $id;
        $this->search = '';
        $this->dispatch($this->eventName, $this->selectedValue);
    }

    public function updatedSelectedValue($value)
    {
        $this->dispatch($this->eventName, $value);
    }

    #[Computed]
    public function selectedRouteName()
    {
        if (!$this->selectedValue) return null;
        
        $route = TatRoutes::with(['salesman'])->find($this->selectedValue);
        
        if (!$route) return null;
        
        $name = $route->name;
        if ($route->salesman) {
            $name .= ' - ' . $route->salesman->name;
        }
        if ($route->sale_day) {
            $name .= ' - ' . ucfirst($route->sale_day);
        }
        
        return $name;
    }

    #[Computed]
    public function routes()
    {
        $query = TatRoutes::query()
            ->with(['zones', 'salesman']);

        // Si se proporciona un distrito, filtrar por las rutas que tienen compañías en ese distrito
        if (!empty($this->district)) {
            $query->whereHas('companies', function ($q) {
                $q->whereHas('company.mainWarehouse', function ($warehouseQuery) {
                    $warehouseQuery->where('district', $this->district);
                });
            });
        }

        // Aplicar búsqueda si existe
        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhereHas('salesman', function ($salesmanQuery) {
                      $salesmanQuery->where('name', 'like', '%' . $this->search . '%');
                  })
                  ->orWhere('sale_day', 'like', '%' . $this->search . '%');
            });
        }

        // Si se proporciona un vendedor, filtrar por sus rutas específicamente
        if (!empty($this->salesmanId)) {
            $query->where('salesman_id', $this->salesmanId);
        }

        return $query->orderBy('name')
            ->limit(50)
            ->get(['id', 'name', 'zone_id', 'salesman_id', 'sale_day']);
    }

    public function render()
    {
        return view('livewire.selects.route-select');
    }
}
