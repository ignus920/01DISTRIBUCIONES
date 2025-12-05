<?php

namespace App\Traits;

use App\Services\Configuration\CompanyConfigurationService;
use App\Services\Company\CompanyDataValidator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

trait HasCompanyConfiguration
{
    /**
     * Instancia del servicio de configuración
     */
    protected ?CompanyConfigurationService $configService = null;

    /**
     * Datos de configuración cacheados en la instancia
     */
    protected array $cachedConfig = [];

    /**
     * ID de la empresa actual
     */
    protected ?int $currentCompanyId = null;

    /**
     * ID del plan actual
     */
    protected ?int $currentPlainId = null;

    /**
     * Flag para controlar si ya fue inicializado
     */
    private bool $isConfigurationInitialized = false;

    /**
     * Inicializa la configuración de la empresa
     */
    protected function initializeCompanyConfiguration(): void
    {
        if ($this->isConfigurationInitialized) {
            Log::info('🔧 initializeCompanyConfiguration() - YA INICIALIZADO, saltando...');
            return;
        }

        Log::info('🔧 initializeCompanyConfiguration() - INICIO');

        $this->configService = app(CompanyConfigurationService::class);
        Log::info('🔧 ConfigService creado', ['service_exists' => $this->configService ? 'YES' : 'NO']);

        // Obtener datos de la empresa actual usando el mismo validador que UpdateCompany
        $user = Auth::user();
        Log::info('🔧 Usuario obtenido', [
            'user_exists' => $user ? 'YES' : 'NO',
            'user_id' => $user->id ?? 'NULL',
            'user_email' => $user->email ?? 'NULL'
        ]);

        if ($user) {
            $validator = app(CompanyDataValidator::class);
            Log::info('🔧 Validator creado');

            $company = $validator->getUserCompany($user);
            Log::info('🔧 Empresa obtenida', [
                'company_exists' => $company ? 'YES' : 'NO',
                'company_id' => $company->id ?? 'NULL',
                'company_name' => $company->businessName ?? 'NULL'
            ]);

            if ($company) {
                $this->currentCompanyId = $company->id;
                $this->currentPlainId = $this->getUserPlainId($user); // Por defecto plan 2 (Avanzado)

                Log::info('🔧 IDs asignados', [
                    'currentCompanyId' => $this->currentCompanyId,
                    'currentPlainId' => $this->currentPlainId
                ]);
            } else {
                Log::warning('🔧 No se encontró empresa para el usuario');
            }

        } else {
            Log::warning('🔧 No hay usuario autenticado');
        }

        // Precargar configuraciones comunes
        if ($this->currentCompanyId && $this->currentPlainId) {
            Log::info('🔧 Precargando configuraciones');
            $this->configService->preloadCommonConfigurations(
                $this->currentCompanyId,
                $this->currentPlainId
            );
            Log::info('🔧 Configuraciones precargadas exitosamente');
        } else {
            Log::warning('🔧 No se pueden precargar configuraciones - faltan IDs', [
                'currentCompanyId' => $this->currentCompanyId,
                'currentPlainId' => $this->currentPlainId
            ]);
        }

        $this->isConfigurationInitialized = true;

        Log::info('🔧 initializeCompanyConfiguration() - FIN', [
            'final_companyId' => $this->currentCompanyId,
            'final_plainId' => $this->currentPlainId,
            'final_configService' => $this->configService ? 'YES' : 'NO'
        ]);
    }

    /**
     * Verifica si un campo debe mostrarse según la configuración
     */
    protected function shouldShowField(string $modulName, string $optionName): bool
    {
        $this->ensureConfigurationInitialized();

        if (!$this->configService || !$this->currentCompanyId || !$this->currentPlainId) {
            return false;
        }

        return $this->configService->shouldShowField(
            $this->currentCompanyId,
            $this->currentPlainId,
            $modulName,
            $optionName
        );
    }

    /**
     * Verifica y asegura que la configuración esté inicializada
     */
    private function ensureConfigurationInitialized(): void
    {
        if (!$this->configService || !$this->currentCompanyId) {
            Log::warning('🔄 Estado perdido - Re-inicializando configuración...');
            $this->isConfigurationInitialized = false; // Resetear flag
            $this->initializeCompanyConfiguration();

            Log::info('🔄 Estado después de re-inicialización', [
                'companyId' => $this->currentCompanyId ?? 'NULL',
                'configService_exists' => isset($this->configService) ? 'YES' : 'NO'
            ]);
        }
    }

    /**
     * Verifica si una opción específica está habilitada (método principal)
     */
    protected function isOptionEnabled(int $optionId): bool
    {
        $this->ensureConfigurationInitialized();

        if (!$this->configService || !$this->currentCompanyId) {
            return false;
        }

        return $this->configService->isOptionEnabled($this->currentCompanyId, $optionId);
    }

    /**
     * Verifica múltiples opciones de una vez
     */
    protected function areOptionsEnabled(array $optionIds): array
    {
        if (!$this->configService || !$this->currentCompanyId) {
            return array_fill_keys($optionIds, false);
        }

        return $this->configService->areOptionsEnabled($this->currentCompanyId, $optionIds);
    }

    /**
     * Obtiene todas las opciones habilitadas para la empresa actual
     */
    protected function getEnabledOptions(): array
    {
        if (!$this->configService || !$this->currentCompanyId) {
            return [];
        }

        return $this->configService->getEnabledOptions($this->currentCompanyId);
    }

    /**
     * Obtiene el valor específico de una opción
     */
    protected function getOptionValue(int $optionId): ?int
    {
        $this->ensureConfigurationInitialized();

        if (!$this->configService || !$this->currentCompanyId) {
            return null;
        }

        return $this->configService->getOptionValue($this->currentCompanyId, $optionId);
    }

    /**
     * Obtiene valor de configuración (método legacy mantenido por compatibilidad)
     */
    protected function getConfigValue(string $modulName, string $optionName, $default = null)
    {
        if (!$this->configService || !$this->currentCompanyId || !$this->currentPlainId) {
            return $default;
        }

        return $this->configService->getConfigValue(
            $this->currentCompanyId,
            $this->currentPlainId,
            $modulName,
            $optionName,
            $default
        );
    }

    /**
     * Obtiene la configuración completa del módulo
     */
    protected function getModuleConfig(string $modulName): array
    {
        if (!$this->configService || !$this->currentCompanyId || !$this->currentPlainId) {
            return [];
        }

        $cacheKey = "module_config_{$modulName}";

        if (!isset($this->cachedConfig[$cacheKey])) {
            $this->cachedConfig[$cacheKey] = $this->configService->getModuleConfiguration(
                $this->currentCompanyId,
                $this->currentPlainId,
                $modulName
            );

            
        }

        return $this->cachedConfig[$cacheKey];
    }

    /**
     * Valida campos según configuración antes de procesar formulario
     */
    protected function validateFormFields(string $modulName, array $rules): array
    {
        $validatedRules = [];
        $moduleConfig = $this->getModuleConfig($modulName);

        foreach ($rules as $field => $rule) {
            // Verificar si el campo debe mostrarse según configuración
            if ($this->shouldShowField($modulName, $field)) {
                $validatedRules[$field] = $rule;
            }
        }

        return $validatedRules;
    }

    /**
     * Filtra datos de un modelo según la configuración
     */
    protected function filterDataByConfiguration(string $modulName, array $data): array
    {
        $filteredData = [];

        foreach ($data as $field => $value) {
            if ($this->shouldShowField($modulName, $field)) {
                $filteredData[$field] = $value;
            }
        }

        return $filteredData;
    }

    /**
     * Obtiene el ID del plan del usuario
     * IMPLEMENTAR según tu lógica de negocio
     */
    protected function getUserPlainId($user): int
    {
        // Lógica para determinar el plan:
        // 1 = post
        // 2 = institucional
        // Implementar según como determines el plan del usuario

        return 2; // Por defecto institucional - CAMBIAR según tu lógica
    }

    /**
     * Genera array de configuración para frontend (JavaScript)
     * Método genérico que devuelve todas las opciones habilitadas
     */
    protected function getConfigForFrontend(): array
    {
        return [
            'enabledOptions' => $this->getEnabledOptions(),
        ];
    }

    /**
     * Limpia el caché de configuración
     */
    protected function clearConfigurationCache(): void
    {
        if ($this->configService && $this->currentCompanyId && $this->currentPlainId) {
            $this->configService->clearCache($this->currentCompanyId, $this->currentPlainId);
        }

        $this->cachedConfig = [];
    }
}