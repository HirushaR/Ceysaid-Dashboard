<?php

namespace Tests\Feature\Admin;

use App\Enums\QuoteStatus;
use App\Livewire\Admin\Invoices\RecordPayment;
use App\Livewire\Admin\Invoices\Create as CreateInvoice;
use App\Livewire\Admin\Invoices\Edit as EditInvoice;
use App\Livewire\Admin\Quotes\Edit as EditQuote;
use App\Livewire\Admin\SupplierPayments\Create as CreateSupplierPayment;
use App\Livewire\Admin\Suppliers\Create as CreateSupplier;
use App\Livewire\Admin\Suppliers\Edit as EditSupplier;
use App\Livewire\Admin\VendorBills\Create as CreateVendorBill;
use App\Livewire\Admin\VendorBills\Edit as EditVendorBill;
use App\Models\Invoice;
use App\Models\Quote;
use App\Models\Supplier;
use App\Models\User;
use App\Models\VendorBill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class FinanceMutationTest extends TestCase
{
    use RefreshDatabase;

    public function test_draft_quote_can_be_edited_and_historical_quote_cannot(): void
    {
        $admin=User::factory()->create(['role'=>'admin']); $invoice=Invoice::factory()->create(); $this->actingAs($admin);
        $quote=Quote::create(['lead_id'=>$invoice->lead_id,'family_id'=>(string)Str::uuid(),'revision'=>1,'quote_number'=>'QT-TEST-1','status'=>QuoteStatus::Draft,'quote_date'=>now(),'valid_until'=>now()->addWeek(),'subject'=>'Old','created_by'=>$admin->id]);
        $quote->lineItems()->create(['description'=>'Old line','quantity'=>1,'rate'=>100,'sort_order'=>0]);
        Livewire::test(EditQuote::class,['quote'=>$quote])->set('subject','Updated')->set('lines',[['description'=>'New line','quantity'=>2,'rate'=>75]])->call('save')->assertHasNoErrors();
        $this->assertSame('Updated',$quote->fresh()->subject); $this->assertEquals(150,(float)$quote->lineItems()->firstOrFail()->amount); $this->assertDatabaseHas('finance_action_logs',['subject_id'=>$quote->id,'action'=>'updated']);
        $quote->update(['status'=>QuoteStatus::Sent]); $this->get(route('admin.quotes.edit',$quote))->assertForbidden();
    }

    public function test_customer_receipt_updates_balance_and_rejects_overpayment(): void
    {
        $admin=User::factory()->create(['role'=>'admin']); $invoice=Invoice::factory()->create(['total_amount'=>1000,'balance_amount'=>1000]); $this->actingAs($admin);
        Livewire::test(RecordPayment::class,['invoice'=>$invoice])->set('amount',400)->call('save')->assertHasNoErrors();
        $invoice->refresh(); $this->assertSame('partial',$invoice->customer_payment_status); $this->assertEquals(600,(float)$invoice->balance_amount);
        Livewire::test(RecordPayment::class,['invoice'=>$invoice])->set('amount',700)->call('save')->assertHasErrors('amount');
        $this->assertEquals(400,(float)$invoice->customerPayments()->sum('amount'));
    }

    public function test_vendor_bill_creation_and_paid_total_edit_guard(): void
    {
        $admin=User::factory()->create(['role'=>'admin']); $invoice=Invoice::factory()->create(); $supplier=Supplier::create(['name'=>'Hotel Partner']); $this->actingAs($admin);
        Livewire::test(CreateVendorBill::class)->set('invoice_id',$invoice->id)->set('supplier_id',$supplier->id)->set('service_type','Hotel')->set('lines',[['description'=>'Rooms','quantity'=>2,'rate'=>300]])->call('save')->assertHasNoErrors();
        $bill=VendorBill::firstOrFail(); $this->assertEquals(600,(float)$bill->bill_amount);
        $bill->vendorBillPayments()->create(['amount'=>500,'payment_date'=>now(),'payment_mode'=>'cash','paid_through'=>'cash']);
        Livewire::test(EditVendorBill::class,['vendorBill'=>$bill->fresh()])->set('lines',[['description'=>'Rooms','quantity'=>1,'rate'=>400]])->call('save')->assertHasErrors('lines');
        $this->assertEquals(600,(float)$bill->fresh()->bill_amount);
    }

    public function test_supplier_payment_allocates_across_bills_and_updates_statuses(): void
    {
        $admin=User::factory()->create(['role'=>'admin']); $invoice=Invoice::factory()->create(); $supplier=Supplier::create(['name'=>'Tour Operator']); $this->actingAs($admin);
        $one=VendorBill::create(['invoice_id'=>$invoice->id,'supplier_id'=>$supplier->id,'vendor_name'=>$supplier->name,'vendor_bill_number'=>'VB-1','bill_amount'=>300,'due_date'=>now(),'service_type'=>'Tour','payment_status'=>'pending']);
        $two=VendorBill::create(['invoice_id'=>$invoice->id,'supplier_id'=>$supplier->id,'vendor_name'=>$supplier->name,'vendor_bill_number'=>'VB-2','bill_amount'=>500,'due_date'=>now(),'service_type'=>'Tour','payment_status'=>'pending']);
        Livewire::test(CreateSupplierPayment::class)->set('supplier_id',$supplier->id)->set('amount',500)->set('allocations.0.amount',300)->set('allocations.1.amount',200)->call('save')->assertHasNoErrors();
        $this->assertDatabaseCount('supplier_payments',1); $this->assertDatabaseCount('vendor_bill_payments',2); $this->assertSame('paid',$one->fresh()->payment_status); $this->assertSame('partial',$two->fresh()->payment_status); $this->assertEquals(300,$two->fresh()->outstanding_amount);
    }

    public function test_supplier_master_record_can_be_created_and_edited(): void
    {
        $admin=User::factory()->create(['role'=>'admin']); $this->actingAs($admin);
        Livewire::test(CreateSupplier::class)->set('name','New Ground Handler')->set('email','accounts@example.test')->call('save')->assertHasNoErrors();
        $supplier=Supplier::where('name','New Ground Handler')->firstOrFail();
        Livewire::test(EditSupplier::class,['supplier'=>$supplier])->set('contact_name','Finance Desk')->set('phone','0112345678')->call('save')->assertHasNoErrors();
        $this->assertSame('Finance Desk',$supplier->fresh()->contact_name);
        $this->get(route('admin.suppliers.show',$supplier))->assertOk()->assertSee('New Ground Handler');
    }

    public function test_native_invoice_create_edit_and_paid_total_guard(): void
    {
        $admin=User::factory()->create(['role'=>'admin']); $lead=\App\Models\Lead::factory()->create(); $this->actingAs($admin);
        Livewire::test(CreateInvoice::class)->set('lead_id',$lead->id)->set('subject','Holiday invoice')->set('lines',[['description'=>'Package','customer_details'=>'Two adults','quantity'=>2,'rate'=>500]])->call('save')->assertHasNoErrors();
        $invoice=Invoice::where('lead_id',$lead->id)->firstOrFail(); $this->assertEquals(1000,(float)$invoice->total_amount); $this->assertStringStartsWith('INV/'.now()->year.'/'.$lead->id,$invoice->invoice_number);
        Livewire::test(EditInvoice::class,['invoice'=>$invoice])->set('lines',[['description'=>'Package revised','customer_details'=>'','quantity'=>1,'rate'=>1200]])->call('save')->assertHasNoErrors();
        $this->assertEquals(1200,(float)$invoice->fresh()->total_amount);
        $invoice->customerPayments()->create(['amount'=>1100,'payment_date'=>today(),'payment_method'=>'cash','deposit_to'=>'cash']);
        Livewire::test(EditInvoice::class,['invoice'=>$invoice->fresh()])->set('lines',[['description'=>'Too low','customer_details'=>'','quantity'=>1,'rate'=>1000]])->call('save')->assertHasErrors('lines');
        $this->assertEquals(1200,(float)$invoice->fresh()->total_amount);
    }

    public function test_overdue_invoice_notifications_are_idempotent_per_day(): void
    {
        $account=User::factory()->create(['role'=>'account']); $invoice=Invoice::factory()->create(['due_date'=>today()->subDay(),'total_amount'=>900,'balance_amount'=>900,'customer_payment_status'=>'pending']);
        $this->artisan('finance:notify-overdue-invoices')->assertSuccessful();
        $this->artisan('finance:notify-overdue-invoices')->assertSuccessful();
        $this->assertSame(1,$account->notifications()->count());
        $this->assertSame($invoice->id,$account->notifications()->first()->data['invoice_id']);
    }
}
