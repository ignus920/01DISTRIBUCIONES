<?php

namespace App\Livewire\Tenant\Inventory;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Tenant\Items\Command as CommandModel;
use App\Services\Tenant\TenantManager;
use App\Models\Auth\Tenant;
use Carbon\Carbon;


class Command extends Component
{
    use WithPagination;

    public $command_id, $name, $print_path, $status, $created_at;

    //Propiedades para la tabla
    public $search = '';
    public $sortField = 'name';
    public $sortDirection = 'asc';
    public $showModal = false;
    public $confirmingCommandDeletion = false;
    public $commandIdToDelete;
    public $perPage = 10;

    protected $rules =[
        'name' => 'required|min:3',
        'print_path' => 'required|min:3',
    ];

    public function resetForm()
    {
        $this->name = '';
        $this->print_path = '';
        $this->status = '';
        $this->created_at = null;
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

    public function create()
    {
        $this->resetExcept(['commands', 'types']);
        $this->showModal = true;
    }

    public function edit($id)
    {
        $this->ensureTenantConnection();
        $command = CommandModel::findOrFail($id);
        $this->name = $command->name;
        $this->print_path = $command->print_path;
        $this->command_id=$command->id;
        $this->showModal = true;
    }

    public function cancel()
    {
        $this->resetValidation();
        $this->reset([
            'name',
            'print_path'
        ]);
        $this->showModal = false;
        $this->confirmingCommandDeletion = false;
    }

    public function save()
    {
        $this->ensureTenantConnection();
        $this->validate();

        $commandData = [
            'name' => $this->name,
            'print_path' => $this->print_path,
        ];

        if($this->command_id){
            $command=CommandModel::findOrFail($this->command_id);
            $command->update($commandData);
            session()->flash('message', 'Comanda actualizada correctamente.');
        }else{
            CommandModel::create($commandData);
            session()->flash('message', 'Comanda creada correctamente.');
        }

        $this->resetValidation();
        $this->reset([
            'name',
            'print_path',
        ]);
        $this->showModal = false;
    }

    public function toggleCommandStatus($id)
    {
        $this->ensureTenantConnection();
        $item=CommandModel::findOrFail($id);

        $newStatus = $item->status ? 0 : 1;
        $item->update([
            'status'=>$newStatus, 
        ]);
        
        session()->flash('message', 'Estado actualizado correctamente');
    }

    public function confirmCommandDeletion($id)
    {
        $this->confirmingCommandDeletion = true;
        $this->commandIdToDelete = $id;
    }

    public function deleteCommand()
    {
        $this->ensureTenantConnection();

        $commandData=[
            'status'=>0,
            'deleted_at'=>Carbon::now(),
        ];

        $command=CommandModel::findOrFail($this->commandIdToDelete);
        //$category->delete();
        $command->update($commandData);
        $this->confirmingCommandDeletion = false;
        $this->reset(['commandIdToDelete']);
        session()->flash('message','Comanda eliminada correctamente');
    }

    public function render()
    {
        $this->ensureTenantConnection();
        $commands=CommandModel::query()
            //->where('status', 1)
            ->when($this->search, function($query){
                $query->where('name', 'like', '%' . $this->search . '%')
                    ->orWhere('print_path', 'like', '%' . $this->search . '%');
            })
            ->orderBy($this->sortField, $this->sortDirection)
            ->paginate($this->perPage);
        return view('livewire.tenant.inventory.command',[
            'commands' => $commands
        ]);
    }
}
