<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Remissions\InvRemissions;
use App\Models\Central\VntCompany;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class RemissionReportController extends Controller
{
    /**
     * Generar y visualizar el PDF de una remisión específica
     * 
     * @param int $id ID de la remisión
     */
    public function downloadPDF($id)
    {
        try {
            // Cargar la remisión con todas sus relaciones necesarias
            $remission = InvRemissions::with([
                'details.item', 
                'quote.customer.company', 
                'quote.warehouse', 
                'user'
            ])->findOrFail($id);

            // Intentar obtener la información de la empresa (Tenant)
            // En un sistema multi-tenant, solemos tener una empresa principal configurada
            $company = VntCompany::first(); 

            // Si la remisión tiene warehouseId, podemos ser más específicos
            if ($remission->warehouseId) {
                $warehouse = \App\Models\Central\VntWarehouse::find($remission->warehouseId);
                if ($warehouse && $warehouse->company) {
                    $company = $warehouse->company;
                }
            }

            $customer = $remission->quote->customer ?? null;

            $data = [
                'quote' => $remission, // La vista 'print-remission-carta' espera la remisión como $quote
                'customer' => $customer,
                'company' => (object) [
                    'businessName' => $company->businessName ?? $company->getFullNameAttribute() ?? 'DISTRIBUIDORA',
                    'identification' => $company->identification ?? 'N/A',
                    'billingAddress' => $remission->quote->warehouse->address ?? $company->billingAddress ?? '',
                    'phone' => $company->phone ?? '3000000000',
                    'billingEmail' => $company->billingEmail ?? 'contacto@distribuidora.com'
                ],
                'documentTitle' => 'REMISIÓN DE VENTA',
                'showQR' => true,
                'defaultObservations' => $remission->observations_return ?? 'Gracias por su compra.'
            ];

            // Cargar la vista diseñada previamente para el cotizador
            $pdf = Pdf::loadView('livewire.tenant.remissions.print.print-remission-carta', $data);
            
            // Establecer el nombre del archivo
            $filename = "Remision_{$remission->consecutive}.pdf";

            // Devolver el stream para que se vea en el navegador o se comparta el link
            return $pdf->stream($filename);

        } catch (\Exception $e) {
            \Log::error("❌ Error en RemissionReportController@downloadPDF: " . $e->getMessage());
            abort(500, "Error al generar el reporte: " . $e->getMessage());
        }
    }
}
