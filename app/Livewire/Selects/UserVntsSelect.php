<?php

namespace App\Livewire\Selects;

use Livewire\Component;
use App\Models\Auth\User;

class UserVntsSelect extends Component
{
    public $userId = '';
    public $name = 'userId';
    public $placeholder = 'Seleccionar usuario';
    public $label = 'Usuario';
    public $required = true;
    public $showLabel = true;
    public $class = 'mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500';

    public function mount($userId = '', $name = 'userId', $placeholder = 'Seleccionar usuario', $label = 'Usuario', $required = true, $showLabel = true, $class = null)
    {
        $this->userId = $userId;
        $this->name = $name;
        $this->placeholder = $placeholder;
        $this->label = $label;
        $this->required = $required;
        $this->showLabel = $showLabel;
        if ($class) {
            $this->class = $class;
        }
    }

    public function updatedUserId($value)
    {
        $this->dispatch('user-changed', userId: $value);
    }

    public function getUsersProperty()
    {

         $sessionTenant = $this->getTenantId();

        return User::query()
            ->whereHas('tenants', function ($query) use ($sessionTenant) {
                $query->where('tenants.id', $sessionTenant);
            })
            ->with(['profile', 'contact.warehouse.company'])
            ->when(function ($query) {
                $query->where(function ($q) {
                     $q->where('profile_id', 4);
                });
            })
            ->orderBy('name')->get(['id', 'name', 'email']);
    }

    public function render()
    {
        return view('livewire.selects.user-vnts-select', [
            'users' => $this->users,
        ]);
    }

     private function getTenantId()
    {
        $tenantId = session('tenant_id');

        if (!$tenantId) {
            throw new \Exception('No tenant selected');
        }
        return $tenantId;
    }
}
