<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\LeadCost;
use App\Models\Invoice;
use App\Models\VendorBill;
use App\Models\Lead;

class MigrateLeadCostsToInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'migrate:lead-costs-to-invoices {--dry-run : Show what would be migrated without actually migrating}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Migrate existing LeadCosts to the new Invoice and VendorBill system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->info('🔍 DRY RUN - No data will be migrated');
        }

        $leadCosts = LeadCost::with('lead')->get();
        $this->info("Found {$leadCosts->count()} lead costs to migrate");

        if ($leadCosts->isEmpty()) {
            $this->info('No lead costs found to migrate.');
            return 0;
        }

        $migrated = 0;
        $errors = 0;
        $skipped = 0;

        foreach ($leadCosts as $leadCost) {
            if (!$leadCost->lead) {
                $this->warn("⚠️  Lead cost {$leadCost->id} has no associated lead. Skipping.");
                $skipped++;
                continue;
            }

            // Check if an invoice with this invoice_number already exists for this lead
            $existingInvoice = Invoice::where('lead_id', $leadCost->lead_id)
                ->where('invoice_number', $leadCost->invoice_number)
                ->first();

            if ($existingInvoice) {
                if ($dryRun) {
                    $this->line("Would skip (invoice already exists): {$leadCost->invoice_number}");
                } else {
                    $this->line("⏭️  Invoice already exists: {$leadCost->invoice_number}");
                    $skipped++;
                }
                continue;
            }

            if ($dryRun) {
                $this->line("Would migrate: Lead {$leadCost->lead->reference_id} - Invoice {$leadCost->invoice_number} (LKR {$leadCost->amount})");
                continue;
            }

            try {
                // Create the Invoice
                $invoice = Invoice::create([
                    'lead_id' => $leadCost->lead_id,
                    'invoice_number' => $leadCost->invoice_number,
                    'total_amount' => $leadCost->amount,
                    'description' => $leadCost->details ?: 'Migrated from lead costs',
                    'status' => $leadCost->is_paid ? 'paid' : 'pending',
                    'payment_amount' => $leadCost->is_paid ? $leadCost->amount : null,
                    'payment_date' => $leadCost->is_paid ? $leadCost->updated_at : null,
                    'created_at' => $leadCost->created_at,
                    'updated_at' => $leadCost->updated_at,
                ]);

                // Create the VendorBill if vendor information exists
                if ($leadCost->vendor_bill && $leadCost->vendor_amount) {
                    VendorBill::create([
                        'invoice_id' => $invoice->id,
                        'vendor_name' => 'Migrated Vendor', // Default name since old system didn't track vendor names
                        'vendor_bill_number' => $leadCost->vendor_bill,
                        'bill_amount' => $leadCost->vendor_amount,
                        'service_type' => 'OTHER', // Default service type
                        'service_details' => $leadCost->details ?: 'Migrated from lead costs',
                        'payment_status' => $leadCost->is_paid ? 'paid' : 'pending',
                        'payment_date' => $leadCost->is_paid ? $leadCost->updated_at : null,
                        'created_at' => $leadCost->created_at,
                        'updated_at' => $leadCost->updated_at,
                    ]);
                }

                $this->info("✅ Migrated: {$leadCost->invoice_number} for Lead {$leadCost->lead->reference_id}");
                $migrated++;
                
            } catch (\Exception $e) {
                $this->error("❌ Failed to migrate {$leadCost->invoice_number}: " . $e->getMessage());
                $errors++;
            }
        }

        if (!$dryRun) {
            $this->info("\n📊 Migration Summary:");
            $this->info("✅ Successfully migrated: {$migrated}");
            $this->info("⏭️  Skipped (already migrated): {$skipped}");
            $this->info("❌ Errors: {$errors}");
            
            if ($migrated > 0) {
                $this->info("\n💡 Next steps:");
                $this->info("1. Verify the migrated data in the admin panel");
                $this->info("2. Test the new invoice system thoroughly");
                $this->info("3. Consider archiving the old lead_costs table once satisfied");
                $this->info("4. Update any remaining references to lead_costs in your codebase");
            }
        } else {
            $this->info("\nRun without --dry-run to perform actual migration");
        }

        return 0;
    }
}
