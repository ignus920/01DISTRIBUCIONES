<?php

namespace App\Livewire\TAT\Customers;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\TAT\Customer\Customer as TatCustomer;
use App\Models\Auth\Tenant;
use Illuminate\Support\Facades\Log;


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

    // Para modo modal
    public $isModalMode = false;
    public $preFilledIdentification = '';
    public $editingCustomerId = null;

    protected $listeners = [
        'type-identification-changed' => 'updateTypeIdentification',
        'regime-changed' => 'updateRegime',
        'city-changed' => 'updateCity'
    ];

    // Form fields - solo los que existen en la base de datos
    public $typePerson = '';
    public $typeIdentificationId = null;
    public $identification = '';
    public $regimeId = null;
    public $cityId = null;
    public $businessName = '';
    public $billingEmail = '';
    public $firstName = '';
    public $lastName = '';
    public $address = '';
    public $business_phone = '';

    // Validation states
    public $validatingIdentification = false;
    public $identificationExists = false;
    public $emailExists = false;

    protected $rules = [
        'typeIdentificationId' => 'required|integer',
        'identification' => 'required|string|max:15',
        'typePerson' => 'required|in:Natural,Juridica',
        'regimeId' => 'nullable|integer',
        'cityId' => 'nullable|integer',
        'businessName' => 'required_if:typePerson,Juridica|nullable|string|max:255',
        'billingEmail' => 'nullable|email|max:255',
        'firstName' => 'required_if:typePerson,Natural|nullable|string|max:255',
        'lastName' => 'nullable|string|max:255',
        'address' => 'nullable|string|max:255',
        'business_phone' => 'nullable|string|max:100',
    ];

    protected $messages = [
        'typeIdentificationId.required' => 'El tipo de identificación es obligatorio.',
        'identification.required' => 'El número de identificación es obligatorio.',
        'typePerson.required' => 'El tipo de persona es obligatorio.',
        'businessName.required_if' => 'El nombre de la empresa es obligatorio para personas jurídicas.',
        'firstName.required_if' => 'El nombre es obligatorio para personas naturales.',
        'billingEmail.email' => 'El email debe tener un formato válido.',
    ];

    public function mount()
    {
        $tenantId = session('tenant_id');
        $tenant = Tenant::find($tenantId);
        $this->company_id = $tenant->company_id ?? 0;


        // Si está en modo modal, activar el modal automáticamente
        if ($this->isModalMode) {
            $this->showModal = true;

            // Si hay un ID de cliente para editar, cargar sus datos
            if ($this->editingCustomerId) {
                $this->loadCustomerData($this->editingCustomerId);
                $this->editingId = $this->editingCustomerId;

                Log::info('Modal activado en modo edición', [
                    'editingCustomerId' => $this->editingCustomerId,
                    'showModal' => $this->showModal
                ]);
            } else {
                // Pre-llenar el número de identificación si se proporcionó (modo crear)
                if (!empty($this->preFilledIdentification)) {
                    $this->identification = $this->preFilledIdentification;
                }

                Log::info('Modal activado en modo crear', [
                    'showModal' => $this->showModal,
                    'identification' => $this->identification
                ]);
            }
        }
    }

    /**
     * Cargar datos del cliente para edición
     */
    private function loadCustomerData($customerId)
    {
        $customer = TatCustomer::find($customerId);

        if (!$customer) {
            Log::warning('Cliente no encontrado para edición', ['id' => $customerId]);
            return;
        }

        // Cargar todos los campos del cliente
        $this->typePerson = $customer->typePerson ?? '';
        $this->typeIdentificationId = $customer->typeIdentificationId;
        $this->identification = $customer->identification ?? '';
        $this->regimeId = $customer->regimeId;
        $this->cityId = $customer->cityId;
        $this->businessName = $customer->businessName ?? '';
        $this->billingEmail = $customer->billingEmail ?? '';
        $this->firstName = $customer->firstName ?? '';
        $this->lastName = $customer->lastName ?? '';
        $this->address = $customer->address ?? '';
        $this->business_phone = $customer->business_phone ?? '';

        Log::info('Datos del cliente cargados para edición', [
            'customerId' => $customerId,
            'identification' => $this->identification,
            'businessName' => $this->businessName,
            'firstName' => $this->firstName
        ]);
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

        return view('livewire.TAT.Customers.tat-customers-manager', compact('customers'))->layout('layouts.app'); // 👈 aquí agregas el layout
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
        $this->typePerson = $customer->typePerson ?? '';
        $this->typeIdentificationId = $customer->typeIdentificationId;
        $this->identification = $customer->identification ?? '';
        $this->regimeId = $customer->regimeId;
        $this->cityId = $customer->cityId;
        $this->businessName = $customer->businessName ?? '';
        $this->billingEmail = $customer->billingEmail ?? '';
        $this->firstName = $customer->firstName ?? '';
        $this->lastName = $customer->lastName ?? '';
        $this->address = $customer->address ?? '';
        $this->business_phone = $customer->business_phone ?? '';

        $this->showModal = true;
    }

    public function save()
    {
        // Validaciones adicionales
        if ($this->identificationExists) {
            session()->flash('error', 'El número de identificación ya está registrado.');
            return;
        }

        if ($this->emailExists) {
            session()->flash('error', 'El email ya está registrado.');
            return;
        }

        $this->validate();

        $data = [
            'company_id' => $this->company_id,
            'typePerson' => $this->typePerson,
            'typeIdentificationId' => $this->typeIdentificationId,
            'identification' => $this->identification,
            'regimeId' => $this->regimeId,
            'cityId' => $this->cityId ?: null,
            'businessName' => $this->businessName ?: null,
            'billingEmail' => $this->billingEmail ?: null,
            'firstName' => $this->firstName ?: null,
            'lastName' => $this->lastName ?: null,
            'address' => $this->address ?: null,
            'business_phone' => $this->business_phone ?: null,
        ];

        if ($this->editingId) {
            TatCustomer::findOrFail($this->editingId)->update($data);
            session()->flash('message', 'Cliente actualizado correctamente.');
            $customerId = $this->editingId;
        } else {
            $customer = TatCustomer::create($data);
            session()->flash('message', 'Cliente creado correctamente.');
            $customerId = $customer->id;
        }

        // Emitir evento para notificar al quoter (tanto para crear como editar)
        if ($this->isModalMode) {
            $this->dispatch('customer-created', customerId: $customerId);
        }

        $this->resetForm();
        $this->showModal = false;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();

        // Si está en modo modal reutilizable, emitir evento al padre
        if ($this->isModalMode) {
            $this->dispatch('customer-modal-closed');
        }
    }

    private function resetForm()
    {
        $this->typePerson = '';
        $this->typeIdentificationId = null;
        $this->identification = '';
        $this->regimeId = null;
        $this->cityId = null;
        $this->businessName = '';
        $this->billingEmail = '';
        $this->firstName = '';
        $this->lastName = '';
        $this->address = '';
        $this->business_phone = '';
        $this->validatingIdentification = false;
        $this->identificationExists = false;
        $this->emailExists = false;
        $this->resetValidation();
    }

    public function cancelForm()
    {
        $this->showModal = false;
        $this->resetForm();
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
        $this->resetErrorBag(['typeIdentificationId']);
    }

    // Method to handle regime selection
    public function updateRegime($regimeId)
    {
        $this->regimeId = $regimeId;
        $this->resetErrorBag(['regimeId']);
    }

    // Method to handle city selection
    public function updateCity($cityId, $index = null)
    {
        $this->cityId = $cityId;
        $this->resetErrorBag(['cityId']);
    }

    // Validation methods
    public function updatedIdentification()
    {
        if (strlen($this->identification) >= 3) {
            $this->validatingIdentification = true;
            $this->checkIdentificationExists();
        }
    }

    public function updatedBillingEmail()
    {
        if (filter_var($this->billingEmail, FILTER_VALIDATE_EMAIL)) {
            $this->checkEmailExists();
        }
    }

    private function checkIdentificationExists()
    {
        $this->identificationExists = TatCustomer::where('company_id', $this->company_id)
            ->where('identification', $this->identification)
            ->when($this->editingId, function($query) {
                return $query->where('id', '!=', $this->editingId);
            })
            ->exists();

        $this->validatingIdentification = false;
    }

    private function checkEmailExists()
    {
        $this->emailExists = TatCustomer::where('company_id', $this->company_id)
            ->where('billingEmail', $this->billingEmail)
            ->when($this->editingId, function($query) {
                return $query->where('id', '!=', $this->editingId);
            })
            ->exists();
    }
}