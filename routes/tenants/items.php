<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Items\ItemsController;
use App\Http\Controllers\Inventory\CategoriesController;
use App\Http\Controllers\Inventory\BrandsController;
use App\Http\Controllers\Inventory\CommandsController;
use App\Http\Controllers\Inventory\HousesController;
use App\Http\Controllers\Inventory\UnitsMeasurementsController;

//Items
Route::prefix('/items')->group(function(){
    Route::get('/items', [ItemsController::class, 'homeItems'])->name('items');
});

//Categories
Route::prefix('/inventory')->group(function(){
    Route::get('/categories', [CategoriesController::class, 'homeCategories'])->name('categories');
});

//Brands
Route::prefix('/inventory')->group(function(){
    Route::get('/brands', [BrandsController::class, 'homeBrands'])->name('brands');
});

//Commands
Route::prefix('/inventory')->group(function(){
    Route::get('/commands', [CommandsController::class, 'homeCommands'])->name('commands');
});

//Houses
Route::prefix('/inventory')->group(function(){
    Route::get('/houses', [HousesController::class, 'homeHouses'])->name('houses');
});

//Unit Measurements
Route::prefix('/inventory')->group(function(){
    Route::get('/units', [UnitsMeasurementsController::class, 'homeUnits'])->name('units');
});