<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Concerns\WhatsAppConversationTableColumns;
use App\Filament\Resources\WhatsAppInboxResource\Pages;
use App\Models\WhatsAppConversation;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class WhatsAppInboxResource extends Resource
{
    use WhatsAppConversationTableColumns;

    protected static ?string $model = WhatsAppConversation::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationLabel = 'WhatsApp Inbox';

    protected static ?string $modelLabel = 'Conversation';

    protected static ?string $pluralModelLabel = 'WhatsApp Inbox';

    protected static ?string $navigationGroup = 'WhatsApp';

    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return static::userCanAccess();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function canView(Model $record): bool
    {
        return false;
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getModel()::unassigned()->where('unread_count', '>', 0)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->unassigned()
            ->with(['contact', 'lead'])
            ->orderByRecentActivity();
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('15s')
            ->defaultSort(
                fn (Builder $query): Builder => $query->reorder()->orderByRecentActivity(),
                'desc',
            )
            ->persistSortInSession(false)
            ->defaultKeySort(false)
            ->columns([
                static::contactColumn(),
                static::adIdColumn(),
                Tables\Columns\TextColumn::make('referral_headline')
                    ->label('Ad headline')
                    ->width('12rem')
                    ->wrap(false)
                    ->limit(32)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();

                        return is_string($state) && strlen($state) > 32 ? $state : null;
                    })
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                static::lastMessageColumn(),
                Tables\Columns\TextColumn::make('last_message_at')
                    ->label('Last activity')
                    ->since(timezone: config('app.timezone'))
                    ->dateTimeTooltip(timezone: config('app.timezone')),
                Tables\Columns\TextColumn::make('unread_count')
                    ->label('Unread')
                    ->badge()
                    ->color('success')
                    ->formatStateUsing(fn (int $state): string => $state > 0 ? (string) $state : '')
                    ->placeholder(''),
            ])
            ->filters([
                Tables\Filters\Filter::make('unread')
                    ->label('Unread only')
                    ->query(fn (Builder $query): Builder => $query->where('unread_count', '>', 0)),
            ])
            ->actions([
                Tables\Actions\Action::make('assign')
                    ->label('Assign to me')
                    ->icon('heroicon-o-user-plus')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Assign this chat to yourself?')
                    ->modalDescription('This conversation will move to My WhatsApp Chats and will no longer appear in the inbox for other sales users.')
                    ->action(function (WhatsAppConversation $record): void {
                        $updated = WhatsAppConversation::query()
                            ->whereKey($record->id)
                            ->unassigned()
                            ->update([
                                'assigned_to' => auth()->id(),
                                'assigned_at' => now(),
                            ]);

                        if (! $updated) {
                            Notification::make()
                                ->title('Already assigned')
                                ->body('Another user has already taken this chat.')
                                ->warning()
                                ->send();

                            return;
                        }

                        $record->refresh();

                        Notification::make()
                            ->title('Chat assigned')
                            ->body('You can now reply from My WhatsApp Chats.')
                            ->success()
                            ->actions([
                                \Filament\Notifications\Actions\Action::make('open')
                                    ->label('Open chat')
                                    ->url(MyWhatsAppChatResource::getUrl('view', ['record' => $record])),
                            ])
                            ->send();
                    })
                    ->successRedirectUrl(fn (WhatsAppConversation $record): string => MyWhatsAppChatResource::getUrl('view', ['record' => $record])),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWhatsAppConversations::route('/'),
        ];
    }

    protected static function userCanAccess(): bool
    {
        $user = auth()->user();

        return $user && ($user->isAdmin() || $user->isSales());
    }
}
