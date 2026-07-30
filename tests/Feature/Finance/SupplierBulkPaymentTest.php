<?php

namespace Tests\Feature\Finance;

use App\Filament\Resources\SupplierPaymentResource;
use App\Filament\Resources\SupplierPaymentResource\Pages\CreateSupplierPayment;
use App\Filament\Resources\SupplierPaymentResource\Pages\ViewSupplierPayment;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Supplier;
use App\Models\User;
use App\Models\VendorBill;
use App\Services\PaymentRegisterService;
use App\Services\RecordSupplierPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use Tests\TestCase;

class SupplierBulkPaymentTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_payment_allocates_one_transaction_across_multiple_vendor_bills(): void
    {
        $account = User::factory()->create(['role' => 'account']);
        $this->actingAs($account);

        $supplier = Supplier::create(['name' => 'Hotel Group']);
        $lead = Lead::factory()->create();
        $invoice = Invoice::factory()->create(['lead_id' => $lead->id]);
        $firstBill = $this->createBill($invoice, $supplier, 'VB-ONE', 300);
        $secondBill = $this->createBill($invoice, $supplier, 'VB-TWO', 400);

        $payment = app(RecordSupplierPaymentService::class)->record([
            'supplier_id' => $supplier->id,
            'payment_date' => '2026-07-30',
            'amount' => 500,
            'payment_mode' => 'cheque',
            'paid_through' => 'ntb_current',
            'reference_number' => 'CHQ-10025',
            'notes' => 'Weekly supplier settlement',
            'allocations' => [
                ['vendor_bill_id' => $firstBill->id, 'amount' => 300],
                ['vendor_bill_id' => $secondBill->id, 'amount' => 200],
            ],
        ]);

        $this->assertSame('SP/2026/00001', $payment->payment_number);
        $this->assertSame($account->id, $payment->created_by);
        $this->assertCount(2, $payment->allocations);
        $this->assertEquals(500.0, (float) $payment->allocations->sum('amount'));
        $this->assertSame('paid', $firstBill->fresh()->payment_status);
        $this->assertSame('partial', $secondBill->fresh()->payment_status);
        $this->assertEquals(200.0, $secondBill->fresh()->outstanding_amount);

        $summary = app(PaymentRegisterService::class)->summary([
            'date_from' => '2026-07-30',
            'date_to' => '2026-07-30',
            'direction' => 'out',
        ]);
        $rows = app(PaymentRegisterService::class)->paginate([
            'date_from' => '2026-07-30',
            'date_to' => '2026-07-30',
            'direction' => 'out',
        ]);

        $this->assertSame(500.0, $summary['paid']);
        $this->assertSame(1, $summary['count']);
        $this->assertCount(1, $rows);
        $this->assertSame($payment->id, (int) $rows->first()->supplier_payment_id);

        Livewire::test(ViewSupplierPayment::class, ['record' => $payment->getRouteKey()])
            ->assertSee('Bill status')
            ->assertSee('Paid')
            ->assertSee('Partial');
    }

    public function test_bulk_payment_rejects_over_allocation_and_mismatched_totals(): void
    {
        $supplier = Supplier::create(['name' => 'Airline']);
        $lead = Lead::factory()->create();
        $invoice = Invoice::factory()->create(['lead_id' => $lead->id]);
        $bill = $this->createBill($invoice, $supplier, 'VB-AIR', 300);

        try {
            app(RecordSupplierPaymentService::class)->record([
                'supplier_id' => $supplier->id,
                'payment_date' => '2026-07-30',
                'amount' => 400,
                'payment_mode' => 'bank_transfer',
                'paid_through' => 'ntb_current',
                'allocations' => [
                    ['vendor_bill_id' => $bill->id, 'amount' => 400],
                ],
            ]);
            $this->fail('Expected over-allocation validation to fail.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('allocations.0.amount', $exception->errors());
        }

        $this->expectException(ValidationException::class);
        app(RecordSupplierPaymentService::class)->record([
            'supplier_id' => $supplier->id,
            'payment_date' => '2026-07-30',
            'amount' => 250,
            'payment_mode' => 'bank_transfer',
            'paid_through' => 'ntb_current',
            'allocations' => [
                ['vendor_bill_id' => $bill->id, 'amount' => 200],
            ],
        ]);
    }

    public function test_only_admin_and_account_can_manage_supplier_payments(): void
    {
        $payment = new \App\Models\SupplierPayment;

        foreach (['admin', 'account'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]));
            $this->assertTrue(SupplierPaymentResource::canViewAny());
            $this->assertTrue(SupplierPaymentResource::canCreate());
            $this->assertFalse(SupplierPaymentResource::canEdit($payment));
            $this->assertFalse(SupplierPaymentResource::canDelete($payment));
        }

        $this->actingAs(User::factory()->create(['role' => 'sales']));
        $this->assertFalse(SupplierPaymentResource::canViewAny());
        $this->assertFalse(SupplierPaymentResource::canCreate());
    }

    public function test_account_user_can_render_bulk_payment_form(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'account']));

        Livewire::test(CreateSupplierPayment::class)
            ->assertOk()
            ->assertSee('Bill allocations')
            ->assertSee('Cheque / transfer reference');
    }

    public function test_selecting_bill_defaults_allocation_to_outstanding_balance_and_updates_summary(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'account']));
        $supplier = Supplier::create(['name' => 'DMC']);
        $lead = Lead::factory()->create();
        $invoice = Invoice::factory()->create(['lead_id' => $lead->id]);
        $bill = $this->createBill($invoice, $supplier, 'VB-DMC', 300);

        Livewire::test(CreateSupplierPayment::class)
            ->set('data.supplier_id', $supplier->id)
            ->set('data.amount', 500)
            ->set('data.allocations', [['vendor_bill_id' => null, 'amount' => null]])
            ->set('data.allocations.0.vendor_bill_id', $bill->id)
            ->assertSet('data.allocations.0.amount', 300.0)
            ->assertSee('LKR 200.00 left');
    }

    public function test_bulk_payment_form_accepts_todays_payment_date(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'account']));
        $supplier = Supplier::create(['name' => 'Today Supplier']);
        $lead = Lead::factory()->create();
        $invoice = Invoice::factory()->create(['lead_id' => $lead->id]);
        $bill = $this->createBill($invoice, $supplier, 'VB-TODAY', 300);

        Livewire::test(CreateSupplierPayment::class)
            ->fillForm([
                'supplier_id' => $supplier->id,
                'amount' => 300,
                'payment_date' => now()->toDateString(),
                'payment_mode' => 'cheque',
                'paid_through' => 'ntb_current',
                'reference_number' => 'CHQ-TODAY',
                'allocations' => [
                    ['vendor_bill_id' => $bill->id, 'amount' => 300],
                ],
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('supplier_payments', [
            'supplier_id' => $supplier->id,
            'amount' => 300,
        ]);
        $this->assertSame(
            now()->toDateString(),
            \App\Models\SupplierPayment::query()->firstOrFail()->payment_date->toDateString()
        );
    }

    private function createBill(Invoice $invoice, Supplier $supplier, string $number, float $amount): VendorBill
    {
        return VendorBill::create([
            'invoice_id' => $invoice->id,
            'supplier_id' => $supplier->id,
            'vendor_name' => $supplier->name,
            'vendor_bill_number' => $number,
            'bill_amount' => $amount,
            'service_type' => 'OTHER',
            'payment_status' => 'pending',
        ]);
    }
}
