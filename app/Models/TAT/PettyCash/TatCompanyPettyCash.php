<?php

namespace App\Models\TAT\PettyCash;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TatCompanyPettyCash extends Model
{
    use HasFactory, SoftDeletes;

    protected $connection = 'tenant'; // Asumiendo conexión tenant
    protected $table = 'tat_company_petty_cash';

    protected $fillable = [
        'company_id',
        'petty_cash_id',
    ];
}
