<?php

namespace App\Livewire\Tenant\Invoices;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Tenant\Invoices\VntInvoices;
use App\Models\Tenant\Invoices\VntInvoicePayments;
use App\Models\Tenant\Invoices\VntInvoicesXsales;
use App\Models\Tenant\PettyCash\PettyCash as PettyCashModel;
use App\Models\Tenant\PettyCash\VntDetailPettyCash;
use App\Services\Tenant\TenantManager;
use App\Models\Auth\Tenant;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Services\Facturacion\FacturacionService;
use App\Services\Facturacion\TenantConfigManager;
use App\Traits\HasCompanyConfiguration;
use App\Services\Factus\FactusClient;
use App\Services\Factus\FactusApiException;

class Invoices extends Component
{
    use WithPagination, HasCompanyConfiguration;

    public $search = '';
    public $perPage = 12;
    public $sortField = 'vnt_invoices.invoiceNumber';
    public $sortDirection = 'desc';
    
    // Filtros
    public $fromDate = '';
    public $toDate = '';

    // ─── Nota Crédito ─────────────────────────────────────────────────────────
    public bool $showCreditNoteModal = false;
    public ?array $creditNoteInvoiceData = null;
    public array $creditNoteItems = [];
    public string $correctionConceptCode = '2';
    public string $creditNotePaymentMethod = '10';
    public string $creditNoteObservation = '';
    public float $creditNoteTotal = 0;
    public bool $creditNoteLoading = false;

    public function boot()
    {
        $this->ensureTenantConnection();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingPerPage()
    {
        $this->resetPage();
    }

    public function updatingFromDate()
    {
        $this->resetPage();
    }

    public function updatingToDate()
    {
        $this->resetPage();
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

    public function mount()
    {
        $this->ensureTenantConnection();
        $this->initializeCompanyConfiguration();

        // Inicializar fechas por defecto (últimos 20 días) si están vacías
        if (empty($this->fromDate)) {
            $this->fromDate = now()->subDays(20)->format('Y-m-d');
        }
        if (empty($this->toDate)) {
            $this->toDate = now()->format('Y-m-d');
        }
    }

    /**
     * Procesar pago de una factura solo en ERP local (sin API externa).
     */
    public function payInvoice($invoiceId)
    {
        $this->ensureTenantConnection();

        try {
            $invoice = VntInvoices::findOrFail($invoiceId);

            if ($invoice->status_payment === 'PAGADO') {
                $this->dispatch('show-toast', [
                    'type' => 'warning',
                    'message' => 'Esta factura ya esta pagada.'
                ]);
                return;
            }

            if ($invoice->status_payment === 'ANULADO') {
                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => 'No se puede pagar una factura anulada.'
                ]);
                return;
            }

            if ($invoice->status !== 'FACTURADO') {
                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => 'Solo se pueden pagar facturas con estado FACTURADO.'
                ]);
                return;
            }

            $remissionIdForPayment = $this->resolveRemissionIdForPayment($invoice);
            if (!$remissionIdForPayment) {
                throw new \Exception('No se encontro remissionId asociado para registrar el pago.');
            }

            $pettyCashId = $this->getActivePettyCashIdForInvoice($invoice);
            if (!$pettyCashId) {
                throw new \Exception('No hay caja abierta para registrar el pago de esta factura.');
            }

            $methodPaymentId = 1; // Efectivo por defecto
            $reasonPettyCashId = 1; // Ingreso por venta
            $totalAmount = $this->calculateInvoiceTotalWithRetentions($invoice);

            DB::connection('tenant')->transaction(function () use (
                $totalAmount,
                $pettyCashId,
                $reasonPettyCashId,
                $methodPaymentId,
                $remissionIdForPayment,
                $invoice
            ) {
                // Primer registro: detalle de caja menor
                VntDetailPettyCash::create([
                    'status' => 1,
                    'value' => $totalAmount,
                    'pettyCashId' => $pettyCashId,
                    'reasonPettyCashId' => $reasonPettyCashId,
                    'methodPaymentId' => $methodPaymentId,
                    // Requerimiento: guardar remissionId en invoiceId
                    'invoiceId' => $remissionIdForPayment,
                    'observations' => 'Pago factura #' . ($invoice->invoiceNumber ?: $invoice->consecutive),
                ]);

                // Segundo registro: pagos de factura
                VntInvoicePayments::create([
                    'value' => $totalAmount,
                    // En invoice_payments se guarda el id real de la factura
                    'invoiceId' => $invoice->id,
                    'methodPaymentId' => $methodPaymentId,
                ]);

                $invoice->update([
                    'status_payment' => 'PAGADO',
                ]);
            });

            Log::info('Pago registrado en ERP local', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoiceNumber,
                'remission_id_saved_as_invoiceId' => $remissionIdForPayment,
                'petty_cash_id' => $pettyCashId,
                'payment_amount' => $totalAmount,
                'method_payment_id' => $methodPaymentId
            ]);

            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => 'Factura #' . $invoice->invoiceNumber . ' pagada y registrada en ERP.'
            ]);

        } catch (\Exception $e) {
            Log::error('Error procesando pago de factura', [
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Error al procesar el pago: ' . $e->getMessage()
            ]);
        }
    }

    private function getActivePettyCashIdForInvoice(VntInvoices $invoice): ?int
    {
        // Requerimiento: priorizar caja abierta status=1 en warehouseId=8.
        $warehouseId = 8;

        // 1) Buscar en conexión tenant (modelo Eloquent)
        $pettyCashId = PettyCashModel::query()
            ->where('status', 1)
            ->where('warehouseId', $warehouseId)
            ->orderByDesc('id')
            ->value('id');

        if ($pettyCashId) {
            return (int) $pettyCashId;
        }

        // 2) Fallback: buscar en conexión por defecto (distribuidora)
        $pettyCashId = DB::table('vnt_petty_cash')
            ->where('status', 1)
            ->where('warehouseId', $warehouseId)
            ->orderByDesc('id')
            ->value('id');

        if ($pettyCashId) {
            return (int) $pettyCashId;
        }

        // 3) Fallback adicional: cualquier caja abierta
        $pettyCashId = DB::table('vnt_petty_cash')
            ->where('status', 1)
            ->orderByDesc('id')
            ->value('id');

        if ($pettyCashId) {
            return (int) $pettyCashId;
        }

        // 4) Ultimo recurso: ultima caja registrada
        $pettyCashId = DB::table('vnt_petty_cash')
            ->orderByDesc('id')
            ->value('id');

        if ($pettyCashId) {
            return (int) $pettyCashId;
        }

        return null;
    }

    private function resolveRemissionIdForPayment(VntInvoices $invoice): ?int
    {
        $remissionId = VntInvoicesXsales::query()
            ->where('invoiceId', $invoice->id)
            ->whereNotNull('remissionId')
            ->orderBy('id')
            ->value('remissionId');

        if (!$remissionId && !empty($invoice->remission)) {
            $remissionId = (int) $invoice->remission;
        }

        return $remissionId ? (int) $remissionId : null;
    }
    public function emitirFactura($invoiceId)
    {
        $this->ensureTenantConnection();

        try {
            $invoice = VntInvoices::findOrFail($invoiceId);

            // Validar que la factura puede ser emitida
            if ($invoice->status === 'FACTURADO') {
                $this->dispatch('show-toast', [
                    'type' => 'warning',
                    'message' => 'Esta factura ya está emitida.'
                ]);
                return;
            }

            if ($invoice->status !== 'SIN EMITIR') {
                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => 'Solo se pueden emitir facturas con estado "SIN EMITIR".'
                ]);
                return;
            }

            // Validar que tenga api_data_id (ID de Alegra)
            if (empty($invoice->api_data_id)) {
                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => 'Esta factura no tiene ID de Alegra. No se puede emitir.'
                ]);
                return;
            }

            // Obtener configuración del tenant
            $tenant = session('tenant_id') ? Tenant::find(session('tenant_id')) : null;
            if (!$tenant) {
                throw new \Exception('No se pudo identificar el tenant');
            }

            $hasFacturacionConfig = TenantConfigManager::hasFacturacionConfig($tenant);
            if (!$hasFacturacionConfig) {
                throw new \Exception('No hay configuración de facturación para este tenant');
            }

            // Crear servicio de facturación
            $facturacionService = FacturacionService::forTenant($tenant);

            Log::info('📤 Intentando emitir factura desde listado de facturas', [
                'invoice_id' => $invoiceId,
                'invoice_number' => $invoice->invoiceNumber,
                'alegra_invoice_id' => $invoice->api_data_id,
                'current_status' => $invoice->status
            ]);

            // Intentar emitir (stamp) la factura en Alegra
            $stampResponse = $facturacionService->stampInvoice($invoice->api_data_id);

            if ($stampResponse['success']) {
                // ✅ STAMP EXITOSO: Actualizar estado a FACTURADO
                $invoice->update(['status' => 'FACTURADO']);

                // ✅ Actualizar status de cotizaciones asociadas a FACTURADO
                $this->updateRelatedQuotesToFacturado($invoice);

                Log::info('✅ Factura emitida exitosamente desde listado', [
                    'invoice_id' => $invoiceId,
                    'invoice_number' => $invoice->invoiceNumber,
                    'alegra_invoice_id' => $invoice->api_data_id,
                    'previous_status' => 'SIN EMITIR',
                    'new_status' => 'FACTURADO'
                ]);

                $this->dispatch('show-toast', [
                    'type' => 'success',
                    'message' => "✅ Factura #{$invoice->invoiceNumber} emitida exitosamente."
                ]);
            } else {
                // ❌ STAMP FALLÓ
                $errorMessage = $this->extractStampErrorMessage($stampResponse);
                throw new \Exception("Error al emitir la factura: {$errorMessage}");
            }

        } catch (\Exception $e) {
            Log::error('❌ Error emitiendo factura desde listado', [
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Error al emitir la factura: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Método para imprimir factura (basado en una factura emitida)
     * Utiliza el api_data_id para obtener el PDF oficial de Alegra.
     */
    public function printInvoice($invoiceId)
    {
        Log::info('🖨️ Invoices.printInvoice llamado', ['invoice_id' => $invoiceId]);

        $this->ensureTenantConnection();
        $this->initializeCompanyConfiguration();

        try {
            // 1. Buscar la factura
            $invoice = VntInvoices::findOrFail($invoiceId);

            Log::info('🔍 Factura encontrada', [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoiceNumber,
                'status' => $invoice->status,
                'api_data_id' => $invoice->api_data_id
            ]);

            // 2. Validar que la factura esté emitida
            if ($invoice->status !== 'FACTURADO') {
                Log::warning('⚠️ Factura no está emitida', [
                    'invoice_id' => $invoiceId,
                    'status' => $invoice->status
                ]);
                $this->dispatch('show-toast', [
                    'type' => 'warning',
                    'message' => 'Solo se pueden imprimir facturas emitidas (estado: FACTURADO).'
                ]);
                return;
            }

            // 3. Validar que tenga api_data_id
            if (empty($invoice->api_data_id)) {
                Log::warning('⚠️ Factura sin api_data_id', ['invoice_id' => $invoiceId]);
                $this->dispatch('show-toast', [
                    'type' => 'warning',
                    'message' => 'Esta factura no tiene ID de Alegra. No se puede obtener el PDF oficial.'
                ]);
                return;
            }

            // 4. Obtener configuración de facturación
            $tenant = session('tenant_id') ? Tenant::find(session('tenant_id')) : null;
            if (!$tenant) {
                throw new \Exception('No se pudo identificar el tenant');
            }

            $facturacionService = new FacturacionService($tenant);
            $hasFacturacionConfig = TenantConfigManager::hasFacturacionConfig($tenant);

            // 5. Obtener PDF de Alegra
            if ($hasFacturacionConfig && $invoice->api_data_id) {
                Log::info('🔗 Obteniendo PDF de factura desde Alegra', ['api_id' => $invoice->api_data_id]);

                $apiResponse = $facturacionService->getInvoicePdf($invoice->api_data_id);

                // Analizar estructura de respuesta
                $respData = $apiResponse['data'] ?? [];

                // Intentar obtener URL de varios posibles campos
                $printUrl = $respData['pdf'] ??
                            $respData['publicUrl'] ??
                            ($respData['data']['publicUrl'] ?? null);

                if ($apiResponse['success'] && !empty($printUrl)) {
                    Log::info('✅ URL de PDF de factura obtenida', ['url' => $printUrl]);

                    $this->dispatch('open-print-window', [
                        'url' => $printUrl,
                        'format' => 'carta'
                    ]);

                    $this->dispatch('show-toast', [
                        'type' => 'success',
                        'message' => 'Factura #' . $invoice->invoiceNumber . ' preparada para impresión.'
                    ]);
                    return;
                } else {
                    Log::warning('⚠️ No se obtuvo URL válida de Alegra para factura', [
                        'response' => $apiResponse,
                        'invoice_id' => $invoiceId
                    ]);
                    $this->dispatch('show-toast', [
                        'type' => 'warning',
                        'message' => 'No se pudo obtener el PDF de la factura desde Alegra. Verifique que esté correctamente emitida.'
                    ]);
                }
            } else {
                Log::info('ℹ️ Facturación no configurada para obtener PDF', [
                    'has_config' => $hasFacturacionConfig,
                    'has_api_id' => !empty($invoice->api_data_id)
                ]);
                $this->dispatch('show-toast', [
                    'type' => 'info',
                    'message' => 'No se puede obtener el PDF de Alegra. Verifique la configuración de facturación.'
                ]);
            }

        } catch (\Exception $e) {
            Log::error('❌ Error en Invoices.printInvoice: ' . $e->getMessage(), [
                'invoice_id' => $invoiceId,
                'trace' => $e->getTraceAsString()
            ]);
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Error al procesar impresión de factura: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Descarga el PDF oficial de la factura desde Factus usando el invoiceNumber.
     */
    public function downloadFacturaPdf($invoiceId)
    {
        $this->ensureTenantConnection();

        try {
            $invoice = VntInvoices::findOrFail($invoiceId);

            if ($invoice->status !== 'FACTURADO') {
                $this->dispatch('show-toast', [
                    'type' => 'warning',
                    'message' => 'Solo se pueden descargar facturas emitidas (estado: FACTURADO).',
                ]);
                return;
            }

            if (empty($invoice->invoiceNumber)) {
                $this->dispatch('show-toast', [
                    'type' => 'warning',
                    'message' => 'Esta factura no tiene número de factura electrónica.',
                ]);
                return;
            }

            $client = app(FactusClient::class);
            $response = $client->getBillPdf($invoice->invoiceNumber);

            if (!$response->successful()) {
                Log::error('Error al obtener PDF de Factus', [
                    'invoice_id'     => $invoiceId,
                    'invoice_number' => $invoice->invoiceNumber,
                    'status'         => $response->status(),
                    'body'           => $response->body(),
                ]);
                $this->dispatch('show-toast', [
                    'type'    => 'error',
                    'message' => 'Error al obtener el PDF desde Factus. Verifique que la factura esté emitida.',
                ]);
                return;
            }

            $json     = $response->json();
            $b64      = $json['data']['pdf_base_64_encoded'] ?? null;
            $fileName = $json['data']['file_name'] ?? ('factura-' . $invoice->invoiceNumber);

            if (empty($b64)) {
                Log::error('Respuesta de Factus sin pdf_base_64_encoded', [
                    'invoice_number' => $invoice->invoiceNumber,
                    'response'       => $json,
                ]);
                $this->dispatch('show-toast', [
                    'type'    => 'error',
                    'message' => 'Factus no devolvió el PDF. Verifique que la factura esté emitida.',
                ]);
                return;
            }

            $pdfContent = base64_decode($b64);
            $filename   = $fileName . '.pdf';

            Log::info('PDF de factura descargado desde Factus', [
                'invoice_id'     => $invoiceId,
                'invoice_number' => $invoice->invoiceNumber,
                'file_name'      => $filename,
            ]);

            return response()->streamDownload(function () use ($pdfContent) {
                echo $pdfContent;
            }, $filename, [
                'Content-Type' => 'application/pdf',
            ]);

        } catch (\Exception $e) {
            Log::error('Error descargando PDF de factura desde Factus', [
                'invoice_id' => $invoiceId,
                'error'      => $e->getMessage(),
            ]);
            $this->dispatch('show-toast', [
                'type'    => 'error',
                'message' => 'Error al descargar el PDF: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Descarga el PDF de la nota crédito desde Factus usando el creditNoteId.
     */
    public function downloadCreditNotePdf($invoiceId)
    {
        $this->ensureTenantConnection();

        try {
            $invoice = VntInvoices::findOrFail($invoiceId);

            if (empty($invoice->creditNoteId)) {
                $this->dispatch('show-toast', [
                    'type'    => 'warning',
                    'message' => 'Esta factura no tiene nota crédito registrada.',
                ]);
                return;
            }

            $client   = app(FactusClient::class);
            $response = $client->getCreditNotePdf($invoice->creditNoteId);

            if (!$response->successful()) {
                Log::error('Error al obtener PDF de nota crédito desde Factus', [
                    'invoice_id'     => $invoiceId,
                    'credit_note_id' => $invoice->creditNoteId,
                    'status'         => $response->status(),
                    'body'           => $response->body(),
                ]);
                $this->dispatch('show-toast', [
                    'type'    => 'error',
                    'message' => 'Error al obtener el PDF de la nota crédito desde Factus.',
                ]);
                return;
            }

            $json     = $response->json();
            $b64      = $json['data']['pdf_base_64_encoded'] ?? null;
            $fileName = $json['data']['file_name'] ?? ('nota-credito-' . $invoice->creditNoteId);

            if (empty($b64)) {
                Log::error('Respuesta de Factus sin pdf_base_64_encoded para nota crédito', [
                    'credit_note_id' => $invoice->creditNoteId,
                    'response'       => $json,
                ]);
                $this->dispatch('show-toast', [
                    'type'    => 'error',
                    'message' => 'Factus no devolvió el PDF de la nota crédito.',
                ]);
                return;
            }

            $pdfContent = base64_decode($b64);
            $filename   = $fileName . '.pdf';

            Log::info('PDF de nota crédito descargado desde Factus', [
                'invoice_id'     => $invoiceId,
                'credit_note_id' => $invoice->creditNoteId,
                'file_name'      => $filename,
            ]);

            return response()->streamDownload(function () use ($pdfContent) {
                echo $pdfContent;
            }, $filename, [
                'Content-Type' => 'application/pdf',
            ]);

        } catch (\Exception $e) {
            Log::error('Error descargando PDF de nota crédito desde Factus', [
                'invoice_id' => $invoiceId,
                'error'      => $e->getMessage(),
            ]);
            $this->dispatch('show-toast', [
                'type'    => 'error',
                'message' => 'Error al descargar el PDF de la nota crédito: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Actualizar status de cotizaciones relacionadas con la factura a FACTURADO
     */
    private function updateRelatedQuotesToFacturado(VntInvoices $invoice): void
    {
        try {
            // Buscar todas las remisiones asociadas a esta factura
            $remissionIds = VntInvoicesXsales::where('invoiceId', $invoice->id)
                ->pluck('remissionId')
                ->toArray();

            if (empty($remissionIds)) {
                Log::info('ℹ️ No se encontraron remisiones asociadas a la factura', [
                    'invoice_id' => $invoice->id
                ]);
                return;
            }

            // Buscar las cotizaciones de esas remisiones
            $quotesToUpdate = DB::connection('tenant')
                ->table('inv_remissions')
                ->join('vnt_quotes', 'inv_remissions.quoteId', '=', 'vnt_quotes.id')
                ->whereIn('inv_remissions.id', $remissionIds)
                ->select('vnt_quotes.id', 'vnt_quotes.consecutive')
                ->get();

            $quotesUpdated = 0;
            foreach ($quotesToUpdate as $quote) {
                DB::connection('tenant')
                    ->table('vnt_quotes')
                    ->where('id', $quote->id)
                    ->update(['status' => 'FACTURADO']);

                $quotesUpdated++;

                Log::info('📋 Status de cotización actualizado a FACTURADO (desde emisión)', [
                    'quote_id' => $quote->id,
                    'quote_consecutive' => $quote->consecutive,
                    'invoice_id' => $invoice->id,
                    'invoice_number' => $invoice->invoiceNumber
                ]);
            }

            Log::info('✅ Cotizaciones actualizadas tras emisión de factura', [
                'invoice_id' => $invoice->id,
                'quotes_updated' => $quotesUpdated
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error actualizando cotizaciones tras emisión de factura', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Extraer mensaje de error más legible del response de stamp
     */
    private function extractStampErrorMessage(array $stampResponse): string
    {
        $message = $stampResponse['message'] ?? 'Error desconocido';

        // Mejorar mensajes comunes de la DIAN
        if (str_contains($message, 'nombre informado no corresponde al registrado en el rut')) {
            $message .= ' - Verifique que los datos del cliente estén actualizados en el RUT.';
        }

        return $message;
    }

    /**
     * Calcular el total de la factura considerando retenciones - SIN REDONDEAR
     */
    private function calculateInvoiceTotalWithRetentions(VntInvoices $invoice): float
    {
        // Obtener el total con impuestos desde la query usando ROUND en SQL para precisión exacta
        $query = VntInvoices::query()
            ->select([
                DB::raw("MAX(COALESCE(dr_totals.total_con_impuestos, dq_totals.total_con_impuestos, 0)) AS total_con_impuestos")
            ])
            ->leftJoinSub($this->getRemissionTotalsSubquery(), "dr_totals", "vnt_invoices.id", "=", "dr_totals.invoiceId")
            ->leftJoinSub($this->getQuoteTotalsSubquery(), "dq_totals", "vnt_invoices.id", "=", "dq_totals.invoiceId")
            ->where('vnt_invoices.id', $invoice->id)
            ->first();

        // Usar números decimales exactos sin redondeo intermedio
        $totalConImpuestos = (float)($query->total_con_impuestos ?? 0);
        $retentionFuente = (float)($invoice->retentionFuente ?? 0);
        $retentionIca = (float)($invoice->retentionIca ?? 0);
        $retentionIva = (float)($invoice->retentionIva ?? 0);

        // Calcular el total EXACTO sin redondeo
        $totalAPagar = $totalConImpuestos - $retentionFuente - $retentionIca - $retentionIva;

        Log::info('💰 Cálculo EXACTO de monto de pago (SIN REDONDEO)', [
            'invoice_id' => $invoice->id,
            'total_con_impuestos_raw' => $query->total_con_impuestos,
            'total_con_impuestos_float' => $totalConImpuestos,
            'retention_fuente' => $retentionFuente,
            'retention_ica' => $retentionIca,
            'retention_iva' => $retentionIva,
            'total_a_pagar_exacto' => $totalAPagar,
            'total_a_pagar_formatted' => number_format($totalAPagar, 2, '.', '')
        ]);

        // Retornar el valor EXACTO sin redondear
        return $totalAPagar;
    }

    /**
     * Construir datos de pago para la API de Alegra - VALORES EXACTOS
     */
    private function buildAlegraPaymentData(VntInvoices $invoice, float $totalAmount): array
    {
        // Obtener retenciones EXACTAS sin redondear
        $retentions = [];
        $totalRetentions = 0;

        if ($invoice->retentionFuente > 0) {
            $retentionAmount = (float)$invoice->retentionFuente;
            $retentions[] = [
                'id' => config('facturacion.retentions.alegra_ids.fuente', '14'),
                'amount' => $retentionAmount  // SIN REDONDEAR
            ];
            $totalRetentions += $retentionAmount;
        }
        if ($invoice->retentionIca > 0) {
            $retentionAmount = (float)$invoice->retentionIca;
            $retentions[] = [
                'id' => config('facturacion.retentions.alegra_ids.ica', '11'),
                'amount' => $retentionAmount  // SIN REDONDEAR
            ];
            $totalRetentions += $retentionAmount;
        }
        if ($invoice->retentionIva > 0) {
            $retentionAmount = (float)$invoice->retentionIva;
            $retentions[] = [
                'id' => config('facturacion.retentions.alegra_ids.iva', '12'),
                'amount' => $retentionAmount  // SIN REDONDEAR
            ];
            $totalRetentions += $retentionAmount;
        }

        // Construir objeto de factura con valor EXACTO
        $totalBruto = $totalAmount + $totalRetentions; // Total antes de retenciones

        $invoiceObject = [
            'id' => $invoice->api_data_id,
            'amount' => $totalBruto // Total bruto EXACTO
        ];

        // Agregar retenciones si existen
        if (!empty($retentions)) {
            $invoiceObject['retentions'] = $retentions;
        }

        // Usar cuenta bancaria por defecto (puede configurarse según necesidades)
        $paymentData = [
            'bankAccount' => [
                'id' => '1' // ID de cuenta bancaria por defecto en Alegra
            ],
            'type' => 'in', // Pago entrante
            'date' => now()->format('Y-m-d'),
            'invoices' => [$invoiceObject]
        ];

        Log::info('🔧 Datos de pago construidos para Alegra - VALORES EXACTOS', [
            'payment_data' => $paymentData,
            'retentions_count' => count($retentions),
            'total_retentions_exacto' => $totalRetentions,
            'net_amount_exacto' => $totalAmount,
            'total_bruto_exacto' => $totalBruto,
            'retentions_detalle' => $retentions,
            'json_exacto' => json_encode($paymentData, JSON_PRESERVE_ZERO_FRACTION)
        ]);

        return $paymentData;
    }

    /**
     * Obtener subconsulta de totales de remisiones - PRECISIÓN EXACTA
     */
    private function getRemissionTotalsSubquery()
    {
        return DB::connection('tenant')->table("inv_detail_remissions")
            ->select(
                "invoiceId",
                DB::raw("CAST(SUM(value * quantity) AS DECIMAL(15,6)) as total_con_impuestos"),
                DB::raw("CAST(SUM((value / (1 + tax / 100)) * quantity) AS DECIMAL(15,6)) as total_sin_impuestos")
            )
            ->whereNotNull("invoiceId")
            ->groupBy("invoiceId");
    }

    /**
     * Obtener subconsulta de totales de cotizaciones - PRECISIÓN EXACTA
     */
    private function getQuoteTotalsSubquery()
    {
        return DB::connection('tenant')->table("vnt_detail_quotes as dq")
            ->select(
                "ixs.invoiceId",
                DB::raw("CAST(SUM(dq.price * dq.quantity) AS DECIMAL(15,6)) as total_con_impuestos"),
                DB::raw("CAST(SUM((dq.price / (1 + dq.tax_percentage / 100)) * dq.quantity) AS DECIMAL(15,6)) as total_sin_impuestos")
            )
            ->join("vnt_invoicesXsales as ixs", "dq.quoteId", "=", "ixs.quoteId")
            ->whereNotNull("ixs.invoiceId")
            ->groupBy("ixs.invoiceId");
    }

    // ─── Nota Crédito: Abrir Modal ────────────────────────────────────────────
    public function openCreditNoteModal(int $invoiceId): void
    {
        $this->ensureTenantConnection();

        try {
            // Cargar datos de la factura con nombre del cliente
            $invoice = DB::connection('tenant')
                ->table('vnt_invoices')
                ->leftJoin('vnt_invoicesXsales as ixs', 'ixs.invoiceId', '=', 'vnt_invoices.id')
                ->leftJoin('inv_remissions as r', 'ixs.remissionId', '=', 'r.id')
                ->leftJoin('vnt_quotes as qr', 'r.quoteId', '=', 'qr.id')
                ->leftJoin('vnt_quotes as qd', 'ixs.quoteId', '=', 'qd.id')
                ->leftJoin('vnt_warehouses as w', 'w.id', '=', DB::raw('COALESCE(qr.customerId, qd.customerId)'))
                ->leftJoin('vnt_companies as c', 'c.id', '=', 'w.companyId')
                ->where('vnt_invoices.id', $invoiceId)
                ->whereNull('vnt_invoices.deleted_at')
                ->groupBy([
                    'vnt_invoices.id',
                    'vnt_invoices.invoiceNumber',
                    'vnt_invoices.consecutive',
                    'vnt_invoices.status',
                    'vnt_invoices.status_payment',
                    'vnt_invoices.api_data_id',
                ])
                ->select([
                    'vnt_invoices.id',
                    'vnt_invoices.invoiceNumber',
                    'vnt_invoices.consecutive',
                    'vnt_invoices.status',
                    'vnt_invoices.status_payment',
                    'vnt_invoices.api_data_id',
                    DB::raw("MAX(TRIM(CONCAT(COALESCE(c.firstName,''),' ',COALESCE(c.secondName,''),' ',COALESCE(c.lastName,''),' ',COALESCE(c.secondLastName,'')))) AS client_name"),
                    DB::raw("MAX(COALESCE(c.businessName,'')) AS business_name"),
                ])
                ->first();

            if (!$invoice) {
                $this->dispatch('show-toast', ['type' => 'error', 'message' => 'Factura no encontrada.']);
                return;
            }

            if (empty($invoice->api_data_id)) {
                $this->dispatch('show-toast', ['type' => 'error', 'message' => 'Esta factura no tiene ID de Factus. No se puede generar nota crédito.']);
                return;
            }

            $items = $this->loadInvoiceItemsForCreditNote($invoiceId);

            if (empty($items)) {
                $this->dispatch('show-toast', ['type' => 'error', 'message' => 'No se encontraron ítems en la factura.']);
                return;
            }

            $clientName = trim($invoice->client_name ?: $invoice->business_name);

            $this->creditNoteInvoiceData = [
                'id'             => $invoice->id,
                'invoiceNumber'  => $invoice->invoiceNumber ?: ('#' . $invoice->consecutive),
                'client_name'    => $clientName ?: 'N/A',
                'status'         => $invoice->status,
                'status_payment' => $invoice->status_payment,
                'api_data_id'    => $invoice->api_data_id,
            ];

            $this->creditNoteItems       = $items;
            $this->correctionConceptCode = '2';
            $this->creditNotePaymentMethod = '10';
            $this->creditNoteObservation = '';
            $this->creditNoteLoading     = false;
            $this->calculateCreditNoteTotal();
            $this->showCreditNoteModal   = true;

        } catch (\Exception $e) {
            Log::error('Error abriendo modal nota crédito', ['invoice_id' => $invoiceId, 'error' => $e->getMessage()]);
            $this->dispatch('show-toast', ['type' => 'error', 'message' => 'Error al cargar la factura: ' . $e->getMessage()]);
        }
    }

    public function closeCreditNoteModal(): void
    {
        $this->showCreditNoteModal    = false;
        $this->creditNoteInvoiceData  = null;
        $this->creditNoteItems        = [];
        $this->creditNoteObservation  = '';
        $this->creditNoteTotal        = 0;
        $this->creditNoteLoading      = false;
    }

    // Lifecycle hook: recalcular total cuando cambia cualquier ítem
    public function updatedCreditNoteItems(): void
    {
        $this->calculateCreditNoteTotal();
    }

    // Lifecycle hook: cuando cambia el motivo, auto-seleccionar todo si es Anulación
    public function updatedCorrectionConceptCode(string $value): void
    {
        if ($value === '2') {
            $this->selectAllCreditNoteItems();
        }
    }

    public function selectAllCreditNoteItems(): void
    {
        foreach ($this->creditNoteItems as &$item) {
            $item['selected'] = true;
            $item['quantity'] = $item['max_quantity'];
        }
        unset($item);
        $this->calculateCreditNoteTotal();
    }

    public function deselectAllCreditNoteItems(): void
    {
        foreach ($this->creditNoteItems as &$item) {
            $item['selected'] = false;
        }
        unset($item);
        $this->calculateCreditNoteTotal();
    }

    public function calculateCreditNoteTotal(): void
    {
        $total = 0.0;
        foreach ($this->creditNoteItems as $item) {
            if (!empty($item['selected'])) {
                $qty   = max(0, (int) ($item['quantity'] ?? 0));
                $price = (float) ($item['price'] ?? 0);
                $total += $price * $qty;
            }
        }
        $this->creditNoteTotal = $total;
    }

    public function submitCreditNote(): void
    {
        $this->ensureTenantConnection();

        if (!$this->creditNoteInvoiceData) {
            return;
        }

        $selectedItems = array_values(array_filter(
            $this->creditNoteItems,
            fn($item) => !empty($item['selected']) && (int)($item['quantity'] ?? 0) > 0
        ));

        if (empty($selectedItems)) {
            $this->dispatch('show-toast', ['type' => 'warning', 'message' => 'Seleccione al menos un ítem para la nota crédito.']);
            return;
        }

        $this->creditNoteLoading = true;

        try {
            $factusClient  = app(FactusClient::class);
            $referenceCode = 'NC' . $this->creditNoteInvoiceData['id'] . 'T' . time();

            $payload = [
                'correction_concept_code' => (int) $this->correctionConceptCode,
                'customization_id'        => 20,
                'bill_id'                 => (int) $this->creditNoteInvoiceData['api_data_id'],
                'reference_code'          => $referenceCode,
                'observation'             => mb_substr(trim($this->creditNoteObservation), 0, 250),
                'payment_method_code'     => $this->creditNotePaymentMethod,
                'items'                   => array_map(fn($item) => [
                    'code_reference'    => (string) ($item['code_reference'] ?? 'SIN-COD'),
                    'name'              => (string) ($item['name'] ?? 'Producto'),
                    'quantity'          => (int) $item['quantity'],
                    'discount_rate'     => 0,
                    'price'             => (float) $item['price'],
                    'tax_rate'          => (string) ($item['tax_rate'] ?? '19.00'),
                    'unit_measure_id'   => (int) ($item['unit_measure_id'] ?? 70),
                    'standard_code_id'  => (int) ($item['standard_code_id'] ?? 1),
                    'is_excluded'       => (int) ($item['is_excluded'] ?? 0),
                    'tribute_id'        => (int) ($item['tribute_id'] ?? 1),
                    'withholding_taxes' => [],
                ], $selectedItems),
            ];

            Log::info('Enviando nota crédito a Factus', ['payload' => $payload]);

            $response = $factusClient->validateCreditNote($payload);

            Log::info('Nota crédito creada en Factus', ['response' => $response]);

            $creditNoteNumber = $response['data']['credit_note']['number'] ?? null;

            $invoice = VntInvoices::find($this->creditNoteInvoiceData['id']);
            if ($invoice) {
                $updateData = [
                    'creditNoteId' => $creditNoteNumber,
                    'creditNote'   => 1,
                ];
                // Anulación completa → marcar pago como ANULADO
                if ((int)$this->correctionConceptCode === 2) {
                    $updateData['status_payment'] = 'ANULADO';
                }
                $invoice->update($updateData);
            }

            $this->closeCreditNoteModal();
            $this->dispatch('show-toast', [
                'type'    => 'success',
                'message' => 'Nota crédito' . ($creditNoteNumber ? " #{$creditNoteNumber}" : '') . ' creada exitosamente.',
            ]);

        } catch (FactusApiException $e) {
            Log::error('Error API Factus al crear nota crédito', ['error' => $e->getMessage()]);
            $this->creditNoteLoading = false;
            $this->dispatch('show-toast', ['type' => 'error', 'message' => 'Error Factus: ' . $e->getMessage()]);
        } catch (\Exception $e) {
            Log::error('Error al crear nota crédito', ['error' => $e->getMessage()]);
            $this->creditNoteLoading = false;
            $this->dispatch('show-toast', ['type' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
        }
    }

    private function loadInvoiceItemsForCreditNote(int $invoiceId): array
    {
        // Intentar primero con detalles de remisión (invoiceId en inv_detail_remissions)
        $rows = DB::connection('tenant')
            ->table('inv_detail_remissions as d')
            ->join('inv_items as i', 'i.id', '=', 'd.itemId')
            ->where('d.invoiceId', $invoiceId)
            ->select(['i.sku', 'i.internal_code', 'i.name', 'd.quantity', 'd.value', 'd.tax'])
            ->get();

        if ($rows->isEmpty()) {
            // Fallback: ítems de cotización vía vnt_invoicesXsales
            $rows = DB::connection('tenant')
                ->table('vnt_detail_quotes as dq')
                ->join('vnt_invoicesXsales as ixs', 'ixs.quoteId', '=', 'dq.quoteId')
                ->join('inv_items as i', 'i.id', '=', 'dq.itemId')
                ->where('ixs.invoiceId', $invoiceId)
                ->whereNotNull('ixs.quoteId')
                ->select(['i.sku', 'i.internal_code', 'i.name', 'dq.quantity', 'dq.price as value', 'dq.tax_percentage as tax'])
                ->get();
        }

        return $rows->map(function ($row) {
            $price   = (float) ($row->value ?? 0);
            $qty     = max(1, (int) ($row->quantity ?? 1));
            $taxRate = (float) ($row->tax ?? 19);
            $priceSinIva = $taxRate > 0 ? ($price / (1 + $taxRate / 100)) : $price;

            return [
                'selected'         => true,
                'code_reference'   => $row->sku ?: ($row->internal_code ?: 'SIN-COD'),
                'name'             => $row->name ?? 'Producto',
                'quantity'         => $qty,
                'max_quantity'     => $qty,
                'price'            => $price,
                'price_sin_iva'    => round($priceSinIva, 2),
                'tax_rate'         => number_format($taxRate, 2, '.', ''),
                'unit_measure_id'  => 70,
                'standard_code_id' => 1,
                'is_excluded'      => $taxRate > 0 ? 0 : 1,
                'tribute_id'       => 1,
                'subtotal'         => round($priceSinIva * $qty, 2),
                'total'            => round($price * $qty, 2),
            ];
        })->values()->toArray();
    }

    public function render()
    {
        try {
            $this->ensureTenantConnection();

            // Subconsulta para totales de detalles de remisión - PRECISIÓN EXACTA con DEBUG
            // CORRECCIÓN: El campo 'value' en inv_detail_remissions en realidad YA incluye impuestos
            $remissionTotals = DB::connection('tenant')->table("inv_detail_remissions")
                ->select(
                    "invoiceId",
                    DB::raw("CAST(SUM(value * quantity) AS DECIMAL(15,6)) as total_con_impuestos"), // El 'value' YA tiene impuestos
                    DB::raw("CAST(SUM((value / (1 + tax / 100)) * quantity) AS DECIMAL(15,6)) as total_sin_impuestos"), // Quitamos impuestos INDIVIDUALMENTE
                    DB::raw("COUNT(DISTINCT remissionId) as remissions_count"), // Para debug: cuántas remisiones
                    DB::raw("GROUP_CONCAT(CONCAT('R', remissionId, ':V', value, ':T', tax, ':Q', quantity) SEPARATOR '|') as debug_items") // Para debug
                )
                ->whereNotNull("invoiceId")
                ->groupBy("invoiceId");

            // Subconsulta para totales de detalles de cotización - PRECISIÓN EXACTA
            // NOTA: Los valores en vnt_detail_quotes YA incluyen impuestos
            $quoteTotals = DB::connection('tenant')->table("vnt_detail_quotes as dq")
                ->select(
                    "ixs.invoiceId",
                    DB::raw("CAST(SUM(dq.price * dq.quantity) AS DECIMAL(15,6)) as total_con_impuestos"),
                    DB::raw("CAST(SUM((dq.price / (1 + dq.tax_percentage / 100)) * dq.quantity) AS DECIMAL(15,6)) as total_sin_impuestos")
                )
                ->join("vnt_invoicesXsales as ixs", "dq.quoteId", "=", "ixs.quoteId")
                ->whereNotNull("ixs.invoiceId")
                ->groupBy("ixs.invoiceId");

            // Subconsulta para GROUP_CONCAT de consecutivos de remisión
            $remissionConsecutives = DB::connection('tenant')->table("vnt_invoicesXsales as s_sub")
                ->select(
                    "s_sub.invoiceId",
                    DB::raw("GROUP_CONCAT(r_sub.consecutive ORDER BY r_sub.consecutive SEPARATOR ', ') as remission_consecutive")
                )
                ->join("inv_remissions as r_sub", "s_sub.remissionId", "=", "r_sub.id")
                ->groupBy("s_sub.invoiceId");

            $query = VntInvoices::query()
                ->select([
                    'vnt_invoices.id',
                    'vnt_invoices.consecutive',
                    'vnt_invoices.status',
                    'vnt_invoices.status_payment',
                    'vnt_invoices.api_data_id',
                    'vnt_invoices.api_data_id_pay',
                    'vnt_invoices.partialPayment',
                    'vnt_invoices.quoteId',
                    'vnt_invoices.warehouseId',
                    'vnt_invoices.remission',
                    'vnt_invoices.creditNoteId',
                    'vnt_invoices.invoiceNumber',
                    'vnt_invoices.retentionFuente',
                    'vnt_invoices.retentionIca',
                    'vnt_invoices.retentionIva',
                    'vnt_invoices.creditNote',
                    'vnt_invoices.orderNumber',
                    'vnt_invoices.created_at',
                    'vnt_invoices.updated_at',
                    'vnt_invoices.deleted_at',
                    'qr.customerId as customerId_qr',
                    'qd.customerId as customerId_qd',
                    DB::raw("MAX(remission_consecutives.remission_consecutive) as remission_consecutive"),
                    DB::raw("MAX(COALESCE(wr.name, wd.name)) as warehouse_name"),
                    DB::raw("MAX(CONCAT(COALESCE(u.name, ''), ' ', COALESCE(u.name, ''))) AS seller"),
                    DB::raw("MAX(CONCAT(COALESCE(c.firstName, ''), ' ', COALESCE(c.secondName, ''), ' ', COALESCE(c.lastName, ''), ' ', COALESCE(c.secondLastName, ''))) AS client_name"),
                    DB::raw("MAX(COALESCE(dr_totals.total_sin_impuestos, dq_totals.total_sin_impuestos, 0)) AS total_sin_impuestos"),
                    DB::raw("MAX(COALESCE(dr_totals.total_con_impuestos, dq_totals.total_con_impuestos, 0)) AS total_con_impuestos"),
                    DB::raw("MAX(IF(s.remissionId IS NOT NULL, 'REMISIONADA', 'COTIZADA')) as tipo_factura"),
                    // Campos DEBUG para facturas agrupadas
                    DB::raw("MAX(dr_totals.remissions_count) as debug_remissions_count"),
                    DB::raw("MAX(dr_totals.debug_items) as debug_items_detail"),
                ])
                ->join("vnt_invoicesXsales as s", "s.invoiceId", "=", "vnt_invoices.id")
                // Joins para la ruta de Remisión
                ->leftJoin("inv_remissions as r", "s.remissionId", "=", "r.id")
                ->leftJoin("inv_store as wr", "r.warehouseId", "=", "wr.id")
                // Joins para la ruta de Cotización (puede venir de una remisión o directa)
                ->leftJoin("vnt_quotes as qr", "r.quoteId", "=", "qr.id") // Cotización vía Remisión
                ->leftJoin("vnt_quotes as qd", "s.quoteId", "=", "qd.id") // Cotización Directa
                ->leftJoin("inv_store as wd", "qd.warehouseId", "=", "wd.id")
                // Joins para datos de Cliente: customerId → vnt_warehouses → vnt_companies
                ->leftJoin("vnt_warehouses as w", "w.id", "=", DB::raw("COALESCE(qr.customerId, qd.customerId)"))
                ->leftJoin("vnt_companies as c", "c.id", "=", "w.companyId")
                ->leftJoin('users as u', 'u.id', '=', DB::raw('COALESCE(qr.userId, qd.userId)'))
                // Joins con las subconsultas
                ->leftJoinSub($remissionTotals, "dr_totals", "vnt_invoices.id", "=", "dr_totals.invoiceId")
                ->leftJoinSub($quoteTotals, "dq_totals", "vnt_invoices.id", "=", "dq_totals.invoiceId")
                ->leftJoinSub($remissionConsecutives, "remission_consecutives", "vnt_invoices.id", "=", "remission_consecutives.invoiceId")
                ->groupBy([
                    "vnt_invoices.id",
                    "vnt_invoices.consecutive",
                    "vnt_invoices.status",
                    "vnt_invoices.status_payment",
                    "vnt_invoices.api_data_id",
                    "vnt_invoices.api_data_id_pay",
                    "vnt_invoices.partialPayment",
                    "vnt_invoices.quoteId",
                    "vnt_invoices.warehouseId",
                    "vnt_invoices.remission",
                    "vnt_invoices.creditNoteId",
                    "vnt_invoices.invoiceNumber",
                    "vnt_invoices.retentionFuente",
                    "vnt_invoices.retentionIca",
                    "vnt_invoices.retentionIva",
                    "vnt_invoices.creditNote",
                    "vnt_invoices.orderNumber",
                    "vnt_invoices.created_at",
                    "vnt_invoices.updated_at",
                    "vnt_invoices.deleted_at",
                    "qr.customerId",
                    "qd.customerId",
                ]);

            // Aplicar búsqueda corregida
            $query->when($this->search, function ($q) {
                $search = '%' . $this->search . '%';
                $q->where(function ($subQ) use ($search) {
                    $subQ->where('vnt_invoices.invoiceNumber', 'like', $search)
                        ->orWhere('vnt_invoices.consecutive', 'like', $search)
                        ->orWhere('remission_consecutives.remission_consecutive', 'like', $search)
                        ->orWhere('vnt_invoices.orderNumber', 'like', $search)
                        ->orWhere(DB::raw("COALESCE(c.businessName, '')"), 'like', $search)
                        ->orWhere(DB::raw("CONCAT(COALESCE(c.firstName, ''), ' ', COALESCE(c.secondName, ''), ' ', COALESCE(c.lastName, ''), ' ', COALESCE(c.secondLastName, ''))"), 'like', $search);
                });
            });

            // Aplicar filtros de fecha
            $query->when($this->fromDate, function ($q) {
                $q->whereDate('vnt_invoices.created_at', '>=', $this->fromDate);
            });

            $query->when($this->toDate, function ($q) {
                $q->whereDate('vnt_invoices.created_at', '<=', $this->toDate);
            });

            // Aplicar ordenamiento
            $query->orderBy($this->sortField, $this->sortDirection);

            $invoices = $query->paginate($this->perPage);

            // LOG DEBUG: Analizar facturas con múltiples remisiones
            foreach ($invoices as $invoice) {
                if ($invoice->debug_remissions_count > 1) {
                    Log::info('🔍 DEBUG: Factura con múltiples remisiones detectada', [
                        'invoice_id' => $invoice->id,
                        'invoice_number' => $invoice->invoiceNumber,
                        'remissions_count' => $invoice->debug_remissions_count,
                        'total_con_impuestos' => $invoice->total_con_impuestos,
                        'total_sin_impuestos' => $invoice->total_sin_impuestos,
                        'items_detail' => $invoice->debug_items_detail,
                        'diferencia_iva' => $invoice->total_con_impuestos - $invoice->total_sin_impuestos
                    ]);
                }
            }

            return view('livewire.tenant.invoices.invoices', [
                'invoices' => $invoices
            ])->layout('layouts.app', ['header' => 'Gestión de Facturas']);
        } catch (\Exception $e) {
            Log::error('❌ Error en la consulta de facturas: ' . $e->getMessage() . ' en la línea ' . $e->getLine());
            // Opcional: retornar una vista de error o una colección vacía para no romper la UI
            return view('livewire.tenant.invoices.invoices', [
                'invoices' => new \Illuminate\Pagination\LengthAwarePaginator([], 0, $this->perPage)
            ])->layout('layouts.app', ['header' => 'Gestión de Facturas']);
        }
    }

    private function ensureTenantConnection()
    {
        $tenantId = session('tenant_id');

        if (!$tenantId) {
            return redirect()->route('tenant.select');
        }

        $tenant = Tenant::find($tenantId);

        if (!$tenant) {
            session()->forget('tenant_id');
            return redirect()->route('tenant.select');
        }

        // Establecer conexión tenant
        $tenantManager = app(TenantManager::class);
        $tenantManager->setConnection($tenant);

        // Inicializar tenancy
        tenancy()->initialize($tenant);
    }
}
