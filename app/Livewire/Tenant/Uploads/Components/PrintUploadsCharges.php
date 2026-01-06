<?php

namespace App\Livewire\Tenant\Uploads\Components;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Tenant\DeliveriesList\DisDeliveries;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Helpers\NumberToWordsHelper;

class PrintUploadsCharges extends Component
{
    use WithPagination;

    public $perPage = 10;

    /**
     * Resetea la paginación cuando cambia el número de items por página
     */
    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function printDetail($deliveryId)
    {
        try {
            // Obtener items con la consulta SQL ordenados por categoría
            $items = DB::table('dis_deliveries as dl')
                ->join('inv_remissions as r', function ($join) {
                    $join->on('dl.salesman_id', '=', 'r.userId')
                        ->whereRaw('DATE(r.deliveryDate) = dl.sale_date');
                })
                ->join('inv_detail_remissions as dt', 'dt.remissionId', '=', 'r.id')
                ->join('inv_items as i', 'i.id', '=', 'dt.itemId')
                ->join('inv_items_store as its', 'i.id', '=', 'its.itemId')
                ->join('inv_categories as c', 'i.categoryId', '=', 'c.id')
                ->where('r.delivery_id', $deliveryId)
                ->select(
                    'i.id as code',
                    'c.name as category',
                    'i.name as name_item',
                    DB::raw('SUM(dt.quantity) as quantity'),
                    DB::raw('SUM(dt.quantity) * dt.value as subtotal')
                )
                ->groupBy('c.id', 'c.name', 'i.id', 'i.name', 'dt.value')
                ->orderBy('c.name')
                ->orderBy('i.name')
                ->get();

            // Calcular el total
            $total = collect($items)->sum('subtotal');

            //Calcular cantidad de pedidos
            $pedidosCount = DB::table('dis_deliveries as dl')
                ->join('inv_remissions as r', function ($join) {
                    $join->on('dl.salesman_id', '=', 'r.userId')
                        ->whereRaw('DATE(r.deliveryDate) = dl.sale_date');
                })
                ->where('r.delivery_id', $deliveryId)
                ->distinct('r.id')
                ->count('r.id');

            $cleanedItems = $this->cleanUtf8Data($items);
            $cleanedTotal = $this->cleanString((string)$total);
            $cleanedPedidosCount = $this->cleanString((string)$pedidosCount);

            $data = [
                'items' => $cleanedItems,
                'total' => $cleanedTotal,
                'deliveryId' => $deliveryId,
                'pedidosCount' => $cleanedPedidosCount,
            ];

            $pdf = PDF::loadView('tenant.uploads.print-detail', $data);
            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->stream();
            }, "Cargue-de-ventas-#{$deliveryId}.pdf");
        } catch (\Exception $e) {
            Log::error($e);
            session()->flash('error', "Error al generar la impresión: " . $e->getMessage());
        }
    }

    public function printOrders($deliveryId)
    {
        try {
            $orders = DB::table('dis_deliveries as dl')
                ->join('inv_remissions as r', function ($join) {
                    $join->on('dl.salesman_id', '=', 'r.userId')
                        ->whereRaw('DATE(r.deliveryDate) = DATE(dl.sale_date)');
                })
                ->join('inv_detail_remissions as dt', 'dt.remissionId', '=', 'r.id')
                ->join('inv_items as i', 'i.id', '=', 'dt.itemId')
                ->join('inv_items_store as its', 'i.id', '=', 'its.itemId')
                ->join('inv_categories as c', 'i.categoryId', '=', 'c.id')
                ->leftJoin('users as u', 'dl.user_id', '=', 'u.id')
                ->join('vnt_quotes as vq', 'vq.id', '=', 'r.quoteId')
                ->join('vnt_companies as vc', 'vc.id', '=', 'vq.customerId')
                ->join('vnt_warehouses as vw', 'vw.companyId', '=', 'vc.id')
                ->join('vnt_contacts as v_c', 'v_c.warehouseId', '=', 'vw.id')
                ->join('tat_companies_routes as t_c_r', 't_c_r.company_id', '=', 'vc.id')
                ->join('tat_routes as t_r', 't_r.id', '=', 't_c_r.route_id')
                ->where('r.delivery_id', $deliveryId)
                ->whereNotNull('vc.identification')
                ->whereNotNull('vw.district')
                ->whereNotNull('vw.address')
                ->whereNotNull('v_c.business_phone')
                ->whereNotNull('t_r.sale_day')
                ->select(
                    // Cabecera de la factura
                    DB::raw("IF(vc.typePerson = 'PERSON_ENTITY', CONCAT(vc.firstName, ' ', vc.lastName), vc.businessName) AS customerName"),
                    'vc.identification',
                    'vw.district',
                    'vw.address',
                    'v_c.business_phone',
                    'u.name as salesPerson',
                    't_r.sale_day',
                    'r.id as remission_id',
                    // Observaciones (items)
                    'i.id as code',
                    'c.name as category',
                    'i.name as name_item',
                    DB::raw('SUM(dt.quantity) as quantity'),
                    DB::raw('SUM(dt.quantity * dt.value) as subtotal')
                )
                ->groupBy(
                    DB::raw("IF(vc.typePerson = 'PERSON_ENTITY', CONCAT(vc.firstName, ' ', vc.lastName), vc.businessName)"),
                    'vc.identification',
                    'vw.district',
                    'vw.address',
                    'v_c.business_phone',
                    'u.name',
                    't_r.sale_day',
                    'r.id',
                    'i.id',
                    'c.name',
                    'i.name'
                )
                ->orderBy('customerName')
                ->orderBy('c.name')
                ->orderBy('i.name')
                ->get();

            // Agrupar por cliente
            $customerOrders = [];
            foreach ($orders as $order) {
                $key = $order->customerName . '_' . $order->identification;

                if (!isset($customerOrders[$key])) {
                    $customerOrders[$key] = [
                        'customer' => [
                            'name' => $order->customerName,
                            'identification' => $order->identification,
                            'district' => $order->district,
                            'address' => $order->address,
                            'phone' => $order->business_phone,
                            'salesPerson' => $order->salesPerson,
                            'saleDay' => $order->sale_day,
                            'remission_id' => $order->remission_id,
                        ],
                        'items' => [],
                        'subtotal' => 0,
                        'iva' => 0,
                        'total' => 0
                    ];
                }

                $customerOrders[$key]['items'][] = [
                    'code' => $order->code,
                    'category' => $order->category,
                    'name' => $order->name_item,
                    'quantity' => $order->quantity,
                    'subtotal' => $order->subtotal
                ];

                $customerOrders[$key]['subtotal'] += $order->subtotal;
            }

            // Calcular totales y convertir a letras
            foreach ($customerOrders as &$customerOrder) {
                $customerOrder['total'] = $customerOrder['subtotal'] + $customerOrder['iva'];
                $customerOrder['totalInWords'] = NumberToWordsHelper::convert($customerOrder['total']);
            }

            // Limpiar datos UTF-8
            $cleanedCustomerOrders = $this->cleanUtf8Data($customerOrders);
            $cleanedDeliveryId = $this->cleanString((string)$deliveryId);

            $data = [
                'customerOrders' => $cleanedCustomerOrders,
                'deliveryId' => $cleanedDeliveryId,
            ];

            $pdf = PDF::loadView('tenant.uploads.print-orders-detail', $data);
            return response()->streamDownload(function () use ($pdf) {
                echo $pdf->stream();
            }, "Ordenes-de-venta-#{$deliveryId}.pdf");
        } catch (\Exception $e) {
            Log::error($e);
            session()->flash('error', "Error al generar la impresión: " . $e->getMessage());
        }
    }

    public function render()
    {
        $deliveries = DisDeliveries::query()
            ->select('id', 'sale_date', 'user_id', 'salesman_id')
            ->orderBy('sale_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($this->perPage);

        return view('livewire.tenant.uploads.components.print-uploads-charges', [
            'deliveries' => $deliveries
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
