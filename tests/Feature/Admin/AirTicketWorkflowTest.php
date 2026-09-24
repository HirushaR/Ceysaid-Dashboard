<?php

namespace Tests\Feature\Admin;

use App\Enums\AirTicketStatus;
use App\Enums\ServiceStatus;
use App\Livewire\Admin\AirTickets\Index;
use App\Livewire\Admin\AirTickets\Queue;
use App\Models\AirTicketRequest;
use App\Models\Invoice;
use App\Models\Supplier;
use App\Models\User;
use App\Models\VendorBill;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AirTicketWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendor_bill_can_be_queued_for_accounts_approval(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $bill = $this->vendorBill();
        $this->actingAs($admin);

        $this->get(route('admin.vendor-bills.show', $bill))
            ->assertOk()
            ->assertSee('Queue to issue');

        Livewire::test(Queue::class, ['vendorBill' => $bill])
            ->set('provider', 'sabre')
            ->set('booking_reference', 'PNR123')
            ->set('airline', 'SriLankan Airlines')
            ->set('time_limit', now()->addDay()->format('Y-m-d\TH:i'))
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect(route('admin.air-tickets.index'));

        $request = AirTicketRequest::firstOrFail();
        $this->assertSame(AirTicketStatus::PENDING_APPROVAL, $request->status);
        $this->assertSame('1250.00', $request->amount);
        $this->assertSame(ServiceStatus::IN_PROGRESS->value, $bill->invoice->lead->fresh()->air_ticket_status);
    }

    public function test_accounts_approve_and_sales_manager_issues_ticket(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $account = User::factory()->create(['role' => 'account']);
        $salesManager = User::factory()->create(['role' => 'sales', 'is_manager' => true]);
        $request = $this->ticketRequest($admin);

        $this->actingAs($account);
        Livewire::test(Index::class)
            ->call('openReview', $request->id)
            ->set('review_notes', 'Fare and amount verified')
            ->call('approve')
            ->assertHasNoErrors();

        $request->refresh();
        $this->assertSame(AirTicketStatus::READY_FOR_TICKETING, $request->status);
        $this->assertSame($account->id, $request->approved_by);

        $this->actingAs($salesManager);
        Livewire::test(Index::class)
            ->call('openIssue', $request->id)
            ->set('ticket_number', 'UL-1234567890')
            ->set('issued_at', now()->format('Y-m-d\TH:i'))
            ->set('issue_notes', 'E-ticket emailed to customer')
            ->call('issue')
            ->assertHasNoErrors();

        $request->refresh();
        $this->assertSame(AirTicketStatus::ISSUED, $request->status);
        $this->assertSame($salesManager->id, $request->issued_by);
        $this->assertSame('UL-1234567890', $request->ticket_number);
        $this->assertSame(ServiceStatus::DONE->value, $request->vendorBill->invoice->lead->fresh()->air_ticket_status);

        $this->get(route('admin.leads.show', $request->vendorBill->invoice->lead))
            ->assertOk()
            ->assertSee('Issued ticket details')
            ->assertSee('UL-1234567890')
            ->assertSee($request->booking_reference)
            ->assertSee('Done');
    }

    public function test_only_accounts_approve_and_only_sales_managers_or_admins_issue(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $sales = User::factory()->create(['role' => 'sales', 'is_manager' => false]);
        $request = $this->ticketRequest($admin);

        $this->actingAs($sales);
        Livewire::test(Index::class)
            ->call('openReview', $request->id)
            ->assertForbidden();

        $request->update(['status' => AirTicketStatus::READY_FOR_TICKETING]);
        Livewire::test(Index::class)
            ->call('openIssue', $request->id)
            ->assertForbidden();
    }

    public function test_pending_and_issued_tabs_are_separate(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $pending = $this->ticketRequest($admin, 'PENDING1');
        $issued = $this->ticketRequest($admin, 'ISSUED1');
        $issued->update([
            'status' => AirTicketStatus::ISSUED,
            'issued_by' => $admin->id,
            'issued_at' => now(),
            'ticket_number' => 'TICKET-1',
        ]);

        $this->actingAs($admin)->get(route('admin.air-tickets.index'))
            ->assertOk()
            ->assertSee('PENDING1')
            ->assertDontSee('ISSUED1');

        $this->get(route('admin.air-tickets.index', ['tab' => 'issued']))
            ->assertOk()
            ->assertSee('ISSUED1')
            ->assertDontSee('PENDING1');
    }

    public function test_accounts_see_waiting_approval_as_pending_and_reviewed_requests_as_approved(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $account = User::factory()->create(['role' => 'account']);
        $waiting = $this->ticketRequest($admin, 'WAITING1');
        $approved = $this->ticketRequest($admin, 'APPROVED1');
        $approved->update([
            'status' => AirTicketStatus::READY_FOR_TICKETING,
            'approved_by' => $account->id,
            'approved_at' => now(),
        ]);
        $issued = $this->ticketRequest($admin, 'APPROVED2');
        $issued->update([
            'status' => AirTicketStatus::ISSUED,
            'approved_by' => $account->id,
            'approved_at' => now(),
            'issued_by' => $admin->id,
            'issued_at' => now(),
            'ticket_number' => 'TICKET-2',
        ]);

        $this->actingAs($account)->get(route('admin.air-tickets.index'))
            ->assertOk()
            ->assertSee($waiting->booking_reference)
            ->assertDontSee($approved->booking_reference)
            ->assertDontSee($issued->booking_reference);

        $this->get(route('admin.air-tickets.index', ['tab' => 'approved']))
            ->assertOk()
            ->assertSee($approved->booking_reference)
            ->assertSee($issued->booking_reference)
            ->assertDontSee($waiting->booking_reference);
    }

    private function vendorBill(): VendorBill
    {
        $invoice = Invoice::factory()->create();
        $supplier = Supplier::create(['name' => 'Air Supplier']);

        return VendorBill::create([
            'invoice_id' => $invoice->id,
            'supplier_id' => $supplier->id,
            'vendor_name' => $supplier->name,
            'vendor_bill_number' => 'VB-TICKET-'.fake()->unique()->numberBetween(100, 999),
            'bill_amount' => 1250,
            'due_date' => now()->addWeek(),
            'service_type' => 'Air Ticket',
            'service_details' => 'Passenger and sector details',
            'payment_status' => 'pending',
        ]);
    }

    private function ticketRequest(User $queuedBy, string $pnr = 'PNR123'): AirTicketRequest
    {
        return AirTicketRequest::create([
            'vendor_bill_id' => $this->vendorBill()->id,
            'provider' => 'sabre',
            'booking_reference' => $pnr,
            'airline' => 'SriLankan Airlines',
            'time_limit' => now()->addDay(),
            'amount' => 1250,
            'status' => AirTicketStatus::PENDING_APPROVAL,
            'queued_by' => $queuedBy->id,
            'queued_at' => now(),
        ]);
    }
}
