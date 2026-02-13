<?php

namespace App\Models\Tenant\Quoter;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\TAT\Items\TatItems;

class TatRestockList extends Model
{
    use HasFactory;

    protected $table = 'tat_restock_list';

    // Definir los campos que se pueden asignar masivamente
    protected $fillable = [
        'itemId',
        'company_id',      // Agregado campo company_id
        'quantity_request',
        'quantity_recive', // Nota: Mantenemos el nombre de la columna tal cual viene en el SQL (posible typo de receive)
        'status',          // Enum: 'Registrado', 'Anulado'
        'order_number',
        'deteted_at'       // Nota: Mantenemos el nombre tal cual (posible typo de deleted_at)
    ];

    // Si la tabla no usa timestamps estándar o tienen nombres diferentes
    // El SQL dice created_at y updated_at existen, así que esto está bien.
    public $timestamps = true;

    // Relación con el Item de Distribución (Catálogo Central)
    public function item()
    {
        return $this->belongsTo(\App\Models\Tenant\Items\Items::class, 'itemId');
    }
}
