<?php

namespace App\Filament\Resources\MyCallCenterLeadResource\Pages;

use App\Enums\LeadStatus;
use App\Filament\Resources\MyCallCenterLeadResource;
use Filament\Infolists\Components;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewMyCallCenterLead extends ViewRecord
{
    protected static string $resource = MyCallCenterLeadResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Basic Information')
                    ->schema([
                        Components\TextEntry::make('reference_id')
                            ->label('Reference ID')
                            ->formatStateUsing(fn ($state, $record) => $state ?: "ID: {$record->id}"),
                        Components\TextEntry::make('customer_name')
                            ->label('Customer Name'),
                        Components\TextEntry::make('platform')
                            ->label('Source Platform')
                            ->formatStateUsing(fn ($state) => \App\Enums\Platform::tryFrom($state)?->label() ?? ucfirst($state ?? '')),
                        Components\TextEntry::make('tour')
                            ->label('Tour Requirements')
                            ->placeholder('—')
                            ->columnSpanFull(),
                        Components\TextEntry::make('message')
                            ->label('Customer Message')
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Components\Section::make('Contact Information')
                    ->schema([
                        Components\TextEntry::make('contact_method')
                            ->label('Contact Method')
                            ->formatStateUsing(fn ($state) => ucfirst($state ?? '—')),
                        Components\TextEntry::make('contact_value')
                            ->label('Contact Value')
                            ->placeholder('—'),
                    ])
                    ->columns(2),

                Components\Section::make('Status')
                    ->schema([
                        Components\TextEntry::make('status')
                            ->label('Lead Status')
                            ->formatStateUsing(fn ($state) => LeadStatus::tryFrom($state)?->label() ?? $state),
                        Components\TextEntry::make('assignedUser.name')
                            ->label('Assigned To')
                            ->placeholder('Unassigned'),
                        Components\TextEntry::make('created_at')
                            ->label('Created At')
                            ->dateTime('M j, Y g:i A'),
                    ])
                    ->columns(2),
            ]);
    }
}
