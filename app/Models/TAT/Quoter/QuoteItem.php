<?php

namespace App\Models\TAT\Quoter;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\TAT\Items\TatItems;

class QuoteItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'tenant';
    protected $table = 'tat_detail_quotes';

    protected $fillable = [
        'quoteId',
        'itemId',
        'quantity',
        'tax_percentage',
        'price',
        'descripcion',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'quoteId' => 'integer',
            'itemId' => 'integer',
            'quantity' => 'integer',
            'tax_percentage' => 'integer',
            'price' => 'float',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    // Relaciones
    public function quote()
    {
        return $this->belongsTo(Quote::class, 'quoteId', 'id');
    }

    public function item()
    {
        return $this->belongsTo(TatItems::class, 'itemId', 'id');
    }

    // Accessors
    public function getSubtotalAttribute()
    {
        return $this->quantity * $this->price;
    }

    public function getTaxAmountAttribute()
    {
        return $this->subtotal * ($this->tax_percentage / 100);
    }

    public function getTotalAttribute()
    {
        return $this->subtotal + $this->tax_amount;
    }

    public function getFormattedPriceAttribute()
    {
        return '$ ' . number_format($this->price, 2);
    }

    public function getFormattedSubtotalAttribute()
    {
        return '$ ' . number_format($this->subtotal, 2);
    }

    public function getFormattedTaxAmountAttribute()
    {
        return '$ ' . number_format($this->tax_amount, 2);
    }

    public function getFormattedTotalAttribute()
    {
        return '$ ' . number_format($this->total, 2);
    }

    // Scopes
    public function scopeByQuote($query, $quoteId)
    {
        return $query->where('quoteId', $quoteId);
    }

    public function scopeByItem($query, $itemId)
    {
        return $query->where('itemId', $itemId);
    }

    // Métodos de utilidad
    public function hasValidQuantity()
    {
        return $this->quantity > 0;
    }

    public function hasValidPrice()
    {
        return $this->price > 0;
    }

    public function updatePrice($newPrice)
    {
        $this->update(['price' => $newPrice]);
    }

    public function updateQuantity($newQuantity)
    {
        if ($newQuantity <= 0) {
            $this->delete();
            return false;
        }

        $this->update(['quantity' => $newQuantity]);
        return true;
    }

    public function increaseQuantity($amount = 1)
    {
        $this->increment('quantity', $amount);
    }

    public function decreaseQuantity($amount = 1)
    {
        $newQuantity = $this->quantity - $amount;
        return $this->updateQuantity($newQuantity);
    }

    // Boot method para eventos del modelo
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($quoteItem) {
            // Establecer descripción automáticamente si no se proporciona
            if (!$quoteItem->descripcion && $quoteItem->item) {
                $quoteItem->descripcion = $quoteItem->item->name;
            }

            // Establecer porcentaje de impuesto por defecto si no se proporciona
            if (!$quoteItem->tax_percentage) {
                $quoteItem->tax_percentage = 19; // 19% IVA por defecto
            }
        });

        static::updating(function ($quoteItem) {
            // Actualizar descripción si el item cambió
            if ($quoteItem->isDirty('itemId') && $quoteItem->item) {
                $quoteItem->descripcion = $quoteItem->item->name;
            }
        });
    }
}