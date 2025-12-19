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
        'tax_percentage',
        'price',
        'quoteId',
        'itemId',
        'description',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'price' => 'float',
        'tax_percentage' => 'int'
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
        return $this->attributes['price'] ?? 0;
    }

    public function getTaxAttribute()
    {
        return $this->attributes['tax_percentage'] ?? 0;
    }

    // Nota: No necesitamos accessor para description porque ya existe como campo
    // Si lo necesitas, usa un nombre diferente para evitar conflictos
}