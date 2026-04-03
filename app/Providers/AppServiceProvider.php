<?php

namespace App\Providers;

use App\Models\CustomerPayment;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Quote;
use App\Models\VendorBill;
use App\Observers\CustomerPaymentObserver;
use App\Observers\InvoiceObserver;
use App\Observers\LeadObserver;
use App\Observers\QuoteObserver;
use App\Observers\VendorBillObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register Lead Observer for automatic notifications
        Lead::observe(LeadObserver::class);

        Invoice::observe(InvoiceObserver::class);
        Quote::observe(QuoteObserver::class);
        VendorBill::observe(VendorBillObserver::class);
        CustomerPayment::observe(CustomerPaymentObserver::class);
    }
}
