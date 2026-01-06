<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use App\Models\PermissionGroup;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'HR Management';

    protected static ?string $navigationLabel = 'User Management';

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        return $user && ($user->hasPermission('users.view') || $user->isHR() || $user->isAdmin());
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();
        return $user && ($user->hasPermission('users.create') || $user->isHR() || $user->isAdmin());
    }

    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();
        
        // Prevent users from editing themselves
        if ($user && $user->id === $record->id) {
            return false;
        }
        
        return $user && ($user->hasPermission('users.edit') || $user->isHR() || $user->isAdmin());
    }

    public static function canDelete(Model $record): bool
    {
        $user = auth()->user();
        return $user && ($user->hasPermission('users.delete') || $user->isHR() || $user->isAdmin());
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('User Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Forms\Components\Select::make('role')
                            ->options([
                                'marketing' => 'Marketing',
                                'sales' => 'Sales',
                                'operation' => 'Operation',
                                'hr' => 'HR',
                                'admin' => 'Admin',
                                'account' => 'Account',
                                'call_center' => 'Call Center',
                            ])
                            ->required()
                            ->default('marketing')
                            ->helperText('Legacy role field - permissions are now managed separately')
                            ->live()
                            ->afterStateUpdated(function ($state, Forms\Set $set) {
                                // Reset manager checkbox if role is not eligible
                                $manageableRoles = ['sales', 'call_center', 'operation', 'account', 'hr'];
                                if (!in_array($state, $manageableRoles)) {
                                    $set('is_manager', false);
                                }
                            }),
                        Forms\Components\Toggle::make('is_manager')
                            ->label('Manager')
                            ->helperText('Enable this user to view and manage team members with the same role')
                            ->visible(function (Forms\Get $get) {
                                $role = $get('role');
                                $manageableRoles = ['sales', 'call_center', 'operation', 'account', 'hr'];
                                return in_array($role, $manageableRoles);
                            })
                            ->default(false),
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->maxLength(255)
                            ->helperText('Leave blank to keep current password'),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('Quick Permission Assignment')
                    ->schema([
                        Forms\Components\Select::make('permission_groups')
                            ->label('Permission Groups')
                            ->options(PermissionGroup::all()->pluck('display_name', 'id'))
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->placeholder('Select permission groups...')
                            ->helperText('Select permission groups to quickly assign common permission sets. Each group contains multiple related permissions. You can view detailed permissions in the "User Permissions" tab after saving.')
                            ->afterStateHydrated(function ($state, $record) {
                                if ($record && $record->exists) {
                                    return $record->permissionGroups->pluck('id')->toArray();
                                }
                                return [];
                            })
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->collapsed(),
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
                    
                Tables\Columns\BadgeColumn::make('role')
                    ->label('Role')
                    ->colors([
                        'danger' => 'admin',
                        'info' => 'hr',
                        'primary' => 'marketing',
                        'success' => 'sales', 
                        'warning' => 'operation',
                        'secondary' => 'account',
                        'info' => 'call_center',
                    ])
                    ->formatStateUsing(fn ($state) => ucfirst($state))
                    ->sortable(),
                    
                Tables\Columns\IconColumn::make('is_manager')
                    ->label('Manager')
                    ->boolean()
                    ->trueIcon('heroicon-o-shield-check')
                    ->falseIcon('heroicon-o-user')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->sortable()
                    ->toggleable(),
                    
                Tables\Columns\TextColumn::make('permissions_count')
                    ->label('Active Permissions')
                    ->counts('permissions')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                    
                Tables\Columns\IconColumn::make('email_verified_at')
                    ->label('Verified')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                    
                Tables\Columns\TextColumn::make('leaves_count')
                    ->label('Active Leaves')
                    ->counts([
                        'leaves' => fn ($query) => $query->where('status', 'approved')
                            ->where('start_date', '<=', now())
                            ->where('end_date', '>=', now())
                    ])
                    ->badge()
                    ->color('info'),
                
                Tables\Columns\TextColumn::make('leave_balance_casual')
                    ->label('Casual')
                    ->getStateUsing(function ($record) {
                        $remaining = $record->getRemainingLeaves();
                        return $remaining['casual'] . ' / 7';
                    })
                    ->badge()
                    ->color(fn ($record) => $record->getRemainingLeaves()['casual'] > 0 ? 'success' : 'danger')
                    ->sortable(false)
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('leave_balance_sick')
                    ->label('Sick')
                    ->getStateUsing(function ($record) {
                        $remaining = $record->getRemainingLeaves();
                        return $remaining['sick'] . ' / 7';
                    })
                    ->badge()
                    ->color(fn ($record) => $record->getRemainingLeaves()['sick'] > 0 ? 'success' : 'danger')
                    ->sortable(false)
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('leave_balance_annual')
                    ->label('Annual')
                    ->getStateUsing(function ($record) {
                        $remaining = $record->getRemainingLeaves();
                        return $remaining['annual'] . ' / 14';
                    })
                    ->badge()
                    ->color(fn ($record) => $record->getRemainingLeaves()['annual'] > 0 ? 'success' : 'danger')
                    ->sortable(false)
                    ->toggleable(),
                
                Tables\Columns\TextColumn::make('leave_balance_total')
                    ->label('Total Remaining')
                    ->getStateUsing(function ($record) {
                        $remaining = $record->getRemainingLeaves();
                        return $remaining['total'] . ' / 28';
                    })
                    ->badge()
                    ->color(fn ($record) => $record->getRemainingLeaves()['total'] > 0 ? 'success' : 'danger')
                    ->weight('medium')
                    ->sortable(false),
                    
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
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        'admin' => 'Admin',
                        'hr' => 'HR',
                        'marketing' => 'Marketing',
                        'sales' => 'Sales',
                        'operation' => 'Operation',
                        'account' => 'Account',
                        'call_center' => 'Call Center',
                    ])
                    ->label('Role'),
                Tables\Filters\TernaryFilter::make('email_verified_at')
                    ->label('Email Verification')
                    ->trueLabel('Verified')
                    ->falseLabel('Unverified')
                    ->nullable(),
                Tables\Filters\Filter::make('has_permissions')
                    ->label('Has Permissions')
                    ->query(fn ($query) => $query->whereHas('permissions')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->button()
                    ->size('sm'),
                Tables\Actions\EditAction::make()
                    ->button()
                    ->size('sm')
                    ->color('gray')
                    ->authorize(fn ($record) => auth()->user() && (
                        auth()->user()->hasPermission('users.edit') || 
                        auth()->user()->isHR() || 
                        auth()->user()->isAdmin()
                    ) && auth()->user()->id !== $record->id),
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
            RelationManagers\LeavesRelationManager::class,
            RelationManagers\UserPermissionsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
