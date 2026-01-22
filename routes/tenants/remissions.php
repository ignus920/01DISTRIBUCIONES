<?php

use App\Livewire\Tenant\Remissions\Remissions;
use Illuminate\Support\Facades\Route;

Route::get('/tenant/remissions', Remissions::class)
    ->middleware(['auth', 'verified', 'tenant'])
    ->name('tenant.remissions');