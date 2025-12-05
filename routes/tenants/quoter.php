<?php

use App\Http\Controllers\Quoter\QuoterController;
use App\Http\Controllers\Quoter\QuoterPrintController;
use App\Livewire\Tenant\Quoter\ProductQuoter;

/*
|--------------------------------------------------------------------------
| Quoter Routes
|--------------------------------------------------------------------------
|
| Aqu� est�n todas las rutas relacionadas con el cotizador (Quoter)
| Estas rutas est�n espec�ficamente para el tenant
|
*/

// Ruta principal del cotizador
Route::get('/tenant/quoter', [QuoterController::class, 'index'])
    ->middleware(['auth', 'verified', 'tenant'])
    ->name('tenant.quoter');

// Ruta de productos del cotizador
Route::get('/tenant/quoter/products', [QuoterController::class, 'products'])
    ->middleware(['auth', 'verified', 'tenant'])
    ->name('tenant.quoter.products');

// Ruta de cotizador desktop
Route::get('/tenant/quoter/desktop', [QuoterController::class, 'desktop'])
    ->middleware(['auth', 'verified', 'tenant'])
    ->name('tenant.quoter.desktop');

// Ruta de cotizador mobile
Route::get('/tenant/quoter/mobile', [QuoterController::class, 'mobile'])
    ->middleware(['auth', 'verified', 'tenant'])
    ->name('tenant.quoter.mobile');

// Componente Livewire para cotizador desktop
Route::get('/tenant/quoter/products/desktop', ProductQuoter::class)
    ->middleware(['auth', 'verified', 'tenant'])
    ->name('tenant.quoter.products.desktop')
    ->defaults('viewType', 'desktop');

// Componente Livewire para cotizador mobile
Route::get('/tenant/quoter/products/mobile', ProductQuoter::class)
    ->middleware(['auth', 'verified', 'tenant'])
    ->name('tenant.quoter.products.mobile')
    ->defaults('viewType', 'mobile');

// Rutas para editar cotizaciones existentes
// Estas rutas cargan el ProductQuoter con un ID de cotización específico para editarla
Route::get('/tenant/quoter/products/desktop/edit/{quoteId}', ProductQuoter::class)
    ->middleware(['auth', 'verified', 'tenant'])
    ->name('tenant.quoter.products.desktop.edit')        // Nombre de la ruta para vista escritorio
    ->defaults('viewType', 'desktop');                   // Establece vista como escritorio por defecto

Route::get('/tenant/quoter/products/mobile/edit/{quoteId}', ProductQuoter::class)
    ->middleware(['auth', 'verified', 'tenant'])
    ->name('tenant.quoter.products.mobile.edit')         // Nombre de la ruta para vista móvil
    ->defaults('viewType', 'mobile');                    // Establece vista como móvil por defecto

// Ruta para servir archivos temporales de impresión
Route::get('/quoter/print/temp/{file}', [QuoterPrintController::class, 'showTempPrint'])
    ->middleware(['auth', 'verified', 'tenant'])
    ->name('quoter.print.temp')
    ->where('file', '^quote_\d+_\d+\.html$');           // Validación de formato de archivo