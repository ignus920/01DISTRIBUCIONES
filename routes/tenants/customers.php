<?php

use Illuminate\Support\Facades\Route;
use App\Auth\Livewire\SelectTenant;
use App\Http\Controllers\Customers\CompanyController;



Route::get('/customers', function () {
    return view('livewire.tenant.vnt-company.customers');
})->name('customers.customers');

Route::get('/vnt-customers', \App\Livewire\Tenant\Customers\VntCustomers::class)
    ->middleware(['auth', 'company.complete', \App\Auth\Middleware\SetTenantConnection::class])
    ->name('tenant.vnt-customers');


// Routes to Api

// Route::prefix('api/customers')->middleware(\App\Auth\Middleware\SetTenantConnection::class)->group(function () {
// // CRUD básico
// Route::get('/', [CompanyController::class, 'index']);
// Route::post('/', [CompanyController::class, 'store']);
// Route::get('/{id}', [CompanyController::class, 'show']);
// Route::put('/{id}', [CompanyController::class, 'update']);
// Route::delete('/{id}', [CompanyController::class, 'destroy']);

// // Métodos adicionales
// Route::post('/{id}/restore', [CompanyController::class, 'restore']);
// Route::delete('/{id}/force', [CompanyController::class, 'forceDelete']);
// Route::patch('/{id}/toggle-status', [CompanyController::class, 'toggleStatus']);
// Route::get('/list/trashed', [CompanyController::class, 'trashed']);

// });


