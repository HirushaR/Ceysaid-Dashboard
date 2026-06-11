<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Concerns\WhatsAppConversationTableColumns;
use App\Filament\Resources\MyWhatsAppChatResource\Pages;
use App\Models\WhatsAppConversation;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MyWhatsAppChatResource extends Resource
{
    use WhatsAppConversationTableColumns;

    protected static ?string $model = WhatsAppConversation::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox';

    protected static ?string $navigationLabel = 'My WhatsApp Chats';

    protected static ?string $modelLabel = 'Chat';

    protected static ?string $pluralModelLabel = 'My WhatsApp Chats';

    protected static ?string $navigationGroup = 'WhatsApp';

    protected static ?int $navigationSort = 2;

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
        if (! static::userCanAccess()) {
            return false;
        }

        $user = auth()->user();

        if ($user->isAdmin()) {
            return $record->isAssigned();
        }

        return $record->isAssignedTo($user);
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::badgeQuery()->where('unread_count', '>', 0)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->assigned()
            ->with(['contact', 'lead', 'assignedUser']);

        $user = auth()->user();

        if ($user && ! $user->isAdmin()) {
            $query->assignedToUser($user->id);
        }

        return $query->orderByRecentActivity();
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
                static::assignedToColumn(),
                static::adIdColumn(),
                static::lastMessageColumn(),
                Tables\Columns\TextColumn::make('lead.reference_id')
                    ->label('Lead')
                    ->placeholder('—')
                    ->url(fn (WhatsAppConversation $record): ?string => ($record->lead ?? $record->lead_id)
                        ? LeadResource::getUrl('view', ['record' => $record->lead ?? $record->lead_id])
                        : null),
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
                Tables\Filters\Filter::make('has_lead')
                    ->label('Has lead')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('lead_id')),
                Tables\Filters\Filter::make('no_lead')
                    ->label('No lead')
                    ->query(fn (Builder $query): Builder => $query->whereNull('lead_id')),
            ])
            ->actions([
                Tables\Actions\Action::make('open')
                    ->label('Open chat')
                    ->icon('heroicon-o-chat-bubble-left-ellipsis')
                    ->url(fn (WhatsAppConversation $record): string => static::getUrl('view', ['record' => $record])),
            ])
            ->recordUrl(fn (WhatsAppConversation $record): string => static::getUrl('view', ['record' => $record]));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMyWhatsAppChats::route('/'),
            'view' => Pages\ConversationPage::route('/{record}'),
        ];
    }

    protected static function userCanAccess(): bool
    {
        $user = auth()->user();

        return $user && ($user->isAdmin() || $user->isSales());
    }

    protected static function badgeQuery(): Builder
    {
        $query = static::getModel()::query()->assigned();

        $user = auth()->user();

        if ($user && ! $user->isAdmin()) {
            $query->assignedToUser($user->id);
        }

        return $query;
    }
}
