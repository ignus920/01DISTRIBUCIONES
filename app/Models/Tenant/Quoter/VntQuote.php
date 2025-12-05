<?php

namespace App\Models\Tenant\Quoter;

use App\Models\Tenant\Customer\VntContacts;
use App\Models\Tenant\Customer\VntWarehouse;
use App\Models\Tenant\Customer\VntCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VntQuote extends Model
{
    protected $connection = 'tenant';
    protected $table = 'vnt_quotes';

    protected $fillable = [
        'consecutive',
        'status',
        'typeQuote',
        'customerId',
        'warehouseId',
        'userId',
        'observations',
        'branchId'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relaciones
    public function detalles(): HasMany
    {
        return $this->hasMany(VntDetailQuote::class, 'quoteId');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(VntCompany::class, 'customerId');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(VntWarehouse::class, 'warehouseId');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(VntWarehouse::class, 'branchId');
    }

    // Métodos de utilidad
    public function getTotalAttribute()
    {
        return $this->detalles->sum(function ($detalle) {
            return $detalle->quantity * $detalle->value;
        });
    }

    public function getCustomerNameAttribute()
    {
        if (!$this->customer) {
            return 'Cliente no encontrado';
        }

        // Si es persona jurídica, usar businessName; si es persona natural, usar nombres
        return $this->customer->businessName ?:
               trim($this->customer->firstName . ' ' . $this->customer->secondName . ' ' .
                    $this->customer->lastName . ' ' . $this->customer->secondLastName);
    }

    public function getWarehouseNameAttribute()
    {
        return $this->warehouse ? $this->warehouse->name : 'Sucursal no encontrada';
    }
}