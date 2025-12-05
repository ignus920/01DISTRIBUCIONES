<?php

namespace App\Models\Tenant\Movements;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvReason extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'tenant';

    protected $table = 'inv_reasons';

    protected $fillable = [
        'name',
        'type',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'status' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    // Relationships

    /**
     * Get the inventory adjustments that use this reason
     */
    public function inventoryAdjustments()
    {
        return $this->hasMany(InvInventoryAdjustment::class, 'reasonId', 'id');
    }

    // Scopes

    /**
     * Scope to filter active reasons
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Scope to filter inactive reasons
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 0);
    }

    /**
     * Scope to filter by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }
}
