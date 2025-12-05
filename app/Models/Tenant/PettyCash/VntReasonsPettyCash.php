<?php

namespace App\Models\Tenant\PettyCash;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;


class VntReasonsPettyCash extends Model
{
    use HasFactory;

    protected $connection = 'tenant';

    protected $table = 'vnt_reasons_petty_cash';

    protected $fillable = [
        'name',
        'status',
        'type',
    ];
}
