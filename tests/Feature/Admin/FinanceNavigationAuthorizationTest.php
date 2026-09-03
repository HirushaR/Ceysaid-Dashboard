<?php

namespace Tests\Feature\Admin;

use App\Models\Invoice;
use App\Models\Lead;
use App\Models\Permission;
use App\Models\Supplier;
use App\Models\User;
use App\Models\VendorBill;
use App\Livewire\Admin\VendorBills\Create as CreateVendorBill;
use App\Support\AdminNavigation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class FinanceNavigationAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_sales_finance_permissions_open_scoped_receivables_and_vendor_bills(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);
        $this->grant($sales, ['invoices.view', 'vendor_bills.view']);
        $mine = Lead::factory()->create(['assigned_to' => $sales->id]);
        $other = Lead::factory()->create();
        $mineInvoice = Invoice::factory()->create(['lead_id' => $mine->id, 'balance_amount' => 300]);
        $otherInvoice = Invoice::factory()->create(['lead_id' => $other->id, 'balance_amount' => 900]);
        VendorBill::create(['invoice_id' => $mineInvoice->id, 'vendor_name' => 'Visible supplier', 'vendor_bill_number' => 'VISIBLE-BILL', 'bill_amount' => 100, 'service_type' => 'HOTEL']);
        VendorBill::create(['invoice_id' => $otherInvoice->id, 'vendor_name' => 'Hidden supplier', 'vendor_bill_number' => 'HIDDEN-BILL', 'bill_amount' => 100, 'service_type' => 'HOTEL']);

        $this->actingAs($sales)->get(route('admin.receivables.index'))
            ->assertOk()->assertSee($mineInvoice->invoice_number)->assertDontSee($otherInvoice->invoice_number);
        $this->get(route('admin.vendor-bills.index'))
            ->assertOk()->assertSee('VISIBLE-BILL')->assertDontSee('HIDDEN-BILL');

        $labels = $this->labels($sales);
        $this->assertContains('Receivables', $labels);
        $this->assertContains('Vendor Bills', $labels);
        $this->assertNotContains('Suppliers', $labels);
        $this->assertNotContains('Payments', $labels);
    }

    public function test_specific_supplier_and_payment_permissions_match_navigation_and_pages(): void
    {
        $user = User::factory()->create(['role' => 'marketing']);
        $this->grant($user, ['suppliers.view', 'payments.view']);

        $this->actingAs($user)->get(route('admin.suppliers.index'))->assertOk();
        $this->get(route('admin.payments.index'))->assertOk();
        $this->assertContains('Suppliers', $this->labels($user));
        $this->assertContains('Payments', $this->labels($user));
    }

    public function test_user_without_finance_permissions_gets_no_finance_links_and_direct_access_is_forbidden(): void
    {
        $user = User::factory()->create(['role' => 'marketing']);
        $labels = $this->labels($user);
        foreach (['Invoices', 'Receivables', 'Vendor Bills', 'Suppliers', 'Payments'] as $label) {
            $this->assertNotContains($label, $labels);
        }

        $this->actingAs($user)->get(route('admin.invoices.index'))->assertForbidden();
        $this->get(route('admin.receivables.index'))->assertForbidden();
        $this->get(route('admin.vendor-bills.index'))->assertForbidden();
        $this->get(route('admin.suppliers.index'))->assertForbidden();
        $this->get(route('admin.payments.index'))->assertForbidden();
    }

    public function test_admin_can_open_every_finance_workspace(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        foreach (['admin.quotes.index', 'admin.invoices.index', 'admin.receivables.index', 'admin.vendor-bills.index', 'admin.suppliers.index', 'admin.payments.index'] as $route) {
            $this->actingAs($admin)->get(route($route))->assertOk();
        }
    }

    public function test_permitted_sales_user_can_create_vendor_bill_for_assigned_invoice_only(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);
        $this->grant($sales, ['invoices.view', 'vendor_bills.view', 'vendor_bills.create']);
        $mine = Invoice::factory()->create([
            'lead_id' => Lead::factory()->create(['assigned_to' => $sales->id])->id,
            'invoice_number' => 'MINE-INVOICE',
        ]);
        $other = Invoice::factory()->create([
            'lead_id' => Lead::factory()->create()->id,
            'invoice_number' => 'OTHER-INVOICE',
        ]);
        $supplier = Supplier::create(['name' => 'Assigned Invoice Supplier']);

        $this->actingAs($sales)->get(route('admin.vendor-bills.create'))
            ->assertOk()
            ->assertSee('MINE-INVOICE')
            ->assertDontSee('OTHER-INVOICE');

        Livewire::test(CreateVendorBill::class)
            ->set('invoice_id', $mine->id)
            ->set('supplier_id', $supplier->id)
            ->set('service_type', 'Hotel')
            ->set('lines', [['description' => 'Rooms', 'quantity' => 2, 'rate' => 250]])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('vendor_bills', [
            'invoice_id' => $mine->id,
            'supplier_id' => $supplier->id,
            'bill_amount' => 500,
        ]);

        Livewire::test(CreateVendorBill::class)
            ->set('invoice_id', $other->id)
            ->set('supplier_id', $supplier->id)
            ->set('service_type', 'Transfer')
            ->set('lines', [['description' => 'Airport transfer', 'quantity' => 1, 'rate' => 100]])
            ->call('save')
            ->assertHasErrors('invoice_id');
    }

    public function test_vendor_bill_view_permission_does_not_expose_create_action(): void
    {
        $sales = User::factory()->create(['role' => 'sales']);
        $this->grant($sales, ['invoices.view', 'vendor_bills.view']);

        $this->actingAs($sales)->get(route('admin.vendor-bills.index'))
            ->assertOk()
            ->assertDontSee('New vendor bill');
        $this->get(route('admin.vendor-bills.create'))->assertForbidden();
    }

    public function test_every_visible_navigation_link_opens_for_each_standard_role(): void
    {
        foreach (['admin', 'hr', 'marketing', 'sales', 'operation', 'account', 'call_center'] as $role) {
            $user = User::factory()->create(['role' => $role]);
            $routes = collect(AdminNavigation::for($user))->flatMap(fn ($group) => $group['items'])->pluck('route')->unique();
            foreach ($routes as $route) {
                $response = $this->actingAs($user)->get(route($route));
                $this->assertNotSame(403, $response->getStatusCode(), "{$role} sees {$route} but receives 403");
            }
        }
    }

    private function grant(User $user, array $names): void
    {
        foreach ($names as $name) {
            [$resource, $action] = explode('.', $name, 2);
            $permission = Permission::firstOrCreate(['name' => $name], ['display_name' => $name, 'resource' => $resource, 'action' => $action]);
            $user->permissions()->syncWithoutDetaching($permission);
        }
    }

    private function labels(User $user): array
    {
        return collect(AdminNavigation::for($user))->flatMap(fn ($group) => collect($group['items'])->pluck('label'))->all();
    }
}
