<?php

namespace App\Models\Tenant\Items;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvItemsStore extends Model
{
    use HasFactory;

    protected $connection = 'tenant';

    protected $table = 'inv_items_store';

    protected $fillable = [
        'itemId',
        'storeId',
        'initial_stock',
        'stock_items_store',
        'stock_min',
        'stock_max',
        'created_at',
        'updated_at'
    ];

    public function item(){
        return $this->belongsTo(Items::class, 'itemId', 'id');
    }

    public function store(){
        return $this->belongsTo(InvStore::class, 'storeId', 'id');
    }
}
