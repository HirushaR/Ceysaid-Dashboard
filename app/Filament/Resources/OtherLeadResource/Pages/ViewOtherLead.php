<?php

namespace App\Filament\Resources\OtherLeadResource\Pages;

use App\Enums\OtherLeadStatus;
use App\Filament\Resources\InvoiceResource;
use App\Filament\Resources\OtherLeadResource;
use App\Filament\Resources\QuoteResource;
use Filament\Actions;
use Filament\Infolists\Components;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewOtherLead extends ViewRecord
{
    protected static string $resource = OtherLeadResource::class;

    protected function resolveRecord($key): \Illuminate\Database\Eloquent\Model
    {
        return static::getResource()::getEloquentQuery()
            ->with(['quote'])
            ->withCount('invoices')
            ->findOrFail($key);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->visible(fn (): bool => OtherLeadResource::canEdit($this->record)),
            Actions\Action::make('confirm')
                ->label('Confirm')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record->other_lead_status === OtherLeadStatus::Draft)
                ->action(function () {
                    $this->record->update(['other_lead_status' => OtherLeadStatus::Confirmed]);
                    Notification::make()->title('Other lead confirmed')->success()->send();
                    $this->redirect(static::getResource()::getUrl('view', ['record' => $this->record->getKey()]));
                }),
            Actions\Action::make('create_quote')
                ->label('Create quote')
                ->icon('heroicon-o-document-duplicate')
                ->url(fn (): string => QuoteResource::getUrl('create', ['lead_id' => $this->record->id]))
                ->visible(fn (): bool => $this->record->other_lead_status === OtherLeadStatus::Confirmed
                    && $this->record->quote === null),
            Actions\Action::make('view_quote')
                ->label('View quote')
                ->icon('heroicon-o-document-duplicate')
                ->url(fn (): string => QuoteResource::getUrl('view', ['record' => $this->record->quote->id]))
                ->visible(fn (): bool => $this->record->quote !== null),
            Actions\Action::make('create_invoice')
                ->label('Create invoice')
                ->icon('heroicon-o-document-text')
                ->url(function (): string {
                    if ($this->record->quote) {
                        return InvoiceResource::getUrl('create', ['quote_id' => $this->record->quote->id]);
                    }

                    return InvoiceResource::getUrl('create', ['lead_id' => $this->record->id]);
                })
                ->visible(fn (): bool => $this->record->other_lead_status === OtherLeadStatus::Confirmed),
            Actions\Action::make('complete')
                ->label('Mark complete')
                ->icon('heroicon-o-flag')
                ->color('primary')
                ->requiresConfirmation()
                ->visible(fn (): bool => $this->record->other_lead_status === OtherLeadStatus::Confirmed)
                ->action(function () {
                    $this->record->update(['other_lead_status' => OtherLeadStatus::Completed]);
                    Notification::make()->title('Other lead completed')->success()->send();
                    $this->redirect(static::getResource()::getUrl('view', ['record' => $this->record->getKey()]));
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Components\Section::make('Overview')
                    ->schema([
                        Components\TextEntry::make('reference_id')
                            ->label('Reference'),
                        Components\TextEntry::make('other_lead_status')
                            ->label('Progress')
                            ->badge()
                            ->formatStateUsing(fn ($state): string => $state instanceof OtherLeadStatus
                                ? $state->label()
                                : (OtherLeadStatus::tryFrom((string) $state)?->label() ?? ''))
                            ->color(fn ($state): string => $state instanceof OtherLeadStatus
                                ? $state->color()
                                : (OtherLeadStatus::tryFrom((string) $state)?->color() ?? 'gray')),
                    ])
                    ->columns(2),
                Components\Section::make('Customer')
                    ->schema([
                        Components\TextEntry::make('customer_name')
                            ->label('Name'),
                        Components\TextEntry::make('contact_method')
                            ->label('Contact method')
                            ->formatStateUsing(fn ($state) => $state ? ucfirst((string) $state) : '—'),
                        Components\TextEntry::make('contact_value')
                            ->label('Contact value')
                            ->placeholder('—'),
                        Components\TextEntry::make('subject')
                            ->label('Title / summary')
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Components\Section::make('Details')
                    ->schema([
                        Components\TextEntry::make('other_lead_start_date')
                            ->label('Start date')
                            ->date()
                            ->placeholder('—'),
                        Components\TextEntry::make('other_lead_end_date')
                            ->label('End date')
                            ->date()
                            ->placeholder('—'),
                        Components\TextEntry::make('other_lead_details')
                            ->label('Details')
                            ->placeholder('—')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Components\Section::make('Finance')
                    ->schema([
                        Components\TextEntry::make('quote.quote_number')
                            ->label('Quote number')
                            ->placeholder('None yet'),
                        Components\TextEntry::make('invoices_count')
                            ->label('Invoices'),
                    ])
                    ->columns(2)
                    ->collapsed(),
            ]);
    }
}
