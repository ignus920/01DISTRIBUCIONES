<?php

use App\Livewire\Tenant\Invoices\Invoices;
use Illuminate\Support\Facades\Route;

Route::get('/tenant/invoices', Invoices::class)
    ->middleware(['auth', 'verified', 'tenant'])
    ->name('tenant.invoices');
