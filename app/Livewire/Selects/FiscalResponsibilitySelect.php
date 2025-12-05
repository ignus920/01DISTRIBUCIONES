<?php

namespace App\Livewire\Selects;

use Livewire\Component;
use App\Models\Central\CnfFiscalResponsability;

class FiscalResponsibilitySelect extends Component
{
    public $fiscalResponsibilityId  = '';
    public $name = 'fiscalResponsibilityId';
    public $placeholder = 'Seleccionar responsabilidad fiscal';
    public $label = 'Responsabilidad Fiscal';
    public $required = true;
    public $showLabel = true;
    public $class = 'mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500';

    public function mount($fiscalResponsibilityId  = '', $name = 'fiscalResponsibilityId', $placeholder = 'Seleccionar responsabilidad fiscal', $label = 'Responsabilidad Fiscal', $required = true, $showLabel = true, $class = null)
    {
        \Illuminate\Support\Facades\Log::info('🔍 FISCAL SELECT - Mount recibido:', [
            'parametro_recibido' => $fiscalResponsibilityId,
            'asignando_a_propiedad' => 'fiscalResponsibilityId'
        ]);

        $this->fiscalResponsibilityId  = $fiscalResponsibilityId ;
        $this->name = $name;
        $this->placeholder = $placeholder;
        $this->label = $label;
        $this->required = $required;
        $this->showLabel = $showLabel;
        if ($class) {
            $this->class = $class;
        }
    }

    public function updatedFiscalResponsibilityId()
    {
        $this->dispatch('fiscal-responsibility-changed', $this->fiscalResponsibilityId );
    }

    public function getFiscalResponsibilitiesProperty()
    {
        return CnfFiscalResponsability::orderBy('id')
            ->get(['id', 'description']);
    }

    public function render()
    {
        return view('livewire.selects.fiscal-responsibility-select', [
            'fiscalResponsibilities' => $this->fiscalResponsibilities 
        ]);
    }
}