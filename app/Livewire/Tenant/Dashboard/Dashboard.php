<?php

namespace App\Livewire\Tenant\Dashboard;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use App\Models\Auth\Tenant;
use App\Models\Tenant\Customer\VntCompany;
use App\Models\Tenant\Items\Items;
use App\Models\Tenant\Quoter\VntQuote;
use App\Models\Tenant\Quoter\VntDetailQuote;
use App\Traits\HasCompanyConfiguration;

class Dashboard extends Component
{
    use HasCompanyConfiguration;

    public $tenant;
    public $user;
    public $stats = [];

    public function mount()
    {
        $this->user = Auth::user();

        // Obtener tenant actual de la sesión
        $tenantId = Session::get('tenant_id');

        if (!$tenantId) {
            return redirect()->route('tenant.select');
        }

        $this->tenant = Tenant::find($tenantId);

        if (!$this->tenant) {
            Session::forget('tenant_id');
            return redirect()->route('tenant.select')->withErrors(['tenant' => 'Tenant no encontrado']);
        }

        // IMPORTANTE: Inicializar configuración de empresa para multitenancy
        $this->initializeCompanyConfiguration();

        // Cargar estadísticas reales
        $this->loadStats();
    }

    /**
     * Carga las estadísticas reales consultando la base de datos del tenant
     */
    protected function loadStats()
    {
        try {
            // 1. Ventas Hoy: Suma de (cantidad * valor) de detalles de cotizaciones de hoy
            $ventasHoy = VntDetailQuote::whereHas('cotizacion', function($query) {
                $query->whereDate('created_at', now()->today());
            })->get()->sum(function($detalle) {
                return $detalle->quantity * ($detalle->price ?? 0);
            });

            // 2. Total Clientes: Conteo de empresas registradas (excluyendo eliminados por SoftDeletes)
            $totalClientes = VntCompany::count();

            // 3. Total Productos: Conteo de items activos
            $totalProductos = Items::active()->count();

            // 4. Ventas del Mes: Suma de (cantidad * valor) de cotizaciones del mes actual
            $ventasMes = VntDetailQuote::whereHas('cotizacion', function($query) {
                $query->whereMonth('created_at', now()->month)
                      ->whereYear('created_at', now()->year);
            })->get()->sum(function($detalle) {
                return $detalle->quantity * ($detalle->price ?? 0);
            });

            $this->stats = [
                'total_ventas_hoy' => $ventasHoy,
                'total_clientes' => $totalClientes,
                'total_productos' => $totalProductos,
                'ventas_mes' => $ventasMes,
            ];

        } catch (\Exception $e) {
            // En caso de error (ej. tabla no existe aún), mostrar ceros para no romper la vista
            $this->stats = [
                'total_ventas_hoy' => 0,
                'total_clientes' => 0,
                'total_productos' => 0,
                'ventas_mes' => 0,
            ];
            
            \Illuminate\Support\Facades\Log::error('Error cargando estadísticas del Dashboard: ' . $e->getMessage());
        }
    }

    public function switchTenant()
    {
        Session::forget('tenant_id');
        return redirect()->route('tenant.select');
    }

    public function logout()
    {
        Session::forget('tenant_id');
        Auth::logout();
        return redirect()->route('login');
    }

    /**
     * Verifica si debe mostrarse una funcionalidad según la configuración (Trait)
     */
    public function canShowFeature(string $optionId): bool
    {
        return $this->shouldShowField('TODAS', $optionId);
    }

    /**
     * Obtiene funcionalidades habilitadas para accesos rápidos
     */
    public function getEnabledFeatures(): array
    {
        $allFeatures = [
            'ventas' => ['option_id' => '3', 'name' => 'Nueva Venta', 'route' => 'tenant.quoter.products.   desktop'],
            'clientes' => ['option_id' => '5', 'name' => 'Clientes', 'route' => 'tenant.customers'],
            'productos' => ['option_id' => '15', 'name' => 'Productos', 'route' => 'items'],
            'caja' => ['option_id' => '28', 'name' => 'Cajas', 'route' => 'petty-cash.petty-cash'],
        ];

        $enabledFeatures = [];

        foreach ($allFeatures as $key => $feature) {
            if ($this->canShowFeature($feature['option_id'])) {
                // Verificar si la ruta existe para evitar errores de renderizado
                try {
                    $feature['url'] = \Illuminate\Support\Facades\Route::has($feature['route']) 
                        ? route($feature['route']) 
                        : $feature['route'];
                } catch (\Exception $e) {
                    $feature['url'] = '#';
                }
                $enabledFeatures[$key] = $feature;
            }
        }

        return $enabledFeatures;
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.tenant.dashboard', [
            'tenant' => $this->tenant,
            'stats' => $this->stats,
            'enabledFeatures' => $this->getEnabledFeatures(),
        ]);
    }
}
