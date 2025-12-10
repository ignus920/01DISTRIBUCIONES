<?php

namespace App\Livewire\TAT\Quoter;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Tenant\Quoter\TatRestockList;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RestockList extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 10;
    public $companyId;

    protected $paginationTheme = 'tailwind';

    public function mount()
    {
        $user = Auth::user();
        $this->companyId = $this->getUserCompanyId($user);
    }

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

    public function render()
    {
        // Agrupar estrictamente por order_number. Usamos max(created_at) para mostrar la fecha más reciente del grupo.
        // Asumimos que el status es el mismo para todo el pedido, tomamos cualquiera (max o min).
        $restockOrders = TatRestockList::where('company_id', $this->companyId)
            ->select(
                'order_number', 
                DB::raw('MAX(status) as status'), 
                DB::raw('MAX(created_at) as created_at'), 
                DB::raw('count(*) as total_items')
            )
            ->groupBy('order_number')
            ->orderBy('created_at', 'desc')
            ->when($this->search, function ($query) {
               $query->having('order_number', 'like', '%' . $this->search . '%');
            })
            ->paginate($this->perPage);

        return view('livewire.TAT.quoter.restock-list', [
            'restockOrders' => $restockOrders
        ])->layout('layouts.app');
    }

    public function editRestock($orderNumber)
    {
        // Redirigir al ProductQuoter Desktop con el parámetro restockOrder
        return redirect()->route('tenant.quoter.products.desktop', ['restockOrder' => $orderNumber]);
    }
}
