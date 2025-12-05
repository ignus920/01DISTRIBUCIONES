<?php

namespace App\Livewire\Tenant\VntCompany\Services;

use App\Models\Tenant\Customer\VntCompany;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use App\Services\Tenant\TenantManager;
use App\Models\Auth\Tenant;


class CompanyQueryService
{
    /**
     * Obtener empresas paginadas con filtros y ordenamiento
     */
    public function getPaginatedCompanies(
        string $search = '',
        int $perPage = 10,
        string $sortField = 'id',
        string $sortDirection = 'desc'
    ): LengthAwarePaginator {

        $this->ensureTenantConnection();
        return VntCompany::query()
            ->with([
                'mainWarehouse:id,companyId,name,address,postcode',
                'mainWarehouse.contacts' => function($query) {
                    $query->select('id', 'warehouseId', 'business_phone', 'personal_phone')
                          ->limit(1);
                }
            ])
            ->when($search, function (Builder $query) use ($search) {
                $this->applySearchFilters($query, $search);
            })
            ->orderBy($sortField, $sortDirection)
            ->paginate($perPage);
    }

    /**
     * Buscar empresas por término de búsqueda
     */
    public function searchCompanies(string $search): Builder
    {
        $this->ensureTenantConnection();
        $query = VntCompany::query();
        $this->applySearchFilters($query, $search);
        return $query;
    }

    /**
     * Obtener empresas activas
     */
    public function getActiveCompanies(): Builder
    {
          $this->ensureTenantConnection();
        return VntCompany::where('status', 1);
    }

    /**
     * Obtener empresas por tipo de persona
     */
    public function getCompaniesByPersonType(string $personType): Builder
    {
        $this->ensureTenantConnection();
        return VntCompany::where('typePerson', $personType);
    }

    /**
     * Obtener empresas por tipo de identificación
     */
    public function getCompaniesByIdentificationType(int $typeIdentificationId): Builder
    {
        $this->ensureTenantConnection();
        return VntCompany::where('typeIdentificationId', $typeIdentificationId);
    }

    /**
     * Obtener estadísticas básicas de empresas
     */
    public function getCompanyStats(): array
    {
        $this->ensureTenantConnection();
        $total = VntCompany::count();
        $active = VntCompany::where('status', 1)->count();
        $inactive = VntCompany::where('status', 0)->count();
        $natural = VntCompany::where('typePerson', 'Natural')->count();
        $juridica = VntCompany::where('typePerson', 'Juridica')->count();

        return [
            'total' => $total,
            'active' => $active,
            'inactive' => $inactive,
            'natural_persons' => $natural,
            'juridical_persons' => $juridica,
            'with_warehouses' => VntCompany::has('warehouses')->count(),
        ];
    }

    /**
     * Verificar si una identificación ya existe
     */
    public function identificationExists(string $identification, ?int $excludeId = null): bool
    {
          $this->ensureTenantConnection();
        $query = VntCompany::where('identification', $identification);
        
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }
        
        return $query->exists();
    }

    /**
     * Aplicar filtros de búsqueda
     */
    private function applySearchFilters(Builder $query, string $search): void
    {
          $this->ensureTenantConnection();
        $query->where(function (Builder $subQuery) use ($search) {
            $subQuery->where('businessName', 'like', '%' . $search . '%')
                ->orWhere('identification', 'like', '%' . $search . '%')
                ->orWhere('firstName', 'like', '%' . $search . '%')
                ->orWhere('lastName', 'like', '%' . $search . '%')
                ->orWhere('billingEmail', 'like', '%' . $search . '%')
                // Búsqueda en warehouse
                ->orWhereHas('mainWarehouse', function($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                      ->orWhere('address', 'like', '%' . $search . '%');
                })
                // Búsqueda en contacts
                ->orWhereHas('mainWarehouse.contacts', function($q) use ($search) {
                    $q->where('business_phone', 'like', '%' . $search . '%')
                      ->orWhere('personal_phone', 'like', '%' . $search . '%');
                });
        });
    }

    /**
     * Obtener campos disponibles para ordenamiento
     */
    public function getSortableFields(): array
    {
        return [
            'id' => 'ID',
            'businessName' => 'Razón Social',
            'identification' => 'Identificación',
            'firstName' => 'Primer Nombre',
            'lastName' => 'Apellido',
            'typePerson' => 'Tipo de Persona',
            'status' => 'Estado',
            'created_at' => 'Fecha de Creación',
            'updated_at' => 'Última Actualización',
        ];
    }

    /**
     * Validar campo de ordenamiento
     */
    public function isValidSortField(string $field): bool
    {
        return array_key_exists($field, $this->getSortableFields());
    }

    /**
     * Validar dirección de ordenamiento
     */
    public function isValidSortDirection(string $direction): bool
    {
        return in_array(strtolower($direction), ['asc', 'desc']);
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