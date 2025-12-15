<?php

namespace App\Livewire\Tenant\Uploads\Components;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Tenant\DeliveriesList\DisDeliveries;

class PrintUploadsCharges extends Component
{
    use WithPagination;

    public $perPage = 10;

    /**
     * Resetea la paginación cuando cambia el número de items por página
     */
    public function updatedPerPage()
    {
        $this->resetPage();
    }

    public function render()
    {
        $deliveries = DisDeliveries::query()
            ->select('id', 'sale_date', 'user_id', 'salesman_id')
            ->orderBy('sale_date', 'desc')
            ->orderBy('id', 'desc')
            ->paginate($this->perPage);

        return view('livewire.tenant.uploads.components.print-uploads-charges', [
            'deliveries' => $deliveries
        ]);
    }
}