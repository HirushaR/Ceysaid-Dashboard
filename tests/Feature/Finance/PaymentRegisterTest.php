<?php

namespace Tests\Feature\Finance;

use App\Filament\Pages\PaymentRegister;
use App\Models\CustomerPayment;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\User;
use App\Models\VendorBill;
use App\Models\VendorBillPayment;
use App\Services\PaymentRegisterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PaymentRegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_admin_and_account_users_can_access_payment_register(): void
    {
        foreach (['admin', 'account'] as $role) {
            $this->actingAs(User::factory()->create(['role' => $role]));
            $this->assertTrue(PaymentRegister::canAccess());
        }

        $this->actingAs(User::factory()->create(['role' => 'sales']));
        $this->assertFalse(PaymentRegister::canAccess());
    }

    public function test_account_user_can_render_register(): void
    {
        $account = User::factory()->create(['role' => 'account']);
        $this->actingAs($account);

        Livewire::test(PaymentRegister::class)
            ->assertOk()
            ->assertSee('Customer receipts')
            ->assertSee('Vendor payments');
    }

    public function test_register_combines_and_summarizes_payments_by_payment_date(): void
    {
        $lead = Lead::factory()->create();
        $invoice = Invoice::factory()->create([
            'lead_id' => $lead->id,
            'total_amount' => 1000,
        ]);

        CustomerPayment::create([
            'invoice_id' => $invoice->id,
            'amount' => 700,
            'payment_date' => '2026-07-30',
            'payment_method' => 'bank_transfer',
            'deposit_to' => 'ntb_current',
        ]);
        CustomerPayment::create([
            'invoice_id' => $invoice->id,
            'amount' => 100,
            'payment_date' => '2026-07-29',
            'payment_method' => 'cash',
            'deposit_to' => 'cash',
        ]);

        $bill = VendorBill::create([
            'invoice_id' => $invoice->id,
            'vendor_name' => 'Hotel Supplier',
            'vendor_bill_number' => 'VB/2026/TEST',
            'bill_amount' => 500,
            'service_type' => 'HOTEL',
            'payment_status' => 'pending',
        ]);
        VendorBillPayment::create([
            'vendor_bill_id' => $bill->id,
            'amount' => 250,
            'payment_date' => '2026-07-30',
            'payment_mode' => 'bank_transfer',
            'paid_through' => 'ntb_current',
        ]);

        $filters = [
            'date_from' => '2026-07-30',
            'date_to' => '2026-07-30',
        ];
        $service = app(PaymentRegisterService::class);
        $summary = $service->summary($filters);
        $rows = $service->paginate($filters);

        $this->assertSame(700.0, $summary['received']);
        $this->assertSame(250.0, $summary['paid']);
        $this->assertSame(450.0, $summary['net']);
        $this->assertSame(2, $summary['count']);
        $this->assertCount(2, $rows);
        $this->assertEqualsCanonicalizing(['in', 'out'], collect($rows->items())->pluck('direction')->all());
    }

    public function test_register_filters_by_direction_method_and_account(): void
    {
        $lead = Lead::factory()->create();
        $invoice = Invoice::factory()->create([
            'lead_id' => $lead->id,
            'total_amount' => 1000,
        ]);

        CustomerPayment::create([
            'invoice_id' => $invoice->id,
            'amount' => 300,
            'payment_date' => '2026-07-30',
            'payment_method' => 'cash',
            'deposit_to' => 'cash',
        ]);
        CustomerPayment::create([
            'invoice_id' => $invoice->id,
            'amount' => 200,
            'payment_date' => '2026-07-30',
            'payment_method' => 'bank_transfer',
            'deposit_to' => 'ntb_current',
        ]);

        $summary = app(PaymentRegisterService::class)->summary([
            'date_from' => '2026-07-30',
            'date_to' => '2026-07-30',
            'direction' => 'in',
            'payment_method' => 'cash',
            'account' => 'cash',
        ]);

        $this->assertSame(300.0, $summary['received']);
        $this->assertSame(0.0, $summary['paid']);
        $this->assertSame(1, $summary['count']);
    }
}
