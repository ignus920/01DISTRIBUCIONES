<?php

namespace App\Livewire\Tenant\PettyCash;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Session;
use App\Models\Tenant\MethodPayments\VntMethodPayMents;
use App\Models\Tenant\PettyCash\PettyCash;
use Illuminate\Support\Facades\Log;
use App\Models\Auth\Tenant;
use App\Services\Tenant\TenantManager;
use App\Models\Tenant\Quoter\VntQuote;

class PaymentQuote extends Component
{
    // Datos de la cotización
    public $quoteId;
    public $quoteCustumer;
    public $quoteNumber;
    public $quoteSubtotal;
    public $quoteTaxes;
    public $quoteTotal;

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

    public function updating($name, $value)
    {
        // Interceptar actualizaciones de valores de métodos de pago
        if (str_contains($name, 'paymentMethods.') && str_ends_with($name, '.value')) {
            return max(0, (float) ($value ?? 0));
        }
        return $value;
    }

    public function updated($name)
    {
        // Después de actualizar cualquier valor de método de pago, recalcular
        if (str_contains($name, 'paymentMethods.') && str_ends_with($name, '.value')) {
            $this->autoDistributePayments();
            $this->calculateBalances();
        }
    }

    public function boot()
    {
        // Asegurar conexión tenant en cada request de Livewire
        $this->ensureTenantConnection();
    }

    public function mount($quoteId = null)
    {
        // Asegurar conexión tenant
        $this->ensureTenantConnection();

        $this->quoteId = $quoteId ? (int) $quoteId : 1;

        // Cargar datos de la cotización
        $this->loadQuoteData();

        // Verificar que haya una caja abierta
        $this->checkActivePettyCash();

        // Los métodos de pago ya están definidos estáticamente

        // Simular anticipos existentes (opcional)
        $this->loadAdvances();

        // Calcular balances iniciales
        $this->calculateBalances();
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
            $quote = VntQuote::with(['customer', 'detalles.item'])
                ->where('id', $this->quoteId)
                ->first();

            if (!$quote) {
                session()->flash('error', 'Cotización no encontrada.');
                $this->setDefaultQuoteData();
                return;
            }

            // Cargar datos del cliente (no almacenar el modelo)
            $this->quoteCustumer = $quote->customer_name ?? 'Cliente no encontrado';

            // Cargar número de cotización
            $this->quoteNumber = 'COT-' . str_pad($quote->consecutive ?? 0, 6, '0', STR_PAD_LEFT);

            // Calcular totales desde los detalles
            $this->calculateQuoteTotals($quote);

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

    // private function setDefaultQuoteData()
    // {
    //     // Datos por defecto si no se puede cargar la cotización
    //     $this->quoteCustumer = 'CLIENTE DE PRUEBA';
    //     $this->quoteNumber = 'COT-000001';
    //     $this->quoteSubtotal = 100000;
    //     $this->quoteTaxes = 19000;
    //     $this->quoteTotal = 119000;
    // }

    private function checkActivePettyCash()
    {
        try {
            $pettyCash = PettyCash::where('status', 1)->first();

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
    }

    public function updateMethodValue($method, $value)
    {
        $value = max(0, (float) ($value ?? 0));
        $this->paymentMethods[$method]['value'] = $value;

        // Auto-balance: distribuir el resto automáticamente
        $this->autoDistributePayments();
        $this->calculateBalances();
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

    public function autoDistributePayments()
    {
        // Calcular total actual de todos los métodos
        $totalCurrentPayments = 0;
        foreach ($this->paymentMethods as $method) {
            $totalCurrentPayments += (float) ($method['value'] ?? 0);
        }

        // Si el total excede la venta, ajustar proporcionalmente
        if ($totalCurrentPayments > $this->quoteTotal) {
            $this->redistributePayments($totalCurrentPayments);
        }
        // Si es menor al total, no hacer nada (permitir combinaciones manuales)
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
        // Validaciones finales
        if (!$this->canProceedToPayment) {
            $this->dispatch('showAlert', 'Error: Debe ingresar al menos un pago para proceder.');
            return;
        }

        if ($this->remainingBalance > 0.01) {
            $this->dispatch('showAlert', 'Advertencia: Queda un saldo pendiente de $' . number_format($this->remainingBalance, 0, ',', '.'));
            return;
        }

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
                 return;
            }

            $currentDate = \Carbon\Carbon::now();
            $recordsCreated = 0;

            foreach ($this->paymentMethods as $key => $method) {
                $value = (float) ($method['value'] ?? 0);
                
                if ($value > 0) {
                    $methodId = $methodMap[$key] ?? 1; // Default a efectivo si no se encuentra
                    
                    \App\Models\Tenant\PettyCash\VntDetailPettyCash::create([
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

            Log::info('Pago procesado:', [
                'quote_id' => $this->quoteId,
                'petty_cash_id' => $this->activePettyCash['id'],
                'records' => $recordsCreated
            ]);
            
            session()->flash('message', 'Pago registrado correctamente. Redirigiendo a nueva venta...');
            return redirect()->route('tenant.tat.quoter.index');
             
        } catch (\Exception $e) {
            Log::error('Error guardando pago: ' . $e->getMessage());
            $this->dispatch('showAlert', 'Error: ' . $e->getMessage());
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
    public function render()
    {
        return view('livewire.tenant.petty-cash.payment-quote');
    }
}