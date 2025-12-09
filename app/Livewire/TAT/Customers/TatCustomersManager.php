<?php

namespace App\Livewire\TAT\Customers;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\TAT\Customer\Customer as TatCustomer;
use App\Models\Auth\Tenant;

class TatCustomersManager extends Component
{
    use WithPagination;

    public $showModal = false;
    public $editingId = null;
    public $search = '';
    public $company_id;
    public $perPage = 10;
    public $sortField = 'created_at';
    public $sortDirection = 'desc';

    protected $listeners = [
        'type-identification-changed' => 'updateTypeIdentification'
    ];

    // Form fields
    public $typePerson = 'Natural';
    public $typeIdentificationId = null;
    public $regimeId = null;
    public $cityId = null;
    public $businessName = '';
    public $billingEmail = '';
    public $firstName = '';
    public $lastName = '';
    public $address = '';
    public $business_phone = '';

    protected $rules = [
        'typePerson' => 'required|in:Natural,Juridica',
        'typeIdentificationId' => 'nullable|integer',
        'regimeId' => 'nullable|integer',
        'cityId' => 'nullable|integer',
        'businessName' => 'required_if:typePerson,Juridica|string|max:255',
        'billingEmail' => 'nullable|email|max:255',
        'firstName' => 'required_if:typePerson,Natural|string|max:255',
        'lastName' => 'required_if:typePerson,Natural|string|max:255',
        'address' => 'nullable|string|max:500',
        'business_phone' => 'nullable|string|max:20',
    ];

    protected $messages = [
        'typePerson.required' => 'El tipo de persona es obligatorio.',
        'businessName.required_if' => 'El nombre de la empresa es obligatorio para personas jurídicas.',
        'firstName.required_if' => 'El nombre es obligatorio para personas naturales.',
        'lastName.required_if' => 'El apellido es obligatorio para personas naturales.',
        'billingEmail.email' => 'El email debe tener un formato válido.',
    ];

    public function mount()
    {
        $tenantId = session('tenant_id');
        $tenant = Tenant::find($tenantId);
        $this->company_id = $tenant->company_id ?? 0;
    }

    public function render()
    {
        $customers = TatCustomer::where('company_id', $this->company_id)
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('businessName', 'like', '%' . $this->search . '%')
                      ->orWhere('firstName', 'like', '%' . $this->search . '%')
                      ->orWhere('lastName', 'like', '%' . $this->search . '%')
                      ->orWhere('billingEmail', 'like', '%' . $this->search . '%')
                      ->orWhere('business_phone', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.TAT.Customers.tat-customers-manager', compact('customers'));
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirection = 'asc';
        }
        $this->sortField = $field;
        $this->resetPage();
    }

    public function create()
    {
        $this->resetForm();
        $this->editingId = null;
        $this->showModal = true;
    }

    public function edit($id)
    {
        $customer = TatCustomer::findOrFail($id);

        $this->editingId = $id;
        $this->typePerson = $customer->typePerson;
        $this->typeIdentificationId = $customer->typeIdentificationId;
        $this->regimeId = $customer->regimeId;
        $this->cityId = $customer->cityId;
        $this->businessName = $customer->businessName;
        $this->billingEmail = $customer->billingEmail;
        $this->firstName = $customer->firstName;
        $this->lastName = $customer->lastName;
        $this->address = $customer->address;
        $this->business_phone = $customer->business_phone;

        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        $data = [
            'company_id' => $this->company_id,
            'typePerson' => $this->typePerson,
            'typeIdentificationId' => $this->typeIdentificationId,
            'regimeId' => $this->regimeId,
            'cityId' => $this->cityId,
            'businessName' => $this->businessName,
            'billingEmail' => $this->billingEmail,
            'firstName' => $this->firstName,
            'lastName' => $this->lastName,
            'address' => $this->address,
            'business_phone' => $this->business_phone,
        ];

        if ($this->editingId) {
            TatCustomer::findOrFail($this->editingId)->update($data);
            session()->flash('message', 'Cliente actualizado correctamente.');
        } else {
            TatCustomer::create($data);
            session()->flash('message', 'Cliente creado correctamente.');
        }

        $this->resetForm();
        $this->showModal = false;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->typePerson = 'Natural';
        $this->typeIdentificationId = null;
        $this->regimeId = null;
        $this->cityId = null;
        $this->businessName = '';
        $this->billingEmail = '';
        $this->firstName = '';
        $this->lastName = '';
        $this->address = '';
        $this->business_phone = '';
        $this->resetValidation();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingPerPage()
    {
        $this->resetPage();
    }

    // Method to update form fields when person type changes
    public function updatedTypePerson()
    {
        // Clear opposite type fields when switching
        if ($this->typePerson === 'Natural') {
            $this->businessName = '';
        } else {
            $this->firstName = '';
            $this->lastName = '';
        }
        $this->resetValidation();
    }

    // Method to handle type identification selection
    public function updateTypeIdentification($typeIdentificationId)
    {
        $this->typeIdentificationId = $typeIdentificationId;
    }
}