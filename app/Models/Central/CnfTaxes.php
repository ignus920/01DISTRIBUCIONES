<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class CnfTaxes extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'tenant';
    protected $table = 'cnf_taxes';

    protected $fillable = [
        'name',
        'percentage',
        'status',
        'description',
        'inventoryAccount',
        'inventariablePurchaseAccount',
        'categoryAccount',
    ];
}
