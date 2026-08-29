<?php

namespace App\Livewire\Tenant\Reports;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Auth\Tenant;
use App\Services\Tenant\TenantManager;

class SalesReport extends Component
{
    use WithPagination;
    use \App\Traits\Livewire\WithExport;

    // Propiedades para filtros
    public $startDate = '';
    public $endDate = '';
    public $hasSearched = false; // Indica si se ha realizado una búsqueda

    // Propiedades para la tabla
    public $search = '';
    public $sortField = 'vendedor';
    public $sortDirection = 'asc';
    public $perPage = 10;

    // Configuración de campos para el componente genérico de filtros
    public $filterFields = [];

    /**
     * Inicializar fechas por defecto (sin filtro)
     */
    public function mount()
    {
        $this->ensureTenantConnection();
        $this->startDate = '';
        $this->endDate = '';
        
        // Configurar campos del filtro genérico
        $this->filterFields = [
            [
                'name' => 'startDate',
                'label' => 'Fecha Inicial',
                'type' => 'date',
                'default' => null,
                'required' => false,
            ],
            [
                'name' => 'endDate',
                'label' => 'Fecha Final',
                'type' => 'date',
                'default' => null,
                'required' => false,
            ],
        ];
    }

    /**
     * Listener para cuando se aplican filtros desde el componente genérico
     */
    protected $listeners = ['filtersApplied', 'filtersCleared'];

    /**
     * Manejar evento de filtros aplicados
     */
    public function filtersApplied($filters)
    {
        $this->ensureTenantConnection();
        $this->startDate = $filters['startDate'] ?? '';
        $this->endDate = $filters['endDate'] ?? '';
        $this->hasSearched = true;
        $this->resetPage();
    }

    /**
     * Manejar evento de filtros limpiados
     */
    public function filtersCleared()
    {
        $this->ensureTenantConnection();
        $this->startDate = '';
        $this->endDate = '';
        $this->hasSearched = false;
        $this->resetPage();
    }

    /**
     * Ordenar la tabla por un campo específico
     */
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

    /**
     * Obtener resumen de ventas por vendedor
     */
    protected function getSalesSummary($paginate = true)
    {
        // Si no se ha realizado una búsqueda, retornar colección vacía
        if (!$this->hasSearched) {
            return $paginate ? new \Illuminate\Pagination\LengthAwarePaginator([], 0, $this->perPage) : collect([]);
        }

        $query = DB::table('inv_detail_remissions as idr')
            ->join('inv_remissions as sta', 'sta.id', '=', 'idr.remissionId')
            ->join('dis_deliveries as dd', 'dd.id', '=', 'sta.delivery_id')
            ->join('users as uv', 'uv.id', '=', 'dd.salesman_id')
            ->select(
                'uv.id as vendedor_id',
                'uv.name as vendedor',
                DB::raw("SUM(CASE WHEN sta.status = 'DEVOLUCION' THEN -(idr.quantity * idr.value) ELSE (idr.quantity * idr.value) END) as total")
            )
            ->whereNotNull('sta.delivery_id');

        // Aplicar filtro de fechas
        if ($this->startDate) {
            $query->whereDate('dd.sale_date', '>=', $this->startDate);
        }

        if ($this->endDate) {
            $query->whereDate('dd.sale_date', '<=', $this->endDate);
        }

        $query->groupBy('uv.id', 'uv.name');

        // Consulta para las ventas realizadas por el sistema (al 4%)
        $unionQuery = DB::table('inv_detail_remissions as idr')
            ->join('inv_remissions as sta', 'sta.id', '=', 'idr.remissionId')
            ->select(
                DB::raw('0 as vendedor_id'),
                DB::raw("'Ventas por Sistema' as vendedor"),
                DB::raw("SUM(CASE WHEN sta.status = 'DEVOLUCION' THEN -(idr.quantity * idr.value) ELSE (idr.quantity * idr.value) END) as total")
            )
            ->where('sta.systemOrder', 1);

        // Aplicar filtro de fechas a las ventas de sistema
        if ($this->startDate) {
            $unionQuery->whereDate('sta.created_at', '>=', $this->startDate);
        }

        if ($this->endDate) {
            $unionQuery->whereDate('sta.created_at', '<=', $this->endDate);
        }

        $unionQuery->groupBy('sta.systemOrder');

        // Unir las consultas
        $query->unionAll($unionQuery);

        // Validar campos de ordenamiento por si quedan rastros viejos de "uv.name" en estado
        $sortField = $this->sortField;
        if ($sortField === 'uv.name') $sortField = 'vendedor';
        if ($sortField === 'uv.id') $sortField = 'vendedor_id';

        // Aplicar ordenamiento
        $query->orderBy($sortField, $this->sortDirection);

        return $paginate ? $query->paginate($this->perPage) : $query->get();
    }

    /**
     * Obtener detalle de ventas de un vendedor específico
     */
    protected function getSalesDetail($salesmanId)
    {
        if ($salesmanId == 0) {
            $query = DB::table('inv_detail_remissions as idr')
                ->join('inv_items as ii', 'ii.id', '=', 'idr.itemId')
                ->join('inv_remissions as sta', 'sta.id', '=', 'idr.remissionId')
                ->leftJoin('vnt_quotes as vq', 'vq.id', '=', 'sta.quoteId')
                ->leftJoin('vnt_companies as vntc', 'vntc.id', '=', 'vq.customerId')
                ->select(
                    DB::raw("'SIS' as pedido"),
                    'sta.id as remission_id',
                    'sta.status as estado',
                    DB::raw("'Ventas por Sistema' as vendedor"),
                    DB::raw("COALESCE(vntc.firstName, 'Sistema') as cliente"),
                    'sta.created_at as fecha',
                    DB::raw("SUM(CASE WHEN sta.status = 'DEVOLUCION' THEN (idr.quantity - idr.cant_return) * idr.value ELSE idr.quantity * idr.value END) as subtotal"),
                    DB::raw("CASE WHEN sta.status = 'DEVOLUCION' THEN 1 ELSE 0 END as devolucion")
                )
                ->where('sta.systemOrder', 1);

            // Aplicar filtro de fechas
            if ($this->startDate) {
                $query->whereDate('sta.created_at', '>=', $this->startDate);
            }

            if ($this->endDate) {
                $query->whereDate('sta.created_at', '<=', $this->endDate);
            }

            $query->groupBy('sta.id', 'sta.status', 'vntc.firstName', 'sta.created_at', DB::raw("CASE WHEN sta.status = 'DEVOLUCION' THEN 1 ELSE 0 END"));
            $query->orderBy('sta.created_at', 'desc');

            return $query->get();
        }

        $query = DB::table('inv_detail_remissions as idr')
            ->join('inv_items as ii', 'ii.id', '=', 'idr.itemId')
            ->join('inv_remissions as sta', 'sta.id', '=', 'idr.remissionId')
            ->join('vnt_quotes as vq', 'vq.id', '=', 'sta.quoteId')
            ->join('vnt_companies as vntc', 'vntc.id', '=', 'vq.customerId')
            ->join('dis_deliveries as dd', 'dd.id', '=', 'sta.delivery_id')
            ->join('users as uv', 'uv.id', '=', 'dd.salesman_id')
            ->select(
                'dd.id as pedido',
                'sta.id as remission_id',
                'sta.status as estado',
                'uv.name as vendedor',
                'vntc.firstName as cliente',
                'dd.sale_date as fecha',
                DB::raw("SUM(CASE WHEN sta.status = 'DEVOLUCION' THEN (idr.quantity - idr.cant_return) * idr.value ELSE idr.quantity * idr.value END) as subtotal"),
                DB::raw("CASE WHEN sta.status = 'DEVOLUCION' THEN 1 ELSE 0 END as devolucion")
            )
            ->whereNotNull('sta.delivery_id')
            ->where('dd.salesman_id', $salesmanId);

        // Aplicar filtro de fechas
        if ($this->startDate) {
            $query->whereDate('dd.sale_date', '>=', $this->startDate);
        }

        if ($this->endDate) {
            $query->whereDate('dd.sale_date', '<=', $this->endDate);
        }

        $query->groupBy('dd.id', 'sta.id', 'sta.status', 'uv.name', 'vntc.firstName', 'dd.sale_date', DB::raw("CASE WHEN sta.status = 'DEVOLUCION' THEN 1 ELSE 0 END"));
        $query->orderBy('dd.sale_date', 'desc');

        return $query->get();
    }

    /**
     * Generar PDF con detalle de ventas de un vendedor
     */
    public function generatePDF($salesmanId)
    {
        try {
            $this->ensureTenantConnection();
            $salesDetail = $this->getSalesDetail($salesmanId);

            if ($salesDetail->isEmpty()) {
                session()->flash('error', 'No hay datos para generar el PDF.');
                return;
            }

            $vendorName = $salesDetail->first()->vendedor;
            $dateRange = '';

            if ($this->startDate && $this->endDate) {
                $dateRange = Carbon::parse($this->startDate)->format('d/m/Y') . ' - ' . Carbon::parse($this->endDate)->format('d/m/Y');
            } elseif ($this->startDate) {
                $dateRange = 'Desde ' . Carbon::parse($this->startDate)->format('d/m/Y');
            } elseif ($this->endDate) {
                $dateRange = 'Hasta ' . Carbon::parse($this->endDate)->format('d/m/Y');
            } else {
                $dateRange = 'Todas las fechas';
            }

            $filename = 'detalle_ventas_' . str_replace(' ', '_', $vendorName) . '_' . now()->format('Y-m-d_His') . '.pdf';

            return Pdf::loadView('pdf.sales-detail', [
                'salesDetail' => $salesDetail,
                'vendorName' => $vendorName,
                'dateRange' => $dateRange
            ])->download($filename);

        } catch (\Throwable $e) {
            session()->flash('error', 'Error al generar PDF: ' . $e->getMessage());
        }
    }

    public function render()
    {
        $salesSummary = $this->getSalesSummary();

        return view('livewire.tenant.reports.sales-report', [
            'salesSummary' => $salesSummary
        ]);
    }

    /**
     * Métodos para Exportación
     */
    protected function getExportData()
    {
        return $this->getSalesSummary(false);
    }

    protected function getExportHeadings(): array
    {
        return [
            'ID Vendedor',
            'Nombre Vendedor',
            'Total Ventas'
        ];
    }

    protected function getExportMapping()
    {
        return function ($record) {
            return [
                $record->vendedor_id,
                $record->vendedor,
                number_format($record->total, 2)
            ];
        };
    }

    protected function getExportFilename(): string
    {
        return 'resumen_ventas_' . now()->format('Y-m-d_His');
    }

    private function ensureTenantConnection(): void
    {
        $tenantId = session('tenant_id');

        if (!$tenantId) {
            return;
        }

        $tenant = Tenant::find($tenantId);

        if (!$tenant) {
            session()->forget('tenant_id');
            return;
        }

        $tenantManager = app(TenantManager::class);
        $tenantManager->setConnection($tenant);
        tenancy()->initialize($tenant);
    }
}
