<x-filament-panels::page>
    <x-workspace-styles />
    @php($tasks=$this->getTasks())
    <div class="ts-grid ts-metrics">
        <div class="ts-card ts-pad"><div class="ts-label">Open work</div><div class="ts-value">{{ $tasks->count() }}</div></div>
        <div class="ts-card ts-pad"><div class="ts-label">Overdue</div><div class="ts-value">{{ $tasks->filter(fn($t)=>$t->due_at?->isPast())->count() }}</div></div>
        <div class="ts-card ts-pad"><div class="ts-label">Due today</div><div class="ts-value">{{ $tasks->filter(fn($t)=>$t->due_at?->isToday())->count() }}</div></div>
        <div class="ts-card ts-pad"><div class="ts-label">Waiting</div><div class="ts-value">{{ $tasks->where('status',\App\Enums\WorkflowTaskStatus::Waiting)->count() }}</div></div>
    </div>
    <div class="ts-card"><div class="ts-pad"><div class="ts-label">Prioritized queue</div></div><div style="overflow-x:auto"><table class="ts-table"><thead><tr><th>Task</th><th>Lead</th><th>Customer</th><th>Due</th><th>Status</th></tr></thead><tbody>
    @forelse($tasks as $task)<tr><td><strong>{{ $task->title }}</strong><div class="ts-muted">{{ str($task->task_type)->replace('_',' ')->title() }}</div></td><td><a class="ts-link" href="{{ \App\Filament\Pages\LeadWorkspace::getUrl(['lead'=>$task->lead_id]) }}">{{ $task->lead?->reference_id }}</a></td><td>{{ $task->lead?->customer_name }}</td><td @class(['text-danger-600 font-semibold'=>$task->due_at?->isPast()])>{{ $task->due_at?->diffForHumans() ?? 'Not scheduled' }}</td><td><span class="ts-badge">{{ str($task->status->value)->replace('_',' ')->title() }}</span></td></tr>@empty<tr><td colspan="5" style="padding:3rem;text-align:center" class="ts-muted">Your queue is clear.</td></tr>@endforelse
    </tbody></table></div></div>
</x-filament-panels::page>
