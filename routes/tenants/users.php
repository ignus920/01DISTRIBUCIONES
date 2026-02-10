<?php

use Illuminate\Support\Facades\Route;

Route::get('/users', function () {
    return view('livewire.tenant.users.users');
})->middleware('tenant')->name('users.users');
