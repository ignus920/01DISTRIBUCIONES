<?php

use Illuminate\Support\Facades\Route;
use App\Auth\Middleware\SetTenantConnection;

Route::get('/petty-cash', function (){
    return view('livewire.tenant.petty-cash.home-petty-cash');
})->middleware(['auth', 'company.complete', SetTenantConnection::class])
  ->name('petty-cash.petty-cash');
?>