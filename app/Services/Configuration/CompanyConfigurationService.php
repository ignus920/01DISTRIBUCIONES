<?php

namespace App\Services\Configuration;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CompanyConfigurationService
{
    /**
     * Tiempo de caché en minutos (1 hora por defecto)
     */
    protected const CACHE_TTL = 60;

    /**
     * Prefijo para las llaves de caché
     */
    protected const CACHE_PREFIX = 'company_config';

    /**
     * Obtiene la configuración completa de la empresa actual
     *
     * @param int $companyId
     * @param int $plainId Plan de la empresa (1=post, 2=institucional)
     * @return array
     */
    public function getCompanyConfiguration(int $companyId, int $plainId): array
    {
        $cacheKey = $this->getCacheKey('full', $companyId, $plainId);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($companyId, $plainId) {
            return $this->fetchConfigurationFromDatabase($companyId, $plainId);
        });
    }

    /**
     * Obtiene configuración específica de un módulo
     *
     * @param int $companyId
     * @param int $plainId
     * @param string $modulName
     * @return array
     */
    public function getModuleConfiguration(int $companyId, int $plainId, string $modulName): array
    {
        $cacheKey = $this->getCacheKey('module', $companyId, $plainId, $modulName);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($companyId, $plainId, $modulName) {
            return $this->fetchModuleConfigurationFromDatabase($companyId, $plainId, $modulName);
        });
    }

    /**
     * Verifica si un campo debe mostrarse en el formulario
     *
     * @param int $companyId
     * @param int $plainId
     * @param string $modulName
     * @param string $optionName
     * @return bool
     */
    public function shouldShowField(int $companyId, int $plainId, string $modulName, string $optionName): bool
    {
        $moduleConfig = $this->getModuleConfiguration($companyId, $plainId, $modulName);

        foreach ($moduleConfig as $option) {
            if ($option->opcion === $optionName) {
                return (bool) $option->value;
            }
        }

        return false; // Por defecto no mostrar si no está configurado
    }

    /**
     * Obtiene valor de una configuración específica
     *
     * @param int $companyId
     * @param int $plainId
     * @param string $modulName
     * @param string $optionName
     * @param mixed $default
     * @return mixed
     */
    public function getConfigValue(int $companyId, int $plainId, string $modulName, string $optionName, $default = null)
    {
        $moduleConfig = $this->getModuleConfiguration($companyId, $plainId, $modulName);

        foreach ($moduleConfig as $option) {
            if ($option->opcion === $optionName) {
                return $option->value;
            }
        }

        return $default;
    }

    /**
     * Invalida el caché de configuración
     *
     * @param int|null $companyId Si es null, limpia toda la configuración
     * @param int|null $plainId
     * @return void
     */
    public function clearCache(int $companyId = null, int $plainId = null): void
    {
        if ($companyId && $plainId) {
            // Limpiar caché específico de una empresa
            $pattern = self::CACHE_PREFIX . ".company.{$companyId}.plain.{$plainId}.*";
            Cache::flush(); // En producción, usar tags para borrado selectivo
        } else {
            // Limpiar todo el caché de configuración
            Cache::flush();
        }
    }

    /**
     * Verifica si una opción específica está habilitada (método principal)
     *
     * @param int $companyId
     * @param int $optionId
     * @return bool
     */
    public function isOptionEnabled(int $companyId, int $optionId): bool
    {
        $cacheKey = $this->getCacheKey('option', $companyId, 0, $optionId);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($companyId, $optionId) {
            return DB::connection('tenant')->table('cnf_company_options')
                ->where('company_id', $companyId)
                ->where('option_id', $optionId)
                ->where('value', '!=', 0)  // Cambio: cualquier valor diferente de 0
                ->whereNull('deleted_at')
                ->exists();
        });
    }

    /**
     * Obtiene todas las opciones habilitadas para una empresa
     *
     * @param int $companyId
     * @return array
     */
    public function getEnabledOptions(int $companyId): array
    {
        $cacheKey = $this->getCacheKey('enabled_options', $companyId, 0);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($companyId) {
            return DB::connection('tenant')->table('cnf_company_options')
                ->where('company_id', $companyId)
                ->where('value', '!=', 0)  // Cambio: cualquier valor diferente de 0
                ->whereNull('deleted_at')
                ->pluck('option_id')
                ->toArray();
        });
    }

    /**
     * Obtiene el valor específico de una opción
     *
     * @param int $companyId
     * @param int $optionId
     * @return int|null Retorna el valor de la opción o null si no existe
     */
    public function getOptionValue(int $companyId, int $optionId): ?int
    {
        $cacheKey = $this->getCacheKey('option_value', $companyId, 0, $optionId);

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($companyId, $optionId) {
            $result = DB::connection('tenant')->table('cnf_company_options')
                ->where('company_id', $companyId)
                ->where('option_id', $optionId)
                ->whereNull('deleted_at')
                ->value('value');

            return $result !== null ? (int) $result : null;
        });
    }

    

    /**
     * Verifica múltiples opciones de una vez
     *
     * @param int $companyId
     * @param array $optionIds
     * @return array [option_id => enabled]
     */
    public function areOptionsEnabled(int $companyId, array $optionIds): array
    {
        $enabledOptions = $this->getEnabledOptions($companyId);
        $result = [];

        foreach ($optionIds as $optionId) {
            $result[$optionId] = in_array($optionId, $enabledOptions);
        }

        return $result;
    }

    /**
     * Habilita o deshabilita una opción para una empresa
     *
     * @param int $companyId
     * @param int $optionId
     * @param bool $enabled
     * @return bool
     */
    public function setOptionEnabled(int $companyId, int $optionId, bool $enabled = true): bool
    {
        $existingOption = DB::connection('tenant')->table('cnf_company_options')
            ->where('company_id', $companyId)
            ->where('option_id', $optionId)
            ->whereNull('deleted_at')
            ->first();

        if ($existingOption) {
            DB::connection('tenant')->table('cnf_company_options')
                ->where('id', $existingOption->id)
                ->update([
                    'value' => $enabled ? 1 : 0,
                    'updated_at' => now()
                ]);
        } else {
            DB::connection('tenant')->table('cnf_company_options')->insert([
                'company_id' => $companyId,
                'option_id' => $optionId,
                'value' => $enabled ? 1 : 0,
                'created_at' => now(),
                'updated_at' => now()
            ]);
        }

        // Limpiar caché relevante
        $this->clearCache($companyId);

        return true;
    }

    /**
     * Precarga configuraciones comunes para mejorar rendimiento
     *
     * @param int $companyId
     * @param int $plainId
     * @return void
     */
    public function preloadCommonConfigurations(int $companyId, int $plainId): void
    {
        // Módulos más utilizados
        $commonModules = ['usuarios', 'formularios', 'reportes', 'facturacion'];

        foreach ($commonModules as $module) {
            $this->getModuleConfiguration($companyId, $plainId, $module);
        }

        // Precargar también opciones habilitadas
        $this->getEnabledOptions($companyId);
    }

    /**
     * Genera la llave de caché
     */
    protected function getCacheKey(string $type, int $companyId, int $plainId, string $additional = ''): string
    {
        $key = self::CACHE_PREFIX . ".{$type}.company.{$companyId}.plain.{$plainId}";

        if ($additional) {
            $key .= ".{$additional}";
        }

        return $key;
    }

    /**
     * Obtiene configuración completa desde la base de datos del TENANT
     */
    protected function fetchConfigurationFromDatabase(int $companyId, int $plainId): array
    {
        return DB::connection('tenant')->table('cnf_company_options')
            ->select('option_id', 'value', 'company_id')
            ->where('company_id', $companyId)
            ->where('value', 1)
            ->whereNull('deleted_at')
            ->get()
            ->toArray();
    }

    /**
     * Obtiene configuración de un módulo específico desde la base de datos del TENANT
     * Retorna directamente los option_id sin mapeo hardcodeado
     */
    protected function fetchModuleConfigurationFromDatabase(int $companyId, int $plainId, string $modulName): array
    {
        return DB::connection('tenant')->table('cnf_company_options')
            ->select('option_id as opcion', 'value', DB::raw("'todas' as modul"))
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->orderBy('option_id')
            ->get()
            ->toArray();
    }
}