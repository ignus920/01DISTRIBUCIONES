<?php

namespace App\Models\Tenant\Customer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VntContacts extends Model
{
    use HasFactory, SoftDeletes;
    /**
     * El nombre de la tabla asociada con el modelo.
     *
     * @var string
     */
    protected $connection = 'tenant';
    protected $table = 'vnt_contacts';

    /**
     * El nombre de la llave primaria de la tabla.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * Indica si el ID del modelo es auto-incremental.
     *
     * @var bool
     */
    public $incrementing = true;

    /**
     * El tipo de dato de la llave primaria.
     *
     * @var string
     */
    protected $keyType = 'int';

    /**
     * Los atributos que son asignables masivamente.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'firstName',
        'secondName',
        'lastName',
        'secondLastName',
        'email',
        'business_phone',
        'personal_phone',
        'status',
        'api_data_id',
        'warehouseId',
        'positionId',
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'status' => 'integer',
        'api_data_id' => 'integer',
        'warehouseId' => 'integer',
        'positionId' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Los atributos de fecha del modelo.
     *
     * @var array<int, string>
     */
    protected $dates = [
        'deleted_at',
    ];

    /**
     * Los valores por defecto de los atributos del modelo.
     *
     * @var array
     */
    protected $attributes = [
        'status' => 1,
        'warehouseId' => 1,
        'positionId' => 1,
    ];

    // --- Relaciones ---

    /**
     * Relación con el almacén/sucursal
     */
    public function warehouse()
    {
        return $this->belongsTo(VntWarehouse::class, 'warehouseId');
    }

    /**
     * Relación con la posición/cargo
     */
    public function position()
    {
        return $this->belongsTo(CnfPosition::class, 'positionId');
    }

    /**
     * Relación con la empresa a través del almacén
     */
    public function company()
    {
        return $this->hasOneThrough(
            VntCompany::class,
            VntWarehouse::class,
            'id', // Foreign key en vnt_warehouses
            'id', // Foreign key en vnt_companies
            'warehouseId', // Local key en vnt_contacts
            'companyId' // Local key en vnt_warehouses
        );
    }

    // --- Scopes ---

    /**
     * Scope para obtener solo contactos activos.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    /**
     * Scope para obtener solo contactos inactivos.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeInactive($query)
    {
        return $query->where('status', 0);
    }

    /**
     * Scope para filtrar por almacén.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $warehouseId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByWarehouse($query, $warehouseId)
    {
        return $query->where('warehouseId', $warehouseId);
    }

    /**
     * Scope para filtrar por posición.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  int  $positionId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByPosition($query, $positionId)
    {
        return $query->where('positionId', $positionId);
    }

    // --- Métodos de utilidad ---

    /**
     * Verifica si el contacto está activo.
     *
     * @return bool
     */
    public function isActive()
    {
        return $this->status === 1;
    }

    /**
     * Verifica si el contacto está inactivo.
     *
     * @return bool
     */
    public function isInactive()
    {
        return $this->status === 0;
    }

    /**
     * Obtiene el nombre completo del contacto.
     *
     * @return string
     */
    public function getFullNameAttribute()
    {
        $names = array_filter([
            $this->firstName,
            $this->secondName,
            $this->lastName,
            $this->secondLastName
        ]);
        
        return implode(' ', $names);
    }

    /**
     * Obtiene el nombre corto del contacto (primer nombre + primer apellido).
     *
     * @return string
     */
    public function getShortNameAttribute()
    {
        $names = array_filter([
            $this->firstName,
            $this->lastName
        ]);
        
        return implode(' ', $names);
    }

    /**
     * Obtiene el teléfono principal (business_phone o personal_phone).
     *
     * @return string|null
     */
    public function getPrimaryPhoneAttribute()
    {
        return $this->business_phone ?: $this->personal_phone;
    }
}