<?php

namespace App\Http\Controllers;

use App\Services\Factus\FactusClient;
use App\Services\Factus\FactusApiException;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class FactusController extends Controller
{
    protected FactusClient $factusClient;

    public function __construct(FactusClient $factusClient)
    {
        $this->factusClient = $factusClient;
    }

    public function getInvoices(Request $request): JsonResponse
    {
        try {
            $invoices = $this->factusClient->getInvoices();
            return response()->json([
                'success' => true,
                'data' => $invoices
            ]);
        } catch (FactusApiException $e) {
            Log::error('Error getting invoices: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Error al obtener facturas',
                'message' => $e->getMessage()
            ], $e->getStatusCode() ?: 500);
        }
    }

    public function createInvoice(Request $request): JsonResponse
    {
        try {
            $invoiceData = $request->all();
            $invoice = $this->factusClient->createInvoice($invoiceData);

            return response()->json([
                'success' => true,
                'data' => $invoice,
                'message' => 'Factura creada exitosamente'
            ], 201);
        } catch (FactusApiException $e) {
            Log::error('Error creating invoice: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Error al crear factura',
                'message' => $e->getMessage()
            ], $e->getStatusCode() ?: 500);
        }
    }

    public function getInvoice(Request $request, string $id): JsonResponse
    {
        try {
            $invoice = $this->factusClient->getInvoice($id);
            return response()->json([
                'success' => true,
                'data' => $invoice
            ]);
        } catch (FactusApiException $e) {
            Log::error('Error getting invoice: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Error al obtener factura',
                'message' => $e->getMessage()
            ], $e->getStatusCode() ?: 500);
        }
    }

    public function getInvoicePdf(Request $request, string $id)
    {
        try {
            $response = $this->factusClient->getInvoicePdf($id);

            return response($response->body())
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="factura-' . $id . '.pdf"');
        } catch (FactusApiException $e) {
            Log::error('Error getting invoice PDF: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Error al obtener PDF de factura',
                'message' => $e->getMessage()
            ], $e->getStatusCode() ?: 500);
        }
    }

    public function getInvoiceXml(Request $request, string $id)
    {
        try {
            $response = $this->factusClient->getInvoiceXml($id);

            return response($response->body())
                ->header('Content-Type', 'application/xml')
                ->header('Content-Disposition', 'inline; filename="factura-' . $id . '.xml"');
        } catch (FactusApiException $e) {
            Log::error('Error getting invoice XML: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Error al obtener XML de factura',
                'message' => $e->getMessage()
            ], $e->getStatusCode() ?: 500);
        }
    }

    public function sendInvoice(Request $request, string $id): JsonResponse
    {
        try {
            $emailData = $request->all();
            $result = $this->factusClient->sendInvoice($id, $emailData);

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Factura enviada exitosamente'
            ]);
        } catch (FactusApiException $e) {
            Log::error('Error sending invoice: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Error al enviar factura',
                'message' => $e->getMessage()
            ], $e->getStatusCode() ?: 500);
        }
    }

    public function deleteInvoice(Request $request, string $id): JsonResponse
    {
        try {
            $result = $this->factusClient->deleteInvoice($id);

            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Factura eliminada exitosamente'
            ]);
        } catch (FactusApiException $e) {
            Log::error('Error deleting invoice: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Error al eliminar factura',
                'message' => $e->getMessage()
            ], $e->getStatusCode() ?: 500);
        }
    }

    public function getCreditNotes(Request $request): JsonResponse
    {
        try {
            $creditNotes = $this->factusClient->getCreditNotes();
            return response()->json([
                'success' => true,
                'data' => $creditNotes
            ]);
        } catch (FactusApiException $e) {
            Log::error('Error getting credit notes: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Error al obtener notas de crédito',
                'message' => $e->getMessage()
            ], $e->getStatusCode() ?: 500);
        }
    }

    public function createCreditNote(Request $request): JsonResponse
    {
        try {
            $creditNoteData = $request->all();
            $creditNote = $this->factusClient->createCreditNote($creditNoteData);

            return response()->json([
                'success' => true,
                'data' => $creditNote,
                'message' => 'Nota de crédito creada exitosamente'
            ], 201);
        } catch (FactusApiException $e) {
            Log::error('Error creating credit note: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Error al crear nota de crédito',
                'message' => $e->getMessage()
            ], $e->getStatusCode() ?: 500);
        }
    }

    public function getCreditNote(Request $request, string $id): JsonResponse
    {
        try {
            $creditNote = $this->factusClient->getCreditNote($id);
            return response()->json([
                'success' => true,
                'data' => $creditNote
            ]);
        } catch (FactusApiException $e) {
            Log::error('Error getting credit note: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Error al obtener nota de crédito',
                'message' => $e->getMessage()
            ], $e->getStatusCode() ?: 500);
        }
    }

    public function getCreditNotePdf(Request $request, string $id)
    {
        try {
            $response = $this->factusClient->getCreditNotePdf($id);

            return response($response->body())
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="nota-credito-' . $id . '.pdf"');
        } catch (FactusApiException $e) {
            Log::error('Error getting credit note PDF: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Error al obtener PDF de nota de crédito',
                'message' => $e->getMessage()
            ], $e->getStatusCode() ?: 500);
        }
    }

    public function getPaymentMethods(Request $request): JsonResponse
    {
        try {
            $paymentMethods = $this->factusClient->getPaymentMethods();
            return response()->json([
                'success' => true,
                'data' => $paymentMethods
            ]);
        } catch (FactusApiException $e) {
            Log::error('Error getting payment methods: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Error al obtener métodos de pago',
                'message' => $e->getMessage()
            ], $e->getStatusCode() ?: 500);
        }
    }

    public function getDocumentTypes(Request $request): JsonResponse
    {
        try {
            $documentTypes = $this->factusClient->getDocumentTypes();
            return response()->json([
                'success' => true,
                'data' => $documentTypes
            ]);
        } catch (FactusApiException $e) {
            Log::error('Error getting document types: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Error al obtener tipos de documento',
                'message' => $e->getMessage()
            ], $e->getStatusCode() ?: 500);
        }
    }

    public function renewToken(Request $request): JsonResponse
    {
        try {
            $result = $this->factusClient->renewToken();
            return response()->json([
                'success' => true,
                'data' => $result,
                'message' => 'Token renovado exitosamente'
            ]);
        } catch (FactusApiException $e) {
            Log::error('Error renewing token: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Error al renovar token',
                'message' => $e->getMessage()
            ], $e->getStatusCode() ?: 500);
        }
    }
}