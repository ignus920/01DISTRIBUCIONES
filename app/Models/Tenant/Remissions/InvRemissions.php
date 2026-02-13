<?php

namespace App\Models\Tenant\Remissions;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Auth\User;


class InvRemissions extends Model
{
    use HasFactory;

    protected $connection = 'tenant';
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
        'modify',
        'observations_return'
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

    public function delivery()
    {
        return $this->belongsTo(\App\Models\Tenant\DeliveriesList\DisDeliveries::class, 'delivery_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'userId');
    }

    /**
     * Getters para compatibilidad con las vistas de impresión del cotizador
     */
    public function getDetallesAttribute()
    {
        return $this->details;
    }

    public function getObservationsAttribute()
    {
        return $this->observations_return;
    }
}
