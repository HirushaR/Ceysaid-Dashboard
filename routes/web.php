<?php

use App\Http\Controllers\CustomerPaymentPdfController;
use App\Http\Controllers\InvoicePdfController;
use App\Http\Controllers\QuotePdfController;
use App\Http\Controllers\VendorBillPdfController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/finance/invoices/{invoice}/pdf', InvoicePdfController::class)->name('finance.invoices.pdf');
    Route::get('/finance/quotes/{quote}/pdf', QuotePdfController::class)->name('finance.quotes.pdf');
    Route::get('/finance/vendor-bills/{vendorBill}/pdf', VendorBillPdfController::class)->name('finance.vendor-bills.pdf');
    Route::get('/finance/customer-payments/{customerPayment}/pdf', CustomerPaymentPdfController::class)->name('finance.customer-payments.pdf');
});
