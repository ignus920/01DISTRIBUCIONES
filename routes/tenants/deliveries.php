<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Tenant\Deliveries\Deliveries;

Route::get('/deliveries', Deliveries::class)
    ->middleware(['auth', 'company.complete', \App\Auth\Middleware\SetTenantConnection::class])
    ->name('tenant.deliveries');
