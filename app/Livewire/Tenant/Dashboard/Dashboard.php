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
    public $startDate;
    public $endDate;
    public $chartDailyData = [];
    public $chartMonthlyData = [];

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

        // Inicializar fechas por defecto (Mes actual)
        $this->startDate = now()->startOfMonth()->format('Y-m-d');
        $this->endDate = now()->today()->format('Y-m-d');

        // Cargar estadísticas reales
        $this->loadStats();

        // Redirigir perfil 17 si accede manualmente al dashboard
        if ($this->user->profile_id == 17) {
            return redirect()->route('tenant.tat.quoter.index');
        }
    }

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['startDate', 'endDate'])) {
            $this->loadStats();
        }
    }

    /**
     * Carga las estadísticas reales consultando la base de datos del tenant
     */
    protected function loadStats()
    {
        try {
            $isSalesman = $this->user->profile_id == 4;
            $userId = $this->user->id;

            // 1. Ventas Hoy: Suma de (cantidad * valor) de detalles de cotizaciones de hoy
            $ventasHoy = VntDetailQuote::whereHas('cotizacion', function($query) use ($isSalesman, $userId) {
                $query->whereDate('created_at', now()->today());
                if ($isSalesman) {
                    $query->where('userId', $userId);
                }
            })->get()->sum(function($detalle) {
                $subtotal = $detalle->quantity * ($detalle->price ?? 0);
                $tax = $subtotal * (($detalle->tax_percentage ?? 0) / 100);
                return $subtotal + $tax;
            });

            // 2. Total Clientes: Segmentado por perfil
            if ($isSalesman) {
                // Clientes asignados a sus rutas
                $totalClientes = VntCompany::whereHas('routes', function($query) use ($userId) {
                    $query->whereHas('route', function($q) use ($userId) {
                        $q->where('salesman_id', $userId);
                    });
                })->count();
            } else {
                // Global para otros perfiles (Admin)
                $totalClientes = VntCompany::count();
            }

            // 3. Total Productos: Conteo de items activos
            $totalProductos = Items::active()->count();

            // 4. Ventas en Rango Seleccionado:
            $ventasRango = VntDetailQuote::whereHas('cotizacion', function($query) use ($isSalesman, $userId) {
                $query->whereBetween('created_at', [$this->startDate . ' 00:00:00', $this->endDate . ' 23:59:59']);
                if ($isSalesman) {
                    $query->where('userId', $userId);
                }
            })->get()->sum(function($detalle) {
                $subtotal = $detalle->quantity * ($detalle->price ?? 0);
                $tax = $subtotal * (($detalle->tax_percentage ?? 0) / 100);
                return $subtotal + $tax;
            });

            $this->stats = [
                'total_ventas_hoy' => $ventasHoy,
                'total_clientes' => $totalClientes,
                'total_productos' => $totalProductos,
                'ventas_rango' => $ventasRango,
            ];

            // 5. Datos para Gráfico Diario (Rango seleccionado)
            $this->loadDailyChartData($isSalesman, $userId);

            // 6. Datos para Gráfico Mensual (Últimos 12 meses)
            $this->loadMonthlyChartData($isSalesman, $userId);

            // Notificar a la vista que los datos de los gráficos han cambiado
            $this->dispatch('update-charts', daily: $this->chartDailyData, monthly: $this->chartMonthlyData);

        } catch (\Exception $e) {
            $this->stats = [
                'total_ventas_hoy' => 0,
                'total_clientes' => 0,
                'total_productos' => 0,
                'ventas_rango' => 0,
            ];
            
            \Illuminate\Support\Facades\Log::error('Error cargando estadísticas del Dashboard: ' . $e->getMessage());
        }
    }

    /**
     * Carga los datos para el gráfico de ventas diarias
     */
    protected function loadDailyChartData($isSalesman, $userId)
    {
        $dailyResults = VntDetailQuote::whereHas('cotizacion', function($query) use ($isSalesman, $userId) {
                $query->whereBetween('created_at', [$this->startDate . ' 00:00:00', $this->endDate . ' 23:59:59']);
                if ($isSalesman) {
                    $query->where('userId', $userId);
                }
            })
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(quantity * price * (1 + COALESCE(tax_percentage, 0) / 100)) as total')
            )
            ->groupBy('date')
            ->orderBy('date', 'ASC')
            ->get();

        $this->chartDailyData = [
            'labels' => $dailyResults->pluck('date')->map(fn($d) => \Carbon\Carbon::parse($d)->format('d-M'))->toArray(),
            'values' => $dailyResults->pluck('total')->toArray()
        ];
    }

    /**
     * Carga los datos para el gráfico de ventas mensuales
     */
    protected function loadMonthlyChartData($isSalesman, $userId)
    {
        $last12Months = now()->subMonths(11)->startOfMonth();
        
        $monthlyResults = VntDetailQuote::whereHas('cotizacion', function($query) use ($isSalesman, $userId, $last12Months) {
                $query->where('created_at', '>=', $last12Months);
                if ($isSalesman) {
                    $query->where('userId', $userId);
                }
            })
            ->select(
                DB::raw('YEAR(created_at) as year'),
                DB::raw('MONTH(created_at) as month'),
                DB::raw('SUM(quantity * price * (1 + COALESCE(tax_percentage, 0) / 100)) as total')
            )
            ->groupBy('year', 'month')
            ->orderBy('year', 'ASC')
            ->orderBy('month', 'ASC')
            ->get();

        $labels = [];
        $values = [];
        
        // El usuario quiere ver nombres de meses en español preferiblemente
        $meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];

        foreach ($monthlyResults as $row) {
            $labels[] = $meses[$row->month - 1] . '-' . substr($row->year, 2);
            $values[] = $row->total;
        }

        $this->chartMonthlyData = [
            'labels' => $labels,
            'values' => $values
        ];
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
            'ventas' => ['option_id' => '3', 'name' => 'Nueva Venta', 'route' => 'tenant.quoter.products'],
            'clientes' => ['option_id' => '5', 'name' => 'Clientes', 'route' => 'customers.customers'],
            'productos' => ['option_id' => '15', 'name' => 'Productos', 'route' => 'items'],
            'caja' => ['option_id' => '28', 'name' => 'Cajas', 'route' => 'petty-cash.petty-cash'],
        ];

        $enabledFeatures = [];

        foreach ($allFeatures as $key => $feature) {
            // 1. RESTRICCIÓN PARA VENDEDORES (Perfil 4): Solo ven 'ventas'
            if ($this->user->profile_id == 4) {
                if ($key !== 'ventas') {
                    continue;
                }
            }

            // 2. Para otros perfiles (Admin): Mostramos siempre que la ruta sea válida o si es una opción base
            // No aplicamos canShowFeature aquí para evitar que configuraciones restrictivas oculten lo que el Admin debe ver
            try {
                $feature['url'] = \Illuminate\Support\Facades\Route::has($feature['route']) 
                    ? route($feature['route']) 
                    : $feature['route'];
            } catch (\Exception $e) {
                $feature['url'] = '#';
            }
            
            $enabledFeatures[$key] = $feature;
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
