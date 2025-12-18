<?php

namespace App\Models\Tenant\VntCustomer;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VntCustomer extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'tenant';
    protected $table = 'vnt_customers';

    protected $fillable = [
        'company_id',
        'typePerson',
        'typeIdentificationId',
        'identification',
        'regimeId',
        'cityId',
        'businessName',
        'billingEmail',
        'firstName',
        'lastName',
        'address',
        'business_phone',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'company_id' => 'integer',
            'typeIdentificationId' => 'integer',
            'regimeId' => 'integer',
            'cityId' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    // Scopes
    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopePersonaNatural($query)
    {
        return $query->where('typePerson', 'Natural');
    }

    public function scopePersonaJuridica($query)
    {
        return $query->where('typePerson', 'Juridica');
    }

    // Accessors
    public function getFullNameAttribute()
    {
        if ($this->typePerson === 'Natural') {
            return trim($this->firstName . ' ' . $this->lastName);
        }

        return $this->businessName;
    }

    public function getDisplayNameAttribute()
    {
        return $this->full_name ?: 'Sin nombre';
    }

    // Métodos de utilidad
    public function isNaturalPerson()
    {
        return $this->typePerson === 'Natural';
    }

    public function isLegalEntity()
    {
        return $this->typePerson === 'Juridica';
    }

    public function hasEmail()
    {
        return !empty($this->billingEmail);
    }

    public function hasPhone()
    {
        return !empty($this->business_phone);
    }
}