<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;

class CnfCity extends Model
{
    protected $connection = 'central';
    protected $table = 'cities';

    protected $fillable = [
        'country_id',
        'state_id',
        'name',
        'country_code'
    ];

    protected $casts = [
        'id' => 'integer',
        'country_id' => 'integer',
        'state_id' => 'integer'
    ];

    public $timestamps = false;
}
