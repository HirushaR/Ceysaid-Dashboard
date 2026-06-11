<?php

namespace App\Filament\Resources\MyWhatsAppChatResource\Pages;

use App\Filament\Resources\LeadResource;
use App\Filament\Resources\MyWhatsAppChatResource;
use App\Jobs\SendWhatsAppMessageJob;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use App\Services\WhatsAppLeadService;
use App\Support\WhatsAppMediaStorage;
use Filament\Actions;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class ConversationPage extends ViewRecord
{
    use WithFileUploads;

    protected static string $resource = MyWhatsAppChatResource::class;

    protected static string $view = 'filament.resources.whats-app-inbox-resource.pages.conversation-page';

    protected static ?string $title = 'WhatsApp Conversation';

    protected static bool $shouldRegisterNavigation = false;

    public string $replyBody = '';

    public $attachment = null;

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        $this->authorizeAccess();

        $this->record->load(['contact', 'lead', 'messages.sentByUser']);
        $this->record->markAsRead();
    }

    protected function authorizeAccess(): void
    {
        $user = auth()->user();

        if (! $user) {
            abort(403);
        }

        if ($user->isAdmin() && $this->record->isAssigned()) {
            return;
        }

        if ($user->isSales() && $this->record->isAssignedTo($user)) {
            return;
        }

        abort(403);
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([]);
    }

    public function getTitle(): string
    {
        return $this->record->contact?->displayName() ?? 'WhatsApp Conversation';
    }

    protected function getHeaderActions(): array
    {
        $actions = [
            Actions\Action::make('back')
                ->label('Back to my chats')
                ->url(MyWhatsAppChatResource::getUrl('index'))
                ->color('gray')
                ->button(),
        ];

        if ($leadUrl = $this->leadViewUrl()) {
            $actions[] = Actions\Action::make('view_lead')
                ->label('View lead')
                ->icon('heroicon-o-user')
                ->url($leadUrl)
                ->button();
        }

        if ($this->record->hasAdAttribution() && $this->record->adUrl()) {
            $actions[] = Actions\Action::make('open_ad')
                ->label('Open ad')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->url($this->record->adUrl())
                ->openUrlInNewTab()
                ->color('info')
                ->button();
        }

        return $actions;
    }

    public function createLeadAction(): Actions\Action
    {
        return Actions\Action::make('createLead')
            ->label('Create lead')
            ->icon('heroicon-o-user-plus')
            ->color('success')
            ->button()
            ->visible(fn (): bool => ! $this->record->lead_id)
            ->requiresConfirmation()
            ->modalHeading('Create lead from this chat?')
            ->modalDescription('A new lead will be created using the contact details and ad attribution from this conversation.')
            ->action(function (): void {
                $lead = app(WhatsAppLeadService::class)->createFromConversation($this->record);

                $this->record->refresh()->load(['contact', 'lead', 'messages.sentByUser']);

                Notification::make()
                    ->title('Lead created')
                    ->body('Lead '.($lead->reference_id ?: '#'.$lead->id).' has been created.')
                    ->success()
                    ->actions([
                        \Filament\Notifications\Actions\Action::make('view')
                            ->label('View lead')
                            ->url(LeadResource::getUrl('view', ['record' => $lead])),
                    ])
                    ->send();
            });
    }

    public function sendReply(): void
    {
        $this->authorizeAccess();

        $hasAttachment = $this->attachment instanceof TemporaryUploadedFile
            || (is_object($this->attachment) && method_exists($this->attachment, 'getRealPath'));

        $this->validate(
            $hasAttachment ? $this->attachmentRules() : ['replyBody' => ['required', 'string', 'max:4096']],
            [
                'replyBody.required' => 'Type a message or attach a file.',
                'attachment.mimes' => 'Unsupported file type for WhatsApp.',
                'attachment.max' => 'File is too large for WhatsApp.',
            ],
        );

        $body = trim($this->replyBody) ?: null;

        if ($hasAttachment) {
            $stored = WhatsAppMediaStorage::storeUploadedFile($this->record->id, $this->attachment);

            $message = WhatsAppMessage::create([
                'whatsapp_conversation_id' => $this->record->id,
                'wamid' => 'local-'.Str::uuid(),
                'direction' => 'outbound',
                'type' => $stored['type'],
                'body' => $body,
                'media_path' => $stored['path'],
                'media_mime_type' => $stored['mime_type'],
                'media_filename' => $stored['filename'],
                'status' => 'pending',
                'sent_by_user_id' => auth()->id(),
                'sent_at' => now(),
            ]);

        } else {
            $message = WhatsAppMessage::create([
                'whatsapp_conversation_id' => $this->record->id,
                'wamid' => 'local-'.Str::uuid(),
                'direction' => 'outbound',
                'type' => 'text',
                'body' => $body,
                'status' => 'pending',
                'sent_by_user_id' => auth()->id(),
                'sent_at' => now(),
            ]);
        }

        SendWhatsAppMessageJob::dispatch($message->id);

        $this->record->syncFromLatestMessage();

        $this->replyBody = '';
        $this->attachment = null;

        $this->record->load(['contact', 'lead', 'messages.sentByUser']);

        Notification::make()
            ->title('Message queued')
            ->body($hasAttachment ? 'Your attachment is being sent via WhatsApp.' : 'Your reply is being sent via WhatsApp.')
            ->success()
            ->send();
    }

    public function removeAttachment(): void
    {
        $this->attachment = null;
    }

    public function refreshMessages(): void
    {
        $this->record->load(['contact', 'lead', 'messages.sentByUser']);
    }

    protected function resolveRecord(int|string $key): WhatsAppConversation
    {
        return static::getResource()::getEloquentQuery()
            ->with(['contact', 'lead', 'messages.sentByUser'])
            ->findOrFail($key);
    }

    public function leadViewUrl(): ?string
    {
        $record = $this->record->lead ?? $this->record->lead_id;

        if (! $record) {
            return null;
        }

        return LeadResource::getUrl('view', ['record' => $record]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function attachmentRules(): array
    {
        $documentMax = config('whatsapp.max_document_size_kb');

        return [
            'attachment' => [
                'required',
                'file',
                'max:'.$documentMax,
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! $value instanceof TemporaryUploadedFile) {
                        $fail('Invalid attachment.');

                        return;
                    }

                    if (! $this->isAllowedAttachment($value)) {
                        $fail('Unsupported file type for WhatsApp.');
                    }
                },
            ],
            'replyBody' => ['nullable', 'string', 'max:4096'],
        ];
    }

    private function isAllowedAttachment(TemporaryUploadedFile $file): bool
    {
        $mimeType = strtolower($file->getMimeType() ?: '');
        $extension = strtolower($file->getClientOriginalExtension() ?: '');
        $allowedMimes = array_map('strtolower', config('whatsapp.allowed_media_mimes', []));
        $allowedExtensions = array_map('strtolower', config('whatsapp.allowed_media_extensions', []));

        if (in_array($mimeType, $allowedMimes, true)) {
            return true;
        }

        // Some browsers/OS report PDFs and Office files as octet-stream.
        if (in_array($mimeType, ['application/octet-stream', 'binary/octet-stream'], true)
            && in_array($extension, $allowedExtensions, true)) {
            return true;
        }

        return in_array($extension, $allowedExtensions, true);
    }
}
