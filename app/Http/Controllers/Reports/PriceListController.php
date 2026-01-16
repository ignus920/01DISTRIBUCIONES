<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class PriceListController extends Controller
{
    /**
     * Generar y descargar PDF de lista de precios
     */
    public function downloadPDF()
    {
        // Obtener datos de la lista de precios
        $priceListData = DB::table('inv_categories as ic')
            ->join('inv_items as ii', 'ic.id', '=', 'ii.categoryId')
            ->join('inv_values as iv', 'iv.itemId', '=', 'ii.id')
            ->select(
                'ic.name as categoria',
                'ii.name as item',
                'iv.values as precio'
            )
            ->where('iv.label', 'Precio Regular')
            ->orderBy('ic.name')
            ->orderBy('ii.name')
            ->get();
        
        if ($priceListData->isEmpty()) {
            abort(404, 'No hay datos disponibles para generar la lista de precios.');
        }

        // Agrupar por categoría para mejor presentación
        $groupedData = $priceListData->groupBy('categoria');

        $pdf = Pdf::loadView('pdf.price-list', [
            'groupedData' => $groupedData,
            'totalItems' => $priceListData->count()
        ]);

        $filename = 'lista_precios_' . now()->format('Y-m-d_His') . '.pdf';
        
        return $pdf->download($filename);
    }
}
