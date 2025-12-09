<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VntContact extends Model
{
    protected $connection = 'tenant';
    protected $table = 'vnt_contacts';

    protected $fillable = [
        'firstName',
        'secondName',
        'lastName',
        'secondLastName',
        'email',
        'business_phone',
        'contact',
        'status',
        'integrationDataId',
        'warehouseId',
        'positionId'
    ];

    protected $casts = [
        'status' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(VntWarehouse::class, 'warehouseId');
    }
}