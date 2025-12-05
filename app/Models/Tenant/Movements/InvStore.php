<?php

namespace App\Models\Tenant\Movements;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Auth\User;

class InvStore extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'tenant';

    protected $table = 'inv_store';

    protected $fillable = [
        'name',
        'warehouseId',
        'store_manager',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'warehouseId' => 'integer',
            'store_manager' => 'integer',
            'status' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    // Relationships

    /**
     * Get the inventory adjustments for this warehouse/store
     */
    public function inventoryAdjustments()
    {
        return $this->hasMany(InvInventoryAdjustment::class, 'warehouseId', 'id');
    }

    /**
     * Get the store manager
     */
    public function storeManager()
    {
        return $this->belongsTo(User::class, 'store_manager', 'id');
    }

    // Scopes

    /**
     * Scope to filter active stores
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Scope to filter inactive stores
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 0);
    }
}
