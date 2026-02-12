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
        'branchId',
        'created_at',
        'updated_at',
        'deleted_at'
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
        return $this->belongsTo(VntWarehouse::class, 'customerId');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(VntWarehouse::class, 'warehouseId');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(VntWarehouse::class, 'branchId');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Auth\User::class, 'userId');
    }

    // Métodos de utilidad
    public function getTotalAttribute()
    {
        return $this->detalles->sum(function ($detalle) {
            $subtotal = $detalle->quantity * $detalle->value;
            $tax = $subtotal * (($detalle->tax ?? 0) / 100);
            return $subtotal + $tax;
        });
    }

    public function getCustomerNameAttribute()
    {
        if (!$this->customer) {
            return 'Cliente no encontrado';
        }

        // Si ahora customer es un VntWarehouse, buscamos el nombre en su compañía
        $company = $this->customer->company;
        
        if (!$company) {
            return $this->customer->name ?: 'Sucursal sin nombre';
        }

        return trim($company->businessName ?: ($company->firstName . ' ' . $company->lastName)) ?: 'Sin nombre';
    }

    public function getWarehouseNameAttribute()
    {
        return $this->warehouse ? $this->warehouse->name : 'Sucursal no encontrada';
    }
}