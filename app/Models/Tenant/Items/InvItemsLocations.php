<?php

namespace App\Models\Tenant\Items;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvItemsLocations extends Model
{
    use HasFactory;

    protected $connection = 'tenant';

    protected $table = 'inv_items_locations';

    protected $fillable = [
        'itemId',
        'storeId',
        'locationId',
        'stock_item_location',
        'created_at',
        'updated_at',
    ];

    public function item(){
        return $this->belongsTo(Items::class, 'itemId', 'id');
    }

    public function store(){
        return $this->belongsTo(InvStore::class, 'storeId', 'id');
    }
}
