<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class PrintPreChargeController extends Controller
{
    public function show($deliverymanId)
    {
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
            ->join('dis_deliveries_list as dl', function ($join) {
                $join->on('q.userId', '=', 'dl.salesman_id')
                    ->on('ro.id', '=', 'dl.route')
                    ->on(DB::raw('DATE(q.created_at)'), '=', 'dl.sale_date');
            })
            ->where('dl.deliveryman_id', $deliverymanId)
            ->select(
                'it.internal_code as code',
                'cat.name as category',
                'it.name as name_item',
                DB::raw('SUM(d.quantity) as quantity'),
                DB::raw('COALESCE(its.stock_items_store, 0) as stockActual')
            )
            ->groupBy('cat.id', 'cat.name', 'it.id', 'it.name', 'its.stock_items_store', 'it.internal_code')
            ->orderBy('cat.name')
            ->orderBy('it.name')
            ->get()
            ->map(fn($row) => (array) $row)
            ->toArray();

        return view('tenant.uploads.pre-charge-pdf', compact('items'));
    }
}
