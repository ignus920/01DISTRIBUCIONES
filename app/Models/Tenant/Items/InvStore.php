<?php

namespace App\Models\Tenant\Items;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvStore extends Model
{
    use HasFactory;

    protected $connection = 'tenant';

    protected $table = 'inv_store';

    protected $fillable = [
        'name',
        'warehouseId',
        'store_manager',
        'status',
        'created_at',
        'updated_at',
    ];
}
