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
use App\Enums\LeadStatus;
use App\Enums\ServiceStatus;

class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Dashboard';

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user && ($user->isSales() || $user->isMarketing() || $user->isOperation() || $user->isAdmin());
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Lead Information')
                    ->schema([
                        Forms\Components\TextInput::make('reference_id')->label('Reference ID')->disabled(),
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
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Contact Information')
                    ->schema([
                        Forms\Components\Select::make('contact_method')
                            ->options([
                                'phone' => 'Phone',
                                'email' => 'Email',
                                'whatsapp' => 'WhatsApp',
                                'facebook' => 'Facebook',
                            ]),
                        Forms\Components\TextInput::make('contact_value'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Assignment & Status')
                    ->schema([
                        Forms\Components\Select::make('assigned_to')
                            ->relationship('assignedUser', 'name')
                            ->searchable()
                            ->hidden(fn($livewire) => $livewire instanceof CreateRecord),
                        Forms\Components\Select::make('assigned_operator')
                            ->relationship('assignedOperator', 'name')
                            ->searchable()
                            ->hidden(fn($livewire) => $livewire instanceof CreateRecord),
                        Forms\Components\Select::make('status')
                            ->options(LeadStatus::options())
                            ->required()
                            ->default(LeadStatus::NEW->value)
                            ->hidden(fn($livewire) => $livewire instanceof CreateRecord),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\Section::make('Travel Details')
                    ->schema([
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
                        Forms\Components\Textarea::make('tour_details')->label('Tour Details'),
                    ])
                    ->columns(2)
                    ->collapsible(),

                Forms\Components\DateTimePicker::make('created_at')->disabled(),
                Forms\Components\DateTimePicker::make('updated_at')->disabled(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();
        if ($user && $user->isSales()) {
            $query->whereNull('assigned_to');
        }
        return $query;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference_id')->label('Reference ID')->sortable(),
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
                    ->colors(LeadStatus::colorMap()),
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
                    ->options(LeadStatus::options())
                    ->label('Lead Status'),
                Tables\Filters\SelectFilter::make('priority')
                    ->options([
                        'low' => 'Low',
                        'medium' => 'Medium',
                        'high' => 'High',
                    ])
                    ->label('Priority'),
                Tables\Filters\SelectFilter::make('platform')
                    ->options([
                        'facebook' => 'Facebook',
                        'whatsapp' => 'WhatsApp',
                        'email' => 'Email',
                    ])
                    ->label('Platform'),
                Tables\Filters\SelectFilter::make('air_ticket_status')
                    ->options(ServiceStatus::options())
                    ->label('Air Ticket Status'),
                Tables\Filters\SelectFilter::make('hotel_status')
                    ->options(ServiceStatus::options())
                    ->label('Hotel Status'),
                Tables\Filters\SelectFilter::make('visa_status')
                    ->options(ServiceStatus::options())
                    ->label('Visa Status'),
                Tables\Filters\SelectFilter::make('land_package_status')
                    ->options(ServiceStatus::options())
                    ->label('Land Package Status'),
                Tables\Filters\SelectFilter::make('assigned_to')
                    ->relationship('assignedUser', 'name')
                    ->label('Assigned To')
                    ->searchable(),
                Tables\Filters\SelectFilter::make('assigned_operator')
                    ->relationship('assignedOperator', 'name')
                    ->label('Assigned Operator')
                    ->searchable(),
                Tables\Filters\TernaryFilter::make('unassigned')
                    ->label('Assignment Status')
                    ->trueLabel('Unassigned')
                    ->falseLabel('Assigned')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNull('assigned_to'),
                        false: fn (Builder $query) => $query->whereNotNull('assigned_to'),
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            'view' => Pages\ViewLead::route('/{record}'),
            'edit' => Pages\EditLead::route('/{record}/edit'),
        ];
    }
}
