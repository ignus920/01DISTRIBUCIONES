<?php

namespace App\Models\TAT\Items;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\TAT\Categories\TatCategories;
use App\Models\Central\CnfTaxes;

class TatItems extends Model
{
    use HasFactory;

    protected $connection = 'tenant';
    protected $table = 'tat_items';

    protected $fillable = [
        'item_father_id',
        'company_id',
        'sku',
        'name',
        'taxId',
        'categoryId',
        'stock',
        'img_path',
        'cost',
        'price',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'item_father_id' => 'integer',
            'company_id' => 'integer',
            'taxId' => 'integer',
            'categoryId' => 'integer',
            'stock' => 'decimal:2',
            'cost' => 'decimal:2',
            'price' => 'decimal:2',
            'status' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    // Relaciones
    public function category()
    {
        return $this->belongsTo(TatCategories::class, 'categoryId', 'id');
    }

    public function tax()
    {
        return $this->belongsTo(CnfTaxes::class, 'taxId', 'id');
    }

    public function itemFather()
    {
        return $this->belongsTo(self::class, 'item_father_id', 'id');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'item_father_id', 'id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 0);
    }

    public function scopeByCategory($query, $categoryId)
    {
        return $query->where('categoryId', $categoryId);
    }

    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    // Accessors
    public function getFormattedCostAttribute()
    {
        return '$ ' . number_format($this->cost, 2);
    }

    public function getFormattedPriceAttribute()
    {
        return '$ ' . number_format($this->price, 2);
    }

    public function getFormattedStockAttribute()
    {
        return number_format($this->stock, 2);
    }

    public function getDisplayNameAttribute()
    {
        return strtoupper($this->attributes['name']);
    }

    // Métodos de utilidad
    public function hasStock()
    {
        return $this->stock > 0;
    }

    public function isActive()
    {
        return $this->status == 1;
    }

    public function getMarginPercentage()
    {
        if ($this->cost <= 0) {
            return 0;
        }
        return (($this->price - $this->cost) / $this->cost) * 100;
    }

    public function getMargin()
    {
        return $this->price - $this->cost;
    }
}