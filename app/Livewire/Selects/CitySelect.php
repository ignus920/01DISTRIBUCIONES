<?php

namespace App\Livewire\Selects;

use Livewire\Component;
use App\Models\Central\CnfCity;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;

class CitySelect extends Component
{
    public $cityId = '';
    public $countryId = 48; 
    public $name = 'cityId';
    public $placeholder = 'Seleccionar ciudad';
    public $label = 'Ciudad';
    public $required = true;
    public $showLabel = true;
    // Nota: Ajustamos la clase por defecto para que sea solo para el botón disparador si es necesario
    public $class = 'mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-left bg-white cursor-default sm:text-sm py-2 pl-3 pr-10 relative'; 
    public $index = null;
    public $search = '';
    
    public function mount($cityId = '', $countryId = 48, $name = 'cityId', $placeholder = 'Seleccionar ciudad', $label = 'Ciudad', $required = true, $showLabel = true, $class = null, $index = null)
    {
        $this->cityId = $cityId;
        $this->countryId = $countryId;
        $this->name = $name;
        $this->placeholder = $placeholder;
        $this->label = $label;
        $this->required = $required;
        $this->showLabel = $showLabel;
        $this->index = $index;
        if ($class) {
            $this->class = $class;
        }
    }

    #[On('country-changed')]
    public function updateCountry($countryId)
    {
        $this->countryId = $countryId;
        $this->cityId = '';
        $this->search = '';
        $this->dispatch('validate-city');
    }

    #[On('validate-city')]
    public function validateCity()
    {
        $this->validate([
            'cityId' => 'required',
        ]);
        // Notificar al padre que el hijo pasó la validación
        $this->dispatch('city-valid', index: $this->index, cityId: $this->cityId);
    }

    // Método para seleccionar una ciudad de la lista
    public function selectCity($id)
    {
        \Illuminate\Support\Facades\Log::info('CitySelect: selectCity called', [
            'id' => $id,
            'index' => $this->index,
            'name' => $this->name
        ]);
        
        $this->cityId = $id;
        $this->search = ''; // Opcional: limpiar búsqueda al seleccionar
        
        // El hook updatedCityId se disparará automáticamente al cambiar la propiedad

          if ($this->index !== null) {
            $this->dispatch('city-changed', cityId: $this->cityId, index: $this->index);
        } else {
            $this->dispatch('city-changed', $this->cityId);
        }
        // y se encargará de notificar al padre
    }

    public function updatedCityId()
    {
        \Illuminate\Support\Facades\Log::info('CitySelect: updatedCityId hook triggered', [
            'cityId' => $this->cityId,
            'index' => $this->index,
            'name' => $this->name
        ]);
        
        if ($this->index !== null) {
            $this->dispatch('city-changed', cityId: $this->cityId, index: $this->index);
        } else {
            $this->dispatch('city-changed', $this->cityId);
        }
        
        \Illuminate\Support\Facades\Log::info('CitySelect: city-changed event dispatched', [
            'cityId' => $this->cityId,
            'index' => $this->index
        ]);
    }

    // Propiedad computada para obtener el nombre de la ciudad seleccionada (para mostrar en el input cerrado)
    #[Computed]
    public function selectedCityName()
    {
        if (!$this->cityId) return null;
        return CnfCity::find($this->cityId)?->name;
    }

    #[Computed]
    public function cities()
    {
        $query = CnfCity::where('country_id', $this->countryId);
         
        if (!empty($this->search)) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }

        return $query->select('id', 'name', 'state_id')
            ->orderBy('name')
            ->limit(50) // Importante: Limitar resultados para no saturar el DOM
            ->get();
    }

    public function render()
    {
        return view('livewire.selects.city-select');
    }
}