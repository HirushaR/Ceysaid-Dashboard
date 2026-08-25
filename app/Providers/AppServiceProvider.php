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
use App\Support\MigrationExecutionContext;
use App\Support\WorkflowMutationContext;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(MigrationExecutionContext::class);
        $this->app->singleton(WorkflowMutationContext::class);
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
