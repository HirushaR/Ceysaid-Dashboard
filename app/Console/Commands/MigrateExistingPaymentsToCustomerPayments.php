<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\CustomerPayment;
use Illuminate\Console\Command;

class MigrateExistingPaymentsToCustomerPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:customer-payments 
                            {--dry-run : Show what would be migrated without actually doing it}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate existing payment data from invoices to customer_payments table';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $isDryRun = $this->option('dry-run');
        
        if ($isDryRun) {
            $this->info('🔍 DRY RUN MODE - No changes will be made');
        }

        // Find invoices with payment data
        $invoicesWithPayments = Invoice::where('payment_amount', '>', 0)
            ->orWhere('payment_date', '!=', null)
            ->orWhere('receipt_number', '!=', null)
            ->get();

        if ($invoicesWithPayments->isEmpty()) {
            $this->info('✅ No existing payment data found to migrate');
            return 0;
        }

        $this->info("📊 Found {$invoicesWithPayments->count()} invoices with payment data");

        $migratedCount = 0;
        $skippedCount = 0;

        foreach ($invoicesWithPayments as $invoice) {
            // Check if customer payment already exists for this invoice
            if ($invoice->customerPayments()->exists()) {
                $this->warn("⚠️  Invoice {$invoice->invoice_number} already has customer payments - skipping");
                $skippedCount++;
                continue;
            }

            // Only migrate if there's actually a payment amount
            if ($invoice->payment_amount > 0) {
                $paymentData = [
                    'invoice_id' => $invoice->id,
                    'amount' => $invoice->payment_amount,
                    'payment_date' => $invoice->payment_date ?? today(),
                    'receipt_number' => $invoice->receipt_number,
                    'payment_method' => null, // We don't have this data in old system
                    'notes' => 'Migrated from invoice payment data',
                ];

                if (!$isDryRun) {
                    CustomerPayment::create($paymentData);
                    // The CustomerPayment model will automatically update the invoice status
                    $this->info("✅ Migrated payment for invoice {$invoice->invoice_number}: $" . number_format($invoice->payment_amount, 2));
                } else {
                    $this->info("📋 Would migrate payment for invoice {$invoice->invoice_number}: $" . number_format($invoice->payment_amount, 2));
                }

                $migratedCount++;
            } else {
                $this->info("⚪ Invoice {$invoice->invoice_number} has payment data but zero amount - skipping");
                $skippedCount++;
            }
        }

        if (!$isDryRun) {
            $this->info("\n🎉 Migration completed!");
            $this->info("✅ Migrated: {$migratedCount} payments");
            $this->info("⚪ Skipped: {$skippedCount} invoices");
            
            if ($migratedCount > 0) {
                $this->info("\n💡 Note: Invoice payment statuses have been automatically updated based on the migrated payments.");
            }
        } else {
            $this->info("\n📋 DRY RUN COMPLETE");
            $this->info("Would migrate: {$migratedCount} payments");
            $this->info("Would skip: {$skippedCount} invoices");
            $this->info("\n💡 Run without --dry-run to perform the actual migration");
        }

        return 0;
    }
}