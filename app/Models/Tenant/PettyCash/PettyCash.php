<?php

namespace App\Models\Tenant\PettyCash;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Auth\User;

class PettyCash extends Model
{
    use HasFactory;

    protected $connection = 'tenant';

    protected $table = 'vnt_petty_cash';

    protected $fillable = [
        'base',
        'consecutive',
        'status',
        'dateClose',
        'created_at',
        'updated_at',
        'userIdClose',
        'userIdOpen',
        'warehouseId',
        'cashier'
    ];

}
