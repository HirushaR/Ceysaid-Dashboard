<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeadResource\Pages;
use App\Filament\Resources\LeadResource\RelationManagers;
use App\Models\Lead;
use App\Traits\HasResourcePermissions;
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
use App\Enums\Platform;
use App\Enums\Priority;
use Illuminate\Database\Eloquent\Model;

class LeadResource extends Resource
{
    use HasResourcePermissions;

    protected static ?string $model = Lead::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Dashboard';

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        
        // Admin users can view all resources
        if ($user->isAdmin()) return true;
        
        // Managers can view leads assigned to their team members
        if ($user->isManager()) {
            return true;
        }
        
        // Only show to sales users
        if (!$user->isSales()) {
            return false;
        }
        
        $resourceName = static::getResourceName();
        return $user->canViewResource($resourceName);
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        
        // Admin users can create all resources
        if ($user->isAdmin()) return true;
        
        // Only allow sales users to create leads
        if (!$user->isSales()) {
            return false;
        }
        
        $resourceName = static::getResourceName();
        return $user->canCreateResource($resourceName);
    }

    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        
        // Admin users can edit all resources
        if ($user->isAdmin()) return true;
        
        // Only allow sales users to edit leads
        if (!$user->isSales()) {
            return false;
        }
        
        $resourceName = static::getResourceName();
        return $user->canEditResource($resourceName);
    }

    public static function canDelete(Model $record): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        
        // Admin users can delete all resources
        if ($user->isAdmin()) return true;
        
        // Only allow sales users to delete leads
        if (!$user->isSales()) {
            return false;
        }
        
        $resourceName = static::getResourceName();
        return $user->canDeleteResource($resourceName);
    }

    public static function canView(Model $record): bool
    {
        $user = auth()->user();
        
        \Log::info('LeadResource::canView called', [
            'record_id' => $record->id,
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'user_role' => $user?->role,
            'record_created_by' => $record->created_by,
            'record_assigned_to' => $record->assigned_to,
        ]);
        
        if (!$user) {
            \Log::info('LeadResource::canView - No user, denying');
            return false;
        }
        
        // Admin users can view all resources
        if ($user->isAdmin()) {
            \Log::info('LeadResource::canView - User is admin, allowing');
            return true;
        }
        
        // Managers can view leads assigned to their team members
        if ($user->isManager()) {
            $teamMemberIds = $user->teamMembers()->pluck('id')->toArray();
            
            \Log::info('LeadResource::canView - User is manager', [
                'team_member_ids' => $teamMemberIds,
            ]);
            
            // Check if lead is assigned to any team member
            if (in_array($record->assigned_to, $teamMemberIds)) {
                \Log::info('LeadResource::canView - Lead assigned to team member, allowing');
                return true;
            }
            if (in_array($record->assigned_operator, $teamMemberIds)) {
                \Log::info('LeadResource::canView - Lead operator is team member, allowing');
                return true;
            }
            // Check call center assignments
            if ($record->callCenterCalls()
                ->whereIn('assigned_call_center_user', $teamMemberIds)
                ->exists()) {
                \Log::info('LeadResource::canView - Lead has call center assignment to team member, allowing');
                return true;
            }
        }
        
        // Only allow sales users to view leads
        if (!$user->isSales()) {
            \Log::info('LeadResource::canView - User is not sales, denying');
            return false;
        }
        
        // Sales users can always view leads they created (if they can create, they can view their own)
        if ($record->created_by === $user->id) {
            \Log::info('LeadResource::canView - Lead created by user, allowing');
            return true;
        }
        
        // Sales users can view leads assigned to them (if they have view permission)
        if ($record->assigned_to === $user->id) {
            $resourceName = static::getResourceName();
            $hasPermission = $user->canViewResource($resourceName);
            \Log::info('LeadResource::canView - Lead assigned to user', [
                'has_permission' => $hasPermission,
                'resource_name' => $resourceName,
            ]);
            return $hasPermission;
        }
        
        // For unassigned leads, check permission
        if ($record->assigned_to === null) {
            $resourceName = static::getResourceName();
            $hasPermission = $user->canViewResource($resourceName);
            \Log::info('LeadResource::canView - Lead is unassigned', [
                'has_permission' => $hasPermission,
                'resource_name' => $resourceName,
            ]);
            return $hasPermission;
        }
        
        // Otherwise, deny access
        \Log::info('LeadResource::canView - No matching condition, denying');
        return false;
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
                    Forms\Components\Grid::make(3)
                    ->schema([
                            Forms\Components\TextInput::make('id')
                                ->label('Lead ID')
                                ->disabled()
                                ->helperText('Database ID')
                                ->hidden(fn($livewire) => $livewire instanceof CreateRecord),
                            Forms\Components\TextInput::make('reference_id')
                                ->label('Reference ID')
                                ->disabled()
                                ->helperText('Auto-generated unique identifier'),
                        Forms\Components\TextInput::make('customer_name')
                                ->label('Customer Name')
                                ->required()
                                ->maxLength(255)
                                ->placeholder('Enter customer name')
                                ->columnSpan(2),
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
                            ->options(Platform::options())
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
        $user = auth()->user();
        $query = parent::getEloquentQuery();
        
        \Log::info('LeadResource::getEloquentQuery called', [
            'user_id' => $user?->id,
            'user_email' => $user?->email,
            'user_role' => $user?->role,
            'is_manager' => $user?->isManager(),
            'is_admin' => $user?->isAdmin(),
            'is_sales' => $user?->isSales(),
        ]);
        
        // If user is a manager (and not admin), filter to show only leads assigned to their team members
        // OR leads they created themselves (if they're also a sales user)
        if ($user && $user->isManager() && !$user->isAdmin()) {
            $teamMemberIds = $user->teamMembers()->pluck('id')->toArray();
            
            \Log::info('LeadResource::getEloquentQuery - Manager filtering', [
                'team_member_ids' => $teamMemberIds,
                'manager_id' => $user->id,
                'is_also_sales' => $user->isSales(),
            ]);
            
            if (empty($teamMemberIds)) {
                // No team members, but if they're a sales user, show their own leads
                if ($user->isSales()) {
                    \Log::info('LeadResource::getEloquentQuery - Manager with no team, but is sales - showing own leads');
                    return $query->where(function (Builder $q) use ($user) {
                        $q->whereNull('assigned_to')
                          ->orWhere('assigned_to', $user->id)
                          ->orWhere('created_by', $user->id);
                    });
                }
                // No team members, return empty query
                \Log::info('LeadResource::getEloquentQuery - No team members, returning empty query');
                return $query->whereRaw('1 = 0');
            }
            
            // Get leads assigned to team members via:
            // 1. assigned_to (sales)
            // 2. assigned_operator (operation)
            // 3. call_center_calls.assigned_call_center_user (call center)
            // 4. If manager is also a sales user, include leads they created or assigned to themselves
            $filteredQuery = $query->where(function (Builder $q) use ($teamMemberIds, $user) {
                $q->whereIn('assigned_to', $teamMemberIds)
                  ->orWhereIn('assigned_operator', $teamMemberIds)
                  ->orWhereHas('callCenterCalls', function ($subQuery) use ($teamMemberIds) {
                      $subQuery->whereIn('assigned_call_center_user', $teamMemberIds);
                  });
                
                // If manager is also a sales user, include their own leads
                if ($user->isSales()) {
                    $q->orWhere('created_by', $user->id)
                      ->orWhere('assigned_to', $user->id)
                      ->orWhereNull('assigned_to');
                }
            });
            
            \Log::info('LeadResource::getEloquentQuery - Manager query built', [
                'sql' => $filteredQuery->toSql(),
                'bindings' => $filteredQuery->getBindings(),
            ]);
            
            return $filteredQuery;
        }
        
        // Logic for sales users: show unassigned leads, leads assigned to them, or leads they created
        if ($user && $user->isSales()) {
            \Log::info('LeadResource::getEloquentQuery - Sales user filtering', [
                'user_id' => $user->id,
            ]);
            
            $filteredQuery = $query->where(function (Builder $q) use ($user) {
                $q->whereNull('assigned_to')
                  ->orWhere('assigned_to', $user->id)
                  ->orWhere('created_by', $user->id);
            });
            
            \Log::info('LeadResource::getEloquentQuery - Sales query built', [
                'sql' => $filteredQuery->toSql(),
                'bindings' => $filteredQuery->getBindings(),
            ]);
            
            // Test if lead 1867 would be found
            $testLead = $filteredQuery->find(1867);
            \Log::info('LeadResource::getEloquentQuery - Test find lead 1867', [
                'found' => $testLead ? 'Yes' : 'No',
                'lead_created_by' => $testLead?->created_by,
                'lead_assigned_to' => $testLead?->assigned_to,
            ]);
            
            return $filteredQuery;
        }
        
        \Log::info('LeadResource::getEloquentQuery - No filtering applied, returning base query');
        return $query;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable()
                    ->copyable()
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Small)
                    ->color('primary')
                    ->weight('bold'),
                    
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
                    ->colors(LeadStatus::colorMap())
                    ->formatStateUsing(fn ($state) => LeadStatus::tryFrom($state)?->label() ?? $state),
                    
                Tables\Columns\TextColumn::make('assignedUser.name')
                    ->label('Assigned To')
                    ->sortable()
                    ->searchable()
                    ->placeholder('Unassigned')
                    ->color('info'),
                    
                Tables\Columns\BadgeColumn::make('priority')
                    ->label('Priority')
                    ->colors(Priority::colorMap())
                    ->formatStateUsing(fn ($state) => Priority::tryFrom($state)?->label() ?? ucfirst($state)),
                    
                Tables\Columns\TextColumn::make('platform')
                    ->label('Source')
                    ->badge()
                    ->colors(Platform::colorMap())
                    ->formatStateUsing(fn ($state) => Platform::tryFrom($state)?->label() ?? ucfirst($state)),
                    
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
                    ->options(Priority::options())
                    ->label('Priority'),
                Tables\Filters\SelectFilter::make('platform')
                    ->options(Platform::options())
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
