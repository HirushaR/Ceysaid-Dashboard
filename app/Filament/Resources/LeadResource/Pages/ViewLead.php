<?php

namespace App\Filament\Resources\LeadResource\Pages;

use App\Filament\Resources\LeadResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;

class ViewLead extends ViewRecord
{
    protected static string $resource = LeadResource::class;
    
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
            \Filament\Actions\Action::make('assign_to_me')
                ->label('Assign to Me')
                ->icon('heroicon-o-user-plus')
                ->color('success')
                ->visible(fn() => auth()->user()?->isSales() && !$this->record->assigned_to)
                ->action(function () {
                    $user = auth()->user();
                    $this->record->assigned_to = $user->id;
                    $this->record->status = \App\Enums\LeadStatus::ASSIGNED_TO_SALES->value;
                    $this->record->save();
                    Notification::make()
                        ->success()
                        ->title('Lead assigned to you and status updated.')
                        ->send();
                }),
            \Filament\Actions\Action::make('delete')
                ->label('Delete')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->action(function () {
                    $this->record->delete();
                    Notification::make()
                        ->success()
                        ->title('Lead deleted successfully.')
                        ->send();
                    return redirect()->to(LeadResource::getUrl('index'));
                }),
        ];
    }
} 