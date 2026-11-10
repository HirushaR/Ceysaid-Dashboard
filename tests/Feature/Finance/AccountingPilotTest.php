<?php

namespace Tests\Feature\Finance;

use App\Enums\TourStatus;
use App\Livewire\Admin\Finance\AccountingPilotReport;
use App\Livewire\Admin\Invoices\Accounting;
use App\Livewire\Admin\Invoices\RecordPayment;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Supplier;
use App\Models\Tour;
use App\Models\User;
use App\Services\AccountingPilot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class AccountingPilotTest extends TestCase
{
    use RefreshDatabase;

    public function test_principal_tour_crosses_year_end_with_separate_receipts_and_delivery(): void
    {
        [$invoice, $maker, $checker] = $this->pilot();
        $this->actingAs($maker)->get(route('admin.invoices.show', $invoice))->assertSee('Booking accounting');
        Livewire::actingAs($maker)->test(Accounting::class, ['invoice' => $invoice])
            ->set('policyEvidence', 'Approved tax-exclusive principal tour policy')
            ->call('enroll')->assertHasNoErrors();
        $this->assertDatabaseCount('accounting_journals', 0);
        $this->get(route('admin.invoices.edit', $invoice))->assertStatus(409);
        $this->get(route('finance.invoices.pdf', $invoice))->assertOk();

        $receipt = $this->recordReceipt($maker, $invoice, '100000.00', '2027-03-20');
        $this->assertDatabaseCount('accounting_journals', 0);

        Livewire::actingAs($checker)->test(AccountingPilotReport::class)
            ->set('closeThrough', '2027-03-31')->set('closeEvidence', 'Close checklist')
            ->call('close')->assertHasErrors('accounting');

        $marchJournal = $this->submit($maker, $invoice, ['receiptId' => $receipt->id]);
        $this->assertDatabaseHas('accounting_journals', ['id' => $marchJournal, 'posted_at' => null]);

        $this->approve($checker, $invoice, $marchJournal);
        $this->approve($checker, $invoice, $marchJournal);
        $this->assertDatabaseCount('accounting_journal_lines', 2);
        $this->actingAs($maker)->get(route('finance.customer-payments.pdf', $receipt))->assertOk();
        $this->get(route('finance.customer-payments.pdf', $receipt))->assertOk();
        $this->assertDatabaseCount('accounting_journals', 1);
        $this->assertSame('2027-03-20', $receipt->payment_date->toDateString());

        $pilot = app(AccountingPilot::class);
        $this->assertSame(10_000_000, $this->net('bank:ntb_current', '2027-03-31'));
        $this->assertSame(-10_000_000, $this->net('2120', '2027-03-31'));
        $this->assertSame(0, $this->net('4100', '2027-03-31'));

        Livewire::actingAs($checker)->test(AccountingPilotReport::class)
            ->set('closeThrough', '2027-03-31')->set('closeEvidence', 'March statements and customer schedule reconciled')
            ->call('close')->assertHasNoErrors()->assertSee('2027-03-31')
            ->set('asOf', '2027-03-31')->call('runReport')->assertSee('100,000.00');
        $this->assertDatabaseHas('audit_logs', ['event' => 'AccountingPilot.period_closed', 'user_id' => $checker->id]);

        Livewire::actingAs($maker)->test(RecordPayment::class, ['invoice' => $invoice])
            ->set('amount', '10.00')->set('payment_date', '2027-03-31')
            ->call('save')->assertHasErrors('accounting');
        $this->assertSame(1, $invoice->customerPayments()->count());

        $second = $this->recordReceipt($maker, $invoice, '250000.00', '2027-05-15');
        $this->assertNotSame($receipt->receipt_number, $second->receipt_number);
        $this->approve($checker, $invoice, $this->submit($maker, $invoice, ['receiptId' => $second->id]));
        Livewire::actingAs($checker)->test(AccountingPilotReport::class)
            ->set('closeThrough', '2027-06-30')->set('closeEvidence', 'Close checklist')
            ->call('close')->assertHasErrors('accounting')->assertSee('Recognition due');
        $invoice->tour->update(['status' => TourStatus::Departed]);
        $this->approve($checker, $invoice, $this->submit($maker, $invoice, [
            'event' => 'delivery', 'postingDate' => '2027-06-20',
        ]));
        $supplier = Supplier::create(['name' => 'Tour operator']);
        $this->approve($checker, $invoice, $this->submit($maker, $invoice, [
            'event' => 'accrual', 'postingDate' => '2027-06-20', 'supplierId' => $supplier->id, 'amount' => '280000.00',
        ]));

        $this->assertSame(0, $this->net('2120', '2027-06-30'));
        $this->assertSame(-35_000_000, $this->net('4100', '2027-06-30'));
        $this->assertSame(28_000_000, $this->net('5170', '2027-06-30'));
        $this->assertSame(7_000_000, -($this->net('4100', '2027-06-30') + $this->net('5170', '2027-06-30')));
        $this->assertSame(-$this->net('2140', '2027-06-30'), (int) $pilot->supplierAccruals('2027-06-30')->sole()->balance_minor);
        $this->assertSame(-$this->net('2120', '2027-03-31'), (int) $pilot->customerBalances('2027-03-31')->sole()->advance_minor);
        $this->assertSame(0, $pilot->trialBalance('2027-06-30')->sum(fn ($row) => $row->debit_minor - $row->credit_minor));
        Livewire::actingAs($checker)->test(AccountingPilotReport::class)
            ->set('closeThrough', '2027-06-30')->set('closeEvidence', 'June accounts reconciled')
            ->call('close')->assertHasNoErrors();
        $this->assertSame('2027-06-30', $pilot->state()->closed_through);
    }

    public function test_advance_billing_creates_receivable_and_later_payment_reduces_it(): void
    {
        [$invoice, $maker, $checker] = $this->pilot();
        $pilot = app(AccountingPilot::class);
        $pilot->enroll($maker, $invoice->id, 'Principal and tax-exclusive policy');
        $this->approve($checker, $invoice, $this->submit($maker, $invoice, [
            'event' => 'billing', 'postingDate' => '2027-03-10',
        ]));
        $receipt = $this->recordReceipt($maker, $invoice, '100000.00', '2027-03-20');
        $this->approve($checker, $invoice, $this->submit($maker, $invoice, ['receiptId' => $receipt->id]));

        $this->assertSame(25_000_000, $this->net('1140', '2027-03-31'));
        $this->assertSame(-35_000_000, $this->net('2120', '2027-03-31'));
        $this->assertSame(0, $this->net('4100', '2027-03-31'));

        $invoice->tour->update(['status' => TourStatus::Departed]);
        $this->approve($checker, $invoice, $this->submit($maker, $invoice, [
            'event' => 'delivery', 'postingDate' => '2027-06-20',
        ]));
        $this->assertSame(-35_000_000, $this->net('4100', '2027-06-30'));
        $this->assertSame(25_000_000, $this->net('1140', '2027-06-30'));
        $late = $this->recordReceipt($maker, $invoice, '250000.00', '2027-07-01');
        $this->approve($checker, $invoice, $this->submit($maker, $invoice, ['receiptId' => $late->id]));
        $this->assertSame(0, $this->net('1140', '2027-07-01'));
        $this->assertSame(0, $this->net('2120', '2027-07-01'));
    }

    public function test_deposit_before_billing_only_bills_the_remaining_amount_and_retry_deduplicates(): void
    {
        [$invoice, $maker, $checker] = $this->pilot();
        $pilot = app(AccountingPilot::class);
        $pilot->enroll($maker, $invoice->id, 'Principal and tax-exclusive policy');
        $receipt = $this->recordReceipt($maker, $invoice, '100000.01', '2027-03-20');
        $input = ['event' => 'receipt', 'customer_payment_id' => $receipt->id, 'evidence' => 'Verified bank reference'];
        $journal = $pilot->submit($maker, $invoice->id, $input);
        $this->assertSame($journal, $pilot->submit($maker, $invoice->id, $input));
        $this->approve($checker, $invoice, $journal);
        $this->assertSame($journal, $pilot->submit($maker, $invoice->id, $input));
        $this->approve($checker, $invoice, $this->submit($maker, $invoice, [
            'event' => 'billing', 'postingDate' => '2027-03-22',
        ]));
        $this->assertSame(24_999_999, $this->net('1140', '2027-03-31'));
        $this->assertSame(-35_000_000, $this->net('2120', '2027-03-31'));
        $this->assertDatabaseCount('accounting_journals', 2);
    }

    public function test_pilot_authorization_and_review_controls(): void
    {
        [$invoice, $maker, $checker] = $this->pilot();
        $this->actingAs(User::factory()->create(['role' => 'sales']))
            ->get(route('admin.invoices.accounting', $invoice))->assertForbidden();
        config(['accounting.pilot_enabled' => false]);
        $this->actingAs($maker)->get(route('admin.accounting-pilot.index'))->assertNotFound();
        config(['accounting.pilot_enabled' => true]);

        app(AccountingPilot::class)->enroll($checker, $invoice->id, 'Principal and tax-exclusive policy');
        $journal = $this->submit($checker, $invoice, ['event' => 'billing', 'postingDate' => '2027-03-22']);
        Livewire::actingAs($checker)->test(Accounting::class, ['invoice' => $invoice])
            ->call('approve', $journal)->assertForbidden();
        Livewire::actingAs($maker)->test(Accounting::class, ['invoice' => $invoice])
            ->call('approve', $journal)->assertForbidden();
        $otherAdmin = User::factory()->create(['role' => 'admin']);
        Livewire::actingAs($otherAdmin)->test(Accounting::class, ['invoice' => $invoice])
            ->set('rejectionReason', 'Contract missing')->call('reject', $journal)->assertHasNoErrors();
        $receipt = $this->recordReceipt($otherAdmin, $invoice, '100000.00', '2027-03-20');
        $receiptJournal = $this->submit($maker, $invoice, ['receiptId' => $receipt->id]);
        Livewire::actingAs($otherAdmin)->test(Accounting::class, ['invoice' => $invoice])
            ->call('approve', $receiptJournal)->assertForbidden();
        $this->approve($checker, $invoice, $receiptJournal);
        $replacement = $this->submit($checker, $invoice, ['event' => 'billing', 'postingDate' => '2027-03-22']);
        $this->assertNotSame($journal, $replacement);
        $this->assertDatabaseHas('accounting_journals', ['id' => $journal, 'rejection_reason' => 'Contract missing', 'posted_at' => null]);
        $this->assertSame(0, $this->net('1140', '2027-03-31'));
    }

    private function pilot(): array
    {
        $this->travelTo(now()->setDate(2027, 7, 15)->setTime(12, 0));
        config(['accounting.pilot_enabled' => true, 'accounting.pilot_start_date' => '2027-03-01']);
        $tour = Tour::factory()->create(['departure_date' => '2027-06-12', 'return_date' => '2027-06-20']);
        $lead = Lead::factory()->groupLead()->create(['tour_id' => $tour->id]);
        $invoice = Invoice::factory()->create([
            'lead_id' => $lead->id, 'invoice_date' => '2027-03-05', 'total_amount' => '350000.00',
        ]);

        return [$invoice, User::factory()->create(['role' => 'account']), User::factory()->create(['role' => 'admin'])];
    }

    private function recordReceipt(User $maker, Invoice $invoice, string $amount, string $date)
    {
        Livewire::actingAs($maker)->test(RecordPayment::class, ['invoice' => $invoice])
            ->set('amount', $amount)->set('payment_date', $date)->set('deposit_to', 'ntb_current')
            ->call('save')->assertHasNoErrors();

        return $invoice->customerPayments()->latest('id')->firstOrFail();
    }

    private function submit(User $maker, Invoice $invoice, array $fields): int
    {
        Livewire::actingAs($maker)->test(Accounting::class, ['invoice' => $invoice])
            ->set($fields)->set('evidence', 'Verified source evidence')
            ->call('submit')->assertHasNoErrors();

        return (int) DB::table('accounting_journals')->where('invoice_id', $invoice->id)->max('id');
    }

    private function approve(User $checker, Invoice $invoice, int $journal): void
    {
        Livewire::actingAs($checker)->test(Accounting::class, ['invoice' => $invoice])
            ->call('approve', $journal)->assertHasNoErrors();
    }

    private function net(string $code, string $date): int
    {
        $row = app(AccountingPilot::class)->trialBalance($date)->firstWhere('account_code', $code);

        return $row ? $row->debit_minor - $row->credit_minor : 0;
    }
}
