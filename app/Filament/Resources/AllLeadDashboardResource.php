<?php

namespace App\Filament\Resources;

use App\Models\Lead;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\AllLeadDashboardResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use App\Enums\LeadStatus;

class AllLeadDashboardResource extends Resource
{
    protected static ?string $model = Lead::class;
    protected static ?string $navigationIcon = 'heroicon-o-table-cells';
    protected static ?string $navigationLabel = 'All Lead Dashboard';
    protected static ?string $label = 'All Lead Dashboard';
    protected static ?string $pluralLabel = 'All Lead Dashboard';
    protected static ?string $navigationGroup = 'Dashboard';

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user && $user->isOperation();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('status', \App\Enums\LeadStatus::INFO_GATHER_COMPLETE->value);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAllLeadDashboards::route('/'),
            'view' => Pages\ViewAllLeadDashboard::route('/{record}'),
            'edit' => Pages\EditAllLeadDashboard::route('/{record}/edit'),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference_id')
                    ->label('Reference ID')
                    ->sortable()
                    ->searchable()
                    ->copyable()
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Small)
                    ->color('gray'),
                    
                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Customer')
                    ->sortable()
                    ->searchable()
                    ->weight('medium')
                    ->description(fn ($record) => $record->customer?->name ? "System: {$record->customer->name}" : null),
                    
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'secondary' => LeadStatus::NEW->value,
                        'info' => LeadStatus::ASSIGNED_TO_SALES->value,
                        'warning' => LeadStatus::ASSIGNED_TO_OPERATIONS->value,
                        'success' => LeadStatus::INFO_GATHER_COMPLETE->value,
                        'primary' => LeadStatus::PRICING_IN_PROGRESS->value,
                        'accent' => LeadStatus::SENT_TO_CUSTOMER->value,
                        'brand' => LeadStatus::CONFIRMED->value,
                        'danger' => LeadStatus::MARK_CLOSED->value,
                    ])
                    ->formatStateUsing(fn ($state) => LeadStatus::tryFrom($state)?->label() ?? $state),
                    
                Tables\Columns\TextColumn::make('assignedUser.name')
                    ->label('Sales Rep')
                    ->sortable()
                    ->searchable()
                    ->placeholder('Unassigned')
                    ->color('info'),
                    
                Tables\Columns\TextColumn::make('assignedOperator.name')
                    ->label('Operator')
                    ->sortable()
                    ->searchable()
                    ->placeholder('Unassigned')
                    ->color('warning'),
                    
                Tables\Columns\TextColumn::make('platform')
                    ->label('Source')
                    ->badge()
                    ->colors([
                        'info' => 'facebook',
                        'success' => 'whatsapp', 
                        'warning' => 'email',
                    ])
                    ->formatStateUsing(fn ($state) => ucfirst($state)),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->since()
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Small)
                    ->color('gray'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(LeadStatus::options())
                    ->label('Lead Status'),
                Tables\Filters\SelectFilter::make('platform')
                    ->options([
                        'facebook' => 'Facebook',
                        'whatsapp' => 'WhatsApp',
                        'email' => 'Email',
                    ])
                    ->label('Platform'),
                Tables\Filters\SelectFilter::make('assigned_to')
                    ->relationship('assignedUser', 'name')
                    ->label('Assigned To')
                    ->searchable(),
                Tables\Filters\SelectFilter::make('assigned_operator')
                    ->relationship('assignedOperator', 'name')
                    ->label('Assigned Operator')
                    ->searchable(),
                Tables\Filters\SelectFilter::make('created_by')
                    ->relationship('creator', 'name')
                    ->label('Created By')
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->button()
                    ->size('sm'),
                Tables\Actions\EditAction::make()
                    ->button()
                    ->size('sm')
                    ->color('gray'),
            ])
            ->recordUrl(fn($record) => static::getUrl('view', ['record' => $record]))
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('customer_name')->label('Customer Name')->disabled(fn($context) => $context === 'view'),
                Forms\Components\Select::make('customer_id')
                    ->label('Customer')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->createOptionForm([
                        Forms\Components\TextInput::make('name')->required(),
                        Forms\Components\Textarea::make('contact_info')->label('Contact Info'),
                    ])
                    ->disabled(fn($context) => $context === 'view'),
                Forms\Components\TextInput::make('platform')->label('Platform')->disabled(fn($context) => $context === 'view'),
                Forms\Components\Textarea::make('tour')->label('Tour')->disabled(fn($context) => $context === 'view'),
                Forms\Components\Textarea::make('message')->label('Message')->disabled(fn($context) => $context === 'view'),
                Forms\Components\TextInput::make('created_by')->label('Created By')->disabled(fn($context) => $context === 'view'),
                Forms\Components\TextInput::make('assigned_to')->label('Assigned To')->disabled(fn($context) => $context === 'view'),
                Forms\Components\TextInput::make('assigned_operator')->label('Assigned Operator')->disabled(fn($context) => $context === 'view'),
                Forms\Components\Placeholder::make('status')
                    ->label('Status')
                    ->content(fn($record) => LeadStatus::tryFrom($record->status)?->label() ?? $record->status ?? '')
                    ->visible(fn($context) => $context === 'view'),
                Forms\Components\TextInput::make('contact_method')->label('Contact Method')->disabled(fn($context) => $context === 'view'),
                Forms\Components\TextInput::make('contact_value')->label('Contact Value')->disabled(fn($context) => $context === 'view'),
                Forms\Components\TextInput::make('subject')->label('Subject')->disabled(fn($context) => $context === 'view'),
                Forms\Components\TextInput::make('country')->label('Country')->disabled(fn($context) => $context === 'view'),
                Forms\Components\TextInput::make('destination')->label('Destination')->disabled(fn($context) => $context === 'view'),
                Forms\Components\TextInput::make('number_of_adults')->label('Number of Adults')->disabled(fn($context) => $context === 'view'),
                Forms\Components\TextInput::make('number_of_children')->label('Number of Children')->disabled(fn($context) => $context === 'view'),
                Forms\Components\TextInput::make('number_of_infants')->label('Number of Infants')->disabled(fn($context) => $context === 'view'),
                Forms\Components\TextInput::make('priority')->label('Priority')->disabled(fn($context) => $context === 'view'),
                Forms\Components\DatePicker::make('arrival_date')->label('Arrival Date')->disabled(fn($context) => $context === 'view'),
                Forms\Components\DatePicker::make('depature_date')->label('Departure Date')->disabled(fn($context) => $context === 'view'),
                Forms\Components\TextInput::make('number_of_days')->label('Number of Days')->disabled(fn($context) => $context === 'view'),
                Forms\Components\Textarea::make('tour_details')->label('Tour Details')->disabled(fn($context) => $context === 'view'),
                Forms\Components\Textarea::make('attachments')->label('Attachments')->disabled(fn($context) => $context === 'view'),
                Forms\Components\DateTimePicker::make('created_at')->label('Created At')->disabled(),
                Forms\Components\DateTimePicker::make('updated_at')->label('Updated At')->disabled(),
            ]);
    }
} 