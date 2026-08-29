<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FactusController;

Route::prefix('factus')->name('factus.')->group(function () {

    // Facturas (Invoices)
    Route::get('/invoices', [FactusController::class, 'getInvoices'])->name('invoices.index');
    Route::post('/invoices', [FactusController::class, 'createInvoice'])->name('invoices.create');
    Route::get('/invoices/{id}', [FactusController::class, 'getInvoice'])->name('invoices.show');
    Route::get('/invoices/{id}/pdf', [FactusController::class, 'getInvoicePdf'])->name('invoices.pdf');
    Route::get('/invoices/{id}/xml', [FactusController::class, 'getInvoiceXml'])->name('invoices.xml');
    Route::post('/invoices/{id}/send', [FactusController::class, 'sendInvoice'])->name('invoices.send');
    Route::delete('/invoices/{id}', [FactusController::class, 'deleteInvoice'])->name('invoices.delete');

    // Notas de crédito (Credit Notes)
    Route::get('/credit-notes', [FactusController::class, 'getCreditNotes'])->name('credit-notes.index');
    Route::post('/credit-notes', [FactusController::class, 'createCreditNote'])->name('credit-notes.create');
    Route::get('/credit-notes/{id}', [FactusController::class, 'getCreditNote'])->name('credit-notes.show');
    Route::get('/credit-notes/{id}/pdf', [FactusController::class, 'getCreditNotePdf'])->name('credit-notes.pdf');

    // Configuración y catálogos
    Route::get('/payment-methods', [FactusController::class, 'getPaymentMethods'])->name('payment-methods');
    Route::get('/document-types', [FactusController::class, 'getDocumentTypes'])->name('document-types');

    // Gestión de tokens
    Route::post('/token/renew', [FactusController::class, 'renewToken'])->name('token.renew');
});