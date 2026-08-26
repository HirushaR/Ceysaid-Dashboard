<?php

namespace Tests\Feature\Finance;

use App\Enums\LeadStatus;
use App\Enums\QuoteStatus;
use App\Filament\Resources\InvoiceResource;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\LeadCost;
use App\Models\Quote;
use App\Models\QuoteLineItem;
use App\Models\Tour;
use App\Models\User;
use App\Services\ConvertQuoteToInvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceFlowsTest extends TestCase
{
    use RefreshDatabase;

    public function test_lead_confirmed_does_not_auto_create_invoice_with_lead_costs(): void
    {
        $lead = Lead::factory()->create(['status' => LeadStatus::SENT_TO_CUSTOMER->value]);
        LeadCost::create([
            'lead_id' => $lead->id,
            'invoice_number' => 'LC1',
            'amount' => 150.50,
            'details' => 'Tour package',
        ]);
        LeadCost::create([
            'lead_id' => $lead->id,
            'invoice_number' => 'LC2',
            'amount' => 49.50,
            'details' => 'Visa',
        ]);

        $lead->update(['status' => LeadStatus::CONFIRMED->value]);

        $lead->refresh();
        $this->assertCount(0, $lead->invoices);
    }

    public function test_lead_confirmed_does_not_auto_create_invoice_without_lead_costs(): void
    {
        $lead = Lead::factory()->create(['status' => LeadStatus::SENT_TO_CUSTOMER->value]);
        $lead->update(['status' => LeadStatus::CONFIRMED->value]);
        $lead->refresh();
        $this->assertCount(0, $lead->invoices);
    }

    public function test_updating_confirmed_lead_still_has_no_auto_invoice(): void
    {
        $lead = Lead::factory()->create(['status' => LeadStatus::SENT_TO_CUSTOMER->value]);
        $lead->update(['status' => LeadStatus::CONFIRMED->value]);
        $this->assertSame(0, $lead->fresh()->invoices()->count());
        $lead->update(['subject' => 'Updated subject']);
        $this->assertSame(0, $lead->fresh()->invoices()->count());
    }

    public function test_quote_converts_to_invoice(): void
    {
        $lead = Lead::factory()->create();
        $quote = Quote::create([
            'lead_id' => $lead->id,
            'quote_number' => 'QUOTE/2099/00001',
            'status' => QuoteStatus::Accepted,
            'quote_date' => now(),
        ]);
        QuoteLineItem::create([
            'quote_id' => $quote->id,
            'sort_order' => 0,
            'description' => 'Package',
            'quantity' => 2,
            'rate' => 100,
            'amount' => 200,
        ]);

        $invoice = app(ConvertQuoteToInvoiceService::class)->convert($quote->fresh());

        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertSame('INV/'.now()->year.'/'.$lead->id, $invoice->invoice_number);
        $this->assertEquals(200.0, (float) $invoice->total_amount);
        $this->assertCount(1, $invoice->lineItems);
        $this->assertEquals(QuoteStatus::Converted, $quote->fresh()->status);
    }

    public function test_converted_quote_cannot_convert_again(): void
    {
        $lead = Lead::factory()->create();
        $quote = Quote::create([
            'lead_id' => $lead->id,
            'quote_number' => 'QUOTE/2099/00002',
            'status' => QuoteStatus::Accepted,
        ]);
        QuoteLineItem::create([
            'quote_id' => $quote->id,
            'sort_order' => 0,
            'description' => 'X',
            'quantity' => 1,
            'rate' => 10,
            'amount' => 10,
        ]);
        app(ConvertQuoteToInvoiceService::class)->convert($quote->fresh());
        $this->expectException(\InvalidArgumentException::class);
        app(ConvertQuoteToInvoiceService::class)->convert($quote->fresh());
    }

    public function test_sales_and_accounting_can_create_invoices_via_resource(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);
        $this->actingAs($sales);
        $this->assertTrue(InvoiceResource::canCreate());

        $account = User::factory()->create(['role' => 'account']);
        $this->actingAs($account);
        $this->assertTrue(InvoiceResource::canCreate());
    }

    public function test_lead_tour_change_syncs_invoice_tour_id(): void
    {
        $tourA = Tour::factory()->create();
        $tourB = Tour::factory()->create();
        $lead = Lead::factory()->create([
            'is_group_lead' => true,
            'tour_id' => $tourA->id,
            'status' => LeadStatus::CONFIRMED->value,
        ]);
        $invoice = Invoice::factory()->create([
            'lead_id' => $lead->id,
            'tour_id' => $tourA->id,
        ]);

        $lead->update(['tour_id' => $tourB->id]);

        $this->assertSame($tourB->id, $invoice->fresh()->tour_id);
    }
}
