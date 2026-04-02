<?php

namespace App\Filament\Resources;

use App\Enums\QuoteStatus;
use App\Filament\Resources\QuoteResource\Pages;
use App\Models\Lead;
use App\Models\Quote;
use App\Traits\HasResourcePermissions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class QuoteResource extends Resource
{
    use HasResourcePermissions;

    protected static ?string $model = Quote::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-duplicate';

    protected static ?string $navigationGroup = 'Finance';

    protected static ?string $label = 'Quote';

    protected static ?string $pluralLabel = 'Quotes';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Quote')
                    ->schema([
                        Forms\Components\Select::make('lead_id')
                            ->label('Lead')
                            ->relationship('lead', 'reference_id')
                            ->getOptionLabelFromRecordUsing(fn (Lead $record): string => "{$record->reference_id} — {$record->customer_name}")
                            ->searchable(['reference_id', 'customer_name'])
                            ->required()
                            ->disabledOn('edit'),
                        Forms\Components\TextInput::make('quote_number')
                            ->label('Quote number')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->disabledOn('edit')
                            ->dehydrated(),
                        Forms\Components\Hidden::make('status')
                            ->default(QuoteStatus::Draft->value)
                            ->dehydrated(),
                        Forms\Components\DatePicker::make('quote_date')->default(now()),
                        Forms\Components\DatePicker::make('valid_until'),
                        Forms\Components\TextInput::make('terms')->maxLength(255),
                        Forms\Components\TextInput::make('subject')->maxLength(255),
                        Forms\Components\Textarea::make('notes')->rows(3)->columnSpanFull(),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Line items')
                    ->schema([
                        Forms\Components\Repeater::make('lineItems')
                            ->relationship()
                            ->schema([
                                Forms\Components\Textarea::make('description')
                                    ->required()
                                    ->rows(2)
                                    ->columnSpanFull(),
                                Forms\Components\TextInput::make('quantity')->numeric()->default(1)->required(),
                                Forms\Components\TextInput::make('rate')
                                    ->label('Rate (LKR)')
                                    ->numeric()
                                    ->prefix('LKR')
                                    ->required(),
                            ])
                            ->defaultItems(1)
                            ->reorderable()
                            ->orderColumn('sort_order')
                            ->columns(2),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('quote_number')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('lead.reference_id')
                    ->label('Lead')
                    ->sortable(),
                Tables\Columns\TextColumn::make('lead.customer_name')
                    ->label('Customer')
                    ->limit(30),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(function ($state) {
                        $s = $state instanceof QuoteStatus ? $state : QuoteStatus::tryFrom((string) $state);

                        return $s === QuoteStatus::Converted ? 'Converted' : 'Draft';
                    }),
                Tables\Columns\TextColumn::make('quote_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->since(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        QuoteStatus::Draft->value => 'Draft',
                        QuoteStatus::Converted->value => 'Converted',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (Quote $record) => $record->status === QuoteStatus::Draft),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (Quote $record) => $record->status === QuoteStatus::Draft),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuotes::route('/'),
            'create' => Pages\CreateQuote::route('/create'),
            'view' => Pages\ViewQuote::route('/{record}'),
            'edit' => Pages\EditQuote::route('/{record}/edit'),
        ];
    }
}
