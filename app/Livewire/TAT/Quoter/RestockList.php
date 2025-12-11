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
    public $selectedOrderNumber;

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
    // ====================================================
    // 1. Confirmados agrupados por order_number
    // ====================================================
    $confirmed = TatRestockList::where('company_id', $this->companyId)
        ->where('status', 'Confirmado')
        ->whereNotNull('order_number')
        ->select(
            'order_number',
            DB::raw('MAX(status) as status'),
            DB::raw('MAX(created_at) as created_at'),
            DB::raw('COUNT(*) as total_items')
        )
        ->groupBy('order_number')
        ->having('total_items', '>', 0); // Filtrar grupos con 0 productos

    // ====================================================
    // 2. Preliminares agrupados COMO UNA SOLA ORDEN
    // ====================================================
    $preliminary = TatRestockList::where('company_id', $this->companyId)
        ->where('status', 'Registrado')
        ->whereNull('order_number')
        ->select(
            DB::raw('NULL as order_number'),
            DB::raw("'Registrado' as status"),
            DB::raw('MAX(created_at) as created_at'),
            DB::raw('COUNT(*) as total_items')
        )
        ->having('total_items', '>', 0); // Filtrar si no hay productos preliminares

    // ====================================================
    // 3. Unimos confirmados + preliminar
    // ====================================================
    $query = $confirmed->union($preliminary);

    // ====================================================
    // 4. Subconsulta con paginación y ordenamiento
    // ====================================================
    $restockOrders = DB::table(DB::raw("({$query->toSql()}) as restocks"))
        ->mergeBindings($query->getQuery())
        ->when($this->search, function ($q) {
            $q->where('order_number', 'like', '%' . $this->search . '%');
        })
        ->orderBy('created_at', 'desc')
        ->paginate($this->perPage);

    return view('livewire.TAT.quoter.restock-list', [
        'restockOrders' => $restockOrders
    ])->layout('layouts.app');
}


    public function editConfirmedRestock($orderNumber)
    {
        $this->selectedOrderNumber = $orderNumber;

        // Redirigir al ProductQuoter Desktop con el parámetro restockOrder
        return $this->redirect(route('tenant.quoter.products.desktop', ['restockOrder' => $this->selectedOrderNumber]));
    }

    public function editPreliminaryRestock()
    {
        return $this->redirect(
            route('tenant.quoter.products.desktop', ['editPreliminary' => 'true'])
        );
    }

    public function createNewRestock()
    {
        // Verificar si ya existe una lista preliminar para esta empresa
        $existingPreliminary = TatRestockList::where('company_id', $this->companyId)
            ->where('status', 'Registrado')
            ->whereNull('order_number')
            ->exists();

        if ($existingPreliminary) {
            // Si ya existe lista preliminar, redirigir a editarla
            return $this->redirect(
                route('tenant.quoter.products.desktop', ['editPreliminary' => 'true'])
            );
        } else {
            // Si no existe lista preliminar, crear nueva
            return $this->redirect(
                route('tenant.quoter.products.desktop')
            );
        }
    }
}
