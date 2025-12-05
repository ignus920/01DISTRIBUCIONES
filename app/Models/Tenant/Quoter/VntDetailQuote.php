<?php

namespace App\Models\Tenant\Quoter;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Tenant\Items\Items;

class VntDetailQuote extends Model
{
    protected $connection = 'tenant';
    protected $table = 'vnt_detail_quotes';

    protected $fillable = [
        'quantity',
        'tax',
        'value',
        'quoteId',
        'itemId',
        'description',
        'priceList'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(VntQuote::class, 'quoteId');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Items::class, 'itemId');
    }
}