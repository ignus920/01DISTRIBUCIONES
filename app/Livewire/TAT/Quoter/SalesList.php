<?php

namespace App\Livewire\TAT\Quoter;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\TAT\Quoter\Quote;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SalesList extends Component
{
    use WithPagination, \App\Traits\Livewire\WithExport;

    public $search = '';
    public $perPage = 10;
    public $companyId;

    // Propiedades para el modal de detalles
    public $showDetailModal = false;
    public $selectedQuote = null;

    protected $paginationTheme = 'tailwind';

    public function mount()
    {
        // Usar la misma lógica que QuoterView para obtener company_id
        $user = Auth::user();
        $this->companyId = $this->getUserCompanyId($user);
    }

    /**
     * Obtener el company_id del usuario autenticado (copiado de QuoterView)
     */
    protected function getUserCompanyId($user)
    {
        if ($user->contact_id) {
            $contact = DB::table('vnt_contacts')
                ->where('id', $user->contact_id)
                ->first();

            if ($contact && isset($contact->warehouseId)) {
                $warehouse = DB::table('vnt_warehouses')
                    ->where('id', $contact->warehouseId)
                    ->first();

                return $warehouse ? $warehouse->companyId : null;
            }
        }

        return null;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    /**
     * Mostrar modal de detalles de la venta
     */
    public function showDetails($quoteId)
    {
        $this->selectedQuote = Quote::with(['customer', 'items.item', 'user'])
            ->where('id', $quoteId)
            ->where('company_id', $this->companyId)
            ->first();

        if ($this->selectedQuote) {
            $this->showDetailModal = true;
        }
    }

    /**
     * Cerrar modal de detalles
     */
    public function closeDetailModal()
    {
        $this->showDetailModal = false;
        $this->selectedQuote = null;
    }

    /**
     * Redirigir al sistema de pagos
     */
    public function showPayment($quoteId)
    {
        $quote = Quote::where('id', $quoteId)
            ->where('company_id', $this->companyId)
            ->first();

        if ($quote) {
            // Redirigir a la ruta de pagos con parámetro de origen
            return redirect()->route('tenant.payment.quote', [
                'quoteId' => $quoteId,
                'from' => 'sales-list'
            ]);
        } else {
            session()->flash('error', 'No se encontró la cotización.');
        }
    }

    /**
     * Editar una venta existente
     */
    public function editSale($quoteId)
    {
        $quote = Quote::with(['customer', 'items.item'])
            ->where('id', $quoteId)
            ->where('company_id', $this->companyId)
            ->first();

        if ($quote) {
            // Verificar que la venta no esté pagada
            if ($quote->status === 'Pagado') {
                session()->flash('error', 'No se puede editar una venta que ya está pagada.');
                return;
            }

            // Redirigir a QuoterView con el ID de la cotización para editar
            return redirect()->route('tenant.tat.quoter.index', ['edit' => $quoteId]);
        } else {
            session()->flash('error', 'No se encontró la cotización.');
        }
    }


    public function render()
{
    $quotes = Quote::where('company_id', $this->companyId)
        ->with(['user', 'customer', 'items'])
        ->when($this->search, function ($query) {
            $query->where(function ($q) {
                $q->where('consecutive', 'like', '%' . $this->search . '%')
                  ->orWhereHas('customer', function ($customerQuery) {
                      $customerQuery->where('businessName', 'like', '%' . $this->search . '%')
                                   ->orWhere('firstName', 'like', '%' . $this->search . '%')
                                   ->orWhere('lastName', 'like', '%' . $this->search . '%')
                                   ->orWhere('identification', 'like', '%' . $this->search . '%');
                  });
            });
        })
        ->orderBy('created_at', 'desc')
        ->paginate($this->perPage);

        return view('livewire.TAT.quoter.sales-list', [
            'quotes' => $quotes
        ])
        ->layout('layouts.app'); // 👈 aquí agregas el layout
    }

    /**
     * Métodos para Exportación
     */

    protected function getExportData()
    {
        return Quote::where('company_id', $this->companyId)
            ->with(['user', 'customer', 'items'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('consecutive', 'like', '%' . $this->search . '%')
                      ->orWhereHas('customer', function ($customerQuery) {
                          $customerQuery->where('businessName', 'like', '%' . $this->search . '%')
                                       ->orWhere('firstName', 'like', '%' . $this->search . '%')
                                       ->orWhere('lastName', 'like', '%' . $this->search . '%')
                                       ->orWhere('identification', 'like', '%' . $this->search . '%');
                      });
                });
            })
            ->orderBy('created_at', 'desc')
            ->get();
    }

    protected function getExportHeadings(): array
    {
        return ['ID', 'Consecutivo', 'Cliente', 'Vendedor', 'Total', 'Estado', 'Fecha'];
    }

    protected function getExportMapping()
    {
        return function($quote) {
            $customerName = $quote->customer 
                ? ($quote->customer->businessName ?: $quote->customer->firstName . ' ' . $quote->customer->lastName)
                : 'N/A';

            return [
                $quote->id,
                $quote->consecutive ?: 'N/A',
                $customerName,
                $quote->user->name ?? 'N/A',
                $quote->total,
                $quote->status,
                $quote->created_at ? $quote->created_at->format('Y-m-d H:i:s') : 'N/A',
            ];
        };
    }

    protected function getExportFilename(): string
    {
        return 'ventas_tat_' . now()->format('Y-m-d_His');
    }
}

