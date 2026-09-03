<div class="space-y-6">
    <div class="page-heading"><div><p class="eyebrow">Workspace</p><h1>Notifications</h1><p>Assignment, workflow and finance alerts.</p></div><button wire:click="markAllRead" class="btn-secondary">Mark all read</button></div>
    <div class="admin-tabs"><button wire:click="$set('filter','all')" @class(['admin-tab','admin-tab-active'=>$filter==='all'])>All</button><button wire:click="$set('filter','unread')" @class(['admin-tab','admin-tab-active'=>$filter==='unread'])>Unread</button></div>
    <section class="panel divide-y divide-slate-100 dark:divide-slate-800">
        @forelse($notifications as $notification)
            @php($action = collect(data_get($notification->data,'actions',[]))->first())
            <article class="p-5 {{ $notification->read_at?'':'bg-blue-50/50 dark:bg-blue-500/5' }}" wire:key="notification-{{ $notification->id }}">
                <div class="flex gap-4"><span class="mt-1 size-2 shrink-0 rounded-full {{ $notification->read_at?'bg-slate-300':'bg-blue-600' }}"></span><div class="min-w-0 flex-1"><p class="font-semibold">{{ data_get($notification->data,'title','Notification') }}</p><p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ data_get($notification->data,'body',data_get($notification->data,'message')) }}</p><p class="mt-2 text-xs text-slate-400">{{ $notification->created_at->diffForHumans() }}</p><div class="mt-3 flex gap-2">@if($action && data_get($action,'url'))<a href="{{ data_get($action,'url') }}" wire:click="markRead('{{ $notification->id }}')" class="btn-primary">{{ data_get($action,'label','Open') }}</a>@endif @unless($notification->read_at)<button wire:click="markRead('{{ $notification->id }}')" class="btn-secondary">Mark read</button>@endunless<button wire:click="delete('{{ $notification->id }}')" wire:confirm="Remove this notification?" class="btn-secondary">Remove</button></div></div></div>
            </article>
        @empty<div class="empty-state"><p class="font-semibold">You’re all caught up</p><p class="mt-1 text-sm">No notifications match this filter.</p></div>@endforelse
    </section>
    {{ $notifications->links() }}
</div>
