<?php

namespace App\Models\Tenant\PettyCash;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PriPettyCash extends Model
{
    use HasFactory;
    
    protected $table = 'tat_petty_cash';
    
    protected $fillable = [
        'base',
        'consecutive',
        'status',
        'dateClose',
        'userIdClose',
        'userIdOpen',
        'warehouseId',
        'cashier'
    ];
}
