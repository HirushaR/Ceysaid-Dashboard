<?php

namespace Tests\Feature\Admin;

use App\Models\Invoice;
use App\Models\Supplier;
use App\Models\User;
use App\Models\VendorBill;
use App\Services\FinanceCashFlowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class FinanceOverviewTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_forecast_calculates_incoming_outgoing_and_overdue_cash(): void
    {
        Carbon::setTestNow('2026-09-25 09:00:00');
        $this->invoiceDue(today()->addDay(), 1000, 'INV-DAY');
        $this->invoiceDue(today()->addDays(5), 2000, 'INV-WEEK');
        $this->invoiceDue(today()->addDays(20), 3000, 'INV-MONTH');
        $this->invoiceDue(today()->subDay(), 400, 'INV-OVERDUE');
        $this->billDue(today()->addDay(), 300, 'VB-DAY');
        $this->billDue(today()->addDays(5), 500, 'VB-WEEK');
        $this->billDue(today()->addDays(20), 700, 'VB-MONTH');
        $this->billDue(today()->subDay(), 200, 'VB-OVERDUE');

        $service = app(FinanceCashFlowService::class);
        $forecast = $service->forecast(today());
        $overdue = $service->overdue(today());

        $this->assertSame(1000.0, $forecast['day']['incoming']);
        $this->assertSame(300.0, $forecast['day']['outgoing']);
        $this->assertSame(3000.0, $forecast['week']['incoming']);
        $this->assertSame(800.0, $forecast['week']['outgoing']);
        $this->assertSame(6000.0, $forecast['month']['incoming']);
        $this->assertSame(1500.0, $forecast['month']['outgoing']);
        $this->assertSame(400.0, $overdue['incoming']);
        $this->assertSame(200.0, $overdue['outgoing']);
    }

    public function test_admin_and_accounts_can_view_finance_overview_but_sales_cannot(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $account = User::factory()->create(['role' => 'account']);
        $sales = User::factory()->create(['role' => 'sales']);
        $this->invoiceDue(today()->addDay(), 1500, 'INV-NEXT');
        $this->billDue(today()->addDays(2), 600, 'VB-NEXT');

        $this->actingAs($admin)->get(route('admin.finance.overview'))
            ->assertOk()
            ->assertSee('Finance Overview')
            ->assertSee('INV-NEXT')
            ->assertSee('VB-NEXT');

        $this->actingAs($account)->get(route('admin.finance.overview'))->assertOk();
        $this->actingAs($sales)->get(route('admin.finance.overview'))->assertForbidden();
    }

    private function invoiceDue(Carbon $dueDate, float $balance, string $number): Invoice
    {
        return Invoice::factory()->create([
            'invoice_number' => $number,
            'due_date' => $dueDate,
            'total_amount' => $balance,
            'balance_amount' => $balance,
            'payment_amount' => 0,
            'customer_payment_status' => 'pending',
        ]);
    }

    private function billDue(Carbon $dueDate, float $amount, string $number): VendorBill
    {
        $supplier = Supplier::create(['name' => 'Supplier '.$number]);

        return VendorBill::create([
            'supplier_id' => $supplier->id,
            'vendor_name' => $supplier->name,
            'vendor_bill_number' => $number,
            'bill_amount' => $amount,
            'due_date' => $dueDate,
            'service_type' => 'Travel service',
            'payment_status' => 'pending',
        ]);
    }
}
