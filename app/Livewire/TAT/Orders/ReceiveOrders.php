<?php

namespace App\Livewire\TAT\Orders;

use Livewire\Attributes\Url;
use Livewire\Attributes\On;
use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use App\Models\Auth\Tenant;
use App\Services\Tenant\TenantManager;

class ReceiveOrders extends Component
{
    #[Url(history: true)]
    public $search = '';

    // Company ID del usuario logueado
    public $companyId;

    // Wizard State
    public $items = []; // Collection of items to process
    public $currentIndex = 0;

    // Current Item Form Data
    public $currentItem = null;
    public $quantityReceived = 0;
    public $observations = '';
    public $difference = 0;

    public function mount()
    {
        $this->ensureTenantConnection();

        // Obtener company_id del usuario autenticado como en QuoterView
        $user = Auth::user();
        $this->companyId = $this->getUserCompanyId($user);

        Log::info('Mount ReceiveOrders', [
            'user_id' => $user->id,
            'user_profile_id' => $user->profile_id,
            'user_contact_id' => $user->contact_id,
            'companyId' => $this->companyId
        ]);

        if (!$this->companyId) {
            Log::error('No se pudo determinar company_id en ReceiveOrders');
            session()->flash('error', 'No se pudo determinar la empresa del usuario.');
            return redirect()->route('tenant.dashboard');
        }

        $this->loadItems();
    }

    public function updatedSearch()
    {
        $this->currentIndex = 0;
        $this->loadItems();
    }

    public function updatedQuantityReceived()
    {
        $this->calculateDifference();
    }

    public function calculateDifference()
    {
        if ($this->currentItem) {
            $requested = $this->currentItem->quantity_request ?? 0;
            $received = intval($this->quantityReceived);
            $this->difference = $received - $requested;
        }
    }

    public function loadItems()
    {
        if (!$this->companyId) {
            $this->items = collect([]);
            return;
        }

        $query = DB::table('tat_restock_list as r')
            ->join('tat_items as i', 'r.itemId', '=', 'i.id')
            ->leftJoin('inv_remissions as rem', 'rem.quoteId', '=', 'r.order_number')
            ->where('r.company_id', $this->companyId)
            ->where('r.status', 'Confirmado')
            ->select(
                'r.id',
                'r.itemId',
                'r.quantity_request',
                'r.quantity_recive', // Puede ser null inicialmente
                'r.created_at',
                'r.order_number',
                'i.name as item_name',
                'i.sku',
                'i.stock',
                'rem.consecutive as remise_number'
            );

        if ($this->search) {
            $query->where(function($q) {
                $q->where('rem.consecutive', 'like', '%' . $this->search . '%')
                  ->orWhere('r.order_number', 'like', '%' . $this->search . '%')
                  ->orWhere('i.name', 'like', '%' . $this->search . '%')
                  ->orWhere('i.sku', 'like', '%' . $this->search . '%');
            });
        }

        // Ordenar por fecha para mantener consistencia
        $this->items = $query->orderBy('r.created_at', 'desc')->get(); // Fetch all items (Wizard doesn't paginate normally)

        $this->loadCurrentItemData();
    }

    public function loadCurrentItemData()
    {
        if (isset($this->items[$this->currentIndex])) {
            $this->currentItem = $this->items[$this->currentIndex];
            // Si ya tiene valor recibido guardado, usarlo, si no, usar el solicitado por defecto
            $this->quantityReceived = $this->currentItem->quantity_recive ?? $this->currentItem->quantity_request; 
            // $this->observations = $this->currentItem->observations ?? ''; // Si existiera columna
            $this->calculateDifference();
        } else {
            $this->currentItem = null;
            $this->quantityReceived = 0;
            $this->difference = 0;
        }
    }

    public function next()
    {
        $this->saveCurrent();
        if ($this->currentIndex < count($this->items) - 1) {
            $this->currentIndex++;
            $this->loadCurrentItemData();
        } else {
            // Validar si es el último, tal vez mostrar mensaje de finalización?
            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => 'Has llegado al final de la lista.'
            ]);
        }
    }

    public function previous()
    {
        $this->saveCurrent();
        if ($this->currentIndex > 0) {
            $this->currentIndex--;
            $this->loadCurrentItemData();
        }
    }

    public function saveCurrent()
    {
        if (!$this->currentItem) return;

        $qtyReceived = intval($this->quantityReceived);
        
        if ($qtyReceived < 0) {
             $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'La cantidad no puede ser negativa.'
            ]);
            return;
        }

        try {
            DB::table('tat_restock_list')
                ->where('id', $this->currentItem->id)
                ->update([
                    'quantity_recive' => $qtyReceived,
                    // 'status' => 'Recibido', // NO actualizamos status a 'Recibido' todavía para que no desaparezca del Wizard? 
                    // O SI actualizamos? El usuario dijo "Confirmar Recibo" antes. 
                    // En un wizard de conteo, usualmente se guarda el conteo y al final se confirma todo, o se va confirmando uno a uno.
                    // Si confirmamos y cambiamos estado, desaparecería de la lista al recargar.
                    // Mantendremos 'Confirmado' pero actualizamos la cantidad. O agregamos un flag 'counted'?
                    // Por simplicidad y UX robusta: Actualizamos quantity_recive PERO NO EL STATUS todavía, 
                    // Solo cambiamos status cuando explícitamente se termine. 
                    // PERO el requerimiento anterior era que "Confirmar" cambiaba el estado.
                    // Si cambiamos, el item desaparece. El usuario quiere navegar Anterior/Siguiente.
                    // Propuesta: Guardar datos, pero cambiar estado solo al final o con un botón específico de "Finalizar Item".
                    // REVISIÓN: En la imagen hay "Siguiente" y "Cerrar". 
                    // Asumiré que el cambio de estado 'Recibido' se hará cuando el usuario explícitamente diga "Terminé" o quizás al guardar cada uno.
                    // Si cambio status a Recibido, el item sale de la query 'where status=Confirmado'.
                    // Para que el wizard fluya, mejor mantenemos status='Confirmado' pero guardamos la cantidad.
                    // Y quizás un botón 'Finalizar Lote' al final? O un botón 'Confirmar Item' individual que lo saque de la lista?
                    // EL usuario pidió "Confirmar Recibo" antes.
                    // Voy a asumir que al darle "Siguiente", se guarda la cantidad.
                    // Y para que sea definitivo, agregaremos un botón "Confirmar y Cerrar" o "Terminar" en el último paso.
                    // O mejor: Un botón explícito "Marcar como Recibido" en la UI que lo saque de la lista.
                    
                    // UPDATE: Por ahora guardamos la cantidad. El estado lo manejamos aparte o lo cambiamos al final.
                    // Espera, si no cambiamos estado, nunca salen de la lista.
                    // VOY A CAMBIAR EL ESTADO A 'Recibido' SOLO si el usuario presiona un botón de acción explícito, 
                    // NO en navegación simple, o el wizard se rompería (el índice cambiaría).
                    'updated_at' => now()
                ]);

            // Actualizar el objeto local también para que se refleje si volvemos
            $this->items[$this->currentIndex]->quantity_recive = $qtyReceived;

             $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => 'Guardado'
            ]);

        } catch (\Exception $e) {
            Log::error("Error guardando item: " . $e->getMessage());
        }
    }
    
    // Método explícito para finalizar un item (sacarlo de la lista)
    public function markAsReceived()
    {
        if (!$this->currentItem) return;

        try {
            // Primero guardar la cantidad actual
            $qtyReceived = intval($this->quantityReceived);

            // Primero guardar la cantidad actual (ya se hizo en saveCurrent, pero aseguramos en el objeto)
            $qtyReceived = intval($this->quantityReceived);

            /*
             * NOTA: NO marcamos como recibido individualmente aquí.
             * Dejamos que el bloque siguiente lo procese junto con los demás
             * para asegurar que se actualice el stock de TODOS.
             *
             * Si no tiene order_number (lista preliminar), se procesará en el else.
             */

            // Verificar si hay más items del mismo order_number que deben marcarse como recibidos
            $orderNumber = $this->currentItem->order_number;
            $companyId = $this->companyId; // Usar company_id obtenido del usuario logueado

            // Marcar todos los items del mismo order_number como recibidos si están confirmados
            if ($orderNumber && $companyId) {
                // Obtener todos los items del mismo order_number que están confirmados
                $itemsToReceive = DB::table('tat_restock_list')
                    ->select('id', 'itemId', 'quantity_recive', 'company_id', 'order_number', 'status')
                    ->where('order_number', $orderNumber)
                    ->where('company_id', $companyId)
                    ->where('status', 'Confirmado')
                    ->get();

                foreach ($itemsToReceive as $item) {
                    Log::info("Procesando item para stock", [
                        'restock_item_id' => $item->id,
                        'itemId' => $item->itemId,
                        'quantity_recive' => $item->quantity_recive ?? 'NULL',
                        'company_id' => $item->company_id
                    ]);

                    // Marcar como recibido
                    DB::table('tat_restock_list')
                        ->where('id', $item->id)
                        ->update([
                            'status' => 'Recibido',
                            'updated_at' => now()
                        ]);

                    // Actualizar stock directamente en tat_items usando itemId
                    $stockUpdated = DB::table('tat_items')
                        ->where('item_father_id', (int)$item->itemId)
                        ->where('company_id', $companyId)
                        ->increment('stock', $item->quantity_recive ?: 0);

                    if ($stockUpdated) {
                        Log::info("Stock actualizado directamente en tat_items", [
                            'item_id' => $item->itemId,
                            'company_id' => $companyId,
                            'cantidad_agregada' => $item->quantity_recive ?: 0
                        ]);
                    } else {
                        Log::warning("No se pudo actualizar stock en tat_items", [
                            'item_id' => $item->itemId,
                            'company_id' => $companyId
                        ]);
                    }
                }

                Log::info("Marcados como recibidos y actualizado stock para order_number: {$orderNumber}");
            } else {
                // Si NO tiene order_number o no hay companyId, procesamos solo el item actual
                Log::info("Procesando item individual (sin order_number)", [
                    'restock_item_id' => $this->currentItem->id
                ]);

                DB::table('tat_restock_list')
                    ->where('id', $this->currentItem->id)
                    ->update([
                        'quantity_recive' => $qtyReceived,
                        'status' => 'Recibido',
                        'updated_at' => now()
                    ]);
                
                // Actualizar stock TAMBIÉN para individual
                 DB::table('tat_items')
                        ->where('item_father_id', (int)$this->currentItem->itemId)
                        ->where('company_id', $this->companyId ?: $this->getUserCompanyId(Auth::user()))
                        ->increment('stock', $qtyReceived);
            }


            // Emitir evento para redirección automática
            $this->dispatch('item-marked-received');

        } catch (\Exception $e) {
            Log::error("Error finalizando item: " . $e->getMessage());
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Error al marcar como recibido: ' . $e->getMessage()
            ]);
        }
    }

    public function confirmMarkAsReceived()
    {
        // Emit event to show confirmation dialog
        $this->dispatch('confirm-mark-received');
    }

    #[On('proceed-mark-received')]
    public function proceedMarkReceived()
    {
        // First save current data, then mark as received
        $this->saveCurrent();
        $this->markAsReceived();
    }

    private function ensureTenantConnection()
    {
        $tenantId = session('tenant_id');
        if (!$tenantId) return redirect()->route('tenant.select');
        $tenant = Tenant::find($tenantId);
        if (!$tenant) {
            session()->forget('tenant_id');
            return redirect()->route('tenant.select');
        }
        $tenantManager = app(TenantManager::class);
        $tenantManager->setConnection($tenant);
    }

    protected function getUserCompanyId($user)
    {
        if (!$user) return null;
        if ($user->contact_id) {
            $contact = DB::table('vnt_contacts')->where('id', $user->contact_id)->first();
            if ($contact && isset($contact->warehouseId)) {
                $warehouse = DB::table('vnt_warehouses')->where('id', $contact->warehouseId)->first();
                if ($warehouse && isset($warehouse->companyId)) return $warehouse->companyId;
            }
        }
        return null;
    }

    public function render()
    {
        return view('livewire.TAT.orders.receive-orders');
    }
}
