<?php

namespace App\Livewire\TAT\Quoter;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\TAT\Quoter\Quote;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SalesList extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 10;
    public $companyId;

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
        ]);
    }
}