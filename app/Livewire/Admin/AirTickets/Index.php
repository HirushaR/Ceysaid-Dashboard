<?php

namespace App\Livewire\Admin\AirTickets;

use App\Enums\AirTicketStatus;
use App\Enums\ServiceStatus;
use App\Models\AirTicketRequest;
use App\Services\LeadActionLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Index extends Component
{
    use WithPagination;

    #[Url] public string $tab = 'pending';
    #[Url] public string $search = '';
    public ?int $reviewing_id = null;
    public string $review_notes = '';
    public ?int $issuing_id = null;
    public string $ticket_number = '';
    public string $issued_at = '';
    public string $issue_notes = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->canViewAirTickets(), 403);
        abort_unless(in_array($this->tab, $this->allowedTabs(), true), 404);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function selectTab(string $tab): void
    {
        abort_unless(in_array($tab, $this->allowedTabs(), true), 404);
        $this->tab = $tab;
        $this->resetPage();
        $this->resetActionForms();
    }

    public function openReview(int $requestId): void
    {
        abort_unless(auth()->user()->canApproveAirTickets(), 403);
        $request = $this->visibleRequest($requestId);
        abort_unless($request->status === AirTicketStatus::PENDING_APPROVAL, 422);
        $this->reviewing_id = $request->id;
        $this->review_notes = '';
        $this->issuing_id = null;
    }

    public function approve(): void
    {
        abort_unless(auth()->user()->canApproveAirTickets(), 403);
        $request = $this->visibleRequest($this->reviewing_id);
        abort_unless($request->status === AirTicketStatus::PENDING_APPROVAL, 422);

        $request->update([
            'status' => AirTicketStatus::READY_FOR_TICKETING,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'decision_notes' => $this->review_notes ?: null,
        ]);
        $this->logLeadAction($request, 'air_ticket_approved', 'Ticket request approved by accounts and is ready for issuing.');
        $this->resetActionForms();
        session()->flash('success', 'Ticket request approved and moved to the issuing list.');
    }

    public function returnForCorrection(): void
    {
        abort_unless(auth()->user()->canApproveAirTickets(), 403);
        $this->validate(['review_notes' => ['required', 'string', 'max:2000']]);
        $request = $this->visibleRequest($this->reviewing_id);
        abort_unless($request->status === AirTicketStatus::PENDING_APPROVAL, 422);

        $request->update([
            'status' => AirTicketStatus::RETURNED_FOR_CORRECTION,
            'approved_by' => null,
            'approved_at' => null,
            'decision_notes' => $this->review_notes,
        ]);
        $this->logLeadAction($request, 'air_ticket_returned', 'Ticket request returned for correction: '.$this->review_notes);
        $this->resetActionForms();
        session()->flash('success', 'Ticket request returned for correction.');
    }

    public function reject(): void
    {
        abort_unless(auth()->user()->canApproveAirTickets(), 403);
        $this->validate(['review_notes' => ['required', 'string', 'max:2000']]);
        $request = $this->visibleRequest($this->reviewing_id);
        abort_unless($request->status === AirTicketStatus::PENDING_APPROVAL, 422);

        DB::transaction(function () use ($request): void {
            $request->update([
                'status' => AirTicketStatus::REJECTED,
                'approved_by' => auth()->id(),
                'approved_at' => now(),
                'decision_notes' => $this->review_notes,
            ]);
            $request->vendorBill?->invoice?->lead?->update(['air_ticket_status' => ServiceStatus::CANCELLED->value]);
            $this->logLeadAction($request, 'air_ticket_rejected', 'Ticket request rejected by accounts: '.$this->review_notes);
        });
        $this->resetActionForms();
        session()->flash('success', 'Ticket request rejected.');
    }

    public function openIssue(int $requestId): void
    {
        abort_unless(auth()->user()->canIssueAirTickets(), 403);
        $request = $this->visibleRequest($requestId);
        abort_unless($request->status === AirTicketStatus::READY_FOR_TICKETING, 422);
        abort_if($request->isTimeLimitExpired(), 422, 'The ticket time limit has expired. Update the request before issuing.');
        $this->issuing_id = $request->id;
        $this->ticket_number = '';
        $this->issued_at = now()->format('Y-m-d\TH:i');
        $this->issue_notes = '';
        $this->reviewing_id = null;
    }

    public function issue(): void
    {
        abort_unless(auth()->user()->canIssueAirTickets(), 403);
        $data = $this->validate([
            'ticket_number' => ['required', 'string', 'max:150'],
            'issued_at' => ['required', 'date', 'before_or_equal:now'],
            'issue_notes' => ['nullable', 'string', 'max:3000'],
        ]);
        $request = $this->visibleRequest($this->issuing_id);
        abort_unless($request->status === AirTicketStatus::READY_FOR_TICKETING, 422);
        abort_if($request->isTimeLimitExpired(), 422, 'The ticket time limit has expired. Update the request before issuing.');

        DB::transaction(function () use ($request, $data): void {
            $request->update([
                'status' => AirTicketStatus::ISSUED,
                'issued_by' => auth()->id(),
                'issued_at' => $data['issued_at'],
                'ticket_number' => $data['ticket_number'],
                'issue_notes' => $data['issue_notes'] ?: null,
            ]);
            $request->vendorBill?->invoice?->lead?->update(['air_ticket_status' => ServiceStatus::DONE->value]);
            $this->logLeadAction($request, 'air_ticket_issued', "Ticket {$data['ticket_number']} issued for PNR {$request->booking_reference}.");
        });
        $this->resetActionForms();
        $this->tab = 'issued';
        session()->flash('success', 'Ticket marked as issued.');
    }

    public function hold(int $requestId): void
    {
        abort_unless(auth()->user()->canIssueAirTickets(), 403);
        $request = $this->visibleRequest($requestId);
        abort_unless($request->status === AirTicketStatus::READY_FOR_TICKETING, 422);
        $request->update(['status' => AirTicketStatus::ON_HOLD]);
        $this->logLeadAction($request, 'air_ticket_on_hold', 'Ticket issuing placed on hold.');
    }

    public function resume(int $requestId): void
    {
        abort_unless(auth()->user()->canIssueAirTickets(), 403);
        $request = $this->visibleRequest($requestId);
        abort_unless($request->status === AirTicketStatus::ON_HOLD, 422);
        abort_if($request->isTimeLimitExpired(), 422, 'The ticket time limit has expired.');
        $request->update(['status' => AirTicketStatus::READY_FOR_TICKETING]);
        $this->logLeadAction($request, 'air_ticket_resumed', 'Ticket issuing resumed.');
    }

    private function visibleRequest(?int $requestId): AirTicketRequest
    {
        abort_unless($requestId, 404);

        return AirTicketRequest::query()
            ->visibleTo(auth()->user())
            ->with('vendorBill.invoice.lead')
            ->findOrFail($requestId);
    }

    private function baseQuery(): Builder
    {
        return AirTicketRequest::query()
            ->visibleTo(auth()->user())
            ->with(['vendorBill.supplier', 'vendorBill.invoice.lead', 'queuedBy', 'approvedBy', 'issuedBy']);
    }

    /** @return list<string> */
    private function allowedTabs(): array
    {
        return $this->isAccountsView() ? ['pending', 'approved'] : ['pending', 'issued'];
    }

    private function isAccountsView(): bool
    {
        $user = auth()->user();

        return $user->isAccount() && ! $user->isAdmin();
    }

    private function logLeadAction(AirTicketRequest $request, string $action, string $description): void
    {
        $request->loadMissing('vendorBill.invoice.lead');
        LeadActionLogger::log(
            $request->vendorBill?->invoice?->lead,
            $action,
            $description,
            null,
            ['air_ticket_request_id' => $request->id, 'status' => $request->status->value],
        );
    }

    private function resetActionForms(): void
    {
        $this->reset(['reviewing_id', 'review_notes', 'issuing_id', 'ticket_number', 'issued_at', 'issue_notes']);
        $this->resetValidation();
    }

    public function render()
    {
        $visible = $this->baseQuery();
        $accountsView = $this->isAccountsView();
        $approvedStatuses = [
            AirTicketStatus::READY_FOR_TICKETING->value,
            AirTicketStatus::ON_HOLD->value,
            AirTicketStatus::ISSUED->value,
        ];
        $summary = [
            'awaiting_approval' => (clone $visible)->where('status', AirTicketStatus::PENDING_APPROVAL->value)->count(),
            'ready' => (clone $visible)->where('status', AirTicketStatus::READY_FOR_TICKETING->value)->count(),
            'approved' => (clone $visible)->whereIn('status', $approvedStatuses)->count(),
            'review_closed' => (clone $visible)->whereIn('status', [AirTicketStatus::RETURNED_FOR_CORRECTION->value, AirTicketStatus::REJECTED->value])->count(),
            'issued' => (clone $visible)->where('status', AirTicketStatus::ISSUED->value)->count(),
        ];
        $summary['pending'] = $accountsView
            ? $summary['awaiting_approval']
            : (clone $visible)->where('status', '!=', AirTicketStatus::ISSUED->value)->count();

        $requests = $this->baseQuery()
            ->when($accountsView, function (Builder $query) use ($approvedStatuses): void {
                $this->tab === 'approved'
                    ? $query->whereIn('status', $approvedStatuses)
                    : $query->where('status', AirTicketStatus::PENDING_APPROVAL->value);
            }, function (Builder $query): void {
                $this->tab === 'issued'
                    ? $query->where('status', AirTicketStatus::ISSUED->value)
                    : $query->where('status', '!=', AirTicketStatus::ISSUED->value);
            })
            ->when($this->search, fn (Builder $query) => $query->where(fn (Builder $inner) => $inner
                ->where('booking_reference', 'like', "%{$this->search}%")
                ->orWhere('airline', 'like', "%{$this->search}%")
                ->orWhere('ticket_number', 'like', "%{$this->search}%")
                ->orWhereHas('vendorBill', fn (Builder $bill) => $bill->where('vendor_bill_number', 'like', "%{$this->search}%"))
                ->orWhereHas('vendorBill.invoice.lead', fn (Builder $lead) => $lead->where('customer_name', 'like', "%{$this->search}%"))))
            ->orderByRaw('time_limit asc')
            ->paginate(20);

        return view('livewire.admin.air-tickets.index', compact('requests', 'summary', 'accountsView'))
            ->layout('components.layouts.admin', ['title' => 'Air Tickets']);
    }
}
