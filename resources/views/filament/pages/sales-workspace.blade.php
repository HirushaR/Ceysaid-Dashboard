<x-filament-panels::page>
    <x-workspace-styles />
    @php($metrics = $this->getMetrics())
    <div class="ts-grid ts-metrics">
        @foreach(['active'=>'Active leads','unassigned'=>'Unassigned','due_today'=>'Due today','overdue'=>'Overdue'] as $key=>$label)
            <div class="ts-card ts-pad"><div class="ts-label">{{ $label }}</div><div class="ts-value">{{ number_format($metrics[$key]) }}</div></div>
        @endforeach
    </div>
    <div class="ts-card">
        <div class="ts-pad ts-toolbar">
            <input class="ts-input" wire:model.live.debounce.300ms="search" placeholder="Search reference, customer or destination">
            <select class="ts-select" wire:model.live="stage"><option value="">All lifecycle stages</option>@foreach(\App\Enums\LeadLifecycleStage::cases() as $stage)<option value="{{ $stage->value }}">{{ str($stage->value)->replace('_',' ')->title() }}</option>@endforeach</select>
            <a class="ts-link" href="{{ \App\Filament\Pages\SalesPipeline::getUrl() }}">Open pipeline →</a>
        </div>
        @php($leads = $this->getLeads())
        <div style="overflow-x:auto"><table class="ts-table"><thead><tr><th>Lead</th><th>Customer</th><th>Lifecycle</th><th>Owner</th><th>Next action</th><th>Destination</th></tr></thead><tbody>
        @forelse($leads as $lead)<tr><td><a class="ts-link" href="{{ \App\Filament\Pages\LeadWorkspace::getUrl(['lead'=>$lead->id]) }}">{{ $lead->reference_id }}</a><div class="ts-muted">#{{ $lead->id }}</div></td><td><strong>{{ $lead->customer_name }}</strong><div class="ts-muted">{{ $lead->platform }}</div></td><td><span class="ts-badge">{{ str($lead->lifecycle_stage?->value ?? 'unmapped')->replace('_',' ')->title() }}</span></td><td>{{ $lead->salesOwner?->name ?? 'Unassigned' }}</td><td @class(['text-danger-600'=>$lead->next_action_at?->isPast()])>{{ $lead->next_action_at?->diffForHumans() ?? 'No task scheduled' }}</td><td>{{ $lead->destination ?: '—' }}</td></tr>@empty<tr><td colspan="6" style="padding:3rem;text-align:center" class="ts-muted">No leads match this view.</td></tr>@endforelse
        </tbody></table></div><div class="ts-pad">{{ $leads->links() }}</div>
    </div>
</x-filament-panels::page>
