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

    protected $queryString = ['search', 'status', 'selectedDeliveryId'];

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
                    $qty = $this->returnQuantities[$detail->id] ?? 0;
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


    public function viewOrder($id)
    {
        $this->selectedOrder = InvRemissions::with(['quote.customer', 'quote.warehouse', 'details.item'])
            ->find($id);
        
        if ($this->selectedOrder) {
            $this->currentItemIndex = 0;
            $this->returnQuantities = [];
            // Inicializar devoluciones en 0 o valor actual si existiera
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

    public function closeOrderModal()
    {
        $this->showingOrderModal = false;
        $this->selectedOrder = null;
    }

    public function payOrder($id)
    {
        // Redirigir al componente de pago existente si es posible
        $remission = InvRemissions::find($id);
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
}
