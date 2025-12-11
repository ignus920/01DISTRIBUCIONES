<?php


namespace App\Models\Tenant\Customer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Tenant\Customer\VntCompany;
use App\Models\TAT\Routes\TatRoutes;
class TatCompanyRoute extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The database connection that should be used by the model.
     *
     * @var string
     */
    protected $connection = 'tenant';

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'tat_companies_routes';  

    
    protected $fillable = [
        'company_id',
        'route_id',
        'sales_order',
        'delivery_order', 
        'created_at',
        'updated_at',
        'deleted_at'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'sales_order' => 'integer',
        'delivery_order' => 'integer',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /**
     * Relación con la empresa
     */
    public function company()
    {
        return $this->belongsTo(VntCompany::class, 'company_id');
    }

    /**
     * Relación con la ruta
     */
    public function route()
    {
        return $this->belongsTo(TatRoutes::class, 'route_id');
    }

}