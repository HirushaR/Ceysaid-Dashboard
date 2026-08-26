<?php

use App\Http\Controllers\CustomerPaymentPdfController;
use App\Http\Controllers\InvoicePdfController;
use App\Http\Controllers\QuotePdfController;
use App\Http\Controllers\SupplierPayablePdfController;
use App\Http\Controllers\SupplierPayablesSummaryPdfController;
use App\Http\Controllers\VendorBillPdfController;
use App\Http\Controllers\WhatsAppMediaController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\Invoices\Index as InvoiceIndex;
use App\Livewire\Admin\Invoices\Show as InvoiceShow;
use App\Livewire\Admin\Leads\Create as LeadCreate;
use App\Livewire\Admin\Leads\Index as LeadIndex;
use App\Livewire\Admin\Leads\Show as LeadShow;
use App\Livewire\Admin\Payments\Index as PaymentIndex;
use App\Livewire\Admin\Quotes\Create as QuoteCreate;
use App\Livewire\Admin\Quotes\Index as QuoteIndex;
use App\Livewire\Admin\Quotes\Show as QuoteShow;
use App\Livewire\Admin\Suppliers\Index as SupplierIndex;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'admin.dashboard' : 'admin.login');
});

Route::middleware('guest')->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
});

Route::middleware('auth')->prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/', Dashboard::class)->name('dashboard');
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
    Route::get('/leads', LeadIndex::class)->name('leads.index');
    Route::get('/leads/create', LeadCreate::class)->name('leads.create');
    Route::get('/leads/{lead}', LeadShow::class)->name('leads.show');
    Route::get('/quotes', QuoteIndex::class)->name('quotes.index');
    Route::get('/quotes/create', QuoteCreate::class)->name('quotes.create');
    Route::get('/quotes/{quote}', QuoteShow::class)->name('quotes.show');
    Route::get('/invoices', InvoiceIndex::class)->name('invoices.index');
    Route::get('/invoices/{invoice}', InvoiceShow::class)->name('invoices.show');
    Route::get('/suppliers', SupplierIndex::class)->name('suppliers.index');
    Route::get('/payments', PaymentIndex::class)->name('payments.index');
    Route::get('/notifications', fn () => view('admin.notifications'))->name('notifications');
    Route::get('/module/{module?}', fn (string $module = 'module') => view('admin.module-placeholder', compact('module')))->name('module');
});

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/finance/invoices/{invoice}/pdf', InvoicePdfController::class)->name('finance.invoices.pdf');
    Route::get('/finance/quotes/{quote}/pdf', QuotePdfController::class)->name('finance.quotes.pdf');
    Route::get('/finance/vendor-bills/{vendorBill}/pdf', VendorBillPdfController::class)->name('finance.vendor-bills.pdf');
    Route::get('/finance/customer-payments/{customerPayment}/pdf', CustomerPaymentPdfController::class)->name('finance.customer-payments.pdf');
    Route::get('/finance/supplier-payables/pdf', SupplierPayablesSummaryPdfController::class)->name('finance.supplier-payables.summary-pdf');
    Route::get('/finance/supplier-payables/{supplier}/pdf', SupplierPayablePdfController::class)->name('finance.supplier-payables.pdf');
    Route::get('/admin/whatsapp-media/{message}', [WhatsAppMediaController::class, 'show'])->name('whatsapp.media');
});
