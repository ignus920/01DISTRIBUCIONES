<?php

namespace App\Livewire\Tenant\Deliveries;

use App\Models\Tenant\DeliveriesList\DisDeliveries;
use App\Models\Tenant\Remissions\InvRemissions;
use App\Models\Tenant\PettyCash\VntDetailPettyCash;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

class Deliveries extends Component
{
    use WithPagination;

    public $selectedDeliveryId;
    public $search = '';
    public $status = 'EN RECORRIDO'; // Estado por defecto que coincide con el inicio del transporte

    // Propiedades para el modal
    public $showingOrderModal = false;
    public $showingFullReturnModal = false;
    public $selectedOrder = null;
    public $fullReturnObservation = '';
    public $currentItemIndex = 0;
    public $returnQuantities = []; 
    public $returnObservations = []; // [detailId => observation]
    public $showReturnsTable = false;
    public $showCollectionsTable = false;

    protected $queryString = ['search', 'status', 'selectedDeliveryId', 'showReturnsTable', 'showCollectionsTable'];

    public function mount()
    {
        $user = auth()->user();
        
        // Para administradores (no perfil 13), intentar obtener el cargue más reciente
        if ($user->profile_id != 13 && !$this->selectedDeliveryId) {
            $latestDelivery = DisDeliveries::orderBy('id', 'desc')->first();
            if ($latestDelivery) {
                $this->selectedDeliveryId = $latestDelivery->id;
            }
        }
        // Para transportadores, por defecto no filtramos por un cargue específico 
        // para que vean TODOS sus pedidos pendientes de todos sus cargues.
    }

    public function updatedSelectedDeliveryId()
    {
        $this->resetPage();
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedStatus()
    {
        $this->resetPage();
    }

    public function getDeliveriesProperty()
    {
        $user = auth()->user();
        $query = DisDeliveries::orderBy('id', 'desc');

        if ($user->profile_id == 13) {
            $query->where('deliveryman_id', $user->id);
        }

        return $query->get();
    }

    public function getRemissionsProperty()
    {
        $user = auth()->user();
        
        $query = InvRemissions::query();

        // Por defecto en esta vista de logística, solo mostramos lo que ya tiene un cargue asignado
        // A menos que estemos buscando algo específico.
        if (!$this->selectedDeliveryId && !$this->search) {
            $query->whereNotNull('delivery_id');
        }

        // Filtrar por cargue si hay uno seleccionado
        if ($this->selectedDeliveryId) {
            $query->where('delivery_id', $this->selectedDeliveryId);
        }

        // Seguridad extra para perfil 13: ver solo lo que le pertenece
        if ($user->profile_id == 13) {
            $query->whereHas('delivery', function($q) use ($user) {
                $q->where('deliveryman_id', $user->id);
            });
        }

        $remissions = $query->with(['quote.customer', 'quote.warehouse', 'quote.detalles', 'quote.branch', 'details'])
            ->when($this->search, function ($query) {
                $query->whereHas('quote.customer', function ($q) {
                    $q->where('businessName', 'like', '%' . $this->search . '%')
                      ->orWhere('firstName', 'like', '%' . $this->search . '%')
                      ->orWhere('lastName', 'like', '%' . $this->search . '%');
                });
            })
            ->when($this->status && $this->status !== 'Todos', function ($query) {
                $query->where('status', $this->status);
            })
            ->paginate(10);

        // Calcular totales y saldos para cada remisión
        foreach ($remissions as $remission) {
            if ($remission->quote) {
                // Calcular valor de devoluciones actuales en sesión
                $returnValue = 0;
                foreach ($remission->details as $detail) {
                    $qty = $detail->cant_return ?? 0;
                    $lineValue = $qty * $detail->value;
                    $lineTax = $lineValue * (($detail->tax ?? 0) / 100);
                    $returnValue += ($lineValue + $lineTax);
                }

                $remission->total_amount = $remission->quote->total - $returnValue;
                
                // Obtener lo pagado de vnt_detail_petty_cash
                $paid = \App\Models\Tenant\PettyCash\VntDetailPettyCash::where('invoiceId', $remission->quoteId)
                    ->where('status', 1)
                    ->sum('value');
                
                $remission->paid_amount = $paid;
                $remission->balance_amount = max(0, $remission->total_amount - $paid);
            } else {
                $remission->total_amount = 0;
                $remission->paid_amount = 0;
                $remission->balance_amount = 0;
            }
        }

        return $remissions;
    }

    public function confirmFullReturn()
    {
        if (empty(trim($this->fullReturnObservation))) {
            $this->dispatch('notificar-error', ['msg' => 'La justificación es obligatoria para la devolución total']);
            return;
        }

        try {
            \DB::transaction(function () {
                $remission = \App\Models\Tenant\Remissions\InvRemissions::findOrFail($this->selectedOrder->id);
                
                // 1. Actualizar Remisión
                $remission->update([
                    'status' => 'DEVUELTO',
                    'observations_return' => $this->fullReturnObservation
                ]);

                // 2. Actualizar todos los detalles al 100% devueltos
                foreach ($remission->details as $detail) {
                    $detail->update([
                        'cant_return' => $detail->quantity,
                        'observations_return' => 'Devolución Total: ' . $this->fullReturnObservation
                    ]);
                }
            });

            $this->showingFullReturnModal = false;
            $this->selectedOrder = null;
            $this->fullReturnObservation = '';
            
            $this->dispatch('pedido-actualizado');
        } catch (\Exception $e) {
            $this->dispatch('notificar-error', ['msg' => 'Error al procesar la devolución: ' . $e->getMessage()]);
        }
    }

    public function openFullReturnModal($orderId)
    {
        $this->selectedOrder = \App\Models\Tenant\Remissions\InvRemissions::with('details')->findOrFail($orderId);
        
        if (!$this->selectedOrder->delivery_id) {
            $this->dispatch('notificar-error', ['msg' => 'No se puede devolver un pedido que no ha sido cargado oficialmente']);
            $this->selectedOrder = null;
            return;
        }

        $this->fullReturnObservation = '';
        $this->showingFullReturnModal = true;
    }

    public function render()
    {
        return view('livewire.tenant.deliveries.deliveries', [
            'deliveries' => $this->deliveries,
            'remissions' => $this->remissions,
        ]);
    }


    public $selectedOrderData = null;

    public function viewOrder($id)
    {
        $this->selectedOrder = InvRemissions::with(['quote.customer', 'quote.warehouse', 'quote.branch', 'details.item'])
            ->find($id);
        
        if ($this->selectedOrder) {
            $this->currentItemIndex = 0;
            $this->returnQuantities = [];
            $this->returnObservations = [];
            
            // Preparar data serializable para Alpine (Modo híbrido)
            $this->selectedOrderData = [
                'id' => $this->selectedOrder->id,
                'consecutive' => $this->selectedOrder->consecutive ?? $this->selectedOrder->id,
                'status' => $this->selectedOrder->status,
                'customer_name' => $this->selectedOrder->quote->customer->businessName ?? ($this->selectedOrder->quote->customer->firstName . ' ' . $this->selectedOrder->quote->customer->lastName),
                'branch_name' => $this->selectedOrder->quote->branch->name ?? 'N/A',
                'delivery_id' => $this->selectedOrder->delivery_id,
                'total_amount' => $this->selectedOrder->total_amount,
                'balance_amount' => $this->selectedOrder->balance_amount,
                'details' => $this->selectedOrder->details->map(function($d) {
                    return [
                        'id' => $d->id,
                        'name' => $d->item->name ?? 'N/A',
                        'quantity' => $d->quantity,
                        'cant_return' => $d->cant_return ?? 0,
                        'value' => $d->value,
                        'observations_return' => $d->observations_return ?? ''
                    ];
                })->toArray()
            ];

            foreach($this->selectedOrder->details as $detail) {
                $this->returnQuantities[$detail->id] = (int)($detail->cant_return ?? 0);
                $this->returnObservations[$detail->id] = $detail->observations_return ?? '';
            }
            $this->showingOrderModal = true;
        } else {
            session()->flash('error', 'No se pudo cargar el detalle del pedido.');
        }
    }

    public function nextItem()
    {
        if ($this->selectedOrder && $this->currentItemIndex < count($this->selectedOrder->details) - 1) {
            $this->currentItemIndex++;
        }
    }

    public function previousItem()
    {
        if ($this->currentItemIndex > 0) {
            $this->currentItemIndex--;
        }
    }

    public function updatedReturnQuantities($value, $key)
    {
        $detailId = $key;
        $detail = \App\Models\Tenant\Remissions\InvDetailRemissions::find($detailId);
        
        if ($detail) {
            $max = $detail->quantity;
            $val = (int)$value;
            if ($val > $max) {
                $this->dispatch('notificar-error', ['msg' => "No puedes devolver más de lo pedido ({$max} und)"]);
                $this->returnQuantities[$detailId] = $max;
            } else {
                $this->returnQuantities[$detailId] = max(0, $val);
            }
        }
    }

    public function updateReturnQuantity($detailId, $qty)
    {
        $value = (int)$qty;
        if ($value < 0) $value = 0;
        
        $detail = \App\Models\Tenant\Remissions\InvDetailRemissions::find($detailId);
        if ($detail && $value > $detail->quantity) {
            $this->dispatch('notificar-error', ['msg' => "Máximo permitido: {$detail->quantity} unidades"]);
            $value = $detail->quantity;
        }

        $this->returnQuantities[$detailId] = $value;
    }

    public function incrementReturn($detailId)
    {
        $current = $this->returnQuantities[$detailId] ?? 0;
        $this->updateReturnQuantity($detailId, $current + 1);
    }

    public function decrementReturn($detailId)
    {
        $current = $this->returnQuantities[$detailId] ?? 0;
        $this->updateReturnQuantity($detailId, $current - 1);
    }

    public function saveReturn($detailId)
    {
        // Validar observación obligatoria si hay cantidad de devolución
        $qty = $this->returnQuantities[$detailId] ?? 0;
        $obs = trim($this->returnObservations[$detailId] ?? '');

        if ($qty > 0 && empty($obs)) {
            $this->dispatch('notificar-error', ['msg' => '¡Atención! La observación es obligatoria para devoluciones']);
            return;
        }

        $detail = \App\Models\Tenant\Remissions\InvDetailRemissions::find($detailId);
        if ($detail) {
            $detail->update([
                'cant_return' => $qty,
                'observations_return' => $obs
            ]);
            
            // Dispatch compacto para toast
            $this->dispatch('pedido-actualizado');
        }
    }

    public function syncPendingReturns($pendingReturns)
    {
        foreach ($pendingReturns as $ret) {
            $detail = \App\Models\Tenant\Remissions\InvDetailRemissions::find($ret['detail_id']);
            if ($detail) {
                $detail->update([
                    'cant_return' => $ret['quantity'],
                    'observations_return' => $ret['observation']
                ]);
            }
        }
        $this->dispatch('pedido-actualizado');
        return true;
    }

    public function closeOrderModal()
    {
        $this->showingOrderModal = false;
        $this->selectedOrder = null;
    }

    public function payOrder($id)
    {
        $remission = InvRemissions::find($id);

        if ($remission && !$remission->delivery_id) {
            $this->dispatch('notificar-error', ['msg' => 'Debe confirmar el cargue antes de registrar pagos']);
            return;
        }

        // Redirigir al componente de pago existente si es posible
        if ($remission && $remission->quoteId) {
            return redirect()->route('tenant.payment.quote', ['quoteId' => $remission->quoteId, 'from' => 'deliveries']);
        }
    }

    public function returnOrder($id)
    {
        session()->flash('info', 'Devolver pedido ' . $id);
    }

    public function printOrder($id)
    {
        session()->flash('info', 'Imprimir pedido ' . $id);
    }

    public function toggleReturns()
    {
        $this->showReturnsTable = !$this->showReturnsTable;
    }

    public function toggleCollections()
    {
        $this->showCollectionsTable = !$this->showCollectionsTable;
    }

    public function getReturnedItemsProperty()
    {
        $query = \App\Models\Tenant\Remissions\InvDetailRemissions::where('cant_return', '>', 0)
            ->with(['item', 'remission']);

        if ($this->selectedDeliveryId) {
            $query->whereHas('remission', function($q) {
                $q->where('delivery_id', $this->selectedDeliveryId);
            });
        } else {
            // Si no hay seleccionado, mostrar de todos los cargues visibles para el usuario
            $deliveryIds = $this->deliveries->pluck('id');
            if ($deliveryIds->isEmpty()) {
                return collect();
            }
            $query->whereHas('remission', function($q) use ($deliveryIds) {
                $q->whereIn('delivery_id', $deliveryIds);
            });
        }

        return $query->get();
    }

    public function getCollectionsProperty()
    {
        // 1. Obtener IDs de remisiones relevantes
        $remissionQuery = InvRemissions::query();
        
        if ($this->selectedDeliveryId) {
            $remissionQuery->where('delivery_id', $this->selectedDeliveryId);
        } else {
             $deliveryIds = $this->deliveries->pluck('id');
             if ($deliveryIds->isEmpty()) {
                 return collect();
             }
             $remissionQuery->whereIn('delivery_id', $deliveryIds);
        }

        // 2. Obtener IDs de cotizaciones vinculadas a esas remisiones
        $quoteIds = $remissionQuery->pluck('quoteId');

        if ($quoteIds->isEmpty()) {
            return collect();
        }

        // 3. Consultar VntDetailPettyCash usando esos quoteIds (invoiceId en tabla petty cash?)
        // Revisando el código original: invoiceId se compara con quoteId.
        return \App\Models\Tenant\PettyCash\VntDetailPettyCash::whereIn('invoiceId', $quoteIds)
            ->where('status', 1)
            ->with('methodPayments')
            ->select('methodPaymentId', DB::raw('SUM(value) as system_total'), DB::raw('0 as discount_total'))
            ->groupBy('methodPaymentId')
            ->get();
    }

    public function getCreditsProperty()
    {
        if (!$this->selectedDeliveryId) {
            return collect();
        }

        $remissions = InvRemissions::where('delivery_id', $this->selectedDeliveryId)
            ->with(['quote.customer'])
            ->get();

        $credits = [];
        foreach ($remissions as $remission) {
            if ($remission->quote) {
                // Calcular valor neto después de devoluciones (usando lógica similar a getRemissionsProperty)
                $returnValue = 0;
                foreach ($remission->details as $detail) {
                    $qty = $detail->cant_return ?? 0;
                    $lineValue = $qty * $detail->value;
                    $lineTax = $lineValue * (($detail->tax ?? 0) / 100);
                    $returnValue += ($lineValue + $lineTax);
                }

                $total = $remission->quote->total - $returnValue;
                
                $paid = \App\Models\Tenant\PettyCash\VntDetailPettyCash::where('invoiceId', $remission->quoteId)
                    ->where('status', 1)
                    ->sum('value');
                
                $balance = $total - $paid;

                if ($balance > 1) { // Evitar redondeos mínimos
                    $credits[] = (object)[
                        'customer' => ($remission->quote->customer->businessName ?? '') ?: ($remission->quote->customer->firstName . ' ' . $remission->quote->customer->lastName),
                        'balance' => $balance
                    ];
                }
            }
        }

        return collect($credits);
    }

    public function getSyncData()
    {
        $user = auth()->user();
        
        // 1. Obtener Cargues
        $deliveriesQuery = DisDeliveries::orderBy('id', 'desc');
        if ($user->profile_id == 13) {
            $deliveriesQuery->where('deliveryman_id', $user->id);
        }
        $deliveries = $deliveriesQuery->get();

        $deliveryIds = $deliveries->pluck('id');

        // 2. Obtener Remisiones
        $remissions = InvRemissions::whereIn('delivery_id', $deliveryIds)
            ->with(['quote.customer', 'quote.warehouse', 'quote.branch', 'details.item'])
            ->get();

        // 3. Formas de pago y otros datos de configuración
        $paymentMethods = \App\Models\Tenant\MethodPayments\VntMethodPayMents::all();

        return [
            'deliveries' => $deliveries,
            'remissions' => $remissions,
            'paymentMethods' => $paymentMethods,
            'serverTime' => now()->toIso8601String(),
            'userId' => $user->id
        ];
    }
    public function syncPendingPayments($payments)
    {
        $user = auth()->user();
        
        // Buscar caja menor activa del usuario
        $activePettyCash = \App\Models\Tenant\PettyCash\PettyCash::where('status', 'ABIERTA')
            ->where('userIdOpen', $user->id)
            ->first();

        // Mapeo simple de métodos de pago (ajustar IDs según tu BD)
        // Se asume: 1=Efectivo, 2=Transferencia, etc. Lo ideal es buscar por nombre.
        $methodsDB = \App\Models\Tenant\MethodPayments\VntMethodPayMents::all();
        
        $count = 0;

        foreach ($payments as $payment) {
            $remission = InvRemissions::find($payment['remissionId']);
            
            if ($remission && $remission->quoteId) {
                // Registrar Pagos
                foreach ($payment['methods'] as $key => $data) {
                    $val = (float)($data['value'] ?? 0);
                    if ($val > 0) {
                        // Buscar ID del método
                        $methodId = 1; // Default Efectivo
                        $searchKey = strtolower($key);
                        
                        // Intentar mapear
                        $found = $methodsDB->filter(function($m) use ($searchKey) {
                            return str_contains(strtolower($m->name), $searchKey); 
                        })->first();

                        if ($found) {
                            $methodId = $found->id;
                        } else {
                           // Fallback manual si no coinciden nombres
                           if ($searchKey == 'nequi') $methodId = 11; // Ejemplo
                           if ($searchKey == 'daviplata') $methodId = 12; // Ejemplo
                           if ($searchKey == 'tarjeta') $methodId = 4; // Ejemplo
                        }

                        VntDetailPettyCash::create([
                            'value' => $val,
                            'status' => 1,
                            'pettyCashId' => $activePettyCash ? $activePettyCash->id : null, 
                            'reasonPettyCashId' => 1, // 1 = Venta/Ingreso (Asumido)
                            'methodPaymentId' => $methodId,
                            'invoiceId' => $remission->quoteId,
                            'observations' => ($payment['observation'] ?? '') . ' (Offline Sync)',
                            'created_at' => \Carbon\Carbon::parse($payment['timestamp']),
                            'updated_at' => now(),
                        ]);
                    }
                }

                // Actualizar estado del pedido a ENTREGADO
                $remission->update(['status' => 'ENTREGADO']);
                $count++;
            }
        }

        if ($count > 0) {
            $this->dispatch('pedido-actualizado');
            $this->dispatch('notificar-error', ['msg' => "Se sincronizaron $count pagos offline correctamente."]);
        }
        
        return true;
    }

    public function syncStatusUpdates($updates)
    {
        try {
            foreach ($updates as $update) {
                $remission = InvRemissions::find($update['remission_id']);
                if ($remission) {
                    $remission->update([
                        'status' => $update['status'],
                        'observations_return' => $update['observation']
                    ]);
                    
                    // Si es devolución total, asegurar que todos los detalles se marquen como devueltos
                    if ($update['status'] === 'DEVUELTO') {
                        foreach ($remission->details as $detail) {
                            $detail->update([
                                'cant_return' => $detail->quantity,
                                'observations_return' => 'Devolución Total (Offline): ' . $update['observation']
                            ]);
                        }
                    }
                }
            }
            $this->dispatch('pedido-actualizado');
            return true;
        } catch (\Exception $e) {
            \Log::error("Error sincronizando estados (offline): " . $e->getMessage());
            return false;
        }
    }
}
