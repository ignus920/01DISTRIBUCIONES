<?php

namespace App\Livewire\Tenant\VntCompany;

use Livewire\Component;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Tenant\Customer\TatCompanyRoute;

class CompanyRoutesModal extends Component
{

    public $showModal = false;
    public $filterRouteId = '';

    protected $listeners = [
        'filter-route-changed' => 'updateFilterRoute'
    ];



    public function getItemsProperty()
    {
        try {
            if (!$this->filterRouteId) {
                return collect([]);
            }

            return TatCompanyRoute::query()
                ->whereHas('company') // CRÍTICO: Evitar registros cuyos clientes fueron borrados
                ->with(['company.activeContacts', 'company.mainWarehouse', 'route.zones', 'route.salesman'])
                ->where('route_id', $this->filterRouteId)
                ->orderBy('sales_order', 'asc')
                ->get();
        } catch (\Exception $e) {
            Log::error('Error getting company routes: ' . $e->getMessage());
            return collect([]);
        }
    }

    public function render()
    {
        return view('livewire.tenant.vnt-company.components.company-routes-modal', [
            'items' => $this->items
        ]);
    }

    public function openModal()
    {
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->filterRouteId = '';
        $this->dispatch('routes-modal-closed');
    }

    public function updateFilterRoute($routeId)
    {
        $this->filterRouteId = $routeId;
    }

    public function clearMessages()
    {
        session()->forget('message');
        session()->forget('error');
    }

    public function moveUp($itemId)
    {
        try {
            DB::beginTransaction();
            
            $currentItem = TatCompanyRoute::findOrFail($itemId);
            
            // Buscar el item anterior (con sales_order menor)
            $previousItem = TatCompanyRoute::query()
                ->where('route_id', $this->filterRouteId)
                ->where('sales_order', '<', $currentItem->sales_order)
                ->orderBy('sales_order', 'desc')
                ->first();
            
            if ($previousItem) {
                // Intercambiar los sales_order
                $tempOrder = $currentItem->sales_order;
                $currentItem->sales_order = $previousItem->sales_order;
                $previousItem->sales_order = $tempOrder;
                
                $currentItem->save();
                $previousItem->save();
                
                session()->flash('message', 'Orden actualizado exitosamente.');
                $this->dispatch('order-updated');
            }
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error moving item up: ' . $e->getMessage());
            session()->flash('error', 'Error al reordenar: ' . $e->getMessage());
        }
    }

    public function moveDown($itemId)
    {
        try {
            DB::beginTransaction();
            
            $currentItem = TatCompanyRoute::findOrFail($itemId);
            
            // Buscar el item siguiente (con sales_order mayor)
            $nextItem = TatCompanyRoute::query()
                ->where('route_id', $this->filterRouteId)
                ->where('sales_order', '>', $currentItem->sales_order)
                ->orderBy('sales_order', 'asc')
                ->first();
            
            if ($nextItem) {
                // Intercambiar los sales_order
                $tempOrder = $currentItem->sales_order;
                $currentItem->sales_order = $nextItem->sales_order;
                $nextItem->sales_order = $tempOrder;
                
                $currentItem->save();
                $nextItem->save();
                
                session()->flash('message', 'Orden actualizado exitosamente.');
                $this->dispatch('order-updated');
            }
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error moving item down: ' . $e->getMessage());
            session()->flash('error', 'Error al reordenar: ' . $e->getMessage());
        }
    }

    public function updateOrder($itemId, $newOrder)
    {
        try {
            DB::beginTransaction();
            
            $newOrder = (int) $newOrder;
            $currentItem = TatCompanyRoute::findOrFail($itemId);
            $oldOrder = $currentItem->sales_order;
            
            // Validar que el nuevo orden esté en el rango válido
            $maxOrder = TatCompanyRoute::where('route_id', $this->filterRouteId)->count();
            if ($newOrder < 1 || $newOrder > $maxOrder) {
                session()->flash('error', 'El orden debe estar entre 1 y ' . $maxOrder);
                DB::rollBack();
                return;
            }
            
            // Si el orden no cambió, no hacer nada
            if ($oldOrder === $newOrder) {
                DB::commit();
                return;
            }
            
            // Obtener todos los items de la ruta ordenados
            $allItems = TatCompanyRoute::query()
                ->where('route_id', $this->filterRouteId)
                ->orderBy('sales_order', 'asc')
                ->get();
            
            // Si el item se mueve hacia arriba (newOrder < oldOrder)
            if ($newOrder < $oldOrder) {
                // Incrementar el orden de los items entre newOrder y oldOrder
                foreach ($allItems as $item) {
                    if ($item->id !== $itemId && $item->sales_order >= $newOrder && $item->sales_order < $oldOrder) {
                        $item->sales_order += 1;
                        $item->save();
                    }
                }
            } 
            // Si el item se mueve hacia abajo (newOrder > oldOrder)
            else {
                // Decrementar el orden de los items entre oldOrder y newOrder
                foreach ($allItems as $item) {
                    if ($item->id !== $itemId && $item->sales_order > $oldOrder && $item->sales_order <= $newOrder) {
                        $item->sales_order -= 1;
                        $item->save();
                    }
                }
            }
            
            // Actualizar el item actual con el nuevo orden
            $currentItem->sales_order = $newOrder;
            $currentItem->save();
            
            session()->flash('message', 'Orden actualizado exitosamente.');
            $this->dispatch('order-updated');
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error updating order: ' . $e->getMessage());
            session()->flash('error', 'Error al actualizar el orden: ' . $e->getMessage());
        }
    }

    public function printRoute()
    {
        try {
            if (!$this->filterRouteId) {
                return;
            }

            $route = \App\Models\TAT\Routes\TatRoutes::with(['salesman', 'zones'])->find($this->filterRouteId);
            if (!$route) {
                session()->flash('error', 'Ruta no encontrada');
                return;
            }

            $items = TatCompanyRoute::query()
                ->with([
                    'company.mainWarehouse', 
                    'company.activeContacts',
                    'route.zones'
                ])
                ->where('route_id', $this->filterRouteId)
                ->orderBy('sales_order', 'asc')
                ->get();

            if ($items->isEmpty()) {
                session()->flash('error', 'No hay clientes en esta ruta para imprimir');
                return;
            }

            // Datos para la vista de impresión
            $data = [
                'companyName' => '01 DISTRIBUCIONES',
                'companyNit' => '901.456.789-0', // Valor quemado por ahora, idealmente viene de config
                'routeName' => $route->name,
                'salesmanName' => $route->salesman->name ?? 'N/A',
                'saleDay' => ucfirst($route->sale_day ?? 'N/A'),
                'deliveryDay' => ucfirst($route->delivery_day ?? 'N/A'),
                'items' => $items
            ];

            $html = view('livewire.tenant.vnt-company.print.print-route', $data)->render();

            // Guardar temporalmente el HTML
            $tempFileName = 'route_' . $this->filterRouteId . '_' . time() . '.html';
            $tempPath = storage_path('app/temp/' . $tempFileName);

            if (!file_exists(dirname($tempPath))) {
                mkdir(dirname($tempPath), 0755, true);
            }

            file_put_contents($tempPath, $html);

            // Generar la URL del archivo
            $printUrl = route('quoter.print.temp', ['file' => $tempFileName]);

            // Despachar evento para abrir en nueva ventana
            $this->dispatch('open-print-window', ['url' => $printUrl]);

        } catch (\Exception $e) {
            Log::error('Error printing route: ' . $e->getMessage());
            session()->flash('error', 'Error al generar la impresión: ' . $e->getMessage());
        }
    }
}
