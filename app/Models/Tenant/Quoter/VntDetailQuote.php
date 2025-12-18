<?php

namespace App\Models\Tenant\Quoter;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Tenant\Items\Items;

class VntDetailQuote extends Model
{
    protected $connection = 'tenant';
    protected $table = 'tat_detail_quotes';

    protected $fillable = [
        'quantity',
        'tax_percentage',
        'price',
        'quoteId',
        'itemId',
        'descripcion',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'price' => 'float',
        'tax_percentage' => 'float'
    ];

    public function cotizacion(): BelongsTo
    {
        return $this->belongsTo(VntQuote::class, 'quoteId');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Items::class, 'itemId');
    }

    // Accessors para mantener compatibilidad con PaymentQuote
    public function getValueAttribute()
    {
        return $this->price;
    }

    public function getTaxAttribute()
    {
        return $this->tax_percentage;
    }

    public function getDescriptionAttribute()
    {
        return $this->descripcion;
    }
}