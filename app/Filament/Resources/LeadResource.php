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
            ->schema(static::getFormSchema())
            ->columns(1);
    }

    public static function getFormSchema(): array
    {
        return [
            // Basic Information Section
            Forms\Components\Section::make('Basic Information')
                ->description('Core lead details and customer information')
            ->schema([
                    Forms\Components\Grid::make(2)
                    ->schema([
                            Forms\Components\TextInput::make('reference_id')
                                ->label('Reference ID')
                                ->disabled()
                                ->helperText('Auto-generated unique identifier'),
                        Forms\Components\TextInput::make('customer_name')
                                ->label('Customer Name')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('Enter customer name'),
                        ]),
                    Forms\Components\Grid::make(2)
                        ->schema([
                        Forms\Components\Select::make('customer_id')
                                ->label('Linked Customer')
                            ->relationship('customer', 'name')
                            ->searchable()
                                ->createOptionForm([
                                    Forms\Components\TextInput::make('name')->required(),
                                    Forms\Components\Textarea::make('contact_info')->label('Contact Info'),
                                ])
                            ->hidden(fn($livewire) => $livewire instanceof CreateRecord),
                        Forms\Components\Select::make('platform')
                                ->label('Source Platform')
                            ->options([
                                'facebook' => 'Facebook',
                                'whatsapp' => 'WhatsApp',
                                'email' => 'Email',
                            ])
                                ->required()
                                ->native(false),
                        ]),
                    Forms\Components\Textarea::make('tour')
                        ->label('Tour Requirements')
                        ->rows(2)
                        ->placeholder('Describe the requested tour or package'),
                    Forms\Components\Textarea::make('message')
                        ->label('Customer Message')
                        ->rows(3)
                        ->placeholder('Original customer message or inquiry'),
                        Forms\Components\Hidden::make('created_by')
                            ->default(fn() => auth()->id()),
                    ])
                ->collapsed(false)
                ->compact(),

            // Contact Information Section
                Forms\Components\Section::make('Contact Information')
                ->description('Customer contact details')
                ->schema([
                    Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\Select::make('contact_method')
                                ->label('Contact Method')
                            ->options([
                                'phone' => 'Phone',
                                'email' => 'Email',
                                'whatsapp' => 'WhatsApp',
                                'facebook' => 'Facebook',
                                ])
                                ->native(false),
                            Forms\Components\TextInput::make('contact_value')
                                ->label('Contact Value')
                                ->placeholder('Enter phone, email, or contact ID'),
                        ]),
                    ])
                ->collapsed(false)
                ->compact(),

            // Travel Details Section
            Forms\Components\Section::make('Travel Details')
                ->description('Trip specifications and requirements')
                ->schema([
                    Forms\Components\Grid::make(3)
                        ->schema([
                            Forms\Components\TextInput::make('subject')
                                ->label('Subject')
                                ->placeholder('Trip title or subject'),
                            Forms\Components\TextInput::make('country')
                                ->label('Country')
                                ->placeholder('Destination country'),
                            Forms\Components\TextInput::make('destination')
                                ->label('Destination')
                                ->placeholder('Specific destination'),
                        ]),
                    Forms\Components\Grid::make(3)
                        ->schema([
                            Forms\Components\TextInput::make('number_of_adults')
                                ->label('Adults')
                                ->numeric()
                                ->minValue(0)
                                ->default(1),
                            Forms\Components\TextInput::make('number_of_children')
                                ->label('Children')
                                ->numeric()
                                ->minValue(0)
                                ->default(0),
                            Forms\Components\TextInput::make('number_of_infants')
                                ->label('Infants')
                                ->numeric()
                                ->minValue(0)
                                ->default(0),
                        ]),
                    Forms\Components\Grid::make(3)
                        ->schema([
                            Forms\Components\DatePicker::make('arrival_date')
                                ->label('Arrival Date')
                                ->native(false),
                            Forms\Components\DatePicker::make('depature_date')
                                ->label('Departure Date')
                                ->native(false),
                            Forms\Components\TextInput::make('number_of_days')
                                ->label('Duration (Days)')
                                ->numeric()
                                ->minValue(1),
                        ]),
                    Forms\Components\Select::make('priority')
                        ->label('Priority Level')
                        ->options([
                            'low' => 'Low',
                            'medium' => 'Medium',
                            'high' => 'High',
                        ])
                        ->default('medium')
                        ->native(false),
                    Forms\Components\Textarea::make('tour_details')
                        ->label('Tour Details')
                        ->rows(3)
                        ->placeholder('Detailed tour requirements and specifications'),
                ])
                ->collapsed(false)
                ->compact(),

            // Assignment & Status Section (Only visible in edit/view)
                Forms\Components\Section::make('Assignment & Status')
                ->description('Lead assignment and current status')
                ->schema([
                    Forms\Components\Grid::make(3)
                    ->schema([
                        Forms\Components\Select::make('assigned_to')
                                ->label('Assigned Sales Rep')
                            ->relationship('assignedUser', 'name')
                            ->searchable()
                                ->placeholder('Select sales representative'),
                        Forms\Components\Select::make('assigned_operator')
                                ->label('Assigned Operator')
                            ->relationship('assignedOperator', 'name')
                            ->searchable()
                                ->placeholder('Select operations staff'),
                        Forms\Components\Select::make('status')
                                ->label('Lead Status')
                            ->options(LeadStatus::options())
                            ->required()
                            ->default(LeadStatus::NEW->value)
                                ->native(false),
                        ]),
                ])
                ->hidden(fn($livewire) => $livewire instanceof CreateRecord)
                ->collapsed(true)
                ->compact(),

            // System Information (Only in view mode)
            Forms\Components\Section::make('System Information')
                ->description('Timestamps and system data')
                ->schema([
                    Forms\Components\Grid::make(2)
                    ->schema([
                            Forms\Components\DateTimePicker::make('created_at')
                                ->label('Created At')
                                ->disabled()
                                ->displayFormat('M j, Y \a\t g:i A'),
                            Forms\Components\DateTimePicker::make('updated_at')
                                ->label('Last Updated')
                                ->disabled()
                                ->displayFormat('M j, Y \a\t g:i A'),
                        ]),
                ])
                ->hidden(fn($context) => $context !== 'view')
                ->collapsed(true)
                ->compact(),
        ];
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
                    ->label('Assigned To')
                    ->sortable()
                    ->searchable()
                    ->placeholder('Unassigned')
                    ->color('info'),
                    
                Tables\Columns\BadgeColumn::make('priority')
                    ->label('Priority')
                    ->colors([
                        'gray' => 'low',
                        'warning' => 'medium',
                        'danger' => 'high',
                    ])
                    ->formatStateUsing(fn ($state) => ucfirst($state)),
                    
                Tables\Columns\TextColumn::make('platform')
                    ->label('Source')
                    ->badge()
                    ->colors([
                        'info' => 'facebook',
                        'success' => 'whatsapp', 
                        'warning' => 'email',
                    ])
                    ->formatStateUsing(fn ($state) => ucfirst($state)),
                    
                Tables\Columns\TextColumn::make('destination')
                    ->label('Destination')
                    ->limit(15)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 15 ? $state : null;
                    }),
                    
                Tables\Columns\TextColumn::make('arrival_date')
                    ->label('Travel Date')
                    ->date('M j, Y')
                    ->sortable()
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Small),
                    
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
                Tables\Filters\SelectFilter::make('assigned_to')
                    ->relationship('assignedUser', 'name')
                    ->label('Assigned To')
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
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->striped()
            ->paginated([10, 25, 50, 100]);
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
