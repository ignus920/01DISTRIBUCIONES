<?php

namespace App\Livewire\Tenant\Customers;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Tenant\Customer;

class CustomerForm extends Component
{
    use WithPagination;

    public $search = '';
    public $showModal = false;
    public $editingId = null;

    // Propiedades del formulario
    public $name = '';
    public $description = '';

    protected $rules = [
        'name' => 'required|string|max:255',
        'description' => 'nullable|string|max:500',
    ];

    public function render()
    {
        $items = Customer::where('name', 'like', '%' . $this->search . '%')
            ->latest()
            ->paginate(10);

        return view('livewire.tenant.customers.customer-form', [
            'items' => $items
        ]);
    }

    public function create()
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit($id)
    {
        $item = Customer::findOrFail($id);
        $this->editingId = $id;
        $this->name = $item->name;
        $this->description = $item->description;
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        if ($this->editingId) {
            $item = Customer::findOrFail($this->editingId);
            $item->update([
                'name' => $this->name,
                'description' => $this->description,
            ]);

            session()->flash('message', 'Registro actualizado exitosamente.');
        } else {
            Customer::create([
                'name' => $this->name,
                'description' => $this->description,
            ]);

            session()->flash('message', 'Registro creado exitosamente.');
        }

        $this->resetForm();
        $this->showModal = false;
    }

    public function delete($id)
    {
        Customer::findOrFail($id)->delete();
        session()->flash('message', 'Registro eliminado exitosamente.');
    }

    private function resetForm()
    {
        $this->editingId = null;
        $this->name = '';
        $this->description = '';
        $this->resetErrorBag();
    }
}
