<?php

namespace App\Livewire\Tenant\VntCompany;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Tenant\Customer\TatCompanyRoute;

class MoveDistrictModal extends Component
{
    use WithPagination;

    public $showModal = false;
    public $search = '';
    public $perPage = 10;
    
    // Form properties
    public $district = '';
    public $sourceRouteId = '';
    public $targetRouteId = '';
    public $selectedCompanies = [];
    public $selectAll = false;
    public $availableDistricts = [];

    protected $listeners = [
        'source-route-changed' => 'updateSourceRoute',
        'target-route-changed' => 'updateTargetRoute',
        'district-changed' => 'updateDistrict'
    ];

    protected function rules()
    {
        return [
            'district' => 'required|string',
            'targetRouteId' => 'required|exists:tat_routes,id',
        ];
    }

    protected function messages()
    {
        return [
            'district.required' => 'Debe seleccionar un barrio.',
            'targetRouteId.required' => 'Debe seleccionar una ruta de destino.',
            'targetRouteId.exists' => 'La ruta de destino seleccionada no existe.',
        ];
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingPerPage()
    {
        $this->resetPage();
    }

    public function updatedSelectAll($value)
    {
        if ($value) {
            $this->selectedCompanies = $this->getFilteredCompanies()->pluck('id')->toArray();
        } else {
            $this->selectedCompanies = [];
        }
    }

    public function getFilteredCompanies()
    {
        if (empty($this->district)) {
            return collect([]);
        }

        $query = TatCompanyRoute::query()
            ->with(['company.mainWarehouse'])
            ->whereHas('company.mainWarehouse', function ($query) {
                $query->where('district', $this->district);
            });

        // NO filtrar por sourceRouteId aquí tampoco
        // Este método se usa para el selectAll

        $query->when($this->search, function ($query) {
            $query->whereHas('company', function ($q) {
                $q->where('businessName', 'like', '%' . $this->search . '%')
                  ->orWhere('firstName', 'like', '%' . $this->search . '%')
                  ->orWhere('lastName', 'like', '%' . $this->search . '%')
                  ->orWhere('identification', 'like', '%' . $this->search . '%');
            });
        });

        return $query->get();
    }

    public function getItemsProperty()
    {
        try {
            // Si no hay distrito seleccionado, no mostrar nada
            if (empty($this->district)) {
                return new \Illuminate\Pagination\LengthAwarePaginator([], 0, $this->perPage);
            }

            // Mostrar todos los clientes del barrio seleccionado
            $query = TatCompanyRoute::query()
                ->with(['company.mainWarehouse', 'route.salesman'])
                ->whereHas('company.mainWarehouse', function ($query) {
                    $query->where('district', $this->district);
                });

            // NO filtrar por sourceRouteId - la tabla siempre muestra todos los clientes del distrito
            // El sourceRouteId solo se usa para el botón de intercambio

            // Aplicar búsqueda si existe
            $query->when($this->search, function ($query) {
                $query->whereHas('company', function ($q) {
                    $q->where('businessName', 'like', '%' . $this->search . '%')
                      ->orWhere('firstName', 'like', '%' . $this->search . '%')
                      ->orWhere('lastName', 'like', '%' . $this->search . '%')
                      ->orWhere('identification', 'like', '%' . $this->search . '%');
                });
            });

            return $query->orderBy('route_id')->orderBy('sales_order')->paginate($this->perPage);
        } catch (\Exception $e) {
            Log::error('Error getting companies by district: ' . $e->getMessage());
            return new \Illuminate\Pagination\LengthAwarePaginator([], 0, $this->perPage);
        }
    }

    public function mount()
    {
        $this->loadAvailableDistricts();
    }

    public function loadAvailableDistricts()
    {
        try {
            $this->availableDistricts = DB::table('vnt_warehouses')
                ->select('district')
                ->whereNotNull('district')
                ->where('district', '!=', '')
                ->distinct()
                ->orderBy('district')
                ->pluck('district')
                ->toArray();
        } catch (\Exception $e) {
            Log::error('Error loading districts: ' . $e->getMessage());
            $this->availableDistricts = [];
        }
    }

    public function render()
    {
        return view('livewire.tenant.vnt-company.components.move-district-modal', [
            'items' => $this->items,
        ]);
    }

    public function openModal()
    {
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
        $this->dispatch('move-district-modal-closed');
    }

    public function updatedDistrict()
    {
        // Reset selections when district changes
        $this->selectedCompanies = [];
        $this->selectAll = false;
        $this->sourceRouteId = '';
        $this->targetRouteId = '';
        $this->resetPage();
    }

    public function swapRoutes()
    {
        $this->validate([
            'district' => 'required|string',
            'sourceRouteId' => 'required|exists:tat_routes,id',
            'targetRouteId' => 'required|exists:tat_routes,id',
        ]);

        if ($this->sourceRouteId == $this->targetRouteId) {
            session()->flash('error', 'Las rutas de origen y destino deben ser diferentes.');
            return;
        }

        try {
            DB::beginTransaction();

            // Obtener todos los clientes de la ruta origen en el distrito
            $sourceCompanies = TatCompanyRoute::query()
                ->whereHas('company.mainWarehouse', function ($query) {
                    $query->where('district', $this->district);
                })
                ->where('route_id', $this->sourceRouteId)
                ->get();

            // Obtener todos los clientes de la ruta destino en el distrito
            $targetCompanies = TatCompanyRoute::query()
                ->whereHas('company.mainWarehouse', function ($query) {
                    $query->where('district', $this->district);
                })
                ->where('route_id', $this->targetRouteId)
                ->get();

            // Intercambiar: Mover clientes de origen a destino
            foreach ($sourceCompanies as $company) {
                $company->update(['route_id' => $this->targetRouteId]);
            }

            // Intercambiar: Mover clientes de destino a origen
            foreach ($targetCompanies as $company) {
                $company->update(['route_id' => $this->sourceRouteId]);
            }

            DB::commit();

            $totalSwapped = $sourceCompanies->count() + $targetCompanies->count();
            session()->flash('message', "Se intercambiaron {$totalSwapped} cliente(s) entre las rutas exitosamente.");
            
            $this->sourceRouteId = '';
            $this->targetRouteId = '';
            $this->selectedCompanies = [];
            $this->selectAll = false;
            $this->resetPage();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error swapping routes: ' . $e->getMessage());
            session()->flash('error', 'Error al intercambiar rutas: ' . $e->getMessage());
        }
    }

    public function moveCompanies()
    {
        $this->validate([
            'district' => 'required|string',
            'targetRouteId' => 'required|exists:tat_routes,id',
        ]);

        if (empty($this->selectedCompanies)) {
            session()->flash('error', 'Debe seleccionar al menos un cliente para mover.');
            return;
        }

        try {
            DB::beginTransaction();

            $movedCount = 0;
            foreach ($this->selectedCompanies as $companyRouteId) {
                $companyRoute = TatCompanyRoute::find($companyRouteId);
                
                if ($companyRoute) {
                    // Obtener el último sales_order de la ruta destino
                    $lastOrder = TatCompanyRoute::where('route_id', $this->targetRouteId)
                        ->orderBy('sales_order', 'desc')
                        ->first();
                    
                    $newSalesOrder = $lastOrder ? ($lastOrder->sales_order + 1) : 1;
                    
                    $companyRoute->update([
                        'route_id' => $this->targetRouteId,
                        'sales_order' => $newSalesOrder,
                    ]);
                    
                    $movedCount++;
                }
            }

            DB::commit();

            session()->flash('message', "Se movieron {$movedCount} cliente(s) exitosamente.");
            $this->selectedCompanies = [];
            $this->selectAll = false;
            $this->targetRouteId = '';
            $this->resetPage();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error moving companies: ' . $e->getMessage());
            session()->flash('error', 'Error al mover clientes: ' . $e->getMessage());
        }
    }

    public function updateSourceRoute($routeId)
    {
        $this->sourceRouteId = $routeId;
        // NO resetear selecciones ni página
        // Solo actualizar el valor para que aparezca el botón de intercambio
    }

    public function updateTargetRoute($routeId)
    {
        $this->targetRouteId = $routeId;
        // Solo actualizar el valor para que aparezca el botón de intercambio
    }

    public function updateDistrict($value)
    {
        $this->district = $value;
        // El método updatedDistrict() se ejecutará automáticamente
    }

    private function resetForm()
    {
        $this->district = '';
        $this->sourceRouteId = '';
        $this->targetRouteId = '';
        $this->selectedCompanies = [];
        $this->selectAll = false;
        $this->resetErrorBag();
        $this->resetValidation();
    }
}
