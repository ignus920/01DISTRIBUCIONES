<?php

namespace App\Livewire\Selects;

use Livewire\Component;
use App\Models\Central\CnfCountry;

class CountrySelect extends Component
{
    public $countryId = 48; // Colombia por defecto
    public $name = 'countryId';
    public $placeholder = 'Seleccionar país';
    public $label = 'País';
    public $required = true;
    public $showLabel = true;
    public $class = 'mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500';
    public function mount($countryId = 48, $name = 'countryId', $placeholder = 'Seleccionar país', $label = 'País', $required = true, $showLabel = true, $class = null)
    {
        $this->countryId = $countryId;
        $this->name = $name;
        $this->placeholder = $placeholder;
        $this->label = $label;
        $this->required = $required;
        $this->showLabel = $showLabel;
        if ($class) {
            $this->class = $class;
        }
    }

    public function updatedCountryId()
    {
        $this->dispatch('country-changed', $this->countryId);
    }

    public function getCountriesProperty()
    {
        return CnfCountry::orderBy('name')->get(['id', 'name', 'iso2', 'iso3']);
    }

    public function render()
    {
        return view('livewire.selects.country-select', [
            'countries' => $this->countries
        ]);
    }
}
