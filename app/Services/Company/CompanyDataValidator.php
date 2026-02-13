<?php

namespace App\Services\Company;

use App\Models\Central\VntCompany;
use App\Models\Central\VntContact;
use App\Models\Central\VntWarehouse;
use App\Models\Auth\User;

class CompanyDataValidator
{
    /**
     * Verificar si los datos de la empresa están completos
     */
    public function isCompanyDataComplete(User $user): bool
    {
        // Siempre retornar true para evitar que aparezca el formulario de setup
        return true;

        // CÓDIGO ORIGINAL COMENTADO - Descomentar si se necesita restaurar la validación
        // // Obtener la empresa del usuario
        // $company = $this->getUserCompany($user);

        // if (!$company) {
        //     return false;
        // }

        // // Solo verificar datos básicos de la empresa y warehouse
        // // Ya no validamos contacto porque no lo usamos en el formulario simplificado
        // if (!$this->isBasicCompanyDataComplete($company)) {
        //     return false;
        // }

        // // Verificar datos del warehouse
        // if (!$this->isWarehouseDataComplete($company)) {
        //     return false;
        // }

        // return true;
    }

    /**
     * Obtener la empresa del usuario
     */
    public function getUserCompany(User $user): ?VntCompany
    {
        // NUEVO ESQUEMA: Obtener empresa a través de User → Contact → Warehouse → Company
        // Ya no usamos tenants porque ahora es una base de datos global
        
        // Verificar si el usuario tiene un contact_id asignado
        if (!$user->contact_id) {
            return null;
        }

        // Obtener el contacto del usuario
        $contact = VntContact::find($user->contact_id);
        
        if (!$contact || !$contact->warehouseId) {
            return null;
        }

        // Obtener el warehouse del contacto
        $warehouse = VntWarehouse::find($contact->warehouseId);
        
        if (!$warehouse || !$warehouse->companyId) {
            return null;
        }

        // Obtener y retornar la empresa
        return VntCompany::find($warehouse->companyId);
    }

    /**
     * Verificar datos básicos de la empresa
     */
    public function isBasicCompanyDataComplete(VntCompany $company): bool
    {
        // Solo validar campos que se llenan en nuestro formulario simplificado
        $requiredFields = [
            'identification', // NIT o identificación
            'typeIdentificationId',
            'regimeId',
            'fiscalResponsabilityId',
            'typePerson',
            'code_ciiu'
        ];

        foreach ($requiredFields as $field) {
            if (empty($company->$field)) {
                return false;
            }
        }

        // Validar campos condicionales según tipo de persona
        if ($company->typePerson === 'Natural') {
            if (empty($company->firstName) || empty($company->lastName)) {
                return false;
            }
        } elseif ($company->typePerson === 'Juridica') {
            if (empty($company->businessName)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Verificar datos del contacto principal
     */
    public function isContactDataComplete(User $user): bool
    {
        $contact = VntContact::where('email', $user->email)->first();

        if (!$contact) {
            return false;
        }

        // Solo validar campos que YA EXISTEN en la tabla vnt_contacts
        $requiredFields = [
            'firstName',
            'lastName',
            'email',
            'phone_contact',
            'positionId', // Este campo ya existe
            'warehouseId' // Este campo ya existe
        ];

        foreach ($requiredFields as $field) {
            if (empty($contact->$field)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Verificar datos del warehouse
     */
    public function isWarehouseDataComplete(VntCompany $company): bool
    {
        $warehouse = VntWarehouse::where('companyId', $company->id)->first();

        if (!$warehouse) {
            return false;
        }

        // Solo validar campos que llenamos en nuestro formulario simplificado
        $requiredFields = [
            'name',      // Se llena automáticamente o por usuario
            'address',   // Campo requerido en formulario
            'postcode',  // Campo requerido en formulario
            'cityId'     // Campo requerido en formulario
        ];

        foreach ($requiredFields as $field) {
            if (empty($warehouse->$field)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Obtener porcentaje de completitud
     */
    public function getCompletionPercentage(User $user): array
    {
        $company = $this->getUserCompany($user);

        if (!$company) {
            return [
                'overall' => 0,
                'company' => 0,
                'warehouse' => 0
            ];
        }

        $companyComplete = $this->isBasicCompanyDataComplete($company);
        $warehouseComplete = $this->isWarehouseDataComplete($company);

        $completed = 0;
        if ($companyComplete) $completed++;
        if ($warehouseComplete) $completed++;

        return [
            'overall' => round(($completed / 2) * 100),
            'company' => $companyComplete ? 100 : 0,
            'warehouse' => $warehouseComplete ? 100 : 0
        ];
    }
}