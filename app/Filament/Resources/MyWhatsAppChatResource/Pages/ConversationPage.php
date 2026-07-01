<?php

namespace App\Filament\Resources\MyWhatsAppChatResource\Pages;

use App\Filament\Resources\LeadResource;
use App\Filament\Resources\MySalesDashboardResource;
use App\Filament\Resources\MyWhatsAppChatResource;
use App\Jobs\SendWhatsAppMessageJob;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use App\Models\WhatsAppChatFolder;
use App\Services\WhatsAppChatFolderService;
use App\Services\WhatsAppLeadService;
use App\Support\WhatsAppMediaStorage;
use Filament\Actions;
use Filament\Forms;
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

        $this->record->load(['contact', 'lead', 'folder', 'messages.sentByUser']);
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

        if ($moveToFolder = $this->moveToFolderAction()) {
            $actions[] = $moveToFolder;
        }

        return $actions;
    }

    protected function moveToFolderAction(): ?Actions\Action
    {
        $user = auth()->user();
        $folderService = app(WhatsAppChatFolderService::class);

        if (! $folderService->userCanManageFolders($user)) {
            return null;
        }

        if ($user->isSales() && ! $this->record->isAssignedTo($user)) {
            return null;
        }

        if ($user->isAdmin() && ! $this->record->isAssigned()) {
            return null;
        }

        return Actions\Action::make('moveToFolder')
            ->label($this->record->folder_id ? 'Change folder' : 'Move to folder')
            ->icon('heroicon-o-folder')
            ->color('gray')
            ->button()
            ->form([
                Forms\Components\Select::make('folder_id')
                    ->label('Folder')
                    ->options(fn (): array => $folderService->folderOptionsForUser($user))
                    ->placeholder('Unfiled')
                    ->nullable()
                    ->searchable(),
            ])
            ->fillForm([
                'folder_id' => $this->record->folder_id,
            ])
            ->action(function (array $data) use ($folderService, $user): void {
                $folder = isset($data['folder_id']) && $data['folder_id']
                    ? WhatsAppChatFolder::query()->find($data['folder_id'])
                    : null;

                $folderService->moveConversation($this->record, $user, $folder);

                $this->record->refresh()->load(['contact', 'lead', 'folder', 'messages.sentByUser']);

                Notification::make()
                    ->title($folder ? 'Chat moved to folder' : 'Chat removed from folder')
                    ->success()
                    ->send();
            });
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
                            ->url($this->leadViewUrlFor($lead)),
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

        $preview = $body
            ?: ($hasAttachment
                ? '['.ucfirst($message->type).': '.($message->media_filename ?? 'attachment').']'
                : '');

        $this->record->update([
            'last_message_at' => $message->sent_at ?? $message->created_at,
            'last_message_preview' => Str::limit($preview, 120),
        ]);

        $this->record->refresh();

        $this->replyBody = '';
        $this->attachment = null;

        $this->record->load(['contact', 'lead', 'folder', 'messages.sentByUser']);

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
        $this->record->load(['contact', 'lead', 'folder', 'messages.sentByUser']);
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

        return $this->leadViewUrlFor($record);
    }

    protected function leadViewUrlFor(\App\Models\Lead|int $lead): string
    {
        $user = auth()->user();

        if ($user?->isSales()) {
            return MySalesDashboardResource::getUrl('view', ['record' => $lead]);
        }

        return LeadResource::getUrl('view', ['record' => $lead]);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    private function attachmentRules(): array
    {
        return [
            'attachment' => [
                'required',
                'file',
                'max:'.$this->maxAttachmentSizeKb(),
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! $value instanceof TemporaryUploadedFile) {
                        $fail('Invalid attachment.');

                        return;
                    }

                    $sizeKb = (int) ceil($value->getSize() / 1024);

                    if ($sizeKb > $this->maxAttachmentSizeKb($value)) {
                        $fail($this->attachmentSizeLimitMessage($value));

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

    private function maxAttachmentSizeKb(?TemporaryUploadedFile $file = null): int
    {
        if ($file) {
            $mimeType = strtolower($file->getMimeType() ?: '');

            if (str_starts_with($mimeType, 'image/')) {
                return (int) config('whatsapp.max_image_size_kb', 5120);
            }
        }

        return (int) config('whatsapp.max_document_size_kb', 16384);
    }

    private function attachmentSizeLimitMessage(TemporaryUploadedFile $file): string
    {
        $mimeType = strtolower($file->getMimeType() ?: '');
        $limitMb = str_starts_with($mimeType, 'image/')
            ? (int) config('whatsapp.max_image_size_kb', 5120) / 1024
            : (int) config('whatsapp.max_document_size_kb', 16384) / 1024;

        return "File is too large for WhatsApp (max {$limitMb} MB for this file type).";
    }
}
