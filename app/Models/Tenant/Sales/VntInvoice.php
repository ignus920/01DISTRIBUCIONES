<?php

namespace App\Models\Tenant\Sales;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class VntInvoice extends Model
{
    use SoftDeletes;

    protected $connection = 'tenant';
    protected $table = 'vnt_invoices';

    protected $fillable = [
        'consecutive',
        'status',
        'status_payment',
        'api_data_id',
        'api_data_id_pay',
        'partialPayment',
        'quoteId',
        'warehouseId',
        'remission',
        'creditNoteId',
        'invoiceNumber',
        'retentionFuente',
        'retentionIca',
        'retentionIva',
        'creditNote',
        'orderNumber',
    ];

    protected $casts = [
        'api_data_id'     => 'integer',
        'api_data_id_pay' => 'integer',
        'partialPayment'  => 'integer',
        'quoteId'         => 'integer',
        'warehouseId'     => 'integer',
        'remission'       => 'integer',
        'creditNoteId'    => 'integer',
        'creditNote'      => 'integer',
    ];
}
