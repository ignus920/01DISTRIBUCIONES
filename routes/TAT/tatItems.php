<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\TAT\Items\TatItemsManager;
use App\Livewire\Tenant\Items\ManageItems;

/**
 * Rutas para el módulo de Items TAT (Tiendas)
 * Incluye lógica condicional según el perfil del usuario
 */

// Ruta para Items de Tienda (perfil 17)
Route::get('/tenant/items/tienda', TatItemsManager::class)
    ->middleware(['auth', 'company.complete', \App\Auth\Middleware\SetTenantConnection::class])
    ->name('tenant.items.tienda');

// Ruta para Items de Inventario (otros perfiles)
Route::get('/tenant/items/inventario', ManageItems::class)
    ->middleware(['auth', 'company.complete', \App\Auth\Middleware\SetTenantConnection::class])
    ->name('tenant.items.inventario');

// Ruta condicional que redirige según el perfil
Route::get('/tenant/items', function () {
    $user = auth()->user();

    // Si el perfil es 17, redirigir al CRUD de tienda
    if ($user && $user->profile_id == 17) {
        return redirect()->route('tenant.items.tienda');
    }

    // Para cualquier otro perfil, redirigir al CRUD original
    return redirect()->route('tenant.items.inventario');
})
->middleware(['auth', 'company.complete', \App\Auth\Middleware\SetTenantConnection::class])
->name('tenant.items');
