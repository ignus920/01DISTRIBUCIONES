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

    protected $appends = ['salesman_name', 'salesman_email'];

    public function zones()
    {
        return $this->belongsTo(\App\Models\TAT\Zones\TatZones::class, 'zone_id');
    }

    public function salesman()
    {
        return $this->belongsTo(\App\Models\Auth\User::class, 'salesman_id');
    }

    public function companies()
    {
        return $this->hasMany(\App\Models\Tenant\Customer\TatCompanyRoute::class, 'route_id');
    }

    /**
     * Obtener el nombre del vendedor
     */
    public function getSalesmanNameAttribute()
    {
        return $this->salesman?->name ?? 'Sin vendedor';
    }

    /**
     * Obtener el email del vendedor
     */
    public function getSalesmanEmailAttribute()
    {
        return $this->salesman?->email ?? 'N/A';
    }

    /**
     * Obtener el nombre completo del vendedor con email
     */
    public function getSalesmanFullInfoAttribute()
    {
        $name = $this->salesman?->name ?? 'Sin vendedor';
        $email = $this->salesman?->email;

        return $email ? "{$name} ({$email})" : $name;
    }
}
