<?php

namespace App\Filament\Resources\ConfirmLeadResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class LeadCostsRelationManager extends RelationManager
{
    protected static string $relationship = 'leadCosts';

    protected static ?string $recordTitleAttribute = 'invoice_number';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('invoice_number')
                    ->label('Invoice Number')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('amount')
                    ->label('Amount')
                    ->numeric()
                    ->step(0.01)
                    ->prefix('$')
                    ->required(),
                Forms\Components\Textarea::make('details')
                    ->label('Details')
                    ->rows(3)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('vendor_bill')
                    ->label('Vendor Bill')
                    ->maxLength(255),
                Forms\Components\TextInput::make('vendor_amount')
                    ->label('Vendor Amount')
                    ->numeric()
                    ->step(0.01)
                    ->prefix('$'),
                Forms\Components\Toggle::make('is_paid')
                    ->label('Is Paid')
                    ->default(false)
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('invoice_number')
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Invoice Number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\TextColumn::make('details')
                    ->label('Details')
                    ->limit(50)
                    ->tooltip(function ($record) {
                        return $record->details;
                    }),
                Tables\Columns\TextColumn::make('vendor_bill')
                    ->label('Vendor Bill')
                    ->searchable(),
                Tables\Columns\TextColumn::make('vendor_amount')
                    ->label('Vendor Amount')
                    ->money('USD')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_paid')
                    ->label('Is Paid')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_paid')
                    ->label('Payment Status')
                    ->boolean()
                    ->trueLabel('Paid')
                    ->falseLabel('Unpaid')
                    ->native(false),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->modalHeading('Add Lead Cost'),
            ])
            ->heading(function () {
                $ownerRecord = $this->getOwnerRecord();
                $costs = $ownerRecord->leadCosts;
                $totalAmount = $costs->sum('amount');
                $paidAmount = $costs->where('is_paid', true)->sum('amount');
                $unpaidAmount = $costs->where('is_paid', false)->sum('amount');
                $paidCount = $costs->where('is_paid', true)->count();
                $unpaidCount = $costs->where('is_paid', false)->count();
                
                return "Lead Costs - Total: $" . number_format($totalAmount, 2) . 
                       " | Paid: $" . number_format($paidAmount, 2) . " ({$paidCount})" .
                       " | Unpaid: $" . number_format($unpaidAmount, 2) . " ({$unpaidCount})";
            })
            ->actions([
                Tables\Actions\Action::make('mark_paid')
                    ->label('Mark as Paid')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Mark as Paid')
                    ->modalDescription('Are you sure you want to mark this cost as paid?')
                    ->action(function ($record) {
                        $record->update(['is_paid' => true]);
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Cost marked as paid')
                            ->body("Invoice {$record->invoice_number} has been marked as paid.")
                            ->send();
                    })
                    ->visible(fn ($record) => !$record->is_paid),
                Tables\Actions\Action::make('mark_unpaid')
                    ->label('Mark as Unpaid')
                    ->icon('heroicon-o-x-circle')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Mark as Unpaid')
                    ->modalDescription('Are you sure you want to mark this cost as unpaid?')
                    ->action(function ($record) {
                        $record->update(['is_paid' => false]);
                        \Filament\Notifications\Notification::make()
                            ->warning()
                            ->title('Cost marked as unpaid')
                            ->body("Invoice {$record->invoice_number} has been marked as unpaid.")
                            ->send();
                    })
                    ->visible(fn ($record) => $record->is_paid),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('mark_paid_bulk')
                        ->label('Mark as Paid')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Mark Selected Costs as Paid')
                        ->modalDescription('Are you sure you want to mark the selected costs as paid?')
                        ->action(function ($records) {
                            $count = $records->count();
                            $records->each(function ($record) {
                                $record->update(['is_paid' => true]);
                            });
                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Costs marked as paid')
                                ->body("{$count} cost(s) have been marked as paid.")
                                ->send();
                        }),
                    Tables\Actions\BulkAction::make('mark_unpaid_bulk')
                        ->label('Mark as Unpaid')
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Mark Selected Costs as Unpaid')
                        ->modalDescription('Are you sure you want to mark the selected costs as unpaid?')
                        ->action(function ($records) {
                            $count = $records->count();
                            $records->each(function ($record) {
                                $record->update(['is_paid' => false]);
                            });
                            \Filament\Notifications\Notification::make()
                                ->warning()
                                ->title('Costs marked as unpaid')
                                ->body("{$count} cost(s) have been marked as unpaid.")
                                ->send();
                        }),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
