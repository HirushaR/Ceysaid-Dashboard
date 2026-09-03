<div class="space-y-6">
    <div class="page-heading">
        <div><p class="eyebrow">CRM</p><h1>Leads</h1><p>Manage sales and operations hand-offs from one queue.</p></div>
        <a href="{{ route('admin.leads.create') }}" class="btn-primary">+ New lead</a>
    </div>
    <section class="panel">
        <div class="grid gap-3 border-b border-slate-100 p-4 sm:grid-cols-[1fr_240px] dark:border-slate-800">
            <input wire:model.live.debounce.300ms="search" class="form-input" placeholder="Search reference, customer, or contact…">
            <select wire:model.live="status" class="form-input"><option value="">All statuses</option>@foreach($statuses as $item)<option value="{{ $item->value }}">{{ $item->label() }}</option>@endforeach</select>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Lead</th><th>Contact</th><th>Status</th><th>Sales</th><th>Operations</th><th>Arrival</th></tr></thead>
                <tbody>
                    @forelse($leads as $lead)
                        <tr>
                            <td><a class="table-link" href="{{ route('admin.leads.show', $lead) }}">{{ $lead->reference_id ?: '#'.$lead->id }}</a><p class="mt-1 font-medium">{{ $lead->customer_name }}</p></td>
                            <td>{{ $lead->contact_value ?: '—' }}<p class="text-xs capitalize text-slate-500">{{ $lead->platform }}</p></td>
                            <td><x-status-badge :status="\App\Enums\LeadStatus::tryFrom($lead->status) ?? $lead->status" /></td>
                            <td>{{ $lead->assignedUser?->name ?? 'Unassigned' }}</td>
                            <td>{{ $lead->assignedOperator?->name ?? 'Unassigned' }}</td>
                            <td>{{ $lead->arrival_date?->format('d M Y') ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty-state">No leads match these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 p-4 dark:border-slate-800">{{ $leads->links() }}</div>
    </section>
</div>
