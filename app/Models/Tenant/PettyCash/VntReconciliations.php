<?php

namespace App\Models\Tenant\PettyCash;

use App\Models\Tenant\PettyCash\PettyCash;
use App\Models\Auth\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VntReconciliations extends Model
{
    use HasFactory;

    protected $connection = 'tenant';

    protected $table = 'vnt_reconciliations';

    protected $fillable = [
        'reconciliation',
        'observations',
        'created_at',
        'updated_at',
        'pettyCashId',
        'userId',
    ];

    public function pettyCash(){
        return $this->belongsTo(PettyCash::class, 'pettyCashId', 'id');
    }

    public function user(){
        return $this->belongsTo(User::class, 'userId', 'id');
    }
}
