<?php

namespace App\Filament\Resources\CallCenterCallResource\Pages;

use App\Filament\Resources\CallCenterCallResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Illuminate\Database\Eloquent\Model;

class ViewCallCenterCall extends ViewRecord
{
    protected static string $resource = CallCenterCallResource::class;

    protected function resolveRecord($key): Model
    {
        return static::getResource()::resolveRecordRouteBinding($key)
            ->load('lead');
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // Lead Information Section
                Components\Section::make('Lead Information')
                    ->schema([
                        Components\Grid::make(2)
                            ->schema([
                                Components\TextEntry::make('lead.customer_name')
                                    ->label('Customer Name')
                                    ->size(Components\TextEntry\TextEntrySize::Large)
                                    ->weight('bold'),
                                Components\TextEntry::make('lead.destination')
                                    ->label('Destination')
                                    ->badge()
                                    ->color('info'),
                            ]),
                        Components\Grid::make(2)
                            ->schema([
                                Components\TextEntry::make('lead.arrival_date')
                                    ->label('Arrival Date')
                                    ->date('M j, Y')
                                    ->color('success'),
                                Components\TextEntry::make('lead.depature_date')
                                    ->label('Departure Date')
                                    ->date('M j, Y')
                                    ->color('warning'),
                            ]),
                        Components\Grid::make(2)
                            ->schema([
                                Components\TextEntry::make('lead.number_of_adults')
                                    ->label('Number of Adults')
                                    ->numeric(),
                                Components\TextEntry::make('lead.number_of_children')
                                    ->label('Number of Children')
                                    ->numeric(),
                            ]),
                        Components\TextEntry::make('lead.contact_value')
                            ->label('Contact')
                            ->copyable()
                            ->placeholder('No contact info'),
                        Components\TextEntry::make('lead.tour_details')
                            ->label('Tour Details')
                            ->placeholder('No tour details provided')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                // Call Management Section
                Components\Section::make('Call Management')
                    ->schema([
                        Components\Grid::make(2)
                            ->schema([
                                Components\TextEntry::make('call_type')
                                    ->label('Call Type')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'pre_departure' => 'info',
                                        'post_arrival' => 'success',
                                        default => 'gray',
                                    }),
                                Components\TextEntry::make('status')
                                    ->label('Call Status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'pending' => 'gray',
                                        'assigned' => 'warning',
                                        'called' => 'info',
                                        'not_answered' => 'danger',
                                        'completed' => 'success',
                                        default => 'gray',
                                    }),
                            ]),
                        Components\TextEntry::make('call_notes')
                            ->label('Call Notes')
                            ->placeholder('No call notes provided')
                            ->columnSpanFull(),
                        Components\Grid::make(2)
                            ->schema([
                                Components\TextEntry::make('call_attempts')
                                    ->label('Call Attempts')
                                    ->numeric()
                                    ->badge()
                                    ->color(fn ($state) => $state > 0 ? 'warning' : 'gray'),
                                Components\TextEntry::make('last_call_attempt')
                                    ->label('Last Call Attempt')
                                    ->dateTime('M j, Y H:i')
                                    ->placeholder('Never called')
                                    ->color('gray'),
                            ]),
                    ])
                    ->columns(2),

                // Call Checklist Section
                Components\Section::make('Call Checklist')
                    ->schema([
                        Components\TextEntry::make('call_checklist_completed')
                            ->label('Completed Items')
                            ->formatStateUsing(function ($state, $record) {
                                if (empty($state) || !is_array($state)) {
                                    return 'No items completed';
                                }
                                
                                $callType = $record?->call_type ?? 'pre_departure';
                                
                                if ($callType === 'post_arrival') {
                                    // Post-Arrival Call Checklist Labels
                                    $checklistLabels = [
                                        'confirmed_safe_arrival' => 'Confirmed safe arrival',
                                        'confirmed_accommodation' => 'Confirmed accommodation details',
                                        'confirmed_pickup_arrangement' => 'Confirmed pickup arrangement',
                                        'provided_local_contact' => 'Provided local contact information',
                                        'confirmed_tour_schedule' => 'Confirmed tour schedule',
                                        'reminded_emergency_procedures' => 'Reminded about emergency procedures',
                                        'confirmed_special_needs' => 'Confirmed any special needs',
                                        'provided_destination_tips' => 'Provided destination tips and information',
                                        'confirmed_next_contact' => 'Confirmed next contact time',
                                        'addressed_concerns' => 'Addressed any immediate concerns',
                                        'confirmed_documentation' => 'Confirmed all documentation is in order',
                                        'provided_weather_info' => 'Provided weather information',
                                    ];
                                } else {
                                    // Pre-Departure Call Checklist Labels
                                    $checklistLabels = [
                                        'confirmed_departure_details' => 'Confirmed departure details',
                                        'confirmed_passenger_count' => 'Confirmed passenger count',
                                        'confirmed_contact_info' => 'Confirmed contact information',
                                        'reminded_documents' => 'Reminded about required documents',
                                        'reminded_visa' => 'Reminded about visa requirements',
                                        'reminded_insurance' => 'Reminded about travel insurance',
                                        'reminded_currency' => 'Reminded about currency exchange',
                                        'confirmed_pickup' => 'Confirmed pickup arrangements',
                                        'confirmed_hotel' => 'Confirmed hotel details',
                                        'provided_emergency_contact' => 'Provided emergency contact information',
                                        'confirmed_special_requirements' => 'Confirmed any special requirements',
                                        'reminded_packing_list' => 'Reminded about packing essentials',
                                    ];
                                }
                                
                                $completedItems = [];
                                foreach ($state as $item) {
                                    if (isset($checklistLabels[$item])) {
                                        $completedItems[] = $checklistLabels[$item];
                                    }
                                }
                                
                                return empty($completedItems) ? 'No items completed' : implode(', ', $completedItems);
                            })
                            ->columnSpanFull(),
                    ])
                    ->collapsed(),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('make_call')
                ->label('Make Call')
                ->icon('heroicon-o-phone')
                ->color('success')
                ->action(function () {
                    // Update status and last call attempt when call button is clicked
                    // Call attempts will be incremented when the form is saved
                    $this->record->update([
                        'last_call_attempt' => now(),
                        'status' => \App\Models\CallCenterCall::STATUS_CALLED,
                    ]);
                    
                    $this->redirect(CallCenterCallResource::getUrl('edit', ['record' => $this->record]));
                })
                ->visible(fn () => auth()->user()?->isCallCenter()),
        ];
    }
}
