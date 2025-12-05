<?php

namespace App\Exports\Tenant\PettyCash;

use App\Models\Tenant\PettyCash\VntDetailPettyCash;
use Maatwebsite\Excel\Concerns\FromCollection;

class PettyCash implements FromCollection
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return VntDetailPettyCash::all();
    }
}
