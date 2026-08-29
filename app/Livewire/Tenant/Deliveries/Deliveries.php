<?php

namespace App\Livewire\Tenant\Deliveries;

use App\Models\Tenant\DeliveriesList\DisDeliveries;
use App\Models\Tenant\Remissions\InvRemissions;
use App\Models\Tenant\PettyCash\VntDetailPettyCash;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use App\Traits\HasCompanyConfiguration;
use App\Traits\CanPrintDocuments;

class Deliveries extends Component
{
    use WithPagination, HasCompanyConfiguration, CanPrintDocuments;

    public $selectedDeliveryId;
    public $search = '';
    public $status = 'EN RECORRIDO'; // Estado por defecto que coincide con el inicio del transporte
    public $orderNovelty = ''; // Justificación global por pedido

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
        
        // Reset modal states explicitly
        $this->showingOrderModal = false;
        $this->selectedOrderData = null;
        $this->selectedOrder = null;

        // Si es administrador, ver todos los estados por defecto
        if ($user->profile_id != 13) {
            $this->status = 'Todos';
        }

        // Ya no seleccionamos un cargue por defecto para que muestre todo globalmente al inicio

        // Automatizar sincronización inicial
        $this->dispatchSync();
    }

    public function updatedSelectedDeliveryId()
    {
        $this->resetPage();
        $this->dispatchSync();
    }

    public function updatedSearch()
    {
        $this->resetPage();
        $this->dispatchSync();
    }

    public function updatedStatus()
    {
        $this->resetPage();
        $this->dispatchSync();
    }

    public function dispatchSync()
    {
        $this->dispatch('hydrate-local-db', $this->getSyncData());
    }

    /**
     * Sincronizar automáticamente al cambiar de página
     */
    /**
     * Calcula totales, saldos y pagos para una remisión.
     * Compartido entre la vista normal y el Sync de IndexedDB.
     */
    private function processRemissionTotals($remission)
    {
        if (!$remission->quote) {
            $remission->total_amount = 0;
            $remission->paid_amount = 0;
            $remission->balance_amount = 0;
            return $remission;
        }

        // 1. Calcular Devoluciones
        $returnValue = 0;
        foreach ($remission->details as $detail) {
            $qty = $detail->cant_return ?? 0;
            $lineValue = $qty * $detail->value;
            $lineTax = $lineValue * (($detail->tax ?? 0) / 100);
            $returnValue += ($lineValue + $lineTax);
        }

        // 2. Total Neto
        $remission->total_amount = $remission->quote->total - $returnValue;
        
        // 3. Pagos realizados (Caja Menor / Recaudos)
        $paid = \App\Models\Tenant\PettyCash\VntDetailPettyCash::where('invoiceId', $remission->quoteId)
            ->where('status', 1)
            ->sum('value');
        
        $remission->paid_amount = $paid;
        
        // 4. Saldo Pendiente
        $remission->balance_amount = max(0, $remission->total_amount - $paid);
        
        return $remission;
    }

    public function updatedPaginators()
    {
        $this->dispatchSync();
    }

    public function getDeliveriesProperty()
    {
        $user = auth()->user();
        $query = DisDeliveries::orderBy('id', 'desc');
        
        // El select de cargues NUNCA debe filtrarse a sí mismo para que el usuario pueda saltar entre ellos.
        // Mostramos los últimos 15 días para que la lista sea completa.
        $query->where(function($q) {
            $q->where('sale_date', '>=', now()->subDays(15)->format('Y-m-d'))
              ->orWhere('id', $this->selectedDeliveryId);
        });

        if ($user->profile_id == 13) {
            $query->where('deliveryman_id', $user->id);
        }

        return $query->get();
    }

    public function getIsCurrentDeliveryClosedProperty()
    {
        if (!$this->selectedDeliveryId) return false;
        return DisDeliveries::where('id', $this->selectedDeliveryId)->where('status', 'CERRADO')->exists();
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
        // Seguridad extra para perfil 13: ver solo lo que le pertenece y de la fecha actual por defecto
        if ($user->profile_id == 13) {
            $query->whereHas('delivery', function($q) use ($user) {
                $q->where('deliveryman_id', $user->id);
                // Si no hay búsqueda ni cargue seleccionado, ampliar el rango a los últimos 15 días (no solo hoy)
                if (!$this->selectedDeliveryId && !$this->search) {
                    $q->where('sale_date', '>=', now()->subDays(15)->format('Y-m-d'));
                }
            });
        }

        $remissions = $query->with(['quote.customer.company.mainWarehouse.activeContacts', 'quote.customer.company.routes.route', 'quote.customer.activeContacts', 'quote.warehouse.activeContacts', 'quote.branch.activeContacts', 'details'])
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
            ->orderBy('delivery_sequence', 'asc') // Ordenar por secuencia
            ->paginate(10);

        // Calcular totales y saldos para cada remisión
        foreach ($remissions as $remission) {
            $this->processRemissionTotals($remission);
            // Asegurar que el nombre del cliente esté listo para el Blade
            $remission->customer_full_name = $remission->quote->customer_name;
            
            // Lógica de Ruta Correcta (TAT)
            $routeName = 'N/A';
            if ($remission->quote && $remission->quote->customer && $remission->quote->customer->company) {
                $firstRoute = $remission->quote->customer->company->routes->first();
                if ($firstRoute && $firstRoute->route) {
                    $routeName = $firstRoute->route->name;
                }
            }
            $remission->route_name = $routeName;

            // Lógica de Dirección y Contacto Estricta (SQL Usuario: Ruta -> Compañía -> Almacén -> Contacto)
            $address = 'Sin dirección';
            $contactName = 'N/A';

            if ($remission->quote && $remission->quote->customer && $remission->quote->customer->company) {
                $company = $remission->quote->customer->company;
                
                // Siguiendo el SQL: vnt_companies -> vnt_warehouses -> vnt_contacts
                // Buscamos el almacén 'Principal' o el marcado como 'main' de esta compañía específica
                $targetWarehouse = $company->mainWarehouse ?? $company->warehouses->first();
                
                if ($targetWarehouse) {
                    $address = $targetWarehouse->address ?: 'Sin dirección';
                    $firstContact = $targetWarehouse->activeContacts->first();
                    if ($firstContact) {
                        $contactName = $firstContact->full_name;
                    }
                }
            }

            // Fallback de seguridad (solo si la ruta no tiene compañía vinculada)
            if ($address == 'Sin dirección' && $remission->quote) {
                $q = $remission->quote;
                $address = ($q->branch && !empty($q->branch->address)) ? $q->branch->address : ($q->warehouse->address ?? 'Sin dirección');
            }
            if ($contactName == 'N/A' && $remission->quote) {
                $q = $remission->quote;
                $contactName = ($q->branch && $q->branch->activeContacts->first()) ? $q->branch->activeContacts->first()->full_name : 'N/A';
            }

            $remission->address = $address;
            $remission->contact_name = $contactName;
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
                        'cant_return' => $detail->quantity
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
                'customer_name' => $this->selectedOrder->quote->customer_name,
                'branch_name' => $this->selectedOrder->quote->customer->name ?? 'N/A',
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
            }
            $this->orderNovelty = $this->selectedOrder->observations_return ?? '';
            $this->showingOrderModal = true;
        } else {
            session()->flash('error', 'No se pudo cargar el detalle del pedido.');
        }
    }

    public function saveProductNovelty($id)
    {
        $remission = InvRemissions::with('details')->find($id);
        if (!$remission) return;

        // Validar si hay alguna devolución de unidades
        $hasReturns = false;
        foreach ($this->returnQuantities as $qty) {
            if ($qty > 0) {
                $hasReturns = true;
                break;
            }
        }

        // Si hay devoluciones, la observación es obligatoria
        if ($hasReturns && empty(trim($this->orderNovelty))) {
            $this->dispatch('notificar-error', ['msg' => 'Para registrar una devolución de unidades, debe escribir la razón en las observaciones.']);
            return;
        }

        try {
            \DB::transaction(function () use ($remission) {
                // 1. Guardar la observación global en la remisión
                $remission->update([
                    'observations_return' => $this->orderNovelty
                ]);

                // 2. Guardar las cantidades devueltas en los detalles
                foreach ($this->returnQuantities as $detailId => $qty) {
                    $detail = $remission->details->where('id', $detailId)->first();
                    if ($detail) {
                        $detail->update([
                            'cant_return' => $qty
                        ]);
                    }
                }
            });

            $this->dispatch('pedido-actualizado');
            $this->dispatch('notificar-error', ['msg' => '¡Devoluciones guardadas correctamente!']);
        } catch (\Exception $e) {
            $this->dispatch('notificar-error', ['msg' => 'Error al guardar: ' . $e->getMessage()]);
        }
    }

    public function shareWhatsApp($id)
    {
        $remission = InvRemissions::with(['quote.customer', 'details'])->find($id);
        if (!$remission) return;

        // Calcular el total neto (Total de cotización - Devoluciones guardadas)
        $returnValue = 0;
        foreach ($remission->details as $detail) {
            $qty = $detail->cant_return ?? 0;
            $lineValue = $qty * $detail->value;
            $lineTax = $lineValue * (($detail->tax ?? 0) / 100);
            $returnValue += ($lineValue + $lineTax);
        }

        $totalNeto = ($remission->quote->total ?? 0) - $returnValue;
        $customer = $remission->quote->customer_name;
        $total = number_format($totalNeto, 0, ',', '.');
        $phone = $remission->quote->customer->phone ?? $remission->quote->customer->cellphone ?? '';
        
        $message = "Hola {$customer}, adjuntamos el detalle de su pedido #{$remission->consecutive}. Total: $ {$total}.";
        
        $pdfUrl = route('tenant.reports.remission', ['id' => $remission->id]);
        $message .= " Ver detalle: {$pdfUrl}";

        $whatsappUrl = "https://wa.me/" . preg_replace('/[^0-9]/', '', $phone) . "?text=" . urlencode($message);
        
        $this->dispatch('open-link', ['url' => $whatsappUrl]);
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
        // Este método queda deprecado en favor de saveProductNovelty que guarda todo el pedido.
        // Lo dejamos para compatibilidad offline si fuera necesario o lo redirigimos.
        $remission = InvRemissions::whereHas('details', fn($q) => $q->where('id', $detailId))->first();
        if ($remission) {
            $this->saveProductNovelty($remission->id);
        }
    }

    public function syncPendingReturns($pendingReturns)
    {
        foreach ($pendingReturns as $ret) {
            $detail = \App\Models\Tenant\Remissions\InvDetailRemissions::with('remission')->find($ret['detail_id']);
            if ($detail) {
                // 1. Actualizar el detalle (cantidad devuelta solamente)
                $detail->update([
                    'cant_return' => $ret['quantity']
                ]);

                // 2. IMPORTANTE: Guardar la observación en la remisión principal (cabecera)
                if ($detail->remission && !empty($ret['observation'])) {
                    $detail->remission->update([
                        'observations_return' => $ret['observation']
                    ]);
                }
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
            return redirect()->route('tenant.payment.quote', [
                'quoteId' => $remission->quoteId,
                'from' => 'deliveries',
                'deliveryId' => $remission->delivery_id ?? $this->selectedDeliveryId,
            ]);
        }
    }

    public function closeDeliveryLoad()
    {
        if (auth()->user()->profile_id == 13) return;

        $delivery = DisDeliveries::find($this->selectedDeliveryId);
        if (!$delivery) {
            $this->dispatch('notificar-error', ['msg' => 'Debe seleccionar un cargue para cerrar']);
            return;
        }

        // Verificar que todos los pedidos estén procesados
        $pending = InvRemissions::where('delivery_id', $this->selectedDeliveryId)
            ->whereIn('status', ['REGISTRADO', 'EN RECORRIDO'])
            ->count();

        if ($pending > 0) {
            $this->dispatch('notificar-error', ['msg' => "No se puede cerrar: Hay {$pending} pedidos pendientes de procesar"]);
            return;
        }

        $delivery->update([
            'status' => 'CERRADO',
            'closed_at' => now()
        ]);

        $this->dispatch('pedido-actualizado');
        $this->dispatch('notificar-error', ['msg' => '¡Cargue cerrado exitosamente!']);
    }

    public function returnOrder($id)
    {
        session()->flash('info', 'Devolver pedido ' . $id);
    }

    public function printOrder($id)
    {
        $this->printRemission($id);
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
        $user = auth()->user();
        
        // Obtenemos los IDs de los cargues que el usuario está viendo
        if ($this->selectedDeliveryId) {
            $deliveryIds = [$this->selectedDeliveryId];
        } else {
            $deliveryIds = $this->getDeliveriesProperty()->pluck('id')->toArray();
        }

        if (empty($deliveryIds)) return collect();

        // Consultamos todos los detalles de remisiones asociadas a esos cargues
        // Exceptuamos pedidos ANULADOS
        $query = \App\Models\Tenant\Remissions\InvDetailRemissions::with(['item', 'remission'])
            ->whereHas('remission', function($q) use ($deliveryIds) {
                $q->whereIn('delivery_id', $deliveryIds)
                  ->where('status', '!=', 'ANULADO');
            });
            
        // Si es transportador, solo sus propios cargues (seguridad extra)
        if ($user->profile_id == 13) {
            $query->whereHas('remission.delivery', function($dq) use ($user) {
                $dq->where('deliveryman_id', $user->id);
            });
        }
            
        $details = $query->get();

        $results = [];

        foreach ($details as $detail) {
            $rem = $detail->remission;
            if (!$rem) continue;
            
            $status = $rem->status;
            $qty_dev = (int)($detail->cant_return ?? 0);
            $qty_no_ent = 0;

            // Lógica de Negocio Unificada:
            // Siempre contamos cant_return como Devolución (DEV).
            // Si el pedido está finalizado (ENTREGADO/DEVUELTO), el NO ENT es 0.
            // Si el pedido está PENDIENTE, el saldo es NO ENTregado.
            
            if (in_array($status, ['ENTREGADO', 'DEVUELTO'])) {
                $qty_no_ent = 0;
            } else {
                $qty_no_ent = $detail->quantity - $qty_dev;
            }

            $total = $qty_dev + $qty_no_ent;

            $results[] = (object)[
                'remission_id' => $rem->id,
                'delivery_id' => $rem->delivery_id,
                'detail_id' => $detail->id,
                'consecutive' => $rem->consecutive ?? $rem->id,
                'item_name' => $detail->item->name ?? 'N/A',
                'dev' => $qty_dev,
                'no_ent' => $qty_no_ent,
                'qty_llevada' => $detail->quantity,
                'total' => $total,
                'value' => $detail->value,
                'subtotal' => ($qty_dev + $qty_no_ent) * $detail->value,
                'observation' => $detail->observations_return ?? $rem->observations_return
            ];
        }

        return collect($results);
    }

    public function getCollectionsProperty()
    {
        // 1. Obtener remisiones del día y de los cargues visibles para que el recaudo sea LIMPIO
        $remissionQuery = InvRemissions::query();
        
        if ($this->selectedDeliveryId) {
            $remissionQuery->where('delivery_id', $this->selectedDeliveryId);
        } else {
             $deliveryIds = $this->deliveries->pluck('id');
             if ($deliveryIds->isEmpty()) {
                 return collect();
             }
             $remissionQuery->whereIn('delivery_id', $deliveryIds);
             // IMPORTANTE: En el resumen general, solo mostrar lo de hoy para evitar ver "falsos duplicados"
             $remissionQuery->whereHas('delivery', function($q) {
                 $q->where('sale_date', '>=', now()->format('Y-m-d'));
             });
        }

        // 2. Obtener IDs de cotizaciones vinculadas a esas remisiones
        $quoteIds = $remissionQuery->pluck('quoteId');

        if ($quoteIds->isEmpty()) {
            return collect();
        }

        // 3. Consultar VntDetailPettyCash usando esos quoteIds
        return \App\Models\Tenant\PettyCash\VntDetailPettyCash::whereIn('invoiceId', $quoteIds)
            ->where('status', 1)
            ->with(['methodPayments', 'quote.customer', 'quote.remission'])
            ->orderBy('id', 'desc')
            ->get();
    }

    public function getCreditsProperty()
    {
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

        $remissions = $remissionQuery->with(['quote.customer', 'details'])->get();

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
                        'remission_id' => $remission->id,
                        'delivery_id' => $remission->delivery_id,
                        'consecutive' => $remission->consecutive ?? $remission->id,
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
        
        // 1. Obtener Cargues (Ampliamos el rango para asegurar que no falten datos históricos recientes)
        $deliveriesQuery = DisDeliveries::orderBy('id', 'desc');
        if ($user->profile_id == 13) {
            $deliveriesQuery->where('deliveryman_id', $user->id);
            // Aseguramos traer cargues de los últimos 30 días para evitar tarjetas vacías en cambios de cargue
            $deliveriesQuery->where('sale_date', '>=', now()->subDays(30)->format('Y-m-d'));
        } else {
             $deliveriesQuery->limit(50); // Límite generoso para admin
        }
        
        $deliveries = $deliveriesQuery->get();
        $deliveryIds = $deliveries->pluck('id');

        // 2. Obtener Remisiones con TODA la metadata necesaria
        $remissions = InvRemissions::whereIn('delivery_id', $deliveryIds)
            ->with(['quote.customer.company.mainWarehouse.activeContacts', 'quote.customer.company.routes.route', 'quote.customer.activeContacts', 'quote.warehouse.activeContacts', 'quote.branch.activeContacts', 'details.item'])
            ->get();

        // 3. Formas de pago y otros datos de configuración
        $paymentMethods = \App\Models\Tenant\MethodPayments\VntMethodPayMents::all();

        // Formatear remisiones para incluir metadata procesada (Evita N/A en offline)
        $remissionsData = $remissions->map(function($rem) {
            // Procesar Totales y Saldos antes de enviar
            $this->processRemissionTotals($rem);

            // Nombre del cliente robusto — usar el mismo accessor que viewOrder() para consistencia online/offline
            $customerName = 'N/A';
            if ($rem->quote) {
                $fromAccessor = $rem->quote->customer_name ?? '';
                if (!empty(trim($fromAccessor))) {
                    $customerName = trim($fromAccessor);
                } elseif ($rem->quote->customer) {
                    // Fallback manual si el accessor devuelve vacío
                    $c = $rem->quote->customer;
                    $manual = $c->businessName ?: (trim($c->firstName . ' ' . ($c->lastName ?? '')));
                    if (!empty(trim($manual))) $customerName = trim($manual);
                }
            }
            
            // Lógica Unificada Estricta (pattern SQL usuario)
            $address = 'Sin dirección';
            $contactName = 'N/A';
            if ($rem->quote && $rem->quote->customer && $rem->quote->customer->company) {
                $company = $rem->quote->customer->company;
                $targetWarehouse = $company->mainWarehouse ?? $company->warehouses->first();
                if ($targetWarehouse) {
                    $address = $targetWarehouse->address ?: 'Sin dirección';
                    $firstContact = $targetWarehouse->activeContacts->first();
                    if ($firstContact) {
                        $contactName = $firstContact->full_name;
                    }
                }
            }

            // Fallbacks
            if ($address == 'Sin dirección' && $rem->quote) {
                $address = ($rem->quote->branch && !empty($rem->quote->branch->address)) ? $rem->quote->branch->address : 'Sin dirección';
            }
            if ($contactName == 'N/A' && $rem->quote) {
                $contactName = ($rem->quote->branch && $rem->quote->branch->activeContacts->first()) ? $rem->quote->branch->activeContacts->first()->full_name : 'N/A';
            }

            return [
                'id' => $rem->id,
                'delivery_id' => $rem->delivery_id,
                'quoteId' => $rem->quoteId,
                'status' => $rem->status,
                'consecutive' => $rem->consecutive ?? $rem->id,
                'customer_name' => $customerName,
                'address' => $address,
                'total_amount' => $rem->total_amount,
                'paid_amount' => $rem->paid_amount,
                'balance_amount' => $rem->balance_amount,
                'contact_name' => $contactName,
                'route_name' => $rem->quote?->customer?->company?->routes?->first()?->route?->name ?? 'N/A', 
                'details' => $rem->details->map(function($d) {
                    return [
                        'id' => $d->id,
                        'remissionId' => $d->remissionId,
                        'itemId' => $d->itemId,
                        'name' => $d->item?->name ?? 'Producto desconocido',
                        'quantity' => $d->quantity,
                        'cant_return' => $d->cant_return ?? 0,
                        'value' => $d->value,
                        'tax' => $d->tax ?? 0
                    ];
                })
            ];
        });

        // 4. Recaudos con nombres procesados
        $collections = $this->getCollectionsProperty();
        $collectionsData = $collections->map(function($col) {
             $customerName = 'N/A';
             $consecutive = 'N/A';
             $remissionId = null;
             
             if ($col->quote) {
                 // Usar el accessor robusto del modelo Quote
                 $customerName = $col->quote->customer_name;
                 
                 // Intentar obtener de la remisión vinculada para el consecutivo
                 if ($col->quote->remission) {
                     $remissionId = $col->quote->remission->id;
                     $consecutive = $col->quote->remission->consecutive ?: $col->quote->remission->id;
                 } else {
                     $consecutive = $col->quote->consecutive ?: $col->quote->id;
                 }
             }

             // Limpieza de Forma de Pago (Evitar 'CASH' técnico)
             $method = $col->methodPayments->description ?? 'EFECTIVO';
             if (strtoupper($method) === 'CASH') $method = 'EFECTIVO';
             
             return [
                'id' => $col->id,
                'invoiceId' => $col->invoiceId, // ID de cotización
                'remission_id' => $remissionId,  // ID de remisión (para filtrado)
                'delivery_id' => $col->quote->remission?->delivery_id ?? null,
                'value' => $col->value,
                'methodPaymentId' => $col->methodPaymentId,
                'methods_summary' => $method,
                'customer_name' => $customerName,
                'observation' => $col->observations ?? '',
                'consecutive' => $consecutive,
                'created_at' => $col->created_at->toISOString()
             ];
        });

        return [
            'deliveries' => $deliveries,
            'remissions' => $remissionsData,
            'paymentMethods' => $paymentMethods,
            'collections' => $collectionsData,
            'returnedItems' => $this->getReturnedItemsProperty(),
            'credits' => $this->getCreditsProperty(),
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
                                'cant_return' => $detail->quantity
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
