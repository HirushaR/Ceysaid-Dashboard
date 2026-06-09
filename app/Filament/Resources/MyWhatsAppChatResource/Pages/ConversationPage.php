<?php

namespace App\Filament\Resources\MyWhatsAppChatResource\Pages;

use App\Filament\Resources\LeadResource;
use App\Filament\Resources\MyWhatsAppChatResource;
use App\Jobs\SendWhatsAppMessageJob;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use App\Services\WhatsAppLeadService;
use Filament\Actions;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Str;

class ConversationPage extends ViewRecord
{
    protected static string $resource = MyWhatsAppChatResource::class;

    protected static string $view = 'filament.resources.whats-app-inbox-resource.pages.conversation-page';

    protected static ?string $title = 'WhatsApp Conversation';

    protected static bool $shouldRegisterNavigation = false;

    public string $replyBody = '';

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

        if ($this->record->lead_id) {
            $actions[] = Actions\Action::make('view_lead')
                ->label('View lead')
                ->icon('heroicon-o-user')
                ->url(LeadResource::getUrl('view', ['record' => $this->record->lead]))
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

        $this->validate([
            'replyBody' => ['required', 'string', 'max:4096'],
        ]);

        $body = trim($this->replyBody);

        $message = WhatsAppMessage::create([
            'whatsapp_conversation_id' => $this->record->id,
            'wamid' => 'local-'.Str::uuid(),
            'direction' => 'outbound',
            'type' => 'text',
            'body' => $body,
            'status' => 'pending',
            'sent_by_user_id' => auth()->id(),
        ]);

        SendWhatsAppMessageJob::dispatch($message->id);

        $this->record->update([
            'last_message_at' => now(),
            'last_message_preview' => Str::limit($body, 120),
        ]);

        $this->replyBody = '';

        $this->record->load(['contact', 'lead', 'messages.sentByUser']);

        Notification::make()
            ->title('Message queued')
            ->body('Your reply is being sent via WhatsApp.')
            ->success()
            ->send();
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
}
