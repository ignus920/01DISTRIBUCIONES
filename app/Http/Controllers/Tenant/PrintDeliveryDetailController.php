<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class PrintDeliveryDetailController extends Controller
{
    /**
     * Muestra la vista de impresión del detalle de una entrega
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

        // Obtener items con la consulta SQL ordenados por categoría
        $items = DB::table('dis_deliveries as dl')
            ->join('inv_remissions as r', function($join) {
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
            ->groupBy('c.id', 'i.id', 'dt.value')
            ->orderBy('c.name')
            ->orderBy('i.name')
            ->get();

        // Calcular el total
        $total = collect($items)->sum('subtotal');

        return view('tenant.uploads.print-detail', compact('delivery', 'items', 'total'));
    }
}
