<?php

namespace App\Livewire\Tenant\Reports;

use Livewire\Component;

class FilterReportsGeneric extends Component
{
    // Configuración de campos recibida del componente padre
    public array $fields = [];
    
    // Valores de los filtros
    public array $filters = [];
    
    // Estado de carga
    public bool $isLoading = false;
    
    // Indica si hay filtros activos
    public bool $hasActiveFilters = false;

    protected $rules = [
        'filters.*' => 'nullable',
    ];

    public function mount(array $fields = [])
    {
        $this->fields = $fields;
        
        // Inicializar valores de filtros
        foreach ($this->fields as $field) {
            $this->filters[$field['name']] = $field['default'] ?? null;
        }
    }

    public function applyFilters()
    {
        // Validar fechas si existen
        $this->validateDates();
        
        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }

        $this->isLoading = true;
        $this->hasActiveFilters = $this->checkActiveFilters();
        
        // Emitir evento al componente padre con los filtros
        $this->dispatch('filtersApplied', filters: $this->filters);
        
        $this->isLoading = false;
    }

    public function clearFilters()
    {
        // Restablecer todos los filtros a sus valores por defecto
        foreach ($this->fields as $field) {
            $this->filters[$field['name']] = $field['default'] ?? null;
        }
        
        $this->hasActiveFilters = false;
        
        // Emitir evento al componente padre
        $this->dispatch('filtersCleared');
    }

    protected function validateDates()
    {
        $startDateField = null;
        $endDateField = null;
        
        // Buscar campos de fecha inicial y final
        foreach ($this->fields as $field) {
            if ($field['type'] === 'date') {
                if (str_contains(strtolower($field['name']), 'start') || 
                    str_contains(strtolower($field['name']), 'inicial')) {
                    $startDateField = $field['name'];
                }
                if (str_contains(strtolower($field['name']), 'end') || 
                    str_contains(strtolower($field['name']), 'final')) {
                    $endDateField = $field['name'];
                }
            }
        }
        
        // Validar que la fecha inicial no sea mayor que la final
        if ($startDateField && $endDateField) {
            $startDate = $this->filters[$startDateField] ?? null;
            $endDate = $this->filters[$endDateField] ?? null;
            
            if ($startDate && $endDate && $startDate > $endDate) {
                $this->addError('filters.' . $endDateField, 'La fecha final debe ser mayor o igual a la fecha inicial.');
            }
        }
        
        // Validar campos requeridos
        foreach ($this->fields as $field) {
            if (($field['required'] ?? false) && empty($this->filters[$field['name']])) {
                $this->addError('filters.' . $field['name'], 'Este campo es requerido.');
            }
        }
    }

    protected function checkActiveFilters(): bool
    {
        foreach ($this->filters as $value) {
            if (!empty($value)) {
                return true;
            }
        }
        return false;
    }

    public function render()
    {
        return view('livewire.tenant.reports.filter-reports-generic');
    }
}
