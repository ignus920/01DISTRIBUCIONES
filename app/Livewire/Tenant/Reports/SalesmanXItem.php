<?php

namespace App\Livewire\Tenant\Reports;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use App\Models\Tenant\Quoter\VntDetailQuote;
use Livewire\Attributes\Computed;

class SalesmanXItem extends Component
{
    use WithPagination;
    use \App\Traits\Livewire\WithExport;

    // Propiedades para filtros
    public $startDate = '';
    public $endDate = '';
    public $hasSearched = false; // Indica si se ha realizado una búsqueda
    public $showDetail = false;

    // Propiedades para la tabla
    public $search = '';
    public $sortField = 'inv_items.name';
    public $sortDirection = 'asc';
    public $perPage = 7;

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

    public function getReporteVendedorXItem($paginate = true)
    {
        if (!$this->hasSearched) {
            return $paginate ? new \Illuminate\Pagination\LengthAwarePaginator([], 0, $this->perPage) : collect([]);
        }

        $query = VntDetailQuote::query()
            ->select([
                'users.name as vendedor',
                'inv_categories.name as categoria',
                'inv_item_house.name as casa',
                'inv_items.name as producto',
                DB::raw('SUM(vnt_detail_quotes.quantity) as cantidad')
            ])
            ->join('vnt_quotes', 'vnt_detail_quotes.quoteId', '=', 'vnt_quotes.id')
            ->join('inv_items', 'vnt_detail_quotes.itemId', '=', 'inv_items.id')
            ->join('inv_categories', 'inv_items.categoryId', '=', 'inv_categories.id')
            ->join('inv_item_house', 'inv_items.houseId', '=', 'inv_item_house.id')
            ->join('users', 'vnt_quotes.userId', '=', 'users.id')
            ->where('vnt_quotes.status', 'REMISIÓN')
            ->groupBy('users.id', 'users.name', 'inv_categories.name', 'inv_item_house.name', 'inv_items.name')
            ->orderBy('users.name');

        if ($this->startDate) {
            $query->whereDate('vnt_quotes.created_at', '>=', $this->startDate);
        }

        if ($this->endDate) {
            $query->whereDate('vnt_quotes.created_at', '<=', $this->endDate);
        }

        return $paginate ? $query->paginate($this->perPage) : $query->get();
    }

    public function render()
    {
        $reporteVendedorXItem = $this->getReporteVendedorXItem();
        return view('livewire.tenant.reports.salesman-x-item', [
            'reporteVendedorXItem' => $reporteVendedorXItem
        ]);
    }

    /**
     * Métodos para Exportación
     */
    protected function getExportData()
    {
        $query = VntDetailQuote::query()
            ->select([
                'users.name as vendedor',
                'inv_categories.name as categoria',
                'inv_item_house.name as casa',
                'inv_items.name as producto',
                DB::raw('SUM(vnt_detail_quotes.quantity) as cantidad')
            ])
            ->join('vnt_quotes', 'vnt_detail_quotes.quoteId', '=', 'vnt_quotes.id')
            ->join('inv_items', 'vnt_detail_quotes.itemId', '=', 'inv_items.id')
            ->join('inv_categories', 'inv_items.categoryId', '=', 'inv_categories.id')
            ->join('inv_item_house', 'inv_items.houseId', '=', 'inv_item_house.id')
            ->join('users', 'vnt_quotes.userId', '=', 'users.id')
            ->where('vnt_quotes.status', 'REMISIÓN')
            ->groupBy('users.id', 'users.name', 'inv_categories.name', 'inv_item_house.name', 'inv_items.name')
            ->orderBy('users.name');

        if ($this->startDate) {
            $query->whereDate('vnt_quotes.created_at', '>=', $this->startDate);
        }

        if ($this->endDate) {
            $query->whereDate('vnt_quotes.created_at', '<=', $this->endDate);
        }

        return $query->get();
    }

    protected function getExportHeadings(): array
    {
        return ['Vendedor', 'Categoría', 'Casa', 'Producto', 'Cantidad'];
    }
    protected function getExportMapping()
    {
        return function ($item) {
            return [
                $item->vendedor,
                $item->categoria,
                $item->casa,
                $item->producto,
                $item->cantidad,
            ];
        };
    }
    protected function getExportFilename(): string
    {
        return 'vendedor_x_producto_' . now()->format('Y-m-d_His');
    }
}
