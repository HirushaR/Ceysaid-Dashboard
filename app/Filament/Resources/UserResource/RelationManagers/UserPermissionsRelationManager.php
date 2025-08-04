<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use App\Models\Permission;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;

class UserPermissionsRelationManager extends RelationManager
{
    protected static string $relationship = 'permissions';
    protected static ?string $title = 'User Permissions';
    protected static ?string $recordTitleAttribute = 'display_name';

    public function canCreate(): bool
    {
        $user = auth()->user();
        return $user && ($user->hasPermission('users.manage_permissions') || $user->isHR() || $user->isAdmin());
    }

    public function canAttach(): bool
    {
        $user = auth()->user();
        return $user && ($user->hasPermission('users.manage_permissions') || $user->isHR() || $user->isAdmin());
    }

    public function canDetach(Model $record): bool
    {
        $user = auth()->user();
        return $user && ($user->hasPermission('users.manage_permissions') || $user->isHR() || $user->isAdmin());
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('permission_id')
                    ->label('Permission')
                    ->options(Permission::all()->pluck('display_name', 'id'))
                    ->required()
                    ->searchable()
                    ->preload()
                    ->helperText('Select the permission to grant to this user'),
                Forms\Components\Hidden::make('granted_by')
                    ->default(auth()->id()),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('display_name')
                    ->label('Permission')
                    ->searchable()
                    ->sortable()
                    ->weight('medium'),
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
                Tables\Columns\TextColumn::make('pivot.granted_at')
                    ->label('Granted At')
                    ->dateTime('M j, Y H:i')
                    ->sortable()
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Small)
                    ->color('gray'),
                Tables\Columns\TextColumn::make('granted_by_name')
                    ->label('Granted By')
                    ->placeholder('System')
                    ->size(Tables\Columns\TextColumn\TextColumnSize::Small)
                    ->color('gray')
                    ->getStateUsing(function ($record) {
                        if ($record->pivot && $record->pivot->granted_by) {
                            $grantedBy = User::find($record->pivot->granted_by);
                            return $grantedBy ? $grantedBy->name : 'Unknown';
                        }
                        return 'System';
                    }),
            ])
            ->defaultSort('display_name', 'asc')
            ->filters([
                Tables\Filters\SelectFilter::make('resource')
                    ->options(Permission::distinct()->pluck('resource', 'resource')->toArray())
                    ->label('Resource'),
                Tables\Filters\SelectFilter::make('action')
                    ->options(Permission::distinct()->pluck('action', 'action')->toArray())
                    ->label('Action'),
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->form([
                        Forms\Components\Select::make('recordId')
                            ->label('Permission')
                            ->options(Permission::all()->pluck('display_name', 'id'))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->helperText('Select the permission to grant to this user'),
                        Forms\Components\Hidden::make('granted_by')
                            ->default(auth()->id()),
                    ])
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['granted_at'] = now();
                        return $data;
                    })
                    ->action(function (array $data, $record) {
                        try {
                            $user = $this->getOwnerRecord();
                            $permission = Permission::find($data['recordId']);
                            
                            if (!$permission) {
                                Notification::make()
                                    ->danger()
                                    ->title('Permission not found')
                                    ->send();
                                return;
                            }
                            
                            $user->permissions()->attach($permission->id, [
                                'granted_by' => $data['granted_by'] ?? auth()->id(),
                                'granted_at' => $data['granted_at'] ?? now(),
                            ]);
                            
                            Notification::make()
                                ->success()
                                ->title('Permission granted successfully')
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('Error granting permission')
                                ->body($e->getMessage())
                                ->send();
                        }
                    })
                    ->label('Grant Permission'),
            ])
            ->actions([
                Tables\Actions\DetachAction::make()
                    ->label('Revoke Permission')
                    ->color('danger')
                    ->action(function ($record) {
                        try {
                            $user = $this->getOwnerRecord();
                            $user->permissions()->detach($record->id);
                            
                            Notification::make()
                                ->success()
                                ->title('Permission revoked successfully')
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->danger()
                                ->title('Error revoking permission')
                                ->body($e->getMessage())
                                ->send();
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make()
                        ->label('Revoke Selected Permissions'),
                ]),
            ])
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }
} 