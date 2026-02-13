<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Forzar HTTPS si la URL de la aplicación empieza con https
        // Esto es necesario para que los estilos y scripts carguen bien en Ngrok
        if (str_contains(config('app.url'), 'https://')) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        if ($this->app->runningInConsole()) {

            $this->commands([
                \App\Console\Commands\MakeLivewireModuleCommand::class,
            ]);
        }

        // Register Livewire components
        Livewire::component('auth.enable2-f-a', \App\Auth\Livewire\Enable2FA::class);
    }
}
