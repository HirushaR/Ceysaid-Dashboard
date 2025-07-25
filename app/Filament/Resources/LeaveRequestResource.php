<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeaveRequestResource\Pages;
use App\Filament\Resources\LeaveRequestResource\RelationManagers;
use App\Models\Leave;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Enums\LeaveType;
use App\Enums\LeaveStatus;

class LeaveRequestResource extends Resource
{
    protected static ?string $model = Leave::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar';

    protected static ?string $navigationGroup = 'Employee Services';

    protected static ?string $navigationLabel = 'My Leave Requests';

    protected static ?string $label = 'Leave Request';

    protected static ?string $pluralLabel = 'My Leave Requests';

    protected static ?int $navigationSort = 1;

    public static function canViewAny(): bool
    {
        return auth()->check();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('📝 Submit Leave Request')
                    ->description('Fill out the form below to request time off. Your request will be reviewed by HR.')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Type of Leave')
                            ->options(LeaveType::getOptions())
                            ->required()
                            ->columnSpanFull()
                            ->helperText('Select the type of leave you are requesting'),
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Start Date')
                            ->required()
                            ->minDate(today())
                            ->helperText('The first day of your leave'),
                        Forms\Components\DatePicker::make('end_date')
                            ->label('End Date')
                            ->required()
                            ->afterOrEqual('start_date')
                            ->minDate(today())
                            ->helperText('The last day of your leave'),
                        Forms\Components\TextInput::make('hours')
                            ->label('Hours (Optional)')
                            ->numeric()
                            ->step(0.25)
                            ->placeholder('8.00')
                            ->helperText('For partial day leaves, specify the number of hours')
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('description')
                            ->label('Reason for Leave')
                            ->required()
                            ->rows(4)
                            ->placeholder('Please provide details about your leave request (e.g., vacation, medical appointment, family emergency, etc.)')
                            ->columnSpanFull(),
                        Forms\Components\Hidden::make('user_id')
                            ->default(auth()->id()),
                        Forms\Components\Hidden::make('status')
                            ->default(LeaveStatus::PENDING->value),
                        Forms\Components\Hidden::make('created_by')
                            ->default(auth()->id()),
                    ])
                    ->columns(2),

                Forms\Components\Section::make('📋 Request Status')
                    ->description('Current status of your leave request')
                    ->schema([
                        Forms\Components\Placeholder::make('status')
                            ->label('Current Status')
                            ->content(fn (?Leave $record) => $record?->status?->getLabel() ?? 'New Request'),
                        Forms\Components\Placeholder::make('approver.name')
                            ->label('Reviewed By')
                            ->content(fn (?Leave $record) => $record?->approver?->name ?? 'Awaiting Review'),
                        Forms\Components\Placeholder::make('approved_at')
                            ->label('Review Date')
                            ->content(fn (?Leave $record) => $record?->approved_at?->format('M d, Y H:i') ?? 'N/A'),
                        Forms\Components\Placeholder::make('rejection_reason')
                            ->label('Rejection Reason')
                            ->content(fn (?Leave $record) => $record?->rejection_reason ?? 'N/A')
                            ->visible(fn (?Leave $record) => $record?->isRejected() ?? false),
                    ])
                    ->columns(2)
                    ->hidden(fn (string $context) => $context === 'create'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state->getLabel()),
                Tables\Columns\TextColumn::make('description')
                    ->limit(40)
                    ->wrap()
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();
                        if (strlen($state) <= 40) {
                            return null;
                        }
                        return $state;
                    }),
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
                    ->default('Pending'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Requested')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(LeaveStatus::getOptions()),
                Tables\Filters\SelectFilter::make('type')
                    ->options(LeaveType::getOptions()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (Leave $record) => $record->isPending()),
                Tables\Actions\Action::make('cancel')
                    ->icon('heroicon-m-x-mark')
                    ->color('gray')
                    ->visible(fn (Leave $record) => $record->isPending())
                    ->requiresConfirmation()
                    ->modalDescription('Are you sure you want to cancel this leave request?')
                    ->action(function (Leave $record) {
                        $record->update([
                            'status' => LeaveStatus::CANCELLED,
                        ]);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn () => auth()->user()->isAdmin()),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No Leave Requests Found')
            ->emptyStateDescription('You haven\'t submitted any leave requests yet.')
            ->emptyStateIcon('heroicon-o-calendar');
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
            'index' => Pages\ListLeaveRequests::route('/'),
            'create' => Pages\CreateLeaveRequest::route('/create'),
            'view' => Pages\ViewLeaveRequest::route('/{record}'),
            'edit' => Pages\EditLeaveRequest::route('/{record}/edit'),
        ];
    }
} 