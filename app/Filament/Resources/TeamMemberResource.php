<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TeamMemberResource\Pages;
use App\Filament\Resources\TeamMemberResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TeamMemberResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Team Members';

    protected static ?string $navigationGroup = 'Management';

    protected static ?string $modelLabel = 'Team Member';

    protected static ?string $pluralModelLabel = 'Team Members';

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user && $user->isManager();
    }

    public static function canCreate(): bool
    {
        return false; // Managers can't create team members through this resource
    }

    public static function canEdit($record): bool
    {
        return false; // Managers can't edit team members through this resource
    }

    public static function canDelete($record): bool
    {
        return false; // Managers can't delete team members through this resource
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        
        if (!$user || !$user->isManager()) {
            return parent::getEloquentQuery()->whereRaw('1 = 0'); // Return empty query
        }

        // Return team members (users with same role, excluding manager and other managers)
        return parent::getEloquentQuery()
            ->where('role', $user->role)
            ->where('id', '!=', $user->id)
            ->where('is_manager', false);
    }

    public static function form(Form $form): Form
    {
        // Read-only form for viewing team member details
        return $form
            ->schema([
                Forms\Components\Section::make('Team Member Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Full Name')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\TextInput::make('role')
                            ->label('Role')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn ($state) => ucfirst($state)),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Full Name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium')
                    ->description(fn ($record) => $record->email),
                    
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->icon('heroicon-o-envelope'),
                    
                Tables\Columns\TextColumn::make('leads_count')
                    ->label('Total Leads')
                    ->badge()
                    ->color('primary')
                    ->getStateUsing(function (User $record) {
                        return $record->getAllLeads()->count();
                    })
                    ->sortable()
                    ->description(fn ($record) => match(true) {
                        $record->isCallCenter() => 'Call Center',
                        $record->isOperation() => 'As Operator',
                        default => 'Assigned'
                    }),
                    
                Tables\Columns\TextColumn::make('active_leads_count')
                    ->label('Active Leads')
                    ->badge()
                    ->color('success')
                    ->getStateUsing(function (User $record) {
                        return $record->getAllLeads()
                            ->whereNotIn('status', ['mark_closed', 'operation_complete', 'document_upload_complete'])
                            ->count();
                    })
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('leaves_count')
                    ->label('Total Leaves')
                    ->counts('leaves')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('active_leaves_count')
                    ->label('Active Leaves')
                    ->counts([
                        'leaves' => fn ($query) => $query->where('status', 'approved')
                            ->where('start_date', '<=', now())
                            ->where('end_date', '>=', now())
                    ])
                    ->badge()
                    ->color('warning')
                    ->sortable(),
                    
                Tables\Columns\IconColumn::make('email_verified_at')
                    ->label('Verified')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                    
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Joined')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->since()
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Small)
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('email_verified_at')
                    ->label('Email Verification')
                    ->trueLabel('Verified')
                    ->falseLabel('Unverified')
                    ->nullable(),
                Tables\Filters\Filter::make('has_active_leaves')
                    ->label('Has Active Leaves')
                    ->query(fn ($query) => $query->whereHas('leaves', function ($q) {
                        $q->where('status', 'approved')
                            ->where('start_date', '<=', now())
                            ->where('end_date', '>=', now());
                    })),
                Tables\Filters\Filter::make('has_leads')
                    ->label('Has Leads')
                    ->query(fn ($query) => $query->whereHas('leads')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->button()
                    ->size('sm'),
            ])
            ->bulkActions([]) // No bulk actions for team members
            ->striped()
            ->paginated([10, 25, 50, 100])
            ->emptyStateHeading('No Team Members')
            ->emptyStateDescription('You don\'t have any team members yet.')
            ->emptyStateIcon('heroicon-o-user-group');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\LeadsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTeamMembers::route('/'),
            'view' => Pages\ViewTeamMember::route('/{record}'),
            // Create and Edit pages removed - managers can't create/edit team members
        ];
    }
}
