<?php

use Illuminate\Support\Facades\Route;
use App\Auth\Livewire\Verify2FA;
use App\Auth\Livewire\SelectTenant;
use App\Auth\Livewire\Enable2FA;
use App\Http\Livewire\Tenant\Dashboard as TenantDashboard;
use App\Http\Controllers\WorldController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\TestController;
use Livewire\Volt\Volt;
use App\Livewire\Company\UpdateCompany;
use App\Auth\Middleware\SetTenantConnection;
use App\Livewire\Tenant\Customers\CustomerManager;




Route::view('/', 'welcome');

// Rutas de autenticación 2FA
Route::get('/verify-2fa', Verify2FA::class)
    ->name('verify.2fa');

// Configuración de empresa (requiere autenticación)
Route::get('/company/setup', UpdateCompany::class)
    ->middleware(['auth'])
    ->name('company.setup');

// Rutas de selección de tenant (requiere autenticación y datos completos)
Route::get('/select-tenant', SelectTenant::class)
    ->middleware(['auth', 'company.complete'])
    ->name('tenant.select');

// Dashboard del tenant (requiere autenticación, datos completos y tenant seleccionado)
Route::get('/tenant/dashboard', TenantDashboard::class)
    ->middleware(['auth', 'company.complete', SetTenantConnection::class])
    ->name('tenant.dashboard');


    

// Módulo de Clientes (requiere autenticación, datos completos y tenant seleccionado)
Route::get('/tenant/customers', CustomerManager::class)
    ->middleware('tenant')
    ->name('tenant.customers');

     


// Configuración de 2FA (requiere autenticación y datos completos)
Route::get('/settings/2fa', Enable2FA::class)
    ->middleware(['auth', 'company.complete'])
    ->name('settings.2fa');

// Dashboard original de Breeze (redirige a selección de tenant)
Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified', 'company.complete'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth', 'company.complete'])
    ->name('profile');

// Rutas API para Laravel World (accesibles desde cualquier tenant)
Route::prefix('api/world')->group(function () {
    Route::get('/countries', [WorldController::class, 'getCountries'])->name('api.world.countries');
    Route::get('/countries/search', [WorldController::class, 'searchCountries'])->name('api.world.countries.search');
    Route::get('/countries/{countryCode}/complete', [WorldController::class, 'getCountryComplete'])->name('api.world.countries.complete');
    Route::get('/countries/{countryId}/states', [WorldController::class, 'getStates'])->name('api.world.states');
    Route::get('/states/{stateId}/cities', [WorldController::class, 'getCities'])->name('api.world.cities');
});

// Rutas API para productos (requiere tenant activo)
Route::prefix('api/products')->middleware(\App\Auth\Middleware\SetTenantConnection::class)->group(function () {
    // CRUD básico
    Route::get('/', [ProductController::class, 'index']);
    Route::post('/', [ProductController::class, 'store']);
    Route::get('/{id}', [ProductController::class, 'show']);
    Route::put('/{id}', [ProductController::class, 'update']);
    Route::delete('/{id}', [ProductController::class, 'destroy']);

    // Métodos adicionales
    Route::post('/{id}/restore', [ProductController::class, 'restore']);
    Route::delete('/{id}/force', [ProductController::class, 'forceDelete']);
    Route::patch('/{id}/toggle-status', [ProductController::class, 'toggleStatus']);
    Route::get('/list/trashed', [ProductController::class, 'trashed']);

    // Estadísticas
    Route::get('/stats/summary', [ProductController::class, 'stats']);
});

// CRUD de empresas (vnt_companies) - Base central
Route::get('/companies-manager', App\Livewire\Central\Companies\CompaniesManager::class)
    ->name('companies.manager');

// Dashboard Super Administrador - Gestión global del sistema
Route::get('/super-admin', App\Livewire\Central\SuperAdmin\GlobalDashboard::class)
    ->middleware(['auth', 'super.admin'])
    ->name('super.admin.dashboard');

// Rutas de prueba para establecer tenant (SOLO PARA DESARROLLO)
// Route::prefix('api/test')->group(function () {
//     Route::get('/tenants', [TestController::class, 'listTenants']);
//     Route::post('/set-tenant', [TestController::class, 'setTenant']);
//     Route::get('/session', [TestController::class, 'sessionInfo']);
// });




// Ejemplo de formulario configurable (SOLO PARA DESARROLLO/DEMO)
// Route::get('/ejemplo-configuracion', App\Livewire\Examples\ConfigurableFormExample::class)
//     ->middleware(['auth', 'company.complete', App\Auth\Middleware\SetTenantConnection::class])
//     ->name('ejemplo.configuracion');








// Rutas para gestión de permisos y roles (Catálogo de permisos)
Route::prefix('api/permissions')->middleware(['auth', 'company.complete'])->group(function () {
    Route::get('/catalog', [App\Http\Controllers\Auth\PermissionController::class, 'getCatalog'])
        ->name('api.permissions.catalog');

    Route::post('/assign', [App\Http\Controllers\Auth\PermissionController::class, 'assignPermissionToProfile'])
        ->name('api.permissions.assign');

    Route::post('/assign-multiple', [App\Http\Controllers\Auth\PermissionController::class, 'assignMultiplePermissions'])
        ->name('api.permissions.assign.multiple');

    Route::delete('/remove', [App\Http\Controllers\Auth\PermissionController::class, 'removePermissionFromProfile'])
        ->name('api.permissions.remove');

    Route::get('/profile/{profileId}', [App\Http\Controllers\Auth\PermissionController::class, 'getProfilePermissions'])
        ->name('api.permissions.profile');

    Route::post('/clone', [App\Http\Controllers\Auth\PermissionController::class, 'cloneProfilePermissions'])
        ->name('api.permissions.clone');
});



require __DIR__.'/auth.php';

// // Rutas separadas para Items según el perfil del usuario
// Route::get('/tenant/items/tienda', \App\Livewire\Tenant\Items\TatItemsManager::class)
//     ->middleware(['auth', 'company.complete', \App\Auth\Middleware\SetTenantConnection::class])
//     ->name('tenant.items.tienda');

// Route::get('/tenant/items/inventario', \App\Livewire\Tenant\Items\ManageItems::class)
//     ->middleware(['auth', 'company.complete', \App\Auth\Middleware\SetTenantConnection::class])
//     ->name('tenant.items.inventario');

// // Ruta condicional que redirige según el perfil
// Route::get('/tenant/items', function () {
//     $user = auth()->user();

//     // Si el perfil es 17, redirigir al CRUD de tienda
//     if ($user && $user->profile_id == 17) {
//         return redirect()->route('tenant.items.tienda');
//     }

//     // Para cualquier otro perfil, redirigir al CRUD original
//     return redirect()->route('tenant.items.inventario');
// })
// ->middleware(['auth', 'company.complete', \App\Auth\Middleware\SetTenantConnection::class])
// ->name('tenant.items');

require __DIR__.'/tenants/parameters.php';
// Incluir rutas del módulo de pagos de cotizacion
require __DIR__.'/tenants/payments.php';
// Incluir rutas del módulo TAT Items
require __DIR__.'/TAT/tatItems.php';
// Incluir rutas del módulo TAT Categories
require __DIR__.'/TAT/tatCategories.php';
// Incluir rutas del módulo TAT Customers
require __DIR__.'/TAT/tatCustomers.php';
// Incluir rutas del módulo TAT Quoter
require __DIR__.'/TAT/quoter.php';

// Incluir rutas de módulo cargue
require __DIR__.'/tenants/uploads.php';

// Incluir rutas del módulo de clientes del tenant
require __DIR__.'/tenants/customers.php';

// Incluir rutas del módulo de reportes del tenant
require __DIR__.'/tenants/reports.php';

// Rutas de entregas
require __DIR__.'/tenants/deliveries.php';

