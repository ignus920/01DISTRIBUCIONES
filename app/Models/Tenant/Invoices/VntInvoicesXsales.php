<?php

namespace App\Models\Tenant\Invoices;

use App\Models\Tenant\Quoter\VntQuote;
use App\Models\Tenant\Remissions\InvRemissions;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VntInvoicesXsales extends Model
{

    protected $table = 'vnt_invoicesXsales';
    public $timestamps = false; // No tiene campos created_at/updated_at

    protected $fillable = [
        'remissionId',
        'quoteId',
        'invoiceId'
    ];



    // Relaciones
    public function quote(): BelongsTo
    {
        return $this->belongsTo(VntQuote::class, 'quoteId');
    }

    public function remission(): BelongsTo
    {
        return $this->belongsTo(InvRemissions::class, 'remissionId');
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(VntInvoices::class, 'invoiceId');
    }
}
