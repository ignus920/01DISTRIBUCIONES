<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CnfTypeIdentification extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'tenant';
    protected $table = 'cnf_type_identifications';

    protected $fillable = [
        'name',
        'acronym',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'status' => 'integer',
        ];
    }
}
