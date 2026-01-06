<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;

class ViewUser extends ViewRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->label('Edit User')
                ->color('primary')
                ->authorize(fn ($record) => auth()->user() && (
                    auth()->user()->hasPermission('users.edit') || 
                    auth()->user()->isHR() || 
                    auth()->user()->isAdmin()
                ) && auth()->user()->id !== $record->id),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Leave Balance (Current Calendar Year)')
                    ->schema([
                        Components\Grid::make(4)
                            ->schema([
                                Components\TextEntry::make('leave_balance_casual')
                                    ->label('Casual Leave')
                                    ->getStateUsing(function ($record) {
                                        $remaining = $record->getRemainingLeaves();
                                        $used = $record->getUsedLeaves();
                                        return "Remaining: {$remaining['casual']} / 7 days\nUsed: {$used['casual']} days";
                                    })
                                    ->badge()
                                    ->color(fn ($record) => $record->getRemainingLeaves()['casual'] > 0 ? 'success' : 'danger'),
                                
                                Components\TextEntry::make('leave_balance_sick')
                                    ->label('Sick Leave')
                                    ->getStateUsing(function ($record) {
                                        $remaining = $record->getRemainingLeaves();
                                        $used = $record->getUsedLeaves();
                                        return "Remaining: {$remaining['sick']} / 7 days\nUsed: {$used['sick']} days";
                                    })
                                    ->badge()
                                    ->color(fn ($record) => $record->getRemainingLeaves()['sick'] > 0 ? 'success' : 'danger'),
                                
                                Components\TextEntry::make('leave_balance_annual')
                                    ->label('Annual Leave')
                                    ->getStateUsing(function ($record) {
                                        $remaining = $record->getRemainingLeaves();
                                        $used = $record->getUsedLeaves();
                                        return "Remaining: {$remaining['annual']} / 14 days\nUsed: {$used['annual']} days";
                                    })
                                    ->badge()
                                    ->color(fn ($record) => $record->getRemainingLeaves()['annual'] > 0 ? 'success' : 'danger'),
                                
                                Components\TextEntry::make('leave_balance_total')
                                    ->label('Total Leave Balance')
                                    ->getStateUsing(function ($record) {
                                        $remaining = $record->getRemainingLeaves();
                                        $used = $record->getUsedLeaves();
                                        return "Remaining: {$remaining['total']} / 28 days\nUsed: {$used['total']} days";
                                    })
                                    ->badge()
                                    ->color(fn ($record) => $record->getRemainingLeaves()['total'] > 0 ? 'success' : 'danger')
                                    ->weight('bold'),
                            ]),
                    ])
                    ->collapsible()
                    ->visible(fn ($record) => auth()->user() && (auth()->user()->isHR() || auth()->user()->isAdmin())),
            ]);
    }
}
