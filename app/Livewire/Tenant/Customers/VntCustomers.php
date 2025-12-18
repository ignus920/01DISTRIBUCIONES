<?php

namespace App\Livewire\Tenant\Customers;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Tenant\VntCustomer\VntCustomer;
use App\Models\Auth\Tenant;
use App\Services\Tenant\TenantManager;
use Illuminate\Support\Facades\Log;

class VntCustomers extends Component
{
    use WithPagination;

    public $showModal = false;
    public $editingId = null;
    public $search = '';
    public $company_id;
    public $perPage = 10;
    public $sortField = 'created_at';
    public $sortDirection = 'desc';

    public $isModalMode = false;
    public $preFilledIdentification = '';
    public $convertToUser = false;

    protected $listeners = [
        'type-identification-changed' => 'updateTypeIdentification',
        'regime-changed' => 'updateRegime',
        'city-changed' => 'updateCity'
    ];

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

    public $validatingIdentification = false;
    public $identificationExists = false;
    public $emailExists = false;
    
    public $showCredentialsAlert = false;
    public $userCredentials = [
        'email' => '',
        'password' => ''
    ];
    
    public $hasAssignedUser = false;
    public $assignedUserEmail = '';

    protected function rules()
    {
        return [
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
    }

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
        $this->ensureTenantConnection();
        
        $tenantId = session('tenant_id');
        $tenant = Tenant::find($tenantId);
        $this->company_id = $tenant->company_id ?? 0;

        if ($this->isModalMode) {
            $this->showModal = true;
            if (!empty($this->preFilledIdentification)) {
                $this->identification = $this->preFilledIdentification;
            }
        }
    }

    private function ensureTenantConnection()
    {
        $tenantId = session('tenant_id');
        if (!$tenantId) return redirect()->route('tenant.select');

        $tenant = Tenant::find($tenantId);
        if (!$tenant) {
            session()->forget('tenant_id');
            return redirect()->route('tenant.select');
        }

        $tenantManager = app(TenantManager::class);
        $tenantManager->setConnection($tenant);
        tenancy()->initialize($tenant);
    }

    public function render()
    {
        $this->ensureTenantConnection();

        $customers = VntCustomer::where('company_id', $this->company_id)
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('businessName', 'like', '%' . $this->search . '%')
                      ->orWhere('firstName', 'like', '%' . $this->search . '%')
                      ->orWhere('lastName', 'like', '%' . $this->search . '%')
                      ->orWhere('billingEmail', 'like', '%' . $this->search . '%')
                      ->orWhere('identification', 'like', '%' . $this->search . '%')
                      ->orWhere('business_phone', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.tenant.customers.vnt-customers', compact('customers'))
            ->layout('layouts.app');
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
        $this->ensureTenantConnection();
        $customer = VntCustomer::findOrFail($id);

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

        // Verificar si el cliente tiene un usuario asignado
        $this->checkIfCustomerHasUser($id);

        $this->showModal = true;
    }

    private function checkIfCustomerHasUser($customerId)
    {
        $this->hasAssignedUser = false;
        $this->assignedUserEmail = '';

        // Buscar en la base de datos central si existe un usuario donde AMBOS coincidan:
        // contact_id = customerId Y email = billingEmail
        tenancy()->central(function () use ($customerId) {
            if (!empty($this->billingEmail)) {
                $user = \App\Models\Auth\User::where('contact_id', $customerId)
                    ->where('email', $this->billingEmail)
                    ->first();
                
                if ($user) {
                    $this->hasAssignedUser = true;
                    $this->assignedUserEmail = $user->email;
                }
            }
        });
    }

    public function save()
    {
        $this->ensureTenantConnection();

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
            'status' => true, // Por defecto activo al crear
        ];

        if ($this->editingId) {
            $customer = VntCustomer::findOrFail($this->editingId);
            $customer->update($data);
            
            // Lógica para convertir en usuario al editar (solo si no tiene usuario asignado)
            if ($this->convertToUser && !empty($this->billingEmail) && !$this->hasAssignedUser) {
                // Verificar una última vez si ya existe un usuario con AMBOS datos coincidentes
                $userExists = false;
                tenancy()->central(function () use (&$userExists, $customer) {
                    $userExists = \App\Models\Auth\User::where('contact_id', $customer->id)
                        ->where('email', $this->billingEmail)
                        ->exists();
                });

                if ($userExists) {
                    session()->flash('error', 'Este cliente ya tiene un usuario asignado con este email.');
                    $this->showModal = false;
                    return;
                }

                $tenantId = session('tenant_id');
                $email = $this->billingEmail;
                $password = '12345678';
                $name = $customer->display_name;
                $contactId = $customer->id;

                \Illuminate\Support\Facades\Log::info('🆕 Iniciando creación de usuario TAT (desde edición)', [
                    'email' => $email,
                    'tenant_id' => $tenantId,
                    'contact_id' => $contactId,
                    'password_preview' => substr($password, 0, 3) . '...'
                ]);

                tenancy()->central(function () use ($name, $email, $password, $tenantId, $contactId) {
                    // Verificar si el usuario ya existe
                    $existingUser = \App\Models\Auth\User::where('email', $email)->first();

                    if ($existingUser) {
                        \Illuminate\Support\Facades\Log::warning('⚠️ El usuario ya existe en la base central', ['email' => $email]);
                        $user = $existingUser;
                    } else {
                        $user = \App\Models\Auth\User::create([
                            'name' => $name,
                            'email' => $email,
                            'password' => \Illuminate\Support\Facades\Hash::make($password),
                            'profile_id' => 17, 
                            'contact_id' => $contactId,
                        ]);
                        \Illuminate\Support\Facades\Log::info('👤 Usuario creado en central', ['user_id' => $user->id]);
                    }

                    // Asegurar asociación con el tenant
                    $membership = \App\Models\Auth\UserTenant::firstOrCreate(
                        ['user_id' => $user->id, 'tenant_id' => $tenantId],
                        ['role' => 17, 'is_active' => 1]
                    );

                    \Illuminate\Support\Facades\Log::info('🏢 Asociación con tenant verificada/creada', [
                        'user_id' => $user->id,
                        'tenant_id' => $tenantId,
                        'was_active' => $membership->is_active
                    ]);
                });
                
                // Guardar credenciales para mostrar en la alerta
                $this->userCredentials = [
                    'email' => $email,
                    'password' => $password
                ];
                $this->showCredentialsAlert = true;
            }
            
            session()->flash('message', 'Cliente actualizado correctamente.');
        } else {
            $customer = VntCustomer::create($data);
            
            // Lógica para convertir en usuario
            if ($this->convertToUser && !empty($this->billingEmail)) {
                // Verificar si el email ya tiene usuario asignado
                $emailHasUser = false;
                tenancy()->central(function () use (&$emailHasUser) {
                    $emailHasUser = \App\Models\Auth\User::where('email', $this->billingEmail)->exists();
                });

                if ($emailHasUser) {
                    session()->flash('error', 'El email ya tiene un usuario asignado en el sistema.');
                    $this->showModal = false;
                    return;
                }

                $tenantId = session('tenant_id');
                $email = $this->billingEmail;
                $password = '12345678';
                $name = $customer->display_name;
                $contactId = $customer->id;

                \Illuminate\Support\Facades\Log::info('🆕 Iniciando creación de usuario TAT', [
                    'email' => $email,
                    'tenant_id' => $tenantId,
                    'contact_id' => $contactId,
                    'password_preview' => substr($password, 0, 3) . '...'
                ]);

                tenancy()->central(function () use ($name, $email, $password, $tenantId, $contactId) {
                    // Verificar si el usuario ya existe
                    $existingUser = \App\Models\Auth\User::where('email', $email)->first();

                    if ($existingUser) {
                        \Illuminate\Support\Facades\Log::warning('⚠️ El usuario ya existe en la base central', ['email' => $email]);
                        $user = $existingUser;
                    } else {
                        $user = \App\Models\Auth\User::create([
                            'name' => $name,
                            'email' => $email,
                            'password' => \Illuminate\Support\Facades\Hash::make($password),
                            'profile_id' => 17, 
                            'contact_id' => $contactId,
                        ]);
                        \Illuminate\Support\Facades\Log::info('👤 Usuario creado en central', ['user_id' => $user->id]);
                    }

                    // Asegurar asociación con el tenant
                    $membership = \App\Models\Auth\UserTenant::firstOrCreate(
                        ['user_id' => $user->id, 'tenant_id' => $tenantId],
                        ['role' => 17, 'is_active' => 1]
                    );

                    \Illuminate\Support\Facades\Log::info('🏢 Asociación con tenant verificada/creada', [
                        'user_id' => $user->id,
                        'tenant_id' => $tenantId,
                        'was_active' => $membership->is_active
                    ]);
                });
                
                // Guardar credenciales para mostrar en la alerta
                $this->userCredentials = [
                    'email' => $email,
                    'password' => $password
                ];
                $this->showCredentialsAlert = true;
            }

            session()->flash('message', 'Cliente creado correctamente.');
            $this->dispatch('customer-created', customerId: $customer->id);
        }

        if ($this->isModalMode) {
            $this->closeModal();
        } else {
            $this->showModal = false;
            // Solo resetear el formulario si NO se creó un usuario
            if (!$this->showCredentialsAlert) {
                $this->resetForm();
            } else {
                // Limpiar solo los campos del formulario pero mantener las credenciales
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
                $this->convertToUser = false;
                $this->resetValidation();
            }
        }
    }

    public function closeModal()
    {
        $this->showModal = false;
        // Solo resetear si no hay credenciales pendientes de mostrar
        if (!$this->showCredentialsAlert) {
            $this->resetForm();
        } else {
            // Limpiar solo los campos del formulario pero mantener las credenciales
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
            $this->convertToUser = false;
            $this->resetValidation();
        }
        if ($this->isModalMode) $this->dispatch('customer-modal-closed');
    }
    
    public function closeCredentialsAlert()
    {
        $this->showCredentialsAlert = false;
        $this->userCredentials = ['email' => '', 'password' => ''];
    }

    public function toggleStatus($customerId)
    {
        $this->ensureTenantConnection();
        
        try {
            $customer = VntCustomer::findOrFail($customerId);
            
            // Cambiar el estado (toggle)
            $customer->status = !$customer->status;
            $customer->save();
            
            $statusText = $customer->status ? 'activado' : 'desactivado';
            session()->flash('message', "Cliente {$statusText} correctamente.");
            
        } catch (\Exception $e) {
            session()->flash('error', 'Error al cambiar el estado del cliente.');
            \Illuminate\Support\Facades\Log::error('Error al cambiar estado de cliente', [
                'customer_id' => $customerId,
                'error' => $e->getMessage()
            ]);
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
        $this->convertToUser = false;
        $this->showCredentialsAlert = false;
        $this->userCredentials = ['email' => '', 'password' => ''];
        $this->hasAssignedUser = false;
        $this->assignedUserEmail = '';
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

    public function updatedTypePerson()
    {
        if ($this->typePerson === 'Natural') {
            $this->businessName = '';
        } else {
            $this->firstName = '';
            $this->lastName = '';
        }
        $this->resetValidation();
    }

    public function updateTypeIdentification($typeIdentificationId)
    {
        $this->typeIdentificationId = $typeIdentificationId;
        $this->resetErrorBag(['typeIdentificationId']);
    }

    public function updateRegime($regimeId)
    {
        $this->regimeId = $regimeId;
        $this->resetErrorBag(['regimeId']);
    }

    public function updateCity($cityId, $index = null)
    {
        $this->cityId = $cityId;
        $this->resetErrorBag(['cityId']);
    }

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
            
            // Si estamos editando, verificar si el email tiene un usuario asignado
            if ($this->editingId) {
                $this->checkIfEmailHasUser();
            }
        }
    }
    
    private function checkIfEmailHasUser()
    {
        if (empty($this->billingEmail) || !$this->editingId) {
            return;
        }

        // Buscar en la base de datos central si existe un usuario donde AMBOS coincidan:
        // contact_id = editingId Y email = billingEmail
        tenancy()->central(function () {
            $user = \App\Models\Auth\User::where('contact_id', $this->editingId)
                ->where('email', $this->billingEmail)
                ->first();
            
            if ($user) {
                // El usuario existe con ambos datos coincidentes
                $this->hasAssignedUser = true;
                $this->assignedUserEmail = $user->email;
            } else {
                // No hay usuario con ambos datos coincidentes, permitir crear
                $this->hasAssignedUser = false;
                $this->assignedUserEmail = '';
            }
        });
    }

    private function checkIdentificationExists()
    {
        $this->identificationExists = VntCustomer::where('company_id', $this->company_id)
            ->where('identification', $this->identification)
            ->when($this->editingId, function($query) {
                return $query->where('id', '!=', $this->editingId);
            })
            ->exists();
        $this->validatingIdentification = false;
    }

    private function checkEmailExists()
    {
        $this->emailExists = VntCustomer::where('company_id', $this->company_id)
            ->where('billingEmail', $this->billingEmail)
            ->when($this->editingId, function($query) {
                return $query->where('id', '!=', $this->editingId);
            })
            ->exists();
    }
}
