<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VntWarehouse extends Model
{
    protected $connection = 'tenant';
    protected $table = 'vnt_warehouses';

    protected $fillable = [
        'companyId',
        'name',
        'address',
        'postcode',
        'cityId',
        'billingFormat',
        'is_credit',
        'termId',
        'creditLimit',
        'status',
        'integrationDataId',
        'main'
    ];

    protected $casts = [
        'companyId' => 'integer',
        'cityId' => 'integer',
        'billingFormat' => 'integer',
        'is_credit' => 'boolean',
        'termId' => 'integer',
        'priceList' => 'integer',
        'status' => 'boolean',
        'main' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public function company(): BelongsTo
    {
        return $this->belongsTo(VntCompany::class, 'companyId');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(VntContact::class, 'warehouseId');
    }
}