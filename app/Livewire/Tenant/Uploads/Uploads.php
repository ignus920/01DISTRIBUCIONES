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
    public $selectedSaleDay = [];
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
        if (!$value) return;

        $hasActive = DisDeliveries::where('deliveryman_id', $value)
            ->where('status', '!=', 'CERRADO')
            ->whereNull('deleted_at')
            ->exists();

        if ($hasActive) {
            $name = DB::table('users')->where('id', $value)->value('name');
            $this->selectedDeliveryMan = '';
            session()->flash('error', "El transportador {$name} ya tiene un cargue activo. Debe cerrarse antes de asignarle uno nuevo.");
        }
    }

    public function updatedSelectedSaleDay()
    {
        if (!empty($this->selectedSaleDay)) {
            $this->remissions = $this->getRemissionsByDay($this->selectedSaleDay);
        } else {
            $this->remissions = [];
        }
    }

    public function getRemissions($date)
    {
        $query = DB::table('vnt_quotes as q')
            ->join('users as u', 'q.userId', '=', 'u.id')
            ->join('inv_remissions as r', 'q.id', '=', 'r.quoteId')
            ->join('vnt_warehouses as w', 'q.customerId', '=', 'w.id')
            ->join('vnt_companies as com', 'w.companyId', '=', 'com.id')
            ->join('tat_companies_routes as cXr', 'com.id', '=', 'cXr.company_id')
            ->join('tat_routes as rt', 'cXr.route_id', '=', 'rt.id')
            ->select(
                'u.name',
                'q.userId',
                'rt.id as route_id',
                'rt.name as ruta',
                DB::raw('DATE(q.created_at) as fecha'),
                DB::raw('COUNT(DISTINCT r.id) as total_registros'),
                DB::raw("CASE WHEN EXISTS (
                    SELECT 1 FROM dis_deliveries_list dl 
                    WHERE dl.salesman_id = q.userId AND dl.route = rt.id
                ) THEN 'SI' ELSE 'NO' END as existe")
            )
            ->whereDate('q.created_at', $date)
            ->where('r.status', 'REGISTRADO');

        if ($this->selectedRoute) {
            $query->where('rt.id', $this->selectedRoute);
        }

        return $query->groupBy('u.id', 'u.name', 'rt.id', 'rt.name', DB::raw('DATE(q.created_at)'))->get();
    }

    public function getRemissionsByDay($saleDays)
    {
        $saleDays = (array) $saleDays;
        if (empty($saleDays)) {
            return [];
        }

        $results = DB::table('vnt_quotes as q')
            ->join('inv_remissions as r', 'q.id', '=', 'r.quoteId')
            ->join('inv_detail_remissions as d', 'd.remissionId', '=', 'r.id')
            ->join('vnt_warehouses as w', 'q.customerId', '=', 'w.id')
            ->join('vnt_companies as com', 'w.companyId', '=', 'com.id')
            ->join('tat_companies_routes as cXr', 'com.id', '=', 'cXr.company_id')
            ->join('tat_routes as rt', 'cXr.route_id', '=', 'rt.id')
            ->select(
                DB::raw('q.userId as user_id'),
                DB::raw('(SELECT us.name FROM users us WHERE us.id = q.userId LIMIT 1) as vendedor'),
                DB::raw('rt.id as route_id'),
                DB::raw('rt.name as ruta'),
                DB::raw('rt.sale_day as dia_venta'),
                DB::raw('COUNT(DISTINCT r.id) as cantidad_pedidos'),
                DB::raw('SUM(d.quantity * d.value) as total_ventas'),
                DB::raw("CASE WHEN EXISTS (
                    SELECT 1 FROM dis_deliveries_list dl WHERE dl.salesman_id = q.userId AND dl.route = rt.id
                ) THEN 'SI' ELSE 'NO' END as existe")
            )
            ->where('r.status', 'REGISTRADO')
            ->whereIn('rt.sale_day', $saleDays)
            ->groupBy(DB::raw('q.userId, rt.id, rt.name, rt.sale_day'))
            ->orderBy('rt.sale_day')
            ->orderBy('rt.name')
            ->orderBy('vendedor')
            ->get();

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


    public function cargar($userId, $routeId)
    {
        if (!$this->selectedDeliveryMan) {
            session()->flash('error', 'Por favor selecciona un transportador primero');
            return;
        }

        // Obtener TODAS las fechas distintas con remisiones pendientes para este vendedor+ruta
        $saleDates = DB::table('vnt_quotes as q')
            ->join('inv_remissions as r', 'q.id', '=', 'r.quoteId')
            ->join('vnt_warehouses as w', 'q.customerId', '=', 'w.id')
            ->join('vnt_companies as com', 'w.companyId', '=', 'com.id')
            ->join('tat_companies_routes as cr', 'com.id', '=', 'cr.company_id')
            ->where('q.userId', $userId)
            ->where('cr.route_id', $routeId)
            ->where('r.status', 'REGISTRADO')
            ->selectRaw('DISTINCT DATE(q.created_at) as sale_date')
            ->pluck('sale_date');

        if ($saleDates->isEmpty()) {
            session()->flash('error', 'No se pudo encontrar la ruta del vendedor');
            return;
        }

        try {
            // Crear un registro por cada fecha distinta que tenga pedidos pendientes
            foreach ($saleDates as $saleDate) {
                $exists = DisDeliveriesList::where('salesman_id', $userId)
                    ->where('route', $routeId)
                    ->where('sale_date', $saleDate)
                    ->exists();

                if (!$exists) {
                    DisDeliveriesList::create([
                        'sale_date'      => $saleDate,
                        'salesman_id'    => $userId,
                        'deliveryman_id' => $this->selectedDeliveryMan,
                        'route'          => $routeId,
                        'user_id'        => Auth::id(),
                        'created_at'     => Carbon::now(),
                    ]);
                }
            }

            // Forzar recarga completa de las remisiones
            if (!empty($this->selectedSaleDay)) {
                $this->remissions = $this->getRemissionsByDay($this->selectedSaleDay);
            }

            $this->refreshPreviewIfVisible();
            $this->dispatch('$refresh');

            session()->flash('message', "Cargando datos para el vendedor seleccionado en la ruta");
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

        try {
            // Obtener todos los vendedores de la ruta que no están cargados PARA ESTA RUTA
            $vendorsToLoad = DB::table('vnt_quotes as q')
                ->join('inv_remissions as r', 'q.id', '=', 'r.quoteId')
                ->join('vnt_warehouses as w', 'q.customerId', '=', 'w.id')
                ->join('vnt_companies as com', 'w.companyId', '=', 'com.id')
                ->join('tat_companies_routes as cr', 'com.id', '=', 'cr.company_id')
                ->where('cr.route_id', $routeId)
                ->where('r.status', 'REGISTRADO')
                ->whereNotExists(function ($query) use ($routeId) {
                    $query->select(DB::raw(1))
                        ->from('dis_deliveries_list as dl')
                        ->whereRaw('dl.salesman_id = q.userId')
                        ->where('dl.route', $routeId);
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
            if (!empty($this->selectedSaleDay)) {
                $this->remissions = $this->getRemissionsByDay($this->selectedSaleDay);
            }

            $this->refreshPreviewIfVisible();
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
                if (!empty($this->selectedSaleDay)) {
                    $this->remissions = $this->getRemissionsByDay($this->selectedSaleDay);
                }

                $this->refreshPreviewIfVisible();
                session()->flash('message', "Se eliminaron {$deleted} registro" . ($deleted != 1 ? 's' : '') . " de la ruta exitosamente");
            } else {
                session()->flash('warning', "No se encontraron registros de esta ruta para eliminar");
            }
        } catch (\Exception $e) {
            Log::error($e);
            session()->flash('error', "Error al eliminar los registros de la ruta: " . $e->getMessage());
        }
    }

    public function eliminar($userId, $routeId)
    {
        try {
            // Buscar y eliminar el registro del vendedor en la ruta específica
            $deleted = DisDeliveriesList::where('salesman_id', $userId)
                ->where('route', $routeId)
                ->delete();

            if ($deleted) {
                // Recargar los datos de la tabla
                if (!empty($this->selectedSaleDay)) {
                    $this->remissions = $this->getRemissionsByDay($this->selectedSaleDay);
                }

                $this->refreshPreviewIfVisible();
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
                    INNER JOIN vnt_warehouses w ON q.customerId = w.id
                    INNER JOIN vnt_companies com ON w.companyId = com.id
                    INNER JOIN tat_companies_routes cr ON com.id = cr.company_id AND dl.route = cr.route_id
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

                $firstItem = $infoDisDeliveriesList->first();

                // Crear UN SOLO dis_deliveries para todo el cargue
                $dis_deliveries = DisDeliveries::create([
                    'salesman_id'    => $firstItem->salesman_id,
                    'deliveryman_id' => $firstItem->deliveryman_id,
                    'user_id'        => Auth::id(),
                    'sale_date'      => $firstItem->sale_date,
                    'created_at'     => Carbon::now(),
                ]);

                // Obtener TODAS las remisiones de todas las rutas marcadas por este usuario
                $remissionIds = DB::table('inv_remissions as r')
                    ->join('vnt_quotes as q', 'r.quoteId', '=', 'q.id')
                    ->join('dis_deliveries_list as dl', function ($join) {
                        $join->on('q.userId', '=', 'dl.salesman_id')
                            ->on(DB::raw('DATE(q.created_at)'), '=', 'dl.sale_date');
                    })
                    ->join('vnt_warehouses as w', 'q.customerId', '=', 'w.id')
                    ->join('vnt_companies as com', 'w.companyId', '=', 'com.id')
                    ->join('tat_companies_routes as cXr', 'com.id', '=', 'cXr.company_id')
                    ->whereRaw('dl.route = cXr.route_id')
                    ->where('dl.user_id', Auth::id())
                    ->where('r.status', 'REGISTRADO')
                    ->pluck('r.id')
                    ->unique();

                // Actualizar TODAS las remisiones con el mismo delivery_id
                if ($remissionIds->isNotEmpty()) {
                    InvRemissions::whereIn('id', $remissionIds)
                        ->update([
                            'delivery_id'  => $dis_deliveries->id,
                            'deliveryDate' => $dis_deliveries->sale_date,
                            'status'       => 'EN RECORRIDO',
                        ]);
                }

                Log::info('Cargue confirmado (único):', [
                    'delivery_id'           => $dis_deliveries->id,
                    'rutas'                 => $infoDisDeliveriesList->pluck('route')->unique()->values(),
                    'remisiones_actualizadas' => $remissionIds->count(),
                ]);

                $this->clearListUpload();
                $this->resetAfterConfirm();
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
            ->join('vnt_warehouses as w', 'q.customerId', '=', 'w.id')
            ->join('vnt_companies as com', 'w.companyId', '=', 'com.id')
            ->join('tat_companies_routes as cr', function($join) {
                $join->on('com.id', '=', 'cr.company_id')
                    ->on('dl.route', '=', 'cr.route_id');
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

    public function resetAfterConfirm()
    {
        $this->selectedDeliveryMan = '';
        $this->selectedSaleDay = [];
        $this->remissions = [];
        $this->showPreviewCharge = false;
        $this->previewItems = [];
        $this->showScares = false;
        $this->scarceUnits = [];
        $this->showConfirmModal = false;
        $this->showFooter = true;
        $this->showClearOptions = false;
    }

    public function showPreCharge()
    {
        if (!$this->selectedDeliveryMan) {
            session()->flash('error', 'Por favor selecciona un transportador primero');
            return;
        }

        try {
            $this->previewItems = $this->fetchPreviewItems();
            $this->showPreviewCharge = true;
        } catch (\Exception $e) {
            Log::error($e);
            session()->flash('error', "Error al obtener los datos de la previa: " . $e->getMessage());
        }
    }

    private function fetchPreviewItems(): array
    {
        return DB::table('inv_detail_remissions as d')
            ->join('inv_remissions as r', 'd.remissionId', '=', 'r.id')
            ->join('inv_items as it', 'd.itemId', '=', 'it.id')
            ->join('vnt_quotes as q', 'r.quoteId', '=', 'q.id')
            ->join('vnt_warehouses as w', 'q.customerId', '=', 'w.id')
            ->join('vnt_companies as c', 'w.companyId', '=', 'c.id')
            ->join('tat_companies_routes as cXr', 'c.id', '=', 'cXr.company_id')
            ->join('tat_routes as ro', 'cXr.route_id', '=', 'ro.id')
            ->join('inv_categories as cat', 'it.categoryId', '=', 'cat.id')
            ->leftJoin('inv_items_store as its', 'it.id', '=', 'its.itemId')
            ->join('dis_deliveries_list as dl', function ($join) {
                $join->on('q.userId', '=', 'dl.salesman_id')
                    ->on('ro.id', '=', 'dl.route')
                    ->on(DB::raw('DATE(q.created_at)'), '=', 'dl.sale_date');
            })
            ->where('dl.deliveryman_id', $this->selectedDeliveryMan)
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
            ->get()
            ->toArray();
    }

    private function refreshPreviewIfVisible(): void
    {
        if ($this->showPreviewCharge && $this->selectedDeliveryMan) {
            $this->previewItems = $this->fetchPreviewItems();
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


    public function render()
    {
        $users = DB::table('users')->select('id', 'name')->where('profile_id', 13)->get();

        $busyDeliverymen = DisDeliveries::where('status', '!=', 'CERRADO')
            ->whereNull('deleted_at')
            ->pluck('deliveryman_id')
            ->unique()
            ->toArray();

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
            'busyDeliverymen' => $busyDeliverymen,
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
