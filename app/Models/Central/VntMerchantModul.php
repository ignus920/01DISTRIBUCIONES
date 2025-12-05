<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VntMerchantModul extends Model
{
    protected $connection = 'tenant';
    protected $table = 'vnt_merchant_moduls';

    protected $fillable = [
        'merchantId',
        'modulId'
    ];

    public $timestamps = false;

    public function merchantType(): BelongsTo
    {
        return $this->belongsTo(VntMerchantType::class, 'merchantId');
    }

    public function modul(): BelongsTo
    {
        return $this->belongsTo(VntModul::class, 'modulId');
    }
}