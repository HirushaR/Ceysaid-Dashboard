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
use App\Enums\Platform;
use Illuminate\Database\Eloquent\Model;

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
        if (!$user) return false;
        
        // Admin and operation users can view this resource
        return $user->isAdmin() || $user->isOperation();
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        
        // Admin users can create leads
        return $user->isAdmin();
    }

    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        
        // Admin users can edit all leads
        if ($user->isAdmin()) return true;
        
        // Operation users can edit all leads in the dashboard (they have leads.edit permission)
        if ($user->isOperation()) return true;
        
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        
        // Only admin users can delete leads
        return $user->isAdmin();
    }

    public static function canView(Model $record): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        
        // Admin users can view all leads
        if ($user->isAdmin()) return true;
        
        // Operation users can view all leads in the dashboard (they have dashboard.all_leads permission)
        if ($user->isOperation()) return true;
        
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $query = parent::getEloquentQuery()->notArchived();
        
        // Admin users can see all leads, operation users see only INFO_GATHER_COMPLETE
        if (!$user || !$user->isAdmin()) {
            $query->where('status', \App\Enums\LeadStatus::INFO_GATHER_COMPLETE->value);
        }
        
        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAllLeadDashboards::route('/'),
            'create' => Pages\CreateAllLeadDashboard::route('/create'),
            'view' => Pages\ViewAllLeadDashboard::route('/{record}'),
            'edit' => Pages\EditAllLeadDashboard::route('/{record}/edit'),
        ];
    }

    public static function table(Table $table): Table
    {
        $user = auth()->user();
        $isAdmin = $user && $user->isAdmin();
        
        $columns = [
            Tables\Columns\TextColumn::make('id')
                ->label('ID')
                ->sortable()
                ->searchable()
                ->copyable()
                ->size(Tables\Columns\TextColumn\TextColumnSize::Small)
                ->color('primary')
                ->weight('bold'),
                
            Tables\Columns\TextColumn::make('customer_name')
                ->label('Customer')
                ->sortable()
                ->searchable()
                ->weight('medium')
                ->description(fn ($record) => $record->customer?->name ? "System: {$record->customer->name}" : null),
                
            Tables\Columns\BadgeColumn::make('status')
                ->label('Status')
                ->colors(LeadStatus::colorMap())
                ->formatStateUsing(fn ($state) => LeadStatus::tryFrom($state)?->label() ?? $state),
                
            Tables\Columns\TextColumn::make('priority')
                ->label('Priority')
                ->badge()
                ->color(fn (?string $state): string => match ($state) {
                    'low' => 'gray',
                    'medium' => 'warning',
                    'high' => 'danger',
                    default => 'gray',
                })
                ->formatStateUsing(fn ($state) => ucfirst($state ?? 'medium')),
        ];

        // Add additional columns for admin users only
        if ($isAdmin) {
            $columns = array_merge($columns, [
                Tables\Columns\TextColumn::make('reference_id')
                    ->label('Reference ID')
                    ->sortable()
                    ->searchable()
                    ->copyable()
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Small)
                    ->color('gray'),
                    
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
                    ->colors(Platform::colorMap())
                    ->formatStateUsing(fn ($state) => Platform::tryFrom($state)?->label() ?? ucfirst($state)),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->since()
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Small)
                    ->color('gray'),
            ]);
        }
        
        return $table
            ->columns($columns)
            ->modifyQueryUsing(function ($query) {
                // Check if user has applied a custom sort
                $hasCustomSort = request()->has('tableSort') && request()->get('tableSort') !== '';
                
                if (!$hasCustomSort) {
                    // Default: Sort by priority first (high > medium > low), then by creation date (oldest first)
                    $query->orderByRaw("
                        CASE 
                            WHEN priority = 'high' THEN 1
                            WHEN priority = 'medium' THEN 2
                            WHEN priority = 'low' THEN 3
                            ELSE 4
                        END ASC
                    ")->orderBy('created_at', 'asc');
                } else {
                    // When user sorts by a column, apply priority as secondary sort to keep high priority leads visible
                    $query->orderByRaw("
                        CASE 
                            WHEN priority = 'high' THEN 1
                            WHEN priority = 'medium' THEN 2
                            WHEN priority = 'low' THEN 3
                            ELSE 4
                        END ASC
                    ");
                }
            })
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
                // Only show these filters for admin users
                Tables\Filters\SelectFilter::make('platform')
                    ->options(Platform::options())
                    ->label('Platform')
                    ->visible(fn() => auth()->user()?->isAdmin()),
                Tables\Filters\SelectFilter::make('assigned_to')
                    ->relationship('assignedUser', 'name')
                    ->label('Assigned To')
                    ->searchable()
                    ->visible(fn() => auth()->user()?->isAdmin()),
                Tables\Filters\SelectFilter::make('assigned_operator')
                    ->relationship('assignedOperator', 'name')
                    ->label('Assigned Operator')
                    ->searchable(),
                Tables\Filters\SelectFilter::make('created_by')
                    ->relationship('creator', 'name')
                    ->label('Created By')
                    ->searchable()
                    ->visible(fn() => auth()->user()?->isAdmin()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->button()
                    ->size('sm'),
                Tables\Actions\EditAction::make()
                    ->button()
                    ->size('sm')
                    ->color('gray'),
                Tables\Actions\DeleteAction::make()
                    ->button()
                    ->size('sm')
                    ->color('danger')
                    ->visible(fn() => auth()->user()?->isAdmin()),
                Tables\Actions\Action::make('archive')
                    ->label('Archive')
                    ->icon('heroicon-o-archive-box')
                    ->color('warning')
                    ->button()
                    ->size('sm')
                    ->visible(fn($record) => auth()->user()?->isAdmin() && !$record->isArchived())
                    ->requiresConfirmation()
                    ->modalHeading('Archive Lead')
                    ->modalDescription('Are you sure you want to archive this lead? It will be hidden from all dashboards but can be accessed from the Archive Leads dashboard.')
                    ->action(function ($record) {
                        $user = auth()->user();
                        $record->archived_at = now();
                        $record->archived_by = $user->id;
                        $record->save();
                        
                        // Log archive action
                        \App\Models\LeadActionLog::create([
                            'lead_id' => $record->id,
                            'user_id' => $user->id,
                            'action' => 'archived',
                            'description' => 'Lead archived',
                        ]);
                        
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Lead archived successfully.')
                            ->send();
                    }),
            ])
            ->recordUrl(fn($record) => static::getUrl('view', ['record' => $record]))
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->visible(fn() => auth()->user()?->isAdmin()),
            ])
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