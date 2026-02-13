<?php

namespace App\Models\Central;

use Illuminate\Database\Eloquent\Model;

class CnfCountry extends Model
{
    protected $connection = 'tenant';
    protected $table = 'countries';

    protected $fillable = [
        'iso2',
        'name',
        'status',
        'phone_code',
        'iso3',
        'region',
        'subregion'
    ];

    protected $casts = [
        'id' => 'integer',
        'status' => 'boolean'
    ];

    public $timestamps = false;
}