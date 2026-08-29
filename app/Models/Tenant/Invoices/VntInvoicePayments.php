<?php

namespace App\Models\Tenant\Invoices;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class VntInvoicePayments extends Model
{
    use SoftDeletes;

    protected $connection = 'tenant';
    protected $table = 'vnt_invoice_payments';

    protected $fillable = [
        'value',
        'invoiceId',
        'methodPaymentId'
    ];

    protected $casts = [
        'value' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relaciones
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(VntInvoices::class, 'invoiceId');
    }

    // Nota: methodPaymentId referencia a vnt_method_payments en BD RAP
    // No podemos hacer relación directa por estar en diferente BD
}