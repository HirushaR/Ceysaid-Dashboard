<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeadResource\Pages;
use App\Filament\Resources\LeadResource\RelationManagers;
use App\Models\Lead;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Resources\Pages\CreateRecord;
use Filament\Resources\Pages\EditRecord;

class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('customer_name')
                    ->required(),
                Forms\Components\Select::make('customer_id')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->hidden(fn($livewire) => $livewire instanceof CreateRecord),
                Forms\Components\Select::make('platform')
                    ->options([
                        'facebook' => 'Facebook',
                        'whatsapp' => 'WhatsApp',
                        'email' => 'Email',
                    ])
                    ->required(),
                Forms\Components\Textarea::make('tour'),
                Forms\Components\Textarea::make('message'),
                Forms\Components\Hidden::make('created_by')
                    ->default(fn() => auth()->id()),
                Forms\Components\Select::make('assigned_to')
                    ->relationship('assignedUser', 'name')
                    ->searchable()
                    ->hidden(fn($livewire) => $livewire instanceof CreateRecord),
                Forms\Components\Select::make('assigned_operator')
                    ->relationship('assignedOperator', 'name')
                    ->searchable()
                    ->hidden(fn($livewire) => $livewire instanceof CreateRecord),
                Forms\Components\Select::make('status')
                    ->options([
                        'new' => 'New',
                        'assigned_to_sales' => 'Assigned to Sales',
                        'assigned_to_operations' => 'Assigned to Operations',
                        'info_gather_complete' => 'Info Gather Complete',
                        'mark_completed' => 'Mark Completed',
                        'mark_closed' => 'Mark Closed',
                        'pricing_in_progress' => 'Pricing In Progress',
                        'sent_to_customer' => 'Sent to Customer',
                    ])
                    ->required()
                    ->default('new')
                    ->hidden(fn($livewire) => $livewire instanceof CreateRecord),
                Forms\Components\Select::make('contact_method')
                    ->options([
                        'phone' => 'Phone',
                        'email' => 'Email',
                        'whatsapp' => 'WhatsApp',
                        'facebook' => 'Facebook',
                    ]),
                Forms\Components\TextInput::make('contact_value'),
                Forms\Components\TextInput::make('subject'),
                Forms\Components\TextInput::make('country'),
                Forms\Components\TextInput::make('destination'),
                Forms\Components\TextInput::make('number_of_adults')->numeric(),
                Forms\Components\TextInput::make('number_of_children')->numeric(),
                Forms\Components\TextInput::make('number_of_infants')->numeric(),
                Forms\Components\Select::make('priority')
                    ->options([
                        'low' => 'Low',
                        'medium' => 'Medium',
                        'high' => 'High',
                    ]),
                Forms\Components\DatePicker::make('arrival_date'),
                Forms\Components\DatePicker::make('depature_date'),
                Forms\Components\TextInput::make('number_of_days')->numeric(),

                Forms\Components\DateTimePicker::make('created_at')->disabled(),
                Forms\Components\DateTimePicker::make('updated_at')->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('customer_name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('customer.name')->label('Customer')->sortable()->searchable(),
                Tables\Columns\BadgeColumn::make('platform')
                    ->colors([
                        'facebook' => 'info',
                        'whatsapp' => 'success',
                        'email' => 'warning',
                    ]),
                Tables\Columns\TextColumn::make('tour')->limit(20),
                Tables\Columns\TextColumn::make('message')->limit(20),
                Tables\Columns\TextColumn::make('created_by')->sortable(),
                Tables\Columns\TextColumn::make('assignedUser.name')->label('Assigned To')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('assignedOperator.name')->label('Assigned Operator')->sortable()->searchable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'new' => 'gray',
                        'assigned_to_sales' => 'info',
                        'assigned_to_operations' => 'warning',
                        'info_gather_complete' => 'success',
                        'mark_completed' => 'success',
                        'mark_closed' => 'danger',
                        'pricing_in_progress' => 'primary',
                        'sent_to_customer' => 'success',
                    ]),
                Tables\Columns\BadgeColumn::make('priority')
                    ->colors([
                        'low' => 'gray',
                        'medium' => 'warning',
                        'high' => 'danger',
                    ]),
                Tables\Columns\TextColumn::make('contact_method'),
                Tables\Columns\TextColumn::make('contact_value'),
                Tables\Columns\TextColumn::make('subject'),
                Tables\Columns\TextColumn::make('country'),
                Tables\Columns\TextColumn::make('destination'),
                Tables\Columns\TextColumn::make('number_of_adults'),
                Tables\Columns\TextColumn::make('number_of_children'),
                Tables\Columns\TextColumn::make('number_of_infants'),
                Tables\Columns\TextColumn::make('arrival_date')->date(),
                Tables\Columns\TextColumn::make('depature_date')->date(),
                Tables\Columns\TextColumn::make('number_of_days'),                
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'info_gather_complete' => 'Info Gather Complete',
                        'mark_completed' => 'Mark Completed',
                        'mark_closed' => 'Mark Closed',
                        'pricing_in_progress' => 'Pricing In Progress',
                        'sent_to_customer' => 'Sent to Customer',
                    ]),
                Tables\Filters\TernaryFilter::make('assigned_to')
                    ->label('Unassigned')
                    ->trueLabel('Unassigned')
                    ->falseLabel('Assigned')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNull('assigned_to'),
                        false: fn (Builder $query) => $query->whereNotNull('assigned_to'),
                    ),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeads::route('/'),
            'create' => Pages\CreateLead::route('/create'),
            'edit' => Pages\EditLead::route('/{record}/edit'),
        ];
    }
}
