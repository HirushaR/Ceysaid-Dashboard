<?php

namespace App\Filament\Resources\Concerns;

use App\Models\WhatsAppConversation;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;

trait WhatsAppConversationTableColumns
{
    protected static function contactColumn(): TextColumn
    {
        return Tables\Columns\TextColumn::make('contact.profile_name')
            ->label('Contact')
            ->width('11rem')
            ->wrap(false)
            ->limit(28)
            ->tooltip(function (TextColumn $column): ?string {
                $state = $column->getState();

                return is_string($state) && strlen($state) > 28 ? $state : null;
            })
            ->description(fn (WhatsAppConversation $record): string => $record->contact?->phone ?? '')
            ->searchable(query: function (Builder $query, string $search): Builder {
                return $query->whereHas('contact', function (Builder $query) use ($search) {
                    $query->where('profile_name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            });
    }

    protected static function assignedToColumn(): TextColumn
    {
        return Tables\Columns\TextColumn::make('assignedUser.name')
            ->label('Assigned to')
            ->width('9rem')
            ->wrap(false)
            ->limit(18)
            ->tooltip(function (TextColumn $column): ?string {
                $state = $column->getState();

                return is_string($state) && strlen($state) > 18 ? $state : null;
            })
            ->visible(fn (): bool => auth()->user()?->isAdmin() ?? false);
    }

    protected static function adIdColumn(): TextColumn
    {
        return Tables\Columns\TextColumn::make('referral_source_id')
            ->label('Ad ID')
            ->width('10rem')
            ->wrap(false)
            ->limit(16)
            ->tooltip(function (TextColumn $column): ?string {
                $state = $column->getState();

                return is_string($state) && strlen($state) > 16 ? $state : null;
            })
            ->placeholder('Direct message')
            ->copyable()
            ->url(fn (WhatsAppConversation $record): ?string => $record->adUrl())
            ->openUrlInNewTab()
            ->toggleable();
    }

    protected static function lastMessageColumn(): TextColumn
    {
        return Tables\Columns\TextColumn::make('last_message_preview')
            ->label('Last message')
            ->width('14rem')
            ->wrap(false)
            ->limit(32)
            ->tooltip(function (TextColumn $column): ?string {
                $state = $column->getState();

                return is_string($state) && strlen($state) > 32 ? $state : null;
            })
            ->placeholder('No messages yet');
    }
}
