<?php

namespace App\Livewire\Tenant\PettyCash;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Session;
use App\Models\Tenant\MethodPayments\VntMethodPayMents;
use App\Models\Tenant\PettyCash\PettyCash;
use App\Traits\HasCompanyConfiguration; // Adding trait
use Illuminate\Support\Facades\Log;
use App\Models\Auth\Tenant;
use App\Services\Tenant\TenantManager;
use Livewire\Attributes\Url;
use App\Models\Tenant\Quoter\VntQuote;

class PaymentQuote extends Component
{
    use HasCompanyConfiguration;

    // Datos de la cotización
    public $quoteId;
    public $quoteCustumer;
    public $quoteNumber;
    public $quoteSubtotal;
    public $quoteTaxes;
    public $quoteTotal;

    protected $queryString = [
        'fromPage' => ['as' => 'from']
    ];

    // Parámetro para saber desde dónde se abrió el componente
    #[Url(as: 'from')]
    public $fromPage = null;

    // Datos de anticipos
    public $advances = [];
    public $totalAdvances = 0;

    // Métodos de pago disponibles (solo 4 principales)
    public $paymentMethods = [
        'efectivo' => ['name' => 'EFECTIVO', 'value' => 0, 'selected' => false],
        'nequi' => ['name' => 'NEQUI', 'value' => 0, 'selected' => false],
        'daviplata' => ['name' => 'DAVIPLATA', 'value' => 0, 'selected' => false],
        'tarjeta' => ['name' => 'TARJETA', 'value' => 0, 'selected' => false],
    ];

    public $currentMethod = 'efectivo';

    // Cálculos dinámicos
    public $totalPaid = 0;
    public $remainingBalance = 0;

    // Estado del pago
    public $willBeCredit = false;
    public $observations = '';

    // Caja activa
    public $activePettyCash;

    // Estado de validaciones
    public $canProceedToPayment = false;
    public $isProcessing = false;

    // Pago del cliente y vueltas
    public $customerPayment = 0;
    public $customerChange = 0;

    public function updated($name)
    {
        // Al terminar de editar (blur), recalculamos y auto-distribuimos
        if (str_contains($name, 'paymentMethods.') && str_ends_with($name, '.value')) {
            $this->validateAndDistribute($name);
        }

        // Calcular vueltas cuando cambia el pago del cliente
        if ($name === 'customerPayment') {
            $this->calculateChange();
        }
    }

    private function validateAndDistribute($name)
    {
        $currentField = str_replace(['paymentMethods.', '.value'], '', $name);
        $newValue = max(0, (float) ($this->paymentMethods[$currentField]['value'] ?? 0));

        // Asegurar que el valor ingresado no sea mayor al total de la venta
        if ($newValue > $this->quoteTotal) {
            $newValue = $this->quoteTotal;
            $this->paymentMethods[$currentField]['value'] = $newValue;

            $this->dispatch('showAlert', 'El valor total supera el valor de la venta ($' . number_format($this->quoteTotal, 0, ',', '.') . '). Se ha ajustado automáticamente.');
        }

        // Redistribuir automáticamente los otros métodos de pago
        $this->redistributePaymentMethods($currentField, $newValue);
        $this->calculateBalances();
    }

    public function boot()
    {
        // Asegurar conexión tenant en cada request de Livewire
        $this->ensureTenantConnection();
        $this->initializeCompanyConfiguration();
    }

    public function getPettyCashModel()
    {
        if (auth()->user()->profile_id == 17) {
            return new \App\Models\TAT\PettyCash\TatPettyCash();
        }

        // Si no (Distribuidora), usar modelo estandar (vnt_)
        return new PettyCash(); 
    }

    public function getDetailPettyCashModel()
    {
        if (auth()->user()->profile_id == 17) {
            return new \App\Models\TAT\PettyCash\TatDetailPettyCash();
        }

        return new \App\Models\Tenant\PettyCash\VntDetailPettyCash();
    }

    public function mount($quoteId = null, $from = null)
    {
        // Asegurar conexión tenant
        $this->ensureTenantConnection();

        $this->quoteId = $quoteId ? (int) $quoteId : 1;

        // Capturar desde dónde se abrió (URL parameter)
        $this->fromPage = $from ?: request('from');
        
        // Log para depuración
        Log::info("PaymentQuote MOUNT - fromPage: " . ($this->fromPage ?? 'NULL') . " | request('from'): " . (request('from') ?? 'NULL'));

        if ($this->fromPage) {
            session(['last_from_page' => $this->fromPage]);
            Log::info("Session 'last_from_page' guardada: " . $this->fromPage);
        }

        // Cargar datos de la cotización
        $this->loadQuoteData();

        // Verificar que haya una caja abierta
        $this->checkActivePettyCash();

        // Los métodos de pago ya están definidos estáticamente

        // Simular anticipos existentes (opcional)
        $this->loadAdvances();

        // Configurar efectivo como método principal con el total por defecto
        $this->setDefaultCashAmount();

        // Calcular balances iniciales
        $this->calculateBalances();

        // Inicializar cálculo de vueltas
        $this->calculateChange();
    }

    private function setDefaultCashAmount()
    {
        // Establecer el efectivo como el total de la venta por defecto
        $this->paymentMethods['efectivo']['value'] = $this->quoteTotal;
        $this->paymentMethods['efectivo']['selected'] = true;

        // Asegurar que otros métodos empiecen en 0
        foreach ($this->paymentMethods as $key => $method) {
            if ($key !== 'efectivo') {
                $this->paymentMethods[$key]['value'] = 0;
                $this->paymentMethods[$key]['selected'] = false;
            }
        }
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

    private function loadQuoteData()
    {
        try {
            $quote = null;

            if (auth()->user()->profile_id == 17) {
                // TAT - Usuario de tienda
                $quote = \App\Models\TAT\Quoter\Quote::with(['customer', 'items.item'])
                    ->where('id', $this->quoteId)
                    ->first();
            } else {
                // VNT - Usuario distribuidora
                $quote = VntQuote::with(['customer', 'detalles.item'])
                    ->where('id', $this->quoteId)
                    ->first();
            }

            if (!$quote) {
                session()->flash('error', 'Cotización no encontrada.');
                $this->setDefaultQuoteData();
                return;
            }

            // Cargar datos del cliente
            if (auth()->user()->profile_id == 17) {
                // TAT
                $this->quoteCustumer = $quote->customer->display_name ?? 'Cliente no encontrado';
                $this->quoteNumber = 'COT-' . str_pad($quote->consecutive ?? 0, 6, '0', STR_PAD_LEFT);
                $this->calculateQuoteTotalsTAT($quote);
            } else {
                // VNT
                $this->quoteCustumer = $quote->customer_name ?? 'Cliente no encontrado';
                $this->quoteNumber = 'COT-' . str_pad($quote->consecutive ?? 0, 6, '0', STR_PAD_LEFT);
                $this->calculateQuoteTotals($quote);
            }

        } catch (\Exception $e) {
            session()->flash('error', 'Error al cargar la cotización: ' . $e->getMessage());
            $this->setDefaultQuoteData();
        }
    }

    private function calculateQuoteTotals($quote)
    {
        $subtotal = 0;
        $totalTaxes = 0;

        foreach ($quote->detalles as $detalle) {
            $lineSubtotal = $detalle->quantity * $detalle->value;
            $lineTax = $lineSubtotal * ($detalle->tax / 100);

            $subtotal += $lineSubtotal;
            $totalTaxes += $lineTax;
        }

        $this->quoteSubtotal = round($subtotal, 0);
        $this->quoteTaxes = round($totalTaxes, 0);
        $this->quoteTotal = round($subtotal + $totalTaxes, 0);
    }

    private function calculateQuoteTotalsTAT($quote)
    {
        $subtotal = 0;
        $totalTaxes = 0;

        foreach ($quote->items as $quoteItem) {
            $lineSubtotal = $quoteItem->quantity * $quoteItem->price;
            $lineTax = $lineSubtotal * ($quoteItem->tax_percentage / 100);

            $subtotal += $lineSubtotal;
            $totalTaxes += $lineTax;
        }

        $this->quoteSubtotal = round($subtotal, 0);
        $this->quoteTaxes = round($totalTaxes, 0);
        $this->quoteTotal = round($subtotal + $totalTaxes, 0);
    }

    private function setDefaultQuoteData()
    {
        // Datos por defecto si no se puede cargar la cotización
        $this->quoteCustumer = 'CLIENTE DE PRUEBA';
        $this->quoteNumber = 'COT-000001';
        $this->quoteSubtotal = 100000;
        $this->quoteTaxes = 19000;
        $this->quoteTotal = 119000;
    }

    private function checkActivePettyCash()
    {
        try {
            $model = $this->getPettyCashModel();
            $warehouseId = $this->getWarehouseId();
            
            $pettyCash = $model->where('status', 1)
                ->where('warehouseId', $warehouseId)
                ->first();

            if (!$pettyCash) {
                session()->flash('error', 'No hay una caja abierta. Debe abrir una caja antes de procesar pagos.');
                return false;
            }

            // Convertir a array para evitar problemas de hidratación
            $this->activePettyCash = $pettyCash->toArray();
            return true;

        } catch (\Exception $e) {
            session()->flash('error', 'Error al verificar la caja: ' . $e->getMessage());
            return false;
        }
    }

    public function getWarehouseId()
    {
        $this->ensureTenantConnection();

        $centralDbName = config('database.connections.central.database');

        $data = \Illuminate\Support\Facades\DB::table("{$centralDbName}.users", 'u')
            ->join("{$centralDbName}.vnt_contacts as c", 'u.contact_id', '=', 'c.id')
            ->join("{$centralDbName}.vnt_warehouses as w", 'c.warehouseId', '=', 'w.id')
            ->where('u.id', auth()->id())
            ->value('w.id');
            
        return $data;
    }


    private function loadAdvances()
    {
        // Aquí simularemos algunos anticipos de ejemplo
        // En la implementación real, esto vendrá de la base de datos
        $this->advances = [
            // ['id' => 1, 'method_name' => 'EFECTIVO', 'value' => 50000, 'date' => '2024-12-01'],
            // ['id' => 2, 'method_name' => 'TRANSFERENCIA', 'value' => 30000, 'date' => '2024-12-02'],
        ];

        $this->totalAdvances = collect($this->advances)->sum('value');
    }

    // Métodos obsoletos eliminados - ahora usamos paymentMethods array

    public function calculateBalances()
    {
        $totalFromMethods = 0;
        foreach ($this->paymentMethods as $method) {
            $totalFromMethods += (float) ($method['value'] ?? 0);
        }
        $this->totalPaid = round($this->totalAdvances + $totalFromMethods, 2);
        $this->remainingBalance = round($this->quoteTotal - $this->totalPaid, 2);

        // Determinar si puede proceder al pago
        $this->canProceedToPayment = $this->totalPaid > 0;

        // Calcular vueltas cada vez que cambie el balance
        $this->calculateChange();
    }

    /**
     * Calcular las vueltas para el cliente
     */
    public function calculateChange()
    {
        $customerPayment = (float) ($this->customerPayment ?? 0);

        // Las vueltas = Lo que paga el cliente - Total de la venta
        $this->customerChange = $customerPayment - $this->quoteTotal;
    }

    public function updateMethodValue($method, $value)
    {
        $value = max(0, (float) ($value ?? 0));
        $this->paymentMethods[$method]['value'] = $value;

        // Auto-balance: distribuir el resto automáticamente
        $this->autoDistributePayments();
        $this->calculateBalances();
    }

    /**
     * Redistribuir automáticamente los métodos de pago cuando se modifica uno
     */
    private function redistributePaymentMethods($changedField, $newValue)
    {
        // Calcular cuánto falta para completar el total después de este cambio
        $remainingAmount = $this->quoteTotal - $newValue;

        // Si no queda nada por pagar, poner todos los demás métodos en 0
        if ($remainingAmount <= 0) {
            foreach ($this->paymentMethods as $key => $method) {
                if ($key !== $changedField) {
                    $this->paymentMethods[$key]['value'] = 0;
                }
            }
            return;
        }

        // Estrategia: El efectivo siempre absorbe el cambio
        if ($changedField !== 'efectivo') {
            // Si el cambio no fue en efectivo, ajustar el efectivo al restante
            $this->paymentMethods['efectivo']['value'] = $remainingAmount;

            // Poner los otros métodos (que no son efectivo ni el que cambió) en 0
            foreach ($this->paymentMethods as $key => $method) {
                if ($key !== $changedField && $key !== 'efectivo') {
                    $this->paymentMethods[$key]['value'] = 0;
                }
            }
        } else {
            // Si el cambio fue en efectivo, mantener los otros métodos como están
            // y distribuir el restante entre ellos si es necesario
            $totalOtherMethods = 0;
            foreach ($this->paymentMethods as $key => $method) {
                if ($key !== 'efectivo') {
                    $totalOtherMethods += (float) ($method['value'] ?? 0);
                }
            }

            // Si los otros métodos suman más que lo disponible, redistribuirlos proporcionalmente
            if ($totalOtherMethods > $remainingAmount && $totalOtherMethods > 0) {
                $factor = $remainingAmount / $totalOtherMethods;
                foreach ($this->paymentMethods as $key => $method) {
                    if ($key !== 'efectivo') {
                        $this->paymentMethods[$key]['value'] = round(($method['value'] ?? 0) * $factor, 0);
                    }
                }
            }
        }
    }

    public function autoDistributePayments()
    {
        // Calcular total pagado con otros métodos (excluyendo efectivo)
        $totalOtherMethods = 0;
        foreach ($this->paymentMethods as $key => $method) {
            if ($key !== 'efectivo') {
                $totalOtherMethods += (float) ($method['value'] ?? 0);
            }
        }

        // El efectivo debe cubrir lo que falta para completar el total
        $remainingAmount = $this->quoteTotal - $totalOtherMethods;

        // Si la cantidad restante es negativa, el efectivo es 0.
        // NO redistribuimos los otros métodos aquí porque es intrusivo mientras el usuario escribe.
        if ($remainingAmount < 0) {
            $this->paymentMethods['efectivo']['value'] = 0;
        } else {
            $this->paymentMethods['efectivo']['value'] = $remainingAmount;
        }
    }

    private function redistributeOtherMethods($totalOtherMethods)
    {
        if ($totalOtherMethods <= 0) return;

        // Calcular factor de redistribución
        $factor = $this->quoteTotal / $totalOtherMethods;

        // Redistribuir proporcionalmente todos los métodos excepto efectivo
        foreach ($this->paymentMethods as $key => $method) {
            if ($key !== 'efectivo') {
                $this->paymentMethods[$key]['value'] = round(($method['value'] ?? 0) * $factor, 2);
            }
        }
    }

    public function autoDistributeFromCash()
    {
        // Calcular total de todos los métodos EXCEPTO efectivo
        $totalOtherMethods = 0;
        foreach ($this->paymentMethods as $key => $method) {
            if ($key !== 'efectivo') {
                $totalOtherMethods += (float) ($method['value'] ?? 0);
            }
        }

        // El efectivo debe ser el total de venta MENOS los otros métodos
        $cashAmount = $this->quoteTotal - $totalOtherMethods;

        // No permitir efectivo negativo
        if ($cashAmount < 0) {
            $cashAmount = 0;
            // Recalcular otros métodos proporcionalmente
            $this->redistributePayments($totalOtherMethods);
        }

        $this->paymentMethods['efectivo']['value'] = max(0, $cashAmount);
    }


    private function redistributePayments($overAmount)
    {
        // Si el total excede la venta, redistribuir proporcionalmente TODOS los métodos
        $scale = $this->quoteTotal / $overAmount;

        foreach ($this->paymentMethods as $key => $method) {
            if (((float) ($method['value'] ?? 0)) > 0) {
                $this->paymentMethods[$key]['value'] = round(((float) ($method['value'] ?? 0)) * $scale);
            }
        }
    }

    public function selectMethod($method)
    {
        $this->currentMethod = $method;
    }

    public function payTotalWithCurrentMethod()
    {
        // Encontrar el total actual pagado
        $totalCurrentlyPaid = array_sum(array_column($this->paymentMethods, 'value'));

        // Si no hay dinero en ningún lado, usar el total de la venta
        if ($totalCurrentlyPaid == 0) {
            $totalCurrentlyPaid = $this->quoteTotal;
        }

        // Limpiar TODOS los métodos
        foreach ($this->paymentMethods as $key => $method) {
            $this->paymentMethods[$key]['value'] = 0;
        }

        // Mover TODO el dinero al siguiente método automáticamente
        $methods = array_keys($this->paymentMethods);
        $currentIndex = array_search($this->currentMethod, $methods);
        $nextIndex = ($currentIndex + 1) % count($methods);
        $nextMethod = $methods[$nextIndex];

        // Asignar el dinero al siguiente método
        $this->paymentMethods[$nextMethod]['value'] = $totalCurrentlyPaid;

        // Cambiar la selección al nuevo método
        $this->currentMethod = $nextMethod;

        $this->calculateBalances();
    }

    public function nextMethod()
    {
        // Solo navegar, NO transferir dinero
        $methods = array_keys($this->paymentMethods);
        $currentIndex = array_search($this->currentMethod, $methods);
        $nextIndex = ($currentIndex + 1) % count($methods);
        $this->currentMethod = $methods[$nextIndex];
    }

    public function previousMethod()
    {
        // Solo navegar, NO transferir dinero
        $methods = array_keys($this->paymentMethods);
        $currentIndex = array_search($this->currentMethod, $methods);
        $prevIndex = ($currentIndex - 1 + count($methods)) % count($methods);
        $this->currentMethod = $methods[$prevIndex];
    }

    // Métodos obsoletos eliminados - funcionalidad movida a payTotalWithCurrentMethod()

    // Método obsoleto eliminado

    public function confirmPayment()
    {
        // Prevenir múltiples clics
        if ($this->isProcessing) {
            return;
        }

        // Validaciones finales
        if (!$this->canProceedToPayment) {
            $this->dispatch('showAlert', 'Error: Debe ingresar al menos un pago para proceder.');
            return;
        }

        if ($this->remainingBalance > 500) {
            $this->dispatch('showAlert', 'Advertencia: Queda un saldo pendiente de $' . number_format($this->remainingBalance, 0, ',', '.'));
            return;
        }

        // Marcar como procesando
        $this->isProcessing = true;

        // Mapeo de IDs de formas de pago
        $methodMap = [
            'efectivo' => 1,
            'nequi' => 11,
            'daviplata' => 12,
            'tarjeta' => 4, // Asumiendo Tarjeta de Crédito por defecto
        ];

        try {
            $this->ensureTenantConnection();
            
            // Verificar caja activa nuevamente por seguridad
            if (!$this->checkActivePettyCash()) {
                Log::warning("Abortando pago: No se encontró caja activa para el usuario " . auth()->id());
                return;
            }

            $currentDate = \Carbon\Carbon::now();
            $recordsCreated = 0;

            // Instanciar modelo dinámico
            $detailModel = $this->getDetailPettyCashModel();

            foreach ($this->paymentMethods as $key => $method) {
                $value = (float) ($method['value'] ?? 0);
                
                if ($value > 0) {
                    $methodId = $methodMap[$key] ?? 1; // Default a efectivo si no se encuentra
                    
                    $detailModel->create([
                        'status' => 1,
                        'value' => $value,
                        'created_at' => $currentDate,
                        'updated_at' => $currentDate,
                        'pettyCashId' => $this->activePettyCash['id'],
                        'reasonPettyCashId' => 1, // ID 1 = Ventas
                        'methodPaymentId' => $methodId,
                        'invoiceId' => $this->quoteId,
                        'observations' => "Pago cotización {$this->quoteNumber}. " . $this->observations
                    ]);
                    
                    $recordsCreated++;
                }
            }

            // Determinar si es una entrega
            $isDelivery = ($this->fromPage === 'deliveries' || session('last_from_page') === 'deliveries');

            // Solo actualizar el estado de la cotización si NO viene de entregas
            // (El usuario indica que para entregas no se debe tocar vnt_quotes)
            if (!$isDelivery) {
                Log::info("Actualizando estado de cotización {$this->quoteId} (No es entrega)...");
                if (auth()->user()->profile_id == 17) {
                    \App\Models\TAT\Quoter\Quote::where('id', $this->quoteId)->update(['status' => 'Pagado']);
                } else {
                    VntQuote::where('id', $this->quoteId)->update(['status' => 'Pagado']);
                }
            } else {
                Log::info("Omitiendo actualización de vnt_quotes/tat_quotes por ser una Entrega.");
            }

            Log::info("Confirmando pago para quote {$this->quoteId}. From: {$this->fromPage}. Session: " . session('last_from_page'));

            // --- INTEGRACIÓN ENTREGAS ---
            try {
                Log::info("Iniciando actualización de Remisión para quote {$this->quoteId}...");
                // Actualización DIRECTA por DB para asegurar el cambio
                // Según SQL dump proporcionado, el valor correcto es 'ENTREGADO' (ENUM)
                $count = \Illuminate\Support\Facades\DB::table('inv_remissions')
                    ->where('quoteId', (int)$this->quoteId)
                    ->update(['status' => 'ENTREGADO']);
                
                if ($count > 0) {
                    Log::info("ÉXITO DB: Remisión marcada como ENTREGADO.");
                } else {
                    Log::warning("AVISO DB: No se encontró remisión para la cotización ID: {$this->quoteId}.");
                }
            } catch (\Exception $e) {
                Log::error("ERROR DB (Remisión): " . $e->getMessage());
                // No lanzamos excepción aquí para no romper el flujo principal de pago si falla la remisión
            }
            // ----------------------------
            
            // Determinar a dónde redirigir de forma segura
            $target = session('last_from_page', $this->fromPage);
            
            // FALLBACK: Si no hay target, verificar si esta venta tiene una remisión (es una entrega)
            if (!$target) {
                $hasRemission = \Illuminate\Support\Facades\DB::table('inv_remissions')
                    ->where('quoteId', (int)$this->quoteId)
                    ->exists();
                if ($hasRemission) {
                    $target = 'deliveries';
                    Log::info("Fallback activado: Se detectó remisión para quote {$this->quoteId}. Target seteado a 'deliveries'.");
                }
            }

            Log::info("Finalizando confirmPayment. Destino detectado: " . ($target ?? 'DEFAULT (quoter)'));

            if ($target === 'deliveries') {
                session()->forget('last_from_page');
                session()->flash('success', 'Pedido entregado y pagado correctamente.');
                return redirect()->route('tenant.deliveries');
            } elseif ($target === 'sales-list') {
                session()->forget('last_from_page');
                session()->flash('success', 'Pago registrado correctamente.');
                return redirect()->route('tenant.tat.sales.list');
            } else {
                session()->flash('message', 'Pago registrado correctamente. Redirigiendo a nueva venta...');
                return redirect()->route('tenant.tat.quoter.index');
            }
             
        } catch (\Exception $e) {
            Log::error('Error guardando pago: ' . $e->getMessage());
            $this->dispatch('showAlert', 'Error: ' . $e->getMessage());
            // Resetear estado de procesamiento en caso de error
            $this->isProcessing = false;
        }
    }

    public function resetPayment()
    {
        foreach ($this->paymentMethods as $key => $method) {
            $this->paymentMethods[$key]['value'] = 0;
        }
        $this->willBeCredit = false;
        $this->observations = '';
        $this->calculateBalances();
        session()->flash('info', 'Formulario de pago reiniciado.');
    }

    #[Layout('layouts.app')]
    /**
     * Cancelar pago y regresar al quoter con los datos cargados
     */
    public function cancelPayment()
    {
        try {
            // Obtener los datos de la cotización para restaurar el carrito
            $quote = null;

            if (auth()->user()->profile_id == 17) {
                // TAT
                $quote = \App\Models\TAT\Quoter\Quote::with('items.item')->find($this->quoteId);
            } else {
                // VNT
                $quote = VntQuote::with('items.item')->find($this->quoteId);
            }

            if (!$quote) {
                session()->flash('error', 'No se pudo encontrar la cotización.');
                
                $target = session('last_from_page', $this->fromPage);
                
                // Fallback si no hay rastro del origen
                if (!$target) {
                    $hasRemission = \Illuminate\Support\Facades\DB::table('inv_remissions')
                        ->where('quoteId', (int)$this->quoteId)
                        ->exists();
                    if ($hasRemission) $target = 'deliveries';
                }

                if ($target === 'deliveries') {
                    return redirect()->route('tenant.deliveries');
                } elseif ($target === 'sales-list') {
                    return redirect()->route('tenant.tat.sales.list');
                } else {
                    return redirect()->route('tenant.tat.quoter.index');
                }
            }

            // Preparar datos del carrito para restaurar
            $cartItems = [];
            foreach ($quote->items as $quoteItem) {
                $item = $quoteItem->item; // Relación con el producto
                if ($item) {
                    // Calcular subtotal con IVA incluido
                    $baseSubtotal = $quoteItem->price * $quoteItem->quantity;
                    $taxPercentage = $quoteItem->tax_percentage ?? 0;
                    $taxAmount = $baseSubtotal * ($taxPercentage / 100);
                    $subtotalWithTax = $baseSubtotal + $taxAmount;

                    $cartItems[] = [
                        'id' => $item->id,
                        'name' => $item->name,
                        'sku' => $item->sku ?? 'N/A',
                        'price' => $quoteItem->price,
                        'quantity' => $quoteItem->quantity,
                        'subtotal' => $subtotalWithTax,
                        'stock' => $item->stock,
                        'tax_name' => $quoteItem->tax_percentage ? $quoteItem->tax_percentage . '%' : 'N/A',
                        'tax_percentage' => $taxPercentage,
                        'img_path' => $item->img_path ?? null,
                        'initials' => $this->getProductInitials($item->name),
                        'avatar_color' => $this->getAvatarColorClass($item->name)
                    ];
                }
            }

            // Preparar datos del cliente
            $customerData = [
                'id' => $quote->customerId,
                'identification' => $quote->customer->identification ?? 'N/A',
                'display_name' => $quote->customer->display_name ?? 'N/A',
                'typePerson' => $quote->customer->typePerson ?? 'Natural'
            ];

            // Guardar en sesión para que el QuoterView los cargue (incluyendo el ID de la venta para edición)
            session([
                'quoter_cart' => $cartItems,
                'quoter_customer' => $customerData,
                'quoter_quote_id' => $this->quoteId, // ID de la venta para editar
                'quoter_restored' => true
            ]);

            // Redirigir según desde dónde se abrió
            $target = session('last_from_page', $this->fromPage);
            
            // FALLBACK INTERNO
            if (!$target) {
                $hasRemission = \Illuminate\Support\Facades\DB::table('inv_remissions')
                    ->where('quoteId', (int)$this->quoteId)
                    ->exists();
                if ($hasRemission) {
                    $target = 'deliveries';
                }
            }

            if ($target === 'deliveries') {
                session()->forget('last_from_page');
                return redirect()->route('tenant.deliveries')
                                ->with('info', 'Pago cancelado. Regresando a Entregas.');
            } elseif ($target === 'sales-list') {
                session()->forget('last_from_page');
                return redirect()->route('tenant.tat.sales.list')
                                ->with('info', 'Pago cancelado. Regresando a la lista de ventas.');
            } else {
                return redirect()->route('tenant.tat.quoter.index')
                                ->with('success', 'Venta restaurada. Puede continuar agregando productos.');
            }

        } catch (\Exception $e) {
            Log::error('Error al cancelar pago', [
                'error' => $e->getMessage(),
                'quoteId' => $this->quoteId
            ]);

            session()->flash('error', 'Error al restaurar la venta: ' . $e->getMessage());
            
            $target = session('last_from_page', $this->fromPage);
            
            if (!$target) {
                $hasRemission = \Illuminate\Support\Facades\DB::table('inv_remissions')
                    ->where('quoteId', (int)$this->quoteId)
                    ->exists();
                if ($hasRemission) $target = 'deliveries';
            }

            if ($target === 'deliveries') {
                return redirect()->route('tenant.deliveries');
            } elseif ($target === 'sales-list') {
                return redirect()->route('tenant.tat.sales.list');
            } else {
                return redirect()->route('tenant.tat.quoter.index');
            }
        }
    }

    /**
     * Obtener iniciales del nombre del producto
     */
    private function getProductInitials($name)
    {
        if (!$name) return '??';

        // Limpiar y dividir el nombre en palabras
        $words = explode(' ', trim($name));

        if (count($words) == 1) {
            // Si es una sola palabra, tomar las primeras 2 letras
            return strtoupper(substr($words[0], 0, 2));
        } else {
            // Si son múltiples palabras, tomar la primera letra de las primeras 2 palabras
            return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
        }
    }

    /**
     * Obtener color de avatar basado en el nombre del producto
     */
    private function getAvatarColorClass($name)
    {
        $colors = [
            'bg-gradient-to-br from-blue-500 to-indigo-600',
            'bg-gradient-to-br from-purple-500 to-pink-600',
            'bg-gradient-to-br from-green-500 to-teal-600',
            'bg-gradient-to-br from-yellow-500 to-orange-600',
            'bg-gradient-to-br from-red-500 to-pink-600',
            'bg-gradient-to-br from-indigo-500 to-purple-600',
            'bg-gradient-to-br from-teal-500 to-cyan-600',
            'bg-gradient-to-br from-orange-500 to-red-600',
        ];

        // Usar el primer caracter del nombre para determinar el color
        $index = ord(strtoupper($name[0])) % count($colors);
        return $colors[$index];
    }

    public function render()
    {
        return view('livewire.tenant.petty-cash.payment-quote');
    }
}