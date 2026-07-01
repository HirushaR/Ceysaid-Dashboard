<?php

namespace App\Filament\Forms;

use App\Enums\LeadStatus;
use App\Models\Tour;
use Filament\Forms;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Database\Eloquent\Builder;

final class LeadTourFormFields
{
    public static function tourSelect(bool $alwaysVisible = false, bool $useRecordForGroupCheck = false): Forms\Components\Select
    {
        return Forms\Components\Select::make('tour_id')
            ->label('Tour code')
            ->relationship(
                'tourMaster',
                'tour_code',
                fn (Builder $query) => $query->orderByDesc('departure_date')
            )
            ->getOptionLabelFromRecordUsing(fn (Tour $record): string => "{$record->tour_code} — {$record->name} ({$record->departure_date?->format('M j, Y')})")
            ->searchable(['tour_code', 'name'])
            ->preload()
            ->live()
            ->visible(function (Get $get, ?\App\Models\Lead $record) use ($alwaysVisible, $useRecordForGroupCheck): bool {
                if ($alwaysVisible) {
                    return true;
                }
                if ($useRecordForGroupCheck && $record?->is_group_lead) {
                    return true;
                }

                return (bool) $get('is_group_lead');
            })
            ->required(function (Get $get, ?\App\Models\Lead $record) use ($alwaysVisible, $useRecordForGroupCheck): bool {
                $isGroup = $alwaysVisible
                    || ($useRecordForGroupCheck && $record?->is_group_lead)
                    || (bool) $get('is_group_lead');
                $status = $get('status') ?? $record?->status;

                return $isGroup && $status === LeadStatus::CONFIRMED->value;
            })
            ->helperText('Link this group booking to a Tour Master record for finance tracking.')
            ->afterStateUpdated(function (?string $state, Set $set): void {
                if (! $state) {
                    return;
                }

                $tour = Tour::query()->find($state);
                if (! $tour) {
                    return;
                }

                $set('tour', $tour->name);
                $set('depature_date', $tour->departure_date?->toDateString());

                if ($tour->return_date) {
                    $days = $tour->departure_date?->diffInDays($tour->return_date);
                    if ($days) {
                        $set('number_of_days', $days);
                    }
                }
            });
    }
}
