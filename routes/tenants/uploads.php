<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Tenant\Uploads\Uploads;
use App\Http\Controllers\Tenant\PrintDeliveryDetailController;
use App\Http\Controllers\Tenant\PrintOrdersDetailController;

Route::prefix('/uploads')->group(function () {
    //Ruta para la gestión de cargues
    Route::get('/uploads', Uploads::class)
    ->name('tenant.uploads.uploads');
    
    //Ruta para imprimir detalle de cargue (ventas)
    Route::get('/print-detail/{deliveryId}', [PrintDeliveryDetailController::class, 'show'])
    ->name('tenant.uploads.print-detail');
    
    //Ruta para imprimir pedidos de cargue por cliente
    Route::get('/print-orders/{deliveryId}', [PrintOrdersDetailController::class, 'show'])
    ->name('tenant.uploads.print-orders');
});