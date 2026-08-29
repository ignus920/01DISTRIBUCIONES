<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Helpers\NumberToWordsHelper;

class DeliveryOrdersReportController extends Controller
{
    /**
     * Muestra el PDF de pedidos de un cargue específico para previsualización
     */
    public function showPDF($deliveryId)
    {
        try {
            // Consulta idéntica a la original pero forzando conexión tenant y trayendo nombre de vendedor
            $orders = DB::connection('tenant')->table('inv_remissions as r')
                ->join('inv_detail_remissions as dt', 'dt.remissionId', '=', 'r.id')
                ->join('inv_items as i', 'i.id', '=', 'dt.itemId')
                ->join('inv_items_store as its', 'i.id', '=', 'its.itemId')
                ->join('inv_categories as c', 'i.categoryId', '=', 'c.id')
                ->join('vnt_quotes as vq', 'vq.id', '=', 'r.quoteId')
                ->join('vnt_warehouses as vw', 'vq.customerId', '=', 'vw.id')
                ->join('vnt_companies as vc', 'vw.companyId', '=', 'vc.id')
                ->join('vnt_contacts as v_c', 'v_c.warehouseId', '=', 'vw.id')
                ->join('tat_companies_routes as t_c_r', 't_c_r.company_id', '=', 'vc.id')
                ->join('tat_routes as t_r', 't_r.id', '=', 't_c_r.route_id')
                ->join('users as u', 'u.id', '=', 'r.userId') // Join para el nombre del vendedor
                ->where('r.delivery_id', $deliveryId)
                ->whereNotNull('vc.identification')
                ->whereNotNull('vw.district')
                ->whereNotNull('vw.address')
                ->whereNotNull('v_c.business_phone')
                ->whereNotNull('t_r.sale_day')
                ->select(
                    DB::raw("IF(vc.typePerson = 'PERSON_ENTITY', CONCAT(vc.firstName, ' ', vc.lastName), vc.businessName) AS customerName"),
                    'vc.identification',
                    'vw.district',
                    'vw.address',
                    'v_c.business_phone',
                    'u.name as salesPerson', // Cambiado r.userId por u.name
                    't_r.sale_day',
                    'r.id as remission_id',
                    'r.created_at as order_date',
                    'i.id as code',
                    'c.name as category',
                    'i.name as name_item',
                    'dt.quantity as quantity',
                    'dt.value as unit_price',
                    DB::raw('dt.quantity * dt.value as subtotal')
                )
                ->orderBy('r.id')
                ->get();

            if ($orders->isEmpty()) {
                return "No se encontraron pedidos para este cargue.";
            }

            // Agrupar por remisión (pedido)
            $customerOrders = [];
            foreach ($orders as $order) {
                $key = $order->remission_id;

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
                        'order_date' => $order->order_date,
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
                    'unit_price' => $order->unit_price,
                    'subtotal' => $order->subtotal
                ];

                $customerOrders[$key]['subtotal'] += $order->subtotal;
            }

            // Calcular totales y convertir a letras
            foreach ($customerOrders as &$customerOrder) {
                $customerOrder['total'] = $customerOrder['subtotal'] + $customerOrder['iva'];
                $customerOrder['totalInWords'] = NumberToWordsHelper::convert($customerOrder['total']);
            }

            $data = [
                'customerOrders' => $customerOrders,
                'deliveryId' => $deliveryId,
            ];

            $pdf = PDF::loadView('tenant.uploads.print-orders-detail', $data);
            return $pdf->stream("Pedidos-Cargue-{$deliveryId}.pdf");

        } catch (\Exception $e) {
            Log::error("Error al generar previsualización de pedidos: " . $e->getMessage());
            return "Error al generar el reporte: " . $e->getMessage();
        }
    }

    /**
     * Muestra el PDF de la lista de mercancía (consolidado) de un cargue
     */
    public function showDetailPDF($deliveryId)
    {
        try {
            $items = DB::connection('tenant')->table('inv_remissions as r')
                ->join('inv_detail_remissions as dt', 'dt.remissionId', '=', 'r.id')
                ->join('inv_items as i', 'i.id', '=', 'dt.itemId')
                ->join('inv_categories as c', 'i.categoryId', '=', 'c.id')
                ->where('r.delivery_id', $deliveryId)
                ->select(
                    'i.id as code',
                    'c.name as category',
                    'i.name as name_item',
                    DB::raw('SUM(dt.quantity) as quantity'),
                    DB::raw('SUM(dt.quantity * dt.value) as subtotal')
                )
                ->groupBy('i.id', 'c.name', 'i.name')
                ->orderBy('c.name')
                ->orderBy('i.name')
                ->get();

            $total = collect($items)->sum('subtotal');
            $pedidosCount = DB::connection('tenant')->table('inv_remissions')
                ->where('delivery_id', $deliveryId)
                ->count();

            $data = [
                'items' => $items,
                'total' => $total,
                'deliveryId' => $deliveryId,
                'pedidosCount' => $pedidosCount,
            ];

            $pdf = PDF::loadView('tenant.uploads.print-detail', $data);
            return $pdf->stream("Cargue-Mercancia-#{$deliveryId}.pdf");

        } catch (\Exception $e) {
            Log::error("Error al generar detalle de cargue: " . $e->getMessage());
            return "Error al generar el reporte: " . $e->getMessage();
        }
    }

    /**
     * Muestra el PDF de un pedido individual en formato Media Carta
     */
    public function showSingleOrderPDF($remissionId)
    {
        try {
            $orders = DB::connection('tenant')->table('inv_remissions as r')
                ->join('inv_detail_remissions as dt', 'dt.remissionId', '=', 'r.id')
                ->join('inv_items as i', 'i.id', '=', 'dt.itemId')
                ->leftJoin('inv_items_store as its', 'i.id', '=', 'its.itemId')
                ->leftJoin('inv_categories as c', 'i.categoryId', '=', 'c.id')
                ->join('vnt_quotes as vq', 'vq.id', '=', 'r.quoteId')
                ->join('vnt_warehouses as vw', 'vq.customerId', '=', 'vw.id')
                ->join('vnt_companies as vc', 'vw.companyId', '=', 'vc.id')
                ->leftJoin('vnt_contacts as v_c', 'v_c.warehouseId', '=', 'vw.id')
                ->leftJoin('tat_companies_routes as t_c_r', 't_c_r.company_id', '=', 'vc.id')
                ->leftJoin('tat_routes as t_r', 't_r.id', '=', 't_c_r.route_id')
                ->join('users as u', 'u.id', '=', 'r.userId')
                ->where('r.id', $remissionId)
                ->select(
                    DB::raw("IF(vc.typePerson = 'PERSON_ENTITY', CONCAT(vc.firstName, ' ', vc.lastName), vc.businessName) AS customerName"),
                    'vc.identification',
                    'vw.district',
                    'vw.address',
                    'v_c.business_phone',
                    'u.name as salesPerson',
                    't_r.sale_day',
                    'r.id as remission_id',
                    'r.created_at as order_date',
                    'i.id as code',
                    'c.name as category',
                    'i.name as name_item',
                    'dt.quantity as quantity',
                    'dt.value as unit_price',
                    DB::raw('dt.quantity * dt.value as subtotal')
                )
                ->get();

            if ($orders->isEmpty()) {
                return "No se encontró el pedido #{$remissionId}.";
            }

            $customerOrders = [];
            foreach ($orders as $order) {
                $key = $order->remission_id;

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
                        'order_date' => $order->order_date,
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
                    'unit_price' => $order->unit_price,
                    'subtotal' => $order->subtotal
                ];

                $customerOrders[$key]['subtotal'] += $order->subtotal;
            }

            foreach ($customerOrders as &$customerOrder) {
                $customerOrder['total'] = $customerOrder['subtotal'] + $customerOrder['iva'];
                $customerOrder['totalInWords'] = NumberToWordsHelper::convert($customerOrder['total']);
            }

            $data = [
                'customerOrders' => $customerOrders,
                'deliveryId' => 'Individual',
            ];

            $pdf = PDF::loadView('tenant.uploads.print-orders-detail', $data);
            return $pdf->stream("Pedido-{$remissionId}.pdf");

        } catch (\Exception $e) {
            Log::error("Error al generar impresión de pedido individual: " . $e->getMessage());
            return "Error al generar el reporte: " . $e->getMessage();
        }
    }
}
