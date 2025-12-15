<?php

namespace App\Models\Tenant\Remissions;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Tenant\Items\Items;

class InvDetailRemissions extends Model
{
    use HasFactory;

    protected $table = 'inv_detail_remissions';

    protected $fillable = [
        'id',
        'quantity',
        'value',
        'discount',
        'tax',
        'remissionId',
        'itemId',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'quantity' => 'integer',
        'value' => 'decimal:2',
        'discount' => 'decimal:2',
        'tax' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Relación con la remisión
     */
    public function remission()
    {
        return $this->belongsTo(InvRemissions::class, 'remissionId', 'id');
    }

    /**
     * Relación con el item
     */
    public function item()
    {
        return $this->belongsTo(Items::class, 'itemId', 'id');
    }
}
