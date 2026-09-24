<div>
@if($lead->is_other_lead)
    @include('livewire.admin.leads.partials.other-workspace')
@else
<div class="space-y-5" x-data="{ tab: 'overview', actions: false }">
    <div class="flex items-center justify-between gap-4">
        <div><p class="eyebrow">Lead workspace</p><h1 class="mt-1 text-xl font-bold tracking-tight sm:text-2xl">{{ $lead->reference_id ?: '#'.$lead->id }} · {{ $lead->customer_name }}</h1></div>
        <div class="flex gap-2">@if(auth()->user()->isAdmin() || (auth()->user()->isSales() && ($lead->assigned_to===auth()->id() || $lead->created_by===auth()->id()) && auth()->user()->canEditResource('leads')) || (auth()->user()->isOperation() && $lead->assigned_operator===auth()->id()))<a href="{{ route('admin.leads.edit',$lead) }}" class="btn-primary">Edit lead</a>@endif<a href="{{ route('admin.leads.index') }}" class="btn-secondary">← Back to leads</a></div>
    </div>

    <section class="panel overflow-visible">
        <div class="flex flex-col justify-between gap-5 p-5 lg:flex-row lg:items-center">
            <div class="min-w-0"><p class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ $lead->reference_id ?: '#'.$lead->id }}</p><div class="mt-1 flex flex-wrap items-center gap-3"><h2 class="truncate text-2xl font-bold">{{ $lead->customer_name }}</h2><x-status-badge :status="\App\Enums\LeadStatus::tryFrom($lead->status) ?? $lead->status" /></div><p class="mt-1 text-sm text-slate-500">{{ $lead->destination ?: 'Destination not set' }} · {{ $lead->is_group_lead ? 'Group' : ($lead->is_cruise_lead ? 'Cruise' : 'Standard') }} · {{ $lead->assignedUser?->name ?? 'Unassigned sales' }}</p></div>
            <div class="relative shrink-0">
                <button @click="actions = !actions" class="btn-primary">Open workflow actions →</button>
                <div x-cloak x-show="actions" @click.outside="actions=false" x-transition class="absolute right-0 top-12 z-20 w-72 rounded-2xl border border-slate-200 bg-white p-2 shadow-xl dark:border-slate-700 dark:bg-slate-900">
                    @forelse($next as $status)<button wire:click="transition('{{ $status->value }}')" wire:confirm="Move this lead to {{ $status->label() }}?" @click="actions=false" class="w-full rounded-xl px-3 py-2.5 text-left text-sm font-semibold hover:bg-slate-100 dark:hover:bg-slate-800">Move to {{ $status->label() }}</button>@empty<p class="px-3 py-3 text-sm text-slate-500">No workflow actions are available for your role.</p>@endforelse
                </div>
            </div>
        </div>
        <div class="grid grid-cols-8 gap-1 px-5 pb-5" aria-label="Lead workflow progress">
            @foreach($pipeline as $index => $stage)<div class="group relative"><div class="h-1.5 rounded-full {{ $index <= $progress ? 'bg-blue-600' : 'bg-slate-200 dark:bg-slate-700' }}"></div><span class="absolute left-0 top-3 hidden whitespace-nowrap text-[10px] font-semibold text-slate-500 group-hover:block">{{ $stage->label() }}</span></div>@endforeach
        </div>
    </section>

    <nav class="admin-tabs" aria-label="Lead sections">
        @foreach(['overview'=>'Overview','requirements'=>'Requirements','conversations'=>'Conversations','quotes'=>'Quotes','files'=>'Files','operations'=>'Operations','finance'=>'Finance','activity'=>'Activity'] as $key=>$label)
            <button @click="tab='{{ $key }}'" :class="tab==='{{ $key }}' ? 'admin-tab-active' : ''" class="admin-tab">{{ $label }}</button>
        @endforeach
    </nav>

    <div x-show="tab==='overview'" class="grid gap-5 xl:grid-cols-[minmax(0,1fr)_340px]">
        <div class="space-y-5">
            <section class="panel p-5">
                <div class="flex flex-wrap items-start justify-between gap-3"><div><p class="section-label">Lead information</p><h2 class="mt-1 text-lg font-bold">Customer and trip summary</h2></div><span class="status-badge capitalize">{{ $lead->priority ?: 'medium' }} priority</span></div>
                <dl class="detail-grid mt-5">
                    <div><dt>Customer</dt><dd>{{ $lead->customer_name }}</dd></div>
                    <div><dt>Contact</dt><dd>{{ $lead->contact_value ?: 'Not provided' }}</dd></div>
                    <div><dt>Preferred contact</dt><dd class="capitalize">{{ $lead->contact_method ?: 'Not set' }}</dd></div>
                    <div><dt>Destination</dt><dd>{{ $lead->destination ?: 'Not set' }}</dd></div>
                    <div><dt>Lead type</dt><dd>{{ $lead->is_group_lead ? 'Group' : ($lead->is_cruise_lead ? 'Cruise' : 'Standard') }}</dd></div>
                    <div><dt>Source</dt><dd class="capitalize">{{ $lead->platform ?: 'Not set' }}</dd></div>
                    <div><dt>Travel dates</dt><dd>{{ $lead->arrival_date?->format('d M Y') ?? 'Not set' }} – {{ $lead->depature_date?->format('d M Y') ?? 'Not set' }}</dd></div>
                    <div><dt>Duration</dt><dd>{{ $lead->number_of_days ? $lead->number_of_days.' days' : 'Not set' }}</dd></div>
                    <div><dt>Passengers</dt><dd>{{ $lead->booked_pax }} total · {{ (int) $lead->number_of_adults }} adults · {{ (int) $lead->number_of_children }} children · {{ (int) $lead->number_of_infants }} infants</dd></div>
                    <div><dt>Created</dt><dd>{{ $lead->created_at?->format('d M Y, g:i A') }}</dd></div>
                </dl>
            </section>
            @if($lead->subject || $lead->message)
                <section class="panel p-5"><p class="section-label">Enquiry</p>@if($lead->subject)<h2 class="mt-2 text-lg font-bold">{{ $lead->subject }}</h2>@endif @if($lead->message)<p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-600 dark:text-slate-300">{{ $lead->message }}</p>@endif</section>
            @endif
        </div>
        <aside class="panel divide-y divide-slate-100 self-start dark:divide-slate-800">
            <section class="p-5"><p class="section-label">Next action</p><h3 class="mt-2 text-lg font-bold">{{ $next->first()?->label() ?? 'No action pending' }}</h3><p class="mt-1 text-sm text-slate-500">Current stage: {{ \App\Enums\LeadStatus::tryFrom($lead->status)?->label() }}</p></section>
            <section class="p-5"><p class="section-label">Commercial</p><dl class="mt-4 space-y-3"><div class="flex justify-between"><dt>Quote</dt><dd class="font-semibold">{{ $lead->quote?->quote_number ?? 'None' }}</dd></div><div class="flex justify-between"><dt>Invoices</dt><dd class="font-semibold">{{ $lead->invoices->count() }}</dd></div><div class="flex justify-between"><dt>Files</dt><dd class="font-semibold">{{ $lead->attachments->count() }}</dd></div></dl></section>
            <section class="p-5"><p class="section-label">Ownership</p><dl class="mt-4 space-y-3"><div class="flex justify-between gap-3"><dt>Sales</dt><dd class="text-right font-semibold">{{ $lead->assignedUser?->name ?? 'Unassigned' }}</dd></div><div class="flex justify-between gap-3"><dt>Operations</dt><dd class="text-right font-semibold">{{ $lead->assignedOperator?->name ?? 'Unassigned' }}</dd></div><div class="flex justify-between gap-3"><dt>Visa processing</dt><dd class="text-right font-semibold">{{ $lead->visaAssignee?->name ?? 'Unassigned' }}</dd></div><div class="flex justify-between gap-3"><dt>Created by</dt><dd class="text-right font-semibold">{{ $lead->creator?->name ?? 'System' }}</dd></div></dl></section>
        </aside>
    </div>

    <div x-cloak x-show="tab==='requirements'" class="panel p-5"><p class="section-label">Customer and trip requirements</p><dl class="detail-grid mt-5">@foreach(['Contact'=>$lead->contact_value,'Method'=>ucfirst($lead->contact_method),'Destination'=>$lead->destination ?: 'Not set','Adults'=>$lead->number_of_adults,'Children'=>$lead->number_of_children,'Infants'=>$lead->number_of_infants,'Priority'=>ucfirst($lead->priority ?? 'medium'),'Arrival'=>$lead->arrival_date?->format('d M Y') ?? 'Not set','Departure'=>$lead->depature_date?->format('d M Y') ?? 'Not set'] as $label=>$value)<div><dt>{{ $label }}</dt><dd>{{ $value }}</dd></div>@endforeach</dl></div>
    <div x-cloak x-show="tab==='conversations'" class="panel p-5"><div class="grid gap-5 lg:grid-cols-[1fr_320px]"><div><p class="section-label">Internal conversation</p><div class="mt-5 space-y-4">@forelse($lead->notes as $item)<article class="rounded-xl bg-slate-50 p-4 dark:bg-slate-800"><p class="text-sm">{{ $item->note }}</p><p class="mt-2 text-xs text-slate-500">{{ $item->user?->name }} · {{ $item->created_at->diffForHumans() }}</p></article>@empty<p class="text-sm text-slate-500">No notes yet.</p>@endforelse</div></div><form wire:submit="addNote"><label class="form-label">Add internal note</label><textarea wire:model="note" class="form-input" rows="5"></textarea>@error('note')<p class="form-error">{{ $message }}</p>@enderror<button class="btn-primary mt-3">Add note</button></form></div></div>
    <div x-cloak x-show="tab==='quotes'" class="panel p-5"><div class="flex items-center justify-between"><div><p class="section-label">Quotes</p><h2 class="mt-1 text-lg font-bold">Commercial proposals</h2></div>@if(!$lead->quote)<a class="btn-primary" href="{{ route('admin.quotes.create',['lead'=>$lead->id]) }}">+ Create quote</a>@endif</div>@if($lead->quote)<a class="mt-5 flex items-center justify-between rounded-xl border border-slate-200 p-4 hover:bg-slate-50 dark:border-slate-700 dark:hover:bg-slate-800" href="{{ route('admin.quotes.show',$lead->quote) }}"><span><strong>{{ $lead->quote->quote_number }}</strong><small class="ml-3 status-badge">{{ $lead->quote->status->label() }}</small></span><span>LKR {{ number_format($lead->quote->totalAmount(),2) }} →</span></a>@else<p class="empty-state">No quote created for this lead.</p>@endif</div>
    <div x-cloak x-show="tab==='files'" class="panel p-5"><p class="section-label">Files</p><div class="mt-5 space-y-2">@forelse($lead->attachments as $file)<div class="flex items-center justify-between rounded-xl border border-slate-200 p-3 dark:border-slate-700"><span class="text-sm font-semibold">{{ $file->original_name ?? $file->file_name ?? 'Attachment' }}</span><span class="text-xs text-slate-500">{{ $file->type }}</span></div>@empty<p class="empty-state">No files uploaded.</p>@endforelse</div></div>
    <div x-cloak x-show="tab==='operations'" class="space-y-5">
        <section class="panel p-5"><p class="section-label">Service fulfilment</p><dl class="detail-grid mt-5">@foreach(['Air ticket'=>$lead->air_ticket_status,'Hotel'=>$lead->hotel_status,'Visa'=>$lead->visa_status,'Land package'=>$lead->land_package_status] as $label=>$value)<div><dt>{{ $label }}</dt><dd><span class="status-badge">{{ str_replace('_',' ',ucfirst($value ?? 'pending')) }}</span></dd></div>@endforeach</dl></section>
        @php($issuedTickets = $lead->invoices->flatMap(fn ($invoice) => $invoice->vendorBills->pluck('airTicketRequest'))->filter(fn ($ticket) => $ticket?->status === \App\Enums\AirTicketStatus::ISSUED))
        @if($issuedTickets->isNotEmpty())
            <section class="panel"><div class="panel-header"><div><h2>Issued ticket details</h2><p>Tickets issued through the vendor bill workflow</p></div><span class="status-badge status-success">{{ $issuedTickets->count() }} issued</span></div><div class="grid gap-4 p-5 lg:grid-cols-2">@foreach($issuedTickets as $ticket)<article class="rounded-xl border border-slate-200 p-4 dark:border-slate-700"><div class="flex items-start justify-between gap-3"><div><p class="text-xs font-bold uppercase tracking-wider text-slate-400">Ticket number</p><h3 class="mt-1 text-lg font-bold">{{ $ticket->ticket_number }}</h3></div><span class="status-badge status-success">Issued</span></div><dl class="mt-4 grid gap-4 sm:grid-cols-2"><div><dt>PNR</dt><dd>{{ $ticket->booking_reference }}</dd></div><div><dt>Airline</dt><dd>{{ $ticket->airline }}</dd></div><div><dt>Provider</dt><dd>{{ Str::headline($ticket->provider) }}</dd></div><div><dt>Amount</dt><dd>LKR {{ number_format((float)$ticket->amount,2) }}</dd></div><div><dt>Issued by</dt><dd>{{ $ticket->issuedBy?->name ?? 'System' }}</dd></div><div><dt>Issued at</dt><dd>{{ $ticket->issued_at?->format('d M Y, g:i A') ?? '—' }}</dd></div></dl>@if($ticket->issue_notes)<p class="mt-4 border-t border-slate-100 pt-3 text-sm text-slate-600 dark:border-slate-800 dark:text-slate-300">{{ $ticket->issue_notes }}</p>@endif</article>@endforeach</div></section>
        @endif
    </div>
    <div x-cloak x-show="tab==='finance'" class="panel p-5"><div><p class="section-label">Finance</p><h2 class="mt-1 text-lg font-bold">Invoices and receipts</h2></div><div class="mt-5 space-y-2">@forelse($lead->invoices as $invoice)<a href="{{ route('admin.invoices.show',$invoice) }}" class="flex items-center justify-between rounded-xl border border-slate-200 p-4 dark:border-slate-700"><span><strong>{{ $invoice->invoice_number }}</strong><small class="ml-2 status-badge">{{ ucfirst($invoice->customer_payment_status) }}</small></span><span>LKR {{ number_format((float)$invoice->balance_amount,2) }} outstanding →</span></a>@empty<p class="empty-state">No invoices created.</p>@endforelse</div></div>
    <div x-cloak x-show="tab==='activity'" class="panel"><div class="panel-header"><div><h2>Lead activity</h2><p>Workflow changes and ownership updates are kept separately from the lead details.</p></div><span class="status-badge">{{ $lead->actionLogs->count() }} {{ \Illuminate\Support\Str::plural('entry', $lead->actionLogs->count()) }}</span></div><div class="divide-y divide-slate-100 dark:divide-slate-800">@forelse($lead->actionLogs as $log)<div class="flex gap-4 p-5"><span class="mt-1.5 size-2 rounded-full bg-blue-600 ring-4 ring-blue-100 dark:ring-blue-900"></span><div><p class="text-sm font-semibold">{{ $log->description }}</p><p class="mt-1 text-xs text-slate-500">{{ $log->user?->name ?? 'System' }} · {{ $log->created_at->format('d M Y, g:i A') }}</p></div></div>@empty<p class="empty-state">No lead activity has been recorded yet.</p>@endforelse</div></div>
</div>
@endif
</div>
