<?php


use Illuminate\Support\Facades\Route;
use App\Auth\Livewire\SelectTenant;

Route::get('/petty-cash', function (){
    return view('livewire.tenant.petty-cash.home-petty-cash');
})->name('petty-cash.petty-cash');
?>