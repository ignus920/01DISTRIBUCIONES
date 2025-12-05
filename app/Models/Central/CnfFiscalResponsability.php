<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CnfFiscalResponsability extends Model
{
    use HasFactory;

    protected $connection = 'central';
    protected $table = 'cnf_fiscal_responsabilities';

    protected $fillable = [
        'description',
        'integrationDataId',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'integrationDataId' => 'integer',
        ];
    }
}
