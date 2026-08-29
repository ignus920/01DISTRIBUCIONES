<?php

namespace App\Models\Tenant\Invoices;

use App\Models\Tenant\Quoter\VntQuote;
use App\Models\Tenant\Customer\VntWarehouse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class VntInvoices extends Model
{
    use SoftDeletes;

 
    protected $table = 'vnt_invoices';

    protected $fillable = [
        'id',
        'consecutive',
        'status',
        'status_payment',
        'api_data_id',
        'api_data_id_pay',
        'partialPayment',
        'created_at',
        'updated_at',
        'deleted_at',
        'quoteId',
        'warehouseId',
        'remission',
        'creditNoteId',
        'invoiceNumber',
        'retentionFuente',
        'retentionIca',
        'retentionIva',
        'creditNote',
        'orderNumber'
    ];

    protected $casts = [
        'partialPayment' => 'decimal:2',
        'retentionFuente' => 'decimal:2',
        'retentionIca' => 'decimal:2',
        'retentionIva' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // Relaciones
    public function quote(): BelongsTo
    {
        return $this->belongsTo(VntQuote::class, 'quoteId');
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(VntWarehouse::class, 'warehouseId');
    }

    // Métodos de utilidad
    public function getStatusTextAttribute(): string
    {
        return match ($this->status) {
            'REGISTRADO' => 'Registrado',
            'FACTURADO' => 'Facturado',
            'ANULADO' => 'Anulado',
            'SIN EMITIR' => 'Sin emitir'
        };
    }

    public function getPaymentStatusTextAttribute(): string
    {
        return match ($this->status_payment) {
            'REGISTRADO' => 'Registrado',
            'ABONO' => 'Abono',
            'PAGADO' => 'Pagado',
            'ANULADO' => 'Anulado'
        };
    }
}
