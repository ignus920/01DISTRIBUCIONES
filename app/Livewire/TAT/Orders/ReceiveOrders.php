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
    
    #[Url] 
    public $order_number = '';

    // Company ID of the logged-in user
    public $companyId;

    // Data
    public $items = []; 
    public $quantities = []; // [itemId => quantity]
    
    // UI State
    public $selectAll = false;
    public $selected = [];

    public function updatedSelectAll($value)
    {
        if ($value) {
            // Only select items that have a set quantity > 0
            $this->selected = $this->items->filter(function($item) {
                $qty = intval($this->quantities[$item->id] ?? 0);
                return $qty > 0;
            })->pluck('id')->map(fn($id) => (string)$id)->toArray();
        } else {
            $this->selected = [];
        }
    }

    public function updated($property, $value)
    {
        // If quantity is changed to <= 0, deselect the item automatically
        if (str_starts_with($property, 'quantities.')) {
            $parts = explode('.', $property);
            if (count($parts) === 2) {
                $itemId = $parts[1];
                if (intval($value) <= 0) {
                    $this->selected = array_values(array_diff($this->selected, [(string)$itemId]));
                    
                    // Also uncheck "select all" if it was checked
                    if (empty($this->selected)) {
                         $this->selectAll = false;
                    }
                }
            }
        }
    }

    public function confirmSelected()
    {
        if (empty($this->selected)) {
            $this->dispatch('show-toast', [
                'type' => 'warning',
                'message' => 'No hay ítems seleccionados.'
            ]);
            return;
        }

        // Filter out any selected items that might have 0 quantity explicitly
        $validSelection = [];
        foreach ($this->selected as $id) {
             if (intval($this->quantities[$id] ?? 0) > 0) {
                 $validSelection[] = $id;
             }
        }
        
        if (empty($validSelection)) {
             $this->dispatch('show-toast', [
                'type' => 'warning',
                'message' => 'Los ítems seleccionados deben tener cantidad mayor a 0.'
            ]);
            $this->selected = [];
            return;
        }

        $count = 0;
        
        DB::beginTransaction();
        try {
            foreach ($validSelection as $id) {

                // Determine if item exist
                $itemId = DB::table('tat_restock_list as tr')
                    ->join('tat_items as it', 'it.id', '=', 'tr.itemId')
                    ->where('tr.id', 96)
                    ->value('tr.itemId');
                     
                $company = DB::table('tat_items')
                    ->where('company_id', $this->companyId)
                    ->first();

                $item = $this->items->firstWhere('id', $id);
                if (!$item) continue;

                 if (!$itemId){
                    // create a new item to tat_items
                    DB::table('tat_items')->insertGetId([
                        'item_father_id' => $item->itemId,
                        'company_id' => $this->companyId,
                        'sku' => $item->it_sku_dis,
                        'name' => $item->it_name_dis,
                        'taxId' => $item->taxId,
                        'categoryId' => $company->categoryId,
                        'stock' => 0,
                        'cost' => (int) $item->price,
                        'price' => (int) (ceil($item->price / 100) * 100),
                        'status' => 1,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);
                 }

                // Determine quantity to save
                $qtyToReceive = intval($this->quantities[$id] ?? 0);

                // 1. Update tat_restock_list status and quantity
                DB::table('tat_restock_list')
                    ->where('id', $item->id)
                    ->update([
                        'quantity_recive' => $qtyToReceive,
                        'status' => 'Recibido',
                        'updated_at' => now()
                    ]);

                // 2. Update stock in tat_items
                DB::table('tat_items')
                    ->where('item_father_id', (int)$item->itemId)
                    ->where('company_id', $this->companyId)
                    ->increment('stock', $qtyToReceive);
                
                $count++;
            }
            
            DB::commit();

            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => "Se han recibido {$count} productos correctamente."
            ]);
            
            // Cleanup
            $this->selected = [];
            $this->selectAll = false;
            $this->loadItems();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error in bulk receive: " . $e->getMessage());
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Error al procesar: ' . $e->getMessage()
            ]);
        }
    }

    public function mount()
    {
        $this->ensureTenantConnection();

        $user = Auth::user();
        $this->companyId = $this->getUserCompanyId($user);

        if (!$this->companyId) {
            session()->flash('error', 'No se pudo determinar la empresa del usuario.');
            return redirect()->route('tenant.dashboard');
        }

        $this->loadItems();
    }

    public function updatedSearch()
    {
        $this->loadItems();
    }
    
  public function loadItems()
{
    if (!$this->companyId) {
        $this->items = collect([]);
        return;
    }

    $query = DB::table('tat_restock_list as r')
        ->leftJoin('tat_categories as tc', function ($join) {
            $join->on('r.company_id', '=', 'tc.company_id');
        })
        ->leftJoin('inv_items as ivi', 'ivi.id', '=', 'r.itemId')
        ->leftJoin('inv_values as inv', function ($join) {
            $join->on('inv.itemId', '=', 'ivi.id')
                 ->where('inv.label', 'Precio Regular');
        })
        ->leftJoin('tat_items as i', function ($join) {
            $join->on('r.itemId', '=', 'i.item_father_id')
                 ->on('r.company_id', '=', 'i.company_id');
        })
        ->leftJoin('inv_remissions as rem', 'rem.quoteId', '=', 'r.order_number')
        ->where('r.company_id', $this->companyId)
        ->where('tc.company_id', $this->companyId)
        ->whereIn('r.status', ['Confirmado', 'Recibido'])
        ->select(
            'r.id',
            'r.itemId',
            'r.quantity_request',
            'r.quantity_recive',
            'r.status',
            'r.created_at',
            'r.order_number',
            'i.name as item_name',
            'i.sku',
            'i.stock',
            'rem.consecutive as remise_number',
            'rem.status as rem_status',
            'ivi.name as it_name_dis',
            'ivi.sku as it_sku_dis',
            'ivi.taxId',
            DB::raw('MAX(inv.values) as price'), // 🔑 evita duplicados
            'inv.label'
        )
        ->groupBy(
            'r.id',
            'r.itemId',
            'r.quantity_request',
            'r.quantity_recive',
            'r.status',
            'r.created_at',
            'r.order_number',
            'i.name',
            'i.sku',
            'i.stock',
            'rem.consecutive',
            'rem.status',
            'ivi.name',
            'ivi.sku',
            'ivi.taxId',
            'inv.label'
        );

    // 🔹 Filtro por orden
    if ($this->order_number) {
        $query->where('r.order_number', $this->order_number);
    } else {
        // 🔹 Si NO hay order_number específico, solo mostrar items con remisión EN RECORRIDO
        $query->where('rem.status', 'EN RECORRIDO');
    }

    // 🔹 Búsqueda
    if ($this->search) {
        $query->where(function ($q) {
            $q->where('rem.consecutive', 'like', '%' . $this->search . '%')
              ->orWhere('r.order_number', 'like', '%' . $this->search . '%')
              ->orWhere('i.name', 'like', '%' . $this->search . '%')
              ->orWhere('i.sku', 'like', '%' . $this->search . '%');
        });
    }

    // 🔹 Ejecutar consulta SOLO UNA VEZ
    $this->items = $query
        ->orderBy('r.created_at', 'desc')
        ->get();

    // 🔹 Inicializar cantidades
    foreach ($this->items as $item) {
        if (!isset($this->quantities[$item->id])) {
            $this->quantities[$item->id] = $item->quantity_request;
        }
    }
}

    // Called when a quantity input changes
    public function updateQuantity($id, $value)
    {
        $this->quantities[$id] = intval($value);
        // Optional: Save draft state here if desired
    }
    
    // Confirmar un solo ítem
    public function confirmItem($id)
    {
        // Encontrar el item en la colección actual o verificar que existe y es válido
        // Usamos first() sobre la colección cargada para evitar re-query si es posible, 
        // pero para seguridad de estado, verificamos en DB o usamos la colección.
        // Dado que $items es una colección de objetos stdClass (query builder get()), buscamos por id.
        $item = $this->items->firstWhere('id', $id);

        if (!$item) {
             $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Ítem no encontrado o ya procesado.'
            ]);
            return;
        }

        $qtyToReceive = isset($this->quantities[$id]) ? intval($this->quantities[$id]) : $item->quantity_request;

        if ($qtyToReceive < 0) {
             $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'La cantidad no puede ser negativa.'
            ]);
            return;
        }

        DB::beginTransaction();
        try {
            // 1. Update tat_restock_list status and quantity
            DB::table('tat_restock_list')
                ->where('id', $id)
                ->update([
                    'quantity_recive' => $qtyToReceive,
                    'status' => 'Recibido',
                    'updated_at' => now()
                ]);

            // 2. Update stock in tat_items
            DB::table('tat_items')
                ->where('item_father_id', (int)$item->itemId)
                ->where('company_id', $this->companyId)
                ->increment('stock', $qtyToReceive);
            
            DB::commit();

            $this->dispatch('show-toast', [
                'type' => 'success',
                'message' => "Producto recibido correctamente."
            ]);
            
            // Recargar items para que el procesado desaparezca de la lista
            $this->loadItems();

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error en recepción individual: " . $e->getMessage());
            $this->dispatch('show-toast', [
                'type' => 'error',
                'message' => 'Error al procesar: ' . $e->getMessage()
            ]);
        }
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
