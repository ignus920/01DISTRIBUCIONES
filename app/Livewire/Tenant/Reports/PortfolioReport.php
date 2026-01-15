<?php

namespace App\Livewire\Tenant\Reports;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

class PortfolioReport extends Component
{
    use WithPagination;
    use \App\Traits\Livewire\WithExport;

    // Propiedades para filtros
    public $startDate = '';
    public $endDate = '';
    public $hasSearched = false;

    // Búsqueda en tabla
    public $search = '';

    // Propiedades para la tabla
    public $sortField = 'fecha';
    public $sortDirection = 'desc';
    public $perPage = 10;

    // Configuración de campos para el componente genérico de filtros
    public $filterFields = [];

    /**
     * Inicializar fechas por defecto (sin filtro)
     */
    public function mount()
    {
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
     * Actualizar búsqueda y resetear paginación
     */
    public function updatingSearch()
    {
        $this->resetPage();
    }

    /**
     * Obtener datos de cartera
     */
    protected function getPortfolioData($paginate = true)
    {
        // Si no se ha realizado una búsqueda, retornar colección vacía
        if (!$this->hasSearched) {
            return $paginate ? new \Illuminate\Pagination\LengthAwarePaginator([], 0, $this->perPage) : collect([]);
        }

        // Construir el query completo con filtros y ordenamiento
        $sql = "
            SELECT
                dd.id AS Pedido,
                sta.id AS remission_id,
                vnt_i.consecutive AS consecutivoFactura,
                uv.name AS vendedor,
                vntc.firstName AS cliente,
                dd.sale_date AS fecha,
                SUM(idr.quantity * idr.value) AS subtotal,
                vnt_i.status_payment
            FROM inv_detail_remissions idr
            INNER JOIN inv_items ii ON ii.id = idr.itemId
            INNER JOIN inv_remissions sta ON sta.id = idr.remissionId
            INNER JOIN vnt_quotes vq ON vq.id = sta.quoteId
            INNER JOIN vnt_invoices vnt_i ON vnt_i.quoteId = vq.id AND vnt_i.remission = sta.id
            INNER JOIN vnt_companies vntc ON vntc.id = vq.customerId
            INNER JOIN dis_deliveries dd ON dd.id = sta.delivery_id
            INNER JOIN users uv ON uv.id = dd.salesman_id
            WHERE sta.delivery_id IS NOT NULL
        ";

        // Agregar filtros
        $bindings = [];
        
        if ($this->startDate) {
            $sql .= " AND dd.sale_date >= ?";
            $bindings[] = $this->startDate;
        }

        if ($this->endDate) {
            $sql .= " AND dd.sale_date <= ?";
            $bindings[] = $this->endDate;
        }

        $sql .= "
            GROUP BY
                dd.id,
                sta.id,
                vnt_i.consecutive,
                uv.name,
                vntc.firstName,
                dd.sale_date,
                vnt_i.status_payment
        ";

        // Aplicar búsqueda en los resultados agrupados
        if ($this->search) {
            $sql = "SELECT * FROM ({$sql}) as grouped_results WHERE 
                CAST(Pedido AS CHAR) LIKE ? OR
                CAST(remission_id AS CHAR) LIKE ? OR
                consecutivoFactura LIKE ? OR
                vendedor LIKE ? OR
                cliente LIKE ? OR
                status_payment LIKE ?";
            
            $searchTerm = '%' . $this->search . '%';
            $bindings = array_merge($bindings, [$searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }

        $sql .= " ORDER BY {$this->sortField} {$this->sortDirection}";

        if ($paginate) {
            // Para paginación, necesitamos contar primero
            $countSql = "SELECT COUNT(*) as total FROM ({$sql}) as count_query";
            $total = DB::selectOne($countSql, $bindings)->total;

            // Calcular offset
            $currentPage = request()->get('page', 1);
            $offset = ($currentPage - 1) * $this->perPage;

            // Agregar LIMIT y OFFSET
            $sql .= " LIMIT {$this->perPage} OFFSET {$offset}";

            $results = DB::select($sql, $bindings);

            return new \Illuminate\Pagination\LengthAwarePaginator(
                $results,
                $total,
                $this->perPage,
                $currentPage,
                ['path' => request()->url()]
            );
        }

        return collect(DB::select($sql, $bindings));
    }

    public function render()
    {
        $portfolioData = $this->getPortfolioData();

        return view('livewire.tenant.reports.portfolio-report', [
            'portfolioData' => $portfolioData
        ]);
    }

    /**
     * Métodos para Exportación
     */
    protected function getExportData()
    {
        return $this->getPortfolioData(false);
    }

    protected function getExportHeadings(): array
    {
        return [
            'Pedido',
            'Remisión',
            'Consecutivo Factura',
            'Vendedor',
            'Cliente',
            'Fecha',
            'Subtotal',
            'Estado de Pago'
        ];
    }

    protected function getExportMapping()
    {
        return function ($record) {
            return [
                $record->Pedido,
                $record->remission_id,
                $record->consecutivoFactura,
                $record->vendedor,
                $record->cliente,
                \Carbon\Carbon::parse($record->fecha)->format('d/m/Y'),
                number_format($record->subtotal, 2),
                $record->status_payment
            ];
        };
    }

    protected function getExportFilename(): string
    {
        return 'cartera_' . now()->format('Y-m-d_His');
    }
}
