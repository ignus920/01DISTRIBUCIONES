<?php

namespace App\Models\Tenant\Sales;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VenDeliveryType extends Model
{
    use SoftDeletes;

    protected $connection = 'tenant';
    protected $table = 'vnt_delivery_types';

    protected $fillable = ['name', 'status'];
}
