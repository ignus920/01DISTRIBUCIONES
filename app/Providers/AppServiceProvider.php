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
        if ($this->app->runningInConsole()) {
            $this->commands([
                \App\Console\Commands\MakeLivewireModuleCommand::class,
            ]);
        }

        // Register Livewire components
        Livewire::component('auth.enable2-f-a', \App\Auth\Livewire\Enable2FA::class);
    }
}
