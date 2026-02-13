<?php

namespace App\Models\Tenant\Movements;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class InvDetailInventoryAdjustment extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'tenant';

    protected $table = 'inv_detail_inv_adjustments';

    protected $fillable = [
        'quantity',
        'inventoryAdjustmentId',
        'itemId',
        'unitMeasurementId',
        'cost',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'quantity' => 'integer',
            'cost' => 'double',
            'inventoryAdjustmentId' => 'integer',
            'itemId' => 'integer',
            'unitMeasurementId' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    // Relationships

    /**
     * Get the inventory adjustment that owns this detail
     */
    public function inventoryAdjustment()
    {
        return $this->belongsTo(InvInventoryAdjustment::class, 'inventoryAdjustmentId', 'id');
    }

    /**
     * Get the item for this detail
     */
    public function item()
    {
        return $this->belongsTo(\App\Models\Tenant\Items\Items::class, 'itemId', 'id');
    }

    /**
     * Get the unit measurement for this detail
     */
    public function unitMeasurement()
    {
        return $this->belongsTo(\App\Models\Tenant\Items\UnitMeasurements::class, 'unitMeasurementId', 'id');
    }

    // Scopes

    /**
     * Scope to filter by inventory adjustment
     */
    public function scopeByAdjustment($query, $adjustmentId)
    {
        return $query->where('inventoryAdjustmentId', $adjustmentId);
    }

    /**
     * Scope to filter by item
     */
    public function scopeByItem($query, $itemId)
    {
        return $query->where('itemId', $itemId);
    }

    // Accessors

    /**
     * Get formatted quantity
     */
    public function getFormattedQuantityAttribute()
    {
        return number_format($this->quantity, 2);
    }
}
