<?php

namespace App\Livewire\Tenant\Reports;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Tenant\Quoter\VntDetailQuote;
use Livewire\Attributes\Computed;

class SalesXItems extends Component
{
    use WithPagination;
    use \App\Traits\Livewire\WithExport;

    // Propiedades para filtros
    public $startDate = '';
    public $endDate = '';
    public $hasSearched = false; // Indica si se ha realizado una búsqueda
    public $showDetail = false;
    public $idItem;

    // Propiedades para la tabla
    public $search = '';
    public $sortField = 'inv_items.name';
    public $sortDirection = 'asc';
    public $perPage = 5;

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

    public function getReporteVentasProductos($paginate = true)
    {
        // Si no se ha realizado una búsqueda, retornar colección vacía
        if (!$this->hasSearched) {
            return $paginate ? new \Illuminate\Pagination\LengthAwarePaginator([], 0, $this->perPage) : collect([]);
        }

        $query = VntDetailQuote::query()
            ->select([
                'inv_items.id as itemId',
                'inv_items.name as producto',
                'inv_item_house.name as casa',
                'inv_categories.name as categoria',
                DB::raw('COUNT(DISTINCT vnt_detail_quotes.quoteId) as pedidos'),
                DB::raw('SUM(vnt_detail_quotes.quantity) as cantidad'),
                DB::raw('SUM(vnt_detail_quotes.price) as total')
            ])
            ->join('vnt_quotes', 'vnt_detail_quotes.quoteId', '=', 'vnt_quotes.id')
            ->join('inv_items', 'vnt_detail_quotes.itemId', '=', 'inv_items.id')
            ->join('inv_categories', 'inv_items.categoryId', '=', 'inv_categories.id')
            ->join('inv_item_house', 'inv_items.houseId', '=', 'inv_item_house.id')
            ->where('vnt_quotes.status', 'REMISIÓN')
            ->whereNull('vnt_quotes.deleted_at')
            ->whereNull('vnt_detail_quotes.deleted_at')
            ->groupBy('inv_items.id', 'inv_items.name', 'inv_item_house.name', 'inv_categories.name')
            ->orderBy('inv_items.name');

        // Aplicar filtro de fechas
        if ($this->startDate) {
            $query->whereDate('vnt_quotes.created_at', '>=', $this->startDate);
        }

        if ($this->endDate) {
            $query->whereDate('vnt_quotes.created_at', '<=', $this->endDate);
        }

        return $paginate ? $query->paginate($this->perPage) : $query->get();
    }

    public function viewDetails($idItem)
    {
        $this->idItem = $idItem;
        $this->showDetail = true;
    }

    public function closeDetails()
    {
        $this->showDetail = false;
        $this->idItem = null;
    }

    #[Computed]
    public function detail()
    {
        if (!$this->idItem) {
            return collect([]);
        }

        $queryDetail = VntDetailQuote::query()
            ->select([
                'inv_items.name as producto',
                'users.name as vendedor',
                DB::raw('COUNT(DISTINCT vnt_detail_quotes.quoteId) as pedidos'),
                DB::raw('SUM(vnt_detail_quotes.quantity) as cantidad'),
            ])
            ->join('vnt_quotes', 'vnt_detail_quotes.quoteId', '=', 'vnt_quotes.id')
            ->join('inv_items', 'vnt_detail_quotes.itemId', '=', 'inv_items.id')
            ->join('users', 'vnt_quotes.userId', '=', 'users.id')
            ->where('vnt_detail_quotes.itemId', $this->idItem)
            ->where('vnt_quotes.status', 'REMISIÓN')
            ->whereNull('vnt_quotes.deleted_at')
            ->whereNull('vnt_detail_quotes.deleted_at')
            ->groupBy('inv_items.name', 'users.name')
            ->orderBy('inv_items.name', 'desc');

        if ($this->startDate) {
            $queryDetail->whereDate('vnt_quotes.created_at', '>=', $this->startDate);
        }

        if ($this->endDate) {
            $queryDetail->whereDate('vnt_quotes.created_at', '<=', $this->endDate);
        }

        return $queryDetail->get();
    }

    public function render()
    {
        $reporteVentasProductos = $this->getReporteVentasProductos();
        return view('livewire.tenant.reports.sales-x-items', [
            'reporteVentasProductos' => $reporteVentasProductos,
        ]);
    }

    protected function getExportData()
    {
        $query = VntDetailQuote::query()
            ->select([
                'inv_items.id as itemId',
                'inv_items.name as producto',
                'inv_item_house.name as casa',
                'inv_categories.name as categoria',
                DB::raw('COUNT(DISTINCT vnt_detail_quotes.quoteId) as pedidos'),
                DB::raw('SUM(vnt_detail_quotes.quantity) as cantidad'),
                DB::raw('SUM(vnt_detail_quotes.price) as total')
            ])
            ->join('vnt_quotes', 'vnt_detail_quotes.quoteId', '=', 'vnt_quotes.id')
            ->join('inv_items', 'vnt_detail_quotes.itemId', '=', 'inv_items.id')
            ->join('inv_categories', 'inv_items.categoryId', '=', 'inv_categories.id')
            ->join('inv_item_house', 'inv_items.houseId', '=', 'inv_item_house.id')
            ->where('vnt_quotes.status', 'REMISIÓN')
            ->whereNull('vnt_quotes.deleted_at')
            ->whereNull('vnt_detail_quotes.deleted_at')
            ->groupBy('inv_items.id', 'inv_items.name', 'inv_item_house.name', 'inv_categories.name')
            ->orderBy('inv_items.name');

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
        return ['Producto', 'Casa', 'Categoría', 'Pedidos', 'Cantidad', 'Total'];
    }

    protected function getExportMapping()
    {
        return function ($item) {
            return [
                $item->producto,
                $item->casa,
                $item->categoria,
                $item->pedidos,
                $item->cantidad,
                $item->total,
            ];
        };
    }

    protected function getExportFilename(): string
    {
        return 'ventas_productos_' . now()->format('Y-m-d_His');
    }
}
