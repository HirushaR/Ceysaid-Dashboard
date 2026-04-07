<?php

namespace Tests\Feature\Finance;

use App\Models\Invoice;
use App\Models\Lead;
use App\Models\VendorBill;
use App\Models\VendorBillPayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VendorBillPaymentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendor_bill_can_be_paid_in_multiple_installments(): void
    {
        $lead = Lead::factory()->create();
        $invoice = Invoice::factory()->create(['lead_id' => $lead->id]);
        $bill = VendorBill::create([
            'invoice_id' => $invoice->id,
            'vendor_name' => 'Test Vendor',
            'vendor_bill_number' => 'VB-TEST-1',
            'bill_amount' => 100000,
            'service_type' => 'OTHER',
            'payment_status' => 'pending',
        ]);

        $this->assertSame('pending', $bill->fresh()->payment_status);
        $this->assertEquals(100000.0, $bill->fresh()->outstanding_amount);

        VendorBillPayment::create([
            'vendor_bill_id' => $bill->id,
            'amount' => 30000,
            'payment_date' => now(),
            'payment_mode' => 'bank_transfer',
            'paid_through' => 'ntb_current',
        ]);

        $bill->refresh();
        $this->assertSame('partial', $bill->payment_status);
        $this->assertEquals(70000.0, $bill->outstanding_amount);

        VendorBillPayment::create([
            'vendor_bill_id' => $bill->id,
            'amount' => 20000,
            'payment_date' => now(),
            'payment_mode' => 'bank_transfer',
            'paid_through' => 'ntb_current',
        ]);

        $bill->refresh();
        $this->assertSame('partial', $bill->payment_status);
        $this->assertEquals(50000.0, $bill->outstanding_amount);

        VendorBillPayment::create([
            'vendor_bill_id' => $bill->id,
            'amount' => 50000,
            'payment_date' => now(),
            'payment_mode' => 'bank_transfer',
            'paid_through' => 'ntb_current',
        ]);

        $bill->refresh();
        $this->assertSame('paid', $bill->payment_status);
        $this->assertEquals(0.0, $bill->outstanding_amount);
    }
}
