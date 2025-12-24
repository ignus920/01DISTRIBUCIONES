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
        bool $includeWarehouseAndContact = false,
        bool $reusable = false
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
            $rules = $this->addWarehouseAndContactRules($rules, $reusable);
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
            'identification.min' => 'El número de identificación debe tener al menos 5 caracteres.',
            'identification.max' => 'El número de identificación no puede tener más de 15 caracteres.',
            'identification.regex' => 'El número de identificación solo puede contener números.',
            'identification.unique' => 'Este número de identificación ya está registrado.',
            'typePerson.required' => 'Debe seleccionar el tipo de persona.',
            'typeIdentificationId.required' => 'Debe seleccionar el tipo de identificación.',
            'typeIdentificationId.exists' => 'El tipo de identificación seleccionado no es válido.',
            'billingEmail.email' => 'El email de facturación debe tener un formato válido.',
            'billingEmail.unique' => 'Este email de facturación ya está registrado.',
            'verification_digit.required' => 'El dígito de verificación es obligatorio para NIT.',
            'verification_digit.max' => 'El dígito de verificación debe ser de 1 carácter.',
            'verification_digit.regex' => 'El dígito de verificación debe ser un número del 0 al 9.',
            'code_ciiu.regex' => 'El código CIIU solo puede contener números.',
            'code_ciiu.max' => 'El código CIIU no puede tener más de 10 caracteres.',
            
            // Persona jurídica
            'businessName.required' => 'La razón social es obligatoria para personas jurídicas.',
            'businessName.min' => 'La razón social debe tener al menos 3 caracteres.',
            'regimeId.required' => 'El régimen es obligatorio para personas jurídicas.',
            'fiscalResponsabilityId.required' => 'La responsabilidad fiscal es obligatoria para personas jurídicas.',
            
            // Persona natural
            'firstName.required' => 'El primer nombre es obligatorio para personas naturales.',
            'firstName.min' => 'El primer nombre debe tener al menos 2 caracteres.',
            'firstName.regex' => 'El primer nombre solo puede contener letras y espacios.',
            'lastName.min' => 'El segundo nombre debe tener al menos 2 caracteres.',
            'lastName.regex' => 'El segundo nombre solo puede contener letras y espacios.',
            'secondName.required' => 'El primer apellido es obligatorio para personas naturales.',
            'secondName.min' => 'El primer apellido debe tener al menos 2 caracteres.',
            'secondName.regex' => 'El primer apellido solo puede contener letras y espacios.',
            'secondLastName.min' => 'El segundo apellido debe tener al menos 2 caracteres.',
            'secondLastName.regex' => 'El segundo apellido solo puede contener letras y espacios.',
            
            // Warehouse (campos individuales)
            // 'warehouseName.required' => 'El nombre de la sucursal es obligatorio.',
            'warehouseAddress.required' => 'La dirección de la sucursal es obligatoria.',
            'warehouseAddress.min' => 'La dirección debe tener al menos 5 caracteres.',
            'warehousePostcode.min' => 'El código postal debe tener al menos 5 caracteres.',
            'warehousePostcode.max' => 'El código postal no puede tener más de 10 caracteres.',
            'warehousePostcode.regex' => 'El código postal solo puede contener números.',
            'warehouseCityId.required' => 'Debe seleccionar una ciudad para la sucursal.',
            
            // Contacto
            'business_phone.min' => 'El teléfono empresarial debe tener al menos 7 caracteres.',
            'business_phone.max' => 'El teléfono empresarial no puede tener más de 100 caracteres.',
            'business_phone.regex' => 'El teléfono empresarial no tiene un formato válido. Ej: +57 300 123 4567',
            'personal_phone.min' => 'El teléfono personal debe tener al menos 7 caracteres.',
            'personal_phone.max' => 'El teléfono personal no puede tener más de 100 caracteres.',
            'personal_phone.regex' => 'El teléfono personal no tiene un formato válido. Ej: +57 310 987 6543',
            'positionId.exists' => 'La posición seleccionada no es válida.',
            
            // Vendedor (campo no existe en la tabla)
            // 'vntUserId.integer' => 'El vendedor seleccionado no es válido.',
            // 'vntUserId.exists' => 'El vendedor seleccionado no existe.',
            // Ruta
            'routeId.integer' => 'La ruta seleccionada no es válida.',
            'routeId.exists' => 'La ruta seleccionada no existe.',
            // Barrio
            'district.required' => 'El barrio es obligatorio.',
            'district.min' => 'El barrio debe tener al menos 3 caracteres.',
            'district.max' => 'El barrio no puede tener más de 100 caracteres.',
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
            
            // Vendedor y Ruta
            // 'vntUserId' => 'vendedor', // Campo no existe en la tabla
            'routeId' => 'ruta',
            'district' => 'barrio',
        ];
    }

    /**
     * Obtener reglas base de validación
     */
    private function getBaseRules(?int $editingId = null, ?int $typeIdentificationId = null): array
    {
        // Regla de identificación con validación de formato
        $identificationRule = 'required|string|min:5|max:15|regex:/^[0-9]+$/|unique:vnt_companies,identification';
        
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
        $verificationDigitRule = 'nullable|string|max:1|regex:/^[0-9]$/';
        if ($typeIdentificationId && (int) $typeIdentificationId === 2) {
            $verificationDigitRule = 'required|string|max:1|regex:/^[0-9]$/';
        }

        // Regla de email único con validación mejorada
        $emailRule = 'nullable|email:rfc,dns|max:255|unique:vnt_companies,billingEmail';
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
            'code_ciiu' => 'nullable|string|max:10|regex:/^[0-9]+$/',
            'verification_digit' => $verificationDigitRule,
            // 'vntUserId' => 'nullable|integer|exists:users,id', // Campo no existe en la tabla
            'routeId' => 'nullable|integer|exists:tat_routes,id',
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
            'businessName' => 'required|string|min:3|max:255',
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
            'firstName' => 'required|string|min:2|max:255|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/',
            'lastName' => 'nullable|string|min:2|max:255|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/',
            'secondName' => 'required|string|min:2|max:255|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/',
            'secondLastName' => 'nullable|string|min:2|max:255|regex:/^[a-zA-ZáéíóúÁÉÍÓÚñÑ\s]+$/',
            'fiscalResponsabilityId' => 'nullable|integer',
            'regimeId' => 'nullable|integer',
        ]);
    }

    /**
     * Agregar reglas de warehouse y contacto
     */
    private function addWarehouseAndContactRules(array $rules, bool $reusable = false): array
    {
        // Eliminar reglas obsoletas de warehouses.* (ya no usamos array)
        $warehouseKeys = ['warehouses', 'warehouses.*.name', 'warehouses.*.address', 'warehouses.*.postcode', 'warehouses.*.cityId', 'warehouses.*.main'];
        foreach ($warehouseKeys as $key) {
            unset($rules[$key]);
        }

        // En modo reusable (para cotizador), el distrito es opcional
        $districtRule = $reusable ? 'nullable|string|min:3|max:100' : 'required|string|min:3|max:100';

        // Agregar reglas para campos individuales de warehouse
        return array_merge($rules, [
            // 'warehouseName' => 'required|string|max:255',
            'warehouseAddress' => 'required|string|min:5|max:255',
            'warehousePostcode' => 'nullable|string|min:5|max:10|regex:/^[0-9]+$/',
            'warehouseCityId' => 'required|integer',
            'district' => $districtRule,
            'business_phone' => 'nullable|string|min:7|max:100|regex:/^[\+]?[(]?[0-9]{1,4}[)]?[-\s\.]?[(]?[0-9]{1,4}[)]?[-\s\.]?[0-9]{1,9}$/',
            'personal_phone' => 'nullable|string|min:7|max:100|regex:/^[\+]?[(]?[0-9]{1,4}[)]?[-\s\.]?[(]?[0-9]{1,4}[)]?[-\s\.]?[0-9]{1,9}$/',
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