<?php

namespace App\Models\Tenant\DeliveriesList;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class DisDeliveries extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'dis_deliveries';

    protected $fillable = [
        'id',
        'salesman_id',
        'user_id',
        'deliveryman_id',
        'sale_date',
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
}
