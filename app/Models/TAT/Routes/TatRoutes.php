<?php

namespace App\Models\TAT\Routes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TatRoutes extends Model
{
    use HasFactory;

    protected $table = 'tat_routes';

    protected $fillable = [
        'id',
        'name',
        'zone_id',
        'salesman_id',
        'sale_day',
        'delivery_day',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function zones(){
        return $this->belongsTo(\App\Models\TAT\Zones\TatZones::class, 'zone_id');
    }
}
