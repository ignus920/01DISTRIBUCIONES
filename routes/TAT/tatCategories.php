<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\TAT\Categories\TatCategoriesManager;

Route::middleware(['auth'])->group(function () {
    // Ruta principal de categorías (siempre disponible)
    Route::get('/tenant/categories', TatCategoriesManager::class)->name('tenant.categories');

    // Rutas específicas
    Route::get('/tenant/categories/tienda', TatCategoriesManager::class)->name('tenant.categories.tienda');
    Route::get('/tenant/categories/inventario', function() {
        return redirect()->route('tenant.categories');
    })->name('tenant.categories.inventario');
});