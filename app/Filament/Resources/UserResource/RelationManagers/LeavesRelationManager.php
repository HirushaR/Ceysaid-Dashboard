<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Enums\LeaveType;
use App\Enums\LeaveStatus;

class LeavesRelationManager extends RelationManager
{
    protected static string $relationship = 'leaves';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('type')
                    ->options(LeaveType::getOptions())
                    ->required(),
                Forms\Components\Textarea::make('description')
                    ->required()
                    ->rows(3),
                Forms\Components\DatePicker::make('start_date')
                    ->required(),
                Forms\Components\DatePicker::make('end_date')
                    ->required()
                    ->afterOrEqual('start_date'),
                Forms\Components\TextInput::make('hours')
                    ->numeric()
                    ->step(0.25)
                    ->helperText('Optional: specify hours for partial day leaves'),
                Forms\Components\Select::make('status')
                    ->options(LeaveStatus::getOptions())
                    ->default(LeaveStatus::PENDING->value)
                    ->required(),
                Forms\Components\Textarea::make('rejection_reason')
                    ->visible(fn (Forms\Get $get) => $get('status') === LeaveStatus::REJECTED->value)
                    ->rows(2),
                Forms\Components\Hidden::make('created_by')
                    ->default(auth()->id()),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('type')
            ->columns([
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state->getLabel()),
                Tables\Columns\TextColumn::make('description')
                    ->limit(30)
                    ->wrap(),
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
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Requested')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(LeaveStatus::getOptions()),
                Tables\Filters\SelectFilter::make('type')
                    ->options(LeaveType::getOptions()),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['user_id'] = $this->ownerRecord->id;
                        return $data;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
