<?php

namespace App\Livewire\Selects;

use Livewire\Component;
use App\Models\Central\CnfRegime;

class RegimeSelect extends Component
{
    public $regimeId = '';
    public $name = 'regimeId';
    public $placeholder = 'Seleccionar régimen';
    public $label = 'Régimen';
    public $required = true;
    public $showLabel = true;
    public $class = 'mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500';

    public function mount($regimeId = '', $name = 'regimeId', $placeholder = 'Seleccionar régimen', $label = 'Régimen', $required = true, $showLabel = true, $class = null)
    {
        $this->regimeId = $regimeId;
        $this->name = $name;
        $this->placeholder = $placeholder;
        $this->label = $label;
        $this->required = $required;
        $this->showLabel = $showLabel;
        if ($class) {
            $this->class = $class;
        }
    }

    public function updatedRegimeId()
    {
        $this->dispatch('regime-changed', $this->regimeId);
    }

    public function getRegimesProperty()
    {
        return CnfRegime::where('status', 1)
            ->orderBy('name')
            ->get(['id', 'name', 'description']);
    }

    public function render()
    {
        return view('livewire.selects.regime-select', [
            'regimes' => $this->regimes
        ]);
    }
}