<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\TAT\Customers\TatCustomersManager;

Route::middleware(['auth'])->group(function () {
    // Ruta principal de customers (siempre disponible)
    Route::get('/tenant/customers', TatCustomersManager::class)->name('tenant.customers');

});