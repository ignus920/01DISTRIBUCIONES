<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Tenant\Uploads\Uploads;

Route::prefix('/uploads')->group(function () {
    //Ruta para la gestión de cargues
    Route::get('/uploads', Uploads::class)
    ->name('tenant.uploads.uploads');
});