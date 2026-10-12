<div class="mx-auto max-w-5xl space-y-6">
    <div class="page-heading">
        <div>
            <p class="eyebrow">WhatsApp / Conversation</p>
            <h1>{{ $conversation->contact?->displayName() ?: 'Unknown contact' }}</h1>
            <p>{{ $conversation->contact?->phone }} · Assigned to {{ $conversation->assignedUser?->name ?: 'nobody yet' }}</p>
        </div>
        <div class="flex gap-2">
            @if($conversation->lead)
                <a href="{{ route('admin.leads.show', $conversation->lead) }}" class="btn-secondary">View lead</a>
            @elseif($conversation->isAssigned())
                <button wire:click="convert" wire:confirm="Create a lead from this conversation?" class="btn-primary">Create lead</button>
            @endif
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-[1fr_260px]">
        <section class="panel overflow-hidden">
            <div class="max-h-[60vh] space-y-3 overflow-y-auto p-5" wire:poll.15s>
                @foreach($conversation->messages as $message)
                    <div class="flex {{ $message->isOutbound() ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-[78%] rounded-2xl px-4 py-3 {{ $message->isOutbound() ? 'bg-blue-600 text-white' : 'bg-slate-100 dark:bg-slate-800' }}">
                            @if($message->body)
                                <p class="whitespace-pre-wrap text-sm">{{ $message->body }}</p>
                            @endif
                            @if($message->hasMedia())
                                <a href="{{ route('whatsapp.media', $message) }}" target="_blank" class="mt-2 flex items-center gap-2 text-sm underline">
                                    <span aria-hidden="true">📎</span>
                                    <span class="truncate">{{ $message->mediaLabel() }}</span>
                                </a>
                            @endif
                            <p class="mt-1 text-right text-[10px] opacity-70">{{ $message->sent_at?->format('d M H:i') }}</p>
                        </div>
                    </div>
                @endforeach
            </div>

            @if($conversation->isAssigned())
            <form wire:submit="send" class="border-t border-slate-100 p-4 dark:border-slate-800">
                @if($attachment)
                    <div class="mb-3 flex items-center justify-between gap-3 rounded-xl border border-blue-200 bg-blue-50 px-3 py-2 text-sm text-blue-900 dark:border-blue-800 dark:bg-blue-950 dark:text-blue-100">
                        <div class="min-w-0">
                            <p class="truncate font-semibold">{{ $attachment->getClientOriginalName() }}</p>
                            <p class="text-xs opacity-70">{{ number_format($attachment->getSize() / 1024, 0) }} KB</p>
                        </div>
                        <button type="button" wire:click="removeAttachment" class="font-semibold hover:underline">Remove</button>
                    </div>
                @endif

                <div class="flex items-end gap-3">
                    <label class="btn-secondary cursor-pointer" title="Attach a file">
                        <span aria-hidden="true">📎</span>
                        <span class="sr-only">Attach file</span>
                        <input wire:model="attachment" type="file" class="sr-only" accept=".jpg,.jpeg,.png,.webp,.mp4,.3gp,.mp3,.ogg,.aac,.m4a,.pdf,.doc,.docx,.xls,.xlsx">
                    </label>
                    <textarea wire:model="reply" rows="2" class="form-input" placeholder="Write a message or add a caption"></textarea>
                    <button class="btn-primary" wire:loading.attr="disabled" wire:target="send,attachment">
                        <span wire:loading.remove wire:target="send">Send</span>
                        <span wire:loading wire:target="send">Sending…</span>
                    </button>
                </div>
                <div wire:loading wire:target="attachment" class="mt-2 text-xs text-slate-500">Uploading attachment…</div>
                @error('reply')<p class="form-error mt-2">{{ $message }}</p>@enderror
                @error('attachment')<p class="form-error mt-2">{{ $message }}</p>@enderror
            </form>
            @else
                <div class="border-t border-amber-200 bg-amber-50 p-4 text-sm text-amber-900 dark:border-amber-900 dark:bg-amber-950 dark:text-amber-100">
                    Assign this conversation to a sales team member before replying or creating a lead.
                </div>
            @endif
        </section>

        <aside class="panel p-5">
            <h2 class="font-bold">Organize chat</h2>
            @if($canAssign)
                <div class="mt-4">
                    <label class="form-label">Assigned salesperson</label>
                    <select wire:change="assignConversation($event.target.value)" class="form-input">
                        <option value="">Unassigned</option>
                        @foreach($assignmentTeam as $member)
                            <option value="{{ $member->id }}" @selected($conversation->assigned_to === $member->id)>{{ $member->name }}</option>
                        @endforeach
                    </select>
                    <p class="mt-2 text-xs text-slate-500">Changing the assignee moves the chat to that salesperson’s inbox.</p>
                </div>
            @endif
            @if($conversation->isAssignedTo(auth()->user()))
            <div class="mt-4">
                <label class="form-label">Folder</label>
                <select wire:model="folder_id" wire:change="move" class="form-input">
                    <option value="">Unfiled</option>
                    @foreach($folders as $folder)
                        <option value="{{ $folder->id }}">{{ $folder->name }}</option>
                    @endforeach
                </select>
            </div>
            @endif
            @if($conversation->hasAdAttribution())
                <div class="mt-6">
                    <p class="text-xs font-bold uppercase text-slate-500">Ad attribution</p>
                    <p class="mt-2 text-sm">{{ $conversation->referral_headline ?: $conversation->referral_source_id }}</p>
                    @if($conversation->adUrl())
                        <a href="{{ $conversation->adUrl() }}" target="_blank" class="mt-2 inline-block text-sm font-semibold text-blue-600">View source</a>
                    @endif
                </div>
            @endif
        </aside>
    </div>
</div>
