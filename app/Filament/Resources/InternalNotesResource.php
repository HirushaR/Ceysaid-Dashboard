<?php

namespace App\Filament\Resources;

use App\Enums\LeadStatus;
use App\Enums\Platform;
use App\Filament\Resources\InternalNotesResource\Pages;
use App\Helpers\NotificationHelper;
use App\Models\Lead;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InternalNotesResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Internal Notes';
    protected static ?string $modelLabel = 'Lead with internal notes';
    protected static ?string $pluralModelLabel = 'Internal Notes';
    protected static ?string $navigationGroup = 'Dashboard';

    public static function getNavigationBadge(): ?string
    {
        $count = NotificationHelper::getInternalNoteUnreadCount();
        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        $count = NotificationHelper::getInternalNoteUnreadCount();
        if ($count <= 0) {
            return null;
        }
        return $count === 1 ? '1 unread message' : $count . ' unread messages';
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        if (! $user) {
            return parent::getEloquentQuery()->whereRaw('1 = 0');
        }

        return parent::getEloquentQuery()
            ->notArchived()
            ->where(function (Builder $q) use ($user) {
                $q->where('assigned_to', $user->id)
                    ->orWhere('assigned_operator', $user->id);
            })
            ->whereHas('notes');
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        return $user->isSales() || $user->isOperation();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInternalNotes::route('/'),
        ];
    }

    public static function table(Table $table): Table
    {
        $user = auth()->user();

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable()
                    ->copyable()
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Small)
                    ->color('primary')
                    ->weight('bold')
                    ->alignCenter()
                    ->width('80px'),

                Tables\Columns\TextColumn::make('reference_id')
                    ->label('Reference ID')
                    ->sortable()
                    ->searchable()
                    ->copyable()
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Small)
                    ->color('gray')
                    ->alignCenter()
                    ->width('120px'),

                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Customer')
                    ->sortable()
                    ->searchable()
                    ->weight('medium'),

                Tables\Columns\TextColumn::make('notes_count')
                    ->label('Unread')
                    ->getStateUsing(function (Lead $record) use ($user) {
                        if (! $user) {
                            return 0;
                        }
                        return $record->notes()
                            ->whereDoesntHave('reads', fn (Builder $q) => $q->where('user_id', $user->id))
                            ->count();
                    })
                    ->badge()
                    ->color(fn ($state) => $state > 0 ? 'warning' : 'gray')
                    ->alignCenter()
                    ->width('90px'),

                Tables\Columns\TextColumn::make('notes_updated')
                    ->label('Latest note')
                    ->getStateUsing(fn (Lead $record) => $record->notes()->latest()->first()?->created_at?->format('M j, Y g:i A') ?? '-')
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Small)
                    ->color('gray'),

                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->color(fn (string $state): string => LeadStatus::tryFrom($state)?->color() ?? 'secondary')
                    ->formatStateUsing(fn ($state) => LeadStatus::tryFrom($state)?->label() ?? $state)
                    ->alignCenter()
                    ->width('140px'),

                Tables\Columns\TextColumn::make('platform')
                    ->label('Source')
                    ->badge()
                    ->formatStateUsing(fn ($state) => Platform::tryFrom($state)?->label() ?? ucfirst($state ?? ''))
                    ->alignCenter()
                    ->width('100px'),
            ])
            ->defaultSort('updated_at', 'desc')
            ->recordUrl(function (Lead $record) {
                $user = auth()->user();
                if (! $user) {
                    return null;
                }
                if ($user->isSales()) {
                    return MySalesDashboardResource::getUrl('view', ['record' => $record]) . '?internal_notes=1#internal-notes';
                }
                if ($user->isOperation()) {
                    return MyOperationLeadDashboardResource::getUrl('view', ['record' => $record]) . '?internal_notes=1#internal-notes';
                }
                return null;
            })
            ->actions([
                Tables\Actions\Action::make('view_lead')
                    ->label('View lead')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (Lead $record) => self::getLeadViewUrl($record))
                    ->openUrlInNewTab(false),
            ])
            ->striped()
            ->paginated([10, 25, 50]);
    }

    protected static function getLeadViewUrl(Lead $record): ?string
    {
        $user = auth()->user();
        if (! $user) {
            return null;
        }
        if ($user->isSales()) {
            return MySalesDashboardResource::getUrl('view', ['record' => $record]) . '?internal_notes=1#internal-notes';
        }
        if ($user->isOperation()) {
            return MyOperationLeadDashboardResource::getUrl('view', ['record' => $record]) . '?internal_notes=1#internal-notes';
        }
        return null;
    }
}
