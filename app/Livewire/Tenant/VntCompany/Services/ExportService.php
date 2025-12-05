<?php

namespace App\Livewire\Tenant\VntCompany\Services;

use App\Models\Tenant\Customer\VntCompany;
use Illuminate\Support\Collection;

class ExportService
{
    protected $queryService;

    public function __construct(CompanyQueryService $queryService)
    {
        $this->queryService = $queryService;
    }

    /**
     * Exportar empresas a Excel
     */
    public function exportToExcel(string $search = ''): array
    {
        // TODO: Implementar exportación real a Excel
        $companies = $this->getCompaniesForExport($search);
        
        return [
            'success' => false,
            'message' => 'Exportación a Excel - En desarrollo',
            'data' => $companies->toArray()
        ];
    }

    /**
     * Exportar empresas a PDF
     */
    public function exportToPdf(string $search = ''): array
    {
        // TODO: Implementar exportación real a PDF
        $companies = $this->getCompaniesForExport($search);
        
        return [
            'success' => false,
            'message' => 'Exportación a PDF - En desarrollo',
            'data' => $companies->toArray()
        ];
    }

    /**
     * Exportar empresas a CSV
     */
    public function exportToCsv(string $search = ''): array
    {
        // TODO: Implementar exportación real a CSV
        $companies = $this->getCompaniesForExport($search);
        
        return [
            'success' => false,
            'message' => 'Exportación a CSV - En desarrollo',
            'data' => $this->prepareCsvData($companies)
        ];
    }

    /**
     * Obtener datos de empresas para exportación
     */
    public function getCompaniesForExport(string $search = ''): Collection
    {
        $query = VntCompany::with(['warehouses']);
        
        if (!empty($search)) {
            $this->queryService->searchCompanies($search);
        }
        
        return $query->get();
    }

    /**
     * Preparar datos para CSV
     */
    public function prepareCsvData(Collection $companies): array
    {
        $csvData = [];
        
        // Headers
        $csvData[] = [
            'ID',
            'Razón Social',
            'Identificación',
            'Tipo Persona',
            'Primer Nombre',
            'Segundo Nombre',
            'Apellido',
            'Segundo Apellido',
            'Email Facturación',
            'Estado',
            'Sucursales',
            'Fecha Creación'
        ];
        
        // Data rows
        foreach ($companies as $company) {
            $csvData[] = [
                $company->id,
                $company->businessName ?? '',
                $company->identification,
                $company->typePerson,
                $company->firstName ?? '',
                $company->secondName ?? '',
                $company->lastName ?? '',
                $company->secondLastName ?? '',
                $company->billingEmail ?? '',
                $company->status ? 'Activo' : 'Inactivo',
                $company->warehouses->count(),
                $company->created_at->format('Y-m-d H:i:s')
            ];
        }
        
        return $csvData;
    }

    /**
     * Preparar datos para Excel
     */
    public function prepareExcelData(Collection $companies): array
    {
        return $companies->map(function ($company) {
            return [
                'id' => $company->id,
                'business_name' => $company->businessName,
                'identification' => $company->identification,
                'type_person' => $company->typePerson,
                'first_name' => $company->firstName,
                'second_name' => $company->secondName,
                'last_name' => $company->lastName,
                'second_last_name' => $company->secondLastName,
                'billing_email' => $company->billingEmail,
                'status' => $company->status ? 'Activo' : 'Inactivo',
                'warehouses_count' => $company->warehouses->count(),
                'created_at' => $company->created_at->format('Y-m-d H:i:s'),
                'warehouses' => $company->warehouses->map(function ($warehouse) {
                    return [
                        'name' => $warehouse->name,
                        'address' => $warehouse->address,
                        'postcode' => $warehouse->postcode,
                        'main' => $warehouse->main ? 'Sí' : 'No'
                    ];
                })
            ];
        })->toArray();
    }

    /**
     * Obtener formatos de exportación disponibles
     */
    public function getAvailableFormats(): array
    {
        return [
            'excel' => [
                'name' => 'Excel',
                'extension' => 'xlsx',
                'mime_type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'available' => false // Cambiar a true cuando se implemente
            ],
            'pdf' => [
                'name' => 'PDF',
                'extension' => 'pdf',
                'mime_type' => 'application/pdf',
                'available' => false // Cambiar a true cuando se implemente
            ],
            'csv' => [
                'name' => 'CSV',
                'extension' => 'csv',
                'mime_type' => 'text/csv',
                'available' => false // Cambiar a true cuando se implemente
            ]
        ];
    }

    /**
     * Validar formato de exportación
     */
    public function isValidFormat(string $format): bool
    {
        return array_key_exists($format, $this->getAvailableFormats());
    }
}