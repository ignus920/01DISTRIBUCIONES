<?php

namespace App\Livewire\Tenant\PettyCash;

use Livewire\Component;
use Livewire\WithPagination;
//Modelos
use App\Models\Tenant\PettyCash\VntDetailPettyCash;
use App\Models\Auth\Tenant;
use App\Models\Tenant\PettyCash\VntReasonsPettyCash;
use App\Models\Tenant\MethodPayments\VntMethodPayMents;
use App\Models\Tenant\PettyCash\PettyCash as PettyCashModel;
//Servicios
use App\Services\Tenant\TenantManager;
use App\Livewire\Tenant\PettyCash\Services\DetailPettyCashServices;
use App\Traits\HasCompanyConfiguration;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PettyCashDetailExport;

class DetailPettyCash extends Component
{
    use WithPagination, HasCompanyConfiguration;

    public $pettyCash_id;
    public $detailMovement;
    public $typeMovement;
    public $reasonMovement;
    public $methodPayMovement;
    public $valueDetail;
    public $observations;
    public $itsOk;
    public $activeTab = 'movements'; // 'movements' o 'reconciliations'

    //Propiedades para la tabla
    public $showModalMovement = false;
    public $search = '';
    public $sortField = 'invoiceId';
    public $sortDirection = 'desc';
    public $perPage = 5;

    public function boot()
    {
        $this->ensureTenantConnection();
        $this->initializeCompanyConfiguration();
    }

    protected $rules = [
        'typeMovement' => 'required',
        'reasonMovement' => 'required|integer',
        'methodPayMovement' => 'required|integer',
        'valueDetail' => 'required|integer'
        //'warehouseId' => 'required|integer', // Added validation for warehouseId
    ];

    protected $messages = [
        'typeMovement.required' => 'El tipo de movimiento es obligatorio.',
        'reasonMovement.required' => 'La razón del movimiento es obligatoria.',
        'methodPayMovement.required' => 'El método de pago es obligatorio.',
        'valueDetail.required' => 'El valor del detalle es obligatorio.',
        'valueDetail.integer' => 'El valor del detalle debe ser un número entero.'
    ];

    protected $listeners = ['refreshDetail' => '$refresh'];

    public function getTypeMovementsProperty()
    {
        $movements = [];

        if ($this->canDoIncome()) {
            $movements['i'] = 'INGRESO';
        }

        if ($this->canDoEgress()) {
            $movements['e'] = 'EGRESO';
        }

        return $movements;
    }

    public function getValuesDetail()
    {
        return $this->getDetailPettyCashModel()
            ->where('pettyCashId', $this->pettyCash_id)->where('status', 1)
            ->with('methodPayments', 'reasonsPettyCash')
            ->whereNotIn('reasonPettyCashId', [5])
            ->when($this->search, function ($query) {
                $query->where('invoiceId', 'like', '%' . $this->search . '%')
                    ->orWhere('id', 'like', '%' . $this->search . '%');
            })->orderBy('created_at', $this->sortDirection)
            ->paginate($this->perPage);
    }

    public function mount($pettyCash_id)
    {
        // Inicializar configuración de empresa
        //$this->initializeCompanyConfiguration();

        // DEBUG: Limpiar caché para testing
        $this->clearConfigurationCache();

        // DEBUG: Log para verificar inicialización
        Log::info('🔍 DetailPettyCash mount() ejecutado', [
            'currentCompanyId' => $this->currentCompanyId,
            'currentPlainId' => $this->currentPlainId,
            'configService_exists' => $this->configService ? 'YES' : 'NO'
        ]);

        $this->pettyCash_id = $pettyCash_id;
        $this->loadDetailsData();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirection = 'asc';
        }

        $this->sortField = $field;
        $this->resetPage();
    }

    public function canDoMovement(): bool
    {
        // DEBUG: Agregar logging detallado
        Log::info('🔍 canDoMovement() iniciado', [
            'user_id' => auth()->user()->id ?? 'NO_USER',
            'profile_id' => auth()->user()->profile_id ?? 'NO_PROFILE'
        ]);

        // Si es usuario TAT, no permitir
        if (auth()->user()->profile_id == 17) {
            Log::info('✅ canDoMovement() - Usuario TAT detectado, permitiendo');
            return false;
        }

        $result = $this->isOptionEnabled(18);
        $value = $this->getOptionValue(18);

        Log::info('🔍 canDoMovement() verificación', [
            'companyId' => $this->currentCompanyId,
            'option_id' => 18,
            'result' => $result ? 'TRUE' : 'FALSE',
            'option_value' => $value,
            'configService_exists' => $this->configService ? 'YES' : 'NO',
            'method_called' => 'isOptionEnabled(18) y getOptionValue(18)'
        ]);

        Log::info($result ? '✅ canDoMovement() - Retornando TRUE' : '❌ canDoMovement() - Retornando FALSE');
        return $result;
    }

    public function canDoIncome(): bool
    {
        // Si es usuario TAT, permitir
        if (auth()->user()->profile_id == 17) {
            return true;
        }

        $result = $this->isOptionEnabled(15);
        $value = $this->getOptionValue(15);
        Log::info('🔍 canDoIncome() verificación', [
            'companyId' => $this->currentCompanyId,
            'option_id' => 15,
            'result' => $result ? 'TRUE' : 'FALSE',
            'option_value' => $value,
            'configService_exists' => $this->configService ? 'YES' : 'NO',
            'method_called' => 'isOptionEnabled(15) y getOptionValue(15)'
        ]);
        return $result;
    }

    public function canDoEgress(): bool
    {
        // Si es usuario TAT, permitir
        if (auth()->user()->profile_id == 17) {
            return true;
        }

        $result = $this->isOptionEnabled(16);
        $value = $this->getOptionValue(16);
        Log::info('🔍 canDoEgress() verificación', [
            'companyId' => $this->currentCompanyId,
            'option_id' => 16,
            'result' => $result ? 'TRUE' : 'FALSE',
            'option_value' => $value,
            'configService_exists' => $this->configService ? 'YES' : 'NO',
            'method_called' => 'isOptionEnabled(16) y getOptionValue(16)'
        ]);
        return $result;
    }

    public function canUseBase(): bool
    {
        $this->initializeCompanyConfiguration();
        $result = $this->isOptionEnabled(71);
        $value = $this->getOptionValue(71);
        Log::info('🔍 canUseBase() verificación', [
            'companyId' => $this->currentCompanyId,
            'option_id' => 16,
            'result' => $result ? 'TRUE' : 'FALSE',
            'option_value' => $value,
            'configService_exists' => $this->configService ? 'YES' : 'NO',
            'method_called' => 'isOptionEnabled(71) y getOptionValue(71)'
        ]);
        return $result;
    }

    public function createMovement()
    {
        $this->resetForm();
        $this->showModalMovement = true;
    }

    public function getReasonsProperty()
    {
        $this->ensureTenantConnection();

        if (empty($this->typeMovement)) {
            return collect(); // Return empty if no type is selected
        }

        return VntReasonsPettyCash::where('id', '!=', 5)
            ->where('type', $this->typeMovement)
            ->get();
    }

    public function getMethodPaymentProperty()
    {
        $this->ensureTenantConnection();

        return VntMethodPayMents::where('status', 1)->where('type', 2)->get();
    }

    public function incomes()
    {
        $this->ensureTenantConnection();

        return $this->getDetailPettyCashModel()->selectRaw('SUM(value) AS sumIncomes')->where('pettyCashId', $this->pettyCash_id)
            ->where('status', 1)
            ->whereIn('reasonPettyCashId', [1, 2, 6])
            ->value('sumIncomes');
    }

    public function egrees()
    {
        $this->ensureTenantConnection();

        return $this->getDetailPettyCashModel()->selectRaw('SUM(value) AS sumEgress')
            ->where('pettyCashId', $this->pettyCash_id)
            ->where('status', 1)
            ->whereIn('reasonPettyCashId', [3, 4])
            ->value('sumEgress');
    }

    public function basePettyCash()
    {
        $this->ensureTenantConnection();

        return $this->getDetailPettyCashModel()->selectRaw('SUM(value) AS sumBase')->where('pettyCashId', $this->pettyCash_id)
            ->where('status', 1)
            ->where('reasonPettyCashId', 5)
            ->value('sumBase');
    }

    public function getResumenProperty()
    {
        //Base
        $resumBase = $this->basePettyCash();

        //Ingresos
        $sumIncomes = $this->incomes();

        //Egresos
        $sumEgresos = $this->egrees();

        //Total
        $total = $resumBase + $sumIncomes - $sumEgresos;

        return [
            'resumBase' => $resumBase,
            'ingresos' => $sumIncomes,
            'egresos' => $sumEgresos,
            'total' => $total
        ];
    }

    public function render()
    {
        try {
            Log::info('Render start', ['showModalMovement' => $this->showModalMovement]);
            $this->ensureTenantConnection();

            // Explicitly fetch data to debug potential DB errors
            $methodPayments = $this->MethodPayment;
            Log::info('MethodPayments fetched', ['count' => $methodPayments->count()]);

            // Calculate details
            $values = $this->getValuesDetail();
            Log::info('Render calculated values');

            return view('livewire.tenant.petty-cash.detail-petty-cash', [
                'detailPettyCash' => $values,
                'typeMovements' => $this->typeMovements,
                'methodPaymentsList' => $methodPayments
            ]);
        } catch (\Exception $e) {
            Log::error('Render failed: ' . $e->getMessage());
            // Intenta renderizar sin el modal si falla, para que al menos se vea la tabla
            return view('livewire.tenant.petty-cash.detail-petty-cash', [
                'detailPettyCash' => isset($values) ? $values : $this->getValuesDetail(),
                'typeMovements' => $this->typeMovements,
                'methodPaymentsList' => collect()
            ]);
        }
    }

    public function loadDetailsData()
    {
        $this->ensureTenantConnection();
        $values = $this->getDetailPettyCashModel()->where('pettyCashId', $this->pettyCash_id)->first();
    }

    public function save()
    {
        try {
            $this->ensureTenantConnection();
            $this->validate();

            // $detailPettyCashService = app(DetailPettyCashServices::class);
            $detailModel = $this->getDetailPettyCashModel();

            if ($this->typeMovement == 'e') {
                if ($this->canUseBase()) {
                    //Base
                    $resumBase = $this->basePettyCash();

                    //Ingresos
                    $sumIncomes = $this->incomes();

                    $disponible = $resumBase + $sumIncomes;

                    if ($disponible >= $this->valueDetail) {
                        $detailModel->create([
                            'status' => 1,
                            'value' => $this->valueDetail,
                            'created_at' => Carbon::now(),
                            'pettyCashId' => $this->pettyCash_id,
                            'reasonPettyCashId' => $this->reasonMovement,
                            'methodPaymentId' => $this->methodPayMovement,
                            'observations' => $this->observations
                        ]);
                        $this->itsOk = true;
                    } else {
                        $this->itsOk = false;
                    }
                } else {
                    //Ingresos
                    $sumIncomes = $this->incomes();
                    if ($sumIncomes >= $this->valueDetail) {
                        $detailModel->create([
                            'status' => 1,
                            'value' => $this->valueDetail,
                            'created_at' => Carbon::now(),
                            'pettyCashId' => $this->pettyCash_id,
                            'reasonPettyCashId' => $this->reasonMovement,
                            'methodPaymentId' => $this->methodPayMovement,
                            'observations' => $this->observations
                        ]);
                        $this->itsOk = true;
                    } else {
                        $this->itsOk = false;
                    }
                }
            } else {
                $detailModel->create([
                    'status' => 1,
                    'value' => $this->valueDetail,
                    'created_at' => Carbon::now(),
                    'pettyCashId' => $this->pettyCash_id,
                    'reasonPettyCashId' => $this->reasonMovement,
                    'methodPaymentId' => $this->methodPayMovement,
                    'observations' => $this->observations
                ]);
                $this->itsOk = true;
            }

            $this->resetForm();
            $this->showModalMovement = false;

            if ($this->itsOk) {
                session()->flash('message', 'Registro realizado exitosamente');
            } else {
                session()->flash('warning', 'Disponible insuficiente para realizar un egreso');
            }
        } catch (\Exception $e) {
            Log::error($e);
            session()->flash('error', 'Error no se realizó correctamente: ' . $e->getMessage());
            $this->resetForm();
        }
    }

    public function deleteMovement($detailMovement)
    {
        $this->ensureTenantConnection();
        $detailModel = $this->getDetailPettyCashModel();

        $movement = $detailModel->findOrFail($detailMovement);
        $typeMovement = $movement->reasonsPettyCash; // Acceder relacion desde instancia
        // Nota: Si reasonsPettyCash es null, esto fallará. Asumimos integridad.
        // Si no funciona relationship access en dynamic model, usar ->load('reasonsPettyCash') o similar.
        // TatDetailPettyCash tiene defined relationship reasonsPettyCash.

        try {
            if ($typeMovement->type == "i") {
                //Ingresos
                $income = $this->incomes();
                //Egresos
                $egress = $this->egrees();
                //Base 
                $base = $this->basePettyCash();

                if ($this->canUseBase()) {
                    $disponible = $income + $base - $egress;
                } else {
                    $disponible = $income - $egress;
                }

                if ($disponible >= $movement->value) {
                    $movement->update(['status' => 0]);
                    $this->dispatch('show-toast', message: 'Registro eliminado exitosamente', type: 'success');
                } else {
                    $this->dispatch('show-toast', message: 'Disponible insuficiente para realizar un egreso', type: 'warning');
                }
            } elseif ($typeMovement->type == "e") {
                $movement->update(['status' => 0]);
                $this->dispatch('show-toast', message: 'Registro eliminado exitosamente', type: 'success');
            }
        } catch (\Exception $e) {
            $this->dispatch('show-toast', message: 'Error no se realizó correctamente: ' . $e->getMessage(), type: 'error');
            $this->resetForm();
        }
    }

    public function exportExcel()
    {
        $fileName = 'DetalleCaja_' . $this->pettyCash_id . '_' . now()->format('Ymd_His') . '.xlsx';
        return Excel::download(new PettyCashDetailExport($this->pettyCash_id, $this->search), $fileName);
    }

    public function getDetailPettyCashModel()
    {
        if (auth()->user()->profile_id == 17) {
            return new \App\Models\TAT\PettyCash\TatDetailPettyCash();
        }

        return new VntDetailPettyCash();
    }
    private function ensureTenantConnection(): void
    {
        $tenantId = session('tenant_id');

        if (!$tenantId) {
            throw new \Exception('No tenant selected');
        }

        $tenant = Tenant::find($tenantId);

        if (!$tenant) {
            session()->forget('tenant_id');
            throw new \Exception('Invalid tenant');
        }

        // Establecer conexión tenant
        $tenantManager = app(TenantManager::class);
        $tenantManager->setConnection($tenant);

        // Inicializar tenancy
        tenancy()->initialize($tenant);
    }

    private function resetForm()
    {
        $this->typeMovement = '';
        $this->reasonMovement = '';
        $this->methodPayMovement = '';
        $this->valueDetail = '';
        $this->observations = '';
    }

    public function cancel()
    {
        $this->resetValidation();
        $this->resetForm();
        $this->showModalMovement = false;
    }

    public function switchTab($tab)
    {
        $this->activeTab = $tab;
    }

    public function getPettyCashModel()
    {
        // Si el usuario es perfil TAT (17)
        if (auth()->user()->profile_id == 17) {
            return new \App\Models\TAT\PettyCash\TatPettyCash();
        }

        // Si no (Distribuidora), usar modelo estandar (vnt_)
        return new PettyCashModel();
    }

    public function getStatusPettyCash()
    {
        $this->ensureTenantConnection();
        $model = $this->getPettyCashModel();
        $status = $model->where('id', $this->pettyCash_id)->value('status');
        return $status;
    }
}
