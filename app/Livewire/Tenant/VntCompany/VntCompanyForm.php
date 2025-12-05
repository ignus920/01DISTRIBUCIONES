<?php

namespace App\Livewire\Tenant\VntCompany;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use App\Livewire\Tenant\VntCompany\Services\CompanyService;
use App\Livewire\Tenant\VntCompany\Services\WarehouseService;
use App\Livewire\Tenant\VntCompany\Services\CompanyQueryService;
use App\Livewire\Tenant\VntCompany\Services\CompanyValidationService;
use App\Livewire\Tenant\VntCompany\Services\ExportService;


class VntCompanyForm extends Component
{
    use WithPagination;

    // Services
    protected $companyService;
    protected $warehouseService;
    protected $queryService;
    protected $validationService;
    protected $exportService;
    protected $listeners = [
        'type-identification-changed' => 'updateTypeIdentification',
        'regime-changed' => 'updateRegime',
        'fiscal-responsibility-changed' => 'updateFiscalResponsibility',
        'city-changed' => 'updateWarehouseCity',
        'position-changed' => 'updatePosition',
        'warehouse-modal-closed' => 'handleWarehouseModalClosed', 
        'contact-modal-closed' => 'handleContactModalClosed',
        'citySelected' => 'updateCityName'
    ];

    public $search = '';
    public $showModal = false;
    public $editingId = null;
    public $perPage = 10;
    public $sortField = 'id';
    public $sortDirection = 'desc';
    
    // Warehouse modal properties
    public $reusable = false;
    public $companyId = null; // ID del cliente a editar (cuando se usa de forma reutilizable)
    public $showWarehouseModal = false;
    public $selectedCompanyId = null;
    
    // Contact modal properties
    public $showContactModal = false;
    public $selectedCompanyIdForContacts = null;

    // Propiedades del formulario
    public $businessName = '';
    public $billingEmail = '';
    public $firstName = '';
    public $lastName = '';
    public $secondName = '';
    public $secondLastName = '';
    public $integrationDataId = '';
    public $identification = '';
    public $checkDigit = '';
    public $status = 1;
    public $typePerson = '';
    public $typeIdentificationId = '';
    public $regimeId = '';
    public $code_ciiu = '';
    public $fiscalResponsabilityId = '';
    public $verification_digit = '';
    
    // Real-time validation properties
    public $identificationExists = false;
    public $validatingIdentification = false;
    public $emailExists = false;
    public $validatingEmail = false;
    
    // Propiedades para contacto
    public $business_phone = '';
    public $personal_phone = '';
    public $positionId = 1; // Posición por defecto

    // Propiedades para sucursales
    public $warehouses = [];
    public $warehouseName = '';
    public $warehouseAddress = '';
    public $warehousePostcode = '';
    public $warehouseCityId = '';
    public $warehouseIsMain = false;
    public $canAddMoreWarehouses = false;
    public $warehouseCityName = '';
    
    // IDs para actualización (evitar duplicación)
    public $mainWarehouseId = null;
    public $mainContactId = null;
    
    // Control de visualización de campos
    public $showNaturalPersonFields = false;
    
    // Propiedad para rastrear errores de validación
    public $formHasErrors = false;

    public function boot(
        CompanyService $companyService,
        WarehouseService $warehouseService,
        CompanyQueryService $queryService,
        CompanyValidationService $validationService,
        ExportService $exportService
    ) {
        $this->companyService = $companyService;
        $this->warehouseService = $warehouseService;
        $this->queryService = $queryService;
        $this->validationService = $validationService;
        $this->exportService = $exportService;
    }

    /**
     * Reglas de validación dinámicas
     * 
     * Las reglas se obtienen del CompanyValidationService y varían según:
     * - Tipo de persona (Natural/Jurídica)
     * - Tipo de identificación (NIT requiere selección manual de tipo de persona)
     * - Modo edición (permite duplicados del mismo registro)
     */
    protected function rules()
    {
        return $this->validationService->getValidationRules(
            $this->typePerson, 
            $this->editingId,
            $this->typeIdentificationId ? (int) $this->typeIdentificationId : null,
            true // Incluir reglas de warehouse y contacto
        );
    }

    /**
     * Mensajes de validación personalizados
     */
    protected function messages()
    {
        return $this->validationService->getValidationMessages();
    }

    /**
     * Atributos personalizados para mensajes de validación
     */
    protected function validationAttributes()
    {
        return $this->validationService->getValidationAttributes();
    }

    protected $queryString = [
        'search' => ['except' => ''],
        'perPage' => ['except' => 10],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingPerPage()
    {
        $this->resetPage();
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


    
   public function getItemsProperty()
   {
     return $this->queryService->getPaginatedCompanies(
        $this->search,
        $this->perPage,
        $this->sortField,
        $this->sortDirection
     ); 
   }

   public function render()
   {
     return view('livewire.tenant.vnt-company.components.vnt-company-form', [
        'items' => $this->items, // Se cachea automáticamente entre renders
        'sortField' => $this->sortField,
        'sortDirection' => $this->sortDirection
    ]);
   }

    public function create()
    {
        $this->clearUniqueValidationErrors();
        $this->resetForm();
        $this->showModal = true;
    }



    public function edit($id)
    {
        
         $this->clearUniqueValidationErrors(); 
        $company = $this->companyService->getCompanyForEdit($id);
        
        // Log company loading for debugging
        Log::info('Loading company for edit', [
            'company_id' => $id,
            'has_main_warehouse' => $company->mainWarehouse !== null,
            'has_contacts' => $company->mainWarehouse?->contacts->isNotEmpty() ?? false
        ]);
        
        $this->editingId = $id;
        $this->typeIdentificationId = $company->typeIdentificationId;
        $this->identification = $company->identification;
        $this->firstName = $company->firstName;
        $this->secondName = $company->secondName;
        $this->lastName = $company->lastName;
        $this->secondLastName = $company->secondLastName;
        $this->businessName = $company->businessName;
        $this->billingEmail = $company->billingEmail;
        $this->regimeId = $company->regimeId;
        $this->fiscalResponsabilityId = $company->fiscalResponsabilityId;
        $this->code_ciiu = $company->code_ciiu;
        $this->checkDigit = (string)$company->checkDigit;
        $this->verification_digit = (string)$company->checkDigit; // Cargar el DV desde checkDigit
        $this->status = $company->status ?? 1;
        
        // Log detallado de la carga de datos para verificación
        Log::info('Company data loaded in edit()', [
            'company_id' => $id,
            'loaded_fields' => [
                'typeIdentificationId' => $this->typeIdentificationId,
                'identification' => $this->identification,
                'firstName' => $this->firstName,
                'secondName' => $this->secondName,
                'lastName' => $this->lastName,
                'secondLastName' => $this->secondLastName,
                'businessName' => $this->businessName,
                'billingEmail' => $this->billingEmail,
                'regimeId' => $this->regimeId,
                'fiscalResponsabilityId' => $this->fiscalResponsabilityId,
                'code_ciiu' => $this->code_ciiu,
                'checkDigit' => $this->checkDigit,
                'verification_digit' => $this->verification_digit,
                'status' => $this->status,
            ]
        ]);
        
        // Determinar tipo de persona para la UI usando la nueva lógica
        $this->typePerson = $this->determineTypePersonForUI($company);
        
        // Establecer showNaturalPersonFields basándose en el tipo determinado
        $this->showNaturalPersonFields = ($this->typePerson === 'Natural');
        
        // Log informativo para debugging
        Log::info('Type person determined for UI', [
            'company_id' => $id,
            'typeIdentificationId' => $company->typeIdentificationId,
            'typePerson_db' => $company->typePerson,
            'typePerson_ui' => $this->typePerson,
            'showNaturalPersonFields' => $this->showNaturalPersonFields,
            'has_natural_data' => $this->hasNaturalPersonData($company)
        ]);
        
        // Load main warehouse data into form properties
        $mainWarehouse = $company->mainWarehouse;
        if ($mainWarehouse) {
            $this->mainWarehouseId = $mainWarehouse->id;
            $this->warehouseName = $mainWarehouse->name;
            $this->warehouseAddress = $mainWarehouse->address;
            $this->warehousePostcode = $mainWarehouse->postcode;
            $this->warehouseCityId = $mainWarehouse->cityId;
            
            // Load contact data if exists
            $mainContact = $mainWarehouse->contacts->first();
            if ($mainContact) {
                $this->mainContactId = $mainContact->id;
                $this->business_phone = $mainContact->business_phone;
                $this->personal_phone = $mainContact->personal_phone;
                $this->positionId = $mainContact->positionId;
            }
        }
        
        // Cargar sucursales usando el service
        $this->warehouses = $this->warehouseService->prepareWarehousesForForm($company);
        
        // Si no hay sucursales, inicializar con una por defecto
        if (empty($this->warehouses)) {
            $this->initializeDefaultWarehouse();
        } else {
            // Evaluar permisos para la empresa existente
            $this->evaluateWarehousePermissions();
        }
        
        // Log final antes de mostrar el modal para verificar el estado
        Log::info('Final state before showing modal', [
            'company_id' => $id,
            'typePerson' => $this->typePerson,
            'typeIdentificationId' => $this->typeIdentificationId,
            'showNaturalPersonFields' => $this->showNaturalPersonFields,
            'verification_digit' => $this->verification_digit
        ]);
        
        $this->showModal = true;
    }

    public function save()
    {
        // Establecer typePerson automáticamente si no es NIT antes de validar
        if ($this->typeIdentificationId && (int) $this->typeIdentificationId !== 2 && empty($this->typePerson)) {
            $this->typePerson = 'Natural';
        }
        
        // Convertir strings vacíos a null solo para campos opcionales en Persona Natural
        if ($this->typePerson === 'Natural') {
            $this->regimeId = $this->regimeId === '' ? null : $this->regimeId;
            $this->fiscalResponsabilityId = $this->fiscalResponsabilityId === '' ? null : $this->fiscalResponsabilityId;
        }
        
        // Validar que identification y email no existan antes de proceder
        if ($this->identificationExists) {
            $this->addError('identification', 'Este número de identificación ya está registrado.');
            return;
        }
        
        if ($this->emailExists) {
            $this->addError('billingEmail', 'Este email de facturación ya está registrado.');
            return;
        }

          if (!$this->cityValidate(0)) {
              $this->addError('warehouseName', 'La ciudad seleccionada no es válida.');
            return; // Si la validación de ciudad falla, detener el guardado
        }
        
        
        // Validación simple usando Livewire nativo
        $this->validate();
        
        $data = $this->getFormData();
        
        // DEBUG: Mostrar todos los valores del formulario
        // dd([
        //     'action' => $this->editingId ? 'update' : 'create',
        //     'editingId' => $this->editingId,
        //     'form_data' => $data,
        //     'warehouses' => $this->warehouses,
        //     'permissions' => [
        //         'canAddMoreWarehouses' => $this->canAddMoreWarehouses,
        //         'warehouseLimitsInfo' => $this->getWarehouseLimitsInfo()
        //     ],
        //     'all_component_properties' => [
        //         'businessName' => $this->businessName,
        //         'billingEmail' => $this->billingEmail,
        //         'firstName' => $this->firstName,
        //         'lastName' => $this->lastName,
        //         'secondName' => $this->secondName,
        //         'secondLastName' => $this->secondLastName,
        //         'integrationDataId' => $this->integrationDataId,
        //         'identification' => $this->identification,
        //         'checkDigit' => $this->checkDigit,
        //         'status' => $this->status,
        //         'typePerson' => $this->typePerson,
        //         'typeIdentificationId' => $this->typeIdentificationId,
        //         'regimeId' => $this->regimeId,
        //         'code_ciiu' => $this->code_ciiu,
        //         'fiscalResponsabilityId' => $this->fiscalResponsabilityId,
        //         'verification_digit' => $this->verification_digit,
        //         'warehouseName' => $this->warehouseName,
        //         'warehouseAddress' => $this->warehouseAddress,
        //         'warehousePostcode' => $this->warehousePostcode,
        //         'warehouseCityId' => $this->warehouseCityId,
        //         'warehouseIsMain' => $this->warehouseIsMain,
        //     ],
        //     'validation_rules' => $this->rules(),
        //     'timestamp' => now()->toDateTimeString()
        // ]);
        
        // Preparar array de warehouses con los datos del formulario
        $warehouses = [[
            'id' =>  $this->mainWarehouseId,
            'name' => $this->editingId
                      ? ($this->warehouseName ?? 'Principal')
                      : 'Principal',
            'address' => $this->warehouseAddress,
            'postcode' => $this->warehousePostcode,
            'cityId' => $this->warehouseCityId, 
            'main' => true, // Siempre es la sucursal principal
        ]];
        // dd($warehouses);
        try {
            if ($this->editingId) {
                $company = $this->companyService->update($this->editingId, $data, $warehouses, $this->mainContactId);
                session()->flash('message', 'Registro actualizado exitosamente.');

                // Disparar evento para componentes que escuchan
                $this->dispatch('customer-updated', $this->editingId);
            } else {
                $company = $this->companyService->create($data, $warehouses);
                session()->flash('message', 'Registro creado exitosamente.');

                // Disparar evento para componentes que escuchan
                if ($company && isset($company->id)) {
                    $this->dispatch('customer-created', $company->id);
                    $this->dispatch('vnt-company-saved', $company->id);
                }
            }

            $this->resetForm();
            $this->showModal = false;
        } catch (\Exception $e) {
            session()->flash('error', 'Error al guardar: ' . $e->getMessage());
            return;
        }
    }

    public function delete($id)
    {
        try {
            $this->companyService->delete($id);
            session()->flash('message', 'Registro eliminado exitosamente.');
        } catch (\Exception $e) {
            session()->flash('error', 'Error al eliminar: ' . $e->getMessage());
        }
    }

    public function handleWarehouseModalClosed()
    {
        $this->showWarehouseModal = false;
        $this->selectedCompanyId = null;
    }

    public function openWarehouseModal($companyId)
    {
        $this->showWarehouseModal = true;
        $this->selectedCompanyId = $companyId;
    }

    public function handleContactModalClosed()
    {
        $this->showContactModal = false;
        $this->selectedCompanyIdForContacts = null;
    }

    public function openContactModal($companyId)
    {
        $this->showContactModal = true;
        $this->selectedCompanyIdForContacts = $companyId;
    }

    public function exportExcel()
    {
        $result = $this->exportService->exportToExcel($this->search);
        $this->dispatch('show-toast', [
            'type' => $result['success'] ? 'success' : 'info',
            'message' => $result['message']
        ]);
    }

    public function exportPdf()
    {
        $result = $this->exportService->exportToPdf($this->search);
        $this->dispatch('show-toast', [
            'type' => $result['success'] ? 'success' : 'info',
            'message' => $result['message']
        ]);
    }

    public function exportCsv()
    {
        $result = $this->exportService->exportToCsv($this->search);
        $this->dispatch('show-toast', [
            'type' => $result['success'] ? 'success' : 'info',
            'message' => $result['message']
        ]);
    }

    private function resetForm()
    {
        $this->editingId = null;
        $this->businessName = '';
        $this->firstName = '';
        $this->billingEmail = '';
        $this->identification = '';
        $this->integrationDataId = '';
        $this->lastName = '';
        $this->secondLastName = '';
        $this->checkDigit = '';
        $this->status = 1; // Default to active for new records
        $this->secondName = '';
        $this->typeIdentificationId = '';
        $this->typePerson = '';
        $this->code_ciiu = '';
        $this->regimeId = '';
        $this->fiscalResponsabilityId = '';
        $this->verification_digit = '';
        $this->business_phone = '';
        $this->personal_phone = '';
        $this->positionId = 1; // Posición por defecto
        
        // Reset real-time validation properties
        $this->identificationExists = false;
        $this->validatingIdentification = false;
        
        // Reset warehouse fields e inicializar con una sucursal por defecto
        $this->warehouses = [];
        $this->initializeDefaultWarehouse();
        $this->warehouseName = '';
        $this->warehouseAddress = '';
        $this->warehousePostcode = '';
        $this->warehouseCityId = '';
        $this->warehouseIsMain = false;
        $this->canAddMoreWarehouses = false;
        
        // Reset IDs
        $this->mainWarehouseId = null;
        $this->mainContactId = null;
        
        // Reset control de visualización
        $this->showNaturalPersonFields = false;
        
        // Reset form validation state
        $this->formHasErrors = false;

        $this->resetErrorBag();
        $this->resetValidation();
    }

    public function cancelForm()
    {
        // Cerrar el modal
        $this->showModal = false;

        // Resetear el formulario
        $this->resetForm();

        // Emitir evento para notificar al componente padre que se canceló
        $this->dispatch('customer-form-cancelled');
    }

    public function updateTypeIdentification($typeIdentificationId)
    {
        $this->typeIdentificationId = $typeIdentificationId;
        
        // Lógica de negocio: establecer tipo de persona según tipo de identificación
        if ((int) $typeIdentificationId === 2) {
            // NIT: Permitir elegir entre Natural y Jurídica (no establecer automáticamente)
            // El usuario debe elegir manualmente
        } else {
            // Cualquier otro tipo de identificación: Automáticamente Persona Natural
            $this->typePerson = 'Natural';
        }
        
        // Re-evaluar permisos de sucursales
        $this->evaluateWarehousePermissions();
    }

    public function updateRegime($regimeId)
    {
        $this->regimeId = $regimeId;
    }

    public function updateFiscalResponsibility($fiscalResponsibilityId)
    {
        $this->fiscalResponsabilityId = $fiscalResponsibilityId;
    }

    public function updateWarehouseCity($cityId, $index = 0)
    {
        // Log para ver qué parámetros están llegando
        Log::info('updateWarehouseCity called', [
            'cityId' => $cityId,
            'cityId_type' => gettype($cityId),
            'index' => $index,
            'index_type' => gettype($index)
        ]);
        
        // Validar que cityId sea numérico
        if (!is_numeric($cityId)) {
            Log::warning('Invalid cityId received in updateWarehouseCity', [
                'cityId' => $cityId,
                'index' => $index
            ]);
            return;
        }
        
        // Actualizar warehouseCityId directamente (usado en validación y guardado)
        $this->warehouseCityId = (int) $cityId;
        $city = \App\Models\Central\CnfCity::find($cityId);
        $this->warehouseCityName = $city ? $city->name : ''; 
       

        // También actualizar en el array de warehouses si existe (para compatibilidad)
        if (isset($this->warehouses[$index])) {
            $this->warehouses[$index]['cityId'] = (int) $cityId;
             $this->warehouses[$index]['cityName'] = $this->warehouseCityName;
        }
        
        // Log para debugging
        Log::info('City updated successfully', [
            'warehouseCityId' => $this->warehouseCityId,
            'warehouseCityName' => $this->warehouseCityName,
            'index' => $index
        ]);
    }

    public function updatePosition($positionId)
    {
        $this->positionId = $positionId;
    }

    public function toggleStatus()
    {
        $this->status = $this->status ? 0 : 1;
    }

    public function updatedStatus($value)
    {
        // Convert boolean to integer for database storage
        $this->status = $value ? 1 : 0;
    }

    /**
     * Toggle status for a specific company item in the table
     * Updates status in vnt_companies, vnt_warehouses, and vnt_contacts
     */
    public function toggleItemStatus($companyId)
    {
        try {
            $this->companyService->toggleCompanyStatus($companyId);
            session()->flash('message', 'Estado actualizado exitosamente.');
        } catch (\Exception $e) {
            session()->flash('error', 'Error al actualizar el estado: ' . $e->getMessage());
        }
    }

    /**
     * Validar un campo específico en tiempo real
     * Se ejecuta cuando el usuario sale del campo (blur)
     * 
     * IMPORTANTE: Después de validar cualquier campo, siempre re-validar
     * la identificación para mantener el estado de identificationExists
     */
    public function updated($propertyName)
    {
        // Validar solo el campo que cambió
        $this->validateOnly($propertyName);
        
        // Actualizar el estado de errores del formulario
        $this->formHasErrors = $this->getErrorBag()->isNotEmpty();
        
        // Validar unicidad de identification si cambió
        if ($propertyName === 'identification' && !empty($this->identification) && !empty($this->typeIdentificationId)) {
            $this->validateIdentificationUniqueness();
        }
        
        // Validar unicidad de email si cambió
        if ($propertyName === 'billingEmail' && !empty($this->billingEmail)) {
            $this->validateEmailUniqueness();
        }
    }

    /**
     * Called when identification property is updated
     * Triggers real-time validation with debounce
     */
    public function updatedIdentification($value): void
    {
        // Validate the field using existing validation
        $this->validateOnly('identification');
        
        // Trigger uniqueness check
        $this->validateIdentificationUniqueness();
    }

    public function updatedBillingEmail(): void
    {
        $this->validateOnly('billingEmail');
        $this->validateEmailUniqueness();
        
        // Re-validar identificación después de cambiar email
        if (!empty($this->identification) && !empty($this->typeIdentificationId)) {
            $this->validateIdentificationUniqueness();
        }
    }
    /**
     * Validate identification uniqueness in real-time
     * Called when identification or typeIdentificationId changes
     * 
     * IMPORTANTE: Este método SIEMPRE debe ejecutarse después de cualquier
     * validación para mantener el estado de identificationExists actualizado
     */
    public function validateIdentificationUniqueness(): void
    {
        // Skip validation if required fields are empty
        if (empty($this->identification) || empty($this->typeIdentificationId)) {
            $this->identificationExists = false;
            $this->validatingIdentification = false;
            return;
        }
        
        // Set loading state
        $this->validatingIdentification = true;
        
        try {
            // Check if combination exists
            $this->identificationExists = $this->validationService->checkIdentificationExists(
                (int) $this->typeIdentificationId,
                $this->identification,
                $this->editingId
            );
        } catch (\Exception $e) {
            // Log error but don't break the form
            Log::error('Error validating identification uniqueness', [
                'error' => $e->getMessage(),
                'identification' => $this->identification,
                'typeIdentificationId' => $this->typeIdentificationId
            ]);
            $this->identificationExists = false;
        } finally {
            // Always clear loading state
            $this->validatingIdentification = false;
        }
    }

     public function validateEmailUniqueness(): void
    {
        // Skip validation if required fields are empty
        if (empty($this->billingEmail)) {
            $this->emailExists = false;
            $this->validatingEmail = false;
            return;
        }
        
        // Set loading state
        $this->validatingEmail = true;
        
        try {
            // Check if combination exists
            $this->emailExists = $this->validationService->checkEmailExists(
                $this->billingEmail,
                $this->editingId
            );
        } catch (\Exception $e) {
            // Log error but don't break the form
            Log::error('Error validating email uniqueness', [
                'error' => $e->getMessage(),
                'billingEmail' => $this->billingEmail
            ]);
            $this->emailExists = false;
        } finally {
            // Always clear loading state
            $this->validatingEmail = false;
        }
    }

    public function setMainWarehouse($index)
    {
        $this->warehouseService->setMainWarehouse($this->warehouses, $index);
    }

    /**
     * Inicializar sucursal por defecto
     */
    private function initializeDefaultWarehouse(): void
    {
        if (empty($this->warehouses)) {
            $defaultWarehouse = $this->warehouseService->createEmptyWarehouse(0);
            $this->warehouses[] = $defaultWarehouse;
        }
        
        // Evaluar permisos para agregar más sucursales
        $this->evaluateWarehousePermissions();
    }

    /**
     * Evaluar si se pueden agregar más sucursales
     */
    public function evaluateWarehousePermissions(): void
    {
        // Lógica de negocio para determinar si se pueden agregar más sucursales
        $this->canAddMoreWarehouses = $this->warehouseService->canAddMoreWarehouses(
            $this->typePerson ?? '',
            $this->typeIdentificationId ? (int) $this->typeIdentificationId : null,
            count($this->warehouses),
            $this->editingId ? (int) $this->editingId : null
        );
    }

    /**
     * Método que se ejecuta cuando cambia el tipo de persona
     */
    public function updatedTypePerson(): void
    {
        $this->evaluateWarehousePermissions();
    }

    /**
     * Método que se ejecuta cuando cambia el tipo de identificación
     * 
     * IMPORTANTE: Siempre re-validar la identificación cuando cambia el tipo
     */
    public function updatedTypeIdentificationId(): void
    {
        // Siempre re-validar la identificación cuando cambia el tipo
        // porque la combinación typeIdentificationId + identification debe ser única
        $this->validateIdentificationUniqueness();
        
        $this->evaluateWarehousePermissions();
    }



    /**
     * Override del método addWarehouse para verificar permisos
     */
    public function addWarehouse()
    {
        if (!$this->canAddMoreWarehouses) {
            session()->flash('error', 'No tiene permisos para agregar más sucursales.');
            return;
        }

        $newWarehouse = $this->warehouseService->createEmptyWarehouse(count($this->warehouses));
        $this->warehouses[] = $newWarehouse;
        
        // Re-evaluar permisos después de agregar
        $this->evaluateWarehousePermissions();
    }

    /**
     * Override del método removeWarehouse para mantener al menos una sucursal
     */
    public function removeWarehouse($index)
    {
        if (count($this->warehouses) <= 1) {
            session()->flash('error', 'Debe mantener al menos una sucursal.');
            return;
        }

        $this->warehouseService->removeWarehouse($this->warehouses, $index);
        
        // Re-evaluar permisos después de remover
        $this->evaluateWarehousePermissions();
    }

    /**
     * Obtener información sobre los límites de sucursales
     */
    public function getWarehouseLimitsInfo(): array
    {
        return $this->warehouseService->getWarehouseLimitsInfo(
            $this->typePerson ?? '', 
            $this->typeIdentificationId ? (int) $this->typeIdentificationId : null
        );
    }

    /**
     * Determinar si una empresa tiene datos de persona natural
     */
    private function hasNaturalPersonData($company): bool
    {
        return !empty($company->firstName) || 
               !empty($company->lastName) || 
               !empty($company->secondName) || 
               !empty($company->secondLastName);
    }

    /**
     * Determinar el tipo de persona para la UI basándose en los datos de la empresa
     * 
     * Reglas de negocio simplificadas:
     * 1. Si typeIdentificationId != 2: Siempre Persona Natural (PERSON_ENTITY)
     * 2. Si typeIdentificationId == 2 (NIT):
     *    - Si tiene datos de persona natural (firstName, lastName): Persona Natural con NIT
     *    - Si NO tiene datos de persona natural: Persona Jurídica
     * 
     * @param object $company Instancia de VntCompany con todos sus datos
     * @return string "Natural" o "Juridica"
     */
    private function determineTypePersonForUI($company): string
    {
        $typeIdentificationId = (int) $company->typeIdentificationId;
        
        //dd($company);
        // Caso 1: No es NIT (typeIdentificationId != 2) → Siempre Persona Natural
        if ($typeIdentificationId !== 2) {
            return 'Natural';
        }
        
        // Caso 2: Es NIT (typeIdentificationId == 2)
        // Verificar si tiene datos de persona natural
        $hasNaturalPersonData = !empty($company->businessName);
        
        // Si tiene datos de persona natural → Persona Natural con NIT
        if (!$hasNaturalPersonData) {
            return 'Natural';
        }
        
        // Si NO tiene datos de persona natural → Persona Jurídica
        return 'Juridica';
    }


     public function clearUniqueValidationErrors()
    {
      // Limpiar errores específicos de unicidad
      $this->resetErrorBag(['billingEmail', 'identification']);
      // También resetear las banderas de existencia
      $this->identificationExists = false;
      $this->emailExists = false;
    }


    /**
     * Obtener datos del formulario para enviar al service
     */

      #[On('city-valid')]
       public function cityValidate($index, $cityId = null): bool
       {
          if ($index != 0) {
            return false;
          }
          
          // Si cityId viene del evento, usarlo directamente
          $cityIdToValidate = $cityId ?? $this->warehouseCityId;
          
          // Validar que se haya seleccionado una ciudad válida
          if (empty($cityIdToValidate) || !is_numeric($cityIdToValidate)) {
              $this->addError('warehouseCityId', 'Debe seleccionar una ciudad válida para la sucursal principal.');
            return false;
          }
          
          // Obtener el nombre de la ciudad para validar que existe
          $city = \App\Models\Central\CnfCity::find($cityIdToValidate);
          if (!$city) {
              $this->addError('warehouseCityId', 'La ciudad seleccionada no es válida.');
            return false;
          }
          
          // Actualizar las propiedades si vienen del evento
          if ($cityId !== null) {
              $this->warehouseCityId = (int) $cityId;
              $this->warehouseCityName = $city->name;
          }
          
          return true;
        }
    private function getFormData(): array
    {
        // Si es NIT, usar verification_digit como checkDigit
        $checkDigit = ((int) $this->typeIdentificationId === 2) 
            ? $this->verification_digit 
            : $this->checkDigit;
        
        return [
            'typeIdentificationId' => $this->typeIdentificationId,
            'identification' => $this->identification,
            'firstName' => $this->firstName,
            'secondName' => $this->secondName,
            'lastName' => $this->lastName,
            'secondLastName' => $this->secondLastName,
            'businessName' => $this->businessName,
            'billingEmail' => $this->billingEmail,
            'typePerson' => $this->typePerson,
            'checkDigit' => (string)$checkDigit,
            'code_ciiu' => $this->code_ciiu,
            'regimeId' => $this->regimeId,
            'fiscalResponsabilityId' => $this->fiscalResponsabilityId,
            'status' => $this->status,
            'business_phone' => $this->business_phone,
            'personal_phone' => $this->personal_phone,
            'positionId' => $this->positionId,
        ];
    }
}
