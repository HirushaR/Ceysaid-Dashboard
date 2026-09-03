<div class="space-y-6">
    <div class="page-heading">
        <div><p class="eyebrow">Workspace</p><h1>Sales Pipeline</h1><p>Follow every active lead from intake to confirmation.</p></div>
        <div class="flex gap-2"><a href="{{ route('admin.leads.index') }}" class="btn-secondary">Table view</a><a href="{{ route('admin.leads.create') }}" class="btn-primary">+ New lead</a></div>
    </div>

    <div class="flex flex-col justify-between gap-3 rounded-2xl border border-indigo-100 bg-gradient-to-r from-white via-blue-50/60 to-indigo-50/70 p-4 shadow-sm sm:flex-row sm:items-center dark:border-slate-800 dark:from-slate-900 dark:via-blue-950/20 dark:to-indigo-950/20">
        <div class="grid flex-1 gap-3 sm:grid-cols-[minmax(220px,440px)_180px]"><input wire:model.live.debounce.300ms="search" class="form-input" placeholder="Search customer, reference, or destination…"><select wire:model.live="type" class="form-input"><option value="">All lead types</option><option value="standard">Standard</option><option value="group">Group</option><option value="cruise">Cruise</option></select></div>
        <p class="text-xs text-slate-500">Cards are read-only. Use lead workflow actions to change stage.</p>
    </div>

    <div class="pipeline-scroll -mx-4 overflow-x-auto px-4 pb-5 lg:-mx-7 lg:px-7">
        <div class="flex min-w-max items-start gap-4">
            @foreach($columns as $columnIndex => $column)
                <section class="pipeline-column" aria-label="{{ $column['label'] }} leads">
                    <header class="flex items-center justify-between gap-2 px-1 pb-3 pt-1"><div class="flex min-w-0 items-center gap-2"><span class="pipeline-stage-number">{{ $columnIndex + 1 }}</span><h2 class="truncate text-sm font-bold">{{ $column['label'] }}</h2></div><span class="pipeline-count">{{ $column['count'] }}</span></header>
                    <div class="space-y-3">
                        @forelse($column['leads'] as $lead)
                            <a href="{{ route('admin.leads.show', $lead) }}" class="pipeline-card group">
                                <div class="flex items-start justify-between gap-2"><h3>{{ $lead->customer_name }}</h3>@if($lead->priority==='high')<span class="size-2 shrink-0 rounded-full bg-rose-500" title="High priority"></span>@endif</div>
                                <p class="mt-1 text-xs font-medium text-slate-500">{{ $lead->reference_id ?: '#'.$lead->id }}</p>
                                <div class="mt-2"><x-status-badge :status="\App\Enums\LeadStatus::tryFrom($lead->status) ?? $lead->status" /></div>
                                <div class="mt-3 flex flex-wrap gap-1.5">@if($lead->is_group_lead)<span class="pipeline-type bg-amber-50 text-amber-700 ring-1 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/20">Group</span>@elseif($lead->is_cruise_lead)<span class="pipeline-type bg-cyan-50 text-cyan-700 ring-1 ring-cyan-200 dark:bg-cyan-500/10 dark:text-cyan-300 dark:ring-cyan-500/20">Cruise</span>@else<span class="pipeline-type bg-slate-100 text-slate-600 dark:bg-slate-700 dark:text-slate-300">Standard</span>@endif @if($lead->priority==='high')<span class="pipeline-type bg-rose-50 text-rose-700 ring-1 ring-rose-200 dark:bg-rose-500/10 dark:text-rose-300 dark:ring-rose-500/20">High priority</span>@endif</div>
                                <div class="mt-4 flex items-end justify-between gap-3 text-xs"><span class="max-w-[145px] truncate font-semibold text-blue-600">{{ $lead->destination ?: 'No destination' }}</span><span class="shrink-0 text-slate-400">{{ $lead->updated_at->diffForHumans(short:true) }}</span></div>
                                <div class="mt-3 flex items-center justify-between border-t border-slate-100 pt-3 text-[11px] text-slate-500 dark:border-slate-700"><span>{{ $lead->assignedUser?->name ?? 'Sales unassigned' }}</span><span class="translate-x-0 opacity-0 transition group-hover:translate-x-1 group-hover:opacity-100">→</span></div>
                            </a>
                        @empty
                            <div class="rounded-xl border border-dashed border-slate-300 px-4 py-10 text-center text-xs text-slate-400 dark:border-slate-700">No leads in this stage</div>
                        @endforelse
                        @if($column['count'] > $column['leads']->count())<p class="py-2 text-center text-xs font-semibold text-slate-400">Showing latest {{ $column['leads']->count() }} of {{ $column['count'] }}</p>@endif
                    </div>
                </section>
            @endforeach
        </div>
    </div>
</div>
