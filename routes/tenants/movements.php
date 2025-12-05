<?php

use Illuminate\Support\Facades\Route;

Route::get('/movements', function () {
    return view('livewire.tenant.movements.movements');
})->name('movements.movements');
