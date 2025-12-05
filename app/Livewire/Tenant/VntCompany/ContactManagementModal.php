<?php

namespace App\Livewire\Tenant\VntCompany;

use Livewire\Component;
use App\Models\Tenant\Customer\VntCompany;
use App\Livewire\Tenant\VntCompany\Services\ContactService;
use App\Services\Tenant\TenantManager;
use App\Models\Auth\Tenant;
use Illuminate\Support\Facades\Log;

class ContactManagementModal extends Component
{
    // Modal state
    public $companyId;
    public $companyName = '';
    
    // Form state
    public $formMode = null; // null, 'create', 'edit'
    public $editingContactId = null;
    
    // Form data
    public $contactForm = [
        'firstName' => '',
        'secondName' => '',
        'lastName' => '',
        'secondLastName' => '',
        'email' => '',
        'business_phone' => '',
        'personal_phone' => '',
        'warehouseId' => '',
        'positionId' => '',
    ];
    
    // Messages
    public $successMessage = '';
    public $errorMessage = '';
    
    // Email validation
    public $emailError = '';
    public $emailExists = false;
    public $isCheckingEmail = false;
    
    protected $contactService;
    
    public function boot(ContactService $contactService)
    {
        $this->contactService = $contactService;
    }
    
    public function mount($companyId)
    {
        $this->companyId = $companyId;
        $this->loadCompanyData();
    }
    
    public function render()
    {
        $this->ensureTenantConnection();
        
        // Load data fresh on every render without storing in properties
        $contacts = $this->contactService->getContactsByCompany($this->companyId);
        $warehouses = $this->contactService->getCompanyWarehouses($this->companyId);
        $positions = $this->contactService->getAvailablePositions();
        
        return view('livewire.tenant.vnt-company.components.contact-management-modal', [
            'contacts' => $contacts,
            'warehouses' => $warehouses,
            'positions' => $positions
        ]);
    }
    
    private function loadCompanyData()
    {
        $this->ensureTenantConnection();
        $company = VntCompany::findOrFail($this->companyId);
        $this->companyName = $company->businessName ?: trim($company->firstName . ' ' . $company->lastName);
    }
    

    
    /**
     * Cerrar modal y dispatch evento
     * Requirements: 1.3, 1.4
     */
    public function closeModal()
    {
        $this->dispatch('contact-modal-closed');
    }
    
    /**
     * Iniciar creación de nuevo contacto
     * Requirements: 2.1
     */
    public function startCreateContact()
    {
        $this->formMode = 'create';
        $this->editingContactId = null;
        $this->resetContactForm();
        $this->clearMessages();
        $this->clearEmailValidation();
    }
    
    /**
     * Cargar datos de contacto para edición
     * Requirements: 3.1
     */
    public function editContact($contactId)
    {
        try {

            $this->ensureTenantConnection();
            $contact = \App\Models\Tenant\Customer\VntContacts::with(['warehouse', 'position'])
                ->findOrFail($contactId);
            
            // Validar que el contacto pertenece a la empresa
            if ($contact->warehouse->companyId != $this->companyId) {
                $this->errorMessage = 'El contacto no pertenece a esta empresa';
                return;
            }
            
            $this->formMode = 'edit';
            $this->editingContactId = $contactId;
            
            // Cargar datos en el formulario
            $this->contactForm = [
                'firstName' => $contact->firstName,
                'secondName' => $contact->secondName ?? '',
                'lastName' => $contact->lastName,
                'secondLastName' => $contact->secondLastName ?? '',
                'email' => $contact->email ?? '',
                'business_phone' => $contact->business_phone ?? '',
                'personal_phone' => $contact->personal_phone ?? '',
                'warehouseId' => $contact->warehouseId,
                'positionId' => $contact->positionId,
            ];
            
            $this->clearMessages();
            $this->clearEmailValidation();
            
        } catch (\Exception $e) {
            $this->errorMessage = 'Error al cargar el contacto: ' . $e->getMessage();
            Log::error('Error loading contact for edit', [
                'contactId' => $contactId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    /**
     * Validar email en tiempo real
     */
    public function validateEmailRealtime($email)
    {
        $this->isCheckingEmail = true;
        $this->emailError = '';
        $this->emailExists = false;
        
        if (empty(trim($email))) {
            $this->isCheckingEmail = false;
            return;
        }
        
        try {
            $this->ensureTenantConnection();
            
            $existingContact = \App\Models\Tenant\Customer\VntContacts::whereHas('warehouse')
            ->where('email', $email)
            ->when($this->formMode === 'edit', function ($query) {
                $query->where('id', '!=', $this->editingContactId);
            })
            ->first();
            
            if ($existingContact) {
                $this->emailExists = true;
                $this->emailError = 'Este email ya existe en los contactos de esta empresa';
            }
            
        } catch (\Exception $e) {
            Log::error('Error validating email', [
                'email' => $email,
                'error' => $e->getMessage()
            ]);
        }
        
        $this->isCheckingEmail = false;
    }
    
    /**
     * Guardar contacto (crear o actualizar)
     * Requirements: 2.5, 2.6, 2.7, 3.3, 3.4, 3.5, 3.6
     */
    public function saveContact()
    {
        try {
            // Validar que no haya error de email duplicado
            if ($this->emailExists) {
                $this->errorMessage = 'No se puede guardar el contacto: ' . $this->emailError;
                return;
            }
            
            // Validar datos del formulario
            $this->validate($this->rules(), $this->messages());
            
            $this->ensureTenantConnection();

            // Preparar datos para el servicio
            $data = array_merge($this->contactForm, [
                'companyId' => $this->companyId
            ]);
            
            if ($this->formMode === 'create') {
                // Crear nuevo contacto
                $this->contactService->createContact($data);
                $this->successMessage = 'Contacto creado exitosamente';
                
                Log::info('Contact created successfully', [
                    'companyId' => $this->companyId,
                    'contactData' => $data
                ]);
                
            } elseif ($this->formMode === 'edit') {
                // Actualizar contacto existente
                $this->contactService->updateContact($this->editingContactId, $data);
                $this->successMessage = 'Contacto actualizado exitosamente';
                
                Log::info('Contact updated successfully', [
                    'contactId' => $this->editingContactId,
                    'companyId' => $this->companyId,
                    'contactData' => $data
                ]);
            }
            
            // Limpiar formulario - los contactos se recargarán automáticamente en render()
            $this->formMode = null;
            $this->editingContactId = null;
            $this->resetContactForm();
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Re-lanzar excepciones de validación para que Livewire las maneje
            throw $e;
            
        } catch (\Exception $e) {
            $this->errorMessage = 'Error al guardar el contacto: ' . $e->getMessage();
            
            Log::error('Error saving contact', [
                'formMode' => $this->formMode,
                'contactId' => $this->editingContactId,
                'companyId' => $this->companyId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    /**
     * Eliminar contacto (soft delete)
     * Requirements: 4.1, 4.2, 4.3, 4.4, 4.5
     */
    public function deleteContact($contactId)
    {
        try {
            
            // Validar que el contacto pertenece a la empresa
            $contact = \App\Models\Tenant\Customer\VntContacts::with('warehouse')
                ->findOrFail($contactId);
            
            if ($contact->warehouse->companyId != $this->companyId) {
                $this->errorMessage = 'El contacto no pertenece a esta empresa';
                return;
            }
            
            // Eliminar contacto (soft delete)
            $this->contactService->deleteContact($contactId);
            
            $this->successMessage = 'Contacto eliminado exitosamente';
            
            Log::info('Contact deleted successfully', [
                'contactId' => $contactId,
                'companyId' => $this->companyId
            ]);
            
        } catch (\Exception $e) {
            $this->errorMessage = 'Error al eliminar el contacto: ' . $e->getMessage();
            
            Log::error('Error deleting contact', [
                'contactId' => $contactId,
                'companyId' => $this->companyId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    /**
     * Cambiar estado de contacto (activo/inactivo)
     * Requirements: 5.1, 5.2, 5.3, 5.4, 5.5
     */
    public function toggleContactStatus($contactId)
    {
        try {
            $this->ensureTenantConnection();
            // Validar que el contacto pertenece a la empresa
            $contact = \App\Models\Tenant\Customer\VntContacts::with('warehouse')
                ->findOrFail($contactId);
            if ($contact->warehouse->companyId != $this->companyId) {
                $this->errorMessage = 'El contacto no pertenece a esta empresa';
                return;
            }
            // Toggle status
            $updatedContact = $this->contactService->toggleContactStatus($contactId);
            $statusText = $updatedContact->status === 1 ? 'activado' : 'desactivado';
            $this->successMessage = "Contacto {$statusText} exitosamente";
            Log::info('Contact status toggled successfully', [
                'contactId' => $contactId,
                'companyId' => $this->companyId,
                'newStatus' => $updatedContact->status
            ]);
            
        } catch (\Exception $e) {
            $this->errorMessage = 'Error al cambiar el estado del contacto: ' . $e->getMessage();
            
            Log::error('Error toggling contact status', [
                'contactId' => $contactId,
                'companyId' => $this->companyId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    /**
     * Cancelar formulario y cerrar
     * Requirements: 2.2, 3.2, 7.6
     */
    public function cancelForm()
    {
        $this->formMode = null;
        $this->editingContactId = null;
        $this->resetContactForm();
        $this->clearMessages();
        $this->clearEmailValidation();
        $this->resetValidation();
    }
    
    /**
     * Limpiar formulario de contacto
     * Requirements: 7.7
     */
    private function resetContactForm()
    {
        $this->contactForm = [
            'firstName' => '',
            'secondName' => '',
            'lastName' => '',
            'secondLastName' => '',
            'email' => '',
            'business_phone' => '',
            'personal_phone' => '',
            'warehouseId' => '',
            'positionId' => '',
        ];
    }
    
    /**
     * Limpiar mensajes de éxito y error
     */
    private function clearMessages()
    {
        $this->successMessage = '';
        $this->errorMessage = '';
    }
    
    /**
     * Limpiar validación de email
     */
    private function clearEmailValidation()
    {
        $this->emailError = '';
        $this->emailExists = false;
        $this->isCheckingEmail = false;
    }
    
    /**
     * Reglas de validación del formulario
     */
    protected function rules()
    {
        return [
            'contactForm.firstName' => 'required|string|max:50',
            'contactForm.secondName' => 'nullable|string|max:50',
            'contactForm.lastName' => 'required|string|max:50',
            'contactForm.secondLastName' => 'nullable|string|max:50',
            'contactForm.email' => 'nullable|email|max:100',
            'contactForm.business_phone' => 'nullable|string|max:20',
            'contactForm.personal_phone' => 'nullable|string|max:20',
            'contactForm.warehouseId' => 'required|exists:vnt_warehouses,id',
            'contactForm.positionId' => 'required|exists:cnf_positions,id',
        ];
    }
    
    /**
     * Mensajes personalizados de validación
     */
    protected function messages()
    {
        return [
            'contactForm.firstName.required' => 'El primer nombre es obligatorio',
            'contactForm.firstName.max' => 'El primer nombre no puede exceder 50 caracteres',
            'contactForm.lastName.required' => 'El primer apellido es obligatorio',
            'contactForm.lastName.max' => 'El primer apellido no puede exceder 50 caracteres',
            'contactForm.email.email' => 'El email debe tener un formato válido',
            'contactForm.email.max' => 'El email no puede exceder 100 caracteres',
            'contactForm.warehouseId.required' => 'La sucursal es obligatoria',
            'contactForm.warehouseId.exists' => 'La sucursal seleccionada no es válida',
            'contactForm.positionId.required' => 'El cargo es obligatorio',
            'contactForm.positionId.exists' => 'El cargo seleccionado no es válido',
        ];
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
