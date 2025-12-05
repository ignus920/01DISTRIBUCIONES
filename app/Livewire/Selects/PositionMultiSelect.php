<?php

namespace App\Livewire\Selects;

use Livewire\Component;
use App\Models\Tenant\CnfPosition;

class PositionMultiSelect extends Component
{
    public $selectedPositions = [];
    public $name = 'positions';
    public $placeholder = 'Seleccionar posiciones';
    public $label = 'Posiciones';
    public $required = false;
    public $showLabel = true;
    public $class = 'mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500';
    public $maxHeight = 'max-h-60';
    public $searchable = true;
    public $search = '';
    public $isOpen = false;

    public function mount($selectedPositions = [], $name = 'positions', $placeholder = 'Seleccionar posiciones', $label = 'Posiciones', $required = true, $showLabel = true, $class = null, $maxHeight = 'max-h-60', $searchable = true)
    {
        $this->selectedPositions = is_array($selectedPositions) ? $selectedPositions : [];
        $this->name = $name;
        $this->placeholder = $placeholder;
        $this->label = $label;
        $this->required = $required;
        $this->showLabel = $showLabel;
        $this->maxHeight = $maxHeight;
        $this->searchable = $searchable;
        
        if ($class) {
            $this->class = $class;
        }
    }

    public function toggleDropdown()
    {
        $this->isOpen = !$this->isOpen;
        if (!$this->isOpen) {
            $this->search = '';
        }
    }

    public function closeDropdown()
    {
        $this->isOpen = false;
        $this->search = '';
    }

    public function togglePosition($positionId)
    {
        if (in_array($positionId, $this->selectedPositions)) {
            $this->selectedPositions = array_values(array_filter($this->selectedPositions, function($id) use ($positionId) {
                return $id != $positionId;
            }));
        } else {
            $this->selectedPositions[] = $positionId;
        }

        $this->dispatch('positions-changed', $this->selectedPositions);
    }

    public function removePosition($positionId)
    {
        $this->selectedPositions = array_values(array_filter($this->selectedPositions, function($id) use ($positionId) {
            return $id != $positionId;
        }));

        $this->dispatch('positions-changed', $this->selectedPositions);
    }

    public function clearAll()
    {
        $this->selectedPositions = [];
        $this->dispatch('positions-changed', $this->selectedPositions);
    }

    public function getPositionsProperty()
    {
        $query = CnfPosition::active()->orderBy('name');
        
        if ($this->searchable && !empty($this->search)) {
            $query->where('name', 'like', '%' . $this->search . '%');
        }
        
        return $query->get(['id', 'name']);
    }

    public function getSelectedPositionNamesProperty()
    {
        if (empty($this->selectedPositions)) {
            return collect();
        }

        return CnfPosition::whereIn('id', $this->selectedPositions)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function render()
    {
        return view('livewire.selects.position-multi-select', [
            'positions' => $this->positions,
            'selectedPositionNames' => $this->selectedPositionNames
        ]);
    }
}