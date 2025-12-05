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
        // Obtener el company_id del usuario para filtrar los datos
        $userCompanyId = $this->getUserCompanyId();

        if ($userCompanyId) {
            // Cargar estadísticas específicas de la empresa del usuario
            $this->stats = [
                'total_ventas_hoy' => 0, // TODO: Implementar consulta real
                'total_clientes' => 0,   // TODO: Implementar consulta real
                'total_productos' => $this->getCompanyProductsCount($userCompanyId),
                'ventas_mes' => 0,       // TODO: Implementar consulta real
            ];
        } else {
            // Datos por defecto si no se encuentra company_id
            $this->stats = [
                'total_ventas_hoy' => 0,
                'total_clientes' => 0,
                'total_productos' => 0,
                'ventas_mes' => 0,
            ];
        }
    }

    /**
     * Obtener el company_id del usuario autenticado
     */
    protected function getUserCompanyId()
    {
        if (!$this->user) {
            return null;
        }

        // Si el usuario tiene un contact_id, obtener el company_id desde ahí
        if ($this->user->contact_id) {
            $contact = \DB::table('vnt_contacts')
                ->where('id', $this->user->contact_id)
                ->first();

            if ($contact && isset($contact->warehouseId)) {
                $warehouse = \DB::table('vnt_warehouses')
                    ->where('id', $contact->warehouseId)
                    ->first();

                return $warehouse ? $warehouse->companyId : null;
            }
        }

        // Método alternativo: buscar en vnt_companies por email
        $company = \DB::table('vnt_companies')
            ->join('vnt_warehouses', 'vnt_companies.id', '=', 'vnt_warehouses.companyId')
            ->join('vnt_contacts', 'vnt_warehouses.id', '=', 'vnt_contacts.warehouseId')
            ->where('vnt_contacts.email', $this->user->email)
            ->select('vnt_companies.id as company_id')
            ->first();

        return $company ? $company->company_id : null;
    }

    /**
     * Contar productos específicos de la empresa
     */
    protected function getCompanyProductsCount($companyId)
    {
        try {
            return \DB::table('tat_items')
                ->where('company_id', $companyId)
                ->where('status', 1)
                ->count();
        } catch (\Exception $e) {
            \Log::error('Error obteniendo productos de la empresa', [
                'company_id' => $companyId,
                'error' => $e->getMessage()
            ]);
            return 0;
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
