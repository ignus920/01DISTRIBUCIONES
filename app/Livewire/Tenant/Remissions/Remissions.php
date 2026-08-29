<?php

namespace App\Livewire\Tenant\Remissions;

use App\Models\Tenant\Remissions\InvRemissions;
use App\Models\Tenant\Invoices\VntInvoicePayments;
use App\Models\Tenant\Sales\VntInvoice;
use App\Models\Tenant\Sales\VntInvoicesXsale;
use App\Models\Auth\Tenant;
use App\Services\Tenant\TenantManager;
use App\Services\Factus\QuoteToInvoiceService;
use App\Traits\HasCompanyConfiguration;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Central\VntWarehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Jenssegers\Agent\Agent;
use App\Traits\CanPrintDocuments;

class Remissions extends Component
{
    use WithPagination, HasCompanyConfiguration, CanPrintDocuments;

    // Propiedades para búsqueda y selección
    public $search = '';
    public $perPage = 10;
    public $selectedRemissions = [];
    public $selectAll = false;

    // Propiedades para búsqueda avanzada
    public $searchNit = '';
    public $searchName = '';
    public $searchQuote = '';
    public $searchStartDate = '';
    public $searchEndDate = '';
    public $searchSalesman = ''; // Nuevo filtro por vendedor
    public $showAdvancedSearch = false;

    // Propiedades para el modal de detalle
    public $showDetailModal = false;
    public $selectedRemission = null;

    // Propiedades para el modal de facturación
    public $showInvoiceModal      = false;
    public $invoicePreviewCustomer = [];
    public $invoicePreviewRemissions = [];
    public $invoicePreviewTotal    = 0;
    public $invoicePreviewItemsCount = 0;

    protected $paginationTheme = 'tailwind';

    /**
     * Se ejecuta al iniciar el componente para asegurar la conexión con el tenant.
     */
    public function boot()
    {
        $this->ensureTenantConnection();
    }

    /**
     * Inicializa el componente, configurando la conexión y la empresa.
     */
    public function mount()
    {
        $this->ensureTenantConnection();
        $this->initializeCompanyConfiguration();

        // Inicializar fechas por defecto (últimos 20 días) si están vacías
        if (empty($this->searchStartDate)) {
            $this->searchStartDate = now()->subDays(20)->format('Y-m-d');
        }
        if (empty($this->searchEndDate)) {
            $this->searchEndDate = now()->format('Y-m-d');
        }
    }

    /**
     * Se ejecuta cuando la propiedad de búsqueda cambia, reseteando la paginación.
     */
    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingSearchNit()
    {
        $this->resetPage();
    }
    public function updatingSearchName()
    {
        $this->resetPage();
    }
    public function updatingSearchQuote()
    {
        $this->resetPage();
    }
    public function updatingSearchStartDate()
    {
        $this->resetPage();
    }
    public function updatingSearchEndDate()
    {
        $this->resetPage();
    }
    public function updatingSearchSalesman()
    {
        $this->resetPage();
    }

    /**
     * Maneja la selección de todas las remisiones en la página actual
     */
    public function updatedSelectAll($value)
    {
        $this->ensureTenantConnection();
        if ($value) {
            $this->selectedRemissions = InvRemissions::query()
                ->when($this->search, function ($query) {
                    $this->applyBaseFilters($query);
                })
                ->when(auth()->user()->profile_id == 4, function ($query) {
                    $query->where('userId', auth()->id());
                })
                ->where('status', 'REGISTRADO') // Solo se facturan las registradas
                ->pluck('id')
                ->map(fn($id) => (string) $id)
                ->toArray();
        } else {
            $this->selectedRemissions = [];
        }
    }

    /**
     * Limpia todos los filtros de búsqueda
     */
    public function clearFilters()
    {
        $this->search = '';
        $this->searchNit = '';
        $this->searchName = '';
        $this->searchQuote = '';
        $this->searchStartDate = '';
        $this->searchEndDate = '';
        $this->searchSalesman = '';
        $this->resetPage();
    }

    /**
     * Acceso directo desde mobile: selecciona una sola remisión y abre el modal de facturación.
     */
    public function facturarRemisionMobile($id)
    {
        $this->selectedRemissions = [(string) $id];
        $this->prepareFacturacion();
    }

    /**
     * Prepara el modal de confirmación de facturación para las remisiones seleccionadas.
     * Valida que sean del mismo cliente y construye el preview.
     */
    public function prepareFacturacion()
    {
        if (empty($this->selectedRemissions)) {
            $this->dispatch('show-toast', ['type' => 'warning', 'message' => 'Selecciona al menos una remisión.']);
            return;
        }

        $this->ensureTenantConnection();

        $remisiones = InvRemissions::with(['quote.customer.company', 'details'])
            ->whereIn('id', $this->selectedRemissions)
            ->get();

        // Validar que todas sean del mismo cliente
        $customerIds = $remisiones->pluck('quote.customerId')->filter()->unique();
        if ($customerIds->count() > 1) {
            $this->dispatch('show-alert', [
                'icon'  => 'error',
                'title' => 'Clientes diferentes',
                'text'  => 'Solo puedes facturar remisiones del mismo cliente a la vez. Por favor selecciona únicamente remisiones que pertenezcan al mismo cliente.',
            ]);
            return;
        }

        $firstRemission = $remisiones->first();
        $company        = $firstRemission->quote?->customer?->company;

        $this->invoicePreviewCustomer = [
            'name'           => $firstRemission->quote?->customer_name ?? 'N/A',
            'identification' => $company?->identification ?? 'N/A',
        ];

        $this->invoicePreviewRemissions = $remisiones->map(fn($r) => [
            'id'          => $r->id,
            'consecutive' => $r->consecutive,
            'date'        => $r->created_at->format('d/m/Y H:i'),
            'items_count' => $r->details->count(),
            'total'       => $r->details->sum(fn($d) => $d->quantity * $d->value),
        ])->toArray();

        $this->invoicePreviewTotal      = collect($this->invoicePreviewRemissions)->sum('total');
        $this->invoicePreviewItemsCount = collect($this->invoicePreviewRemissions)->sum('items_count');
        $this->showInvoiceModal         = true;
    }

    /**
     * Ejecuta la facturación consolidada de las remisiones seleccionadas.
     */
    public function confirmarFacturacion()
    {
        if (empty($this->selectedRemissions)) {
            return;
        }

        $this->ensureTenantConnection();

        try {
            $service = app(QuoteToInvoiceService::class);
            $result  = $service->convertRemissionsToInvoice(array_map('intval', $this->selectedRemissions));

            if (!$result['success']) {
                $errorMessage = $this->parseFactusError($result['message'] ?? 'Error desconocido');
                $this->dispatch('show-toast', ['type' => 'error', 'message' => $errorMessage]);
                return;
            }

            DB::transaction(function () use ($result) {
                $remisiones = InvRemissions::with('quote')
                    ->whereIn('id', $this->selectedRemissions)
                    ->get();

                $firstRemission = $remisiones->first();
                $consecutive    = (VntInvoice::max('consecutive') ?? 0) + 1;

                $invoice = VntInvoice::create([
                    'consecutive'    => $consecutive,
                    'status'         => 'FACTURADO',
                    'status_payment' => 'REGISTRADO',
                    'api_data_id'    => $result['factus_bill_id'],
                    'quoteId'        => $firstRemission->quoteId,
                    'warehouseId'    => $firstRemission->warehouseId,
                    'remission'      => $firstRemission->id,
                    'invoiceNumber'  => $result['invoice_number'] ?? '',
                    'creditNote'     => 0,
                ]);

                // Si las remisiones ya tenían pagos en caja menor (invoiceId = remissionId),
                // crear los registros correspondientes en vnt_invoice_payments para la nueva factura.
                $existingRemissionPayments = DB::table('vnt_detail_petty_cash')
                    ->select('value', 'methodPaymentId')
                    ->whereIn('invoiceId', $this->selectedRemissions)
                    ->whereNull('deleted_at')
                    ->get();

                foreach ($existingRemissionPayments as $payment) {
                    VntInvoicePayments::create([
                        'value' => (float) $payment->value,
                        'invoiceId' => $invoice->id,
                        'methodPaymentId' => $payment->methodPaymentId,
                    ]);
                }

                // Actualizar estado de pago de la factura según lo ya pagado en remisiones.
                $paidTotal = (float) $existingRemissionPayments->sum('value');
                $invoiceTotal = (float) $remisiones->sum(function ($remision) {
                    return $remision->details->sum(fn($d) => $d->quantity * $d->value);
                });

                if ($paidTotal > 0) {
                    $invoice->status_payment = $paidTotal >= $invoiceTotal ? 'PAGADO' : 'ABONO';
                    $invoice->save();
                }

                foreach ($remisiones as $remision) {
                    VntInvoicesXsale::create([
                        'remissionId' => $remision->id,
                        'quoteId'     => $remision->quoteId,
                        'invoiceId'   => $invoice->id,
                    ]);

                    if ($remision->quoteId) {
                        \App\Models\Tenant\Quoter\VntQuote::where('id', $remision->quoteId)
                            ->update(['status' => 'FACTURADO']);
                    }
                }

                Log::info('Remisiones facturadas correctamente', [
                    'remission_ids'  => $this->selectedRemissions,
                    'invoice_id'     => $invoice->id,
                    'invoice_number' => $result['invoice_number'],
                    'invoice_payments_created' => $existingRemissionPayments->count(),
                ]);
            });

            $this->showInvoiceModal   = false;
            $this->selectedRemissions = [];
            $this->selectAll          = false;

            $this->dispatch('show-toast', [
                'type'    => 'success',
                'message' => 'Facturado exitosamente — ' . ($result['invoice_number'] ?? 'N/A'),
            ]);

            if (!empty($result['public_url'])) {
                $this->dispatch('open-invoice-pdf', ['url' => $result['public_url']]);
            }
        } catch (\Exception $e) {
            Log::error('Error en confirmarFacturacion: ' . $e->getMessage());
            $this->dispatch('show-toast', ['type' => 'error', 'message' => 'Error al procesar la facturación: ' . $e->getMessage()]);
        }
    }

    /**
     * Parsea los errores de Factus para mostrar mensajes legibles.
     */
    private function parseFactusError(string $rawMessage): string
    {
        $jsonStart = strpos($rawMessage, '{');
        if ($jsonStart === false) {
            return $rawMessage;
        }

        $jsonString = substr($rawMessage, $jsonStart);
        $data       = json_decode($jsonString, true);

        if (!$data || !isset($data['errors'])) {
            return $rawMessage;
        }

        $messages = [];
        foreach ($data['errors'] as $field => $fieldErrors) {
            foreach ((array) $fieldErrors as $msg) {
                $messages[] = $msg;
            }
        }

        return implode(' | ', $messages) ?: $rawMessage;
    }

    /**
     * Aplica los filtros base a la consulta
     */
    private function applyBaseFilters($query)
    {
        $query->where(function ($q) {
            $q->where('consecutive', 'like', '%' . $this->search . '%')
                ->orWhere('status', 'like', '%' . $this->search . '%')
                ->orWhereHas('quote.customer.company', function ($sub) {
                    $sub->where('businessName', 'like', '%' . $this->search . '%')
                        ->orWhere('firstName', 'like', '%' . $this->search . '%')
                        ->orWhere('lastName', 'like', '%' . $this->search . '%');
                });
        });

        // Búsqueda avanzada
        if ($this->searchNit) {
            $query->whereHas('quote.customer.company', function ($q) {
                $q->where('identification', 'like', '%' . $this->searchNit . '%');
            });
        }

        if ($this->searchName) {
            $query->whereHas('quote.customer.company', function ($q) {
                $q->where('businessName', 'like', '%' . $this->searchName . '%')
                    ->orWhere('firstName', 'like', '%' . $this->searchName . '%')
                    ->orWhere('lastName', 'like', '%' . $this->searchName . '%');
            });
        }

        if ($this->searchQuote) {
            $query->whereHas('quote', function ($q) {
                $q->where('consecutive', 'like', '%' . $this->searchQuote . '%');
            });
        }

        if ($this->searchStartDate) {
            $query->whereDate('created_at', '>=', $this->searchStartDate);
        }

        if ($this->searchEndDate) {
            $query->whereDate('created_at', '<=', $this->searchEndDate);
        }

        if ($this->searchSalesman) {
            $query->where('userId', $this->searchSalesman);
        }
    }

    /**
     * Asegura que exista una conexión válida con el tenant basada en la sesión.
     */
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

        $tenantManager = app(TenantManager::class);
        $tenantManager->setConnection($tenant);
        tenancy()->initialize($tenant);
    }

    /**
     * Carga y muestra los detalles de una remisión específica en un modal.
     * 
     * @param int $id ID de la remisión
     */
    public function viewDetails($id)
    {
        $this->ensureTenantConnection();
        $this->selectedRemission = InvRemissions::with([
            'quote.customer',
            'quote.warehouse.contacts',
            'quote.branch',
            'details.item'
        ])->find($id);

        $this->showDetailModal = true;
    }

    /**
     * Renderiza la vista del componente con el listado de remisiones filtrado.
     */
    /**
     * Redirige al cotizador para editar una remisión existente
     */
    public function editarRemision($id)
    {
        $agent = new Agent();

        if ($agent->isMobile() || $agent->isTablet()) {
            return redirect()->route('tenant.quoter.products.mobile.remission', ['remissionId' => $id]);
        }

        return redirect()->route('tenant.quoter.products.desktop.remission', ['remissionId' => $id]);
    }


    /**
     * Anula una remisión y regresa la cotización a estado REGISTRADO
     * 
     * @param int $id ID de la remisión
     */
    /**
     * Avanza el status de la remisión al siguiente en la progresión:
     * REGISTRADO → ALISTAMIENTO → EN RECORRIDO → ENTREGADO (bloqueado)
     */
    public function cambiarStatus(int $id)
    {
        $this->ensureTenantConnection();

        $progression = [
            'REGISTRADO'   => 'ALISTAMIENTO',
            'ALISTAMIENTO' => 'EN RECORRIDO',
            'EN RECORRIDO' => 'ENTREGADO',
        ];

        try {
            $remission = InvRemissions::findOrFail($id);

            if ($remission->status === 'ENTREGADO') {
                $this->dispatch('show-toast', [
                    'type'    => 'warning',
                    'message' => 'La remisión ya fue entregada y no puede cambiar de estado.',
                ]);
                return;
            }

            if (!array_key_exists($remission->status, $progression)) {
                $this->dispatch('show-toast', [
                    'type'    => 'error',
                    'message' => 'No se puede cambiar el estado de esta remisión.',
                ]);
                return;
            }

            $nuevoStatus = $progression[$remission->status];
            $remission->status = $nuevoStatus;
            $remission->save();

            $this->dispatch('show-toast', [
                'type'    => 'success',
                'message' => 'Estado actualizado a ' . $nuevoStatus,
            ]);
        } catch (\Exception $e) {
            Log::error('❌ Error al cambiar status de remisión: ' . $e->getMessage());
            $this->dispatch('show-toast', [
                'type'    => 'error',
                'message' => 'Error al cambiar el estado: ' . $e->getMessage(),
            ]);
        }
    }

    public function anularRemision($id)
    {
        $this->ensureTenantConnection();

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($id) {
                // 1. Buscar la remisión
                $remission = InvRemissions::findOrFail($id);

                // 2. Anular la remisión
                $remission->status = 'ANULADO';
                $remission->save();

                // 3. Si tiene cotización asociada, regresarla a REGISTRADO
                if ($remission->quoteId) {
                    $quote = \App\Models\Tenant\Quoter\VntQuote::find($remission->quoteId);
                    if ($quote) {
                        $quote->status = 'REGISTRADO';
                        $quote->save();
                    }
                }

                Log::info('🚫 Remisión anulada correctamente', [
                    'remission_id' => $id,
                    'consecutive' => $remission->consecutive,
                    'quote_id' => $remission->quoteId
                ]);
            });

            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => 'Remisión anulada correctamente. La cotización vuelve a estar disponible.'
            ]);
        } catch (\Exception $e) {
            Log::error('❌ Error al anular remisión: ' . $e->getMessage());
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Error al anular la remisión: ' . $e->getMessage()
            ]);
        }
    }

    public function render()
    {
        $this->ensureTenantConnection();

        // Cargar lista de vendedores (usuarios con perfil de ventas o que han realizado pedidos)
        $salesmen = \App\Models\Auth\User::whereHas('profile', function($q) {
            $q->where('id', 4); // Asumiendo que 4 es el perfil de vendedor según el código
        })->get();

        // Consulta de remisiones con relaciones y filtros de búsqueda
        $remissions = InvRemissions::with(['quote.customer.company', 'quote.warehouse', 'details', 'deliveryType', 'methodPayment', 'invoiceXsale.invoice', 'user'])
            ->where(function ($query) {
                $this->applyBaseFilters($query);
            })
            ->when(auth()->user()->profile_id == 4, function ($query) {
                $query->where('userId', auth()->id());
            })
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage);

        // Cruce de pagos: vnt_detail_petty_cash.invoiceId = inv_remissions.id (remissionId)
        $remissionIds = $remissions->getCollection()->pluck('id')->all();
        $paymentSummaryByRemission = collect();

        if (!empty($remissionIds)) {
            $paymentSummaryByRemission = DB::table('vnt_detail_petty_cash')
                ->select(
                    'invoiceId',
                    DB::raw('COUNT(*) as payments_count'),
                    DB::raw('SUM(value) as paid_value')
                )
                ->whereIn('invoiceId', $remissionIds)
                ->whereNull('deleted_at')
                ->groupBy('invoiceId')
                ->get()
                ->keyBy('invoiceId');
        }

        $remissions->getCollection()->transform(function ($remission) use ($paymentSummaryByRemission) {
            $paymentSummary = $paymentSummaryByRemission->get($remission->id);
            $remission->has_registered_payment = !is_null($paymentSummary);
            $remission->registered_payment_count = (int) ($paymentSummary->payments_count ?? 0);
            $remission->registered_payment_total = (float) ($paymentSummary->paid_value ?? 0);
            return $remission;
        });

        return view('livewire.tenant.remissions.remissions', [
            'remissions' => $remissions,
            'salesmen'   => $salesmen
        ])->layout('layouts.app', ['header' => 'Pedidos']);
    }
}
