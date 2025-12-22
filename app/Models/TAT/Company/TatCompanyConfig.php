<?php

namespace App\Models\TAT\Company;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TatCompanyConfig extends Model
{
    use HasFactory;

    protected $connection = 'tenant';
    protected $table = 'tat_company_config';

    protected $fillable = [
        'company_id',
        'vender_sin_saldo',
        'permitir_cambio_precio',
    ];

    protected $casts = [
        'vender_sin_saldo' => 'boolean',
        'permitir_cambio_precio' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Obtener o crear la configuración para una empresa
     */
    public static function getForCompany($companyId)
    {
        return self::firstOrCreate(
            ['company_id' => $companyId],
            [
                'vender_sin_saldo' => false,
                'permitir_cambio_precio' => false,
            ]
        );
    }

    /**
     * Actualizar configuración específica
     */
    public function updateConfig($key, $value)
    {
        if (in_array($key, $this->fillable)) {
            $this->update([$key => $value]);
            return true;
        }
        return false;
    }

    /**
     * Verificar si puede vender sin saldo
     */
    public function canSellWithoutStock()
    {
        return $this->vender_sin_saldo;
    }

    /**
     * Verificar si permite cambio de precio
     */
    public function allowsPriceChange()
    {
        return $this->permitir_cambio_precio;
    }

    /**
     * Obtener todas las configuraciones como array
     */
    public function getConfigArray()
    {
        return [
            'vender_sin_saldo' => $this->vender_sin_saldo,
            'permitir_cambio_precio' => $this->permitir_cambio_precio,
        ];
    }

    /**
     * Actualizar múltiples configuraciones
     */
    public function updateMultipleConfigs(array $configs)
    {
        $validConfigs = array_intersect_key($configs, array_flip($this->fillable));

        if (!empty($validConfigs)) {
            $this->update($validConfigs);
            return true;
        }

        return false;
    }
}