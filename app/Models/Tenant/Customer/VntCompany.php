<?php

namespace App\Models\Tenant\Customer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VntCompany extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * El nombre de la tabla asociada con el modelo.
     * La migración usa 'vnt_companies'.
     *
     * @var string
     */

     protected $connection = 'tenant';
     protected $table = 'vnt_companies';

    /**
     * El nombre de la llave primaria de la tabla.
     * La migración usa 'id'.
     *
     * @var string
     */
     protected $primaryKey = 'id';

    /**
     * Los atributos que son asignables masivamente (Mass Assignable).
     * Incluye todas las columnas no de auditoría (created_at, updated_at, deleted_at).
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'businessName',
        'billingEmail',
        'firstName',
        'integrationDataId',
        'identification',
        'checkDigit',
        'lastName',
        'secondLastName',
        'secondName',
        'status', // Tiene un default(1) en la migración
        'typePerson',
        'typeIdentificationId',
        'regimeId',
        'code_ciiu',
        'fiscalResponsabilityId',
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     * 'created_at', 'updated_at', y 'deleted_at' son manejados automáticamente si usas SoftDeletes y no los defines aquí.
     * Se especifican para claridad si quieres un formato específico, pero no es estrictamente necesario aquí.
     *
     * @var array<string, string>
     */
    protected $casts = [
        // 'created_at' => 'datetime', // Laravel maneja esto por defecto
        // 'updated_at' => 'datetime', // Laravel maneja esto por defecto
        // 'deleted_at' => 'datetime', // SoftDeletes maneja esto
        'status' => 'integer',
        'integrationDataId' => 'integer',
        'checkDigit' => 'integer',
        'typeIdentificationId' => 'integer',
        'regimeId' => 'integer',
        'fiscalResponsabilityId' => 'integer',
    ];

    /**
     * Los atributos de fecha del modelo.
     *
     * @var array<int, string>
     */
    protected $dates = [
        'deleted_at',
    ];


    // --- Relaciones ---

    /**
     * Relación con las sucursales (warehouses)
     */
    public function warehouses()
    {
        return $this->hasMany(VntWarehouse::class, 'companyId');
    }

    /**
     * Sucursal principal
     */
    public function mainWarehouse()
    {
        return $this->hasOne(VntWarehouse::class, 'companyId')->where('main', 1);
    }

    /**
     * Todos los contactos de la empresa a través de sus almacenes
     */
    public function contacts()
    {
        return $this->hasManyThrough(
            VntContacts::class,
            VntWarehouse::class,
            'companyId', // Foreign key en vnt_warehouses
            'warehouseId', // Foreign key en vnt_contacts
            'id', // Local key en vnt_companies
            'id' // Local key en vnt_warehouses
        );
    }

    /**
     * Contactos activos de la empresa
     */
    public function activeContacts()
    {
        return $this->hasManyThrough(
            VntContacts::class,
            VntWarehouse::class,
            'companyId',
            'warehouseId',
            'id',
            'id'
        )->where('vnt_contacts.status', 1);
    }
}
