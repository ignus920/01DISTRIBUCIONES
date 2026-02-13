<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VntMerchantType extends Model
{
    protected $connection = 'tenant';
    protected $table = 'vnt_merchant_types';

    protected $fillable = [
        'name',
        'description',
        'version',
        'status'
    ];

    protected $casts = [
        'status' => 'boolean',
        'createdAt' => 'datetime',
        'updatedAt' => 'datetime',
        'deletedAt' => 'datetime'
    ];

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public function merchantModuls(): HasMany
    {
        return $this->hasMany(VntMerchantModul::class, 'merchantId');
    }
}