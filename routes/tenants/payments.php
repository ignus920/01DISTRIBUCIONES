<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\Tenant\PettyCash\PaymentQuote;

Route::get('/tenant/payment/quote/{quoteId?}', PaymentQuote::class)
    ->middleware(['auth', 'company.complete', \App\Auth\Middleware\SetTenantConnection::class])
    ->name('tenant.payment.quote');

// Route::get('/tenant/payment/payment-quote-old', function () {
//     return view('livewire.tenant.petty-cash.payment-quote-old');
// })->middleware(['auth', 'company.complete', \App\Auth\Middleware\SetTenantConnection::class])
//   ->name('tenant.payment.quote.old');


