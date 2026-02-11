<?php

namespace App\Livewire\Tenant\Remissions;

use App\Models\Tenant\Remissions\InvRemissions;
use App\Models\Auth\Tenant;
use App\Services\Tenant\TenantManager;
use App\Traits\HasCompanyConfiguration;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Central\VntWarehouse;
use Illuminate\Support\Facades\Log;
use Jenssegers\Agent\Agent;

class Remissions extends Component
{
    use WithPagination, HasCompanyConfiguration;

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
    public $showAdvancedSearch = false;

    // Propiedades para el modal de detalle
    public $showDetailModal = false;
    public $selectedRemission = null;

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
    }

    /**
     * Se ejecuta cuando la propiedad de búsqueda cambia, reseteando la paginación.
     */
    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingSearchNit() { $this->resetPage(); }
    public function updatingSearchName() { $this->resetPage(); }
    public function updatingSearchQuote() { $this->resetPage(); }
    public function updatingSearchStartDate() { $this->resetPage(); }
    public function updatingSearchEndDate() { $this->resetPage(); }

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
        $this->resetPage();
    }

    /**
     * Procesa la facturación masiva de las remisiones seleccionadas
     */
    public function facturarMasivo()
    {
        if (empty($this->selectedRemissions)) {
            $this->dispatch('show-toast', [
                'type' => 'warning',
                'message' => 'Por favor selecciona al menos una remisión.'
            ]);
            return;
        }

        $this->ensureTenantConnection();

        try {
            $remisiones = InvRemissions::with(['quote.customer'])
                ->whereIn('id', $this->selectedRemissions)
                ->get();

            // Agrupamos por cliente para la facturación
            $agrupados = $remisiones->groupBy(function($r) {
                return $r->quote->customerId ?? 'sin_cliente';
            });

            Log::info('🚀 Iniciando Facturación Masiva', [
                'count' => count($this->selectedRemissions),
                'clientes_unicos' => $agrupados->count(),
                'remisiones_ids' => $this->selectedRemissions
            ]);

            /**
             * NOTA TÉCNICA PARA FUTURA INTEGRACIÓN:
             * Aquí se debe integrar con el UnifiedController de Factura Electrónica.
             * 1. Validar que el cliente tenga datos completos para DIAN.
             * 2. Crear el objeto Invoice consolidando los items de todas las remisiones del grupo.
             * 3. Consumir el servicio (Alegra, Siigo, etc.)
             * 4. Actualizar estado de remisiones a 'FACTURADO'.
             */

            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => 'Procesando facturación para ' . $remisiones->count() . ' remisiones de ' . $agrupados->count() . ' clientes.'
            ]);

            // Limpiamos selección
            $this->selectedRemissions = [];
            $this->selectAll = false;

        } catch (\Exception $e) {
            Log::error('❌ Error en facturarMasivo: ' . $e->getMessage());
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Error al procesar facturación masiva.'
            ]);
        }
    }

    /**
     * Aplica los filtros base a la consulta
     */
    private function applyBaseFilters($query)
    {
        $query->where(function($q) {
            $q->where('consecutive', 'like', '%' . $this->search . '%')
                ->orWhere('status', 'like', '%' . $this->search . '%')
                ->orWhereHas('quote.customer', function ($sub) {
                    $sub->where('businessName', 'like', '%' . $this->search . '%')
                      ->orWhere('firstName', 'like', '%' . $this->search . '%')
                      ->orWhere('lastName', 'like', '%' . $this->search . '%');
                });
        });

        // Búsqueda avanzada
        if ($this->searchNit) {
            $query->whereHas('quote.customer', function($q) {
                $q->where('identification', 'like', '%' . $this->searchNit . '%');
            });
        }

        if ($this->searchName) {
            $query->whereHas('quote.customer', function($q) {
                $q->where('businessName', 'like', '%' . $this->searchName . '%')
                  ->orWhere('firstName', 'like', '%' . $this->searchName . '%')
                  ->orWhere('lastName', 'like', '%' . $this->searchName . '%');
            });
        }

        if ($this->searchQuote) {
            $query->whereHas('quote', function($q) {
                $q->where('consecutive', 'like', '%' . $this->searchQuote . '%');
            });
        }

        if ($this->searchStartDate) {
            $query->whereDate('created_at', '>=', $this->searchStartDate);
        }

        if ($this->searchEndDate) {
            $query->whereDate('created_at', '<=', $this->searchEndDate);
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
     * Método para imprimir remisión
     */
    public function printRemission($id)
    {
        Log::info('🖨️ printRemission llamado', ['remission_id' => $id]);

        $this->ensureTenantConnection();
        $this->initializeCompanyConfiguration();

        try {
            $remission = InvRemissions::with(['details.item', 'quote.customer', 'user'])->findOrFail($id);
            Log::info('📄 Remisión cargada', ['consecutive' => $remission->consecutive]);

            $company = $this->getCompanyInfo($remission);
            $printFormat = $this->getPrintCopiesLimit(); // 0 = POS, 1 = Carta

            $data = [
                'quote' => $remission, // Pasamos la remisión como 'quote' para reusar la vista
                'customer' => $remission->quote->customer ?? null,
                'company' => $company,
                'documentTitle' => 'REMISIÓN',
                'showQR' => true,
                'defaultObservations' => 'Sin observaciones.'
            ];

            $viewName = ($printFormat === 1)
                ? 'livewire.tenant.remissions.print.print-remission-carta'
                : 'livewire.tenant.remissions.print.print-remission-pos';

            $html = view($viewName, $data)->render();

            $tempFileName = 'quote_' . $id . '_' . time() . '.html';
            $tempPath = storage_path('app/temp/' . $tempFileName);

            if (!file_exists(dirname($tempPath))) {
                mkdir(dirname($tempPath), 0755, true);
            }

            file_put_contents($tempPath, $html);

            $printUrl = route('quoter.print.temp', ['file' => $tempFileName]);

              
        Log::info('🔗 URL generada remisiones', ['url' => $printUrl]);

            $this->dispatch('open-print-window', [
                'url' => $printUrl,
                'format' => $printFormat === 1 ? 'carta' : 'pos'
            ]);

            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => 'Remisión #' . $remission->consecutive . ' preparada para impresión'
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Error en printRemission: ' . $e->getMessage());
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Error al preparar impresión: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Obtiene el formato de impresión desde la configuración
     */
    public function getPrintCopiesLimit(): int
    {
        try {
            return $this->getOptionValue(3) ?? 0;
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Obtiene información de la empresa
     */
    private function getCompanyInfo($remission = null)
    {
        if ($remission && $remission->warehouseId) {
            try {
                $warehouse = VntWarehouse::find($remission->warehouseId);
                if ($warehouse) {
                    return (object) [
                        'businessName' => $warehouse->name ?? 'EMPRESA',
                        'identification' => '123456789',
                        'billingAddress' => $warehouse->address ?? '',
                        'phone' => '1234567890',
                        'billingEmail' => 'test@empresa.com'
                    ];
                }
            } catch (\Exception $e) {
                Log::error('Error consultando warehouse central: ' . $e->getMessage());
            }
        }

        return (object) [
            'businessName' => 'EMPRESA',
            'identification' => '123456789',
            'billingAddress' => '',
            'phone' => '1234567890',
            'billingEmail' => 'test@empresa.com'
        ];
    }

    /**
     * Anula una remisión y regresa la cotización a estado REGISTRADO
     * 
     * @param int $id ID de la remisión
     */
    public function anularRemision($id)
    {
        $this->ensureTenantConnection();

        try {
            \Illuminate\Support\Facades\DB::transaction(function() use ($id) {
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

        // Consulta de remisiones con relaciones y filtros de búsqueda
        $remissions = InvRemissions::with(['quote.customer', 'quote.warehouse', 'quote.branch', 'details'])
            ->where(function($query) {
                $this->applyBaseFilters($query);
            })
            ->when(auth()->user()->profile_id == 4, function ($query) {
                $query->where('userId', auth()->id());
            })
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage);

        return view('livewire.tenant.remissions.remissions', [
            'remissions' => $remissions
        ])->layout('layouts.app', ['header' => 'Remisiones']);
    }
}