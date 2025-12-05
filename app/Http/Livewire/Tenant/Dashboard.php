<?php

namespace App\Http\Livewire\Tenant;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use App\Models\Auth\Tenant;
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

        // IMPORTANTE: Inicializar configuración de empresa
        $this->initializeCompanyConfiguration();

        // Cargar estadísticas básicas
        $this->loadStats();
    }

    protected function loadStats()
    {
        // Aquí puedes cargar estadísticas de la base del tenant
        // Por ahora dejamos datos de ejemplo
        $this->stats = [
            'total_ventas_hoy' => 0,
            'total_clientes' => 0,
            'total_productos' => 0,
            'ventas_mes' => 0,
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
     * Verifica si debe mostrarse una funcionalidad específica según configuración
     */
    public function canShowFeature(string $optionId): bool
    {
        // Usar el trait para verificar si la opción está habilitada
        return $this->shouldShowField('TODAS', $optionId);
    }

    /**
     * Obtener las funcionalidades habilitadas para mostrar accesos rápidos
     */
    public function getEnabledFeatures(): array
    {
        $allFeatures = [
            'ventas' => ['option_id' => '3', 'name' => 'Nueva Venta', 'route' => 'tenant.quoter'],
            'clientes' => ['option_id' => '5', 'name' => 'Clientes', 'route' => 'tenant.customers'],
            'productos' => ['option_id' => '15', 'name' => 'Productos', 'route' => '/items/items'],
            'caja' => ['option_id' => '28', 'name' => 'Cajas', 'route' => '#'],
            'inventario' => ['option_id' => '16', 'name' => 'Inventario', 'route' => '#'],
            'reportes' => ['option_id' => '45', 'name' => 'Reportes', 'route' => '#'],
        ];

        $enabledFeatures = [];

        foreach ($allFeatures as $key => $feature) {
            if ($this->canShowFeature($feature['option_id'])) {
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
