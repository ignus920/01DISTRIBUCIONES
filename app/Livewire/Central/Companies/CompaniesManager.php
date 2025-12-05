<?php

namespace App\Livewire\Central\Companies;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Central\VntCompany;
use App\Models\Central\CnfTypeIdentification;
use App\Models\Central\CnfRegime;
use App\Models\Central\CnfFiscalResponsability;

class CompaniesManager extends Component
{
    use WithPagination;

    public $search = '';
    public $showModal = false;
    public $editingId = null;

    // Propiedades del formulario
    public $businessName = '';
    public $billingEmail = '';
    public $firstName = '';
    public $lastName = '';
    public $secondLastName = '';
    public $secondName = '';
    public $identification = '';
    public $checkDigit = '';
    public $typePerson = 'Juridica';
    public $typeIdentificationId = '';
    public $regimeId = '';
    public $code_ciiu = '';
    public $fiscalResponsabilityId = '';
    public $status = 1;

    protected $rules = [
        'businessName' => 'required|string|max:255',
        'billingEmail' => 'required|email|max:255',
        'firstName' => 'nullable|string|max:255',
        'lastName' => 'nullable|string|max:255',
        'secondLastName' => 'nullable|string|max:255',
        'secondName' => 'nullable|string|max:255',
        'identification' => 'nullable|string|max:15',
        'checkDigit' => 'nullable|integer',
        'typePerson' => 'required|in:Juridica,Natural',
        'typeIdentificationId' => 'nullable|exists:cnf_type_identifications,id',
        'regimeId' => 'nullable|exists:cnf_regime,id',
        'code_ciiu' => 'nullable|string|max:255',
        'fiscalResponsabilityId' => 'nullable|exists:cnf_fiscal_responsabilities,id',
        'status' => 'required|boolean',
    ];

    public function render()
    {
        $items = VntCompany::with(['typeIdentification', 'regime', 'fiscalResponsability'])
            ->where(function($query) {
                $query->where('businessName', 'like', '%' . $this->search . '%')
                      ->orWhere('billingEmail', 'like', '%' . $this->search . '%')
                      ->orWhere('identification', 'like', '%' . $this->search . '%');
            })
            ->latest()
            ->paginate(10);

        // Datos para los selects
        $typeIdentifications = CnfTypeIdentification::orderBy('name')->get();
        $regimes = CnfRegime::orderBy('name')->get();
        $fiscalResponsabilities = CnfFiscalResponsability::orderBy('description')->get();

        return view('livewire.central.companies.companies-manager', [
            'items' => $items,
            'typeIdentifications' => $typeIdentifications,
            'regimes' => $regimes,
            'fiscalResponsabilities' => $fiscalResponsabilities
        ]);
    }

    public function create()
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit($id)
    {
        $item = VntCompany::findOrFail($id);
        $this->editingId = $id;
        $this->businessName = $item->businessName;
        $this->billingEmail = $item->billingEmail;
        $this->firstName = $item->firstName;
        $this->lastName = $item->lastName;
        $this->secondLastName = $item->secondLastName;
        $this->secondName = $item->secondName;
        $this->identification = $item->identification;
        $this->checkDigit = $item->checkDigit;
        $this->typePerson = $item->typePerson;
        $this->typeIdentificationId = $item->typeIdentificationId;
        $this->regimeId = $item->regimeId;
        $this->code_ciiu = $item->code_ciiu;
        $this->fiscalResponsabilityId = $item->fiscalResponsabilityId;
        $this->status = $item->status;
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        $data = [
            'businessName' => $this->businessName,
            'billingEmail' => $this->billingEmail,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'secondLastName' => $this->secondLastName,
            'secondName' => $this->secondName,
            'identification' => $this->identification,
            'checkDigit' => $this->checkDigit,
            'typePerson' => $this->typePerson,
            'typeIdentificationId' => $this->typeIdentificationId ?: null,
            'regimeId' => $this->regimeId ?: null,
            'code_ciiu' => $this->code_ciiu,
            'fiscalResponsabilityId' => $this->fiscalResponsabilityId ?: null,
            'status' => $this->status,
        ];

        if ($this->editingId) {
            $item = VntCompany::findOrFail($this->editingId);
            $item->update($data);
            session()->flash('message', 'Empresa actualizada exitosamente.');
        } else {
            VntCompany::create($data);
            session()->flash('message', 'Empresa creada exitosamente.');
        }

        $this->resetForm();
        $this->showModal = false;
    }

    public function delete($id)
    {
        VntCompany::findOrFail($id)->delete();
        session()->flash('message', 'Empresa eliminada exitosamente.');
    }

    private function resetForm()
    {
        $this->editingId = null;
        $this->businessName = '';
        $this->billingEmail = '';
        $this->firstName = '';
        $this->lastName = '';
        $this->secondLastName = '';
        $this->secondName = '';
        $this->identification = '';
        $this->checkDigit = '';
        $this->typePerson = 'Juridica';
        $this->typeIdentificationId = '';
        $this->regimeId = '';
        $this->code_ciiu = '';
        $this->fiscalResponsabilityId = '';
        $this->status = 1;
        $this->resetErrorBag();
    }
}
