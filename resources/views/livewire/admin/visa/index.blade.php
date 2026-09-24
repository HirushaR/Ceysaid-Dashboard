<div class="space-y-6">
    <div class="page-heading">
        <div><p class="eyebrow">Operations</p><h1>Visa Processing</h1><p>Confirmed leads ready for visa allocation and processing.</p></div>
    </div>

    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Visa queue summary">
        @foreach([['Confirmed leads',$summary['total']],['Unassigned',$summary['unassigned']],['In progress',$summary['in_progress']],['Completed',$summary['done']]] as [$label,$value])
            <article class="metric-card"><p>{{ $label }}</p><strong>{{ $value }}</strong></article>
        @endforeach
    </section>

    <section class="panel">
        <div class="grid gap-3 border-b border-slate-100 p-4 md:grid-cols-3 dark:border-slate-800">
            <input wire:model.live.debounce.300ms="search" class="form-input" placeholder="Search lead, customer, destination, or contact…">
            <select wire:model.live="status" class="form-input"><option value="">All visa statuses</option>@foreach($serviceStatuses as $value=>$label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select>
            @if($canAssign)<select wire:model.live="assignee" class="form-input"><option value="">All assignees</option><option value="unassigned">Unassigned</option>@foreach($visaTeam as $member)<option value="{{ $member->id }}">{{ $member->name }}</option>@endforeach</select>@endif
        </div>

        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Lead</th><th>Customer</th><th>Destination</th><th>Travel dates</th><th>Visa status</th><th>Visa assignee</th></tr></thead>
                <tbody>
                    @forelse($leads as $lead)
                        <tr @class(['lead-row-high-priority' => $lead->priority === 'high'])>
                            <td><a class="table-link" href="{{ route('admin.leads.show',$lead) }}">{{ $lead->reference_id ?: '#'.$lead->id }}</a><p class="mt-1 text-xs text-slate-500">{{ \App\Enums\LeadStatus::tryFrom($lead->status)?->label() }}</p></td>
                            <td><p class="font-semibold">{{ $lead->customer_name }}</p><p class="mt-1 text-xs text-slate-500">{{ $lead->contact_value ?: 'No contact' }}</p></td>
                            <td>{{ $lead->destination ?: '—' }}</td>
                            <td>{{ $lead->arrival_date?->format('d M Y') ?? '—' }}<p class="mt-1 text-xs text-slate-500">to {{ $lead->depature_date?->format('d M Y') ?? '—' }}</p></td>
                            <td>
                                @if(auth()->user()->canProcessVisaLead($lead))
                                    <select wire:change="updateVisaStatus({{ $lead->id }}, $event.target.value)" class="form-input min-w-36" aria-label="Visa status for {{ $lead->customer_name }}">@foreach($serviceStatuses as $value=>$label)<option value="{{ $value }}" @selected($lead->visa_status===$value)>{{ $label }}</option>@endforeach</select>
                                @else
                                    <span class="status-badge">{{ \App\Enums\ServiceStatus::tryFrom($lead->visa_status)?->getLabel() ?? ucfirst($lead->visa_status) }}</span>
                                @endif
                            </td>
                            <td>
                                @if($canAssign)
                                    <select wire:change="assignVisa({{ $lead->id }}, $event.target.value)" class="form-input min-w-44" aria-label="Visa assignee for {{ $lead->customer_name }}"><option value="">Unassigned</option>@foreach($visaTeam as $member)<option value="{{ $member->id }}" @selected($lead->visa_assigned_to===$member->id)>{{ $member->name }}</option>@endforeach</select>
                                @else
                                    <span class="font-semibold">{{ $lead->visaAssignee?->name ?? 'Unassigned' }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty-state">No confirmed visa leads match these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 p-4 dark:border-slate-800">{{ $leads->links() }}</div>
    </section>
</div>
