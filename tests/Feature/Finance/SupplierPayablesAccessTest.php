<?php

namespace Tests\Feature\Finance;

use App\Filament\Resources\SupplierPayablesResource;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupplierPayablesAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_supplier_payables(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        $this->assertTrue(SupplierPayablesResource::canViewAny());
    }

    public function test_account_user_can_view_supplier_payables(): void
    {
        $account = User::factory()->create(['role' => 'account']);
        $this->actingAs($account);
        $this->assertTrue(SupplierPayablesResource::canViewAny());
    }

    public function test_sales_cannot_view_supplier_payables(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);
        $this->actingAs($sales);
        $this->assertFalse(SupplierPayablesResource::canViewAny());
    }

    public function test_admin_can_download_supplier_payables_summary_pdf(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin);
        $response = $this->get(route('finance.supplier-payables.summary-pdf'));
        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
    }

    public function test_admin_can_download_single_supplier_payable_pdf(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $supplier = Supplier::create(['name' => 'Test Supplier Co']);
        $this->actingAs($admin);
        $response = $this->get(route('finance.supplier-payables.pdf', ['supplier' => $supplier]));
        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('content-type'));
    }

    public function test_sales_cannot_download_supplier_payables_pdf(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);
        $supplier = Supplier::create(['name' => 'Blocked Supplier']);
        $this->actingAs($sales);
        $this->get(route('finance.supplier-payables.pdf', ['supplier' => $supplier]))->assertForbidden();
    }
}
