<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VntPlain extends Model
{
    protected $connection = 'tenant';
    protected $table = 'vnt_plains';

    protected $fillable = [
        'name',
        'description',
        'status',
        'type',
        'merchantTypeId',
        'warehoseQty',
        'usersQty',
        'storesQty'
    ];

    protected $casts = [
        'status' => 'boolean',
        'merchantTypeId' => 'integer',
        'warehoseQty' => 'integer',
        'usersQty' => 'integer',
        'storesQty' => 'integer',
        'createAt' => 'datetime',
        'updateAt' => 'datetime',
        'deletedAt' => 'datetime'
    ];

    const CREATED_AT = 'createAt';
    const UPDATED_AT = 'updateAt';

    public function merchantType(): BelongsTo
    {
        return $this->belongsTo(VntMerchantType::class, 'merchantTypeId');
    }
}