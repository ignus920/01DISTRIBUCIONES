<?php

namespace App\Livewire\Tenant\Users;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Auth\User;
use App\Models\Auth\UserTenant;
use App\Models\Central\UsrProfile;
use App\Models\Central\UsrPermissionProfile;
use App\Models\Central\VntWarehouse;
use App\Models\Central\VntContact;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Traits\Livewire\WithExport;
use App\Models\Central\CnfPosition;
use App\Models\Central\VntCompany;
use App\Traits\HasCompanyConfiguration;
use App\Models\Auth\Tenant;
use App\Services\Tenant\TenantManager;

class UserRapForm extends Component
{
    use WithPagination, HasCompanyConfiguration, WithExport;
    
    protected $listeners = ['positionUpdated', 'refresh-users' => 'refreshUsers'];
    // Table properties
    public $search = '';
    public $perPage = 10;
    public $sortField = 'id';
    public $sortDirection = 'desc';

    // Modal control properties
    public $showModal = false;
    public $editingId = null;

    // Form properties

    // Propiedades para contactos 
    public $firstName = '';
    public $secondName = '';
    public $lastName = '';
    public $secondLastName = '';
    public $email = '';
    public $password = '';
    public $password_confirmation = '';
    public $phone = '';
    public $positionId = null;
    public $status = 1;
    public $profile_id = null;




    public $warehouseId = null;
    public $avatar = null;
    public $two_factor_enabled = false;
    public $two_factor_type = null;

    // Data collection properties
    public $profiles = []; // Lista de perfiles disponibles para asignar al usuario
    public $warehouses = []; // Lista de sucursales disponibles según la compañía
    public $profilePermissions = []; // Permisos del perfil seleccionado para mostrar al usuario

    // Message properties
    public $successMessage = '';
    public $errorMessage = '';

    // Change Password Modal properties
    public $showChangePasswordModal = false;
    public $userToChangePassword = null;
    public $newPassword = '';
    public $confirmPassword = '';


    /**
     * Mount component and load initial data
     */
    public function mount(): void
    {
 
        $this->loadProfiles();
        $this->loadWarehouses();
    }

    /**
     * Handle position update from PositionSelect component
     */
    public function positionUpdated($positionId): void
    {
        $this->positionId = $positionId;
    }

    /**
     * Refresh users list after status toggle
     */
    public function refreshUsers(): void
    {
        // This method is called via dispatch to force re-render
        // Livewire will automatically re-query the users property
    }

    /**
     * Load profiles from database
     */
    private function loadProfiles(): void
    {
        $this->profiles = UsrProfile::where('status', 1)
           ->where('name', '!=', 'Tienda')
           ->orderBy('name')
           ->get();
    }

    /**
     * Load warehouses with company relationship
     */
    private function loadWarehouses(): void
    {
       $sessionTenant = $this->getTenantId();

       // Obtener el tenant desde la base de datos usando el ID de sesión
       $tenant = Tenant::find($sessionTenant);

       if (!$tenant || !$tenant->company_id) {
           $this->warehouses = collect([]);
           return;
       }
       // Traer todos los almacenes que coincidan con ese company_id
       $this->warehouses = VntWarehouse::where('companyId', $tenant->company_id)
           ->where('status', true)
           ->with('company')
           ->orderBy('name')
           ->get();
       }


       
    /**
     * Cargar permisos del perfil seleccionado
     * Obtiene todos los permisos asociados al perfil con sus respectivos niveles de acceso
     */
    public function loadProfilePermissions($profileId = null): void
    {
        if (!$profileId) {
            $this->profilePermissions = [];
            return;
        }

        try {
            $profile = UsrProfile::with(['permissions' => function($query) {
                $query->active();
            }])->find($profileId);

            if (!$profile) {
                $this->profilePermissions = [];
                return;
            }

            $this->profilePermissions = $profile->permissions->map(function($permission) {
                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'ver' => (bool)($permission->pivot->show ?? false),
                    'crear' => (bool)($permission->pivot->creater ?? false),
                    'editar' => (bool)($permission->pivot->editer ?? false),
                    'eliminar' => (bool)($permission->pivot->deleter ?? false),
                ];
            })->toArray();

        } catch (\Exception $e) {
            Log::error('Error loading profile permissions', [
                'profile_id' => $profileId,
                'error' => $e->getMessage()
            ]);
            $this->profilePermissions = [];
        }
    }

    /**
     * Manejar el cambio de selección de perfil
     * Se ejecuta automáticamente cuando el usuario selecciona un perfil
     */
    public function updatedProfileId($profileId): void
    {
        $this->loadProfilePermissions($profileId);
    }


    

    /**
     * Open modal in create mode
     */
    public function create(): void
    {
        if ($this->canCreateOrUpdateUsers()) {
            $this->errorMessage = 'No tienes permisos para crear usuarios';
            return;
        }

        // Limpiar completamente el estado antes de abrir
        $this->resetForm();
        $this->resetErrorBag();
        $this->resetValidation();
        
        $this->successMessage = '';
        $this->errorMessage = '';
        $this->editingId = null;
        
        $this->showModal = true;
    }

    /**
     * Open modal in edit mode with user data
     */
    public function edit(int $userId): void
    {
        // Limpiar completamente el estado anterior
        $this->resetForm();
        $this->resetErrorBag();
        $this->resetValidation();
        $this->successMessage = '';
        $this->errorMessage = '';
        
        $user = User::with('contact')->findOrFail($userId);
        
        $this->editingId = $userId;
        
        // Load user data
        $this->email = $user->email;
        $this->phone = $user->phone;
        $this->profile_id = $user->profile_id;
        $this->two_factor_enabled = $user->two_factor_enabled ?? false;
        $this->two_factor_type = $user->two_factor_type;

        // Load contact data if exists
        if ($user->contact) {
            $this->firstName = $user->contact->firstName;
            $this->secondName = $user->contact->secondName;
            $this->lastName = $user->contact->lastName;
            $this->secondLastName = $user->contact->secondLastName;
            $this->warehouseId = $user->contact->warehouseId;
            $this->positionId = $user->contact->positionId;
        }

        // Cargar permisos del perfil si existe
        if ($this->profile_id) {
            $this->loadProfilePermissions($this->profile_id);
        }

        $this->showModal = true;
    }

    /**
     * Close modal and reset form
     */
    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
        $this->successMessage = '';
        $this->errorMessage = '';
        
        // Limpiar validaciones completamente
        $this->resetErrorBag();
        $this->resetValidation();
    }

    /**
     * Reset all form fields
     */
    private function resetForm(): void
    {
        $this->editingId = null;
        $this->firstName = '';
        $this->secondName = '';
        $this->lastName = '';
        $this->secondLastName = '';
        $this->email = '';
        $this->password = '';
        $this->password_confirmation = '';
        $this->phone = '';
        $this->profile_id = null;
        $this->warehouseId = null;
        $this->positionId = null;
        $this->avatar = null;
        $this->two_factor_enabled = false;
        $this->two_factor_type = null;
        $this->profilePermissions = []; // Limpiar permisos cuando se resetea el formulario
    }

    /**
     * Validation rules for form fields
     */
    protected function rules(): array
    {
        $rules = [
            'firstName' => 'required|string|max:255',
            'lastName' => 'required|string|max:255',
            'secondName' => 'nullable|string|max:255',
            'secondLastName' => 'nullable|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->editingId)
            ],
            'phone' => [
                'required',
                'string',
                'regex:/^3[0-9]{9}$/',
                'digits:10'
            ],
            'profile_id' => 'required|exists:usr_profiles,id',
            'warehouseId' => 'required|exists:vnt_warehouses,id',
            'positionId' => 'required|exists:cnf_positions,id',
            'avatar' => 'nullable|image|max:2048',
            'two_factor_enabled' => 'boolean',
            'two_factor_type' => 'nullable|in:email,sms,authenticator',
        ];

        // Password validation only for create mode
        if (!$this->editingId) {
            $rules['password'] = 'required|string|min:8|confirmed';
            $rules['password_confirmation'] = 'required';
        }

        return $rules;
    }

    /**
     * Custom validation messages in Spanish
     */
    protected function messages(): array
    {
        return [
            'firstName.required' => 'El primer nombre es obligatorio',
            'firstName.string' => 'El primer nombre debe ser texto',
            'firstName.max' => 'El primer nombre no debe superar 255 caracteres',
            'lastName.required' => 'El primer apellido es obligatorio',
            'lastName.string' => 'El primer apellido debe ser texto',
            'lastName.max' => 'El primer apellido no debe superar 255 caracteres',
            'secondName.string' => 'El segundo nombre debe ser texto',
            'secondName.max' => 'El segundo nombre no debe superar 255 caracteres',
            'secondLastName.string' => 'El segundo apellido debe ser texto',
            'secondLastName.max' => 'El segundo apellido no debe superar 255 caracteres',
            'email.required' => 'El email es obligatorio',
            'email.email' => 'El email debe ser válido',
            'email.max' => 'El email no debe superar 255 caracteres',
            'email.unique' => 'Este email ya está registrado',
            'phone.regex' => 'El número debe ser un celular colombiano válido que inicie con 3 (ej: 3123456789).',
            'phone.digits' => 'El número de celular debe tener exactamente 10 dígitos.',
            'phone.required' => 'El número de celular es obligatorio',
            'phone.max' => 'El teléfono no debe superar 20 caracteres',
            'password.required' => 'La contraseña es obligatoria',
            'password.string' => 'La contraseña debe ser texto',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres',
            'password.confirmed' => 'Las contraseñas no coinciden',
            'password_confirmation.required' => 'La confirmación de contraseña es obligatoria',
            'profile_id.required' => 'Debe seleccionar un perfil',
            'profile_id.exists' => 'El perfil seleccionado no es válido',
            'warehouseId.required' => 'Debe seleccionar una sucursal',
            'warehouseId.exists' => 'La sucursal seleccionada no es válida',
            'positionId.required' => 'Debe seleccionar un cargo',
            'positionId.exists' => 'El cargo seleccionado no es válido',
            // 'avatar.image' => 'El archivo debe ser una imagen',
            // 'avatar.max' => 'La imagen no debe superar 2MB',
            // 'two_factor_enabled.boolean' => 'El valor de autenticación de dos factores debe ser verdadero o falso',
            // 'two_factor_type.in' => 'El tipo de autenticación de dos factores debe ser email, sms o authenticator',
        ];
    }

    /**
     * Validate form fields
     */
    private function validateForm(): void
    {
        $this->validate($this->rules(), $this->messages());
    }

    /**
     * Save user (create or update)
     */
    public function save(): void
    {
        try {
            // Clear previous error message
            $this->errorMessage = '';
            $this->successMessage = '';
            
            // Validate all inputs
            $this->validateForm();
            
            // Check if editingId exists to determine create vs update mode
            if ($this->editingId) {
                // Update mode
                $user = User::findOrFail($this->editingId);
                $this->updateUserWithContact($user);
            } else {
                // Create mode
                $this->createUserWithContact();
            }

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Log validation errors for debugging
            Log::info('Validation error in save method', [
                'errors' => $e->errors(),
            ]);
            // Re-throw the exception so Livewire can display the errors in the form
            throw $e;
        } catch (\Exception $e) {
            // Other errors are handled in create/update methods
            // This catch is for any unexpected errors
            $this->errorMessage = 'Error inesperado: ' . $e->getMessage();
            
            Log::error('Unexpected error in save method', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /**
     * Concatenate full name from name fields
     */
    private function concatenateFullName(): string
    {
        $nameParts = array_filter([
            $this->firstName,
            $this->secondName,
            $this->lastName,
            $this->secondLastName
        ]);
        
        return implode(' ', $nameParts);
    }

    /**
     * Create user with associated contact record
     */
    private function createUserWithContact(): void
    {
        try {
            $sessionTenant = $this->getTenantId();
            DB::beginTransaction();

            // Create VntContact record
            $contact = VntContact::create([
                'firstName' => $this->firstName,
                'secondName' => $this->secondName,
                'lastName' => $this->lastName,
                'secondLastName' => $this->secondLastName,
                'email' => $this->email,
                'business_phone' => $this->phone,
                'warehouseId' => $this->warehouseId,
                'positionId' => $this->positionId,
                'status' => true,
            ]);

            // Concatenate full name for User.name
            $fullName = $this->concatenateFullName();

            // Create User record
            $user = User::create([
                'name' => $fullName,
                'email' => $this->email,
                'password' => Hash::make($this->password),
                'phone' => $this->phone,
                'profile_id' => $this->profile_id,
                'contact_id' => $contact->id,
                'avatar' => $this->avatar,
                'two_factor_failed_attempts' => 0,
                'two_factor_locked_until' => null,
            ]);

            // Asociar el usuario con el tenant
            UserTenant::create([
                'user_id' => $user->id,
                'tenant_id' => $sessionTenant,
                'role' => $this->profile_id,
                'is_active' => 1
            ]);

            DB::commit();
            
            // Set success message before closing
            $this->successMessage = 'Usuario creado exitosamente';
            $this->errorMessage = '';
            
            // Close modal after successful creation
            $this->closeModal();
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->errorMessage = 'Error al crear el usuario: ' . $e->getMessage();
            
            Log::error('User creation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => [
                    'email' => $this->email,
                    'profile_id' => $this->profile_id,
                    'warehouseId' => $this->warehouseId,
                ]
            ]);
        }
    }

    /**
     * Update user with associated contact record
     */
    private function updateUserWithContact(User $user): void
    {
        try {
            DB::beginTransaction();

            // Load existing User and VntContact records
            $contact = VntContact::findOrFail($user->contact_id);

            // Update VntContact fields
            $contact->update([
                'firstName' => $this->firstName,
                'secondName' => $this->secondName,
                'lastName' => $this->lastName,
                'secondLastName' => $this->secondLastName,
                'email' => $this->email,
                'business_phone' => $this->phone,
                'warehouseId' => $this->warehouseId,
                'positionId' => $this->positionId,
            ]);

            // Concatenate full name from updated name fields
            $fullName = $this->concatenateFullName();

            // Update User fields (excluding password)
            $user->update([
                'name' => $fullName,
                'phone' => $this->phone,
                'profile_id' => $this->profile_id,
                'avatar' => $this->avatar,
                'two_factor_enabled' => $this->two_factor_enabled,
                'two_factor_type' => $this->two_factor_type,
            ]);

            DB::commit();
            
            // Set success message before closing
            $this->successMessage = 'Usuario actualizado exitosamente';
            $this->errorMessage = '';
            
            // Close modal after successful update
            $this->closeModal();
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->errorMessage = 'Error al actualizar el usuario: ' . $e->getMessage();
            
            Log::error('User update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => [
                    'user_id' => $user->id,
                    'email' => $this->email,
                    'profile_id' => $this->profile_id,
                    'warehouseId' => $this->warehouseId,
                ]
            ]);
        }
    }

    /**
     * Computed property to get users with relationships
     */
    public function getUsersProperty()
    {
        $sessionTenant = $this->getTenantId();

        return User::query()
           
            ->with(['profile', 'contact.warehouse.company'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('email', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);
    }

    /**
     * Reset pagination when search is updated
     */
    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Toggle sort field and direction
     */
    public function sortBy(string $field): void
    {
        if ($this->sortField === $field) {
            // Toggle direction if same field
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            // Set new field and default to ascending
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
    }

    /**
     * Toggle user status in vnt_contacts and user_tenants
     * 
     * @param int $userId ID del usuario a actualizar
     * @return void
     */
    public function toggleItemStatus(int $userId): void
    {
        try {
            // 1. Obtener tenant actual
            $tenantId = $this->getTenantId();
            
            // 2. Buscar usuario con relaciones
            $user = User::with('contact')->findOrFail($userId);
            
            // 3. Validar que tiene contacto
            if (!$user->contact) {
                throw new \Exception('Usuario sin contacto asociado');
            }
            
            // dd($userId);
            // 4. Buscar relación UserTenant
            $userTenant = UserTenant::where('user_id', $userId)
                ->where('tenant_id', $tenantId)
                ->firstOrFail();
            // dd($userTenant);
            // 5. Iniciar transacción
            DB::beginTransaction();
            
            // 6. Calcular nuevo estado (toggle)
            $newStatus = !$user->contact->status;

            if ($this->canCreateOrUpdateUsers(true, $newStatus)) {
                DB::rollBack();
                $this->errorMessage = 'No tienes permisos para crear usuarios';
                return;
            }
            
            $company = VntCompany::with(['warehouses.contacts', 'contacts'])->where('billingEmail', $user->email)->firstOrFail();
             // Toggle company status
            $newStatus = $company->status ? 0 : 1;
            $company->update(['status' => $newStatus]);
             // Update all warehouses status
            foreach ($company->warehouses as $warehouse) {
                 $warehouse->update(['status' => $newStatus]);
                // Update all contacts for this warehouse
                foreach ($warehouse->contacts as $contact) {
                   $contact->update(['status' => $newStatus]);
                } 
            }
             
             // Update all contacts directly from company (vnt_contacts)
            foreach ($company->contacts as $contact) {
              $contact->update(['status' => $newStatus]);
            }

            // 7. Actualizar vnt_contacts
            $user->contact->update(['status' => $newStatus]);
            
            // 8. Actualizar user_tenants
            $userTenant->update(['is_active' => $newStatus]);
            
            // 9. Confirmar transacción
            DB::commit();
            
            // 10. Mensaje de éxito
            $this->successMessage = 'Estado actualizado exitosamente';
            
            // Clear any error messages
            $this->errorMessage = '';
            
            // 11. Forzar re-render de la tabla para actualizar el switch
            $this->dispatch('refresh-users');
            
        } catch (\Exception $e) {
            // Rollback y manejo de error
            DB::rollBack();
            $this->errorMessage = 'Error al actualizar el estado: ' . $e->getMessage();
            
            Log::error('Toggle status failed', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Delete user
     */
    public function delete(int $userId): void
    {
        try {
            // Check to prevent deletion of currently logged-in user
            if (Auth::id() === $userId) {
                $this->errorMessage = 'No puedes eliminar tu propio usuario';
                return;
            }

            // Find and delete the user
            $user = User::findOrFail($userId);
            $user->delete();

            // Set success message
            $this->successMessage = 'Usuario eliminado exitosamente';
            
            // Clear any error messages
            $this->errorMessage = '';
            
        } catch (\Exception $e) {
            // Set error message on failure
            $this->errorMessage = 'Error al eliminar el usuario: ' . $e->getMessage();
            
            Log::error('User deletion failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'user_id' => $userId,
            ]);
        }
    }

    /**
     * Open change password modal for a specific user
     */
    /**
     * Open the change password modal
     */
    public function openChangePasswordModal(int $userId): void
    {
        try {
            $this->userToChangePassword = User::findOrFail($userId);
            $this->resetChangePasswordForm();
            $this->showChangePasswordModal = true;
        } catch (\Exception $e) {
            $this->errorMessage = 'Error: Usuario no encontrado';
        }
    }

    /**
     * Close the change password modal
     */
    public function closeChangePasswordModal(): void
    {
        $this->showChangePasswordModal = false;
        $this->resetChangePasswordForm();
    }

    /**
     * Reset the change password form
     */
    public function resetChangePasswordForm(): void
    {
        $this->newPassword = '';
        $this->confirmPassword = '';
        $this->resetValidation(['newPassword', 'confirmPassword']);
    }

    /**
     * Change user password
     */
    public function changePassword(): void
    {
        $this->validate([
            'newPassword' => 'required|min:8',
            'confirmPassword' => 'required|same:newPassword',
        ], [
            'newPassword.required' => 'La nueva contraseña es requerida.',
            'newPassword.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'confirmPassword.required' => 'La confirmación de contraseña es requerida.',
            'confirmPassword.same' => 'Las contraseñas no coinciden.',
        ]);

        try {
            $this->userToChangePassword->update([
                'password' => Hash::make($this->newPassword)
            ]);

            $this->successMessage = 'Contraseña actualizada exitosamente para ' . $this->userToChangePassword->name;
            $this->closeChangePasswordModal();

            // Clear messages after a delay
            $this->dispatch('$nextTick', fn() =>
                $this->dispatch('$nextTick', fn() =>
                    $this->dispatch('clearMessages')
                )
            );

        } catch (\Exception $e) {
            $this->errorMessage = 'Error al cambiar la contraseña: ' . $e->getMessage();
        }
    }

    /**
     * Export users to Excel format
     */
    // Metodos para WithExport
    public function getExportData()
    {
        $this->ensureTenantConnection();
        return User::query()
            ->with(['profile', 'contact.warehouse.company', 'contact.position'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('email', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy('id', 'desc')
            ->get();
    }

    public function getExportHeadings(): array
    {
        return [
            'ID',
            'Nombre Completo',
            'Email',
            'Teléfono',
            'Perfil',
            'Sucursal',
            'Empresa',
            'Cargo',
            'Fecha Registro'
        ];
    }

    public function getExportMapping($user): array
    {
        $contact = $user->contact;
        $fullName = $user->name;
        $warehouse = '';
        $company = '';
        $position = '';

        if ($contact) {
            $nameParts = array_filter([
                $contact->firstName,
                $contact->secondName,
                $contact->lastName,
                $contact->secondLastName
            ]);
            if (!empty($nameParts)) {
                $fullName = implode(' ', $nameParts);
            }

            if ($contact->warehouse) {
                $warehouse = $contact->warehouse->name ?? '';
                if ($contact->warehouse->company) {
                    $company = $contact->warehouse->company->name ?? '';
                }
            }

            $position = $contact->position->name ?? '';
        }

        return [
            $user->id,
            $fullName,
            $user->email,
            $user->phone ?? '',
            $user->profile->name ?? '',
            $warehouse,
            $company,
            $position,
            $user->created_at ? $user->created_at->format('Y-m-d H:i') : ''
        ];
    }

    public function getExportFilename(): string
    {
        return 'usuarios_' . date('Y-m-d_His');
    }

    public function render()
    {
        return view('livewire.tenant.users.components.user-rap-form', [
            'users' => $this->users,
        ]);
    }

    private function getTenantId()
    {
        $tenantId = session('tenant_id');

        if (!$tenantId) {
            throw new \Exception('No tenant selected');
        }
        return $tenantId;
    }

    private function canCreateOrUpdateUsers(bool $update = false, $toggle = false): bool
    {

        $this->ensureTenantConnection();        
        $this->initializeCompanyConfiguration();

        // DEBUG: Limpiar caché para testing
        $this->clearConfigurationCache();
        $result = $this->isOptionEnabled(1);
        $value = $this->getOptionValue(1);
        

        $filteredUsers = $this->users->filter(function($user) {
            return $user->contact && $user->contact->status == 1;
         });
         $count = $filteredUsers->count();

        // DEBUG: Log detallado de verificación
        Log::info('🔍 canCreateOrUpdateUsers() verificación', [
            'companyId' => $this->currentCompanyId,
            'option_id' => 1,
            'result' => $result ? 'TRUE' : 'FALSE',
            'option_value' => $value,
            'configService_exists' => $this->configService ? 'YES' : 'NO',
            'method_called' => 'isOptionEnabled(10) y getOptionValue(10)',
            'update' => $update,
            'count' => $count,
            'toggle' => $toggle
        ]);
        // validation create
        if(!$update){
           return (int)$value <= (int)$count;
        }
        // validation update positive
        if(!$toggle){
            return (int)$value < (int)$count;
        }
        // validation update negative
        if($toggle){
            return (int)$value == (int)$count;
        }
         return false;
    }

    
    private function ensureTenantConnection(): void
    {
        $tenantId = session('tenant_id');

        if (!$tenantId) {
            throw new \Exception('No tenant selected');
        }

        $tenant = Tenant::find($tenantId);

        if (!$tenant) {
            session()->forget('tenant_id');
            throw new \Exception('Invalid tenant');
        }

        // Establecer conexión tenant
        $tenantManager = app(TenantManager::class);
        $tenantManager->setConnection($tenant);

        // Inicializar tenancy
        tenancy()->initialize($tenant);
    }
}
