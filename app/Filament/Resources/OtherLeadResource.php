<?php

namespace App\Filament\Resources;

use App\Enums\OtherLeadStatus;
use App\Filament\Resources\OtherLeadResource\Pages;
use App\Models\Lead;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class OtherLeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Other Leads';

    protected static ?string $label = 'Other Lead';

    protected static ?string $pluralLabel = 'Other Leads';

    protected static ?string $navigationGroup = 'Dashboard';

    protected static ?int $navigationSort = 11;

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();

        return parent::getEloquentQuery()
            ->notArchived()
            ->where('is_other_lead', true)
            ->where('created_by', $user ? $user->id : 0);
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user && $user->isSales();
    }

    public static function canCreate(): bool
    {
        return static::canViewAny();
    }

    public static function canEdit(Model $record): bool
    {
        $user = auth()->user();
        if (! $user || ! $user->isSales()) {
            return false;
        }

        return (int) $record->created_by === (int) $user->id
            && $record->other_lead_status !== OtherLeadStatus::Completed;
    }

    public static function canView(Model $record): bool
    {
        $user = auth()->user();
        if (! $user || ! $user->isSales()) {
            return false;
        }

        return (int) $record->created_by === (int) $user->id;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Customer')
                    ->schema([
                        Forms\Components\TextInput::make('customer_name')
                            ->label('Customer name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('contact_method')
                            ->label('Contact method')
                            ->options([
                                'phone' => 'Phone',
                                'email' => 'Email',
                                'whatsapp' => 'WhatsApp',
                                'facebook' => 'Facebook',
                            ])
                            ->native(false),
                        Forms\Components\TextInput::make('contact_value')
                            ->label('Contact value')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('subject')
                            ->label('Title / summary')
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Other lead details')
                    ->description('Ticket, hotel, dates, and any related information')
                    ->schema([
                        Forms\Components\DatePicker::make('other_lead_start_date')
                            ->label('Start date')
                            ->native(false),
                        Forms\Components\DatePicker::make('other_lead_end_date')
                            ->label('End date')
                            ->native(false)
                            ->afterOrEqual('other_lead_start_date'),
                        Forms\Components\Textarea::make('other_lead_details')
                            ->label('Details')
                            ->rows(8)
                            ->placeholder('e.g. flights, hotels, booking references, special requests…')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference_id')
                    ->label('Reference')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer_name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('subject')
                    ->label('Summary')
                    ->limit(40)
                    ->tooltip(fn ($state) => $state),
                Tables\Columns\TextColumn::make('other_lead_status')
                    ->label('Progress')
                    ->badge()
                    ->formatStateUsing(fn ($state): string => $state instanceof OtherLeadStatus
                        ? $state->label()
                        : (OtherLeadStatus::tryFrom((string) $state)?->label() ?? ''))
                    ->color(fn ($state): string => $state instanceof OtherLeadStatus
                        ? $state->color()
                        : (OtherLeadStatus::tryFrom((string) $state)?->color() ?? 'gray')),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('M j, Y')
                    ->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('other_lead_status')
                    ->label('Progress')
                    ->options(OtherLeadStatus::options()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOtherLeads::route('/'),
            'create' => Pages\CreateOtherLead::route('/create'),
            'view' => Pages\ViewOtherLead::route('/{record}'),
            'edit' => Pages\EditOtherLead::route('/{record}/edit'),
        ];
    }
}
