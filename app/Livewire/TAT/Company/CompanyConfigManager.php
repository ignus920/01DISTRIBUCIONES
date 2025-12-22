<?php

namespace App\Livewire\TAT\Company;

use Livewire\Component;
use Livewire\Attributes\Validate;
use App\Models\TAT\Company\TatCompanyConfig;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CompanyConfigManager extends Component
{
    #[Validate('boolean')]
    public $vender_sin_saldo = false;

    #[Validate('boolean')]
    public $permitir_cambio_precio = false;

    public $companyId;
    public $isLoading = false;
    public $hasChanges = false;

    protected $listeners = [
        'configChanged' => '$refresh'
    ];

    public function mount()
    {
        $this->loadCompanyId();
        $this->loadCurrentConfig();
    }

    /**
     * Cargar el company_id del usuario autenticado
     */
    private function loadCompanyId()
    {
        $user = Auth::user();

        if ($user && $user->contact_id) {
            $contact = DB::table('vnt_contacts')
                ->where('id', $user->contact_id)
                ->first();

            if ($contact && isset($contact->warehouseId)) {
                $warehouse = DB::table('vnt_warehouses')
                    ->where('id', $contact->warehouseId)
                    ->first();

                $this->companyId = $warehouse ? $warehouse->companyId : null;
            }
        }

        if (!$this->companyId) {
            Log::warning('No se pudo determinar company_id para configuración', [
                'user_id' => $user->id ?? null,
                'contact_id' => $user->contact_id ?? null
            ]);
        }
    }

    /**
     * Cargar la configuración actual
     */
    public function loadCurrentConfig()
    {
        if (!$this->companyId) {
            return;
        }

        try {
            $config = TatCompanyConfig::getForCompany($this->companyId);

            $this->vender_sin_saldo = $config->vender_sin_saldo;
            $this->permitir_cambio_precio = $config->permitir_cambio_precio;

            $this->hasChanges = false;
        } catch (\Exception $e) {
            Log::error('Error cargando configuración de empresa', [
                'company_id' => $this->companyId,
                'error' => $e->getMessage()
            ]);

            session()->flash('error', 'Error al cargar la configuración de la empresa.');
        }
    }

    /**
     * Detectar cambios en las propiedades
     */
    public function updated($property)
    {
        $this->hasChanges = true;

        // Validar en tiempo real
        $this->validate();
    }

    /**
     * Guardar la configuración
     */
    public function save()
    {
        if (!$this->companyId) {
            session()->flash('error', 'No se puede determinar la empresa del usuario.');
            return;
        }

        $this->isLoading = true;

        try {
            $this->validate();

            $config = TatCompanyConfig::getForCompany($this->companyId);

            $updated = $config->updateMultipleConfigs([
                'vender_sin_saldo' => $this->vender_sin_saldo,
                'permitir_cambio_precio' => $this->permitir_cambio_precio,
            ]);

            if ($updated) {
                $this->hasChanges = false;

                Log::info('Configuración de empresa actualizada', [
                    'company_id' => $this->companyId,
                    'user_id' => Auth::id(),
                    'config' => [
                        'vender_sin_saldo' => $this->vender_sin_saldo,
                        'permitir_cambio_precio' => $this->permitir_cambio_precio,
                    ]
                ]);

                session()->flash('success', 'Configuración actualizada exitosamente.');

                // Emitir evento para otros componentes que puedan estar escuchando
                $this->dispatch('configChanged', [
                    'company_id' => $this->companyId,
                    'config' => $config->getConfigArray()
                ]);
            } else {
                session()->flash('error', 'No se pudo actualizar la configuración.');
            }
        } catch (\Exception $e) {
            Log::error('Error actualizando configuración de empresa', [
                'company_id' => $this->companyId,
                'error' => $e->getMessage(),
                'user_id' => Auth::id()
            ]);

            session()->flash('error', 'Error al guardar la configuración: ' . $e->getMessage());
        } finally {
            $this->isLoading = false;
        }
    }

    /**
     * Resetear a valores por defecto
     */
    public function resetToDefaults()
    {
        $this->vender_sin_saldo = false;
        $this->permitir_cambio_precio = false;
        $this->hasChanges = true;

        session()->flash('info', 'Configuración restablecida a valores por defecto. Haz clic en "Guardar" para aplicar los cambios.');
    }

    /**
     * Cancelar cambios
     */
    public function cancel()
    {
        $this->loadCurrentConfig();
        session()->flash('info', 'Cambios cancelados.');
    }

    /**
     * Verificar si el usuario tiene permisos para modificar configuraciones
     */
    public function getCanEditConfigProperty()
    {
        $user = Auth::user();

        // Solo administradores o usuarios con perfil específico pueden editar
        return $user && in_array($user->profile_id, [1, 17]); // Admin o perfil TAT
    }

    /**
     * Obtener información de la empresa
     */
    public function getCompanyInfoProperty()
    {
        if (!$this->companyId) {
            return null;
        }

        try {
            return DB::table('vnt_companies')
                ->where('id', $this->companyId)
                ->select('id', 'businessName', 'identification')
                ->first();
        } catch (\Exception $e) {
            return null;
        }
    }

    public function render()
    {
        return view('livewire.TAT.company.company-config-manager');
    }
}