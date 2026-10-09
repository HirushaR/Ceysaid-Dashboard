<?php

namespace Tests\Feature\Finance;

use App\Livewire\Admin\Finance\Transfers;
use App\Models\CustomerPayment;
use App\Models\InternalTransfer;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Supplier;
use App\Models\User;
use App\Models\VendorBill;
use App\Services\BankStatementService;
use App\Services\PaymentRegisterService;
use App\Services\RecordSupplierPaymentService;
use App\Services\UpdateSupplierPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use ZipArchive;

class FinanceLedgerEnhancementTest extends TestCase
{
    use RefreshDatabase;

    public function test_internal_transfer_updates_both_ledgers_but_not_net_cash_movement(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'account']));

        Livewire::test(Transfers::class)
            ->set('transfer_date', '2026-10-09')
            ->set('from_account', 'cash')
            ->set('to_account', 'ntb_current')
            ->set('amount', 500)
            ->set('reference_number', 'DEP-100')
            ->call('save')
            ->assertHasNoErrors();

        $transfer = InternalTransfer::firstOrFail();
        $this->assertSame('TRF/2026/00001', $transfer->transfer_number);
        $summary = app(PaymentRegisterService::class)->summary([]);
        $this->assertSame(500.0, $summary['transfers']);
        $this->assertSame(0.0, $summary['net']);

        $cash = app(BankStatementService::class)->statement('cash', '2026-10-01', '2026-10-31');
        $bank = app(BankStatementService::class)->statement('ntb_current', '2026-10-01', '2026-10-31');
        $this->assertSame(500.0, $cash['debits']);
        $this->assertSame(500.0, $bank['credits']);
    }

    public function test_any_name_search_finds_customer_and_sales_person(): void
    {
        $sales = User::factory()->create(['role' => 'sales', 'name' => 'Nimali Fernando']);
        $lead = Lead::factory()->create(['customer_name' => 'Kasun Perera']);
        $invoice = Invoice::factory()->create(['lead_id' => $lead->id, 'sales_person_id' => $sales->id]);
        CustomerPayment::create(['invoice_id' => $invoice->id, 'amount' => 100, 'payment_date' => '2026-10-09', 'payment_method' => 'cash', 'deposit_to' => 'cash']);

        $service = app(PaymentRegisterService::class);
        $this->assertCount(1, $service->all(['search' => 'Kasun']));
        $this->assertCount(1, $service->all(['search' => 'Nimali']));
    }

    public function test_supplier_payment_edit_reallocates_bills_and_records_reason(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'account']));
        $supplier = Supplier::create(['name' => 'Test Airline']);
        $invoice = Invoice::factory()->create(['lead_id' => Lead::factory()->create()->id]);
        $first = $this->bill($invoice, $supplier, 'VB-EDIT-1', 300);
        $second = $this->bill($invoice, $supplier, 'VB-EDIT-2', 300);
        $payment = app(RecordSupplierPaymentService::class)->record(['supplier_id' => $supplier->id, 'payment_date' => '2026-10-09', 'amount' => 200, 'payment_mode' => 'cash', 'paid_through' => 'cash', 'allocations' => [['vendor_bill_id' => $first->id, 'amount' => 200]]]);

        app(UpdateSupplierPaymentService::class)->update($payment, ['payment_date' => '2026-10-09', 'amount' => 250, 'payment_mode' => 'bank_transfer', 'paid_through' => 'ntb_current', 'reference_number' => 'BANK-1', 'notes' => '', 'audit_reason' => 'Moved allocation to correct bill', 'allocations' => [['vendor_bill_id' => $second->id, 'amount' => 250]]]);

        $this->assertSame('pending', $first->fresh()->payment_status);
        $this->assertSame('partial', $second->fresh()->payment_status);
        $this->assertDatabaseHas('audit_logs', ['event' => 'SupplierPayment.edited', 'reason' => 'Moved allocation to correct bill']);
    }

    public function test_payment_register_export_is_a_valid_xlsx_file(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']));
        $response = $this->get(route('admin.finance-export', ['type' => 'payment-register']));
        $response->assertOk()->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $zipPath = tempnam(sys_get_temp_dir(), 'xlsx-test-');
        file_put_contents($zipPath, $response->streamedContent());
        $zip = new ZipArchive;
        $this->assertTrue($zip->open($zipPath) === true);
        $this->assertNotFalse($zip->getFromName('xl/worksheets/sheet1.xml'));
        $zip->close();
        unlink($zipPath);
    }

    private function bill(Invoice $invoice, Supplier $supplier, string $number, float $amount): VendorBill
    {
        return VendorBill::create(['invoice_id' => $invoice->id, 'supplier_id' => $supplier->id, 'vendor_name' => $supplier->name, 'vendor_bill_number' => $number, 'bill_amount' => $amount, 'service_type' => 'AIR_TICKET', 'payment_status' => 'pending']);
    }
}
