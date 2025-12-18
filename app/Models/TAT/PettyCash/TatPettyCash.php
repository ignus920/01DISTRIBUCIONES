<?php

namespace App\Models\TAT\PettyCash;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TatPettyCash extends Model
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
