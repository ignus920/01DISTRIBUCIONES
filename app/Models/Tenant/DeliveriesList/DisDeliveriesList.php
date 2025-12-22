<?php

namespace App\Models\Tenant\DeliveriesList;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DisDeliveriesList extends Model
{
    use HasFactory;

    protected $table = 'dis_deliveries_list';

    protected $fillable = [
        'id',
        'sale_date',
        'salesman_id',
        'deliveryman_id',
        'user_id',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
}
