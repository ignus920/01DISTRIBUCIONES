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
use App\Models\Tenant\DeliveriesList\DisDeliveries;
//Services
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;

class Uploads extends Component
{
    //Propiedades para la tabla
    public $showModal = false;
    public $search = '';
    public $sortField = 'consecutive';
    public $sortDirection = 'desc';
    public $perPage = 10;


    public $selectedDate = '';
    public $selectedRoute = '';
    public $selectedRouteName = '';
    public $selectedRouteSaleDay = '';
    public $selectedRouteDeliveryDay = '';
    public $remissions = [];
    public $selectedDeliveryMan = '';
    public $selectedSaleDay = '';
    public $showScares = false;
    public $scarceUnits = [];
    public $showformMovements = false;
    public $showConfirmModal = false;
    public $showFooter = true;
    public $showClearOptions = false;
    public $showPreviewCharge = false;
    public $previewItems = [];


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


    public function updatedSelectedDeliveryMan($value)
    {
        // El transportador no afecta el filtro de remisiones,
        // solo se usa para asignar en el momento del cargue
    }

    public function updatedSelectedSaleDay($value)
    {
        // Cargar remisiones por día si hay día seleccionado
        if ($value) {
            $this->remissions = $this->getRemissionsByDay($value);
        } else {
            $this->remissions = [];
        }
    }

    public function validateDeliveryManRoute($deliveryManId, $routeId)
    {
        // Obtener el día de venta de la ruta actual
        $currentRouteSaleDay = DB::table('tat_routes')
            ->where('id', $routeId)
            ->value('sale_day');

        // Verificar si el transportador ya tiene pedidos asignados en rutas de días diferentes
        $existingDeliveries = DisDeliveriesList::where('deliveryman_id', $deliveryManId)
            ->join('tat_routes as rt', 'dis_deliveries_list.route', '=', 'rt.id')
            ->where('rt.sale_day', '!=', $currentRouteSaleDay)
            ->first();

        // Log para debug
        Log::info('Validación transportador por día:', [
            'deliveryman_id' => $deliveryManId,
            'route_id' => $routeId,
            'current_sale_day' => $currentRouteSaleDay,
            'existing_deliveries' => $existingDeliveries ? $existingDeliveries->toArray() : null,
            'has_conflict' => $existingDeliveries ? true : false
        ]);

        return !$existingDeliveries; // Retorna true si no hay conflictos (false si hay conflicto)
    }

    public function getRemissions($date)
    {
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
                ) THEN 'SI' ELSE 'NO' END as existe")
            );

        // Filtrar por ruta si está seleccionada
        if ($this->selectedRoute) {
            $subquery->join('tat_companies_routes as cr', 'q.customerId', '=', 'cr.company_id')
                     ->where('cr.route_id', $this->selectedRoute);
        }

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
            ->whereDate('q.created_at', $date)
            ->where('r.status', 'REGISTRADO');

        // Agregar esto para depurar
        // Log::info('Consulta SQL:', [
        //     'sql' => $query->toSql(),
        //     'bindings' => $query->getBindings(),
        //     'date' => $date,
        //     'routeId' => $routeId
        // ]);

        // Filtrar por ruta específica si está seleccionada
        if ($this->selectedRoute) {
            $query->where('rt.id', $this->selectedRoute);
        }

        return $query->groupBy('u.id', 'u.name', DB::raw('DATE(q.created_at)'), 'rt.name')->get();
    }

    public function getRemissionsByDay($saleDay)
    {
        if (!$saleDay) {
            return [];
        }

        // Consulta principal con la nueva estructura de relaciones y el campo existe
        $query = DB::table('vnt_quotes as q')
            ->join('inv_remissions as r', 'q.id', '=', 'r.quoteId')
            ->join('vnt_warehouses as w', 'q.customerId', '=', 'w.id')
            ->join('vnt_companies as com', 'w.companyId', '=', 'com.id')
            ->join('tat_companies_routes as cXr', 'com.id', '=', 'cXr.company_id')
            ->join('tat_routes as rt', 'cXr.route_id', '=', 'rt.id')
            ->leftJoin('dis_deliveries_list as dl', 'q.userId', '=', 'dl.salesman_id')
            ->select(
                'rt.name as ruta',
                'rt.id as route_id',
                DB::raw('COUNT(DISTINCT q.userId) as cantidad_vendedores'),
                DB::raw('COUNT(*) as cantidad_pedidos'),
                DB::raw('CASE
                    WHEN COUNT(DISTINCT CASE WHEN dl.salesman_id IS NOT NULL THEN q.userId END) = 0 THEN "NO"
                    WHEN COUNT(DISTINCT CASE WHEN dl.salesman_id IS NOT NULL THEN q.userId END) = COUNT(DISTINCT q.userId) THEN "COMPLETO"
                    ELSE "PARCIAL"
                END as existe')
            )
            ->where('r.status', 'REGISTRADO')
            ->where('rt.sale_day', $saleDay)
            ->groupBy('rt.id', 'rt.name')
            ->orderBy('rt.name');

        $results = $query->get();

        // Log para depurar
        Log::info('Remisiones por día:', [
            'sale_day' => $saleDay,
            'results' => $results->toArray()
        ]);

        return $results;
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
        if (!$this->selectedDeliveryMan) {
            session()->flash('error', 'Por favor selecciona un transportador primero');
            return;
        }

        // Obtener la ruta del vendedor
        $vendorRoute = DB::table('vnt_quotes as q')
            ->join('inv_remissions as r', 'q.id', '=', 'r.quoteId')
            ->join('vnt_warehouses as w', 'q.customerId', '=', 'w.id')
            ->join('vnt_companies as com', 'w.companyId', '=', 'com.id')
            ->join('tat_companies_routes as cr', 'com.id', '=', 'cr.company_id')
            ->where('q.userId', $userId)
            ->where('r.status', 'REGISTRADO')
            ->select(DB::raw('DATE(q.created_at) as sale_date'), 'cr.route_id as route')
            ->first();

        if (!$vendorRoute) {
            session()->flash('error', 'No se pudo encontrar la ruta del vendedor');
            return;
        }

        $saleDate = $vendorRoute->sale_date;
        $routeId = $vendorRoute->route;

        // Validar que el transportador no tenga pedidos de días diferentes
        if (!$this->validateDeliveryManRoute($this->selectedDeliveryMan, $routeId)) {
            // Obtener información detallada para el mensaje
            $transporterName = DB::table('users')->where('id', $this->selectedDeliveryMan)->value('name');

            $existingDelivery = DisDeliveriesList::where('deliveryman_id', $this->selectedDeliveryMan)
                ->join('tat_routes as rt', 'dis_deliveries_list.route', '=', 'rt.id')
                ->first();

            if ($existingDelivery) {
                session()->flash('error', "El transportador {$transporterName} ya tiene pedidos asignados para el día '{$existingDelivery->sale_day}'. Un transportador solo puede llevar pedidos del mismo día. Por favor seleccione otro transportador.");
            }
            return;
        }

        try {
            // Usar la fecha y ruta obtenidas dinámicamente
            $uploadData = [
                'sale_date' => $saleDate,
                'salesman_id' => $userId,
                'deliveryman_id' => $this->selectedDeliveryMan,
                'route' => $routeId,
                'user_id' => Auth::id(),
                'created_at' => Carbon::now()
            ];
            DisDeliveriesList::create($uploadData);

            // Forzar recarga completa de las remisiones
            if ($this->selectedSaleDay) {
                $this->remissions = $this->getRemissionsByDay($this->selectedSaleDay);
            }

            // También refrescar el componente para asegurar que se actualice la vista
            $this->dispatch('$refresh');

            session()->flash('message', "Cargando datos para el usuario ID: $userId - Ruta seleccionada");
        } catch (\Exception $e) {
            Log::error($e);
            session()->flash('error', "Error al registrar el cargue" . $e->getMessage());
        }
    }

    public function cargarRuta($routeId)
    {
        if (!$this->selectedDeliveryMan) {
            session()->flash('error', 'Por favor selecciona un transportador primero');
            return;
        }

        // Validar que el transportador no tenga pedidos de días diferentes
        if (!$this->validateDeliveryManRoute($this->selectedDeliveryMan, $routeId)) {
            $transporterName = DB::table('users')->where('id', $this->selectedDeliveryMan)->value('name');
            $existingDelivery = DisDeliveriesList::where('deliveryman_id', $this->selectedDeliveryMan)
                ->join('tat_routes as rt', 'dis_deliveries_list.route', '=', 'rt.id')
                ->first();

            if ($existingDelivery) {
                session()->flash('error', "El transportador {$transporterName} ya tiene pedidos asignados para el día '{$existingDelivery->sale_day}'. Un transportador solo puede llevar pedidos del mismo día. Por favor seleccione otro transportador.");
            }
            return;
        }

        try {
            // Obtener todos los vendedores de la ruta que no están cargados
            $vendorsToLoad = DB::table('vnt_quotes as q')
                ->join('inv_remissions as r', 'q.id', '=', 'r.quoteId')
                ->join('vnt_warehouses as w', 'q.customerId', '=', 'w.id')
                ->join('vnt_companies as com', 'w.companyId', '=', 'com.id')
                ->join('tat_companies_routes as cr', 'com.id', '=', 'cr.company_id')
                ->where('cr.route_id', $routeId)
                ->where('r.status', 'REGISTRADO')
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('dis_deliveries_list as dl')
                        ->whereRaw('dl.salesman_id = q.userId');
                })
                ->select('q.userId', DB::raw('DATE(q.created_at) as sale_date'))
                ->distinct()
                ->get();

            $loadedCount = 0;
            foreach ($vendorsToLoad as $vendor) {
                $uploadData = [
                    'sale_date' => $vendor->sale_date,
                    'salesman_id' => $vendor->userId,
                    'deliveryman_id' => $this->selectedDeliveryMan,
                    'route' => $routeId,
                    'user_id' => Auth::id(),
                    'created_at' => Carbon::now()
                ];

                DisDeliveriesList::create($uploadData);
                $loadedCount++;
            }

            // Recargar los datos de la tabla
            if ($this->selectedSaleDay) {
                $this->remissions = $this->getRemissionsByDay($this->selectedSaleDay);
            }

            $this->dispatch('$refresh');

            if ($loadedCount > 0) {
                session()->flash('message', "Se cargaron {$loadedCount} vendedor" . ($loadedCount != 1 ? 'es' : '') . " de la ruta exitosamente");
            } else {
                session()->flash('warning', "Todos los vendedores de esta ruta ya están cargados");
            }
        } catch (\Exception $e) {
            Log::error($e);
            session()->flash('error', "Error al cargar la ruta: " . $e->getMessage());
        }
    }

    public function eliminarRuta($routeId)
    {
        try {
            // Eliminar todos los registros de la ruta
            $deleted = DisDeliveriesList::where('route', $routeId)->delete();

            if ($deleted) {
                // Recargar los datos de la tabla
                if ($this->selectedSaleDay) {
                    $this->remissions = $this->getRemissionsByDay($this->selectedSaleDay);
                }

                session()->flash('message', "Se eliminaron {$deleted} registro" . ($deleted != 1 ? 's' : '') . " de la ruta exitosamente");
            } else {
                session()->flash('warning', "No se encontraron registros de esta ruta para eliminar");
            }
        } catch (\Exception $e) {
            Log::error($e);
            session()->flash('error', "Error al eliminar los registros de la ruta: " . $e->getMessage());
        }
    }

    public function eliminar($userId)
    {
        try {
            // Buscar y eliminar el registro del vendedor
            $deleted = DisDeliveriesList::where('salesman_id', $userId)->delete();

            if ($deleted) {
                // Recargar los datos de la tabla
                if ($this->selectedSaleDay) {
                    $this->remissions = $this->getRemissionsByDay($this->selectedSaleDay);
                }

                session()->flash('message', "Registro eliminado exitosamente");
            } else {
                session()->flash('warning', "No se encontró el registro para eliminar");
            }
        } catch (\Exception $e) {
            Log::error($e);
            session()->flash('error', "Error al eliminar el registro: " . $e->getMessage());
        }
    }

    public function validateScarce()
    {
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

    public function showConfirmUploadModal()
    {
        // Verificar que hay registros en dis_deliveries_list para confirmar
        $hasDeliveries = DisDeliveriesList::where('user_id', Auth::id())->exists();

        if (!$hasDeliveries) {
            session()->flash('error', 'No hay pedidos cargados para confirmar');
            return;
        }

        $this->showConfirmModal = true;
    }

    public function cancelConfirmUpload()
    {
        $this->showConfirmModal = false;
        $this->showFooter = true;
        $this->showClearOptions = false;
    }


    public function confirmUpload()
    {
        $this->showConfirmModal = false;

        $hayFaltantes = $this->validateScarce();

        if ($hayFaltantes === 'SI') {
            $this->showScares = true;
            $this->scarceUnits = $this->getscarceUnits();
            return;
        } else {
            try {
                $infoDisDeliveriesList = DisDeliveriesList::where('user_id', Auth::id())->get();

                // Agrupar por ruta para crear un solo dis_deliveries por ruta
                $groupedByRoute = $infoDisDeliveriesList->groupBy('route');

                foreach ($groupedByRoute as $routeId => $vendorsInRoute) {
                    // Tomar el primer item del grupo para obtener datos de la ruta
                    $firstItem = $vendorsInRoute->first();

                    // Crear un solo registro dis_deliveries para toda la ruta
                    $dataDeliveries = [
                        'salesman_id' => $firstItem->salesman_id, // Puede ser cualquiera de la ruta
                        'deliveryman_id' => $firstItem->deliveryman_id,
                        'user_id' => Auth::id(),
                        'sale_date' => $firstItem->sale_date,
                        'created_at' => Carbon::now()
                    ];

                    //Registro en la tabla dis_deliveries (UNO por ruta)
                    $dis_deliveries = DisDeliveries::create($dataDeliveries);

                    //Actualización registros en la tabla inv_remissions - primero obtener IDs de TODA la ruta
                    $remissionIds = DB::table('inv_detail_remissions as d')
                        ->join('inv_remissions as r', 'd.remissionId', '=', 'r.id')
                        ->join('vnt_quotes as q', 'r.quoteId', '=', 'q.id')
                        ->join('vnt_warehouses as w', 'q.customerId', '=', 'w.id')
                        ->join('vnt_companies as c', 'w.companyId', '=', 'c.id')
                        ->join('tat_companies_routes as cXr', 'c.id', '=', 'cXr.company_id')
                        ->join('tat_routes as ro', 'cXr.route_id', '=', 'ro.id')
                        ->where('ro.id', $routeId) // Filtrar por esta ruta específica
                        ->where('r.status', 'REGISTRADO')
                        ->groupBy('r.id')
                        ->pluck('r.id');

                    // Actualizar TODAS las remisiones de esta ruta con el mismo delivery_id
                    if ($remissionIds->isNotEmpty()) {
                        InvRemissions::whereIn('id', $remissionIds)
                            ->update(['delivery_id' => $dis_deliveries->id, 'deliveryDate' => $dis_deliveries->sale_date, 'status' => 'EN RECORRIDO']);
                    }

                    Log::info('Cargue confirmado por ruta:', [
                        'route_id' => $routeId,
                        'delivery_id' => $dis_deliveries->id,
                        'vendedores_en_ruta' => $vendorsInRoute->count(),
                        'remisiones_actualizadas' => $remissionIds->count()
                    ]);
                }

                $this->clearListUpload();
                // Si no hay faltantes, proceder con la lógica de confirmación
                session()->flash('message', 'Cargue confirmado exitosamente.');
            } catch (\Exception $e) {
                Log::error($e);
                session()->flash('error', "Error al registrar el cargue: " . $e->getMessage());
            }
        }
    }

    public function getscarceUnits()
    {
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

    public function closeAlertScares()
    {
        $this->showScares = false;
    }

    public function openMovementForm()
    {
        //$this->dispatch("openMovementForm");
        //$this->showModal = true;
        //$this->showScares = false;

    }

    public function closeModal()
    {
        $this->showConfirmModal = false;
        $this->showFooter = true;
        $this->showClearOptions = false;
    }

    public function clearListUpload()
    {
        try {
            $deleted = DisDeliveriesList::where('user_id', Auth::id())
                ->delete();
            if ($deleted) {
                session()->flash('message', "La lista de cargue se vació exitosamente");
            } else {
                session()->flash('error', "No se encontraron registros para eliminar");
            }
            $this->showConfirmModal = false;
            $this->showFooter = true;
            $this->showClearOptions = false;
        } catch (\Exception $e) {
            Log::error($e);
            session()->flash('error', "Error al eliminar los registros: " . $e->getMessage());
        }
    }

    public function showPreCharge()
    {
        if (!$this->selectedDeliveryMan) {
            session()->flash('error', 'Por favor selecciona un transportador primero');
            return;
        }

        try {
            // Obtener items con la nueva consulta SQL
            $this->previewItems = DB::table('inv_detail_remissions as d')
                ->join('inv_remissions as r', 'd.remissionId', '=', 'r.id')
                ->join('inv_items as it', 'd.itemId', '=', 'it.id')
                ->join('vnt_quotes as q', 'r.quoteId', '=', 'q.id')
                ->join('vnt_warehouses as w', 'q.customerId', '=', 'w.id')
                ->join('vnt_companies as c', 'w.companyId', '=', 'c.id')
                ->join('tat_companies_routes as cXr', 'c.id', '=', 'cXr.company_id')
                ->join('tat_routes as ro', 'cXr.route_id', '=', 'ro.id')
                ->join('inv_categories as cat', 'it.categoryId', '=', 'cat.id')
                ->leftJoin('inv_items_store as its', 'it.id', '=', 'its.itemId')
                ->whereIn('ro.id', function($query) {
                    $query->select('route')
                        ->from('dis_deliveries_list')
                        ->where('deliveryman_id', $this->selectedDeliveryMan);
                })
                ->select(
                    'it.internal_code as code',
                    'cat.name as category',
                    'it.name as name_item',
                    DB::raw('SUM(d.quantity) as quantity'),
                    DB::raw('COALESCE(its.stock_items_store, 0) as stock_actual'),
                    DB::raw('CASE
                        WHEN its.stock_items_store IS NULL THEN "FALTANTE - No existe en inventario"
                        WHEN SUM(d.quantity) > its.stock_items_store THEN "FALTANTE"
                        ELSE "DISPONIBLE"
                    END as status_stock')
                )
                ->groupBy('cat.id', 'cat.name', 'it.id', 'it.name', 'its.stock_items_store', 'it.internal_code')
                ->orderBy('cat.name')
                ->orderBy('it.name')
                ->get();

            $this->showPreviewCharge = true;
        } catch (\Exception $e) {
            Log::error($e);
            session()->flash('error', "Error al obtener los datos de la previa: " . $e->getMessage());
        }
    }

    public function hidePreCharge()
    {
        $this->showPreviewCharge = false;
        $this->previewItems = [];
    }

    public function hasLoadedDeliveries()
    {
        if (!$this->selectedDeliveryMan) {
            return false;
        }

        return DisDeliveriesList::where('deliveryman_id', $this->selectedDeliveryMan)->exists();
    }

    public function printPreCharge()
    {
        if (!$this->selectedDeliveryMan) {
            session()->flash('error', 'Por favor selecciona un transportador primero');
            return;
        }

        try {
            // Obtener items con la nueva consulta SQL
            $items = DB::table('inv_detail_remissions as d')
                ->join('inv_remissions as r', 'd.remissionId', '=', 'r.id')
                ->join('inv_items as it', 'd.itemId', '=', 'it.id')
                ->join('vnt_quotes as q', 'r.quoteId', '=', 'q.id')
                ->join('vnt_warehouses as w', 'q.customerId', '=', 'w.id')
                ->join('vnt_companies as c', 'w.companyId', '=', 'c.id')
                ->join('tat_companies_routes as cXr', 'c.id', '=', 'cXr.company_id')
                ->join('tat_routes as ro', 'cXr.route_id', '=', 'ro.id')
                ->join('inv_categories as cat', 'it.categoryId', '=', 'cat.id')
                ->leftJoin('inv_items_store as its', 'it.id', '=', 'its.itemId')
                ->whereIn('ro.id', function($query) {
                    $query->select('route')
                        ->from('dis_deliveries_list')
                        ->where('deliveryman_id', $this->selectedDeliveryMan);
                })
                ->select(
                    'it.internal_code as code',
                    'cat.name as category',
                    'it.name as name_item',
                    DB::raw('SUM(d.quantity) as quantity'),
                    'its.stock_items_store as stockActual'
                )
                ->groupBy('cat.id', 'cat.name', 'it.id', 'it.name', 'its.stock_items_store', 'it.internal_code')
                ->orderBy('cat.name')
                ->orderBy('it.name')
                ->get();

            $cleanedItems = $this->cleanUtf8Data($items);

            $data = [
                'items' => $cleanedItems,
            ];

            $pdf = PDF::loadView('tenant.uploads.pre-charge-pdf', $data);
            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->stream();
            }, 'pre-cargue.pdf');
        } catch (\Exception $e) {
            Log::error($e);
            session()->flash('error', "Error al generar la impresión: " . $e->getMessage());
        }
    }

    public function render()
    {
        $users = DB::table('users')->select('id', 'name')->where('profile_id', 13)->get();


        // Definir los días de la semana para el selector
        $daysOfWeek = [
            'Lunes' => 'Lunes',
            'Martes' => 'Martes',
            'Miércoles' => 'Miércoles',
            'Jueves' => 'Jueves',
            'Viernes' => 'Viernes',
            'Sábado' => 'Sábado',
            'Domingo' => 'Domingo'
        ];

        return view('livewire.tenant.uploads.uploads', [
            'users' => $users,
            'remissions' => $this->remissions,
            'scarceUnits' => $this->scarceUnits,
            'daysOfWeek' => $daysOfWeek,
        ]);
    }

    private function cleanUtf8Data($data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->cleanUtf8Data($value);
            }
            return $data;
        } elseif (is_object($data)) {
            // Si es un objeto, convertirlo a array, verificando si tiene el método toArray
            $dataArray = method_exists($data, 'toArray') ? $data->toArray() : (array) $data;
            return $this->cleanUtf8Data($dataArray);
        } elseif (is_string($data)) {
            // Limpiar la cadena UTF-8
            $cleaned = mb_convert_encoding($data, 'UTF-8', 'UTF-8');
            // Remover caracteres inválidos
            $cleaned = preg_replace('/[^\x{0000}-\x{007F}]/u', '', $cleaned);
            // Otra alternativa más agresiva
            $cleaned = iconv('UTF-8', 'UTF-8//IGNORE//TRANSLIT', $data);
            return $cleaned;
        }
        return $data;
    }

    private function cleanString($string)
    {
        // Primero intentar con iconv
        $string = iconv('UTF-8', 'UTF-8//IGNORE', $string);

        // Si aún hay problemas, usar regex para eliminar caracteres no UTF-8 válidos
        $string = preg_replace('/[^\x{0000}-\x{007F}\x{00A0}-\x{00FF}]/u', '', $string);

        // Convertir entidades HTML si es necesario
        $string = html_entity_decode($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return $string;
    }
}
