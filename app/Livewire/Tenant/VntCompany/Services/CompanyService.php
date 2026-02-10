<?php

namespace App\Livewire\Tenant\VntCompany\Services;

use App\Models\Tenant\Customer\VntCompany;
use App\Models\Tenant\VntCustomer\VntCustomer;
use App\Services\Tenant\TenantManager;
use App\Models\Auth\Tenant;
use Illuminate\Support\Facades\Log;

class CompanyService
{
    protected $warehouseService;
    protected $contactService;

    public function __construct(WarehouseService $warehouseService, ContactService $contactService)
    {
        $this->warehouseService = $warehouseService;
        $this->contactService = $contactService;
    }

    /**
     * Crear una nueva empresa
     */
    public function create(array $data, array $warehouses = []): VntCompany
    {
        $this->ensureTenantConnection();

        // VERIFICACIÓN: Si el cliente ya existe por identificación, lo retornamos para evitar errores SQL
        $existing = VntCompany::where('identification', $data['identification'])->first();
        if ($existing) {
            Log::info('ℹ️ CompanyService: Empresa ya existe, omitiendo creación.', ['id' => $existing->id]);
            return $existing;
        }

        // dd($data, $warehouses);
        $companyData = $this->prepareCompanyData($data);
        $company = VntCompany::create($companyData);

        // Crear almacenes
        $this->warehouseService->createWarehouses($company, $warehouses);

        // Preparar datos adicionales para el contacto (teléfonos y posición)
        $contactAdditionalData = [
            'business_phone' => $data['business_phone'] ?? null,
            'personal_phone' => $data['personal_phone'] ?? null,
            //'positionId' => $data['positionId'] ?? 1,
        ];

        // Crear contacto básico automáticamente usando los datos de la empresa
        $this->contactService->createContactsForCompany($company, $contactAdditionalData);

        // Crear registro en vnt_customers (3ra tabla requerida por el usuario)
        $this->createVntCustomer($company, $data);

        return $company;
    }

    /**
     * Crear registro en la tabla vnt_customers
     */
    private function createVntCustomer(VntCompany $company, array $data): void
    {
        $tenantId = session('tenant_id');
        $tenant = Tenant::find($tenantId);
        $ownerCompanyId = $tenant->company_id ?? 0;

        // Verificar si ya existe en vnt_customers
        $exists = VntCustomer::where('identification', $data['identification'])->exists();
        if ($exists) {
            Log::info('ℹ️ CompanyService: El registro en vnt_customers ya existe por identificación.');
            return;
        }

        VntCustomer::create([
            'company_id' => $ownerCompanyId,
            'typePerson' => ($data['typeIdentificationId'] == 2) ? 'Juridica' : 'Natural',
            'typeIdentificationId' => (int) $data['typeIdentificationId'],
            'identification' => $data['identification'],
            'regimeId' => $data['regimeId'] ?? 2,
            'cityId' => $data['cityId'] ?? null,
            'businessName' => $data['businessName'] ?? null,
            'billingEmail' => $data['billingEmail'] ?? null,
            'firstName' => $data['firstName'] ?? ($data['businessName'] ?? null),
            'lastName' => $data['lastName'] ?? null,
            'address' => $data['address'] ?? null,
            'business_phone' => $data['business_phone'] ?? null,
            'status' => 1,
        ]);
    }

    /**
     * Actualizar una empresa existente
     */
    public function update(int $id, array $data, array $warehouses = [], ?int $contactId = null): VntCompany
    {
        $this->ensureTenantConnection();

        Log::info('CompanyService::update - Start', [
            'company_id' => $id,
            'typeIdentificationId' => $data['typeIdentificationId'],
            'typePerson' => $data['typePerson']
        ]);

        $company = VntCompany::findOrFail($id);
        $companyData = $this->prepareCompanyData($data);

        $company->update($companyData);

        // Actualizar almacenes
        if (!empty($warehouses)) {
            $this->warehouseService->updateWarehouses($company, $warehouses);
        }

        // Preparar datos adicionales para el contacto (teléfonos y posición)
        $contactAdditionalData = [
            'business_phone' => $data['business_phone'] ?? null,
            'personal_phone' => $data['personal_phone'] ?? null,
            'positionId' => $data['positionId'] ?? 1,
        ];

        // Actualizar contacto básico con los nuevos datos de la empresa
        $this->contactService->updateContactForCompany($company, $contactAdditionalData, $contactId);

        return $company;
    }

    /**
     * Eliminar una empresa
     */
    public function delete(int $id): bool
    {
        $this->ensureTenantConnection();

        $company = VntCompany::findOrFail($id);
        return $company->delete();
    }

    /**
     * Toggle company status and cascade to warehouses and contacts
     */
    public function toggleCompanyStatus(int $id): void
    {
        $this->ensureTenantConnection();

        $company = VntCompany::with(['warehouses.contacts', 'contacts'])->findOrFail($id);

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

        Log::info('Company status toggled', [
            'company_id' => $id,
            'new_status' => $newStatus,
            'warehouses_updated' => $company->warehouses->count(),
            'contacts_updated' => $company->contacts->count()
        ]);
    }

    /**
     * Obtener una empresa con sus sucursales
     */
    public function getCompanyWithWarehouses(int $id): VntCompany
    {
        $this->ensureTenantConnection();

        return VntCompany::with('warehouses')->findOrFail($id);
    }

    /**
     * Obtener una empresa con todas las relaciones necesarias para edición
     */
    public function getCompanyForEdit(int $id): VntCompany
    {
        $this->ensureTenantConnection();

        return VntCompany::with([
            'mainWarehouse.contacts',
            'warehouses'
        ])->findOrFail($id);
    }

    /**
     * Obtener una empresa con todos sus datos relacionados
     */
    public function getCompanyWithAllRelations(int $id): VntCompany
    {
        $this->ensureTenantConnection();

        return VntCompany::with([
            'warehouses',
            'contacts.warehouse',
            'contacts.position'
        ])->findOrFail($id);
    }

    /**
     * Obtener contactos de una empresa
     */
    public function getCompanyContacts(int $companyId): \Illuminate\Database\Eloquent\Collection
    {
        $this->ensureTenantConnection();

        $company = VntCompany::findOrFail($companyId);
        return $this->contactService->getCompanyContacts($company);
    }

    /**
     * Preparar datos de la empresa aplicando reglas de negocio
     * 
     * Reglas:
     * 1. Si typeIdentificationId != 2: Persona Natural (PERSON_ENTITY en BD)
     * 2. Si typeIdentificationId == 2 Y typePerson == 'Natural': Persona Natural con NIT (LEGAL_ENTITY en BD)
     * 3. Si typeIdentificationId == 2 Y typePerson == 'Juridica': Persona Jurídica (LEGAL_ENTITY en BD)
     */
    private function prepareCompanyData(array $data): array
    {
        // Datos base comunes a todos los tipos
        $preparedData = [
            'typeIdentificationId' => $data['typeIdentificationId'],
            'identification' => $data['identification'],
            'businessName' => $data['businessName'] ?? null,
            'billingEmail' => $data['billingEmail'] ?? null,
            'business_phone' => $data['business_phone'] ?? null,
            'personal_phone' => $data['business_phone'] ?? null,
            'status' => $data['status'] ?? 1,
            'type' => $data['type'] ?? null,
            // 'vntUserId' => $data['vntUserId'] ?? null, // Campo no existe en la tabla
            'routeId' => $data['routeId'] ?? null,
        ];

        $typeIdentificationId = (int) $data['typeIdentificationId'];
        $typePerson = $data['typePerson'];
        $isNIT = $typeIdentificationId === 2;

        // Caso 1: Persona natural sin NIT (CC, CE, etc.)
        // typePerson en DB: PERSON_ENTITY
        if (!$isNIT && $typePerson === 'Natural') {
            $preparedData['typePerson'] = 'PERSON_ENTITY';
            $preparedData['firstName'] = $data['firstName'] ?? null;
            $preparedData['secondName'] = $data['secondName'] ?? null;
            $preparedData['lastName'] = $data['lastName'] ?? null;
            $preparedData['secondLastName'] = $data['secondLastName'] ?? null;
            $preparedData['checkDigit'] = null;
            $preparedData['code_ciiu'] = $data['code_ciiu'] ?? null;
            $preparedData['regimeId'] = 2;
            $preparedData['fiscalResponsabilityId'] = 1;
        }
        // Caso 2: Persona natural con NIT
        // typePerson en DB: LEGAL_ENTITY (por requerimientos tributarios)
        elseif ($isNIT && $typePerson === 'Natural') {
            $preparedData['typePerson'] = 'LEGAL_ENTITY';
            $preparedData['firstName'] = $data['firstName'] ?? null;
            $preparedData['secondName'] = $data['secondName'] ?? null;
            $preparedData['lastName'] = $data['lastName'] ?? null;
            $preparedData['secondLastName'] = $data['secondLastName'] ?? null;
            $preparedData['checkDigit'] = $data['checkDigit'] ?? null;
            $preparedData['code_ciiu'] = $data['code_ciiu'] ?? null;
            $preparedData['regimeId'] = 2;
            $preparedData['fiscalResponsabilityId'] = 1;
        }
        // Caso 3: Persona jurídica (siempre con NIT)
        // typePerson en DB: LEGAL_ENTITY
        else {
            $preparedData['typePerson'] = 'LEGAL_ENTITY';
            $preparedData['businessName'] = $data['businessName'] ?? null;
            $preparedData['firstName'] = $data['businessName'] ?? null;
            $preparedData['checkDigit'] = $data['checkDigit'] ?? null;
            $preparedData['code_ciiu'] = $data['code_ciiu'] ?? null;
            $preparedData['regimeId'] = $data['regimeId'] ?? null;
            $preparedData['fiscalResponsabilityId'] = $data['fiscalResponsabilityId'] ?? null;
        }

        return $preparedData;
    }

    /**
     * Asegurar conexión tenant
     */
    private function ensureTenantConnection(): void
    {
        $tenantId = session('tenant_id') ?: (function_exists('tenant') ? tenant('id') : null);

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
