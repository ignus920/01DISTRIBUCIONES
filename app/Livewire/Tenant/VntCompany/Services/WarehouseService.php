<?php

namespace App\Livewire\Tenant\VntCompany\Services;

use App\Models\Tenant\Customer\VntCompany;
use App\Models\Tenant\Customer\VntWarehouse;

class WarehouseService
{
    /**
     * Crear sucursales para una empresa
     */
    public function createWarehouses(VntCompany $company, array $warehouses): void
    {
        foreach ($warehouses as $warehouseData) {
            // if ($this->isValidWarehouseData($warehouseData)) {
                VntWarehouse::create([
                    'companyId' => $company->id,
                    'name' => $warehouseData['name'],
                    'address' => $warehouseData['address'],
                    'postcode' => $warehouseData['postcode'] ?? null,
                    'cityId' => !empty($warehouseData['cityId']) ? $warehouseData['cityId'] : null,
                    'district' => $warehouseData['district'] ?? null,
                    'main' => $warehouseData['main'] ? 1 : 0,
                    'status' => 1,
                ]);
            // }
        }
    }

    /**
     * Actualizar sucursales de una empresa
     */
    public function updateWarehouses(VntCompany $company, array $warehouses): void
    {
        // Eliminar sucursales que ya no están en el array
        $existingIds = collect($warehouses)->pluck('id')->filter();
        $company->warehouses()->whereNotIn('id', $existingIds)->delete();

        foreach ($warehouses as $warehouseData) {
            // if ($this->isValidWarehouseData($warehouseData)) {
                if (isset($warehouseData['id'])) {
                    // Actualizar sucursal existente
                    $this->updateExistingWarehouse($warehouseData);
                } else {
                    // Crear nueva sucursal
                    $this->createNewWarehouse($company, $warehouseData);
                }
            // }
        }
    }

    /**
     * Preparar datos de sucursales para el formulario
     */
    public function prepareWarehousesForForm(VntCompany $company): array
    {
        return $company->warehouses->map(function ($warehouse) {
            return [
                'id' => $warehouse->id,
                'name' => $warehouse->name,
                'address' => $warehouse->address,
                'postcode' => $warehouse->postcode,
                'cityId' => $warehouse->cityId,
                'district' => $warehouse->district,
                'main' => (bool) $warehouse->main,
            ];
        })->toArray();
    }

    /**
     * Crear una nueva sucursal vacía para el formulario
     */
    public function createEmptyWarehouse(int $existingWarehousesCount): array
    {
        return [
            'name' => '',
            'address' => '',
            'postcode' => '',
            'cityId' => '',
            'main' => $existingWarehousesCount === 0, // Primera sucursal es principal por defecto
        ];
    }

    /**
     * Establecer una sucursal como principal
     */
    public function setMainWarehouse(array &$warehouses, int $mainIndex): void
    {
        foreach ($warehouses as $key => $warehouse) {
            $warehouses[$key]['main'] = ($key === $mainIndex);
        }
    }

    /**
     * Remover una sucursal del array
     */
    public function removeWarehouse(array &$warehouses, int $index): void
    {
        unset($warehouses[$index]);
        $warehouses = array_values($warehouses); // Reindexar array
    }

    /**
     * Validar si los datos de la sucursal son válidos
     */
    private function isValidWarehouseData(array $warehouseData): bool
    {
        return !empty($warehouseData['name']) && !empty($warehouseData['address']);
    }

    /**
     * Actualizar una sucursal existente
     */
    private function updateExistingWarehouse(array $warehouseData): void
    {
        $warehouse = VntWarehouse::find($warehouseData['id']);
        
        if (!$warehouse) {
            // Si no existe, no hacer nada (será creada como nueva)
            return;
        }
        
        $warehouse->update([
            'name' => $warehouseData['name'],
            'address' => $warehouseData['address'],
            'postcode' => $warehouseData['postcode'] ?? null,
            'cityId' => !empty($warehouseData['cityId']) ? $warehouseData['cityId'] : null,
            'district' => $warehouseData['district'] ?? null,
            'main' => $warehouseData['main'] ? 1 : 0,
        ]);
    }

    /**
     * Determinar si se pueden agregar más sucursales
     */
    public function canAddMoreWarehouses(
        string $typePerson = '',
        ?int $typeIdentificationId = null,
        int $currentWarehouseCount = 0,
        ?int $editingId = null
    ): bool {
        // Lógica de negocio para determinar permisos
        // Regla 1: Personas naturales solo pueden tener 1 sucursal
        if ($typePerson === 'Natural') {
            return $currentWarehouseCount < 1;
        }
        
        // Regla 2: Personas jurídicas pueden tener múltiples sucursales
        if ($typePerson === 'Juridica') {
            // Límite máximo de sucursales (configurable)
            $maxWarehouses = $this->getMaxWarehousesForCompany($typeIdentificationId, $editingId);
            return $currentWarehouseCount < $maxWarehouses;
        }
        
        // Por defecto, no permitir agregar más si no se ha definido el tipo de persona
        return false;
    }

    /**
     * Obtener el límite máximo de sucursales para una empresa
     */
    public function getMaxWarehousesForCompany(?int $typeIdentificationId = null, ?int $editingId = null): int
    {
        // Lógica de negocio para determinar límites
        
        // Por defecto, personas jurídicas pueden tener hasta 5 sucursales
        $defaultLimit = 5;
        
        // Aquí puedes agregar lógica más compleja basada en:
        // - Tipo de identificación
        // - Plan de suscripción
        // - Configuraciones específicas de la empresa
        // - Etc.
        
        if ($typeIdentificationId) {
            switch ($typeIdentificationId) {
                case 1: // Cédula de ciudadanía - Persona natural
                    return 1;
                case 2: // NIT - Persona jurídica
                    return 10; // Empresas grandes pueden tener más sucursales
                default:
                    return $defaultLimit;
            }
        }
        
        return $defaultLimit;
    }

    /**
     * Obtener información sobre los límites de sucursales
     */
    public function getWarehouseLimitsInfo(string $typePerson = '', ?int $typeIdentificationId = null): array
    {
        $maxWarehouses = $this->getMaxWarehousesForCompany($typeIdentificationId);
        
        return [
            'max_warehouses' => $maxWarehouses,
            'type_person' => $typePerson,
            'can_have_multiple' => $typePerson === 'Juridica',
            'limit_reason' => $this->getLimitReason($typePerson, $typeIdentificationId),
        ];
    }

    /**
     * Obtener la razón del límite de sucursales
     */
    private function getLimitReason(string $typePerson = '', ?int $typeIdentificationId = null): string
    {
        if ($typePerson === 'Natural') {
            return 'Las personas naturales solo pueden tener una sucursal principal.';
        }
        
        if ($typePerson === 'Juridica') {
            $maxWarehouses = $this->getMaxWarehousesForCompany($typeIdentificationId);
            return "Las personas jurídicas pueden tener hasta {$maxWarehouses} sucursales.";
        }
        
        return 'Debe seleccionar el tipo de persona para determinar el límite de sucursales.';
    }

    /**
     * Crear una nueva sucursal
     */
    private function createNewWarehouse(VntCompany $company, array $warehouseData): void
    {
        VntWarehouse::create([
            'companyId' => $company->id,
            'name' => $warehouseData['name'],
            'address' => $warehouseData['address'],
            'postcode' => $warehouseData['postcode'] ?? null,
            'cityId' => !empty($warehouseData['cityId']) ? $warehouseData['cityId'] : null,
            'district' => $warehouseData['district'] ?? null,
            'main' => $warehouseData['main'] ? 1 : 0,
            'status' => 1,
        ]);
    }

    /**
     * Toggle warehouse status
     */
    public function toggleWarehouseStatus(int $warehouseId): void
    {
        $warehouse = VntWarehouse::findOrFail($warehouseId);
        
        // Toggle warehouse status
        $newStatus = $warehouse->status ? 0 : 1;
        $warehouse->update(['status' => $newStatus]);
    }
}