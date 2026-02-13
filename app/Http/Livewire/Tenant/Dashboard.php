<?php

namespace App\Http\Livewire\Tenant;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Auth\Tenant;
use App\Traits\HasCompanyConfiguration;

class Dashboard extends Component
{
    use HasCompanyConfiguration;

    /** @var Tenant|null */
    public $tenant = null;

    /** @var \App\Models\User|null */
    public $user = null;

    /** @var array */
    public $stats = [];

    /**
     * Método mount
     * ⚠️ En Livewire SIEMPRE se inicializan propiedades usadas en Blade
     */
    public function mount(): void
    {
        // ✅ Inicialización defensiva (OBLIGATORIA)
        $this->stats = [
            'total_ventas_hoy' => 0,
            'total_clientes'   => 0,
            'total_productos'  => 0,
            'ventas_mes'       => 0,
        ];

        $this->user = Auth::user();

        // Seguridad adicional
        if (!$this->user) {
            $this->redirect(route('login'), navigate: true);
            return;
        }

        // Redirigir transportadores (profile_id = 13) a entregas
        if ($this->user->profile_id == 13) {
            $this->redirect(route('tenant.deliveries'), navigate: true);
            return;
        }

        // Tenant desde sesión
        $tenantId = Session::get('tenant_id');

        if (!$tenantId) {
            $this->redirect(route('tenant.select'), navigate: true);
            return;
        }

        $this->tenant = Tenant::find($tenantId);

        if (!$this->tenant) {
            Session::forget('tenant_id');
            $this->redirect(route('tenant.select'), navigate: true);
            return;
        }

        // Inicializar configuración de empresa
        $this->initializeCompanyConfiguration();

        // Cargar estadísticas
        $this->loadStats();
    }

    /**
     * Carga estadísticas del dashboard
     */
    protected function loadStats(): void
    {
        $companyId = $this->getUserCompanyId();

        if (!$companyId) {
            // Ya están en cero, no hacemos nada más
            return;
        }

        $this->stats = [
            'total_ventas_hoy' => 0, // TODO: query real
            'total_clientes'   => 0, // TODO: query real
            'total_productos'  => $this->getCompanyProductsCount($companyId),
            'ventas_mes'       => 0, // TODO: query real
        ];
    }

    /**
     * Obtiene el company_id del usuario autenticado
     */
    protected function getUserCompanyId(): ?int
    {
        if (!$this->user) {
            return null;
        }

        // Caso 1: usuario con contact_id
        if ($this->user->contact_id) {
            $contact = DB::table('vnt_contacts')
                ->where('id', $this->user->contact_id)
                ->first();

            if ($contact && isset($contact->warehouseId)) {
                $warehouse = DB::table('vnt_warehouses')
                    ->where('id', $contact->warehouseId)
                    ->first();

                return $warehouse?->companyId;
            }
        }

        // Caso 2: resolver por email (fallback)
        $company = DB::table('vnt_companies')
            ->join('vnt_warehouses', 'vnt_companies.id', '=', 'vnt_warehouses.companyId')
            ->join('vnt_contacts', 'vnt_warehouses.id', '=', 'vnt_contacts.warehouseId')
            ->where('vnt_contacts.email', $this->user->email)
            ->select('vnt_companies.id as company_id')
            ->first();

        return $company?->company_id;
    }

    /**
     * Cuenta productos activos de la empresa
     */
    protected function getCompanyProductsCount(int $companyId): int
    {
        try {
            return DB::table('tat_items')
                ->where('company_id', $companyId)
                ->where('status', 1)
                ->count();
        } catch (\Throwable $e) {
            Log::error('Dashboard | Error contando productos', [
                'company_id' => $companyId,
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }

    /**
     * Cambiar de tenant
     */
    public function switchTenant(): void
    {
        Session::forget('tenant_id');
        $this->redirect(route('tenant.select'), navigate: true);
    }

    /**
     * Logout seguro
     */
    public function logout(): void
    {
        Session::forget('tenant_id');
        Auth::logout();
        $this->redirect(route('login'), navigate: true);
    }

    /**
     * Verifica si una funcionalidad está habilitada
     */
    public function canShowFeature(string $optionId): bool
    {
        return $this->shouldShowField('TODAS', $optionId);
    }

    /**
     * Funcionalidades habilitadas
     */
    public function getEnabledFeatures(): array
    {
        $features = [
            'ventas'     => ['option_id' => '3',  'name' => 'Nueva Venta', 'route' => 'tenant.quoter.products'],
            'clientes'   => ['option_id' => '5',  'name' => 'Clientes',    'route' => 'tenant.customers'],
            'productos'  => ['option_id' => '15', 'name' => 'Productos',   'route' => '#'],
            'caja'       => ['option_id' => '28', 'name' => 'Cajas',       'route' => '#'],
            'inventario' => ['option_id' => '16', 'name' => 'Inventario',  'route' => '#'],
            'reportes'   => ['option_id' => '45', 'name' => 'Reportes',    'route' => '#'],
        ];

        return collect($features)
            ->filter(fn ($feature) => $this->canShowFeature($feature['option_id']))
            ->toArray();
    }

    #[Layout('layouts.app')]
    public function render()
    {
        return view('livewire.tenant.dashboard', [
            'tenant'          => $this->tenant,
            'stats'           => $this->stats,
            'enabledFeatures' => $this->getEnabledFeatures(),
        ]);
    }
}
