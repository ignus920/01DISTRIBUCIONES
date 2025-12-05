<?php

namespace App\Livewire\Tenant\VntCompany\Services;

use App\Models\Tenant\Customer\VntContacts;
use App\Models\Tenant\Customer\VntCompany;
use App\Models\Tenant\Customer\VntWarehouse;
use App\Models\Tenant\Customer\CnfPosition;

class ContactService
{
    /**
     * Crear contacto básico para una empresa usando los datos existentes
     */
    public function createContactsForCompany(VntCompany $company, array $additionalData = []): void
    {
        // Obtener el almacén principal de la empresa
        $mainWarehouse = $company->mainWarehouse;
        
        if (!$mainWarehouse) {
            // Si no hay almacén principal, usar el primer almacén
            $mainWarehouse = $company->warehouses()->first();
        }

        if (!$mainWarehouse) {
            throw new \Exception('No se puede crear contacto: la empresa no tiene almacenes');
        }

        $contactData = [
            'warehouseId' => $mainWarehouse->id,
            'positionId' => $additionalData['positionId'] ?? 1, // Usar positionId del formulario o 1 por defecto
            'status' => 1,
            'email' => $company->billingEmail,
            'business_phone' => $additionalData['business_phone'] ?? null,
            'personal_phone' => $additionalData['personal_phone'] ?? null,
        ];

        // Lógica según el tipo de persona
        if ($company->typePerson === 'LEGAL_ENTITY') {
            // Persona jurídica - solo firstName con businessName
            $contactData['firstName'] = $company->businessName;
        } else {
            // Persona natural - usar nombres individuales
            $contactData['firstName'] = $company->firstName;
            $contactData['secondName'] = $company->secondName;
            $contactData['lastName'] = $company->lastName;
            $contactData['secondLastName'] = $company->secondLastName;
        }

        VntContacts::create($contactData);
    }

    /**
     * Actualizar contacto básico de una empresa
     * Actualiza el contacto existente con los nuevos datos de la empresa
     */
    public function updateContactForCompany(VntCompany $company, array $additionalData = [], ?int $contactId = null): void
    {
        // Buscar contacto específico por ID si se proporciona
        if ($contactId) {
            $contact = VntContacts::find($contactId);
        } else {
            // Fallback: buscar el primer contacto de la empresa
            $contact = $company->contacts()->first();
        }
        
        if (!$contact) {
            // Si no existe contacto, crear uno nuevo
            $this->createContactsForCompany($company, $additionalData);
            return;
        }

        // Obtener el warehouse actual (puede haber cambiado)
        $mainWarehouse = $company->mainWarehouse;
        if (!$mainWarehouse) {
            $mainWarehouse = $company->warehouses()->first();
        }

        $contactData = [
            'warehouseId' => $mainWarehouse->id, // Actualizar warehouseId
            'email' => $company->billingEmail,
            'status' => $company->status,
            'business_phone' => $additionalData['business_phone'] ?? $contact->business_phone,
            'personal_phone' => $additionalData['personal_phone'] ?? $contact->personal_phone,
            'positionId' => $additionalData['positionId'] ?? $contact->positionId,
        ];

        // Actualizar nombres según el tipo de persona
        if ($company->typePerson === 'LEGAL_ENTITY') {
            // Persona jurídica - solo firstName con businessName
            $contactData['firstName'] = $company->businessName;
            $contactData['secondName'] = null;
            $contactData['lastName'] = null;
            $contactData['secondLastName'] = null;
        } else {
            // Persona natural - usar nombres individuales
            $contactData['firstName'] = $company->firstName;
            $contactData['secondName'] = $company->secondName;
            $contactData['lastName'] = $company->lastName;
            $contactData['secondLastName'] = $company->secondLastName;
        }

        $contact->update($contactData);
    }

    /**
     * Obtener contactos de una empresa con sus relaciones
     */
    public function getCompanyContacts(VntCompany $company): \Illuminate\Database\Eloquent\Collection
    {
        return $company->contacts()->with(['warehouse', 'position'])->get();
    }

    /**
     * Obtener todos los contactos de una empresa con sus relaciones
     * Filtra por sucursales que pertenecen a la empresa
     */
    public function getContactsByCompany(int $companyId): \Illuminate\Database\Eloquent\Collection
    {
        return VntContacts::with(['warehouse', 'position'])
            ->whereHas('warehouse', function($query) use ($companyId) {
                $query->where('companyId', $companyId);
            })
            ->get();
    }

    /**
     * Crear un nuevo contacto
     */
    public function createContact(array $data): VntContacts
    {
        // Validar que el warehouse pertenece a la empresa si se proporciona companyId
        if (isset($data['companyId']) && isset($data['warehouseId'])) {
            if (!$this->validateWarehouseBelongsToCompany($data['warehouseId'], $data['companyId'])) {
                throw new \Exception('La sucursal seleccionada no pertenece a la empresa');
            }
        }

        // Preparar datos del contacto con valores por defecto
        $contactData = [
            'firstName' => $data['firstName'],
            'secondName' => $data['secondName'] ?? null,
            'lastName' => $data['lastName'],
            'secondLastName' => $data['secondLastName'] ?? null,
            'email' => $data['email'] ?? null,
            'business_phone' => $data['business_phone'] ?? null,
            'personal_phone' => $data['personal_phone'] ?? null,
            'warehouseId' => $data['warehouseId'],
            'positionId' => $data['positionId'],
            'status' => $data['status'] ?? 1,
        ];

        return VntContacts::create($contactData);
    }

    /**
     * Actualizar un contacto existente
     */
    public function updateContact(int $contactId, array $data): VntContacts
    {
        $contact = VntContacts::findOrFail($contactId);

        // Validar que el warehouse pertenece a la empresa si se proporciona companyId
        if (isset($data['companyId']) && isset($data['warehouseId'])) {
            if (!$this->validateWarehouseBelongsToCompany($data['warehouseId'], $data['companyId'])) {
                throw new \Exception('La sucursal seleccionada no pertenece a la empresa');
            }
        }

        // Preparar datos de actualización
        $updateData = [
            'firstName' => $data['firstName'],
            'secondName' => $data['secondName'] ?? null,
            'lastName' => $data['lastName'],
            'secondLastName' => $data['secondLastName'] ?? null,
            'email' => $data['email'] ?? null,
            'business_phone' => $data['business_phone'] ?? null,
            'personal_phone' => $data['personal_phone'] ?? null,
            'warehouseId' => $data['warehouseId'],
            'positionId' => $data['positionId'],
        ];

        // Solo actualizar status si se proporciona explícitamente
        if (isset($data['status'])) {
            $updateData['status'] = $data['status'];
        }

        $contact->update($updateData);

        return $contact->fresh(['warehouse', 'position']);
    }

    /**
     * Eliminar un contacto (soft delete)
     */
    public function deleteContact(int $contactId): bool
    {
        $contact = VntContacts::findOrFail($contactId);
        return $contact->delete();
    }

    /**
     * Cambiar el estado de un contacto (activo/inactivo)
     */
    public function toggleContactStatus(int $contactId): VntContacts
    {
        $contact = VntContacts::findOrFail($contactId);
        
        // Toggle status: 1 -> 0, 0 -> 1
        $contact->status = $contact->status === 1 ? 0 : 1;
        $contact->save();

        return $contact->fresh(['warehouse', 'position']);
    }

    /**
     * Validar que el warehouse pertenece a la empresa
     */
    public function validateWarehouseBelongsToCompany(int $warehouseId, int $companyId): bool
    {
        return VntWarehouse::where('id', $warehouseId)
            ->where('companyId', $companyId)
            ->exists();
    }

    /**
     * Obtener warehouses de una empresa
     */
    public function getCompanyWarehouses(int $companyId): \Illuminate\Database\Eloquent\Collection
    {
        return VntWarehouse::where('companyId', $companyId)
            ->where('status', 1)
            ->orderBy('name')
            ->get();
    }

    /**
     * Obtener todas las posiciones disponibles
     */
    public function getAvailablePositions(): \Illuminate\Database\Eloquent\Collection
    {
        return CnfPosition::where('status', 1)
            ->orderBy('name')
            ->get();
    }
}