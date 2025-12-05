<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VntModul extends Model
{
    protected $connection = 'tenant';
    protected $table = 'vnt_moduls';

    protected $fillable = [
        'name',
        'description',
        'version',
        'migration',
        'dev_hours',
        'status'
    ];

    protected $casts = [
        'dev_hours' => 'integer',
        'status' => 'boolean',
        'createdAt' => 'datetime',
        'updatedAt' => 'datetime',
        'deletedAt' => 'datetime'
    ];

    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    public function merchantModuls(): HasMany
    {
        return $this->hasMany(VntMerchantModul::class, 'modulId');
    }
}