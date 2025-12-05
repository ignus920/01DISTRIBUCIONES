<?php

namespace App\Livewire\Central\SuperAdmin;

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use App\Models\Central\VntCompany;
use App\Models\Auth\User;
use App\Models\Central\UsrProfile;

#[Layout('layouts.app')]
class GlobalDashboard extends Component
{
    use WithPagination;

    public $activeTab = 'companies';
    public $search = '';
    public $selectedCompany = null;
    public $managingCompany = null;
    public $companyData = [];
    public $companyUsers = [];
    public $companyModules = [];

    // DataTable properties
    public $perPage = 10;
    public $sortField = 'created_at';
    public $sortDirection = 'desc';

    protected $queryString = [
        'search' => ['except' => ''],
        'perPage' => ['except' => 10],
        'activeTab' => ['except' => 'companies'],
    ];

    public function mount()
    {
        // Solo permitir a Super Administradores
        if (!auth()->user()->isSuperAdmin()) {
            abort(403, 'No tienes permisos para acceder a esta sección.');
        }
    }

    public function setActiveTab($tab)
    {
        $this->activeTab = $tab;
        $this->resetPage();
    }

    public function selectCompany($companyId)
    {
        $this->selectedCompany = $companyId;
    }

    public function clearSelection()
    {
        $this->selectedCompany = null;
    }

    public function manageCompany($companyId)
    {
        $this->managingCompany = $companyId;
        $this->loadCompanyData();
        $this->activeTab = 'manage_company';
    }

    public function loadCompanyData()
    {
        if (!$this->managingCompany) return;

        // Cargar datos de la empresa
        $company = VntCompany::find($this->managingCompany);
        $this->companyData = $company ? $company->toArray() : [];

        // Cargar usuarios de la empresa (mediante tenants)
        $this->companyUsers = User::with('profile')
            ->whereHas('tenants', function ($q) {
                $q->where('tenants.id', $this->managingCompany);
            })
            ->get();

        // TODO: Cargar módulos de la empresa (cuando tengamos la tabla de módulos)
        $this->companyModules = [];
    }

    public function closeCompanyManagement()
    {
        $this->managingCompany = null;
        $this->companyData = [];
        $this->companyUsers = [];
        $this->companyModules = [];
        $this->activeTab = 'companies';
    }

    public function saveCompanyData()
    {
        if (!$this->managingCompany) return;

        $company = VntCompany::find($this->managingCompany);
        if ($company) {
            $company->update($this->companyData);
            session()->flash('message', 'Datos de la empresa actualizados correctamente.');
        }
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedPerPage()
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

    public function exportExcelCompanies()
    {
        // TODO: Implementar exportación a Excel
        $this->dispatch('show-toast', [
            'type' => 'info',
            'message' => 'Exportación a Excel de empresas - En desarrollo'
        ]);
    }

    public function exportPdfCompanies()
    {
        // TODO: Implementar exportación a PDF
        $this->dispatch('show-toast', [
            'type' => 'info',
            'message' => 'Exportación a PDF de empresas - En desarrollo'
        ]);
    }

    public function exportCsvCompanies()
    {
        // TODO: Implementar exportación a CSV
        $this->dispatch('show-toast', [
            'type' => 'info',
            'message' => 'Exportación a CSV de empresas - En desarrollo'
        ]);
    }

    public function exportExcelUsers()
    {
        // TODO: Implementar exportación a Excel
        $this->dispatch('show-toast', [
            'type' => 'info',
            'message' => 'Exportación a Excel de usuarios - En desarrollo'
        ]);
    }

    public function exportPdfUsers()
    {
        // TODO: Implementar exportación a PDF
        $this->dispatch('show-toast', [
            'type' => 'info',
            'message' => 'Exportación a PDF de usuarios - En desarrollo'
        ]);
    }

    public function exportCsvUsers()
    {
        // TODO: Implementar exportación a CSV
        $this->dispatch('show-toast', [
            'type' => 'info',
            'message' => 'Exportación a CSV de usuarios - En desarrollo'
        ]);
    }

    public function render()
    {
        $data = [];

        switch ($this->activeTab) {
            case 'companies':
                $data['companies'] = VntCompany::query()
                    ->when($this->search, function ($query) {
                        $query->where(function ($q) {
                            $q->where('businessName', 'like', '%' . $this->search . '%')
                              ->orWhere('identification', 'like', '%' . $this->search . '%')
                              ->orWhere('billingEmail', 'like', '%' . $this->search . '%')
                              ->orWhere('firstName', 'like', '%' . $this->search . '%')
                              ->orWhere('lastName', 'like', '%' . $this->search . '%');
                        });
                    })
                    ->orderBy($this->sortField, $this->sortDirection)
                    ->paginate($this->perPage);
                break;

            case 'users':
                $data['users'] = User::with('profile')
                    ->when($this->search, function ($query) {
                        $query->where(function ($q) {
                            $q->where('name', 'like', '%' . $this->search . '%')
                              ->orWhere('email', 'like', '%' . $this->search . '%');
                        });
                    })
                    ->when($this->selectedCompany, function ($query) {
                        $query->whereHas('tenants', function ($q) {
                            $q->where('tenants.id', $this->selectedCompany);
                        });
                    })
                    ->orderBy($this->sortField, $this->sortDirection)
                    ->paginate($this->perPage);
                break;

            case 'stats':
                $data['stats'] = [
                    'total_companies' => VntCompany::count(),
                    'active_companies' => VntCompany::where('status', 1)->count(),
                    'total_users' => User::count(),
                    'super_admins' => User::superAdmins()->count(),
                    'regular_users' => User::nonSuperAdmins()->count(),
                ];
                break;
        }

        // Siempre incluir lista de empresas para el selector
        $data['companiesList'] = VntCompany::select('id', 'businessName')->get();

        return view('livewire.central.super-admin.global-dashboard', $data);
    }
}
