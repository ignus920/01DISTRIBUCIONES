<?php

namespace App\Livewire\Tenant\Reports;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

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
    public $sortField = 'uv.name';
    public $sortDirection = 'asc';
    public $perPage = 10;

    // Reglas de validación
    protected $rules = [
        'startDate' => 'nullable|date',
        'endDate' => 'nullable|date|after_or_equal:startDate',
    ];

    // Mensajes de validación personalizados
    protected $messages = [
        'endDate.after_or_equal' => 'La fecha final debe ser posterior o igual a la fecha inicial',
        'startDate.date' => 'La fecha inicial debe ser una fecha válida',
        'endDate.date' => 'La fecha final debe ser una fecha válida',
    ];

    /**
     * Inicializar fechas por defecto (sin filtro)
     */
    public function mount()
    {
        $this->startDate = '';
        $this->endDate = '';
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
     * Aplicar filtros
     */
    public function applyFilters()
    {
        $this->validate();
        $this->hasSearched = true;
        $this->resetPage();
    }

    /**
     * Limpiar filtros
     */
    public function clearFilters()
    {
        $this->startDate = '';
        $this->endDate = '';
        $this->hasSearched = false;
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

        // Aplicar ordenamiento
        $query->orderBy($this->sortField, $this->sortDirection);

        return $paginate ? $query->paginate($this->perPage) : $query->get();
    }

    /**
     * Obtener detalle de ventas de un vendedor específico
     */
    protected function getSalesDetail($salesmanId)
    {
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
                DB::raw('SUM(idr.quantity * idr.value) as subtotal'),
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

        $query->groupBy('dd.id', 'sta.id', 'sta.status', 'uv.name', 'vntc.firstName', 'dd.sale_date');
        $query->orderBy('dd.sale_date', 'desc');

        return $query->get();
    }

    /**
     * Generar PDF con detalle de ventas de un vendedor
     */
    public function generatePDF($salesmanId)
    {
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

        $pdf = Pdf::loadView('pdf.sales-detail', [
            'salesDetail' => $salesDetail,
            'vendorName' => $vendorName,
            'dateRange' => $dateRange
        ]);

        $filename = 'detalle_ventas_' . str_replace(' ', '_', $vendorName) . '_' . now()->format('Y-m-d_His') . '.pdf';
        
        return response()->streamDownload(function() use ($pdf) {
            echo $pdf->output();
        }, $filename);
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
}
