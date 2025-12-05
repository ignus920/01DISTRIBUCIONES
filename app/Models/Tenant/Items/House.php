<?php

namespace App\Models\Tenant\Items;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class House extends Model
{
    use HasFactory;

    protected $connection = 'tenant';

    protected $table = 'inv_item_house';

    protected $fillable = [
        'name',
        'status',
        'deleted_at'
    ];
}
