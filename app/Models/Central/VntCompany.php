<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VntCompany extends Model
{
    use HasFactory;

    protected $connection = 'tenant';
    protected $table = 'vnt_companies';

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
        'status',
        'typePerson',
        'typeIdentificationId',
        'regimeId',
        'code_ciiu',
        'fiscalResponsabilityId',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'integrationDataId' => 'integer',
            'checkDigit' => 'integer',
            'status' => 'integer',
            'typeIdentificationId' => 'integer',
            'regimeId' => 'integer',
            'fiscalResponsabilityId' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    // Relaciones con las tablas de configuración
    public function typeIdentification()
    {
        return $this->belongsTo(CnfTypeIdentification::class, 'typeIdentificationId');
    }

    public function regime()
    {
        return $this->belongsTo(CnfRegime::class, 'regimeId');
    }

    public function fiscalResponsability()
    {
        return $this->belongsTo(CnfFiscalResponsability::class, 'fiscalResponsabilityId');
    }

    // Relación con almacenes (warehouses)
    public function warehouses()
    {
        return $this->hasMany(VntWarehouse::class, 'companyId');
    }

    // Relación con contactos
    public function contacts()
    {
        return $this->hasManyThrough(
            VntContact::class,
            VntWarehouse::class,
            'companyId', // Foreign key en vnt_warehouses
            'warehouseId', // Foreign key en vnt_contacts
            'id', // Local key en vnt_companies
            'id' // Local key en vnt_warehouses
        );
    }

    // Accessors para mostrar nombre completo
    public function getFullNameAttribute()
    {
        return trim("{$this->firstName} {$this->secondName} {$this->lastName} {$this->secondLastName}");
    }

    // Scopes útiles
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }

    public function scopeByPersonType($query, $type)
    {
        return $query->where('typePerson', $type);
    }
}
