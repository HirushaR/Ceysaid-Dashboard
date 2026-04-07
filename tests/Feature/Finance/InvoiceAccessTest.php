<?php

namespace Tests\Feature\Finance;

use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceAccessTest extends TestCase
{
    use RefreshDatabase;

    private function grantInvoiceView(User $user): void
    {
        $permission = Permission::firstOrCreate(
            ['name' => 'invoices.view'],
            [
                'display_name' => 'View Invoices',
                'resource' => 'invoices',
                'action' => 'view',
                'description' => 'Test',
            ]
        );
        $user->permissions()->syncWithoutDetaching([$permission->id]);
    }

    public function test_accounting_user_sees_all_invoices(): void
    {
        $lead1 = Lead::factory()->create();
        $lead2 = Lead::factory()->create();
        $inv1 = Invoice::factory()->create(['lead_id' => $lead1->id]);
        $inv2 = Invoice::factory()->create(['lead_id' => $lead2->id]);

        $account = User::factory()->create(['role' => 'account']);
        $this->actingAs($account);

        $this->assertTrue($account->canViewInvoice($inv1));
        $this->assertTrue($account->canViewInvoice($inv2));
        $this->assertSame(2, Invoice::query()->visibleToUser($account)->count());
    }

    public function test_sales_user_only_sees_invoices_for_assigned_leads(): void
    {
        $salesA = User::factory()->create(['role' => 'sales']);
        $salesB = User::factory()->create(['role' => 'sales']);
        $this->grantInvoiceView($salesA);
        $this->grantInvoiceView($salesB);

        $leadForA = Lead::factory()->create(['assigned_to' => $salesA->id]);
        $leadForB = Lead::factory()->create(['assigned_to' => $salesB->id]);
        $invA = Invoice::factory()->create(['lead_id' => $leadForA->id]);
        $invB = Invoice::factory()->create(['lead_id' => $leadForB->id]);

        $this->actingAs($salesA);
        $this->assertTrue($salesA->canViewInvoice($invA));
        $this->assertFalse($salesA->canViewInvoice($invB));
        $this->assertSame(1, Invoice::query()->visibleToUser($salesA)->count());
        $this->assertTrue(Invoice::query()->visibleToUser($salesA)->whereKey($invA->id)->exists());

        $this->actingAs($salesB);
        $this->assertTrue($salesB->canViewInvoice($invB));
        $this->assertFalse($salesB->canViewInvoice($invA));
    }

    public function test_operation_user_only_sees_invoices_for_operator_assigned_leads(): void
    {
        $op = User::factory()->create(['role' => 'operation']);
        $this->grantInvoiceView($op);

        $lead = Lead::factory()->create(['assigned_operator' => $op->id]);
        $otherLead = Lead::factory()->create(['assigned_operator' => null]);
        $inv = Invoice::factory()->create(['lead_id' => $lead->id]);
        $otherInv = Invoice::factory()->create(['lead_id' => $otherLead->id]);

        $this->actingAs($op);
        $this->assertTrue($op->canViewInvoice($inv));
        $this->assertFalse($op->canViewInvoice($otherInv));
    }

    public function test_sales_and_operation_both_see_invoice_when_each_assigned_on_lead(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);
        $operation = User::factory()->create(['role' => 'operation']);
        $this->grantInvoiceView($sales);
        $this->grantInvoiceView($operation);

        $lead = Lead::factory()->create([
            'assigned_to' => $sales->id,
            'assigned_operator' => $operation->id,
        ]);
        $inv = Invoice::factory()->create(['lead_id' => $lead->id]);

        $this->actingAs($sales);
        $this->assertTrue($sales->canViewInvoice($inv));

        $this->actingAs($operation);
        $this->assertTrue($operation->canViewInvoice($inv));
    }
}
