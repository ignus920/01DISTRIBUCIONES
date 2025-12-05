<?php

namespace App\Livewire\Company;

use App\Models\Central\VntCompany;
use App\Models\Central\VntContact;
use App\Models\Central\VntWarehouse;
use App\Models\Auth\Tenant;
use App\Services\Company\CompanyDataValidator;
use App\Services\Tenant\TenantManager;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Layout;

class UpdateCompany extends Component
{
    // Control de pasos
    public int $currentStep = 1;
    public int $totalSteps = 2;

    // Datos de la empresa (campos existentes)
    public string $identification = '';
    public string $verification_digit = '';
    public string $typePerson = '';
    public string $code_ciiu = '';
    public $typeIdentificationId = 0;
    public $regimeId = 0;
    public $fiscalResponsibilityId = 0;

    // Campos para persona natural
    public string $firstName = '';
    public string $lastName = '';

    // Campo para persona jurídica
    public string $businessName = '';

    // Datos del contacto (campos existentes)
    public $positionId = 0;
    public $warehouseId = 0;

    // Datos del warehouse (campos existentes)
    public string $address = '';
    public string $postcode = '';
    public $cityId = 0;
    public $termId = 0;

    // Campos para sucursales
    public bool $hasMultipleBranches = false;
    public string $branchName = '';
    public string $branchType = 'fija';
    public string $city = '';
    public string $billingFormat = '';
    public bool $isCredit = false;
    public int $creditLimit = 0;
    public bool $hasPriceList = false;
    public int $apiDataId = 0;
    public int $countriId = 48; // País por defecto Colombia

    // Estado
    public string $successMessage = '';

    // Datos existentes
    public ?VntCompany $company = null;
    public ?VntContact $contact = null;
    public ?VntWarehouse $warehouse = null;

    #[Layout('layouts.app')]
    public function mount()
    {
        // Si el usuario es Super Administrador, redirigir al dashboard
        if (Auth::user()->isSuperAdmin()) {
            $this->redirect(route('dashboard'));
            return;
        }

        $this->loadSelectData();
        $this->loadExistingData();
        $this->determineCurrentStep();
    }

    #[On('type-identification-changed')]
    public function updateTypeIdentification($typeIdentificationId)
    {
        $this->typeIdentificationId = $typeIdentificationId;

        // Limpiar campos cuando cambie el tipo
        $this->identification = '';
        $this->verification_digit = '';
    }

    #[On('regime-changed')]
    public function updateRegime($regimeId)
    {
        $this->regimeId = $regimeId;
        Log::info('Régimen seleccionado', ['regimeId' => $regimeId]);
    }

    #[On('fiscal-responsibility-changed')]
    public function updateFiscalResponsibility($fiscalResponsibilityId)
    {
        $this->fiscalResponsibilityId = $fiscalResponsibilityId;
        Log::info('Responsabilidad fiscal seleccionada', ['fiscalResponsibilityId' => $fiscalResponsibilityId]);
    }

    #[On('city-changed')]
    public function updateCity($cityId)
    {
        $this->cityId = $cityId;
        Log::info('Ciudad seleccionada', ['cityId' => $cityId]);
    }

    /**
     * Validación en tiempo real para el número de identificación
     */
    public function updatedIdentification()
    {
        // Solo validar si tiene contenido
        if (!empty($this->identification)) {
            $this->validateOnly('identification', [
                'identification' => [
                    'required',
                    'string',
                    'max:15',
                    'unique:vnt_companies,identification' . ($this->company ? ',' . $this->company->id : '')
                ]
            ], [
                'identification.unique' => 'Este número de identificación (NIT) ya está registrado en el sistema.',
                'identification.max' => 'El número de identificación no puede tener más de 15 caracteres.',
            ]);
        }
    }

    protected function loadSelectData()
    {
        // YA NO NECESARIO - Los componentes cargan automáticamente los datos
        // Método mantenido para compatibilidad, pero puede eliminarse
    }

    protected function loadExistingData()
    {
        $validator = app(CompanyDataValidator::class);
        $user = Auth::user();

        $this->company = $validator->getUserCompany($user);
        $this->contact = VntContact::where('email', $user->email)->first();
        $this->warehouse = $this->company ? VntWarehouse::where('companyId', $this->company->id)->first() : null;

        // Cargar datos existentes en los campos
        if ($this->company) {
            // Refrescar el modelo desde la BD para obtener datos actualizados
            $this->company->refresh();

            // Obtener atributos del modelo para usar en logs y cargar datos
            $attributes = $this->company->getAttributes();

            $this->identification = $this->company->identification ?? '';
            $this->verification_digit = $this->company->checkDigit ?? '';
            $this->typePerson = $this->company->typePerson ?? '';
            $this->code_ciiu = $this->company->code_ciiu ?? '';
            $this->typeIdentificationId = $this->company->typeIdentificationId ?? 0;
            $this->regimeId = $this->company->regimeId ?? 0;

            // DEBUG: Usar una nueva llamada a getAttributes() para verificar
            $freshAttributes = $this->company->fresh()->getAttributes();

            Log::info('🔍 DEBUG - Comparando attributes:', [
                'attributes_originales' => $attributes,
                'fresh_attributes' => $freshAttributes,
                'fiscal_en_originales' => $attributes['fiscalResponsabilityId'] ?? 'NO_EXISTE',
                'fiscal_en_fresh' => $freshAttributes['fiscalResponsabilityId'] ?? 'NO_EXISTE',
            ]);

            // Usar fresh() para obtener los datos más recientes
            $this->fiscalResponsibilityId = $freshAttributes['fiscalResponsabilityId'] ?? 0;

            Log::info('🔍 DEBUG - Valor final asignado:', [
                'fiscalResponsibilityId_loaded' => $this->fiscalResponsibilityId,
            ]);

            // Cargar campos de persona
            $this->firstName = $this->company->firstName ?? '';
            $this->lastName = $this->company->lastName ?? '';
            $this->businessName = $this->company->businessName ?? '';
        }

        if ($this->contact) {
            $this->positionId = $this->contact->positionId ?? 0;
            $this->warehouseId = $this->contact->warehouseId ?? 0;
        }

        if ($this->warehouse) {
            $this->postcode = $this->warehouse->postcode ?? '';
            $this->cityId = $this->warehouse->cityId ?? 0;
            $this->termId = $this->warehouse->termId ?? 1;

            // Cargar datos de sucursal
            $this->branchName = $this->warehouse->name ?? '';
            $this->branchType = $this->warehouse->branch_type ?? 'fija';
            $this->address = $this->warehouse->address ?? '';
            $this->city = $this->warehouse->city ?? '';
            $this->billingFormat = $this->warehouse->billingFormat ?? '';
            $this->isCredit = (bool) ($this->warehouse->is_credit ?? false);
            $this->creditLimit = $this->warehouse->creditLimit ?? 0;
            $this->hasPriceList = !($this->warehouse->pric_list ?? false);
            $this->apiDataId = $this->warehouse->integrationDataId ?? 0;
            $this->countriId = $this->warehouse->countri_id ?? 48;

            // Determinar si tiene múltiples sucursales (lógica simple por ahora)
            $this->hasMultipleBranches = $this->branchName !== 'Principal';
        }
    }

    protected function determineCurrentStep()
    {
        // Verificar si los datos básicos de la empresa están completos (Paso 1)
        if (!$this->company ||
            empty($this->identification) ||
            empty($this->typePerson) ||
            empty($this->code_ciiu) ||
            $this->typeIdentificationId == 0 ||
            $this->regimeId == 0 ||
            $this->fiscalResponsibilityId == 0) {
            $this->currentStep = 1;
            return;
        }

        // Verificar si los datos de ubicación están completos (Paso 2)
        if (empty($this->address) ||
            $this->cityId == 0 ||
            empty($this->postcode)) {
            $this->currentStep = 2;
            return;
        }

        // Todos los datos están completos, redirigir al dashboard
        $this->redirect(route('tenant.select'));
    }

    public function nextStep()
    {
        $this->validateCurrentStep();

        if ($this->currentStep < $this->totalSteps) {
            $this->currentStep++;
        }
    }

    public function previousStep()
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    protected function validateCurrentStep()
    {
        switch ($this->currentStep) {
            case 1:
                $this->validateStep1();
                break;
            case 2:
                $this->validateStep2();
                break;
        }
    }

    protected function validateStep1()
    {
        Log::info('🐛 DEBUG validateStep1 - Valores actuales:', [
            'typeIdentificationId' => $this->typeIdentificationId,
            'identification' => $this->identification,
            'verification_digit' => $this->verification_digit,
            'regimeId' => $this->regimeId,
            'fiscalResponsibilityId' => $this->fiscalResponsibilityId,
        ]);

        $rules = [
            'typeIdentificationId' => ['required', 'numeric', 'min:1'],
            'identification' => [
                'required',
                'string',
                'max:15',
                'unique:vnt_companies,identification' . ($this->company ? ',' . $this->company->id : '')
            ],
            // 'typePerson' => ['required', 'string'], // Comentado temporalmente - campo falta en vista
            // 'code_ciiu' => ['required', 'string'], // Comentado temporalmente - campo falta en vista
            'regimeId' => ['required', 'numeric', 'min:1'],
            'fiscalResponsibilityId' => ['required', 'numeric', 'min:1'],
        ];

        Log::info('🐛 DEBUG - Reglas de validación:', $rules);

        // Si es NIT (id=2), también validar el dígito de verificación
        if ($this->typeIdentificationId == 2) {
            $rules['verification_digit'] = ['required', 'string', 'max:1'];
        }

        // Validar campos según tipo de persona - COMENTADO TEMPORALMENTE
        // if ($this->typePerson == 'Natural') {
        //     $rules['firstName'] = ['required', 'string', 'max:100'];
        //     $rules['lastName'] = ['required', 'string', 'max:100'];
        // } elseif ($this->typePerson == 'Juridica') {
        //     $rules['businessName'] = ['required', 'string', 'max:255'];
        // }

        $messages = [
            'identification.unique' => 'Este número de identificación (NIT) ya está registrado en el sistema.',
            'typeIdentificationId.required' => 'Debe seleccionar un tipo de identificación.',
            'typeIdentificationId.min' => 'Debe seleccionar un tipo de identificación válido.',
            'identification.required' => 'El número de identificación es obligatorio.',
            'identification.max' => 'El número de identificación no puede tener más de 15 caracteres.',
            'regimeId.required' => 'Debe seleccionar un régimen.',
            'regimeId.min' => 'Debe seleccionar un régimen válido.',
            'fiscalResponsibilityId.required' => 'Debe seleccionar una responsabilidad fiscal.',
            'fiscalResponsibilityId.min' => 'Debe seleccionar una responsabilidad fiscal válida.',
            'verification_digit.required' => 'El dígito de verificación es obligatorio para NIT.',
            'verification_digit.max' => 'El dígito de verificación debe ser un solo carácter.',
        ];

        $this->validate($rules, $messages);
        $this->saveStep1();
    }

    protected function validateStep2()
    {
        Log::info('🐛 DEBUG validateStep2 - Valores actuales:', [
            'hasMultipleBranches' => $this->hasMultipleBranches,
            'address' => $this->address,
            'cityId' => $this->cityId,
            'postcode' => $this->postcode,
            'branchName' => $this->branchName,
        ]);

        $rules = [
            'hasMultipleBranches' => ['required', 'boolean'],
            'address' => ['required', 'string', 'max:255'],
            'cityId' => ['required', 'numeric', 'min:1'],
            'postcode' => ['required', 'string', 'max:10'],
        ];

        // Si tiene múltiples sucursales, validar nombre de sucursal
        if ($this->hasMultipleBranches) {
            $rules['branchName'] = ['required', 'string', 'max:255'];
        } else {
            // Si no tiene múltiples sucursales, automáticamente asignar "Principal"
            $this->branchName = 'Principal';
        }

        $this->validate($rules);
        $this->saveStep2();
    }

    protected function saveStep1()
    {
        if ($this->company) {
            $updateData = [
                'identification' => $this->identification,
                'typePerson' => $this->typePerson,
                'code_ciiu' => $this->code_ciiu,
                'typeIdentificationId' => $this->typeIdentificationId,
                'regimeId' => $this->regimeId,
                'fiscalResponsabilityId' => $this->fiscalResponsibilityId,
            ];

            // Solo actualizar checkDigit si es NIT
            if ($this->typeIdentificationId == 2) {
                $updateData['checkDigit'] = $this->verification_digit;
            } else {
                $updateData['checkDigit'] = null;
            }

            // Guardar campos según tipo de persona
            if ($this->typePerson == 'Natural') {
                $updateData['firstName'] = $this->firstName;
                $updateData['lastName'] = $this->lastName;
                $updateData['businessName'] = null; // Limpiar razón social
            } elseif ($this->typePerson == 'Juridica') {
                $updateData['businessName'] = $this->businessName;
                $updateData['firstName'] = null; // Limpiar nombre
                $updateData['lastName'] = null; // Limpiar apellido
            }

            Log::info('💾 DEBUG - Datos a guardar:', [
                'updateData' => $updateData,
                'verification_digit_property' => $this->verification_digit,
                'fiscalResponsibilityId_property' => $this->fiscalResponsibilityId,
            ]);

            $this->company->update($updateData);

            Log::info('✅ Datos de empresa actualizados', ['company_id' => $this->company->id]);
        }
    }

    protected function saveStep2()
    {
        if ($this->warehouse) {
            $updateData = [
                'name' => $this->branchName,
                'address' => $this->address,
                'postcode' => $this->postcode,
                'country_id' => $this->countriId,
                'cityId' => $this->cityId,
                'billingFormat' => 16, // Valor por defecto
                'is_credit' => 0, // Valor por defecto
                'termId' => 1, // Valor por defecto
                'creditLimit' => '0', // Valor por defecto
                'status' => 1,
                'main' => 1, // Principal
            ];

            $this->warehouse->update($updateData);

            Log::info('✅ Datos de warehouse/sucursal actualizados', ['warehouse_id' => $this->warehouse->id]);

            // Actualizar contacto con posición de Administrador
            if ($this->contact) {
                $this->contact->update([
                    'positionId' => 2, // Administrador
                    'warehouseId' => $this->warehouse->id
                ]);

                Log::info('✅ Contacto actualizado con posición Administrador y warehouse', [
                    'contact_id' => $this->contact->id,
                    'positionId' => 2,
                    'warehouseId' => $this->warehouse->id
                ]);
            }

            // Configurar base de datos del tenant si aún no está configurada
            $this->setupTenantDatabaseIfNeeded();
        }
    }

    /**
     * Configura la base de datos del tenant si aún no está configurada.
     * Esto se ejecuta cuando el usuario completa todos sus datos de empresa.
     */
    protected function setupTenantDatabaseIfNeeded(): void
    {
        try {
            // Buscar el tenant asociado al usuario actual
            $user = Auth::user();
            $tenant = Tenant::whereHas('users', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->first();

            if (!$tenant) {
                Log::warning('⚠️ No se encontró tenant para el usuario', ['user_id' => $user->id]);
                return;
            }

            // Verificar si la base de datos ya está configurada
            if ($tenant->database_setup) {
                Log::info('ℹ️ Base de datos del tenant ya está configurada', ['tenant_id' => $tenant->id]);
                return;
            }

            Log::info('🏗️ Configurando base de datos del tenant después de completar datos de empresa', [
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'company_id' => $this->company->id
            ]);

            // Configurar la base de datos y ejecutar migraciones
            $tenantManager = app(TenantManager::class);
            $tenantManager->setupTenantDatabase($tenant);

            Log::info('✅ Base de datos del tenant configurada exitosamente', ['tenant_id' => $tenant->id]);

            // Opcional: mostrar mensaje al usuario
            session()->flash('tenant_database_ready', 'Tu base de datos empresarial ha sido configurada exitosamente.');

        } catch (\Exception $e) {
            Log::error('❌ Error configurando base de datos del tenant', [
                'user_id' => $user->id ?? null,
                'company_id' => $this->company->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // No interrumpir el flujo del usuario, solo registrar el error
            // El admin puede revisar logs y reconfigurar manualmente si es necesario
        }
    }

    #[On('finish-company-config')]
    public function finish()
    {
        try {
            Log::info('🚀 Iniciando finish() - validateCurrentStep...');
            $this->validateCurrentStep();
            Log::info('✅ validateCurrentStep completado exitosamente');

            Log::info('🎉 Configuración de empresa completada', ['user_id' => Auth::user()->id]);

            Log::info('💾 Configurando session flash messages...');
            session()->flash('company_setup_completed', true);
            session()->flash('success', '¡Configuración de empresa completada exitosamente!');

            // Emitir evento para mostrar SweetAlert de éxito
            $this->dispatch('show-completion-alert', [
                'title' => '¡Empresa Configurada!',
                'message' => 'Tu empresa ha sido configurada exitosamente. Serás redirigido al panel de control.',
                'redirectTo' => route('tenant.select')
            ]);

            Log::info('📢 Evento show-completion-alert emitido correctamente');

        } catch (\Exception $e) {
            Log::error('❌ Error en finish()', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Emitir evento de error
            $this->dispatch('show-completion-alert', [
                'title' => 'Error',
                'message' => 'Hubo un error al finalizar la configuración: ' . $e->getMessage(),
                'redirectTo' => route('company.setup')
            ]);
        }
    }

    public function getProgressPercentage(): int
    {
        // Calcular progreso basado en los 2 pasos
        $step1Complete = !empty($this->identification) &&
                        !empty($this->typePerson) &&
                        !empty($this->code_ciiu) &&
                        $this->typeIdentificationId > 0 &&
                        $this->regimeId > 0 &&
                        $this->fiscalResponsibilityId  > 0;

        $step2Complete = !empty($this->address) &&
                        $this->cityId > 0 &&
                        !empty($this->postcode);

        if ($step1Complete && $step2Complete) {
            return 100;
        } elseif ($step1Complete) {
            return 50;
        } else {
            return 25;
        }
    }

    public function render()
    {
        return view('livewire.company.update-company');
    }
}