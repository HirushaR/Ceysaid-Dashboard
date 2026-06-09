<x-filament-panels::page>
    <div wire:poll.15s="refreshMessages" class="space-y-4">
        <div class="rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
            <div class="border-b border-gray-200 px-4 py-3 dark:border-gray-700">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div>
                        <p class="text-sm font-semibold text-gray-950 dark:text-white">
                            {{ $record->contact?->displayName() }}
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                            {{ $record->contact?->phone }}
                        </p>
                    </div>
                    <div class="flex flex-col items-end gap-2">
                        <div class="flex flex-wrap items-center justify-end gap-2">
                            @if ($record->lead_id)
                                <x-filament::button
                                    tag="a"
                                    href="{{ \App\Filament\Resources\LeadResource::getUrl('view', ['record' => $record->lead]) }}"
                                    color="gray"
                                    icon="heroicon-o-user"
                                    size="sm"
                                >
                                    View lead{{ $record->lead?->reference_id ? ': '.$record->lead->reference_id : '' }}
                                </x-filament::button>
                            @else
                                {{ ($this->getAction('createLead')) }}
                            @endif
                        </div>
                        @if ($record->hasAdAttribution())
                            @if ($record->referral_headline)
                                <p class="max-w-xs text-right text-xs text-gray-500 dark:text-gray-400">
                                    {{ $record->referral_headline }}
                                </p>
                            @endif
                            @if ($adUrl = $record->adUrl())
                                <a
                                    href="{{ $adUrl }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    class="inline-flex items-center gap-1 rounded-md bg-primary-50 px-2 py-1 text-xs font-medium text-primary-700 hover:bg-primary-100 dark:bg-primary-500/10 dark:text-primary-400 dark:hover:bg-primary-500/20"
                                >
                                    <x-heroicon-o-arrow-top-right-on-square class="h-3.5 w-3.5" />
                                    Open ad
                                </a>
                            @endif
                        @endif
                    </div>
                </div>
            </div>

            @if ($record->hasAdAttribution())
                <div class="flex flex-wrap items-center gap-3 border-b border-gray-200 bg-gray-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-800/50">
                    @if ($adImageUrl = $record->adImageUrl())
                        <a href="{{ $record->adUrl() }}" target="_blank" rel="noopener noreferrer" class="shrink-0">
                            <img
                                src="{{ $adImageUrl }}"
                                alt="Ad preview"
                                class="h-14 w-14 rounded-lg border border-gray-200 object-cover dark:border-gray-600"
                            />
                        </a>
                    @endif
                    <div class="min-w-0 flex-1 text-xs text-gray-600 dark:text-gray-300">
                        <p class="font-medium text-gray-800 dark:text-gray-200">Click-to-WhatsApp ad</p>
                        @if ($record->referral_headline)
                            <p class="mt-0.5 text-gray-500 dark:text-gray-400">{{ $record->referral_headline }}</p>
                        @endif
                        @if ($record->referral_source_id)
                            <p class="mt-0.5">Ad ID: <span class="font-mono">{{ $record->referral_source_id }}</span></p>
                        @endif
                    </div>
                </div>
            @endif

            <div class="flex max-h-[32rem] flex-col gap-3 overflow-y-auto px-4 py-4">
                @forelse ($record->messages as $message)
                    <div @class([
                        'flex',
                        'justify-end' => $message->isOutbound(),
                        'justify-start' => $message->isInbound(),
                    ])>
                        <div @class([
                            'max-w-[75%] rounded-2xl px-4 py-2 text-sm shadow-sm',
                            'bg-primary-600 text-white' => $message->isOutbound(),
                            'bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-gray-100' => $message->isInbound(),
                        ])>
                            @if ($message->isInbound() && ($adUrl = $message->adUrl()))
                                <a
                                    href="{{ $adUrl }}"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    @class([
                                        'mb-2 inline-flex items-center gap-1 rounded-md px-2 py-1 text-[11px] font-medium',
                                        'bg-white/20 text-white hover:bg-white/30' => $message->isOutbound(),
                                        'bg-primary-50 text-primary-700 hover:bg-primary-100 dark:bg-primary-500/10 dark:text-primary-400' => $message->isInbound(),
                                    ])
                                >
                                    <x-heroicon-o-megaphone class="h-3 w-3" />
                                    From ad · open link
                                </a>
                            @endif

                            @if ($message->body)
                                <p class="whitespace-pre-wrap">{{ $message->body }}</p>
                            @endif

                            @if ($message->media_path && str_starts_with((string) $message->media_mime_type, 'image/'))
                                <img
                                    src="{{ route('whatsapp.media', $message) }}"
                                    alt="WhatsApp media"
                                    class="mt-2 max-h-64 rounded-lg"
                                />
                            @elseif ($message->media_path)
                                <a
                                    href="{{ route('whatsapp.media', $message) }}"
                                    target="_blank"
                                    class="mt-2 inline-flex text-xs underline"
                                >
                                    Download {{ ucfirst($message->type) }}
                                </a>
                            @elseif ($message->media_id && ! $message->media_path)
                                <p class="mt-1 text-xs opacity-70">Media downloading…</p>
                            @endif

                            <div @class([
                                'mt-1 flex items-center gap-2 text-[11px]',
                                'text-primary-100' => $message->isOutbound(),
                                'text-gray-500 dark:text-gray-400' => $message->isInbound(),
                            ])>
                                <span>{{ ($message->sent_at ?? $message->created_at)?->timezone(config('app.timezone'))->format('M j, g:i A') }}</span>
                                @if ($message->isOutbound())
                                    <span>{{ ucfirst($message->status) }}</span>
                                @endif
                                @if ($message->sentByUser)
                                    <span>· {{ $message->sentByUser->name }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                        No messages in this conversation yet.
                    </p>
                @endforelse
            </div>

            <div class="border-t border-gray-200 px-4 py-4 dark:border-gray-700">
                <form wire:submit="sendReply" class="space-y-3">
                    <textarea
                        wire:model="replyBody"
                        rows="3"
                        placeholder="Type your reply…"
                        class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"
                    ></textarea>
                    @error('replyBody')
                        <p class="text-xs text-danger-600">{{ $message }}</p>
                    @enderror
                    <div class="flex justify-end">
                        <x-filament::button type="submit">
                            Send reply
                        </x-filament::button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-filament-panels::page>
