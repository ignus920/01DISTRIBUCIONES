<?php

namespace App\Livewire\TAT\Quoter;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\TAT\Quoter\Quote;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Auth\Tenant;
use App\Services\Tenant\TenantManager;
use App\Traits\HasCompanyConfiguration;
use App\Models\Central\VntWarehouse;

class SalesList extends Component
{
    use WithPagination, \App\Traits\Livewire\WithExport, HasCompanyConfiguration;

    public $search = '';
    public $perPage = 10;
    public $companyId;

    // Propiedades para el modal de detalles
    public $showDetailModal = false;
    public $selectedQuote = null;

    protected $paginationTheme = 'tailwind';

    public function mount()
    {
        // Usar la misma lógica que QuoterView para obtener company_id
        $user = Auth::user();
        $this->companyId = $this->getUserCompanyId($user);
    }

    /**
     * Obtener el company_id del usuario autenticado (copiado de QuoterView)
     */
    protected function getUserCompanyId($user)
    {
        if ($user->contact_id) {
            $contact = DB::table('vnt_contacts')
                ->where('id', $user->contact_id)
                ->first();

            if ($contact && isset($contact->warehouseId)) {
                $warehouse = DB::table('vnt_warehouses')
                    ->where('id', $contact->warehouseId)
                    ->first();

                return $warehouse ? $warehouse->companyId : null;
            }
        }

        return null;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    /**
     * Mostrar modal de detalles de la venta
     */
    public function showDetails($quoteId)
    {
        $this->selectedQuote = Quote::with(['customer', 'items.item', 'user'])
            ->where('id', $quoteId)
            ->where('company_id', $this->companyId)
            ->first();

        if ($this->selectedQuote) {
            $this->showDetailModal = true;
        }
    }

    /**
     * Cerrar modal de detalles
     */
    public function closeDetailModal()
    {
        $this->showDetailModal = false;
        $this->selectedQuote = null;
    }

    /**
     * Redirigir al sistema de pagos
     */
    public function showPayment($quoteId)
    {
        $quote = Quote::where('id', $quoteId)
            ->where('company_id', $this->companyId)
            ->first();

        if ($quote) {
            // Redirigir a la ruta de pagos con parámetro de origen
            return redirect()->route('tenant.payment.quote', [
                'quoteId' => $quoteId,
                'from' => 'sales-list'
            ]);
        } else {
            session()->flash('error', 'No se encontró la cotización.');
        }
    }

    /**
     * Editar una venta existente
     */
    public function editSale($quoteId)
    {
        $quote = Quote::with(['customer', 'items.item'])
            ->where('id', $quoteId)
            ->where('company_id', $this->companyId)
            ->first();

        if ($quote) {
            // Verificar que la venta no esté pagada
            if ($quote->status === 'Pagado') {
                session()->flash('error', 'No se puede editar una venta que ya está pagada.');
                return;
            }

            // Redirigir a QuoterView con el ID de la cotización para editar
            return redirect()->route('tenant.tat.quoter.index', ['edit' => $quoteId]);
        } else {
            session()->flash('error', 'No se encontró la cotización.');
        }
    }

    public function getPrintCopiesLimit(): int
    {
        Log::info('🔍 getPrintCopiesLimit() - Inicio del debug', [
            'companyId' => $this->currentCompanyId ?? 'NULL',
            'configService_exists' => isset($this->configService) ? 'YES' : 'NO',
            'method' => 'getPrintCopiesLimit()'
        ]);

        try {
            $value = $this->getOptionValue(3);

            Log::info('📊 getPrintCopiesLimit() - Valor obtenido', [
                'raw_value' => $value,
                'value_type' => gettype($value),
                'is_null' => $value === null ? 'YES' : 'NO',
                'final_return' => $value ?? 0
            ]);

            $finalValue = $value ?? 0;

            Log::info('✅ getPrintCopiesLimit() - Resultado final', [
                'final_value' => $finalValue,
                'format_description' => $finalValue == 0 ? 'POS (térmica 80mm)' : 'Carta (institucional)',
                'option_3_explanation' => '0=POS, 1=Institucional'
            ]);

            return $finalValue;
        } catch (\Exception $e) {
            Log::error('❌ getPrintCopiesLimit() - Error al obtener valor', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return 0; // Default a POS en caso de error
        }
    }

    public function printQuote($id)
    {
        // Debug: Log para verificar que el método se está llamando
        Log::info('🖨️ printQuote llamado', ['quote_id' => $id]);

        // Asegurar que todas las conexiones estén establecidas
        $this->ensureTenantConnection();
        $this->initializeCompanyConfiguration();

        try {
            Log::info('🔄 Iniciando carga de cotización...');

            // Cargar la cotización paso a paso para debug
            Log::info('🔄 Cargando cotización básica...');
            $quote = Quote::findOrFail($id);
            Log::info('📄 Cotización básica cargada', ['consecutive' => $quote->consecutive]);

            Log::info('🔄 Cargando detalles...');
            try {
                $quote->load('items');
                Log::info('📋 Detalles cargados', ['count' => $quote->items->count()]);
            } catch (\Exception $detailError) {
                Log::error('❌ Error cargando detalles', ['error' => $detailError->getMessage()]);
                throw $detailError;
            }

            Log::info('🔄 Cargando cliente...');
            try {
                $quote->load('customer');
                Log::info('👤 Cliente cargado', ['customer_id' => $quote->customerId]);
            } catch (\Exception $customerError) {
                Log::error('❌ Error cargando cliente', ['error' => $customerError->getMessage()]);
                // Continuar sin cliente para debug
                $quote->customer = null;
            }

            // Nota: No cargamos warehouse aquí porque se consultará directamente desde central en getCompanyInfo()
            Log::info('🔄 WarehouseId de la cotización: ' . $quote->warehouseId);

            Log::info('🔄 Cargando items de los detalles...');
            try {
                $quote->load('items.item');
                Log::info('📦 Items cargados', ['items_count' => $quote->items->count()]);

                // Debug: verificar si hay items null
                $nullItems = $quote->items->whereNull('item')->count();
                if ($nullItems > 0) {
                    Log::warning('⚠️ Hay items null', ['null_count' => $nullItems]);
                } else {
                    Log::info('✅ Todos los items están correctamente cargados');
                }
            } catch (\Exception $itemError) { // Cambiado de Exception a Throwable para capturar más errores
                // Log detallado del error
                Log::error('❌ Error cargando items', ['error' => $itemError->getMessage()]);
            }

            // Obtener información de la empresa
            $company = $this->getCompanyInfo($quote);
            Log::info('🏢 Empresa cargada', ['company' => $company->businessName ?? 'N/A']);

            // Determinar el formato de impresión según configuración
            $printFormat = $this->getPrintCopiesLimit(); // 0 = POS Simple, 1 = Institucional
            Log::info('🎯 Formato determinado desde configuración', ['printFormat' => $printFormat]);

            // Datos para la vista
            $data = [
                'quote' => $quote,
                'customer' => $quote->customer,
                'company' => $company,
                'showQR' => true, // Opcional: mostrar código QR
                'defaultObservations' => 'Observaciones por defecto'
            ];
            Log::info('📝 Datos preparados para la vista');

            // Seleccionar la vista según el formato
            $viewName = ($printFormat === 1)
                ? 'livewire.tat.quoter.print.print-carta'
                : 'livewire.tat.quoter.print.print-pos';
            Log::info('🎨 Vista seleccionada', ['viewName' => $viewName]);

            // Generar el HTML y redirigir a nueva ventana para impresión
            Log::info('🔄 Iniciando generación de HTML...');

            try {
                $html = view($viewName, $data)->render();
                Log::info('✅ HTML generado exitosamente', ['length' => strlen($html)]);
            } catch (\Exception $viewError) {
                Log::error('❌ Error generando vista', ['error' => $viewError->getMessage()]);
                throw $viewError;
            }

            // Guardar temporalmente el HTML para la impresión
            $tempFileName = 'quote_' . $id . '_' . time() . '.html';
            $tempPath = storage_path('app/temp/' . $tempFileName);
            Log::info('📁 Archivo temporal', ['fileName' => $tempFileName, 'path' => $tempPath]);

            // Crear directorio si no existe
            if (!file_exists(dirname($tempPath))) {
                mkdir(dirname($tempPath), 0755, true);
                Log::info('📂 Directorio temp creado');
            }

            file_put_contents($tempPath, $html);
            Log::info('💾 Archivo guardado', ['size' => filesize($tempPath) . ' bytes']);

            // Generar la URL del archivo
            $printUrl = route('quoter.print.temp', ['file' => $tempFileName]);
            Log::info('🔗 URL generada', ['url' => $printUrl]);

            // Dispatch evento para abrir ventana de impresión
            $this->dispatch('open-print-window', [
                'url' => $printUrl,
                'format' => $printFormat === 1 ? 'carta' : 'pos'
            ]);
            Log::info('🚀 Evento dispatch enviado');

            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => 'Cotización #' . $quote->consecutive . ' preparada para impresión (' . ($printFormat === 1 ? 'Formato Carta' : 'Formato POS') . ')'
            ]);
        } catch (\Exception $e) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Error al preparar impresión: ' . $e->getMessage()
            ]);
        }
    }


    public function render()
    {
        $quotes = Quote::where('company_id', $this->companyId)
            ->select('tat_quotes.*')
            ->addSelect(DB::raw('(SELECT SUM((price + (price * tax_percentage / 100)) * quantity) FROM tat_detail_quotes WHERE quoteId = tat_quotes.id) as total'))
            ->with(['user', 'customer', 'items'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('consecutive', 'like', '%' . $this->search . '%')
                        ->orWhereHas('customer', function ($customerQuery) {
                            $customerQuery->where('businessName', 'like', '%' . $this->search . '%')
                                ->orWhere('firstName', 'like', '%' . $this->search . '%')
                                ->orWhere('lastName', 'like', '%' . $this->search . '%')
                                ->orWhere('identification', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage);

        return view('livewire.TAT.quoter.sales-list', [
            'quotes' => $quotes
        ])->layout('layouts.app'); // 👈 aquí agregas el layout
    }

    /**
     * Métodos para Exportación
     */

    protected function getExportData()
    {
        return Quote::where('company_id', $this->companyId)
            ->with(['user', 'customer', 'items'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('consecutive', 'like', '%' . $this->search . '%')
                        ->orWhereHas('customer', function ($customerQuery) {
                            $customerQuery->where('businessName', 'like', '%' . $this->search . '%')
                                ->orWhere('firstName', 'like', '%' . $this->search . '%')
                                ->orWhere('lastName', 'like', '%' . $this->search . '%')
                                ->orWhere('identification', 'like', '%' . $this->search . '%');
                        });
                });
            })
            ->orderBy('created_at', 'desc')
            ->get();
    }

    protected function getExportHeadings(): array
    {
        return ['ID', 'Consecutivo', 'Cliente', 'Vendedor', 'Total', 'Estado', 'Fecha'];
    }

    protected function getExportMapping()
    {
        return function ($quote) {
            $customerName = $quote->customer
                ? ($quote->customer->businessName ?: $quote->customer->firstName . ' ' . $quote->customer->lastName)
                : 'N/A';

            return [
                $quote->id,
                $quote->consecutive ?: 'N/A',
                $customerName,
                $quote->user->name ?? 'N/A',
                $quote->total,
                $quote->status,
                $quote->created_at ? $quote->created_at->format('Y-m-d H:i:s') : 'N/A',
            ];
        };
    }

    protected function getExportFilename(): string
    {
        return 'ventas_tat_' . now()->format('Y-m-d_His');
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
    private function getCompanyInfo($quote = null)
    {
        Log::info('🏢 getCompanyInfo llamado');

        // Intentar obtener información del warehouse desde la base central
        if ($quote && $quote->warehouseId) {
            Log::info('🏢 Obteniendo warehouse desde base central', ['warehouse_id' => $quote->warehouseId]);

            try {
                // Consultar directamente desde la base central usando el modelo VntWarehouse
                $warehouse = VntWarehouse::find($quote->warehouseId);

                if ($warehouse) {
                    Log::info('🏢 Warehouse encontrado en central', [
                        'id' => $warehouse->id,
                        'name' => $warehouse->name,
                        'address' => $warehouse->address
                    ]);

                    $companyData = [
                        'businessName' => $warehouse->name ?? 'EMPRESA DE PRUEBA',
                        'firstName' => 'Admin',
                        'lastName' => 'Sistema',
                        'identification' => '123456789',
                        'billingAddress' => $warehouse->address ?? 'Dirección de prueba',
                        'phone' => '1234567890',
                        'billingEmail' => 'test@empresa.com'
                    ];

                    Log::info('🏢 Datos empresa obtenidos del warehouse central', $companyData);
                } else {
                    Log::warning('⚠️ Warehouse no encontrado en central con ID: ' . $quote->warehouseId);
                    throw new \Exception('Warehouse no encontrado');
                }
            } catch (\Exception $e) {
                Log::error('❌ Error consultando warehouse central: ' . $e->getMessage());

                // Datos por defecto si hay error
                $companyData = [
                    'businessName' => 'EMPRESA DE PRUEBA',
                    'firstName' => 'Admin',
                    'lastName' => 'Sistema',
                    'identification' => '123456789',
                    'billingAddress' => 'Dirección de prueba',
                    'phone' => '1234567890',
                    'billingEmail' => 'test@empresa.com'
                ];
            }
        } else {
            Log::warning('⚠️ No se encontró warehouseId en la cotización, usando datos por defecto');

            // Datos por defecto si no hay warehouse
            $companyData = [
                'businessName' => 'EMPRESA DE PRUEBA',
                'firstName' => 'Admin',
                'lastName' => 'Sistema',
                'identification' => '123456789',
                'billingAddress' => 'Dirección de prueba',
                'phone' => '1234567890',
                'billingEmail' => 'test@empresa.com'
            ];
        }

        Log::info('🏢 Datos empresa preparados', $companyData);

        return (object) $companyData;
    }
}
