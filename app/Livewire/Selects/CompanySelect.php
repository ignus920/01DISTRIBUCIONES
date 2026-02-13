<?php

namespace App\Livewire\Selects;

use Livewire\Component;
use App\Models\Tenant\Customer\VntCompany;

class CompanySelect extends Component
{
    public $selectedValue = '';
    public $eventName = 'company-changed';
    public $placeholder = 'Seleccionar cliente';
    public $label = 'Cliente';
    public $required = true;
    public $showLabel = true;

    public function mount($selectedValue = '', $eventName = 'company-changed', $placeholder = 'Seleccionar cliente', $label = 'Cliente', $required = true, $showLabel = true)
    {
        $this->selectedValue = $selectedValue;
        $this->eventName = $eventName;
        $this->placeholder = $placeholder;
        $this->label = $label;
        $this->required = $required;
        $this->showLabel = $showLabel;
    }

    public function updatedSelectedValue($value)
    {
        $this->dispatch($this->eventName, $value);
    }

    public function getCompaniesProperty()
    {
        return VntCompany::query()
            ->where('status', 1)
            ->orderBy('businessName')
            ->get(['id', 'businessName', 'firstName', 'lastName', 'identification'])
            ->map(function ($company) {
                return [
                    'id' => $company->id,
                    'name' => $company->businessName ?: trim($company->firstName . ' ' . $company->lastName),
                    'identification' => $company->identification
                ];
            });
    }

    public function render()
    {
        return view('livewire.selects.company-select', [
            'companies' => $this->companies,
        ]);
    }
}
