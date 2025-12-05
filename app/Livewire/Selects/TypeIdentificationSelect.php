<?php

namespace App\Livewire\Selects;

use Livewire\Component;
use App\Models\Central\CnfTypeIdentification;

class TypeIdentificationSelect extends Component
{
    public $typeIdentificationId = '';
    public $name = 'typeIdentificationId';
    public $placeholder = 'Seleccionar tipo de identificación';
    public $label = 'Tipo de Identificación';
    public $required = true;
    public $showLabel = true;
    public $class = 'mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500';

    public function mount($typeIdentificationId = '', $name = 'typeIdentificationId', $placeholder = 'Seleccionar tipo de identificación', $label = 'Tipo de Identificación', $required = true, $showLabel = true, $class = null)
    {
        $this->typeIdentificationId = $typeIdentificationId;
        $this->name = $name;
        $this->placeholder = $placeholder;
        $this->label = $label;
        $this->required = $required;
        $this->showLabel = $showLabel;
        if ($class) {
            $this->class = $class;
        }
    }

    public function updatedTypeIdentificationId()
    {
        $this->dispatch('type-identification-changed', $this->typeIdentificationId);
    }

    public function getTypeIdentificationsProperty()
    {
        return CnfTypeIdentification::orderBy('name')->get(['id', 'name', 'acronym']);
    }

    public function render()
    {
        return view('livewire.selects.type-identification-select', [
            'typeIdentifications' => $this->typeIdentifications
        ]);
    }
}
