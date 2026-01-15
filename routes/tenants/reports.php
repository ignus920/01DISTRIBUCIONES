<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Tenant\Reports\SalesReport;
use App\Livewire\Tenant\Reports\ProfitabilityReport;

/**
 * Rutas para el módulo de Reportes del Tenant
 * Incluye reportes de ventas y otros reportes analíticos
 */

// Grupo de rutas para reportes con prefijo '/reports'
Route::prefix('/reports')->group(function () {
    
    // Ruta para reporte de ventas por fecha
    Route::get('/sales', SalesReport::class)
        ->name('tenant.reports.sales');
    
    // Ruta para reporte de rentabilidad
    Route::get('/profitability', ProfitabilityReport::class)
        ->name('tenant.reports.profitability');
    
    // Aquí se pueden agregar más rutas de reportes en el futuro
    // Ejemplo:
    // Route::get('/inventory', App\Livewire\Tenant\Reports\InventoryReport::class)
    //     ->name('tenant.reports.inventory');
    
});
