<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Tenant\Reports\SalesReport;
use App\Livewire\Tenant\Reports\SalesXItems;
use App\Livewire\Tenant\Reports\ImpactSales;

/**
 * Rutas para el módulo de Reportes del Tenant
 * Incluye reportes de ventas y otros reportes analíticos
 */

// Grupo de rutas para reportes con prefijo '/reports'
Route::prefix('/reports')->group(function () {

    // Ruta para reporte de ventas por fecha
    Route::get('/sales', SalesReport::class)
        ->name('tenant.reports.sales');

    Route::get('/sales-x-items', SalesXItems::class)
        ->name('tenant.reports.sales-x-items');

    Route::get('/impact-sales', ImpactSales::class)
        ->name('tenant.reports.impact-sales');

    // Aquí se pueden agregar más rutas de reportes en el futuro
    // Ejemplo:
    // Route::get('/inventory', App\Livewire\Tenant\Reports\InventoryReport::class)
    //     ->name('tenant.reports.inventory');

});
