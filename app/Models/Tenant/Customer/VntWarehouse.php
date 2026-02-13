<?php

namespace App\Models\Tenant\Customer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VntWarehouse extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The table associated with the model.
     *
     * @var string
     */
     protected $connection = 'tenant';
     protected $table = 'vnt_warehouses'; 

     public const BRANCH_TYPE_FIJA = 'FIJA';
     public const BRANCH_TYPE_DESPACHO = 'DESPACHO';
    /**
     * The primary key for the model.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Indicates if the model's ID is auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * The data type of the primary key.
     *
     * @var string
     */
    protected $keyType = 'int';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'companyId',
        'name',
        'address',
        'postcode',
        'cityId',
        'billingFormat',
        'is_credit',
        'termId',
        'creditLimit',
        'priceList',
        'status', // Tiene un default(1) en la migración
        'district',
        'api_data_id',
        'main',
        'branch_type',
        // 'created_at' y 'updated_at' se manejan automáticamente
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        // 'id' => 'integer', // bigInt UNSIGNED
        'companyId' => 'integer', // bigInt UNSIGNED
        'cityId' => 'integer',
        'billingFormat' => 'integer',
        'is_credit' => 'integer',
        'termId' => 'integer',
        'priceList' => 'integer',
        'status' => 'integer',
        'api_data_id' => 'integer',
        'main' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        // No hay campos obvios para ocultar, pero puedes agregar aquí información sensible
    ];

    // --- Relaciones ---

    /**
     * Relación con la empresa
     */
    public function company()
    {
        return $this->belongsTo(VntCompany::class, 'companyId');
    }

    /**
     * Relación con los contactos del almacén
     */
    public function contacts()
    {
        return $this->hasMany(VntContacts::class, 'warehouseId');
    }

    /**
     * Contactos activos del almacén
     */
    public function activeContacts()
    {
        return $this->hasMany(VntContacts::class, 'warehouseId')->where('status', 1);
    }

    /**
     * Relación con la ciudad
     */
    public function city()
    {
        return $this->belongsTo(\App\Models\Central\CnfCity::class, 'cityId');
    }

    public function scopeDespacho($query)
    {
        return $query->where('branch_type', self::BRANCH_TYPE_DESPACHO);
    }
    
    // --- Métodos de Conveniencia ---

    /**
     * Verifica si la sucursal es de tipo FIJA.
     */
    public function isFija(): bool
    {
        return $this->branch_type === self::BRANCH_TYPE_FIJA;
    }

    /**
     * Verifica si la sucursal es de tipo DESPACHO.
     */
    public function isDespacho(): bool
    {
        return $this->branch_type === self::BRANCH_TYPE_DESPACHO;
    }

    // public function term()
    // {
    //     // Asumiendo una relación con un modelo Term
    //     return $this->belongsTo(Term::class, 'termId');
    // }
}