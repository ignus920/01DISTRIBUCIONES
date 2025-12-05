<?php

namespace App\Livewire\Tenant\Customers;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Tenant\Customer;
use App\Services\Tenant\TenantManager;
use App\Models\Auth\Tenant;

class CustomerManager extends Component
{
    use WithPagination;

    public $search = '';
    public $showModal = false;
    public $editingId = null;
    public $perPage = 10;
    public $sortField = 'id';
    public $sortDirection = 'desc';

    // Propiedades del formulario
    public $name = '';
    public $email = '';
    public $phone = '';
    public $address = '';
    public $country_id = '';
    public $state_id = '';
    public $city_id = '';
    public $tax_id = '';
    public $type = 'individual';
    public $active = true;

    protected $rules = [
        'name' => 'required|string|max:200',
        'email' => 'nullable|email|max:255',
        'phone' => 'nullable|string|max:20',
        'address' => 'nullable|string',
        'country_id' => 'nullable|integer',
        'state_id' => 'nullable|integer',
        'city_id' => 'nullable|integer',
        'tax_id' => 'nullable|string|max:50',
        'type' => 'required|in:individual,business',
        'active' => 'boolean',
    ];

    protected $queryString = [
        'search' => ['except' => ''],
        'perPage' => ['except' => 10],
    ];

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

    public function mount()
    {
        $this->ensureTenantConnection();
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

    public function render()
    {
        $this->ensureTenantConnection();

        $items = Customer::query()
            ->when($this->search, function ($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('email', 'like', '%' . $this->search . '%')
                      ->orWhere('phone', 'like', '%' . $this->search . '%')
                      ->orWhere('tax_id', 'like', '%' . $this->search . '%');
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);

        return view('livewire.tenant.customers.customer-manager', [
            'items' => $items
        ])->layout('layouts.app');
    }

    public function create()
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function edit($id)
    {
        $this->ensureTenantConnection();

        $item = Customer::findOrFail($id);
        $this->editingId = $id;
        $this->name = $item->name;
        $this->email = $item->email;
        $this->phone = $item->phone;
        $this->address = $item->address;
        $this->country_id = $item->country_id;
        $this->state_id = $item->state_id;
        $this->city_id = $item->city_id;
        $this->tax_id = $item->tax_id;
        $this->type = $item->type;
        $this->active = $item->active;
        $this->showModal = true;
    }

    public function save()
    {
        $this->ensureTenantConnection();
        $this->validate();

        $data = [
            'name' => $this->name,
            'email' => $this->email ?: null,
            'phone' => $this->phone ?: null,
            'address' => $this->address ?: null,
            'country_id' => $this->country_id ?: null,
            'state_id' => $this->state_id ?: null,
            'city_id' => $this->city_id ?: null,
            'tax_id' => $this->tax_id ?: null,
            'type' => $this->type,
            'active' => $this->active,
        ];

        if ($this->editingId) {
            $item = Customer::findOrFail($this->editingId);
            $item->update($data);

            session()->flash('message', 'Cliente actualizado exitosamente.');
        } else {
            Customer::create($data);

            session()->flash('message', 'Cliente creado exitosamente.');
        }

        $this->resetForm();
        $this->showModal = false;
    }

    public function delete($id)
    {
        $this->ensureTenantConnection();

        Customer::findOrFail($id)->delete();
        session()->flash('message', 'Cliente eliminado exitosamente.');
    }

    public function exportExcel()
    {
        // TODO: Implementar exportación a Excel
        $this->dispatch('show-toast', [
            'type' => 'info',
            'message' => 'Exportación a Excel - En desarrollo'
        ]);
    }

    public function exportPdf()
    {
        // TODO: Implementar exportación a PDF
        $this->dispatch('show-toast', [
            'type' => 'info',
            'message' => 'Exportación a PDF - En desarrollo'
        ]);
    }

    public function exportCsv()
    {
        // TODO: Implementar exportación a CSV
        $this->dispatch('show-toast', [
            'type' => 'info',
            'message' => 'Exportación a CSV - En desarrollo'
        ]);
    }

    private function resetForm()
    {
        $this->editingId = null;
        $this->name = '';
        $this->email = '';
        $this->phone = '';
        $this->address = '';
        $this->country_id = '';
        $this->state_id = '';
        $this->city_id = '';
        $this->tax_id = '';
        $this->type = 'individual';
        $this->active = true;
        $this->resetErrorBag();
    }
}
