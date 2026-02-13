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
    use \App\Traits\Livewire\WithExport;

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
    // 1. Confirmados y Recibidos agrupados por order_number con remisión y cotización
    // ====================================================
    $confirmed = DB::table('tat_restock_list as r')
        ->leftJoin('vnt_quotes as q', 'q.id', '=', 'r.order_number')
        ->leftJoin('inv_remissions as rem', 'rem.quoteId', '=', 'r.order_number')
        ->where('r.company_id', $this->companyId)
        ->whereIn('r.status', ['Confirmado', 'Recibido'])
        ->whereNotNull('r.order_number')
        ->select(
            'r.order_number',
            DB::raw('MAX(r.status) as status'),
            DB::raw('MAX(r.created_at) as created_at'),
            DB::raw('SUM(r.quantity_request) as total_items'),
            DB::raw('MAX(q.consecutive) as quote_consecutive'),
            DB::raw('MAX(rem.consecutive) as remise_number'),
            DB::raw('MAX(rem.status) as rem_status')
        )
        ->groupBy('r.order_number')
        ->having('total_items', '>', 0);

    // ====================================================
    // 2. Preliminares agrupados COMO UNA SOLA ORDEN
    // ====================================================
    $preliminary = DB::table('tat_restock_list')
        ->where('company_id', $this->companyId)
        ->where('status', 'Registrado')
        ->whereNull('order_number')
        ->select(
            DB::raw('NULL as order_number'),
            DB::raw("'Registrado' as status"),
            DB::raw('MAX(created_at) as created_at'),
            DB::raw('SUM(quantity_request) as total_items'),
            DB::raw('NULL as quote_consecutive'),
            DB::raw('NULL as remise_number'),
            DB::raw('NULL as rem_status')
        )
        ->having('total_items', '>', 0);

    // ====================================================
    // 3. Unimos confirmados + preliminar
    // ====================================================
    $query = $confirmed->union($preliminary);

    // ====================================================
    // 4. Subconsulta con paginación y ordenamiento
    // ====================================================
    $restockOrders = DB::table(DB::raw("({$query->toSql()}) as restocks"))
        ->mergeBindings($query)
        ->when($this->search, function ($q) {
            $q->where('order_number', 'like', '%' . $this->search . '%');
        })
        ->orderBy('created_at', 'desc')
        ->paginate($this->perPage);

    return view('livewire.TAT.quoter.restock-list', [
        'restockOrders' => $restockOrders
    ]);
}


    protected function getRouteName($isMobile)
    {
        return $isMobile ? 'tenant.quoter.products.mobile' : 'tenant.quoter.products.desktop';
    }

    public function editConfirmedRestock($orderNumber)
    {
        $this->selectedOrderNumber = $orderNumber;
        
        $agent = new \Jenssegers\Agent\Agent();
        $routeName = $this->getRouteName($agent->isMobile());

        return $this->redirect(route($routeName, ['restockOrder' => $this->selectedOrderNumber]));
    }

    public function editPreliminaryRestock()
    {
        $agent = new \Jenssegers\Agent\Agent();
        $routeName = $this->getRouteName($agent->isMobile());

        return $this->redirect(
            route($routeName, ['editPreliminary' => 'true'])
        );
    }

    public function createNewRestock()
    {
        $agent = new \Jenssegers\Agent\Agent();
        $routeName = $this->getRouteName($agent->isMobile());

        // Verificar si ya existe una lista preliminar para esta empresa
        $existingPreliminary = TatRestockList::where('company_id', $this->companyId)
            ->where('status', 'Registrado')
            ->whereNull('order_number')
            ->exists();

        if ($existingPreliminary) {
            // Si ya existe lista preliminar, redirigir a editarla
            return $this->redirect(
                route($routeName, ['editPreliminary' => 'true'])
            );
        } else {
            // Si no existe lista preliminar, crear nueva
            return $this->redirect(
                route($routeName)
            );
        }
    }

    /**
     * Métodos para Exportación
     */

    protected function getExportData()
    {
        // Replicamos la lógica del render para obtener las órdenes agrupadas
        $confirmed = DB::table('tat_restock_list as r')
            ->leftJoin('vnt_quotes as q', 'q.id', '=', 'r.order_number')
            ->leftJoin('inv_remissions as rem', 'rem.quoteId', '=', 'r.order_number')
            ->where('r.company_id', $this->companyId)
            ->whereIn('r.status', ['Confirmado', 'Recibido'])
            ->whereNotNull('r.order_number')
            ->select(
                'r.order_number',
                DB::raw('MAX(r.status) as status'),
                DB::raw('MAX(r.created_at) as created_at'),
                DB::raw('SUM(r.quantity_request) as total_items'),
                DB::raw('MAX(q.consecutive) as quote_consecutive'),
                DB::raw('MAX(rem.consecutive) as remise_number'),
                DB::raw('MAX(rem.status) as rem_status')
            )
            ->groupBy('r.order_number');

        $preliminary = DB::table('tat_restock_list')
            ->where('company_id', $this->companyId)
            ->where('status', 'Registrado')
            ->whereNull('order_number')
            ->select(
                DB::raw('NULL as order_number'),
                DB::raw("'Registrado' as status"),
                DB::raw('MAX(created_at) as created_at'),
                DB::raw('SUM(quantity_request) as total_items'),
                DB::raw('NULL as quote_consecutive'),
                DB::raw('NULL as remise_number'),
                DB::raw('NULL as rem_status')
            )
            ->having('total_items', '>', 0);

        $query = $confirmed->union($preliminary);

        return DB::table(DB::raw("({$query->toSql()}) as restocks"))
            ->mergeBindings($query)
            ->when($this->search, function ($q) {
                $q->where('order_number', 'like', '%' . $this->search . '%');
            })
            ->orderBy('created_at', 'desc')
            ->get();
    }

    protected function getExportHeadings(): array
    {
        return ['Orden #', 'Estado', 'Fecha Creación', 'Total Items', 'Consecutivo Cotización', 'Consecutivo Remisión', 'Estado Remisión'];
    }

    protected function getExportMapping()
    {
        return function($order) {
            return [
                $order->order_number ?: 'Lista Preliminar',
                $order->status,
                \Carbon\Carbon::parse($order->created_at)->format('Y-m-d H:i:s'),
                $order->total_items,
                $order->quote_consecutive ?: 'N/A',
                $order->remise_number ?: 'N/A',
                $order->rem_status ?: 'N/A',
            ];
        };
    }

    protected function getExportFilename(): string
    {
        return 'solicitudes_reabastecimiento_' . now()->format('Y-m-d_His');
    }
}
