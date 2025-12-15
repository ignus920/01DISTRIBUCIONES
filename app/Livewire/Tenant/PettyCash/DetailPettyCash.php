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

    public function getValuesDetail()
    {
        return VntDetailPettyCash::where('pettyCashId', $this->pettyCash_id)->where('status', 1)
            ->with('methodPayments','reasonsPettyCash')
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

    public function sortBy($field){
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
        $this->initializeCompanyConfiguration();
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
        return $result;
    }

    public function canDoIncome(): bool
    {
        $this->initializeCompanyConfiguration();
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
        $this->initializeCompanyConfiguration();
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

    public function createMovement(){
        $this->showModalMovement=true;
        //dd($this->canDoIncome());
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

    public function incomes(){
        $this->ensureTenantConnection();

        return VntDetailPettyCash::selectRaw('SUM(value) AS sumIncomes')->
                                            where('pettyCashId', $this->pettyCash_id)
                                            ->where('status',1)
                                            ->whereIn('reasonPettyCashId', [1,2,6])
                                            ->value('sumIncomes');
    }

    public function egrees(){
        $this->ensureTenantConnection();

        return VntDetailPettyCash::selectRaw('SUM(value) AS sumEgress')
                                            ->where('pettyCashId', $this->pettyCash_id)
                                            ->where('status',1)
                                            ->whereIn('reasonPettyCashId', [3,4])
                                            ->value('sumEgress');
    }

    public function basePettyCash(){
        $this->ensureTenantConnection();

        return VntDetailPettyCash::selectRaw('SUM(value) AS sumBase')->
                                            where('pettyCashId', $this->pettyCash_id)
                                            ->where('status',1)
                                            ->where('reasonPettyCashId', 5)
                                            ->value('sumBase');
    }

    public function getResumenProperty(){
        //Base
        $resumBase = $this->basePettyCash();

        //Ingresos
        $sumIncomes = $this->incomes();

        //Egresos
        $sumEgresos = $this->egrees();

        //Total
        $total= $resumBase + $sumIncomes - $sumEgresos;

        return [
            'resumBase' => $resumBase,
            'ingresos' => $sumIncomes,
            'egresos' => $sumEgresos,
            'total' => $total
        ];
    }

    public function render()
    {
        $this->ensureTenantConnection();
        $values = $this->getValuesDetail();
        return view('livewire.tenant.petty-cash.detail-petty-cash', [
            'detailPettyCash' => $values,
            'typeMovements' => $this->typeMovements
        ]);
    }

    public function loadDetailsData(){
        $this->ensureTenantConnection();
        $values = VntDetailPettyCash::where('pettyCashId', $this->pettyCash_id)->first();
    }

    public function save(){
        try{
            $this->ensureTenantConnection();
            $this->validate();

            $detailPettyCashService = app(DetailPettyCashServices::class);
            if($this->typeMovement=='e'){
                if($this->canUseBase()){
                    //Base
                    $resumBase = $this->basePettyCash();

                    //Ingresos
                    $sumIncomes = $this->incomes();

                    $disponible = $resumBase + $sumIncomes;

                    if($disponible>=$this->valueDetail){
                        $detailPettyCashService->createMovement([
                            'status' => 1,
                            'value' => $this->valueDetail,
                            'created_at' => Carbon::now(),
                            'pettyCashId' => $this->pettyCash_id,
                            'reasonPettyCashId' => $this->reasonMovement,
                            'methodPaymentId' => $this->methodPayMovement,
                            'observations' => $this->observations
                        ]);
                        $this->itsOk=true;
                    }else{
                        $this->itsOk=false;
                    }
                }else{
                    //Ingresos
                    $sumIncomes = $this->incomes();
                    if($sumIncomes>=$this->valueDetail){
                        $detailPettyCashService->createMovement([
                            'status' => 1,
                            'value' => $this->valueDetail,
                            'created_at' => Carbon::now(),
                            'pettyCashId' => $this->pettyCash_id,
                            'reasonPettyCashId' => $this->reasonMovement,
                            'methodPaymentId' => $this->methodPayMovement,
                            'observations' => $this->observations
                        ]);
                        $this->itsOk=true;
                    }else{
                        $this->itsOk=false;
                    }
                }
            }else{
                $detailPettyCashService->createMovement([
                    'status' => 1,
                    'value' => $this->valueDetail,
                    'created_at' => Carbon::now(),
                    'pettyCashId' => $this->pettyCash_id,
                    'reasonPettyCashId' => $this->reasonMovement,
                    'methodPaymentId' => $this->methodPayMovement,
                    'observations' => $this->observations
                ]);
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
            session()->flash('error', 'Error no se realizó correctamente: ' . $e->getMessage());
            $this->resetForm();
        }
    }

    public function deleteMovement($detailMovement){
        $this->ensureTenantConnection();
        $typeMovement=VntDetailPettyCash::find($detailMovement)->reasonsPettyCash;
        $movement=VntDetailPettyCash::findOrFail($detailMovement);
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
