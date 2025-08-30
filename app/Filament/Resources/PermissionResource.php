<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PermissionResource\Pages;
use App\Models\Permission;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PermissionResource extends Resource
{
    protected static ?string $model = Permission::class;
    protected static ?string $navigationIcon = 'heroicon-o-key';
    protected static ?string $navigationGroup = 'HR Management';
    protected static ?string $navigationLabel = 'Permissions';
    protected static ?string $label = 'Permission';
    protected static ?string $pluralLabel = 'Permissions';

    public static function canViewAny(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        
        // Admin users can view all resources
        if ($user->isAdmin()) return true;
        
        return $user->hasPermission('permissions.view');
    }

    public static function canCreate(): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        
        // Admin users can create all resources
        if ($user->isAdmin()) return true;
        
        return $user->hasPermission('permissions.create');
    }

    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        
        // Admin users can edit all resources
        if ($user->isAdmin()) return true;
        
        return $user->hasPermission('permissions.edit');
    }

    public static function canDelete(Model $record): bool
    {
        $user = auth()->user();
        if (!$user) return false;
        
        // Admin users can delete all resources
        if ($user->isAdmin()) return true;
        
        return $user->hasPermission('permissions.delete');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Permission Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('Unique identifier for the permission (e.g., leads.view)'),
                        Forms\Components\TextInput::make('display_name')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Human-readable name for the permission'),
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->helperText('Brief description of what this permission allows'),
                        Forms\Components\TextInput::make('resource')
                            ->required()
                            ->maxLength(255)
                            ->helperText('The resource this permission applies to (e.g., leads, users)'),
                        Forms\Components\TextInput::make('action')
                            ->required()
                            ->maxLength(255)
                            ->helperText('The action this permission allows (e.g., view, create, edit, delete)'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('display_name')
                    ->label('Permission Name')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
                Tables\Columns\TextColumn::make('name')
                    ->label('Permission Key')
                    ->searchable()
                    ->copyable()
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Small)
                    ->color('gray'),
                Tables\Columns\TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->wrap()
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 50 ? $state : null;
                    }),
                Tables\Columns\BadgeColumn::make('resource')
                    ->label('Resource')
                    ->colors([
                        'primary' => 'leads',
                        'success' => 'customers',
                        'warning' => 'invoices',
                        'danger' => 'vendor_bills',
                        'info' => 'users',
                        'secondary' => 'leaves',
                    ]),
                Tables\Columns\BadgeColumn::make('action')
                    ->label('Action')
                    ->colors([
                        'primary' => 'view',
                        'success' => 'create',
                        'warning' => 'edit',
                        'danger' => 'delete',
                        'info' => 'approve',
                    ]),
                Tables\Columns\TextColumn::make('users_count')
                    ->label('Users')
                    ->counts('users')
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Small)
                    ->color('gray'),
            ])
            ->defaultSort('resource', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('resource')
                    ->options(Permission::distinct()->pluck('resource', 'resource')->toArray())
                    ->label('Resource'),
                Tables\Filters\SelectFilter::make('action')
                    ->options(Permission::distinct()->pluck('action', 'action')->toArray())
                    ->label('Action'),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPermissions::route('/'),
            'create' => Pages\CreatePermission::route('/create'),
            'view' => Pages\ViewPermission::route('/{record}'),
            'edit' => Pages\EditPermission::route('/{record}/edit'),
        ];
    }
} 