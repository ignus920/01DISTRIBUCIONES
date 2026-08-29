<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesReportController extends Controller
{
    public function downloadPDF(Request $request, $salesmanId)
    {
        try {
            $startDate = $request->query('start');
            $endDate   = $request->query('end');

            $salesDetail = $this->getSalesDetail($salesmanId, $startDate, $endDate);

            if ($salesDetail->isEmpty()) {
                abort(404, 'No hay datos para generar el PDF.');
            }

            $vendorName = $salesDetail->first()->vendedor;

            if ($startDate && $endDate) {
                $dateRange = Carbon::parse($startDate)->format('d/m/Y') . ' - ' . Carbon::parse($endDate)->format('d/m/Y');
            } elseif ($startDate) {
                $dateRange = 'Desde ' . Carbon::parse($startDate)->format('d/m/Y');
            } elseif ($endDate) {
                $dateRange = 'Hasta ' . Carbon::parse($endDate)->format('d/m/Y');
            } else {
                $dateRange = 'Todas las fechas';
            }

            $filename = 'detalle_ventas_' . str_replace(' ', '_', $vendorName) . '_' . now()->format('Y-m-d_His') . '.pdf';

            $pdf = Pdf::loadView('pdf.sales-detail', [
                'salesDetail' => $salesDetail,
                'vendorName'  => $vendorName,
                'dateRange'   => $dateRange,
            ]);

            return $pdf->download($filename);

        } catch (\Throwable $e) {
            \Log::error('Error SalesReportController@downloadPDF: ' . $e->getMessage());
            abort(500, 'Error al generar el PDF: ' . $e->getMessage());
        }
    }

    private function getSalesDetail($salesmanId, $startDate, $endDate)
    {
        $db = DB::connection('tenant');

        if ($salesmanId == 0) {
            $query = $db->table('inv_detail_remissions as idr')
                ->join('inv_remissions as sta', 'sta.id', '=', 'idr.remissionId')
                ->leftJoin('vnt_quotes as vq', 'vq.id', '=', 'sta.quoteId')
                ->leftJoin('vnt_companies as vntc', 'vntc.id', '=', 'vq.customerId')
                ->select(
                    DB::raw("'SIS' as pedido"),
                    'sta.id as remission_id',
                    'sta.status as estado',
                    DB::raw("'Ventas por Sistema' as vendedor"),
                    DB::raw("COALESCE(vntc.firstName, 'Sistema') as cliente"),
                    'sta.created_at as fecha',
                    DB::raw("SUM(CASE WHEN sta.status = 'DEVOLUCION' THEN (idr.quantity - idr.cant_return) * idr.value ELSE idr.quantity * idr.value END) as subtotal"),
                    DB::raw("CASE WHEN sta.status = 'DEVOLUCION' THEN 1 ELSE 0 END as devolucion")
                )
                ->where('sta.systemOrder', 1);

            if ($startDate) $query->whereDate('sta.created_at', '>=', $startDate);
            if ($endDate)   $query->whereDate('sta.created_at', '<=', $endDate);

            $query->groupBy('sta.id', 'sta.status', 'vntc.firstName', 'sta.created_at',
                DB::raw("CASE WHEN sta.status = 'DEVOLUCION' THEN 1 ELSE 0 END"));
            $query->orderBy('sta.created_at', 'desc');

            return $query->get();
        }

        $query = $db->table('inv_detail_remissions as idr')
            ->join('inv_remissions as sta', 'sta.id', '=', 'idr.remissionId')
            ->join('vnt_quotes as vq', 'vq.id', '=', 'sta.quoteId')
            ->join('vnt_companies as vntc', 'vntc.id', '=', 'vq.customerId')
            ->join('dis_deliveries as dd', 'dd.id', '=', 'sta.delivery_id')
            ->join('users as uv', 'uv.id', '=', 'dd.salesman_id')
            ->select(
                'dd.id as pedido',
                'sta.id as remission_id',
                'sta.status as estado',
                'uv.name as vendedor',
                'vntc.firstName as cliente',
                'dd.sale_date as fecha',
                DB::raw("SUM(CASE WHEN sta.status = 'DEVOLUCION' THEN (idr.quantity - idr.cant_return) * idr.value ELSE idr.quantity * idr.value END) as subtotal"),
                DB::raw("CASE WHEN sta.status = 'DEVOLUCION' THEN 1 ELSE 0 END as devolucion")
            )
            ->whereNotNull('sta.delivery_id')
            ->where('dd.salesman_id', $salesmanId);

        if ($startDate) $query->whereDate('dd.sale_date', '>=', $startDate);
        if ($endDate)   $query->whereDate('dd.sale_date', '<=', $endDate);

        $query->groupBy('dd.id', 'sta.id', 'sta.status', 'uv.name', 'vntc.firstName', 'dd.sale_date',
            DB::raw("CASE WHEN sta.status = 'DEVOLUCION' THEN 1 ELSE 0 END"));
        $query->orderBy('dd.sale_date', 'desc');

        return $query->get();
    }
}
