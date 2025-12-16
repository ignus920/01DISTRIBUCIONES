<?php

namespace App\Livewire\Tenant\PettyCash;

use Livewire\Component;
use Livewire\WithPagination;
//Modelos
use App\Models\Auth\Tenant;
use App\Models\Tenant\PettyCash\PettyCash as PettyCashModel;
use App\Models\Tenant\PettyCash\VntDetailPettyCash;
use App\Models\Tenant\PettyCash\VntReconciliations;
use App\Models\Tenant\PettyCash\VntDetailReconciliations;

//Servicios
use Illuminate\Support\Facades\Auth;
use App\Services\Tenant\TenantManager;
use App\Traits\HasCompanyConfiguration;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\PDF;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class PettyCash extends Component
{
    use WithPagination, HasCompanyConfiguration;

    public $pettyCash_id;
    public $base;
    public $showDetail = false;
    public $showModalSalesFinish = false;
    //public $warehouseId; // Added for dynamic warehouse selection
    public $paymentCounts = [];
    public $paymentValues = [];
    public $observations = '';

    //Propiedades para la tabla
    public $showModal = false;
    public $search = '';
    public $sortField = 'consecutive';
    public $sortDirection = 'desc';
    public $perPage = 10;

    //Messages
    public $errorMessage = '';

    protected $listeners = ['refreshPettyCash' => '$refresh'];

    protected $rules =[
        'base' => 'required|integer',
        //'warehouseId' => 'required|integer', // Added validation for warehouseId
    ];

    protected $queryString = [
        'search' => ['except' => ''],
        'perPage' => ['except' => 10],
    ];

    public function mount(){
        $this->ensureTenantConnection();
        // Inicializar configuración de empresa
        $this->initializeCompanyConfiguration();

        // DEBUG: Limpiar caché para testing
        $this->clearConfigurationCache();

        // DEBUG: Log para verificar inicialización
        Log::info('🔍 PettyCash mount() ejecutado', [
            'currentCompanyId' => $this->currentCompanyId,
            'currentPlainId' => $this->currentPlainId,
            'configService_exists' => $this->configService ? 'YES' : 'NO'
        ]);
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
    
    public function create()
    {
        $this->showModal = true;
    }

    public function save(){
        try{
            $this->ensureTenantConnection();
    
            $exists=$this->PettyCashExits($this->getwarehouse());
    
            if ($exists) {
                $this->addError('base', 'No se puede registrar, hay cajas abiertas');
            }else{
                $this->resetErrorBag('base');
                $this->validate();
            
                // Determine the next consecutive number for the given warehouse
                $lastConsecutive = PettyCashModel::where('warehouseId', $this->getwarehouse())->where('userIdOpen')->max('consecutive');
            
                $newConsecutive = $lastConsecutive ? $lastConsecutive + 1 : 1;
            
                $pettyCashData = [
                    'base' => $this->base,
                    'consecutive' => $newConsecutive, // Use the calculated consecutive
                    'status' => 1,
                    'created_at' => Carbon::now(),
                    'userIdOpen' => Auth::id(),
                    'warehouseId' => $this->getwarehouse(),//$this->warehouseId, // Use the dynamic warehouseId
                    'cashier' => Auth::id(),
                ];
            
                $newPettyCashId=PettyCashModel::create($pettyCashData);
                $pettyCash_id=$newPettyCashId->id;
                $this->saveDetailPettyCash($pettyCash_id);
                session()->flash('message', 'Registro realizado exitosamente.');
            
                $this->resetValidation();
                $this->resetForm();
            
                $this->showModal = false;
            }
        }catch(\Exception $e){
            Log::error($e);
            session()->flash('error', 'Error al registrar la caja.'. $e->getMessage());
        }
    }

    public function PettyCashExits($warehouseId){
        $this->ensureTenantConnection();

        return PettyCashModel::where('status', 1)->where('warehouseId', $warehouseId)->exists();
    }

    public function saveDetailPettyCash($pettyCash_id){
        try{
            $this->ensureTenantConnection();
            
            
            $dataDetailPettyCash = [
                'status' => 1,
                'value' => $this->base,
                'created_at' => Carbon::now(),
                'pettyCashId' => $pettyCash_id,
                'reasonPettyCashId' => 5,
                'methodPaymentId' => 1,
                'observations' => 'Apertura de caja'
            ];
    
            VntDetailPettyCash::create($dataDetailPettyCash);
        }catch(\Exception $e){
            session()->flash('error', 'Error al registrar el detalle: ' . $e->getMessage());
        }
    }

    public function viewDetail($pettyCash_id){
        $this->pettyCash_id=$pettyCash_id;
        $this->showDetail=true;
    }

    public function openSalesFinishModal($pettyCash_id){
        $this->showModalSalesFinish=true;
        // Inicializar arrays si están vacíos
        if (empty($this->paymentCounts)) {
            $methods = ['1', '2', '3', '4', '10', '11', '12'];
            foreach ($methods as $method) {
                $this->paymentCounts[$method] = 0;
                $this->paymentValues[$method] = 0;
            }
        }
        $this->pettyCash_id=$pettyCash_id;
    }

    public function closePettyCash()
    {
        $this->ensureTenantConnection();
        $dataPettyCash=[
            'status' => 0, 
            'dateClose' => Carbon::now(),
            'userIdClose' => Auth::id(),
            'updated_at' => Carbon::now(),
        ];

        $dataReconciliations=[
            'reconciliation' => 1,
            'observations' => $this->observations,
            'created_at' => Carbon::now(),
            'pettyCashId' => $this->pettyCash_id,
            'userId' => Auth::id()
        ];

        try{
            //Cambio estado Caja
            $pettyCashClose=PettyCashModel::findOrFail($this->pettyCash_id);
            $pettyCashClose->update($dataPettyCash);
            
            //Registro del cierre
            $close=VntReconciliations::create($dataReconciliations);
            
            //$this->showModalSalesFinish = false;
            //$this->reset(['paymentCounts', 'paymentValues', 'observations']);
            $this->saveDetailReconciliations($close->id);
            session()->flash('message', 'Registro realizado exitosamente');

            $this->showModalSalesFinish = false;
            $this->resetForm();

            return $this->ticketPettyCash($close->id, $this->pettyCash_id);
            $this->dispatch('refreshPettyCash');

        } catch (\Exception $e) {
            Log::error($e);
            session()->flash('error', 'Error no se realizó correctamente' . $e->getMessage());
        }
    }

    public function arqueoPettyCash(){
        $this->ensureTenantConnection();
        $dataReconciliations=[
            'reconciliation' => 0,
            'observations' => $this->observations,
            'created_at' => Carbon::now(),
            'pettyCashId' => $this->pettyCash_id,
            'userId' => Auth::id()
        ];

        try{
            $arqueo=VntReconciliations::create($dataReconciliations);
            $this->saveDetailReconciliations($arqueo->id);
            session()->flash('message', 'Registro realizado exitosamente');
            
            $this->showModalSalesFinish = false;
            $this->resetForm();
            return $this->ticketPettyCash($arqueo->id, $this->pettyCash_id);
            $this->dispatch('refreshPettyCash');

        }catch (\Exception $e) {
            Log::error($e);
            session()->flash('error', 'Error no se realizó correctamente' . $e->getMessage());
        }
    }

    public function saveDetailReconciliations($reconciliationId)
    {
        $this->ensureTenantConnection();

        // 1. Get all movements for the current petty cash
        $movements = VntDetailPettyCash::with('reasonsPettyCash')
            ->where('pettyCashId', $this->pettyCash_id)
            ->where('status', 1)
            ->whereNotIn('reasonPettyCashId', [5])
            ->get();

        // 2. Calculate system totals per payment method
        $systemValues = [];
        foreach ($movements as $movement) {
            $methodId = $movement->methodPaymentId;

            if (!isset($systemValues[$methodId])) {
                $systemValues[$methodId] = 0;
            }

            if ($movement->reasonsPettyCash->type === 'i') {
                $systemValues[$methodId] += $movement->value;
            } elseif ($movement->reasonsPettyCash->type === 'e') {
                $systemValues[$methodId] -= $movement->value;
            }
        }

        // 3. Define the payment methods available for reconciliation
        $paymentMethods = ['1', '2', '4', '10', '11', '12'];

        // 4. Iterate and save reconciliation details
        foreach ($paymentMethods as $methodId) {
            $userCount = $this->paymentCounts[$methodId] ?? 0;
            $systemTotal = $systemValues[$methodId] ?? 0;

            // Only create a record if there's a user count or a system total to record
            if ($userCount > 0 || $systemTotal != 0) {
                VntDetailReconciliations::create([
                    'reconciliationId' => $reconciliationId,
                    'methodPaymentId' => $methodId,
                    'value' => $userCount,
                    'valueSystem' => $systemTotal,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function ticketPettyCash($close_id, $pettyCash_id){
        $this->ensureTenantConnection();  

        // Obtener los datos del detalle del cierre/arqueo, compañia, sucursal, cajero, número de caja
        $detailPettyCash = VntDetailReconciliations::with('reconciliation', 'methodPayments')->where('reconciliationId', $close_id)->get();
        $infoCompany = $this->cashierPettyCash($close_id);
        $infoPettyCash = PettyCashModel::where('id', $pettyCash_id)->get();

        // Convertir a array y limpiar los datos de las consultas
        $cleanedDetails = $this->cleanUtf8Data($detailPettyCash->toArray());
        $cleanedInfoCompany = $this->cleanUtf8Data($infoCompany);
        $cleanedPettyCash = $this->cleanUtf8Data($infoPettyCash->toArray());
        
        $data = [
            'details' => $cleanedDetails, // Usar los datos limpios
            'pettyCash' => $cleanedPettyCash,
            'date' => now()->format('d/m/Y'),
            'time' => now()->format('H:i:s'),
            'infoCashier' => $cleanedInfoCompany,
        ];

        // Forzar encabezados UTF-8 en la respuesta
        $pdf = PDF::loadView('livewire.tenant.petty-cash.petty-cash-pdf', $data)
            ->setPaper('a4', 'portrait')
            ->setOption('defaultFont', 'Arial')
            ->setOption('isHtml5ParserEnabled', true)
            ->setOption('isRemoteEnabled', true)
            ->setOption('encoding', 'UTF-8')
            ->setOption('fontHeightRatio', 0.7); // Ajustar ratio de fuente si es necesario

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'pettyCash_' . $close_id . '_' . now()->format('Ymd_His') . '.pdf');
    }


    public function render()
{   
    $this->ensureTenantConnection();

    $petty_cashes = PettyCashModel::query()
        ->select('vnt_petty_cash.*', 'u.name')
        ->join('users as u', 'u.id', '=', 'vnt_petty_cash.userIdOpen')
        ->when($this->search, function ($query) {
            $query->where('vnt_petty_cash.consecutive', 'like', '%' . $this->search . '%')
                  ->orWhere('u.name', 'like', '%' . $this->search . '%');
        })
        ->orderBy($this->sortField, $this->sortDirection)
        ->paginate($this->perPage);

    return view('livewire.tenant.petty-cash.petty-cash', [
        'boxes' => $petty_cashes
    ]);
}

    public function canOpenPettyCash(): bool{
        $result = $this->isOptionEnabled(17);
        $value = $this->getOptionValue(17);

        Log::info('🔍 canOpenPettyCash() verificación', [
            'companyId' => $this->currentCompanyId,
            'option_id' => 17,
            'result' => $result ? 'TRUE' : 'FALSE',
            'option_value' => $value,
            'configService_exists' => $this->configService ? 'YES' : 'NO',
            'method_called' => 'isOptionEnabled(17) y getOptionValue(17)'
        ]);
        return $result;
    }

    public function cancel()
    {
        $this->resetValidation();
        $this->resetForm();
        $this->showModal = false;
    }

    public function cashierPettyCash($close_id)
    {
        // 1. Establecer el contexto del tenant para poder obtener su información.
        $this->ensureTenantConnection();
        
        // 2. Obtener dinámicamente el nombre de la base de datos del tenant.
        $tenantDbName = tenancy()->tenant->getInternalDatabaseNameAttribute();

        // 3. Definir el nombre de la base de datos central (asumimos 'rap' por el código existente y el error).
        $centralDbName = config('database.connections.central.database');

        // 4. Construir la consulta usando DB::table en la conexión por defecto (que es la central).
        //    Se especifica el nombre de la base de datos para CADA tabla para evitar ambigüedades.
        $data = DB::table("{$centralDbName}.user_tenants", 'uXt')
            ->select(
                'u.name as user_name',
                'c.businessName as company_name',
                'w.name as warehouse_name'
            )
            // Join a la tabla en la base de datos del tenant
            ->join("{$tenantDbName}.vnt_reconciliations as r", 'r.userId', '=', 'uXt.user_id')
            
            // Joins a las tablas en la base de datos central
            ->join("{$centralDbName}.users as u", 'u.id', '=', 'uXt.user_id')
            ->join("{$centralDbName}.tenants as t", 't.id', '=', 'uXt.tenant_id')
            ->join("{$centralDbName}.vnt_companies as c", 'c.id', '=', 't.company_id')
            ->join("{$centralDbName}.vnt_contacts as cnt", 'cnt.id', '=', 'u.contact_id')
            ->join("{$centralDbName}.vnt_warehouses as w", 'w.id', '=', 'cnt.warehouseId') // Asunción sobre r.warehouseId

            // Condiciones
            ->where('uXt.tenant_id', '8fb35c7f-b3b6-4e6b-b240-a4acefb1ab9a')
            ->where('uXt.user_id', Auth::id())
            ->where('r.id', $close_id)
            ->first();

        // Para depurar, puedes descomentar la siguiente línea:
        // dd($data);
        return $data;
    }

    public function getwarehouse(){
        $this->ensureTenantConnection();

        $centralDbName = config('database.connections.central.database');

        $data=DB::table("{$centralDbName}.users", 'u')
                    ->select('w.id')
                    ->join("{$centralDbName}.vnt_contacts as c", 'u.contact_id', '=', 'c.id')
                    ->join("{$centralDbName}.vnt_warehouses as w", 'c.warehouseId', '=', 'w.id')
                    ->where('u.id', Auth::id());
        return $data;
    }

    private function cleanUtf8Data($data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->cleanUtf8Data($value);
            }
            return $data;
        } elseif (is_object($data)) {
            // Si es un objeto, convertirlo a array, verificando si tiene el método toArray
            $dataArray = method_exists($data, 'toArray') ? $data->toArray() : (array) $data;
            return $this->cleanUtf8Data($dataArray);
        } elseif (is_string($data)) {
            // Limpiar la cadena UTF-8
            $cleaned = mb_convert_encoding($data, 'UTF-8', 'UTF-8');
            // Remover caracteres inválidos
            $cleaned = preg_replace('/[^\x{0000}-\x{007F}]/u', '', $cleaned);
            // Otra alternativa más agresiva
            $cleaned = iconv('UTF-8', 'UTF-8//IGNORE//TRANSLIT', $data);
            return $cleaned;
        }
        return $data;
    }

    private function cleanString($string)
    {
        // Primero intentar con iconv
        $string = iconv('UTF-8', 'UTF-8//IGNORE', $string);

        // Si aún hay problemas, usar regex para eliminar caracteres no UTF-8 válidos
        $string = preg_replace('/[^\x{0000}-\x{007F}\x{00A0}-\x{00FF}]/u', '', $string);

        // Convertir entidades HTML si es necesario
        $string = html_entity_decode($string, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return $string;
    }

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

        // Establecer conexión tenant
        $tenantManager = app(TenantManager::class);
        $tenantManager->setConnection($tenant);

        // Inicializar tenancy
        tenancy()->initialize($tenant);
    }

    private function resetForm(){
        $this->base='';
        $systemValues = [];
        $paymentCounts = [];
        $paymentValues = [];
    }
}
