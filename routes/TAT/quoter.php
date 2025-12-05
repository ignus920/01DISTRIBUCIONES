<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\TAT\Quoter\QuoterView;

/**
 * Rutas para el módulo de Cotizador TAT (Tiendas)
 * Para usuarios con perfil de tienda (profile_id 17)
 */

// Ruta principal del cotizador TAT
Route::get('/tenant/tat-quoter', QuoterView::class)
    ->middleware(['auth', 'company.complete', \App\Auth\Middleware\SetTenantConnection::class])
    ->name('tenant.tat.quoter.index');

// Rutas adicionales para el cotizador si se necesitan más funcionalidades
Route::prefix('tenant/quoter')->group(function () {

    // Ruta para ver historial de cotizaciones (si se implementa más adelante)
    // Route::get('/history', QuoteHistory::class)
    //     ->middleware(['auth', 'company.complete', \App\Auth\Middleware\SetTenantConnection::class])
    //     ->name('tenant.quoter.history');

    // Ruta para ver una cotización específica (si se implementa más adelante)
    // Route::get('/view/{id}', QuoteView::class)
    //     ->middleware(['auth', 'company.complete', \App\Auth\Middleware\SetTenantConnection::class])
    //     ->name('tenant.quoter.view');

    // Ruta para imprimir cotización (si se implementa más adelante)
    // Route::get('/print/{id}', [QuotePrintController::class, 'print'])
    //     ->middleware(['auth', 'company.complete', \App\Auth\Middleware\SetTenantConnection::class])
    //     ->name('tenant.quoter.print');
});