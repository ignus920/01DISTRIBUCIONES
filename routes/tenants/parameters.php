<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Tenant\Parameters\PriceList;

/**
 * Rutas para el módulo de Parámetros del Tenant
 * Incluye gestión de listas de precios y otros parámetros configurables
 */

// Grupo de rutas para parámetros con prefijo '/parameters'
Route::prefix('/parameters')->group(function () {
    
    // Ruta para gestión de listas de precios
    Route::get('/pricelists',PriceList::class)
        ->name('tenant.parameters.pricelists');
    
    // Aquí se pueden agregar más rutas de parámetros en el futuro
    // Ejemplo:
    // Route::get('/taxes', App\Livewire\Tenant\Parameters\Taxes::class)
    //     ->name('tenant.parameters.taxes');
    
});
