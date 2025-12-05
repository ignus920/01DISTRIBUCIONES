<?php

namespace App\Models\Tenant\Items;

use Illuminate\Database\Eloquent\Model;

class CnfPricelist extends Model
{
    protected $connection = 'tenant';
    protected $table = 'cnf_pricelist';

    protected $fillable = [
        'title',
        'value',
        'createAd',
        'updateAd',
        'status'
    ];

    protected $casts = [
        'value' => 'float',
        'status' => 'integer',
        'createAd' => 'datetime',
        'updateAd' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}