<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\TAT\Customers\TatCustomersManager;

Route::middleware(['auth'])->group(function () {
    // Ruta principal de customers (siempre disponible)
    Route::get('/tenant/customers', TatCustomersManager::class)->name('tenant.customers');

    // Rutas específicas
    Route::get('/tenant/customers/tienda', TatCustomersManager::class)->name('tenant.customers.tienda');
    Route::get('/tenant/customers/inventario', function() {
        return redirect()->route('tenant.customers');
    })->name('tenant.customers.inventario');
});