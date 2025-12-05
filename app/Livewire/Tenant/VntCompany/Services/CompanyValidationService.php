<?php

namespace App\Livewire\Tenant\VntCompany\Services;
use App\Services\Tenant\TenantManager;
use App\Models\Auth\Tenant;

class CompanyValidationService
{
    /**
     * Obtener reglas de validación dinámicas
     */
    public function getValidationRules(
        string $typePerson = '', 
        ?int $editingId = null, 
        ?int $typeIdentificationId = null,
        bool $includeWarehouseAndContact = false
    ): array {
        $baseRules = $this->getBaseRules($editingId, $typeIdentificationId);
        
        // Aplicar reglas según tipo de persona
        $rules = match ($typePerson) {
            'Juridica' => $this->getJuridicalPersonRules($baseRules),
            'Natural' => $this->getNaturalPersonRules($baseRules),
            default => $baseRules,
        };
        
        // Si se solicita, incluir reglas de warehouse y contacto
        if ($includeWarehouseAndContact) {
            $rules = $this->addWarehouseAndContactRules($rules);
        }
        
        return $rules;
    }

    /**
     * Obtener mensajes de validación personalizados
     */
    public function getValidationMessages(): array
    {
        return [
            // Campos base
            'identification.required' => 'El número de identificación es obligatorio.',
            'identification.unique' => 'Este número de identificación ya está registrado.',
            'typePerson.required' => 'Debe seleccionar el tipo de persona.',
            'typeIdentificationId.required' => 'Debe seleccionar el tipo de identificación.',
            'typeIdentificationId.exists' => 'El tipo de identificación seleccionado no es válido.',
            'billingEmail.email' => 'El email de facturación debe tener un formato válido.',
            'billingEmail.unique' => 'Este email de facturación ya está registrado.',
            'verification_digit.required' => 'El dígito de verificación es obligatorio para NIT.',
            'verification_digit.max' => 'El dígito de verificación debe ser de 1 carácter.',
            
            // Persona jurídica
            'businessName.required' => 'La razón social es obligatoria para personas jurídicas.',
            'regimeId.required' => 'El régimen es obligatorio para personas jurídicas.',
            'fiscalResponsabilityId.required' => 'La responsabilidad fiscal es obligatoria para personas jurídicas.',
            
            // Persona natural
            'firstName.required' => 'El primer nombre es obligatorio para personas naturales.',
            'lastName.required' => 'El segundo nombre es obligatorio para personas naturales.',
            
            // Warehouse (campos individuales)
            // 'warehouseName.required' => 'El nombre de la sucursal es obligatorio.',
            'warehouseAddress.required' => 'La dirección de la sucursal es obligatoria.',
            'warehousePostcode.max' => 'El código postal no puede tener más de 10 caracteres.',
            
            // Contacto
            'business_phone.max' => 'El teléfono empresarial no puede tener más de 100 caracteres.',
            'personal_phone.max' => 'El teléfono personal no puede tener más de 100 caracteres.',
            'positionId.exists' => 'La posición seleccionada no es válida.',
        ];
    }

    /**
     * Obtener atributos personalizados para validación
     */
    public function getValidationAttributes(): array
    {
        return [
            // Campos base
            'identification' => 'número de identificación',
            'typePerson' => 'tipo de persona',
            'typeIdentificationId' => 'tipo de identificación',
            'billingEmail' => 'email de facturación',
            'checkDigit' => 'dígito de verificación',
            'code_ciiu' => 'código CIIU',
            'status' => 'estado',
            
            // Persona jurídica
            'businessName' => 'razón social',
            'regimeId' => 'régimen',
            'fiscalResponsabilityId' => 'responsabilidad fiscal',
            
            // Persona natural
            'firstName' => 'primer nombre',
            'lastName' => 'apellido',
            'secondName' => 'segundo nombre',
            'secondLastName' => 'segundo apellido',
            
            // Warehouse (campos individuales)
            // 'warehouseName' => 'nombre de sucursal',
            'warehouseAddress' => 'dirección de sucursal',
            'warehousePostcode' => 'código postal',
            'warehouseCityId' => 'ciudad',
            
            // Contacto
            'business_phone' => 'teléfono empresarial',
            'personal_phone' => 'teléfono personal',
            'positionId' => 'posición',
        ];
    }

    /**
     * Obtener reglas base de validación
     */
    private function getBaseRules(?int $editingId = null, ?int $typeIdentificationId = null): array
    {
        $identificationRule = 'required|string|max:15|unique:vnt_companies,identification';
        
        if ($editingId) {
            $identificationRule .= ',' . $editingId;
        }

        // Determinar si typePerson es requerido basado en el tipo de identificación
        $typePersonRule = 'required|string|in:Natural,Juridica';
        
        // Si NO es NIT (typeIdentificationId != 2), typePerson puede ser nullable porque se establece automáticamente
        if ($typeIdentificationId && (int) $typeIdentificationId !== 2) {
            $typePersonRule = 'nullable|string|in:Natural,Juridica';
        }

        // Determinar si verification_digit es requerido (solo para NIT)
        $verificationDigitRule = 'nullable|string|max:1';
        if ($typeIdentificationId && (int) $typeIdentificationId === 2) {
            $verificationDigitRule = 'required|string|max:1';
        }

        // Regla de email único
        $emailRule = 'nullable|email|max:255|unique:vnt_companies,billingEmail';
        if ($editingId) {
            $emailRule .= ',' . $editingId;
        }

        return [
            'identification' => $identificationRule,
            'typePerson' => $typePersonRule,
            'typeIdentificationId' => 'required|integer|exists:central.cnf_type_identifications,id',
            'status' => 'nullable|integer|in:0,1',
            'billingEmail' => $emailRule,
            'checkDigit' => 'nullable|integer|max:99',
            'integrationDataId' => 'nullable|integer',
            'code_ciiu' => 'nullable|string|max:255',
            'verification_digit' => $verificationDigitRule,
            'warehouses' => 'array',
            // 'warehouses.*.name' => 'required|string|max:255',
            'warehouses.*.address' => 'required|string|max:255',
            'warehouses.*.postcode' => 'nullable|string|max:10',
            'warehouses.*.cityId' => 'nullable|integer',
            'warehouses.*.main' => 'boolean',
        ];
    }

    /**
     * Obtener reglas para persona jurídica
     */
    private function getJuridicalPersonRules(array $baseRules): array
    {

        
        return array_merge($baseRules, [
            'businessName' => 'required|string|max:255',
            'regimeId' => 'required|integer',
             'fiscalResponsabilityId' => 'required|integer',
        ]);
    }

    /**
     * Obtener reglas para persona natural
     */
    private function getNaturalPersonRules(array $baseRules): array
    {
        return array_merge($baseRules, [
            'firstName' => 'required|string|max:255',
            'lastName' => 'nullable|string|max:255',
            'secondName' => 'required|string|max:255',
            'secondLastName' => 'nullable|string|max:255',
            'fiscalResponsabilityId' => 'nullable|integer',
            'regimeId' => 'nullable|integer',
        ]);
    }

    /**
     * Agregar reglas de warehouse y contacto
     */
    private function addWarehouseAndContactRules(array $rules): array
    {
        // Eliminar reglas obsoletas de warehouses.* (ya no usamos array)
        $warehouseKeys = ['warehouses', 'warehouses.*.name', 'warehouses.*.address', 'warehouses.*.postcode', 'warehouses.*.cityId', 'warehouses.*.main'];
        foreach ($warehouseKeys as $key) {
            unset($rules[$key]);
        }
        
        // Agregar reglas para campos individuales de warehouse
        return array_merge($rules, [
            // 'warehouseName' => 'required|string|max:255',
            'warehouseAddress' => 'required|string|max:255',
            'warehousePostcode' => 'nullable|string|max:10',
            'warehouseCityId' => 'nullable|integer',
            'business_phone' => 'nullable|string|max:100',
            'personal_phone' => 'nullable|string|max:100',
            'positionId' => 'nullable|integer|exists:cnf_positions,id',
        ]);
    }

    /**
     * Check if identification already exists for the given type
     * 
     * @param int $typeIdentificationId The type of identification
     * @param string $identification The identification number
     * @param int|null $excludeId Company ID to exclude (for edit mode)
     * @return bool True if identification exists, false otherwise
     */
    public function checkIdentificationExists(
        int $typeIdentificationId, 
        string $identification, 
        ?int $excludeId = null
    ): bool {
        $this->ensureTenantConnection();
        $query = \App\Models\Tenant\Customer\VntCompany::where('typeIdentificationId', $typeIdentificationId)
            ->where('identification', $identification);
        // Exclude current record when editing
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
       // dd($query->exists());
        return $query->exists();
    }


     public function checkEmailExists(
        string $email, 
        ?int $excludeId = null
    ): bool {
        $this->ensureTenantConnection();
        
        // Verificar en vnt_companies (billingEmail)
        // $companyQuery = \App\Models\Tenant\Customer\VntCompany::where('billingEmail', $email);
        // if ($excludeId) {
        //     $companyQuery->where('id', '!=', $excludeId);
        // }
        
        // if ($companyQuery->exists()) {
        //     return true;
        // }
        
        // Verificar en vnt_contacts (email)
        $contactQuery = \App\Models\Tenant\Customer\VntContacts::where('email', $email);
        
        return $contactQuery->exists();
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