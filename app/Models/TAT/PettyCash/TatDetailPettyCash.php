<?php

namespace App\Models\TAT\PettyCash;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Tenant\MethodPayments\VntMethodPayMents;
use App\Models\Tenant\PettyCash\VntReasonsPettyCash;

class TatDetailPettyCash extends Model
{
    use HasFactory;

    protected $table = 'tat_detail_petty_cash';

    protected $fillable = [
        'status',
        'value',
        'pettyCashId',
        'reasonPettyCashId',
        'methodPaymentId',
        'invoiceId',
        'observations'
    ];

    public function reasonsPettyCash(){
        return $this->hasOne(VntReasonsPettyCash::class, 'id', 'reasonPettyCashId');
    }

    public function methodPayments(){
        return $this->hasOne(VntMethodPayMents::class, 'id', 'methodPaymentId');
    }
}
