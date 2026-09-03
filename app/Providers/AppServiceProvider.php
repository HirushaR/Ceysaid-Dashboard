<?php

namespace App\Providers;

use App\Models\CustomerPayment;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Quote;
use App\Models\VendorBill;
use App\Models\Customer;
use App\Models\Leave;
use App\Models\Supplier;
use App\Models\Tour;
use App\Models\User;
use App\Policies\CustomerPolicy;
use App\Policies\InvoicePolicy;
use App\Policies\LeadPolicy;
use App\Policies\LeavePolicy;
use App\Policies\QuotePolicy;
use App\Policies\SupplierPolicy;
use App\Policies\TourPolicy;
use App\Policies\VendorBillPolicy;
use App\Observers\CustomerPaymentObserver;
use App\Observers\InvoiceObserver;
use App\Observers\LeadObserver;
use App\Observers\QuoteObserver;
use App\Observers\VendorBillObserver;
use App\Observers\AuditObserver;
use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Models\SupplierPayment;
use App\Models\OfficeClosure;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Auth\Notifications\ResetPassword;

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
        ResetPassword::createUrlUsing(fn (User $user, string $token) => route('admin.password.reset', ['token' => $token, 'email' => $user->email]));
        Gate::policy(Lead::class, LeadPolicy::class);
        Gate::policy(Invoice::class, InvoicePolicy::class);
        Gate::policy(Quote::class, QuotePolicy::class);
        Gate::policy(Supplier::class, SupplierPolicy::class);
        Gate::policy(VendorBill::class, VendorBillPolicy::class);
        Gate::policy(Customer::class, CustomerPolicy::class);
        Gate::policy(Tour::class, TourPolicy::class);
        Gate::policy(Leave::class, LeavePolicy::class);

        // Register Lead Observer for automatic notifications
        Lead::observe(LeadObserver::class);

        Invoice::observe(InvoiceObserver::class);
        Quote::observe(QuoteObserver::class);
        VendorBill::observe(VendorBillObserver::class);
        CustomerPayment::observe(CustomerPaymentObserver::class);
        foreach ([User::class, Supplier::class, SupplierPayment::class, Permission::class, PermissionGroup::class, Tour::class, Leave::class, OfficeClosure::class] as $model) {
            $model::observe(AuditObserver::class);
        }
    }
}
