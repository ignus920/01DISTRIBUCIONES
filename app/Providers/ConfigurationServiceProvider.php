<?php

namespace App\Providers;

use App\Services\Configuration\CompanyConfigurationService;
use Illuminate\Support\ServiceProvider;

class ConfigurationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(CompanyConfigurationService::class, function ($app) {
            return new CompanyConfigurationService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}