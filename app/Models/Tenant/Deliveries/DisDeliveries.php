<?php

namespace App\Models\Tenant\Deliveries;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DisDeliveries extends Model
{
    use HasFactory;

    protected $table = 'dis_deliveries';

    protected $fillable = [
        'id',
        'salesman_id',
        'user_id',
        'sale_date',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];
}
