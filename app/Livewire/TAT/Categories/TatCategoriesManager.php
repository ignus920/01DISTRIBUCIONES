<?php

namespace App\Livewire\TAT\Categories;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\TAT\Categories\TatCategories;
use App\Models\Auth\Tenant;

class TatCategoriesManager extends Component
{
    use WithPagination;

    public $showModal = false;
    public $editingId = null;
    public $search = '';
    public $company_id;
    public $perPage = 10;
    public $sortField = 'created_at';
    public $sortDirection = 'desc';

    // Form fields
    public $name = '';
    public $status = true;

    protected $rules = [
        'name' => 'required|string|max:255',
        'status' => 'required|boolean',
    ];

    protected $messages = [
        'name.required' => 'El nombre es obligatorio.',
        'name.max' => 'El nombre no puede tener más de 255 caracteres.',
        'status.required' => 'El estado es obligatorio.',
    ];

    public function mount()
    {
        $tenantId = session('tenant_id');
        $tenant = Tenant::find($tenantId);
        $this->company_id = $tenant->company_id ?? 0;
    }

    public function render()
    {
        $categories = TatCategories::where('company_id', $this->company_id)
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%');
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.TAT.Categories.tat-categories-manager', compact('categories'))->layout('layouts.app'); // 👈 aquí agregas el layout
    }

    public function create()
    {
        $this->resetForm();
        $this->editingId = null;
        $this->showModal = true;
    }

    public function edit($id)
    {
        $category = TatCategories::findOrFail($id);

        $this->editingId = $id;
        $this->name = $category->name;
        $this->status = $category->status;

        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        $data = [
            'company_id' => $this->company_id,
            'name' => $this->name,
            'status' => $this->status,
        ];

        if ($this->editingId) {
            TatCategories::findOrFail($this->editingId)->update($data);
            session()->flash('message', 'Categoría actualizada correctamente.');
        } else {
            TatCategories::create($data);
            session()->flash('message', 'Categoría creada correctamente.');
        }

        $this->resetForm();
        $this->showModal = false;
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

    public function toggleStatus($id)
    {
        $category = TatCategories::findOrFail($id);
        $category->update(['status' => !$category->status]);
        session()->flash('message', 'Estado de la categoría actualizado correctamente.');
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    private function resetForm()
    {
        $this->name = '';
        $this->status = true;
        $this->resetValidation();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingPerPage()
    {
        $this->resetPage();
    }
}