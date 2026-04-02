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
use App\Models\User;
use App\Services\ConvertQuoteToInvoiceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceFlowsTest extends TestCase
{
    use RefreshDatabase;

    public function test_lead_confirmed_creates_invoice_from_lead_costs(): void
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
        $this->assertCount(1, $lead->invoices);
        $invoice = $lead->invoices->first();
        $this->assertEquals(200.00, (float) $invoice->total_amount);
        $this->assertCount(2, $invoice->lineItems);
    }

    public function test_lead_confirmed_with_no_costs_creates_shell_invoice(): void
    {
        $lead = Lead::factory()->create(['status' => LeadStatus::SENT_TO_CUSTOMER->value]);
        $lead->update(['status' => LeadStatus::CONFIRMED->value]);
        $lead->refresh();
        $this->assertCount(1, $lead->invoices);
        $invoice = $lead->invoices->first();
        $this->assertEquals(0.0, (float) $invoice->total_amount);
        $this->assertCount(1, $invoice->lineItems);
        $this->assertStringContainsString('Pending pricing', $invoice->lineItems->first()->description);
    }

    public function test_updating_confirmed_lead_does_not_add_second_invoice(): void
    {
        $lead = Lead::factory()->create(['status' => LeadStatus::SENT_TO_CUSTOMER->value]);
        $lead->update(['status' => LeadStatus::CONFIRMED->value]);
        $n = $lead->invoices()->count();
        $lead->update(['subject' => 'Updated subject']);
        $this->assertEquals($n, $lead->fresh()->invoices()->count());
    }

    public function test_quote_converts_to_invoice(): void
    {
        $lead = Lead::factory()->create();
        $quote = Quote::create([
            'lead_id' => $lead->id,
            'quote_number' => 'QUOTE/2099/00001',
            'status' => QuoteStatus::Draft,
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
            'status' => QuoteStatus::Draft,
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

    public function test_only_accounting_can_create_invoices_via_resource(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);
        $this->actingAs($sales);
        $this->assertFalse(InvoiceResource::canCreate());

        $account = User::factory()->create(['role' => 'account']);
        $this->actingAs($account);
        $this->assertTrue(InvoiceResource::canCreate());
    }
}
