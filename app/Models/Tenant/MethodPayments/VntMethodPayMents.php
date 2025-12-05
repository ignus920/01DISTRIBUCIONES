<?php

namespace App\Models\Tenant\MethodPayments;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VntMethodPayMents extends Model
{
    use HasFactory;

    protected $connection = 'tenant';

    protected $table = 'vnt_method_payments';

    protected $fillable = [
        'name',
        'status',
        'description',
        'created_at',
        'updated_at',
        'type',
        'method',
        'bank',
    ];
}
