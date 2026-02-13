<?php

namespace App\Models\TAT\Quoter;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Quote extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'tenant';
    protected $table = 'tat_quotes';

    protected $fillable = [
        'company_id',
        'consecutive',
        'status',
        'customerId',
        'userId',
        'observations',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'company_id' => 'integer',
            'consecutive' => 'integer',
            'customerId' => 'integer',
            'userId' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    // Relaciones
    public function user()
    {
        return $this->belongsTo(User::class, 'userId', 'id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class, 'quoteId');
    }

    public function customer()
    {
        // Relación con el cliente, asumiendo que existe una tabla de clientes
        return $this->belongsTo(\App\Models\TAT\Customer\Customer::class, 'customerId', 'id');
    }

    // Scopes
    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeRegistrado($query)
    {
        return $query->where('status', 'Registrado');
    }

    public function scopeAnulado($query)
    {
        return $query->where('status', 'Anulado');
    }

    public function scopeExpired($query)
    {
        return $query->where('valid_until', '<', now());
    }

    public function scopeValid($query)
    {
        return $query->where('valid_until', '>=', now());
    }

    // Accessors
    public function getFormattedTotalAttribute()
    {
        return '$ ' . number_format($this->total, 2);
    }

    public function getFormattedSubtotalAttribute()
    {
        return '$ ' . number_format($this->subtotal, 2);
    }

    public function getFormattedTaxAmountAttribute()
    {
        return '$ ' . number_format($this->tax_amount, 2);
    }

    public function getFormattedDiscountAmountAttribute()
    {
        return '$ ' . number_format($this->discount_amount, 2);
    }

    public function getStatusLabelAttribute()
    {
        $statuses = [
            'pending' => 'Pendiente',
            'approved' => 'Aprobada',
            'rejected' => 'Rechazada',
            'expired' => 'Expirada',
            'converted' => 'Convertida a Factura'
        ];

        return $statuses[$this->status] ?? 'Desconocido';
    }

    public function getStatusColorAttribute()
    {
        $colors = [
            'pending' => 'yellow',
            'approved' => 'green',
            'rejected' => 'red',
            'expired' => 'gray',
            'converted' => 'blue'
        ];

        return $colors[$this->status] ?? 'gray';
    }

    // Métodos de utilidad
    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isApproved()
    {
        return $this->status === 'approved';
    }

    public function isRejected()
    {
        return $this->status === 'rejected';
    }

    public function isExpired()
    {
        return $this->valid_until && $this->valid_until < now();
    }

    public function isValid()
    {
        return !$this->isExpired();
    }

    public function getTotalItems()
    {
        return $this->items()->sum('quantity');
    }

    public function approve($notes = null)
    {
        $this->update([
            'status' => 'approved',
            'notes' => $notes,
        ]);
    }

    public function reject($notes = null)
    {
        $this->update([
            'status' => 'rejected',
            'notes' => $notes,
        ]);
    }

    public function convertToInvoice()
    {
        $this->update([
            'status' => 'converted',
        ]);
    }

    public function calculateTotals()
    {
        $subtotal = $this->items()->sum('subtotal');
        $taxAmount = $subtotal * 0.19; // Asumiendo 19% de IVA
        $total = $subtotal + $taxAmount - $this->discount_amount;

        $this->update([
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'total' => $total,
        ]);
    }

    // Boot method para eventos del modelo
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($quote) {
            if (!$quote->status) {
                $quote->status = 'Registrado';
            }
        });
    }
}
