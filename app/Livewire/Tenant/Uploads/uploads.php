<?php

namespace App\Livewire\Tenant\Uploads;

use App\Models\TAT\Categories\TatCategories;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
//Modelos
use App\Models\TAT\Routes;
use App\Models\TAT\Routes\TatRoutes;
use App\Models\Tenant\Remissions\InvRemissions;
use App\Models\Tenant\DeliveriesList\DisDeliveriesList;
use App\Models\Tenant\Deliveries\DisDeliveries;
//Services
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class Uploads extends Component
{
    //Propiedades para la tabla
    public $showModal = false;
    public $search = '';
    public $sortField = 'consecutive';
    public $sortDirection = 'desc';
    public $perPage = 10;


    public $selectedDate = '';
    public $remissions = [];
    public $selectedRoute = '';
    public $showScares = false;
    public $scarceUnits = [];
    public $showformMovements = false;


    // impresion de carges 
    public $showCharge = "pedidos";  


    public function updatedSelectedDate($value)
    {
        // Solo hacer la consulta si hay fecha válida
        if ($value) {
            try {
                $this->remissions = $this->getRemissions($value);
            } catch (\Exception $e) {
                session()->flash('error', 'Error al cargar las remisiones: ' . $e->getMessage());
                $this->remissions = [];
            }
        } else {
            $this->remissions = [];
        }
    }

    public function updatedSelectedRoute($value)
    {
        if ($value && $this->selectedDate) {
            $this->remissions = $this->getRemissions($this->selectedDate, $value);
        }
    }

    public function getRemissions($date, $routeId = null){
        // Usamos una subconsulta para calcular el campo "existe"
        $subquery = DB::table('vnt_quotes as q')
            ->select(
                'q.id',
                'q.userId',
                'q.created_at',
                'q.customerId',
                DB::raw("CASE WHEN EXISTS (
                    SELECT 1 
                    FROM dis_deliveries_list d 
                    WHERE d.salesman_id = q.userId
                    AND d.sale_date = DATE(q.created_at)
                ) THEN 'SI' ELSE 'NO' END as existe")
            );
        
        // if ($routeId) {
        //     $subquery->where('r.routeId', $routeId);
        // }
    
        // Consulta principal
        $query = DB::table(DB::raw("({$subquery->toSql()}) as q"))
            ->mergeBindings($subquery)
            ->join('users as u', 'q.userId', '=', 'u.id')
            ->join('inv_remissions as r', 'q.id', '=', 'r.quoteId')
            ->join('tat_companies_routes as cXr', 'q.customerId', '=', 'cXr.company_id')
            ->join('tat_routes as rt', 'cXr.route_id', '=', 'rt.id')
            ->select(
                'u.name',
                'q.userId',
                'rt.name as ruta',
                DB::raw('DATE(q.created_at) as fecha'),
                DB::raw('COUNT(*) as total_registros'),
                DB::raw('MAX(q.existe) as existe')
            )
            ->whereDate('q.created_at', $date);

            // Agregar esto para depurar
        // Log::info('Consulta SQL:', [
        //     'sql' => $query->toSql(),
        //     'bindings' => $query->getBindings(),
        //     'date' => $date,
        //     'routeId' => $routeId
        // ]);

        return $query->groupBy('u.id', 'u.name', DB::raw('DATE(q.created_at)'),'rt.name')->get();
    }

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

    public function clearDate()
    {
        $this->selectedDate = '';
        $this->remissions = [];
    }

    public function cargar($userId)
    {
        if (!$this->selectedDate) {
            session()->flash('error', 'Por favor selecciona una fecha primero');
            return;
        }
    
        // Tu lógica de carga aquí
        try{
            $uploadData=[
                'sale_date' => $this->selectedDate,
                'salesman_id' => $userId,
                'user_id' => Auth::id(),
                'created_at' => Carbon::now()
            ];
            DisDeliveriesList::create($uploadData);

            $this->remissions = $this->getRemissions($this->selectedDate, $this->selectedRoute);
            
            session()->flash('message', "Cargando datos para el usuario ID: $userId - Fecha: {$this->selectedDate}");

        }catch(\Exception $e){
            // Para debug, muestra un mensaje
            session()->flash('error', "Error al registrar el cargue".$e->getMessage());
        }
    
    }

    public function eliminar($userId)
    {   
        if (!$this->selectedDate) {
            session()->flash('error', 'Por favor selecciona una fecha primero');
            return;
        }

        try {
            // Buscar y eliminar el registro
            $deleted = DisDeliveriesList::where('salesman_id', $userId)
                ->whereDate('sale_date', $this->selectedDate)
                ->delete();

            if ($deleted) {
                // Recargar los datos de la tabla
                $this->remissions = $this->getRemissions($this->selectedDate, $this->selectedRoute);

                session()->flash('message', "Registro eliminado exitosamente");
            } else {
                session()->flash('warning', "No se encontró el registro para eliminar");
            }

        } catch(\Exception $e) {
            session()->flash('error', "Error al eliminar el registro: " . $e->getMessage());
        }
    }

    public function validateScarce(){
        $result = DB::selectOne("
        SELECT 
            CASE 
                WHEN EXISTS (
                    SELECT 1 
                    FROM dis_deliveries_list dl 
                    INNER JOIN vnt_quotes q ON dl.salesman_id = q.userId 
                        AND DATE(q.created_at) = dl.sale_date 
                    INNER JOIN vnt_detail_quotes dt ON dt.quoteId = q.id 
                    LEFT JOIN inv_items_store its ON dt.itemId = its.itemId 
                    GROUP BY dt.itemId, its.stock_items_store 
                    HAVING SUM(dt.quantity) > COALESCE(its.stock_items_store, 0)
                       OR its.stock_items_store IS NULL
                    LIMIT 1
                ) THEN 'SI' 
                ELSE 'NO' 
            END AS hay_faltantes");
    
        return $result->hay_faltantes;        
    }

    public function confirmUpload()
    {
        $hayFaltantes = $this->validateScarce();

        if ($hayFaltantes === 'SI') {
            $this->showScares = true;
            $this->scarceUnits = $this->getscarceUnits();
            return;
        }else{
            try{
                $infoDisDeliveriesList = DisDeliveriesList::query()->get();
                foreach ($infoDisDeliveriesList as $deliveryListItem) {
                    $dataDeliveries = [
                        'salesman_id' => $deliveryListItem->salesman_id,
                        'user_id' => Auth::id(),
                        'sale_date' => $deliveryListItem->sale_date,
                        'created_at' => Carbon::now()
                    ];

                    //Registro en la tabla dis_deliveries
                    $dis_deliveries = DisDeliveries::create($dataDeliveries);

                    //Actualización registros en la tabla inv_remissions
                    InvRemissions::whereHas('quote', function ($query) use ($deliveryListItem) {
                        $query->where('userId', $deliveryListItem->salesman_id)
                              ->whereDate('created_at', $deliveryListItem->sale_date);
                    })->update(['delivery_id' => $dis_deliveries->id, 'deliveryDate' => $dis_deliveries->sale_date]);

                }
                // Si no hay faltantes, proceder con la lógica de confirmación
                session()->flash('message', 'Cargue confirmado exitosamente.');
            }catch(\Exception $e){
                // Para debug, muestra un mensaje
                session()->flash('error', "Error al registrar el cargue: " . $e->getMessage());
            }
        }
    }

    public function getscarceUnits(){
        $results = DB::table('dis_deliveries_list as dl')
            ->join('vnt_quotes as q', function ($join) {
                $join->on('dl.salesman_id', '=', 'q.userId')
                     ->on(DB::raw('DATE(q.created_at)'), '=', 'dl.sale_date');
            })
            ->join('vnt_detail_quotes as dt', 'dt.quoteId', '=', 'q.id')
            ->join('inv_items as i', 'i.id', '=', 'dt.itemId')
            ->join('inv_categories as c', 'i.categoryId', '=', 'c.id')
            ->leftJoin('inv_items_store as its', 'i.id', '=', 'its.itemId')
            ->select(
                'i.name as nombre_item',
                'c.name as categoria',
                DB::raw('SUM(dt.quantity) as cantidad_pedida'),
                DB::raw('COALESCE(its.stock_items_store, 0) as stock_actual'),
                DB::raw('COALESCE(its.stock_items_store, 0) - SUM(dt.quantity) as diferencia'),
                DB::raw("CASE
                    WHEN its.stock_items_store IS NULL THEN 'SI - No existe en inventario'
                    WHEN SUM(dt.quantity) > its.stock_items_store THEN 'SI'
                    ELSE 'NO'
                    END as tiene_faltante")
            )
            ->groupBy('i.id', 'i.name', 'c.name', 'its.stock_items_store')
            ->havingRaw('its.stock_items_store IS NULL OR SUM(dt.quantity) > its.stock_items_store')
            ->get();
        return $results;
    }

    public function closeAlertScares(){
        $this->showScares = false;
    }

    public function openMovementsForm()
    {
        $this->showModal = true;
        $this->showScares = false;
    }

    public function render()
    {
        $users = DB::table('users')->select('id', 'name')->where('profile_id', 13)->get();
        return view('livewire.tenant.uploads.uploads', [
            'users' => $users,
            'remissions' => $this->remissions,
            'scarceUnits' => $this->scarceUnits,
        ]);
    }
}
