<?php

namespace App\Models\Tenant\Items;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Tenant\Items\Items;

class Brand extends Model
{
    use HasFactory;

    protected $connection = 'tenant';

    protected $table = 'inv_item_brand';

    protected $fillable = [
        'name',
        'status',
        'createdAt',
        'updatedAt',
        'deletedAt'
    ];
}
