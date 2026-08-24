<?php

namespace Tests\Feature\Finance;

use App\Enums\LeadStatus;
use App\Enums\TourStatus;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Tour;
use App\Models\VendorBill;
use App\Models\VendorBillPayment;
use App\Services\TourCodeGenerator;
use App\Services\TourFinanceReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TourFinanceReportServiceTest extends TestCase
{
    use RefreshDatabase;

    private TourFinanceReportService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(TourFinanceReportService::class);
    }

    public function test_tour_code_generator_produces_unique_codes(): void
    {
        $generator = app(TourCodeGenerator::class);
        $date = now()->addMonth();

        $code = $generator->generate('China', $date, 'China 08 Days');
        $this->assertMatchesRegularExpression('/^[A-Z]{3}-\d{2}[A-Z]{3}-\d{4}$/', $code);

        Tour::factory()->create(['tour_code' => $code, 'departure_date' => $date]);

        $code2 = $generator->generate('China', $date, 'China 08 Days');
        $this->assertNotSame($code, $code2);
    }

    public function test_invoice_inherits_tour_id_from_lead_on_create(): void
    {
        $tour = Tour::factory()->create();
        $lead = Lead::factory()->create([
            'is_group_lead' => true,
            'tour_id' => $tour->id,
            'status' => LeadStatus::CONFIRMED->value,
        ]);

        $invoice = Invoice::factory()->create([
            'lead_id' => $lead->id,
            'total_amount' => 500000,
            'balance_amount' => 500000,
        ]);

        $this->assertSame($tour->id, $invoice->fresh()->tour_id);
    }

    public function test_confirmed_group_lead_requires_tour(): void
    {
        $lead = Lead::factory()->create([
            'is_group_lead' => true,
            'status' => LeadStatus::SENT_TO_CUSTOMER->value,
            'tour_id' => null,
        ]);

        $this->expectException(ValidationException::class);

        $lead->update(['status' => LeadStatus::CONFIRMED->value]);
    }

    public function test_monthly_receivables_grouped_by_due_date(): void
    {
        $tour = Tour::factory()->create();
        $lead = Lead::factory()->create([
            'is_group_lead' => true,
            'tour_id' => $tour->id,
            'status' => LeadStatus::CONFIRMED->value,
        ]);

        Invoice::factory()->create([
            'lead_id' => $lead->id,
            'tour_id' => $tour->id,
            'total_amount' => 300000,
            'balance_amount' => 200000,
            'customer_payment_status' => 'partial',
            'due_date' => now()->addMonth()->startOfMonth()->addDays(10),
        ]);

        $rows = $this->service->monthlyReceivables(['tour_id' => $tour->id]);
        $monthKey = now()->addMonth()->format('Y-m');

        $this->assertTrue($rows->contains(fn (array $row) => $row['month_key'] === $monthKey && $row['amount'] === 200000.0));
    }

    public function test_tour_cash_gap_calculation(): void
    {
        $tour = Tour::factory()->create(['departure_date' => now()->addDays(20)]);
        $lead = Lead::factory()->create([
            'is_group_lead' => true,
            'tour_id' => $tour->id,
            'status' => LeadStatus::CONFIRMED->value,
        ]);

        $invoice = Invoice::factory()->create([
            'lead_id' => $lead->id,
            'tour_id' => $tour->id,
            'total_amount' => 500000,
            'balance_amount' => 300000,
            'customer_payment_status' => 'partial',
        ]);

        $bill = VendorBill::create([
            'invoice_id' => $invoice->id,
            'vendor_name' => 'DMC',
            'vendor_bill_number' => 'VB-TEST-001',
            'bill_amount' => 400000,
            'due_date' => now()->addDays(15),
            'service_type' => 'LAND PACKAGE',
            'payment_status' => 'partial',
        ]);

        VendorBillPayment::create([
            'vendor_bill_id' => $bill->id,
            'amount' => 100000,
            'payment_date' => now(),
            'payment_mode' => 'bank_transfer',
            'paid_through' => 'cash',
        ]);

        $row = $this->service->tourCashGap(['tour_id' => $tour->id])->first();

        $this->assertNotNull($row);
        $this->assertSame(300000.0, $row['balance_receivable']);
        $this->assertSame(300000.0, $row['vendor_payable']);
        $this->assertSame(0.0, $row['cash_gap']);
    }

    public function test_negative_cash_gap_flagged_as_urgent_near_departure(): void
    {
        $tour = Tour::factory()->create(['departure_date' => now()->addDays(10)]);
        $lead = Lead::factory()->create([
            'is_group_lead' => true,
            'tour_id' => $tour->id,
            'status' => LeadStatus::CONFIRMED->value,
        ]);

        $invoice = Invoice::factory()->create([
            'lead_id' => $lead->id,
            'tour_id' => $tour->id,
            'total_amount' => 200000,
            'balance_amount' => 50000,
            'customer_payment_status' => 'partial',
        ]);

        VendorBill::create([
            'invoice_id' => $invoice->id,
            'vendor_name' => 'Airline',
            'vendor_bill_number' => 'VB-TEST-002',
            'bill_amount' => 300000,
            'due_date' => now()->addDays(5),
            'service_type' => 'AIR TICKET',
            'payment_status' => 'pending',
        ]);

        $row = $this->service->tourCashGap(['tour_id' => $tour->id])->first();

        $this->assertTrue($row['is_negative']);
        $this->assertTrue($row['is_urgent']);
        $this->assertSame(-250000.0, $row['cash_gap']);
    }

    public function test_departure_month_profit_excludes_open_tours(): void
    {
        $openTour = Tour::factory()->create([
            'status' => TourStatus::Open,
            'departure_date' => now()->subWeek(),
        ]);
        $departedTour = Tour::factory()->departed()->create([
            'departure_date' => now()->subWeek(),
        ]);

        foreach ([$openTour, $departedTour] as $tour) {
            $lead = Lead::factory()->create([
                'is_group_lead' => true,
                'tour_id' => $tour->id,
                'status' => LeadStatus::CONFIRMED->value,
            ]);
            Invoice::factory()->create([
                'lead_id' => $lead->id,
                'tour_id' => $tour->id,
                'total_amount' => 100000,
            ]);
        }

        $monthKey = now()->subWeek()->format('Y-m');
        $rows = $this->service->departureMonthProfit();
        $row = $rows->firstWhere('month_key', $monthKey);

        $this->assertNotNull($row);
        $this->assertSame(100000.0, $row['revenue']);
    }

    public function test_tour_wise_profit_sums_sales_and_vendor_costs(): void
    {
        $tour = Tour::factory()->create();
        $lead = Lead::factory()->create([
            'is_group_lead' => true,
            'tour_id' => $tour->id,
            'status' => LeadStatus::CONFIRMED->value,
        ]);

        $invoice = Invoice::factory()->create([
            'lead_id' => $lead->id,
            'tour_id' => $tour->id,
            'total_amount' => 600000,
        ]);

        VendorBill::create([
            'invoice_id' => $invoice->id,
            'vendor_name' => 'Hotel',
            'vendor_bill_number' => 'VB-TEST-003',
            'bill_amount' => 400000,
            'due_date' => now()->addWeek(),
            'service_type' => 'HOTEL',
            'payment_status' => 'pending',
        ]);

        $row = $this->service->tourWiseProfit(['tour_id' => $tour->id])->first();

        $this->assertSame(600000.0, $row['sales_value']);
        $this->assertSame(400000.0, $row['vendor_cost']);
        $this->assertSame(200000.0, $row['gross_profit']);
        $this->assertSame(33.3, $row['gp_percent']);
    }
}
