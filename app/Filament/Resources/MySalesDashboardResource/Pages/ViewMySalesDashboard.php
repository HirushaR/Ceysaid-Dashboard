<?php

namespace App\Filament\Resources\MySalesDashboardResource\Pages;

use App\Filament\Resources\MySalesDashboardResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;

class ViewMySalesDashboard extends ViewRecord
{
    protected static string $resource = MySalesDashboardResource::class;
    
    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // Header section with key info
                Components\Section::make('Lead Overview')
                    ->schema([
                        Components\Grid::make(3)
                            ->schema([
                                Components\TextEntry::make('reference_id')
                                    ->label('Reference ID')
                                    ->badge()
                                    ->color('gray'),
                                Components\TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'new' => 'gray',
                                        'assigned_to_sales' => 'info',
                                        'assigned_to_operations' => 'warning',
                                        'info_gather_complete' => 'success',
                                        'pricing_in_progress' => 'primary',
                                        'sent_to_customer' => 'accent',
                                        'confirmed' => 'brand',
                                        'mark_closed' => 'danger',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn ($state) => \App\Enums\LeadStatus::tryFrom($state)?->label() ?? $state),
                                Components\TextEntry::make('priority')
                                    ->label('Priority')
                                    ->badge()
                                    ->color(fn (?string $state): string => match ($state) {
                                        'low' => 'gray',
                                        'medium' => 'warning',
                                        'high' => 'danger',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn ($state) => ucfirst($state ?? 'medium')),
                            ]),
                    ])
                    ->columns(1),

                // Customer Information
                Components\Section::make('Customer Information')
                    ->schema([
                        Components\Grid::make(2)
                            ->schema([
                                Components\TextEntry::make('customer_name')
                                    ->label('Customer Name')
                                    ->size(Components\TextEntry\TextEntrySize::Large)
                                    ->weight('bold'),
                                Components\TextEntry::make('platform')
                                    ->label('Source Platform')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'facebook' => 'info',
                                        'whatsapp' => 'success',
                                        'email' => 'warning',
                                        default => 'gray',
                                    }),
                            ]),
                        Components\Grid::make(2)
                            ->schema([
                                Components\TextEntry::make('contact_method')
                                    ->label('Contact Method')
                                    ->formatStateUsing(fn ($state) => ucfirst($state ?? 'Not specified')),
                                Components\TextEntry::make('contact_value')
                                    ->label('Contact Value')
                                    ->placeholder('Not provided')
                                    ->copyable(),
                            ]),
                        Components\TextEntry::make('message')
                            ->label('Customer Message')
                            ->placeholder('No message provided')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                // Travel Details
                Components\Section::make('Travel Information')
                    ->schema([
                        Components\Grid::make(3)
                            ->schema([
                                Components\TextEntry::make('destination')
                                    ->label('Destination')
                                    ->placeholder('Not specified'),
                                Components\TextEntry::make('country')
                                    ->label('Country')
                                    ->placeholder('Not specified'),
                                Components\TextEntry::make('subject')
                                    ->label('Trip Subject')
                                    ->placeholder('Not specified'),
                            ]),
                        Components\Grid::make(3)
                            ->schema([
                                Components\TextEntry::make('arrival_date')
                                    ->label('Arrival Date')
                                    ->date('M j, Y')
                                    ->placeholder('Not set'),
                                Components\TextEntry::make('depature_date')
                                    ->label('Departure Date')
                                    ->date('M j, Y')
                                    ->placeholder('Not set'),
                                Components\TextEntry::make('number_of_days')
                                    ->label('Duration')
                                    ->suffix(' days')
                                    ->placeholder('Not specified'),
                            ]),
                        Components\Grid::make(3)
                            ->schema([
                                Components\TextEntry::make('number_of_adults')
                                    ->label('Adults')
                                    ->placeholder('0'),
                                Components\TextEntry::make('number_of_children')
                                    ->label('Children')
                                    ->placeholder('0'),
                                Components\TextEntry::make('number_of_infants')
                                    ->label('Infants')
                                    ->placeholder('0'),
                            ]),
                        Components\TextEntry::make('tour')
                            ->label('Tour Requirements')
                            ->placeholder('No requirements specified')
                            ->columnSpanFull(),
                        Components\TextEntry::make('tour_details')
                            ->label('Detailed Tour Information')
                            ->placeholder('No details provided')
                            ->columnSpanFull(),
                    ])
                    ->columns(3),

                // Assignment Information
                Components\Section::make('Assignment & Team')
                    ->schema([
                        Components\Grid::make(2)
                            ->schema([
                                Components\TextEntry::make('assignedUser.name')
                                    ->label('Assigned Sales Rep')
                                    ->placeholder('Unassigned')
                                    ->badge()
                                    ->color('info'),
                                Components\TextEntry::make('assignedOperator.name')
                                    ->label('Assigned Operator')
                                    ->placeholder('Unassigned')
                                    ->badge()
                                    ->color('success'),
                            ]),
                        Components\Grid::make(2)
                            ->schema([
                                Components\TextEntry::make('creator.name')
                                    ->label('Created By')
                                    ->placeholder('Unknown'),
                                Components\TextEntry::make('customer.name')
                                    ->label('Linked Customer')
                                    ->placeholder('No customer link'),
                            ]),
                    ])
                    ->columns(2)
                    ->collapsed(),

                // System Information
                Components\Section::make('System Information')
                    ->schema([
                        Components\Grid::make(2)
                            ->schema([
                                Components\TextEntry::make('created_at')
                                    ->label('Created At')
                                    ->dateTime('M j, Y \a\t g:i A'),
                                Components\TextEntry::make('updated_at')
                                    ->label('Last Updated')
                                    ->dateTime('M j, Y \a\t g:i A'),
                            ]),
                    ])
                    ->columns(2)
                    ->collapsed(),
            ])
            ->columns(1);
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\EditAction::make()
                ->label('Edit')
                ->icon('heroicon-o-pencil')
                ->button(),
            \Filament\Actions\Action::make('info_gather_complete')
                ->label('Info Gather Complete')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->action(function () {
                    $this->record->status = \App\Enums\LeadStatus::INFO_GATHER_COMPLETE->value;
                    $this->record->save();
                    \Filament\Notifications\Notification::make()
                        ->success()
                        ->title('Lead marked as Info Gather Complete.')
                        ->send();
                })
                ->visible(fn ($record) => $record->status === \App\Enums\LeadStatus::ASSIGNED_TO_SALES->value),
            \Filament\Actions\Action::make('sent_to_customer')
                ->label('Sent to Customer')
                ->color('success')
                ->icon('heroicon-o-paper-airplane')
                ->requiresConfirmation()
                ->modalHeading('Are you sure?')
                ->modalDescription('Confirm that all steps are done and this lead will be marked as sent to customer.')
                ->action(function () {
                    $this->record->status = \App\Enums\LeadStatus::SENT_TO_CUSTOMER->value;
                    $this->record->save();
                    \Filament\Notifications\Notification::make()
                        ->success()
                        ->title('Lead marked as Sent to Customer.')
                        ->send();
                })
                ->visible(fn ($record) => $record->status === \App\Enums\LeadStatus::OPERATION_COMPLETE->value),
            \Filament\Actions\Action::make('confirm_lead')
                ->label('Confirm Lead')
                ->color('info')
                ->icon('heroicon-o-check')
                ->requiresConfirmation()
                ->modalHeading('Confirm this lead?')
                ->modalDescription('This will mark the lead as confirmed.')
                ->action(function () {
                    $this->record->status = \App\Enums\LeadStatus::CONFIRMED->value;
                    $this->record->save();
                    \Filament\Notifications\Notification::make()
                        ->success()
                        ->title('Lead confirmed successfully.')
                        ->send();
                })
                ->visible(fn ($record) => $record->status === \App\Enums\LeadStatus::SENT_TO_CUSTOMER->value),
        ];
    }
} 