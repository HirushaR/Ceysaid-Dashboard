<?php

namespace Tests\Feature\Finance;

use App\Enums\QuoteStatus;
use App\Models\Lead;
use App\Models\Permission;
use App\Models\Quote;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuoteAccessTest extends TestCase
{
    use RefreshDatabase;

    private function grantQuoteView(User $user): void
    {
        $permission = Permission::firstOrCreate(
            ['name' => 'quotes.view'],
            [
                'display_name' => 'View Quotes',
                'resource' => 'quotes',
                'action' => 'view',
                'description' => 'Test',
            ]
        );
        $user->permissions()->syncWithoutDetaching([$permission->id]);
    }

    public function test_accounting_user_sees_all_quotes(): void
    {
        $lead1 = Lead::factory()->create();
        $lead2 = Lead::factory()->create();
        $q1 = Quote::create([
            'lead_id' => $lead1->id,
            'quote_number' => 'QT/2099/1',
            'status' => QuoteStatus::Draft,
            'quote_date' => now(),
        ]);
        $q2 = Quote::create([
            'lead_id' => $lead2->id,
            'quote_number' => 'QT/2099/2',
            'status' => QuoteStatus::Draft,
            'quote_date' => now(),
        ]);

        $account = User::factory()->create(['role' => 'account']);
        $this->actingAs($account);

        $this->assertTrue($account->canViewQuote($q1));
        $this->assertTrue($account->canViewQuote($q2));
        $this->assertSame(2, Quote::query()->visibleToUser($account)->count());
    }

    public function test_sales_user_only_sees_quotes_for_assigned_leads(): void
    {
        $salesA = User::factory()->create(['role' => 'sales']);
        $salesB = User::factory()->create(['role' => 'sales']);
        $this->grantQuoteView($salesA);
        $this->grantQuoteView($salesB);

        $leadForA = Lead::factory()->create(['assigned_to' => $salesA->id]);
        $leadForB = Lead::factory()->create(['assigned_to' => $salesB->id]);
        $quoteA = Quote::create([
            'lead_id' => $leadForA->id,
            'quote_number' => 'QT/2099/A',
            'status' => QuoteStatus::Draft,
            'quote_date' => now(),
        ]);
        $quoteB = Quote::create([
            'lead_id' => $leadForB->id,
            'quote_number' => 'QT/2099/B',
            'status' => QuoteStatus::Draft,
            'quote_date' => now(),
        ]);

        $this->actingAs($salesA);
        $this->assertTrue($salesA->canViewQuote($quoteA));
        $this->assertFalse($salesA->canViewQuote($quoteB));

        $this->actingAs($salesB);
        $this->assertTrue($salesB->canViewQuote($quoteB));
        $this->assertFalse($salesB->canViewQuote($quoteA));
    }
}
