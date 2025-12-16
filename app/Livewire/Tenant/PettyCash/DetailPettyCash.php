<?php

namespace App\Livewire\Tenant\PettyCash;

use Livewire\Component;
use Livewire\WithPagination;
//Modelos
use App\Models\Tenant\PettyCash\VntDetailPettyCash;
use App\Models\Auth\Tenant;
use App\Models\Tenant\PettyCash\VntReasonsPettyCash;
use App\Models\Tenant\MethodPayments\VntMethodPayMents;
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

    //Propiedades para la tabla
    public $showModalMovement = false;
    public $search = '';
    public $sortField = 'invoiceId';
    public $sortDirection = 'desc';
    public $perPage = 6;

    public function boot()
    {
        $this->ensureTenantConnection();
        $this->initializeCompanyConfiguration();
    }

    protected $rules =[
        'typeMovement' => 'required',
        'reasonMovement' => 'required|integer',
        'methodPayMovement' => 'required|integer',
        'valueDetail' => 'required',
        'observations' => 'required'
        //'warehouseId' => 'required|integer', // Added validation for warehouseId
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

    public function getDetailPettyCashModel()
    {
        $distribuidoraId = '06bbc9a5-3fd0-4bb6-95c2-891904bec837';
        $currentTenantId = session('tenant_id');

        if ($currentTenantId === $distribuidoraId) {
            return new \App\Models\Tenant\PettyCash\PriDetailPettyCash();
        }

        return new VntDetailPettyCash();
    }

    public function getValuesDetail()
    {
        return $this->getDetailPettyCashModel()->where('pettyCashId', $this->pettyCash_id)->where('status', 1)
            ->with(['methodPayments','reasonsPettyCash'])
            ->whereNotIn('reasonPettyCashId', [5])
            ->when($this->search, function($query){
                $query->where('invoiceId', 'like', '%' . $this->search . '%')
                    ->orWhere('id', 'like', '%' . $this->search . '%');
            })->orderBy('created_at', $this->sortDirection)
            ->paginate($this->perPage);
    }

    public function mount($pettyCash_id){
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

    // ... methods ...

    // canDoMovement, canDoIncome, canDoEgress, canUseBase, createMovement, getReasonsProperty, getMethodPaymentProperty (No changes needed logic-wise, assuming services work)

    public function incomes(){
        $this->ensureTenantConnection();

        return $this->getDetailPettyCashModel()->selectRaw('SUM(value) AS sumIncomes')->
                                            where('pettyCashId', $this->pettyCash_id)
                                            ->where('status',1)
                                            ->whereIn('reasonPettyCashId', [1,2,6])
                                            ->value('sumIncomes');
    }

    public function egrees(){
        $this->ensureTenantConnection();

        return $this->getDetailPettyCashModel()->selectRaw('SUM(value) AS sumEgress')
                                            ->where('pettyCashId', $this->pettyCash_id)
                                            ->where('status',1)
                                            ->whereIn('reasonPettyCashId', [3,4])
                                            ->value('sumEgress');
    }

    public function basePettyCash(){
        $this->ensureTenantConnection();

        return $this->getDetailPettyCashModel()->selectRaw('SUM(value) AS sumBase')->
                                            where('pettyCashId', $this->pettyCash_id)
                                            ->where('status',1)
                                            ->where('reasonPettyCashId', 5)
                                            ->value('sumBase');
    }

    // ... getResumenProperty, render (no changes) ...

    public function loadDetailsData(){
        $this->ensureTenantConnection();
        $values = $this->getDetailPettyCashModel()->where('pettyCashId', $this->pettyCash_id)->first();
    }

    public function save(){
        try{
            $this->ensureTenantConnection();
            $this->validate();

            // Using direct model creation instead of service to avoid service dependency on VntDetailPettyCash
            // Or ideally update the service. For now, let's replicate logic with dynamic model.
            $detailModel = $this->getDetailPettyCashModel();
            
            $data = [
                'status' => 1,
                'value' => $this->valueDetail,
                'created_at' => Carbon::now(),
                'pettyCashId' => $this->pettyCash_id,
                'reasonPettyCashId' => $this->reasonMovement,
                'methodPaymentId' => $this->methodPayMovement,
                'observations' => $this->observations
            ];

            if($this->typeMovement=='e'){
                if($this->canUseBase()){
                    //Base
                    $resumBase = $this->basePettyCash();

                    //Ingresos
                    $sumIncomes = $this->incomes();

                    $disponible = $resumBase + $sumIncomes;

                    if($disponible>=$this->valueDetail){
                        $detailModel->create($data);
                        $this->itsOk=true;
                    }else{
                        $this->itsOk=false;
                    }
                }else{
                    //Ingresos
                    $sumIncomes = $this->incomes();
                    if($sumIncomes>=$this->valueDetail){
                        $detailModel->create($data);
                        $this->itsOk=true;
                    }else{
                        $this->itsOk=false;
                    }
                }
            }else{
                $detailModel->create($data);
                $this->itsOk=true;
            }

            $this->resetForm();
            $this->showModalMovement=false;

            if($this->itsOk){
                session()->flash('message', 'Registro realizado exitosamente');
            }else{
                session()->flash('warning', 'Disponible insuficiente para realizar un egreso');
            }

        }catch(\Exception $e){
            Log::error($e);
            session()->flash('error', 'Error no se realizó correctamente: ' . $e->getMessage());
            $this->resetForm();
        }
    }

    public function deleteMovement($detailMovement)
    {
        $this->ensureTenantConnection();
        $model = $this->getDetailPettyCashModel();
        
        $movement = $model->findOrFail($detailMovement);
        $typeMovement = $movement->reasonsPettyCash; // Adjusted to use relationship directly from model instance

        try{
            if($typeMovement->type == "i"){
                //Ingresos
                $income=$this->incomes();
                //Egresos
                $egress=$this->egrees();
                //Base 
                $base=$this->basePettyCash();

                if($this->canUseBase()){
                    $disponible=$income+$base-$egress;
                }else{
                    $disponible=$income-$egress;
                }
                
                if($disponible>=$movement->value){
                    $movement->update(['status' => 0]);
                    $this->dispatch('show-toast', message: 'Registro eliminado exitosamente', type: 'success');
                }else{
                    $this->dispatch('show-toast', message: 'Disponible insuficiente para realizar un egreso', type: 'warning');
                }
            }elseif($typeMovement->type == "e"){
                $movement->update(['status' => 0]);
                $this->dispatch('show-toast', message: 'Registro eliminado exitosamente', type: 'success');
            }

        }catch(\Exception $e){
            $this->dispatch('show-toast', message: 'Error no se realizó correctamente: ' . $e->getMessage(), type: 'error');
            $this->resetForm();
        }
    }

    public function exportExcel(){
        $fileName = 'DetalleCaja_' . $this->pettyCash_id . '_' . now()->format('Ymd_His') . '.xlsx';
        return Excel::download(new PettyCashDetailExport($this->pettyCash_id, $this->search), $fileName);
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

    private function resetForm(){
        $this->typeMovement='';
        $this->reasonMovement='';
        $this->methodPayMovement='';
        $this->valueDetail='';
        $this->observations='';
    }

    public function cancel()
    {
        $this->resetValidation();
        $this->resetForm();
        $this->showModalMovement = false;
    } 
}
