<div class="space-y-6">
    <div class="page-heading">
        <div><p class="eyebrow">Operations</p><h1>Air Tickets</h1><p>Accounts approval and ticket issuing from queued vendor bills.</p></div>
    </div>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Air ticket summary">
        @foreach($accountsView ? [['Pending approval',$summary['awaiting_approval']],['Approved',$summary['approved']],['Issued',$summary['issued']],['Returned / rejected',$summary['review_closed']]] : [['Pending',$summary['pending']],['Accounts approval',$summary['awaiting_approval']],['Ready to issue',$summary['ready']],['Issued',$summary['issued']]] as [$label,$value])
            <article class="metric-card"><p>{{ $label }}</p><strong>{{ $value }}</strong></article>
        @endforeach
    </section>

    @if($reviewing_id)
        <form wire:submit="approve" class="panel p-5">
            <div class="flex flex-wrap items-start justify-between gap-3"><div><p class="section-label">Accounts review</p><h2 class="mt-1 text-lg font-bold">Approve or return the ticket request</h2></div><button type="button" wire:click="$set('reviewing_id', null)" class="btn-secondary">Close</button></div>
            <div class="mt-5"><label class="form-label">Review notes</label><textarea wire:model="review_notes" rows="3" class="form-input" placeholder="Required when returning or rejecting"></textarea>@error('review_notes')<p class="form-error">{{ $message }}</p>@enderror</div>
            <div class="mt-4 flex flex-wrap justify-end gap-2"><button type="button" wire:click="reject" wire:confirm="Reject this ticket request?" class="btn-secondary text-rose-600">Reject</button><button type="button" wire:click="returnForCorrection" class="btn-secondary">Return for correction</button><button class="btn-primary">Approve for ticketing</button></div>
        </form>
    @endif

    @if($issuing_id)
        <form wire:submit="issue" class="panel p-5">
            <div class="flex flex-wrap items-start justify-between gap-3"><div><p class="section-label">Issue ticket</p><h2 class="mt-1 text-lg font-bold">Record issued ticket details</h2></div><button type="button" wire:click="$set('issuing_id', null)" class="btn-secondary">Close</button></div>
            <div class="mt-5 grid gap-5 md:grid-cols-2"><div><label class="form-label">Ticket number *</label><input wire:model="ticket_number" class="form-input" placeholder="Enter ticket number">@error('ticket_number')<p class="form-error">{{ $message }}</p>@enderror</div><div><label class="form-label">Issued at *</label><input wire:model="issued_at" type="datetime-local" class="form-input">@error('issued_at')<p class="form-error">{{ $message }}</p>@enderror</div><div class="md:col-span-2"><label class="form-label">Issue notes</label><textarea wire:model="issue_notes" rows="3" class="form-input"></textarea></div></div>
            <div class="mt-4 flex justify-end"><button class="btn-primary">Confirm ticket issued</button></div>
        </form>
    @endif

    <section class="panel">
        <div class="flex flex-col gap-3 border-b border-slate-100 p-4 sm:flex-row sm:items-center sm:justify-between dark:border-slate-800">
            <nav class="admin-tabs border-0" aria-label="Ticket queues"><button wire:click="selectTab('pending')" @class(['admin-tab','admin-tab-active'=>$tab==='pending'])>Pending <span class="ml-1 rounded-full bg-slate-100 px-2 py-0.5 text-xs dark:bg-slate-800">{{ $summary['pending'] }}</span></button>@if($accountsView)<button wire:click="selectTab('approved')" @class(['admin-tab','admin-tab-active'=>$tab==='approved'])>Approved <span class="ml-1 rounded-full bg-slate-100 px-2 py-0.5 text-xs dark:bg-slate-800">{{ $summary['approved'] }}</span></button>@else<button wire:click="selectTab('issued')" @class(['admin-tab','admin-tab-active'=>$tab==='issued'])>Issued <span class="ml-1 rounded-full bg-slate-100 px-2 py-0.5 text-xs dark:bg-slate-800">{{ $summary['issued'] }}</span></button>@endif</nav>
            <input wire:model.live.debounce.300ms="search" class="form-input w-full sm:max-w-sm" placeholder="Search bill, PNR, airline, customer…">
        </div>

        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Vendor bill</th><th>Customer / Supplier</th><th>PNR / Airline</th><th>Time limit</th><th>Amount</th><th>Status</th><th>Action</th></tr></thead>
                <tbody>
                    @forelse($requests as $ticket)
                        <tr>
                            <td>@if(auth()->user()->can('view',$ticket->vendorBill))<a class="table-link" href="{{ route('admin.vendor-bills.show',$ticket->vendorBill) }}">{{ $ticket->vendorBill?->vendor_bill_number }}</a>@else<span class="font-semibold">{{ $ticket->vendorBill?->vendor_bill_number }}</span>@endif<p class="mt-1 text-xs text-slate-500">{{ Str::headline($ticket->provider) }}</p></td>
                            <td><p class="font-semibold">{{ $ticket->vendorBill?->invoice?->lead?->customer_name ?? 'No linked customer' }}</p><p class="mt-1 text-xs text-slate-500">{{ $ticket->vendorBill?->supplier?->name ?? $ticket->vendorBill?->vendor_name }}</p></td>
                            <td><p class="font-semibold">{{ $ticket->booking_reference }}</p><p class="mt-1 text-xs text-slate-500">{{ $ticket->airline }}</p>@if($ticket->ticket_number)<p class="mt-1 text-xs font-semibold text-emerald-600">Ticket {{ $ticket->ticket_number }}</p>@endif</td>
                            <td @class(['font-semibold','text-rose-600'=>$ticket->isTimeLimitExpired()])>{{ $ticket->time_limit?->format('d M Y, g:i A') }}@if($ticket->isTimeLimitExpired())<p class="mt-1 text-xs">Time limit expired</p>@endif</td>
                            <td class="font-semibold">LKR {{ number_format((float)$ticket->amount,2) }}</td>
                            <td><span class="status-badge {{ $ticket->status->badgeClass() }}">{{ $ticket->isTimeLimitExpired() ? 'Time Limit Expired' : $ticket->status->label() }}</span>@if($ticket->decision_notes)<p class="mt-2 max-w-xs text-xs text-slate-500">{{ $ticket->decision_notes }}</p>@endif</td>
                            <td>
                                <div class="flex flex-wrap gap-2">
                                    @if($ticket->status===\App\Enums\AirTicketStatus::PENDING_APPROVAL && auth()->user()->canApproveAirTickets())<button wire:click="openReview({{ $ticket->id }})" class="text-sm font-semibold text-blue-600">Review</button>@endif
                                    @if($ticket->status===\App\Enums\AirTicketStatus::RETURNED_FOR_CORRECTION && auth()->user()->canQueueAirTickets() && auth()->user()->can('view',$ticket->vendorBill))<a href="{{ route('admin.vendor-bills.queue-to-issue',$ticket->vendorBill) }}" class="text-sm font-semibold text-amber-600">Correct</a>@endif
                                    @if($ticket->status===\App\Enums\AirTicketStatus::READY_FOR_TICKETING && auth()->user()->canIssueAirTickets() && !$ticket->isTimeLimitExpired())<button wire:click="openIssue({{ $ticket->id }})" class="text-sm font-semibold text-blue-600">Issue ticket</button><button wire:click="hold({{ $ticket->id }})" class="text-sm font-semibold text-slate-500">Hold</button>@endif
                                    @if($ticket->status===\App\Enums\AirTicketStatus::ON_HOLD && auth()->user()->canIssueAirTickets() && !$ticket->isTimeLimitExpired())<button wire:click="resume({{ $ticket->id }})" class="text-sm font-semibold text-blue-600">Resume</button>@endif
                                    @if($ticket->status===\App\Enums\AirTicketStatus::ISSUED)<span class="text-xs text-slate-500">{{ $ticket->issuedBy?->name }}<br>{{ $ticket->issued_at?->format('d M Y, g:i A') }}</span>@endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="empty-state">No {{ $tab }} air tickets found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 p-4 dark:border-slate-800">{{ $requests->links() }}</div>
    </section>
</div>
