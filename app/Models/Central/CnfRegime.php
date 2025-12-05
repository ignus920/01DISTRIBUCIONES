<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CnfRegime extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'central';
    protected $table = 'cnf_regime';

    protected $fillable = [
        'name',
        'status',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'id' => 'integer',
            'status' => 'integer',
        ];
    }
}
