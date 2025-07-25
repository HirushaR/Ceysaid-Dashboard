<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeaveResource\Pages;
use App\Filament\Resources\LeaveResource\RelationManagers;
use App\Models\Leave;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Enums\LeaveType;
use App\Enums\LeaveStatus;

class LeaveResource extends Resource
{
    protected static ?string $model = Leave::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'HR Management';

    protected static ?string $navigationLabel = 'Leave Management';

    protected static ?string $pluralLabel = 'Pending Leave Approvals';

    protected static ?string $modelLabel = 'Leave Request';

    public static function canViewAny(): bool
    {
        return auth()->user() && (auth()->user()->isHR() || auth()->user()->isAdmin());
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Leave Request Information')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->columnSpanFull(),
                        Forms\Components\Select::make('type')
                            ->options(LeaveType::getOptions())
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->options(LeaveStatus::getOptions())
                            ->default(LeaveStatus::PENDING->value)
                            ->required(),
                        Forms\Components\DatePicker::make('start_date')
                            ->required(),
                        Forms\Components\DatePicker::make('end_date')
                            ->required()
                            ->afterOrEqual('start_date'),
                        Forms\Components\TextInput::make('hours')
                            ->numeric()
                            ->step(0.25)
                            ->helperText('Optional: specify hours for partial day leaves'),
                        Forms\Components\Textarea::make('description')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('HR Actions')
                    ->schema([
                        Forms\Components\Select::make('approved_by')
                            ->relationship('approver', 'name')
                            ->searchable()
                            ->preload()
                            ->helperText('HR person who approved/rejected this leave'),
                        Forms\Components\DateTimePicker::make('approved_at')
                            ->label('Approved/Rejected At'),
                        Forms\Components\Textarea::make('rejection_reason')
                            ->visible(fn (Forms\Get $get) => $get('status') === LeaveStatus::REJECTED->value)
                            ->required(fn (Forms\Get $get) => $get('status') === LeaveStatus::REJECTED->value)
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->hidden(fn (string $context) => $context === 'create'),

                Forms\Components\Hidden::make('created_by')
                    ->default(auth()->id()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Employee')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.role')
                    ->label('Role')
                    ->badge()
                    ->colors([
                        'primary' => 'marketing',
                        'success' => 'sales',
                        'warning' => 'operation',
                        'info' => 'hr',
                        'danger' => 'admin',
                    ]),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state->getLabel()),
                Tables\Columns\TextColumn::make('start_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('duration_in_days')
                    ->label('Duration')
                    ->suffix(' days'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => LeaveStatus::PENDING->value,
                        'success' => LeaveStatus::APPROVED->value,
                        'danger' => LeaveStatus::REJECTED->value,
                        'gray' => LeaveStatus::CANCELLED->value,
                    ])
                    ->formatStateUsing(fn ($state) => $state->getLabel()),
                Tables\Columns\TextColumn::make('approver.name')
                    ->label('Approved By')
                    ->default('N/A'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Requested')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('📋 Leave Status')
                    ->options([
                        'all' => '📊 All Leaves',
                        'pending' => '⏳ Pending',
                        'approved' => '✅ Approved',
                        'rejected' => '❌ Rejected',
                        'cancelled' => '🚫 Cancelled',
                    ])
                    ->default('pending')
                    ->query(function (Builder $query, array $data): Builder {
                        if (empty($data['value']) || $data['value'] === 'all') {
                            return $query; // Show all
                        }
                        return $query->where('status', $data['value']);
                    }),
                Tables\Filters\SelectFilter::make('type')
                    ->label('📝 Leave Type')
                    ->options(LeaveType::getOptions())
                    ->placeholder('All Types'),
                Tables\Filters\SelectFilter::make('user')
                    ->label('👤 Employee')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->placeholder('All Employees'),
                Tables\Filters\Filter::make('date_range')
                    ->label('📅 Date Range')
                    ->form([
                        Forms\Components\DatePicker::make('from_date')
                            ->label('From Date'),
                        Forms\Components\DatePicker::make('to_date')
                            ->label('To Date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from_date'],
                                fn (Builder $query, $date): Builder => $query->whereDate('start_date', '>=', $date),
                            )
                            ->when(
                                $data['to_date'],
                                fn (Builder $query, $date): Builder => $query->whereDate('end_date', '<=', $date),
                            );
                    }),
            ])
            ->filtersFormColumns(2)
            ->persistFiltersInSession()
            ->modifyQueryUsing(function (Builder $query) {
                // Apply default pending filter only when no status filter is active
                $filters = request()->get('tableFilters', []);
                if (!isset($filters['status'])) {
                    return $query->where('status', LeaveStatus::PENDING);
                }
                return $query;
            })
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('approve')
                    ->icon('heroicon-m-check')
                    ->color('success')
                    ->visible(fn (Leave $record) => $record->isPending())
                    ->requiresConfirmation()
                    ->modalHeading('Approve Leave Request')
                    ->modalDescription(fn (Leave $record) => "Are you sure you want to approve the {$record->type->getLabel()} request from {$record->user->name}?")
                    ->modalSubmitActionLabel('Yes, Approve')
                    ->action(function (Leave $record) {
                        $record->update([
                            'status' => LeaveStatus::APPROVED,
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                            'rejection_reason' => null,
                        ]);
                    })
                    ->successNotification(
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Leave Request Approved')
                            ->body(fn (Leave $record) => "Successfully approved {$record->user->name}'s {$record->type->getLabel()} request.")
                    ),
                Tables\Actions\Action::make('reject')
                    ->icon('heroicon-m-x-mark')
                    ->color('danger')
                    ->visible(fn (Leave $record) => $record->isPending())
                    ->modalHeading('Reject Leave Request')
                    ->modalDescription(fn (Leave $record) => "You are about to reject the {$record->type->getLabel()} request from {$record->user->name}. Please provide a reason.")
                    ->modalSubmitActionLabel('Reject Request')
                    ->form([
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('Reason for Rejection')
                            ->required()
                            ->rows(3)
                            ->placeholder('Please provide a clear reason for rejecting this leave request...'),
                    ])
                    ->action(function (Leave $record, array $data) {
                        $record->update([
                            'status' => LeaveStatus::REJECTED,
                            'approved_by' => auth()->id(),
                            'approved_at' => now(),
                            'rejection_reason' => $data['rejection_reason'],
                        ]);
                    })
                    ->successNotification(
                        \Filament\Notifications\Notification::make()
                            ->warning()
                            ->title('Leave Request Rejected')
                            ->body(fn (Leave $record) => "Rejected {$record->user->name}'s {$record->type->getLabel()} request.")
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('bulk_approve')
                        ->label('Approve Selected')
                        ->icon('heroicon-m-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Bulk Approve Leave Requests')
                        ->modalDescription('Are you sure you want to approve all selected pending leave requests?')
                        ->modalSubmitActionLabel('Approve All')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            $approvedCount = 0;
                            foreach ($records as $record) {
                                if ($record->isPending()) {
                                    $record->update([
                                        'status' => LeaveStatus::APPROVED,
                                        'approved_by' => auth()->id(),
                                        'approved_at' => now(),
                                        'rejection_reason' => null,
                                    ]);
                                    $approvedCount++;
                                }
                            }

                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title('Bulk Approval Complete')
                                ->body("Successfully approved {$approvedCount} leave requests.")
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('bulk_reject')
                        ->label('Reject Selected')
                        ->icon('heroicon-m-x-circle')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Bulk Reject Leave Requests')
                        ->modalDescription('You are about to reject all selected pending leave requests. Please provide a reason.')
                        ->modalSubmitActionLabel('Reject All')
                        ->form([
                            Forms\Components\Textarea::make('bulk_rejection_reason')
                                ->label('Reason for Rejection')
                                ->required()
                                ->rows(3)
                                ->placeholder('Please provide a reason for rejecting these leave requests...'),
                        ])
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) {
                            $rejectedCount = 0;
                            foreach ($records as $record) {
                                if ($record->isPending()) {
                                    $record->update([
                                        'status' => LeaveStatus::REJECTED,
                                        'approved_by' => auth()->id(),
                                        'approved_at' => now(),
                                        'rejection_reason' => $data['bulk_rejection_reason'],
                                    ]);
                                    $rejectedCount++;
                                }
                            }

                            \Filament\Notifications\Notification::make()
                                ->warning()
                                ->title('Bulk Rejection Complete')
                                ->body("Rejected {$rejectedCount} leave requests.")
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListLeaves::route('/'),
            'create' => Pages\CreateLeave::route('/create'),
            'view' => Pages\ViewLeave::route('/{record}'),
            'edit' => Pages\EditLeave::route('/{record}/edit'),
        ];
    }
}
