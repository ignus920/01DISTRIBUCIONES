<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Helpers\NumberToWordsHelper;

class PrintOrdersDetailController extends Controller
{
    /**
     * Muestra la vista de impresión del detalle de pedidos por cliente
     */
    public function show($deliveryId)
    {
        // Obtener información de la entrega
        $delivery = DB::table('dis_deliveries')
            ->where('id', $deliveryId)
            ->first();

        if (!$delivery) {
            abort(404, 'Entrega no encontrada');
        }

        // Obtener datos agrupados por cliente usando el query proporcionado
        $orders = DB::table('dis_deliveries as dl')
            ->join('inv_remissions as r', function($join) {
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

        return view('tenant.uploads.print-orders-detail', compact('delivery', 'customerOrders'));
    }
}
