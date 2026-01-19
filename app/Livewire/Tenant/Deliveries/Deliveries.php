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
    public $selectedOrder = null;
    public $currentItemIndex = 0;
    public $returnQuantities = []; // [detailId => quantity]

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
                    $returnValue += $qty * $detail->value;
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
                // Inicializar en 0 por defecto como solicitó el usuario
                $this->returnQuantities[$detail->id] = 0; 
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

    public function updateReturnQuantity($detailId, $value)
    {
        $value = (int)$value;
        if ($value < 0) $value = 0;
        
        // Validar que no devuelva más de lo pedido
        $detail = $this->selectedOrder->details->where('id', $detailId)->first();
        if ($detail && $value > $detail->quantity) {
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
            return redirect()->route('tenant.petty-cash.payment-quote', ['quoteId' => $remission->quoteId, 'from' => 'deliveries']);
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
