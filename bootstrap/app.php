<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function (){
              Route::middleware('web')
                ->group(base_path('routes/tenants/customers.php'));

             Route::middleware('web')
                ->group(base_path('routes/tenants/items.php'));

             Route::middleware('web')
                ->group(base_path('routes/tenants/petty_cash.php'));

             Route::middleware('web')
                ->group(base_path('routes/tenants/quoter.php'));

             Route::middleware('web')
                ->group(base_path('routes/tenants/users.php'));      
        
             Route::middleware('web')
                ->group(base_path('routes/tenants/movements.php')); 
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'tenant' => \App\Http\Middleware\SetTenantConnection::class,
            'company.complete' => \App\Http\Middleware\EnsureCompanyDataComplete::class,
            'super.admin' => \App\Http\Middleware\SuperAdminMiddleware::class,
        ]);

        // Aplicar middleware tenant a rutas de Livewire cuando sea necesario
        $middleware->group('tenant', [
            'auth',
            'company.complete',
            \App\Auth\Middleware\SetTenantConnection::class,
        ]);

        // Excluir rutas de test del CSRF (SOLO PARA DESARROLLO)
        $middleware->validateCsrfTokens(except: [
            'api/test/*',
            'api/products/*',
            'api/customers/*',
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
