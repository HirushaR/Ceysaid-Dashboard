<div class="space-y-6">
    <div class="page-heading">
        <div>
            <p class="eyebrow">WhatsApp</p>
            <h1>Inbox</h1>
            <p>Assign new conversations, manage folders, and convert chats into leads.</p>
        </div>
        @if($canAssign)
            <a href="{{ route('admin.whatsapp.index', ['scope' => 'unassigned']) }}" class="btn-secondary">
                Unassigned
                @if($unassignedCount)
                    <span class="status-badge ml-2">{{ $unassignedCount }}</span>
                @endif
            </a>
        @endif
    </div>

    <section class="panel">
        <div class="grid gap-3 p-4 md:grid-cols-3">
            <input wire:model.live.debounce.300ms="search" class="form-input" placeholder="Search contact or phone">
            <select wire:model.live="scope" class="form-input">
                <option value="mine">My chats</option>
                @if($canAssign)
                    <option value="unassigned">Unassigned ({{ $unassignedCount }})</option>
                    <option value="all">All chats</option>
                @endif
            </select>
            <select wire:model.live="folder" class="form-input">
                <option value="">All folders</option>
                @foreach($folders as $item)
                    <option value="{{ $item->id }}">{{ $item->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="divide-y divide-slate-100 dark:divide-slate-800">
            @forelse($conversations as $chat)
                <article class="flex items-center gap-4 p-5 hover:bg-slate-50 dark:hover:bg-slate-800" wire:key="whatsapp-chat-{{ $chat->id }}">
                    <a href="{{ route('admin.whatsapp.show', $chat) }}" class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <p class="font-semibold">{{ $chat->contact?->displayName() ?: 'Unknown contact' }}</p>
                            @if($chat->unread_count)
                                <span class="status-badge">{{ $chat->unread_count }}</span>
                            @endif
                            @unless($chat->assigned_to)
                                <span class="status-badge bg-amber-100 text-amber-800">Needs assignment</span>
                            @endunless
                        </div>
                        <p class="truncate text-sm text-slate-500">{{ $chat->last_message_preview ?: 'No messages' }}</p>
                    </a>

                    @if($canAssign)
                        <div class="w-52 shrink-0">
                            <label class="sr-only" for="assignee-{{ $chat->id }}">Assign conversation</label>
                            <select id="assignee-{{ $chat->id }}" wire:change="assignConversation({{ $chat->id }}, $event.target.value)" class="form-input text-sm">
                                <option value="">Unassigned</option>
                                @foreach($assignmentTeam as $member)
                                    <option value="{{ $member->id }}" @selected($chat->assigned_to === $member->id)>{{ $member->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @else
                        <div class="shrink-0 text-right text-xs text-slate-500">
                            <p>{{ $chat->folder?->name ?: 'Unfiled' }}</p>
                            <p>{{ $chat->last_message_at?->diffForHumans() }}</p>
                        </div>
                    @endif
                </article>
            @empty
                <div class="empty-state">No conversations found.</div>
            @endforelse
        </div>
        <div class="p-4">{{ $conversations->links() }}</div>
    </section>
</div>
