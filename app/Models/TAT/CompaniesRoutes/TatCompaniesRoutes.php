<?php

namespace App\Models\TAT\CompaniesRoutes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TatCompaniesRoutes extends Model
{
    use HasFactory;

    protected $table = 'tat_companies_routes';

    protected $fillable = [
        'id',
        'company_id',
        'route_id',
        'sales_order',
        'delivery_order',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public function company(){
        return $this->hasMany(\App\Models\Tenant\Customer\VntCompany::class, 'company_id');
    }

    public function routes(){
        return $this->hasMany(\App\Models\TAT\Routes\TatRoutes::class, 'route_id');
    }
}
