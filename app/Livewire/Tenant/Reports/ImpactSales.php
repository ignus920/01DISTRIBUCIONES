<?php

namespace App\Livewire\Tenant\Reports;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use App\Models\Tenant\Quoter\VntDetailQuote;
use Livewire\Attributes\Computed;

class ImpactSales extends Component
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

    public function getReporteImpactoVentas($paginate = true)
    {
        if (!$this->hasSearched) {
            return $paginate ? new \Illuminate\Pagination\LengthAwarePaginator([], 0, $this->perPage) : collect([]);
        }

        $query = VntDetailQuote::query()
            ->select([
                'users.name as vendedor',
                'inv_categories.name as categoria',
                'inv_item_house.name as casa',
                DB::raw("CONCAT(vnt_companies.firstName, ' ', vnt_companies.lastName) as cliente"),
                'vnt_companies.businessName',
                'inv_items.id as idProducto',
                'inv_items.name as producto',
                DB::raw("SUM(vnt_detail_quotes.quantity) as cantidad")
            ])
            ->join('vnt_quotes', 'vnt_detail_quotes.quoteId', '=', 'vnt_quotes.id')
            ->join('inv_items', 'vnt_detail_quotes.itemId', '=', 'inv_items.id')
            ->join('inv_categories', 'inv_items.categoryId', '=', 'inv_categories.id')
            ->join('inv_item_house', 'inv_items.houseId', '=', 'inv_item_house.id')
            ->join('users', 'vnt_quotes.userId', '=', 'users.id')
            ->join('vnt_companies', 'vnt_quotes.customerId', '=', 'vnt_companies.id')
            ->where('vnt_quotes.status', 'REMISIÓN')
            ->whereNull('vnt_quotes.deleted_at')
            ->whereNull('vnt_detail_quotes.deleted_at')
            ->groupBy('inv_items.id', 'inv_items.name', 'users.name', 'inv_categories.name', 'inv_item_house.name', 'vnt_companies.firstName', 'vnt_companies.lastName', 'vnt_companies.businessName')
            ->orderBy('inv_items.name');

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
        $reporteImpactoVentas = $this->getReporteImpactoVentas();
        return view('livewire.tenant.reports.impact-sales', [
            'reporteImpactoVentas' => $reporteImpactoVentas,
        ]);
    }
}
