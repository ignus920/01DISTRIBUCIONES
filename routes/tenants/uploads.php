<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Tenant\Uploads\Uploads;
use App\Http\Controllers\Tenant\PrintDeliveryDetailController;
use App\Http\Controllers\Tenant\PrintOrdersDetailController;
use App\Http\Controllers\Tenant\PrintPreChargeController;

Route::prefix('/uploads')->middleware(['auth', 'verified', 'tenant'])->group(function () {
    //Ruta para la gestión de cargues
    Route::get('/uploads', Uploads::class)
    ->name('tenant.uploads.uploads');

    //Ruta para imprimir detalle de cargue (ventas)
    Route::get('/print-detail/{deliveryId}', [PrintDeliveryDetailController::class, 'show'])
    ->name('tenant.uploads.print-detail');

    //Ruta para imprimir pedidos de cargue por cliente
    Route::get('/print-orders/{deliveryId}', [PrintOrdersDetailController::class, 'show'])
    ->name('tenant.uploads.print-orders');

    //Ruta para pre-cargue (abre en navegador para imprimir)
    Route::get('/print-pre-charge/{deliverymanId}', [PrintPreChargeController::class, 'show'])
    ->name('tenant.uploads.print-pre-charge');
});