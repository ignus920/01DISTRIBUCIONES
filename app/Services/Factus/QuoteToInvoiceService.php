<?php

namespace App\Services\Factus;

use App\Models\Tenant\Quoter\VntQuote;
use App\Models\Tenant\Customer\VntWarehouse;
use Illuminate\Support\Facades\Log;

class QuoteToInvoiceService
{
    protected FactusClient $factusClient;

    public function __construct(FactusClient $factusClient)
    {
        $this->factusClient = $factusClient;
    }

    public function convertQuoteToInvoice(int $quoteId): array
    {
        try {
            Log::info('Iniciando conversión de cotización a factura Factus', ['quote_id' => $quoteId]);

            $quote = VntQuote::with([
                'detalles.item',
                'customer.company',
                'customer.contacts',
                'customer.city',   // ciudad del warehouse del cliente
                'warehouse.city',  // ciudad del warehouse de la empresa
            ])->findOrFail($quoteId);

            Log::info('Cotización cargada', [
                'consecutive' => $quote->consecutive,
                'customer_id' => $quote->customerId,
                'items_count' => $quote->detalles->count(),
            ]);

            $invoiceData = $this->buildInvoiceData($quote);

            Log::info('Payload enviado a Factus', $invoiceData);

            $response = $this->factusClient->validateBill($invoiceData);

            Log::info('Factura validada exitosamente en Factus', [
                'quote_id'        => $quoteId,
                'factus_response' => $response,
            ]);

            $bill = $response['data']['bill'] ?? [];

            return [
                'success'           => true,
                'message'           => 'Factura creada y validada exitosamente',
                'data'              => $response,
                'quote_consecutive' => $quote->consecutive,
                'factus_bill_id'    => $bill['id'] ?? null,
                'invoice_number'    => $bill['number'] ?? null,
                'public_url'        => $bill['public_url'] ?? null,
            ];

        } catch (FactusApiException $e) {
            Log::error('Error de API Factus al crear factura', [
                'quote_id'    => $quoteId,
                'error'       => $e->getMessage(),
                'status_code' => $e->getStatusCode(),
            ]);

            return [
                'success'     => false,
                'error'       => 'Error en la API de facturación',
                'message'     => $e->getMessage(),
                'status_code' => $e->getStatusCode(),
            ];

        } catch (\Exception $e) {
            Log::error('Error general al convertir cotización a factura', [
                'quote_id' => $quoteId,
                'error'    => $e->getMessage(),
                'trace'    => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error'   => 'Error interno del sistema',
                'message' => $e->getMessage(),
            ];
        }
    }

    protected function buildInvoiceData(VntQuote $quote): array
    {
        $customer          = $this->getCustomerData($quote);
        $items             = $this->processQuoteItems($quote);
        $referenceCode     = $this->generateReferenceCode($quote);
        $numberingRangeId  = $this->factusClient->getActiveNumberingRangeId();

        $payload = [
            'document'            => '01',  // Factura de venta electrónica
            'numbering_range_id'  => $numberingRangeId,
            'reference_code'      => $referenceCode,
            'observation'         => $quote->observations ?? '',
            'payment_form'        => '1',   // 1 = contado
            'payment_method_code' => '10',  // 10 = efectivo
            'payment_due_date'    => now()->format('Y-m-d'),
            'customer'            => $customer,
            'items'               => $items,
        ];

        return $payload;
    }

    protected function getEstablishmentData(VntQuote $quote): array
    {
        $warehouse = null;

        if ($quote->warehouseId) {
            $warehouse = VntWarehouse::with(['company', 'city'])->find($quote->warehouseId);
        }

        if ($warehouse) {
            $company       = $warehouse->company;
            $name          = ($company && !empty($company->businessName))
                ? $company->businessName
                : ($warehouse->name ?? 'DISTRIBUIDORA');
            $municipalityId = $this->resolveMunicipalityId($warehouse->city->name ?? null);

            return [
                'name'            => $name,
                'address'         => $warehouse->address ?? 'N/A',
                'phone_number'    => $company->phone ?? $company->mobile ?? 'N/A',
                'email'           => ($company && $company->billingEmail)
                                        ? $company->billingEmail
                                        : 'facturacion@distribuidora.com',
                'municipality_id' => $municipalityId,
            ];
        }

        return [
            'name'            => 'DISTRIBUIDORA',
            'address'         => 'Dirección principal',
            'phone_number'    => 'N/A',
            'email'           => 'facturacion@distribuidora.com',
            'municipality_id' => $this->resolveMunicipalityId(null),
        ];
    }

    protected function getCustomerData(VntQuote $quote): array
    {
        if (!$quote->customer || !$quote->customer->company) {
            return [
                'identification_document_id' => 13,
                'identification'             => '222222222',
                'dv'                         => null,
                'company'                    => '',
                'trade_name'                 => '',
                'names'                      => 'Cliente Genérico',
                'address'                    => 'N/A',
                'email'                      => 'cliente@email.com',
                'phone'                      => 'N/A',
                'legal_organization_id'      => '2',
                'tribute_id'                 => '21',
                'municipality_id'            => (string) $this->resolveMunicipalityId(null),
            ];
        }

        $company           = $quote->customer->company;
        $customerWarehouse = $quote->customer;
        $contact           = $customerWarehouse->contacts->where('status', 1)->first();
        $docTypeId         = $this->mapDocumentTypeId($company->typeIdentificationId, $company->identification ?? '');
        $dv                = $company->checkDigit ?? null;
        $municipalityId    = $this->resolveMunicipalityId($customerWarehouse->city->name ?? null);

        $names = trim(($company->firstName ?? '') . ' ' . ($company->lastName ?? ''));
        if (!$names) {
            $names = $company->businessName ?? 'Cliente';
        }

        return [
            'identification_document_id' => $docTypeId,
            'identification'             => $company->identification ?? '222222222',
            'dv'                         => $dv !== null ? (string) $dv : null,
            'company'                    => $company->businessName ?? '',
            'trade_name'                 => '',
            'names'                      => $names,
            'address'                    => $customerWarehouse->address ?? 'N/A',
            'email'                      => $company->billingEmail
                                            ?? ($contact->email ?? 'cliente@email.com'),
            'phone'                      => $contact->personal_phone
                                            ?? ($contact->business_phone ?? 'N/A'),
            'legal_organization_id'      => '2',
            'tribute_id'                 => '21',
            'municipality_id'            => (string) $municipalityId,
        ];
    }

    protected function processQuoteItems(VntQuote $quote): array
    {
        $items = [];

        foreach ($quote->detalles as $detail) {
            $item = $detail->item;

            if (!$item) {
                Log::warning('Item no encontrado para detalle', ['detail_id' => $detail->id]);
                continue;
            }

            $taxRate  = (float) ($detail->tax_percentage ?? 19);
            $price    = (float) ($detail->price ?? 0);
            $quantity = (int)   ($detail->quantity ?? 1);

            $items[] = [
                'code_reference'    => (string) ($item->sku ?? $item->id),
                'name'              => $item->name ?? 'Producto',
                'quantity'          => $quantity,
                'discount_rate'     => 0,
                'price'             => $price,
                'tax_rate'          => number_format($taxRate, 2, '.', ''),
                'unit_measure_id'   => 70,  // Unidad
                'standard_code_id'  => 1,
                'is_excluded'       => $taxRate > 0 ? 0 : 1,
                'tribute_id'        => 1,
                'withholding_taxes' => [],
            ];
        }

        return $items;
    }

    /**
     * Resuelve el municipality_id de Factus a partir del nombre de ciudad.
     * Si no se encuentra, usa Bogotá como fallback.
     */
    protected function resolveMunicipalityId(?string $cityName): int
    {
        if ($cityName) {
            $id = $this->factusClient->getMunicipalityId($cityName);
            if ($id) {
                return $id;
            }
        }

        // Fallback: Bogotá D.C.
        $id = $this->factusClient->getMunicipalityId('Bogotá');
        return $id ?? 149; // 149 = Bogotá en Factus
    }

    /**
     * Convierte múltiples remisiones en una sola factura electrónica,
     * consolidando ítems (agrupa por itemId y suma cantidades).
     */
    public function convertRemissionsToInvoice(array $remissionIds): array
    {
        try {
            $remissions = \App\Models\Tenant\Remissions\InvRemissions::with([
                'details.item',
                'quote.customer.company',
                'quote.customer.contacts',
                'quote.customer.city',
                'quote.warehouse.city',
                'quote.warehouse.company',
            ])->whereIn('id', $remissionIds)->get();

            if ($remissions->isEmpty()) {
                throw new \Exception('No se encontraron remisiones con los IDs proporcionados.');
            }

            $firstRemission   = $remissions->first();
            $quote            = $firstRemission->quote;
            $customer         = $this->getCustomerData($quote);
            $items            = $this->consolidateRemissionItems($remissions);
            $numberingRangeId = $this->factusClient->getActiveNumberingRangeId();
            $consecutives     = $remissions->pluck('consecutive')->implode(', #');
            $referenceCode    = 'REM' . implode('_', $remissionIds) . '_' . time();

            $payload = [
                'document'            => '01',
                'numbering_range_id'  => $numberingRangeId,
                'reference_code'      => $referenceCode,
                'observation'         => 'Factura consolidada — Remisiones: #' . $consecutives,
                'payment_form'        => '1',
                'payment_method_code' => '10',
                'payment_due_date'    => now()->format('Y-m-d'),
                'customer'            => $customer,
                'items'               => $items,
            ];

            Log::info('Payload Factus (remisiones consolidadas)', $payload);

            $response = $this->factusClient->validateBill($payload);
            $bill     = $response['data']['bill'] ?? [];

            return [
                'success'        => true,
                'message'        => 'Factura creada y validada exitosamente',
                'data'           => $response,
                'factus_bill_id' => $bill['id'] ?? null,
                'invoice_number' => $bill['number'] ?? null,
                'public_url'     => $bill['public_url'] ?? null,
            ];

        } catch (FactusApiException $e) {
            Log::error('Error Factus al facturar remisiones', [
                'remission_ids' => $remissionIds,
                'error'         => $e->getMessage(),
                'status_code'   => $e->getStatusCode(),
            ]);
            return [
                'success'     => false,
                'error'       => 'Error en la API de facturación',
                'message'     => $e->getMessage(),
                'status_code' => $e->getStatusCode(),
            ];
        } catch (\Exception $e) {
            Log::error('Error general al facturar remisiones', [
                'remission_ids' => $remissionIds,
                'error'         => $e->getMessage(),
            ]);
            return [
                'success' => false,
                'error'   => 'Error interno del sistema',
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Consolida ítems de múltiples remisiones agrupando por itemId y sumando cantidades.
     */
    protected function consolidateRemissionItems($remissions): array
    {
        $consolidated = [];

        foreach ($remissions as $remission) {
            foreach ($remission->details as $detail) {
                $item = $detail->item;
                if (!$item) continue;

                $key      = $item->id;
                $taxRate  = (float) ($detail->tax ?? 19);
                $price    = (float) ($detail->value ?? 0);
                $quantity = (float) ($detail->quantity ?? 1);

                if (isset($consolidated[$key])) {
                    $consolidated[$key]['quantity'] += $quantity;
                } else {
                    $consolidated[$key] = [
                        'code_reference'    => (string) ($item->sku ?? $item->id),
                        'name'              => $item->name ?? 'Producto',
                        'quantity'          => $quantity,
                        'discount_rate'     => 0,
                        'price'             => $price,
                        'tax_rate'          => number_format($taxRate, 2, '.', ''),
                        'unit_measure_id'   => 70,
                        'standard_code_id'  => 1,
                        'is_excluded'       => $taxRate > 0 ? 0 : 1,
                        'tribute_id'        => 1,
                        'withholding_taxes' => [],
                    ];
                }
            }
        }

        return array_values($consolidated);
    }

    protected function generateReferenceCode(VntQuote $quote): string
    {
        return 'COT' . $quote->consecutive . '_' . time();
    }

    /**
     * Mapear typeIdentificationId de la BD al identification_document_id de Factus.
     *
     * BD interna:                    Factus:
     *   1 = Cédula Ciudadanía (CC) → 3
     *   2 = NIT                    → 6
     *   3 = Cédula Extranjería(CE) → 5
     *   4 = Pasaporte              → 7
     *   5 = Tarjeta de Identidad   → 2
     *   6 = Registro Civil         → 1
     */
    protected function mapDocumentTypeId(?int $typeIdentificationId, string $identification): int
    {
        $map = [
            1 => 3,   // Cédula de ciudadanía
            2 => 6,   // NIT
            3 => 5,   // Cédula de extranjería
            4 => 7,   // Pasaporte
            5 => 2,   // Tarjeta de identidad
            6 => 1,   // Registro civil
        ];

        if ($typeIdentificationId && isset($map[$typeIdentificationId])) {
            return $map[$typeIdentificationId];
        }

        // Fallback por longitud del número de identificación
        $length = strlen($identification);
        if ($length >= 9) {
            return 6;  // NIT (≥9 dígitos)
        }

        return 3; // Cédula de ciudadanía por defecto
    }
}
