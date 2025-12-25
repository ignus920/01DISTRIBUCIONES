<?php

namespace App\Livewire\TAT\Items;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;
use App\Models\TAT\Items\TatItems;
use App\Models\TAT\Categories\TatCategories;
use App\Models\Central\CnfTaxes;
use App\Models\Auth\Tenant;
use App\Services\Tenant\TenantManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TatItemsManager extends Component
{
    use WithPagination, WithFileUploads;

    // Propiedades del formulario
    public $item_id = null;
    public $item_father_id = 0;
    public $company_id;
    public $sku = '';
    public $name = '';
    public $taxId = null;
    public $categoryId = null;
    public $stock = 0;
    public $cost = 0;
    public $price = 0;
    public $status = 1;
    public $img_path = '';
    public $image = null; // Para la nueva imagen
    public $existingImage = null; // Para mostrar la imagen actual

    // Propiedades de la tabla
    public $search = '';
    public $sortField = 'name';
    public $sortDirection = 'asc';
    public $perPage = 10;

    // Estados del modal
    public $showModal = false;
    public $editMode = false;

    // Mensajes
    public $successMessage = '';
    public $errorMessage = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'perPage' => ['except' => 10],
    ];

    protected function rules(): array
    {
        return [
            'sku' => 'required|string|max:100|unique:tat_items,sku,' . $this->item_id,
            'name' => 'required|string|max:255',
            'taxId' => 'nullable|exists:cnf_taxes,id',
            'categoryId' => 'required|exists:tat_categories,id',
            'stock' => 'required|numeric|min:0',
            'cost' => 'required|numeric|min:0',
            'price' => 'required|numeric|min:0',
            'status' => 'required|in:0,1',
            'image' => 'nullable|image|max:2048', // Max 2MB
        ];
    }

    protected function messages(): array
    {
        return [
            'sku.required' => 'El SKU es obligatorio',
            'sku.unique' => 'Este SKU ya existe',
            'name.required' => 'El nombre es obligatorio',
            'categoryId.required' => 'La categoría es obligatoria',
            'stock.required' => 'El stock es obligatorio',
            'stock.numeric' => 'El stock debe ser un número',
            'stock.min' => 'El stock no puede ser negativo',
            'cost.required' => 'El costo es obligatorio',
            'cost.numeric' => 'El costo debe ser un número',
            'cost.min' => 'El costo no puede ser negativo',
            'price.required' => 'El precio es obligatorio',
            'price.numeric' => 'El precio debe ser un número',
            'price.min' => 'El precio no puede ser negativo',
            'image.image' => 'El archivo debe ser una imagen',
            'image.max' => 'La imagen no puede ser mayor a 2MB',
        ];
    }

    public function mount()
    {
        $this->ensureTenantConnection();
        $this->loadCompanyId();
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

    private function loadCompanyId()
    {
        $tenantId = session('tenant_id');
        $tenant = Tenant::find($tenantId);
        $this->company_id = $tenant->company_id ?? 0;
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingPerPage()
    {
        $this->resetPage();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortDirection = 'asc';
        }

        $this->sortField = $field;
        $this->resetPage();
    }

    public function create()
    {
        $this->resetForm();
        $this->editMode = false;
        $this->showModal = true;
    }

    public function edit($itemId)
    {
        $this->ensureTenantConnection();

        $item = TatItems::findOrFail($itemId);

        $this->item_id = $item->id;
        $this->item_father_id = $item->item_father_id;
        $this->sku = $item->sku;
        $this->name = $item->name;
        $this->taxId = $item->taxId;
        $this->categoryId = $item->categoryId;
        $this->stock = $item->stock;
        $this->cost = $item->cost;
        $this->price = $item->price;
        $this->status = $item->status;
        $this->img_path = $item->img_path;
        $this->existingImage = $item->img_path;

        $this->editMode = true;
        $this->showModal = true;
    }

    public function save()
    {
        try {
            $this->errorMessage = '';
            $this->successMessage = '';

            $this->validate();

            // Manejar la carga de imagen
            $imagePath = $this->img_path ?: ''; // Asegurar que siempre tenga un valor
            if ($this->image) {
                // Eliminar imagen anterior si existe
                if ($this->editMode && $this->img_path && \Storage::disk('public')->exists($this->img_path)) {
                    \Storage::disk('public')->delete($this->img_path);
                }
                
                // Guardar nueva imagen
                $imagePath = $this->image->store('items', 'public');
            } elseif (!$this->editMode) {
                // Si estamos creando un nuevo item sin imagen, usar cadena vacía
                $imagePath = '';
            }

            $itemData = [
                'item_father_id' => 0,
                'company_id' => $this->company_id,
                'sku' => $this->sku,
                'name' => $this->name,
                'taxId' => $this->taxId,
                'categoryId' => $this->categoryId,
                'stock' => $this->stock,
                'cost' => $this->cost,
                'price' => $this->price,
                'status' => $this->status,
                'img_path' => $imagePath,
            ];

            if ($this->editMode && $this->item_id) {
                // Actualizar
                $item = TatItems::findOrFail($this->item_id);
                $item->update($itemData);
                $this->successMessage = 'Item actualizado exitosamente';
            } else {
                // Crear
                TatItems::create($itemData);
                $this->successMessage = 'Item creado exitosamente';
            }

            $this->closeModal();

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Los errores de validación se manejan automáticamente
            Log::info('Validation error in save method', [
                'errors' => $e->errors(),
            ]);
        } catch (\Exception $e) {
            $this->errorMessage = 'Error al guardar el item: ' . $e->getMessage();
            Log::error('Item save failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    public function deleteImage()
    {
        try {
            if ($this->editMode && $this->item_id) {
                $item = TatItems::findOrFail($this->item_id);
                
                // Eliminar archivo físico
                if ($item->img_path && \Storage::disk('public')->exists($item->img_path)) {
                    \Storage::disk('public')->delete($item->img_path);
                }
                
                // Actualizar base de datos
                $item->update(['img_path' => '']);
                
                // Limpiar propiedades
                $this->img_path = '';
                $this->existingImage = null;
                $this->image = null;
                
                $this->successMessage = 'Imagen eliminada exitosamente';
            } else {
                // Solo limpiar la imagen temporal si estamos creando
                $this->image = null;
                $this->existingImage = null;
            }
        } catch (\Exception $e) {
            $this->errorMessage = 'Error al eliminar la imagen: ' . $e->getMessage();
            Log::error('Image deletion failed', [
                'item_id' => $this->item_id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function toggleStatus($itemId)
    {
        try {
            $this->ensureTenantConnection();

            $item = TatItems::findOrFail($itemId);
            $newStatus = $item->status ? 0 : 1;

            $item->update(['status' => $newStatus]);

            $this->successMessage = 'Estado actualizado correctamente';
            $this->errorMessage = '';

        } catch (\Exception $e) {
            $this->errorMessage = 'Error al actualizar el estado: ' . $e->getMessage();
            Log::error('Toggle status failed', [
                'item_id' => $itemId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function delete($itemId)
    {
        try {
            $this->ensureTenantConnection();

            $item = TatItems::findOrFail($itemId);
            $item->delete(); // Eliminación permanente

            $this->successMessage = 'Item eliminado exitosamente';
            $this->errorMessage = '';

        } catch (\Exception $e) {
            $this->errorMessage = 'Error al eliminar el item: ' . $e->getMessage();
            Log::error('Item deletion failed', [
                'item_id' => $itemId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
        $this->resetErrorBag();
        $this->resetValidation();
    }

    private function resetForm()
    {
        $this->item_id = null;
        $this->item_father_id = null;
        $this->sku = '';
        $this->name = '';
        $this->taxId = null;
        $this->categoryId = null;
        $this->stock = 0;
        $this->cost = 0;
        $this->price = 0;
        $this->status = 1;
        $this->img_path = '';
        $this->image = null;
        $this->existingImage = null;
        $this->editMode = false;
    }

    public function getItemsProperty()
    {
        $this->ensureTenantConnection();

        return TatItems::query()
            ->with(['category', 'tax'])
            ->where('company_id', $this->company_id)
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('sku', 'like', '%' . $this->search . '%');
                });
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);
    }

    public function getCategoriesProperty()
    {
        $this->ensureTenantConnection();
        return TatCategories::where('company_id', $this->company_id)
                           ->where('status', 1)
                           ->orderBy('name')
                           ->get();
    }

    public function getTaxesProperty()
    {
        $this->ensureTenantConnection();
        return CnfTaxes::all();
    }

    public function getProductInitials($productName)
    {
        $words = explode(' ', trim($productName));
        if (count($words) >= 2) {
            return strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
        }
        return strtoupper(substr($productName, 0, 2));
    }

    public function getAvatarColorClass($productName)
    {
        $colors = [
            'bg-red-500', 'bg-blue-500', 'bg-green-500', 'bg-yellow-500', 'bg-purple-500',
            'bg-pink-500', 'bg-indigo-500', 'bg-orange-500', 'bg-teal-500', 'bg-cyan-500'
        ];

        $hash = crc32($productName);
        return $colors[abs($hash) % count($colors)];
    }

    public function render()
    {
        return view('livewire.TAT.Items.tat-items-manager', [
            'items' => $this->items,
            'categories' => $this->categories,
            'taxes' => $this->taxes,
        ])->layout('layouts.app'); // 👈 aquí agregas el layout
    }
}