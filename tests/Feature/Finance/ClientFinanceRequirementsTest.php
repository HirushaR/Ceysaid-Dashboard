<?php

namespace Tests\Feature\Finance;

use App\Enums\DepositAccount;
use App\Livewire\Admin\Expenses\Index as ExpenseIndex;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Supplier;
use App\Models\Tour;
use App\Models\User;
use App\Models\VendorBill;
use App\Services\PaymentRegisterService;
use App\Services\RecordSupplierPaymentService;
use App\Services\TourFinanceReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ClientFinanceRequirementsTest extends TestCase
{
    use RefreshDatabase;

    public function test_supplier_payment_can_be_recorded_without_vendor_bill(): void
    {
        $account = User::factory()->create(['role' => 'account']);
        $supplier = Supplier::create(['name' => 'Advance Supplier']);
        $this->actingAs($account);

        $payment = app(RecordSupplierPaymentService::class)->record([
            'supplier_id' => $supplier->id,
            'payment_date' => now()->toDateString(),
            'amount' => 125000,
            'payment_mode' => 'bank_transfer',
            'paid_through' => 'hnb_current',
            'reference_number' => 'ADV-100',
            'allocations' => [],
        ]);

        $this->assertCount(0, $payment->allocations);
        $this->get(route('admin.payments.supplier.show', $payment))
            ->assertOk()
            ->assertSee('without a vendor bill');
    }

    public function test_accounts_can_add_expense_and_it_is_in_outgoing_register(): void
    {
        $account = User::factory()->create(['role' => 'account']);
        $this->actingAs($account);

        Livewire::test(ExpenseIndex::class)
            ->set('category', 'Office')
            ->set('description', 'Printer supplies')
            ->set('amount', 4500)
            ->set('payment_mode', 'cash')
            ->set('paid_through', 'cash')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('expenses', ['description' => 'Printer supplies', 'created_by' => $account->id]);
        $summary = app(PaymentRegisterService::class)->summary(['direction' => 'out']);
        $this->assertSame(4500.0, $summary['paid']);

        $this->actingAs(User::factory()->create(['role' => 'sales']));
        $this->get(route('admin.expenses.index'))->assertForbidden();
    }

    public function test_common_tour_vendor_bill_is_counted_once_in_tour_profit(): void
    {
        $tour = Tour::factory()->create(['tour_type' => 'fixed_departure']);
        $supplier = Supplier::create(['name' => 'Group Hotel']);
        VendorBill::create([
            'tour_id' => $tour->id,
            'supplier_id' => $supplier->id,
            'vendor_name' => $supplier->name,
            'vendor_bill_number' => 'VB-COMMON-1',
            'bill_amount' => 200000,
            'due_date' => now()->addWeek(),
            'service_type' => 'Group accommodation',
            'payment_status' => 'pending',
        ]);

        $row = app(TourFinanceReportService::class)->tourWiseProfit(['tour_id' => $tour->id])->first();

        $this->assertSame(200000.0, $row['vendor_cost']);
        $this->assertSame(-200000.0, $row['gross_profit']);
    }

    public function test_invoice_snapshots_sales_person_and_selected_bank_account(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);
        $lead = Lead::factory()->create(['assigned_to' => $sales->id]);
        $invoice = Invoice::factory()->create([
            'lead_id' => $lead->id,
            'sales_person_id' => null,
            'bank_account' => 'seylan_current',
        ]);

        $this->assertSame($sales->id, $invoice->sales_person_id);
        $this->assertSame('seylan_current', $invoice->bank_account);
        $this->assertSame('139013690778001', config('ceysaid.company.bank_accounts.seylan_current.account_number'));
    }

    public function test_all_requested_pay_through_accounts_are_available(): void
    {
        $this->assertSame([
            'cash',
            'ntb_current',
            'ntb_saving',
            'seylan_saving',
            'seylan_current',
            'hnb_saving',
            'hnb_current',
        ], array_keys(DepositAccount::options()));
    }
}
