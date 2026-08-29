<?php

namespace App\Livewire\Tenant\Quoter;

use App\Services\Tenant\TenantManager;
use App\Models\Auth\Tenant;
use App\Models\Tenant\Quoter\VntQuote;
use App\Models\Central\VntWarehouse;
use App\Traits\HasCompanyConfiguration;
use Illuminate\Support\Facades\Log;
use Livewire\Component;
use Livewire\WithPagination;
use App\Traits\Livewire\WithExport;
use Illuminate\Support\Facades\Auth;
use App\Models\Tenant\Remissions\InvRemissions;
use App\Models\Tenant\Sales\VntInvoice;
use App\Models\Tenant\Sales\VntInvoicesXsale;
use App\Services\Factus\QuoteToInvoiceService;
use Livewire\Attributes\On;

class Quoter extends Component
{
    use WithPagination, WithExport, HasCompanyConfiguration;

    protected $listeners = ['refresh-component' => '$refresh'];

    public $search = '';
    public $viewType = 'desktop'; // 'desktop' o 'mobile'
    public $perPage = 10; // Registros por página
    public $showDetailsModal = false;
    public $selectedQuote = null;
    public $sortBy = 'created_at'; // Campo para ordenar
    public $sortDirection = 'desc'; // Dirección: 'asc' o 'desc'

    // Propiedades para el modal de confirmación de pedido
    public $showConfirmationModal = false;
    public $selectedReason = null;
    public $availableReasons = [];
    public $confirmationLoading = false;
    public $quoterItems = [];
    public $selectedCustomer = null;
    public $customerSearch = '';
    public $observaciones = null;
    public $showCartModal = false;
    public $totalAmount = 0;

    protected $paginationTheme = 'tailwind';

    public function mount($viewType = null)
    {
        // Obtener viewType desde parámetro, ruta o usar desktop por defecto
        $this->viewType = $viewType ?? request()->route('viewType', 'desktop');

        // Establecer conexión tenant antes de cualquier consulta
        $this->ensureTenantConnection();

        // Inicializar configuración de empresa
        $this->initializeCompanyConfiguration();
    }

    /**
     * Método que se ejecuta cuando el componente se hidrata (después de navegación)
     */
    public function hydrate()
    {
        Log::info('💧 Quoter hydrate() ejecutado - Re-estableciendo conexiones');

        // Re-establecer conexión tenant
        $this->ensureTenantConnection();

        // Re-inicializar configuración de empresa
        $this->initializeCompanyConfiguration();
    }


    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingPerPage()
    {
        $this->resetPage();
    }

    /**
     * Maneja el ordenamiento de columnas
     * 
     * @param string $field Campo por el cual ordenar
     */
    public function setSortBy($field)
    {
        // Si ya estamos ordenando por este campo, invertir la dirección
        if ($this->sortBy === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            // Si es un nuevo campo, ordenar ascendente por defecto
            $this->sortBy = $field;
            $this->sortDirection = 'asc';
        }

        // Resetear a la primera página cuando se cambia el ordenamiento
        $this->resetPage();
    }

    #[On('nuevaCotizacion')]
    public function nuevaCotizacion()
    {
        // Limpiar items del cotizador de la sesión para que entre limpio
        session()->forget('quoter_items');

        return redirect('/tenant/quoter/products');
    }

    public function eliminar($id)
    {
        $quote = VntQuote::find($id);
        if ($quote) {
            $quote->delete();
            session()->flash('message', 'Cotización eliminada correctamente.');
        }
    }

    /**
     * Muestra el modal con los detalles de la cotización
     * 
     * @param int $id ID de la cotización
     */
    public function verDetalles($id)
    {
        try {
            // Mostrar el modal
            $this->showDetailsModal = true;
            // Asegurar conexión tenant
            $this->ensureTenantConnection();

            $this->selectedQuote = VntQuote::with([
                'detalles.item',
                'customer.company.routes.route',
                'customer.city',
                'warehouse',
                'branch'
            ])->findOrFail($id);

            Log::info('✅ Cotización cargada', [
                'consecutive' => $this->selectedQuote->consecutive,
                'detalles_count' => $this->selectedQuote->detalles->count()
            ]);

            Log::info('✅ verDetalles completado');

            // Forzar actualización del DOM
            $this->js('console.log("Modal should be visible now", ' . json_encode(['showDetailsModal' => $this->showDetailsModal]) . ')');
        } catch (\Exception $e) {
            Log::error('❌ Error al cargar detalles de cotización', [
                'quote_id' => $id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            // Asegurar que el modal no se muestre si hay error
            $this->showDetailsModal = false;
            $this->selectedQuote = null;

            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Error al cargar los detalles: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Cierra el modal de detalles
     */
    public function cerrarDetalles()
    {
        $this->showDetailsModal = false;
        $this->selectedQuote = null;
    }

    /**
     * Redirige al cotizador para editar una cotización existente
     * Este método se ejecuta cuando el usuario hace clic en el botón "Editar"
     *
     * @param int $id ID de la cotización a editar
     * @return \Illuminate\Http\RedirectResponse
     */
    public function editarCotizacion($id)
    {

        // Determinar la ruta correcta según el tipo de vista (móvil o escritorio)
        $routeName = $this->viewType === 'mobile'
            ? 'tenant.quoter.products.mobile.edit'    // Ruta para vista móvil
            : 'tenant.quoter.products.desktop.edit';  // Ruta para vista escritorio

        // Redirigir al cotizador con el ID de la cotización para cargarla y editarla
        return redirect()->route($routeName, ['quoteId' => $id]);
    }

    /**
     * Redirige directamente al carrito de compras (ProductQuoter) para editar una cotización
     * Este método se usa ÚNICAMENTE en vista móvil para ir directo al carrito
     *
     * @param int $id ID de la cotización a editar
     * @return \Illuminate\Http\RedirectResponse
     */
    public function irAlCarrito($id)
    {
        // Solo funciona en vista móvil
        if ($this->viewType !== 'mobile') {
            return $this->editarCotizacion($id);
        }

        // Redirigir directamente al carrito móvil con la cotización cargada
        return redirect()->route('tenant.quoter.products.mobile.edit', ['quoteId' => $id]);
    }

    /**
     * Verifica tipo de impresion (opción 3)
     */
    // public function canPrint(): bool
    //  {
    //     $result = $this->isOptionEnabled(3);
    //      $value = $this->getOptionValue(3);

    //      //DEBUG: Log detallado de verificación
    //      Log::info('🔍 canPrint() verificación', [
    //          'companyId' => $this->currentCompanyId,
    //          'option_id' => 3,
    //          'result' => $result ? 'TRUE' : 'FALSE',
    //         'option_value' => $value,
    //         'configService_exists' => $this->configService ? 'YES' : 'NO',
    //         'method_called' => 'isOptionEnabled(3) y getOptionValue(3)'
    //     ]);
    //     return $result;
    //  }

    /**
     * Obtiene el tipo de impresion
     */
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





    /**
     * Método para imprimir cotización
     * Determina el formato según la configuración:
     * - Valor 0: POS Simple (Tirilla 80mm)
     * - Valor 1: POS Institucional (Carta)
     */
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
            $quote = VntQuote::findOrFail($id);
            Log::info('📄 Cotización básica cargada', ['consecutive' => $quote->consecutive]);

            Log::info('🔄 Cargando detalles...');
            try {
                $quote->load('detalles');
                Log::info('📋 Detalles cargados', ['count' => $quote->detalles->count()]);
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
                $quote->load('detalles.item');
                Log::info('📦 Items cargados');

                // Debug: verificar si hay items null
                $nullItems = $quote->detalles->whereNull('item')->count();
                if ($nullItems > 0) {
                    Log::warning('⚠️ Hay items null', ['null_count' => $nullItems]);
                }
            } catch (\Exception $itemError) {
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
                ? 'livewire.tenant.quoter.print.print-carta'
                : 'livewire.tenant.quoter.print.print-pos';
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

    /**
     * Obtener información de la empresa para los documentos
     */
    private function getCompanyInfo($quote = null)
    {
        Log::info('🏢 getCompanyInfo llamado');

        // Intentar obtener información del warehouse desde la base central
        if ($quote && $quote->warehouseId) {
            Log::info('🏢 Obteniendo warehouse desde base central', ['warehouse_id' => $quote->warehouseId]);

            try {
                // Consultar directamente desde la base central usando el modelo VntWarehouse con su empresa
                $warehouse = VntWarehouse::with('company')->find($quote->warehouseId);

                if ($warehouse) {
                    Log::info('🏢 Warehouse encontrado en central', [
                        'id' => $warehouse->id,
                        'name' => $warehouse->name,
                        'address' => $warehouse->address
                    ]);

                    // Priorizar datos de la empresa vinculada al warehouse
                    $company = $warehouse->company;

                    $companyData = [
                        'businessName' => $company->businessName ?? $warehouse->name ?? 'DISTRIBUCIONES',
                        'firstName' => $company->firstName ?? '',
                        'lastName' => $company->lastName ?? '',
                        'identification' => $company->identification ?? 'N/A',
                        'billingAddress' => $warehouse->address ?? $company->billingAddress ?? 'N/A',
                        'phone' => $company->phone ?? $company->billingPhone ?? 'N/A',
                        'billingEmail' => $company->billingEmail ?? 'pedidos@distribuciones.com'
                    ];

                    Log::info('🏢 Datos empresa obtenidos del warehouse central', $companyData);
                } else {
                    Log::warning('⚠️ Warehouse no encontrado en central con ID: ' . $quote->warehouseId);
                    throw new \Exception('Warehouse no encontrado');
                }
            } catch (\Exception $e) {
                Log::error('❌ Error consultando warehouse central: ' . $e->getMessage());

                // Fallback razonable
                $companyData = [
                    'businessName' => 'DISTRIBUCIONES',
                    'firstName' => '',
                    'lastName' => '',
                    'identification' => 'N/A',
                    'billingAddress' => 'N/A',
                    'phone' => 'N/A',
                    'billingEmail' => 'pedidos@distribuciones.com'
                ];
            }
        } else {
            Log::warning('⚠️ No se encontró warehouseId en la cotización, usando datos por defecto');

            $companyData = [
                'businessName' => 'DISTRIBUCIONES',
                'firstName' => '',
                'lastName' => '',
                'identification' => 'N/A',
                'billingAddress' => 'N/A',
                'phone' => 'N/A',
                'billingEmail' => 'pedidos@distribuciones.com'
            ];
        }

        Log::info('🏢 Datos empresa preparados', $companyData);

        return (object) $companyData;
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

    public function render()
    {
        // Asegurar conexión tenant activa
        $this->ensureTenantConnection();

        //dd(Auth::id());
        // Cargar cotizaciones con sus relaciones

        $quotes = VntQuote::with([
            'customer.company.routes.route',
            'customer.city',
            'customer.contacts',
            'warehouse.contacts',
            'branch',
            'detalles',
            'user',
            'remissions'
        ])
            ->when(Auth::user()->profile_id != 2, function ($query) {
                return $query->where('userId', Auth::id());
            })
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('consecutive', 'like', '%' . $this->search . '%')
                        ->orWhere('status', 'like', '%' . $this->search . '%')
                        ->orWhere('typeQuote', 'like', '%' . $this->search . '%')
                        ->orWhere('observations', 'like', '%' . $this->search . '%')
                        ->orWhereHas('customer', function ($subQ) {
                            $subQ->where('firstName', 'like', '%' . $this->search . '%')
                                ->orWhere('lastName', 'like', '%' . $this->search . '%')
                                ->orWhere('billingEmail', 'like', '%' . $this->search . '%');
                        })
                        ->orWhereHas('warehouse', function ($subQ) {
                            $subQ->where('name', 'like', '%' . $this->search . '%')
                                ->orWhere('address', 'like', '%' . $this->search . '%');
                        });
                });
            });

        // Aplicar ordenamiento
        // Para campos que son accessors, ordenar en PHP después de paginar
        if (in_array($this->sortBy, ['customer_name', 'warehouse_name'])) {
            $quotes = $quotes->paginate($this->perPage);

            // Ordenar en PHP para accessors
            if ($this->sortBy === 'customer_name') {
                $quotes->getCollection()->transform(function ($item) {
                    return $item;
                });
                $sorted = $quotes->getCollection()->sortBy(function ($item) {
                    return $item->customer_name;
                });
                if ($this->sortDirection === 'desc') {
                    $sorted = $sorted->reverse();
                }
                $quotes->setCollection($sorted);
            } elseif ($this->sortBy === 'warehouse_name') {
                $sorted = $quotes->getCollection()->sortBy(function ($item) {
                    return $item->warehouse_name;
                });
                if ($this->sortDirection === 'desc') {
                    $sorted = $sorted->reverse();
                }
                $quotes->setCollection($sorted);
            }
        } else {
            // Para columnas reales de la BD, ordenar en la query
            $quotes = $quotes->orderBy($this->sortBy, $this->sortDirection)
                ->paginate($this->perPage);
        }

        $viewName = $this->viewType === 'mobile'
            ? 'livewire.tenant.quoter.components.quoter-mobile'
            : 'livewire.tenant.quoter.components.quoter-desktop';

        return view($viewName, [
            'quotes' => $quotes
        ]);
    }
    /**
     * Abrir modal de confirmación de pedido
     * 
     * @param int|null $quoteId ID de la cotización a confirmar (opcional)
     */

    public function validateRemision($quoteId)
    {
        // 1. Intentar obtener de la relación ya cargada (para evitar N+1 en el render)
        if ($this->selectedQuote && $this->selectedQuote->id == $quoteId) {
            $remissions = $this->selectedQuote->remissions;
        } else {
            // Fallback para cuando se llama fuera del loop de renderizado (poco común)
            // Buscamos la cotización en la colección actual si es posible
            $remissions = InvRemissions::where('quoteId', $quoteId)->get();
        }

        // Filtramos las que NO están anuladas
        $activeRemissions = $remissions->filter(function ($rem) {
            $status = trim(strtoupper($rem->status ?? ''));
            return $status !== 'ANULADO' && $status !== '';
        });

        return $activeRemissions->isEmpty();
    }

    /**
     * Procesa un pedido offline recibido desde el frontend
     * 
     * @param array $orderData Datos del pedido (uuid, items, customer, etc.)
     * @return array Resultado de la operación
     */
    public function processOfflineOrder($orderData)
    {
        try {
            Log::info('📥 [Livewire] Recibido pedido offline para sincronizar', ['uuid' => $orderData['uuid'] ?? 'N/A']);

            // Validar que tengamos los datos mínimos
            if (empty($orderData)) {
                return ['success' => false, 'message' => 'Datos de pedido vacíos'];
            }

            // Despachar el Job
            \App\Jobs\Tenant\Quoter\ProcessOfflineOrderJob::dispatch(
                $orderData,
                Auth::id(),
                session('warehouse_id', 1),
                session('branch_id', 1)
            );

            return ['success' => true];
        } catch (\Exception $e) {
            Log::error('❌ [Livewire] Error procesando pedido offline', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Abrir modal de confirmación de pedido
     * 
     * @param int|null $quoteId ID de la cotización a confirmar (opcional)
     */
    public function confirmarPedido($quoteId = null)
    {
        if (empty($this->quoterItems) && !$quoteId) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'No hay productos en el cotizador'
            ]);
            return;
        }

        $this->ensureTenantConnection();

        // Si se proporciona un quoteId, cargar la cotización desde la base de datos
        if ($quoteId) {
            try {
                $quote = VntQuote::with(['detalles.item', 'customer'])->findOrFail($quoteId);

                // Mapear los detalles de la cotización a items del cotizador
                $this->quoterItems = $quote->detalles->map(function ($detail) {
                    return [
                        'id' => $detail->itemId,
                        'name' => $detail->item->name ?? 'Producto desconocido',
                        'sku' => $detail->item->sku ?? null,
                        'quantity' => $detail->quantity,
                        'price' => $detail->value,
                        'total' => $detail->quantity * $detail->value,
                    ];
                })->toArray();

                // Cargar cliente si existe
                if ($quote->customer) {
                    $this->selectedCustomer = $quote->customer->id;
                }

                // Cargar observaciones
                $this->observaciones = $quote->observations ?? null;

                Log::info('Cotización cargada para confirmación', [
                    'quote_id' => $quoteId,
                    'items_count' => count($this->quoterItems)
                ]);
            } catch (\Exception $e) {
                Log::error('Error al cargar cotización para confirmación', [
                    'quote_id' => $quoteId,
                    'error' => $e->getMessage()
                ]);

                $this->dispatch('show-toast', [
                    'type' => 'error',
                    'message' => 'Error al cargar la cotización: ' . $e->getMessage()
                ]);
                return;
            }
        }

        // Cargar razones disponibles
        try {
            $this->availableReasons = \App\Models\Tenant\Movements\InvReason::active()->get()->toArray();
        } catch (\Exception $e) {
            Log::warning('No se pudieron cargar las razones de movimiento', [
                'error' => $e->getMessage()
            ]);
            $this->availableReasons = [];
        }

        // Abrir modal
        $this->showConfirmationModal = true;
    }

    /**
     * Cerrar modal de confirmación
     */
    public function closeConfirmationModal()
    {
        $this->showConfirmationModal = false;
        $this->selectedReason = null;
        $this->confirmationLoading = false;
    }

    /**
     * Confirmar y procesar el pedido
     */
    public function processOrderConfirmation()
    {
        if (empty($this->quoterItems)) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'No hay productos en el cotizador'
            ]);
            return;
        }

        if (!$this->selectedReason) {
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Debe seleccionar una razón para el pedido'
            ]);
            return;
        }

        $this->confirmationLoading = true;
        $this->ensureTenantConnection();

        try {
            // Obtener la razón seleccionada
            $reason = \App\Models\Tenant\Movements\InvReason::find($this->selectedReason);

            if (!$reason) {
                throw new \Exception('Razón no encontrada');
            }

            // Crear remisión
            $remission = \App\Models\Tenant\Remissions\InvRemissions::create([
                'consecutive' => $this->generateRemissionConsecutive(),
                'reasonId' => $reason->id,
                'userId' => auth()->id(),
                'status' => 'REGISTRADO',
                'total_value' => $this->totalAmount,
                'observations' => $this->observaciones ?? null,
            ]);

            // Crear detalles de remisión para cada producto
            $detailsCreated = 0;
            foreach ($this->quoterItems as $item) {
                \App\Models\Tenant\Remissions\InvDetailRemissions::create([
                    'remissionId' => $remission->id,
                    'itemId' => $item['id'],
                    'quantity' => $item['quantity'],
                    'value' => $item['price'],
                    'discount' => 0,
                    'tax' => 0,
                ]);
                $detailsCreated++;
            }

            Log::info('Remisión creada exitosamente', [
                'remission_id' => $remission->id,
                'consecutive' => $remission->consecutive,
                'details_created' => $detailsCreated,
                'user_id' => auth()->id()
            ]);

            // Limpiar cotizador
            $this->quoterItems = [];
            $this->selectedCustomer = null;
            $this->customerSearch = '';
            $this->observaciones = null;
            session()->forget('quoter_items');
            $this->calculateTotal();

            // Cerrar modal
            $this->closeConfirmationModal();
            $this->showCartModal = false;

            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => 'Remisión #' . $remission->consecutive . ' creada exitosamente'
            ]);

            // Redirigir a la página de remisiones o cotizaciones
            return redirect()->route('tenant.remissions');
        } catch (\Exception $e) {
            Log::error('Error al procesar confirmación de pedido: ' . $e->getMessage());
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Error al confirmar pedido: ' . $e->getMessage()
            ]);
        } finally {
            $this->confirmationLoading = false;
        }
    }

    /**
     * Generar consecutivo para remisión
     */
    private function generateRemissionConsecutive()
    {
        $lastRemission = \App\Models\Tenant\Remissions\InvRemissions::orderBy('consecutive', 'desc')->first();
        return $lastRemission ? $lastRemission->consecutive + 1 : 1;
    }

    /**
     * Calcular el total del cotizador
     */
    private function calculateTotal()
    {
        $this->totalAmount = collect($this->quoterItems)->sum(function ($item) {
            return ($item['quantity'] ?? 0) * ($item['price'] ?? 0);
        });
    }

    /**
     * Obtener la cantidad total de productos en el cotizador
     */
    public function getQuoterCountProperty()
    {
        return collect($this->quoterItems)->sum('quantity');
    }

    /**
     * Métodos para Exportación (WithExport Trait)
     */

    protected function getExportData()
    {
        $query = VntQuote::with(['customer', 'warehouse.contacts', 'branch', 'detalles', 'user'])
            ->when(Auth::user()->profile_id != 2, function ($query) {
                return $query->where('userId', Auth::id());
            })
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('consecutive', 'like', '%' . $this->search . '%')
                        ->orWhere('status', 'like', '%' . $this->search . '%')
                        ->orWhere('typeQuote', 'like', '%' . $this->search . '%')
                        ->orWhere('observations', 'like', '%' . $this->search . '%')
                        ->orWhereHas('customer', function ($subQ) {
                            $subQ->where('firstName', 'like', '%' . $this->search . '%')
                                ->orWhere('lastName', 'like', '%' . $this->search . '%')
                                ->orWhere('billingEmail', 'like', '%' . $this->search . '%');
                        })
                        ->orWhereHas('warehouse', function ($subQ) {
                            $subQ->where('name', 'like', '%' . $this->search . '%')
                                ->orWhere('address', 'like', '%' . $this->search . '%');
                        });
                });
            });

        if (in_array($this->sortBy, ['customer_name', 'warehouse_name'])) {
            $data = $query->get();

            if ($this->sortBy === 'customer_name') {
                $sorted = $data->sortBy(function ($item) {
                    return $item->customer_name;
                });
            } else {
                $sorted = $data->sortBy(function ($item) {
                    return $item->warehouse_name;
                });
            }

            if ($this->sortDirection === 'desc') {
                $sorted = $sorted->reverse();
            }
            return $sorted;
        }

        return $query->orderBy($this->sortBy, $this->sortDirection)->get();
    }

    protected function getExportHeadings(): array
    {
        return ['CONSECUTIVO', 'CLIENTE', 'VENDEDOR', 'TIPO', 'ESTADO', 'SUCURSAL', 'TOTAL', 'FECHA'];
    }

    protected function getExportMapping()
    {
        return function ($quote) {
            return [
                $quote->consecutive,
                $quote->customer_name,
                $quote->user->name ?? 'N/A',
                $quote->typeQuote,
                $quote->status,
                $quote->warehouse->name ?? 'N/A',
                $quote->total,
                $quote->created_at->format('d/m/Y H:i')
            ];
        };
    }

    protected function getExportFilename(): string
    {
        return 'cotizaciones_' . now()->format('Y-m-d_His');
    }

    /**
     * Facturar una cotización usando la API de Factus
     *
     * @param int $quoteId ID de la cotización a facturar
     */
    public function facturarCotizacion($quoteId)
    {
        try {
            Log::info('Iniciando facturación de cotización', ['quote_id' => $quoteId]);

            $this->ensureTenantConnection();

            $quote = VntQuote::with('detalles')->find($quoteId);

            if (!$quote) {
                $this->dispatch('show-toast', ['type' => 'error', 'message' => 'Cotización no encontrada']);
                return;
            }

            if ($quote->detalles->isEmpty()) {
                $this->dispatch('show-toast', ['type' => 'error', 'message' => 'La cotización no tiene productos para facturar']);
                return;
            }

            // Solo se pueden facturar cotizaciones que tengan remisión
            $remission = InvRemissions::where('quoteId', $quoteId)->first();

            if (!$remission) {
                $this->dispatch('show-toast', ['type' => 'error', 'message' => 'Solo se pueden facturar cotizaciones que tengan remisión generada']);
                return;
            }

            $quoteToInvoiceService = app(QuoteToInvoiceService::class);
            $result = $quoteToInvoiceService->convertQuoteToInvoice($quoteId);

            if ($result['success']) {
                // Guardar en vnt_invoices
                $consecutive = (VntInvoice::max('consecutive') ?? 0) + 1;

                $invoice = VntInvoice::create([
                    'consecutive'    => $consecutive,
                    'status'         => 'FACTURADO',
                    'status_payment' => 'REGISTRADO',
                    'api_data_id'    => $result['factus_bill_id'],
                    'quoteId'        => $quoteId,
                    'warehouseId'    => $quote->warehouseId,
                    'remission'      => $remission->id,
                    'invoiceNumber'  => $result['invoice_number'] ?? '',
                    'creditNote'     => 0,
                ]);

                // Guardar en vnt_invoicesXsales
                VntInvoicesXsale::create([
                    'remissionId' => $remission->id,
                    'quoteId'     => $quoteId,
                    'invoiceId'   => $invoice->id,
                ]);

                $quote->update(['status' => 'FACTURADO']);

                Log::info('Cotización facturada y guardada', [
                    'quote_id'       => $quoteId,
                    'invoice_id'     => $invoice->id,
                    'invoice_number' => $result['invoice_number'],
                    'factus_bill_id' => $result['factus_bill_id'],
                ]);

                $this->dispatch('show-toast', [
                    'type'    => 'success',
                    'message' => 'Facturado exitosamente — ' . ($result['invoice_number'] ?? 'N/A'),
                ]);

                if (!empty($result['public_url'])) {
                    $this->dispatch('open-invoice-pdf', ['url' => $result['public_url']]);
                }

                $this->render();
            } else {
                $errorMessage = $this->parseFactusError($result['message'] ?? 'Error desconocido');

                Log::error('Error al facturar cotización', [
                    'quote_id' => $quoteId,
                    'error'    => $result['message'] ?? '',
                ]);

                $this->dispatch('show-toast', [
                    'type'    => 'error',
                    'message' => $errorMessage,
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Excepción al facturar cotización', [
                'quote_id' => $quoteId,
                'error'    => $e->getMessage(),
                'trace'    => $e->getTraceAsString(),
            ]);

            $this->dispatch('show-toast', [
                'type'    => 'error',
                'message' => 'Error interno: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Extrae los mensajes de error de validación de Factus en texto legible.
     */
    protected function parseFactusError(string $rawMessage): string
    {
        // El mensaje viene como: "Factus API Error: {json}"
        $jsonStart = strpos($rawMessage, '{');
        if ($jsonStart === false) {
            return $rawMessage;
        }

        $decoded = json_decode(substr($rawMessage, $jsonStart), true);

        if (!$decoded) {
            return $rawMessage;
        }

        // Si hay errores de validación, listarlos
        $errors = $decoded['data']['errors'] ?? [];
        if (!empty($errors)) {
            $messages = [];
            foreach ($errors as $field => $fieldErrors) {
                foreach ((array) $fieldErrors as $msg) {
                    $messages[] = $msg;
                }
            }
            return implode(' | ', $messages);
        }

        return $decoded['message'] ?? $decoded['data']['message'] ?? $rawMessage;
    }
}
