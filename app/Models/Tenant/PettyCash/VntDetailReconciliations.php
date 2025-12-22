<?php

namespace App\Models\Tenant\PettyCash;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Tenant\MethodPayments\VntMethodPayMents;

class VntDetailReconciliations extends Model
{
    use HasFactory;

    protected $connection = 'tenant';

    protected $table = 'vnt_detail_reconciliations';

    protected $fillable = [
        'value',
        'valueSystem',
        'created_at',
        'updated_at',
        'methodPaymentId',
        'reconciliationId',
    ];

    public function reconciliation(){
        return $this->belongsTo(VntReconciliations::class, 'reconciliationId', 'id');
    }

    public function methodPayments(){
        return $this->belongsTo(VntMethodPayMents::class, 'methodPaymentId', 'id');
    }
}
