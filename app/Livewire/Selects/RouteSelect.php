<?php

namespace App\Livewire\Selects;

use Livewire\Component;
use App\Models\TAT\Routes\TatRoutes;

class RouteSelect extends Component
{
    public $selectedValue = '';
    public $eventName = 'route-changed';
    public $placeholder = 'Seleccionar ruta';
    public $label = 'Ruta';
    public $required = true;
    public $showLabel = true;
    public $district = null;

    public function mount($selectedValue = '', $eventName = 'route-changed', $placeholder = 'Seleccionar ruta', $label = 'Ruta', $required = true, $showLabel = true, $district = null)
    {
        $this->selectedValue = $selectedValue;
        $this->eventName = $eventName;
        $this->placeholder = $placeholder;
        $this->label = $label;
        $this->required = $required;
        $this->showLabel = $showLabel;
        $this->district = $district;
    }

    public function updatedSelectedValue($value)
    {
        $this->dispatch($this->eventName, $value);
    }

    public function getRoutesProperty()
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

        return $query->orderBy('name')
            ->get(['id', 'name', 'zone_id', 'salesman_id', 'sale_day']);
    }

    public function render()
    {
        return view('livewire.selects.route-select', [
            'routes' => $this->routes,
        ]);
    }
}
