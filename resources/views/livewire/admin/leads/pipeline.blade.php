<div class="space-y-6">
    <div class="page-heading">
        <div><p class="eyebrow">Workspace</p><h1>Sales Pipeline</h1><p>Follow every active lead from intake to confirmation.</p></div>
        <div class="flex gap-2"><a href="{{ route('admin.leads.index') }}" class="btn-secondary">Table view</a><a href="{{ route('admin.leads.create') }}" class="btn-primary">+ New lead</a></div>
    </div>

    <div class="flex flex-col justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-4 sm:flex-row sm:items-center dark:border-slate-800 dark:bg-slate-900">
        <div class="grid flex-1 gap-3 sm:grid-cols-[minmax(220px,440px)_180px]"><input wire:model.live.debounce.300ms="search" class="form-input" placeholder="Search customer, reference, or destination…"><select wire:model.live="type" class="form-input"><option value="">All lead types</option><option value="standard">Standard</option><option value="group">Group</option><option value="cruise">Cruise</option></select></div>
        <p class="text-xs text-slate-500">Cards are read-only. Use lead workflow actions to change stage.</p>
    </div>

    <div class="pipeline-scroll -mx-4 overflow-x-auto px-4 pb-5 lg:-mx-7 lg:px-7">
        <div class="flex min-w-max items-start gap-4">
            @foreach($columns as $column)
                <section class="pipeline-column" aria-label="{{ $column['label'] }} leads">
                    <header class="flex items-center justify-between px-1 pb-3"><h2 class="text-sm font-bold">{{ $column['label'] }}</h2><span class="grid min-w-6 place-items-center rounded-full bg-white px-2 py-1 text-xs font-bold text-blue-700 shadow-sm dark:bg-slate-800 dark:text-blue-300">{{ $column['count'] }}</span></header>
                    <div class="space-y-3">
                        @forelse($column['leads'] as $lead)
                            <a href="{{ route('admin.leads.show', $lead) }}" class="pipeline-card group">
                                <div class="flex items-start justify-between gap-2"><h3>{{ $lead->customer_name }}</h3>@if($lead->priority==='high')<span class="size-2 shrink-0 rounded-full bg-rose-500" title="High priority"></span>@endif</div>
                                <p class="mt-1 text-xs font-medium text-slate-500">{{ $lead->reference_id ?: '#'.$lead->id }}</p>
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
