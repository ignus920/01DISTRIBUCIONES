<?php

namespace App\Models\Tenant\Sales;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VntInvoicesXsale extends Model
{
    public $timestamps = false; // La tabla no tiene created_at/updated_at

    protected $connection = 'tenant';
    protected $table = 'vnt_invoicesXsales';

    protected $fillable = [
        'remissionId',
        'quoteId',
        'invoiceId',
    ];

    protected $casts = [
        'remissionId' => 'integer',
        'quoteId'     => 'integer',
        'invoiceId'   => 'integer',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(VntInvoice::class, 'invoiceId');
    }
}
