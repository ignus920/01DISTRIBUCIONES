<?php

namespace App\Models\Tenant\Parameters;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla cnf_pricelist
 * Gestiona las listas de precios del sistema
 */
class PriceList extends Model
{
    use HasFactory;

    // Conexión a la base de datos del tenant
    protected $connection = 'tenant';

    // Nombre de la tabla
    protected $table = 'cnf_pricelist';

    // Campos que se pueden asignar masivamente
    protected $fillable = [
        'title',      // Título de la lista de precios
        'value',      // Valor/multiplicador de la lista
        'createAd',   // Fecha de creación
        'updateAd',   // Fecha de actualización
        'status',     // Estado (1=activo, 0=inactivo)
    ];

    // Deshabilitar timestamps automáticos de Laravel
    public $timestamps = false;

    /**
     * Obtener solo las listas de precios activas
     */
    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
}
