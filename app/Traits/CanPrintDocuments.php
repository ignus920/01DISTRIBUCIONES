<?php

namespace App\Traits;

use App\Models\Tenant\Remissions\InvRemissions;
use App\Models\Central\VntWarehouse;
use Illuminate\Support\Facades\Log;

trait CanPrintDocuments
{
    /**
     * Lógica centralizada para imprimir una remisión.
     * Soporta formatos POS y Carta según configuración.
     */
    public function printRemission($id)
    {
        Log::info('🖨️ [CanPrintDocuments] Iniciando impresión de remisión', ['id' => $id]);

        $this->ensureConfigurationInitialized();

        try {
            // Cargar la remisión con relaciones necesarias
            $remission = InvRemissions::with(['details.item', 'quote.customer', 'user'])->findOrFail($id);
            
            // Obtener información de la empresa (Warehouse central)
            $company = $this->getCompanyInfoForPrint($remission);
            
            // Determinar formato (Opc 3: 0=POS, 1=Carta)
            $printFormat = (int)($this->getOptionValue(3) ?? 0);

            $data = [
                'quote' => $remission, // La vista espera 'quote'
                'customer' => $remission->quote->customer ?? null,
                'company' => $company,
                'documentTitle' => 'REMISIÓN',
                'showQR' => true,
                'defaultObservations' => $remission->observations_return ?? 'Gracias por su compra.'
            ];

            // Seleccionar vista según formato
            $viewName = ($printFormat === 1)
                ? 'livewire.tenant.remissions.print.print-remission-carta'
                : 'livewire.tenant.remissions.print.print-remission-pos';

            $html = view($viewName, $data)->render();

            // Guardar HTML temporal
            $tempFileName = 'quote_' . $id . '_' . time() . '.html';
            $tempPath = storage_path('app/temp/' . $tempFileName);

            if (!file_exists(dirname($tempPath))) {
                mkdir(dirname($tempPath), 0755, true);
            }

            file_put_contents($tempPath, $html);

            $printUrl = route('quoter.print.temp', ['file' => $tempFileName]);

            // Despachar evento para el navegador
            $this->dispatch('open-print-window', [
                'url' => $printUrl,
                'format' => $printFormat === 1 ? 'carta' : 'pos'
            ]);

            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => 'Impresión preparada para la remisión #' . $remission->consecutive
            ]);

        } catch (\Exception $e) {
            Log::error('❌ [CanPrintDocuments] Error en printRemission: ' . $e->getMessage());
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Error al preparar impresión: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Obtiene información de la empresa desde el warehouse central.
     */
    private function getCompanyInfoForPrint($model)
    {
        if ($model && $model->warehouseId) {
            try {
                // Consultar directamente desde la base central
                $warehouse = VntWarehouse::with('company')->find($model->warehouseId);
                if ($warehouse) {
                    $company = $warehouse->company;
                    return (object) [
                        'businessName' => $company->businessName ?? $warehouse->name ?? 'DISTRIBUIDORA',
                        'identification' => $company->identification ?? 'N/A',
                        'billingAddress' => $warehouse->address ?? $company->billingAddress ?? '',
                        'phone' => $company->phone ?? $company->billingPhone ?? '',
                        'billingEmail' => $company->billingEmail ?? ''
                    ];
                }
            } catch (\Exception $e) {
                Log::error('❌ [CanPrintDocuments] Error consultando warehouse central: ' . $e->getMessage());
            }
        }

        // Fallback genérico
        return (object) [
            'businessName' => 'DISTRIBUIDORA',
            'identification' => 'N/A',
            'billingAddress' => '',
            'phone' => '',
            'billingEmail' => ''
        ];
    }
}
