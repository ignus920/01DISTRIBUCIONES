<?php

namespace App\Models\TAT\Categories;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TatCategories extends Model
{
    use HasFactory;

    protected $table = 'tat_categories';

    protected $fillable = [
        'company_id',
        'name',
        'status'
    ];

    protected $casts = [
        'status' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function items()
    {
        return $this->hasMany(\App\Models\TAT\Items\TatItems::class, 'category_id');
    }
}