<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Tenant\Parameters\PriceList;
use App\Livewire\Tenant\Parameters\Zones;
use App\Livewire\Tenant\Parameters\Routes;

/**
 * Rutas para el módulo de Parámetros del Tenant
 * Incluye gestión de listas de precios y otros parámetros configurables
 */

// Grupo de rutas para parámetros con prefijo '/parameters'
Route::prefix('/parameters')->group(function () {
    
    // Ruta para gestión de listas de precios
    Route::get('/pricelists',PriceList::class)
        ->name('tenant.parameters.pricelists');
    
    Route::get('/zones', Zones::class)
        ->name('tenant.parameters.zones');
    
    Route::get('/routes', Routes::class)
        ->name('tenant.parameters.routes');
    // Aquí se pueden agregar más rutas de parámetros en el futuro
    // Ejemplo:
    // Route::get('/taxes', App\Livewire\Tenant\Parameters\Taxes::class)
    //     ->name('tenant.parameters.taxes');
    
});
