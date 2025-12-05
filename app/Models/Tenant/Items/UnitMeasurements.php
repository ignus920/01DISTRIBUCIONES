<?php

namespace App\Models\Tenant\Items;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UnitMeasurements extends Model
{
    use HasFactory;

    protected $connection = 'tenant';

    protected $table = 'inv_unit_measurements';

    protected $fillable = [
        'description',
        'quantity',
        'status',
        'deleted_at',
    ];
}
