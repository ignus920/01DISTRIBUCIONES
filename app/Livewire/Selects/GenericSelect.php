<?php

namespace App\Livewire\Selects;

use Livewire\Component;
use Livewire\Attributes\Computed;

class GenericSelect extends Component
{
    public $selectedValue = '';
    public $items = [];
    public $name = 'genericSelect';
    public $placeholder = 'Seleccionar opción';
    public $label = 'Seleccionar';
    public $required = false;
    public $showLabel = true;
    public $class = 'mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 text-left bg-white cursor-default sm:text-sm py-2 pl-3 pr-10 relative';
    public $eventName = 'item-selected';
    public $search = '';
    public $displayField = 'name';
    public $valueField = 'id';
    public $searchFields = ['name'];
    public $index = null;

    public function mount(
        $selectedValue = '',
        $items = [],
        $name = 'genericSelect',
        $placeholder = 'Seleccionar opción',
        $label = 'Seleccionar',
        $required = false,
        $showLabel = true,
        $class = null,
        $eventName = 'item-selected',
        $displayField = 'name',
        $valueField = 'id',
        $searchFields = ['name'],
        $index = null
    ) {
        $this->selectedValue = $selectedValue;
        $this->items = $items;
        $this->name = $name;
        $this->placeholder = $placeholder;
        $this->label = $label;
        $this->required = $required;
        $this->showLabel = $showLabel;
        $this->eventName = $eventName;
        $this->displayField = $displayField;
        $this->valueField = $valueField;
        $this->searchFields = $searchFields;
        $this->index = $index;
        
        if ($class) {
            $this->class = $class;
        }
    }

    public function selectItem($value)
    {
        $this->selectedValue = $value;
        $this->search = '';
        
        if ($this->index !== null) {
            $this->dispatch($this->eventName, value: $this->selectedValue, index: $this->index);
        } else {
            $this->dispatch($this->eventName, $this->selectedValue);
        }
    }

    public function updatedSelectedValue()
    {
        if ($this->index !== null) {
            $this->dispatch($this->eventName, value: $this->selectedValue, index: $this->index);
        } else {
            $this->dispatch($this->eventName, $this->selectedValue);
        }
    }

    #[Computed]
    public function selectedItemName()
    {
        if (!$this->selectedValue) return null;
        
        $item = collect($this->items)->firstWhere($this->valueField, $this->selectedValue);
        
        if (!$item) return null;
        
        return is_array($item) ? $item[$this->displayField] : $item->{$this->displayField};
    }

    #[Computed]
    public function filteredItems()
    {
        if (empty($this->search)) {
            return collect($this->items)->take(50);
        }

        $search = strtolower($this->search);
        
        return collect($this->items)->filter(function ($item) use ($search) {
            foreach ($this->searchFields as $field) {
                $value = is_array($item) ? ($item[$field] ?? '') : ($item->{$field} ?? '');
                if (str_contains(strtolower($value), $search)) {
                    return true;
                }
            }
            return false;
        })->take(50);
    }

    public function render()
    {
        return view('livewire.selects.generic-select');
    }
}
