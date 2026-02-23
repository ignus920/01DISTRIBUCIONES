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
use App\Models\Central\VntCompany;

class SalesList extends Component
{
    use WithPagination, \App\Traits\Livewire\WithExport, HasCompanyConfiguration;

    public $search = '';
    public $perPage = 10;
    public $companyId;

    // Propiedades para el modal de detalles
    public $showDetailModal = false;
    public $selectedQuote = null;

    // Información de la empresa para mostrar en la interfaz
    public $companyInfo = null;

    protected $paginationTheme = 'tailwind';

    public function mount()
    {
        // Usar la misma lógica que QuoterView para obtener company_id
        $user = Auth::user();
        $this->companyId = $this->getUserCompanyId($user);

        // Obtener información de la empresa para mostrar en la interfaz
        $this->loadCompanyInfo();
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

    /**
     * Cargar información de la empresa para mostrar en la interfaz
     */
    protected function loadCompanyInfo()
    {
        try {
            $user = Auth::user();
            if ($user && $user->contact_id) {
                $contact = DB::table('vnt_contacts')
                    ->where('id', $user->contact_id)
                    ->first();

                if ($contact && isset($contact->warehouseId)) {
                    // Simular una quote temporal para usar el método getCompanyInfo
                    $tempQuote = (object) ['warehouseId' => $contact->warehouseId];
                    $this->companyInfo = $this->getCompanyInfo($tempQuote);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error cargando información de empresa: ' . $e->getMessage());
            $this->companyInfo = (object) [
                'businessName' => 'EMPRESA',
                'billingAddress' => 'Dirección no disponible',
                'phone' => '1234567890'
            ];
        }
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
                ? 'livewire.TAT.quoter.print.print-carta'
                : 'livewire.TAT.quoter.print.print-pos';
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

        // Intentar obtener información del warehouse y su empresa desde la base central
        if ($quote && $quote->warehouseId) {
            Log::info('🏢 Obteniendo warehouse con empresa desde base central', ['warehouse_id' => $quote->warehouseId]);

            try {
                // Primero obtener el warehouse para sacar el companyId
                $warehouse = VntWarehouse::find($quote->warehouseId);

                if ($warehouse && $warehouse->companyId) {
                    Log::info('🏢 Warehouse encontrado, obteniendo empresa', [
                        'warehouse_id' => $warehouse->id,
                        'warehouse_name' => $warehouse->name,
                        'warehouse_address' => $warehouse->address,
                        'company_id' => $warehouse->companyId
                    ]);

                    // Consulta directa a vnt_companies usando companyId
                    Log::info('🔍 Ejecutando consulta: VntCompany::find(' . $warehouse->companyId . ')');
                    $company = VntCompany::find($warehouse->companyId);

                    if ($company) {
                        Log::info('🏢 Empresa encontrada en vnt_companies', [
                            'company_id' => $company->id,
                            'businessName' => $company->businessName ?? 'NULL',
                            'firstName' => $company->firstName ?? 'NULL',
                            'lastName' => $company->lastName ?? 'NULL',
                            'identification' => $company->identification ?? 'NULL',
                            'billingEmail' => $company->billingEmail ?? 'NULL',
                            'all_company_data' => $company->toArray()
                        ]);

                        // Intentar obtener teléfono del primer contacto del warehouse
                        $phone = '1234567890'; // Default
                        $contacts = DB::table('vnt_contacts')
                            ->where('warehouseId', $warehouse->id)
                            ->whereNotNull('personal_phone')
                            ->first();

                        if ($contacts && $contacts->personal_phone) {
                            $phone = $contacts->personal_phone;
                            Log::info('📞 Teléfono obtenido del contacto', ['phone' => $phone]);
                        }

                        $companyData = [
                            'businessName' => $company->businessName ?? $warehouse->name ?? 'Empresa',
                            'firstName' => $company->firstName ?? 'Admin',
                            'lastName' => $company->lastName ?? 'Sistema',
                            'identification' => $company->identification ?? '123456789',
                            'billingAddress' => $warehouse->address ?? 'Dirección no disponible',
                            'phone' => $phone,
                            'billingEmail' => $company->billingEmail ?? 'contacto@empresa.com'
                        ];

                        Log::info('🏢 Datos empresa obtenidos correctamente', $companyData);
                    } else {
                        Log::warning('⚠️ No se encontró la empresa con ID: ' . $warehouse->companyId);
                        throw new \Exception('Empresa no encontrada');
                    }
                } elseif ($warehouse) {
                    Log::warning('⚠️ Warehouse encontrado pero sin empresa asociada o companyId nulo', [
                        'warehouse_id' => $warehouse->id,
                        'warehouse_name' => $warehouse->name,
                        'company_id' => $warehouse->companyId ?? 'NULL'
                    ]);

                    $companyData = [
                        'businessName' => $warehouse->name ?? 'EMPRESA',
                        'firstName' => 'Admin',
                        'lastName' => 'Sistema',
                        'identification' => '123456789',
                        'billingAddress' => $warehouse->address ?? 'Dirección no disponible',
                        'phone' => '1234567890',
                        'billingEmail' => 'contacto@empresa.com'
                    ];
                } else {
                    Log::warning('⚠️ Warehouse no encontrado en central con ID: ' . $quote->warehouseId);
                    throw new \Exception('Warehouse no encontrado');
                }
            } catch (\Exception $e) {
                Log::error('❌ Error consultando warehouse central: ' . $e->getMessage());

                // Datos por defecto si hay error
                $companyData = [
                    'businessName' => 'EMPRESA',
                    'firstName' => 'Admin',
                    'lastName' => 'Sistema',
                    'identification' => '123456789',
                    'billingAddress' => 'Dirección no disponible',
                    'phone' => '1234567890',
                    'billingEmail' => 'contacto@empresa.com'
                ];
            }
        } else {
            Log::warning('⚠️ No se encontró warehouseId en la cotización, intentando usar companyId actual');

            // Usar el companyId del usuario actual cuando no hay warehouseId en la quote
            if ($this->companyId || $this->currentCompanyId) {
                $companyIdToUse = $this->currentCompanyId ?? $this->companyId;
                Log::info('🏢 Usando companyId actual para obtener datos de empresa', ['company_id' => $companyIdToUse]);

                try {
                    $company = VntCompany::find($companyIdToUse);

                    if ($company) {
                        Log::info('🏢 Empresa encontrada usando companyId actual', [
                            'company_id' => $company->id,
                            'businessName' => $company->businessName ?? 'NULL',
                            'identification' => $company->identification ?? 'NULL'
                        ]);

                        // Intentar obtener warehouse principal de esta empresa
                        $warehouse = VntWarehouse::where('companyId', $companyIdToUse)->where('main', 1)->first();
                        if (!$warehouse) {
                            $warehouse = VntWarehouse::where('companyId', $companyIdToUse)->first();
                        }

                        $phone = '1234567890'; // Default
                        if ($warehouse) {
                            $contacts = DB::connection('mysql')->table('vnt_contacts')
                                ->where('warehouseId', $warehouse->id)
                                ->whereNotNull('personal_phone')
                                ->first();

                            if ($contacts && $contacts->personal_phone) {
                                $phone = $contacts->personal_phone;
                                Log::info('📞 Teléfono obtenido del contacto', ['phone' => $phone]);
                            }
                        }

                        $companyData = [
                            'businessName' => $company->businessName ?? ($company->firstName . ' ' . $company->lastName),
                            'firstName' => $company->firstName ?? 'Admin',
                            'lastName' => $company->lastName ?? 'Sistema',
                            'identification' => $company->identification ?? '123456789',
                            'billingAddress' => $warehouse->address ?? 'Dirección no disponible',
                            'phone' => $phone,
                            'billingEmail' => $company->billingEmail ?? 'contacto@empresa.com'
                        ];

                        Log::info('🏢 Datos empresa obtenidos usando companyId actual', $companyData);
                    } else {
                        Log::warning('⚠️ No se encontró empresa con companyId: ' . $companyIdToUse);
                        throw new \Exception('Empresa no encontrada con companyId actual');
                    }
                } catch (\Exception $e) {
                    Log::error('❌ Error obteniendo empresa con companyId: ' . $e->getMessage());

                    // Datos por defecto si hay error
                    $companyData = [
                        'businessName' => 'EMPRESA',
                        'firstName' => 'Admin',
                        'lastName' => 'Sistema',
                        'identification' => '123456789',
                        'billingAddress' => 'Dirección no disponible',
                        'phone' => '1234567890',
                        'billingEmail' => 'contacto@empresa.com'
                    ];
                }
            } else {
                Log::warning('⚠️ No se encontró companyId, usando datos por defecto');

                // Datos por defecto si no hay companyId
                $companyData = [
                    'businessName' => 'EMPRESA',
                    'firstName' => 'Admin',
                    'lastName' => 'Sistema',
                    'identification' => '123456789',
                    'billingAddress' => 'Dirección no disponible',
                    'phone' => '1234567890',
                    'billingEmail' => 'contacto@empresa.com'
                ];
            }
        }

        Log::info('🏢 Datos empresa preparados', $companyData);

        return (object) $companyData;
    }

    /**
     * Método de debug para verificar datos de empresa
     */
    public function debugCompanyData($quoteId)
    {
        $quote = Quote::find($quoteId);

        if (!$quote) {
            Log::info('❌ Quote no encontrada: ' . $quoteId);
            return;
        }

        Log::info('🔍 DEBUG - Datos de la cotización:', [
            'quote_id' => $quote->id,
            'warehouse_id' => $quote->warehouseId
        ]);

        // Verificar warehouse
        $warehouse = DB::table('vnt_warehouses')->where('id', $quote->warehouseId)->first();
        Log::info('🔍 DEBUG - Datos del warehouse:', [
            'warehouse' => $warehouse ? (array)$warehouse : 'NO ENCONTRADO'
        ]);

        if ($warehouse && $warehouse->companyId) {
            // Verificar empresa
            $company = DB::table('vnt_companies')->where('id', $warehouse->companyId)->first();
            Log::info('🔍 DEBUG - Datos de la empresa:', [
                'company' => $company ? (array)$company : 'NO ENCONTRADA'
            ]);
        }

        $this->dispatch('show-toast', [
            'type' => 'info',
            'message' => 'Debug ejecutado, revisa los logs'
        ]);
    }
}
