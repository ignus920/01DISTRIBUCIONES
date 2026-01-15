<?php

namespace App\Livewire\Tenant\Reports;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

class ProfitabilityReport extends Component
{
    use WithPagination;
    use \App\Traits\Livewire\WithExport;

    // Propiedades para filtros
    public $startDate = '';
    public $endDate = '';
    public $hasSearched = false;

    // Propiedades para la tabla
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
     * Obtener datos de rentabilidad
     */
    protected function getProfitabilityData($paginate = true)
    {
        // Si no se ha realizado una búsqueda, retornar colección vacía
        if (!$this->hasSearched) {
            return $paginate ? new \Illuminate\Pagination\LengthAwarePaginator([], 0, $this->perPage) : collect([]);
        }

        // Construir el query completo con filtros y ordenamiento
        $sql = "
            WITH movimientos AS (
                SELECT
                    id,
                    itemId,
                    cost,
                    ROW_NUMBER() OVER (PARTITION BY itemId ORDER BY id DESC) AS rn,
                    COUNT(*) OVER (PARTITION BY itemId) AS total_movimientos
                FROM inv_detail_inv_adjustments
            ),
            promedios AS (
                -- Promedio de hasta 4 movimientos
                SELECT
                    itemId,
                    AVG(NULLIF(cost, 0)) AS CostoPromedio
                FROM movimientos
                WHERE rn <= 4
                GROUP BY itemId
            ),
            ultimo AS (
                -- Último movimiento válido según regla
                SELECT
                    itemId,
                    CASE 
                        WHEN total_movimientos >= 2 AND cost <> 0 THEN cost
                        ELSE NULL
                    END AS PrecioUltimo
                FROM movimientos
                WHERE rn = 2
            ),
            detalle AS (
                SELECT
                    dd.id AS pedido,
                    dd.sale_date AS fecha,
                    vntc.firstName AS cliente,
                    uv.id AS vendedor_id,
                    uv.name AS vendedor,
                    sta.id AS remission_id,
                    sta.status AS estado,
                    ii.id AS Codigo,
                    ii.name AS Producto,
                    COALESCE(NULLIF(ivda.quantity, 0), idr.quantity) AS cantidad,
                    COALESCE(p.CostoPromedio, idr.value) AS CostoPromedio,
                    -- Aplicamos la regla de PrecioUltimo
                    COALESCE(u.PrecioUltimo, idr.value) AS PrecioUltimo
                FROM inv_detail_remissions idr
                INNER JOIN inv_items ii ON ii.id = idr.itemId
                INNER JOIN inv_remissions sta ON sta.id = idr.remissionId
                INNER JOIN vnt_quotes vq ON vq.id = sta.quoteId
                INNER JOIN vnt_companies vntc ON vntc.id = vq.customerId
                INNER JOIN dis_deliveries dd ON dd.id = sta.delivery_id
                INNER JOIN users uv ON uv.id = dd.salesman_id
                LEFT JOIN inv_detail_inv_adjustments ivda ON ivda.itemId = idr.itemId
                LEFT JOIN promedios p ON p.itemId = idr.itemId
                LEFT JOIN ultimo u ON u.itemId = idr.itemId
                WHERE sta.delivery_id IS NOT NULL
        ";

        // Agregar filtros de fecha
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
            )
            SELECT
                vendedor_id,
                vendedor,
                SUM(cantidad) AS TotalCantidad,
                SUM(CostoPromedio) AS TotalCostoPromedio,
                SUM(PrecioUltimo) AS TotalPrecioUltimo,
                SUM(PrecioUltimo - CostoPromedio) AS TotalRentabilidad,
                -- Porcentaje de rentabilidad sobre el total de PrecioUltimo con 2 decimales
                ROUND(
                    CASE 
                        WHEN SUM(PrecioUltimo) = 0 THEN 0
                        ELSE (SUM(PrecioUltimo - CostoPromedio) / SUM(PrecioUltimo)) * 100
                    END, 
                    2
                ) AS PorcentajeRentabilidad
            FROM detalle
            GROUP BY vendedor_id, vendedor
            ORDER BY {$this->sortField} {$this->sortDirection}
        ";

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

    /**
     * Obtener detalle de rentabilidad por vendedor
     */
    public function viewDetail($salesmanId)
    {
        // Redirigir a una nueva página con el detalle
        return redirect()->route('tenant.reports.profitability-detail', [
            'salesman_id' => $salesmanId,
            'start_date' => $this->startDate,
            'end_date' => $this->endDate
        ]);
    }

    /**
     * Generar PDF con detalle de rentabilidad de un vendedor
     */
    public function generatePDF($salesmanId)
    {
        $detailData = $this->getVendorDetail($salesmanId);
        
        if ($detailData->isEmpty()) {
            session()->flash('error', 'No hay datos para generar el PDF.');
            return;
        }

        $vendorName = $detailData->first()->vendedor;
        $dateRange = '';
        
        if ($this->startDate && $this->endDate) {
            $dateRange = \Carbon\Carbon::parse($this->startDate)->format('d/m/Y') . ' - ' . \Carbon\Carbon::parse($this->endDate)->format('d/m/Y');
        } elseif ($this->startDate) {
            $dateRange = 'Desde ' . \Carbon\Carbon::parse($this->startDate)->format('d/m/Y');
        } elseif ($this->endDate) {
            $dateRange = 'Hasta ' . \Carbon\Carbon::parse($this->endDate)->format('d/m/Y');
        } else {
            $dateRange = 'Todas las fechas';
        }

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.profitability-detail', [
            'detailData' => $detailData,
            'vendorName' => $vendorName,
            'dateRange' => $dateRange
        ]);

        $filename = 'detalle_rentabilidad_' . str_replace(' ', '_', $vendorName) . '_' . now()->format('Y-m-d_His') . '.pdf';
        
        return response()->streamDownload(function() use ($pdf) {
            echo $pdf->output();
        }, $filename);
    }

    /**
     * Obtener detalle de un vendedor específico
     */
    protected function getVendorDetail($salesmanId)
    {
        $sql = "
            WITH movimientos AS (
                SELECT
                    id,
                    itemId,
                    cost,
                    ROW_NUMBER() OVER (PARTITION BY itemId ORDER BY id DESC) AS rn,
                    COUNT(*) OVER (PARTITION BY itemId) AS total_movimientos
                FROM inv_detail_inv_adjustments
            )
            SELECT
                dd.id AS pedido,
                dd.sale_date AS fecha,
                vntc.firstName AS cliente,
                uv.name AS vendedor,
                sta.id AS remission_id,
                sta.status AS estado,
                ii.id AS Codigo,
                ii.name AS Producto,
                COALESCE(NULLIF(ivda.quantity, 0), idr.quantity) AS Pedido,
                0 AS Devolucion,
                COALESCE(NULLIF(ivda.quantity, 0), idr.quantity) AS Entrega,
                COALESCE(AVG(NULLIF(um.cost, 0)), idr.value) AS CostoPromedio,
                COALESCE(
                    CASE 
                        WHEN lm.total_movimientos >= 2 AND lm.cost <> 0 THEN lm.cost
                        ELSE idr.value
                    END,
                    idr.value
                ) AS PrecioUltimo,
                COALESCE(
                    COALESCE(
                        CASE 
                            WHEN lm.total_movimientos >= 2 AND lm.cost <> 0 THEN lm.cost
                            ELSE idr.value
                        END,
                        idr.value
                    ) - COALESCE(AVG(NULLIF(um.cost, 0)), idr.value), 
                    0
                ) AS Rentabilidad
            FROM inv_detail_remissions idr
            INNER JOIN inv_items ii ON ii.id = idr.itemId
            INNER JOIN inv_remissions sta ON sta.id = idr.remissionId
            INNER JOIN vnt_quotes vq ON vq.id = sta.quoteId
            INNER JOIN vnt_companies vntc ON vntc.id = vq.customerId
            INNER JOIN dis_deliveries dd ON dd.id = sta.delivery_id
            INNER JOIN users uv ON uv.id = dd.salesman_id
            LEFT JOIN movimientos um ON um.itemId = idr.itemId AND um.rn <= 4
            LEFT JOIN movimientos lm ON lm.itemId = idr.itemId AND lm.rn = 2
            LEFT JOIN inv_detail_inv_adjustments ivda ON ivda.itemId = idr.itemId
            WHERE sta.delivery_id IS NOT NULL
            AND dd.salesman_id = ?
        ";

        $bindings = [$salesmanId];

        // Agregar filtros de fecha
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
                dd.sale_date,
                vntc.firstName,
                uv.name,
                sta.id,
                sta.status,
                ii.id,
                ii.name,
                ivda.quantity,
                idr.quantity,
                idr.value,
                lm.cost,
                lm.total_movimientos
            ORDER BY dd.sale_date DESC
        ";

        return collect(DB::select($sql, $bindings));
    }

    public function render()
    {
        $profitabilityData = $this->getProfitabilityData();

        return view('livewire.tenant.reports.profitability-report', [
            'profitabilityData' => $profitabilityData
        ]);
    }

    /**
     * Métodos para Exportación
     */
    protected function getExportData()
    {
        return $this->getProfitabilityData(false);
    }

    protected function getExportHeadings(): array
    {
        return [
            'Vendedor',
            'Total Cantidad',
            'Total Costo Promedio',
            'Total Precio Último',
            'Total Rentabilidad',
            'Porcentaje Rentabilidad (%)'
        ];
    }

    protected function getExportMapping()
    {
        return function ($record) {
            return [
                $record->vendedor,
                number_format($record->TotalCantidad, 0),
                number_format($record->TotalCostoPromedio, 2),
                number_format($record->TotalPrecioUltimo, 2),
                number_format($record->TotalRentabilidad, 2),
                number_format($record->PorcentajeRentabilidad, 2)
            ];
        };
    }

    protected function getExportFilename(): string
    {
        return 'rentabilidad_' . now()->format('Y-m-d_His');
    }
}
