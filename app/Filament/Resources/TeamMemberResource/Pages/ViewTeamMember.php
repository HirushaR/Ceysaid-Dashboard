<?php

namespace App\Filament\Resources\TeamMemberResource\Pages;

use App\Filament\Resources\TeamMemberResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;

class ViewTeamMember extends ViewRecord
{
    protected static string $resource = TeamMemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Managers can't edit team members through this resource
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Team Member Information')
                    ->schema([
                        Components\Grid::make(2)
                            ->schema([
                                Components\TextEntry::make('name')
                                    ->label('Full Name')
                                    ->weight('bold')
                                    ->size(Components\TextEntry\TextEntrySize::Large),
                                Components\TextEntry::make('email')
                                    ->label('Email')
                                    ->icon('heroicon-o-envelope')
                                    ->copyable(),
                                Components\TextEntry::make('role')
                                    ->label('Role')
                                    ->badge()
                                    ->color('primary')
                                    ->formatStateUsing(fn ($state) => ucfirst($state)),
                                Components\TextEntry::make('email_verified_at')
                                    ->label('Email Verified')
                                    ->badge()
                                    ->color(fn ($state) => $state ? 'success' : 'danger')
                                    ->formatStateUsing(fn ($state) => $state ? 'Verified' : 'Not Verified'),
                                Components\TextEntry::make('created_at')
                                    ->label('Joined Date')
                                    ->dateTime('M j, Y')
                                    ->since()
                                    ->icon('heroicon-o-calendar'),
                            ]),
                    ]),
                    
                Components\Section::make('Performance Overview')
                    ->schema([
                        Components\Grid::make(4)
                            ->schema([
                                Components\TextEntry::make('leads_count')
                                    ->label('Total Leads')
                                    ->numeric()
                                    ->badge()
                                    ->color('primary')
                                    ->getStateUsing(fn ($record) => $record->getAllLeads()->count()),
                                Components\TextEntry::make('active_leads_count')
                                    ->label('Active Leads')
                                    ->numeric()
                                    ->badge()
                                    ->color('success')
                                    ->getStateUsing(fn ($record) => $record->getAllLeads()
                                        ->whereNotIn('status', ['mark_closed', 'operation_complete', 'document_upload_complete'])
                                        ->count()),
                                Components\TextEntry::make('leaves_count')
                                    ->label('Total Leaves')
                                    ->numeric()
                                    ->badge()
                                    ->color('info')
                                    ->default(fn ($record) => $record->leaves()->count()),
                                Components\TextEntry::make('active_leaves_count')
                                    ->label('Active Leaves')
                                    ->numeric()
                                    ->badge()
                                    ->color('warning')
                                    ->default(fn ($record) => $record->leaves()
                                        ->where('status', 'approved')
                                        ->where('start_date', '<=', now())
                                        ->where('end_date', '>=', now())
                                        ->count()),
                            ]),
                    ]),
            ]);
    }
}
