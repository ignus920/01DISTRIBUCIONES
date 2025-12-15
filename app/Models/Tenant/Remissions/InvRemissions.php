<?php

namespace App\Models\Tenant\Remissions;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class InvRemissions extends Model
{
    use HasFactory;

    protected $table = 'inv_remissions';

    protected $fillable = [
        'id',
        'consecutive',
        'status',
        'created_at',
        'updated_at',
        'quoteId',
        'warehouseId',
        'deliveryTypeId',
        'methodPaymentId',
        'userId',
        'deliveryDate',
        'delivery_id',
        'expiration',
        'modify'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function quote(){
        return $this->belongsTo(\App\Models\Tenant\Quoter\VntQuote::class, 'quoteId');
    }

    /**
     * Relación con los detalles de la remisión
     */
    public function details()
    {
        return $this->hasMany(InvDetailRemissions::class, 'remissionId', 'id');
    }
}
