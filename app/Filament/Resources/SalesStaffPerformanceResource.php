<?php

namespace App\Filament\Resources;

use App\Models\User;
use App\Models\Lead;
use App\Models\Invoice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SalesStaffPerformanceResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationLabel = 'Sales Performance';

    protected static ?string $modelLabel = 'Sales Staff Performance';

    protected static ?string $pluralModelLabel = 'Sales Staff Performance';

    protected static ?string $navigationGroup = 'Analytics';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Performance Filters')
                    ->schema([
                        Forms\Components\DatePicker::make('start_date')
                            ->label('Start Date')
                            ->default(now()->subDays(30))
                            ->required(),
                        
                        Forms\Components\DatePicker::make('end_date')
                            ->label('End Date')
                            ->default(now())
                            ->required(),
                        
                        Forms\Components\Select::make('sales_user')
                            ->label('Sales User')
                            ->options(User::where('role', 'sales')->pluck('name', 'id'))
                            ->searchable()
                            ->placeholder('All Sales Users'),
                    ])
                    ->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(self::getEloquentQuery())
            ->columns([
                TextColumn::make('name')
                    ->label('Sales Staff')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                
                TextColumn::make('total_leads')
                    ->label('Total Leads')
                    ->numeric()
                    ->alignCenter()
                    ->getStateUsing(function (User $record) {
                        $startDate = request('start_date', now()->subDays(30));
                        $endDate = request('end_date', now());
                        
                        return $record->leads()
                            ->whereBetween('created_at', [$startDate, $endDate])
                            ->count();
                    }),
                
                TextColumn::make('converted_leads')
                    ->label('Converted Leads')
                    ->numeric()
                    ->alignCenter()
                    ->color('success')
                    ->getStateUsing(function (User $record) {
                        $startDate = request('start_date', now()->subDays(30));
                        $endDate = request('end_date', now());
                        
                        return $record->leads()
                            ->whereBetween('created_at', [$startDate, $endDate])
                            ->whereIn('status', ['confirmed', 'operation_complete', 'document_upload_complete'])
                            ->count();
                    }),
                
                TextColumn::make('conversion_rate')
                    ->label('Conversion Rate')
                    ->formatStateUsing(fn ($state) => $state . '%')
                    ->alignCenter()
                    ->color(fn ($state) => $state >= 20 ? 'success' : ($state >= 10 ? 'warning' : 'danger'))
                    ->getStateUsing(function (User $record) {
                        $startDate = request('start_date', now()->subDays(30));
                        $endDate = request('end_date', now());
                        
                        $totalLeads = $record->leads()
                            ->whereBetween('created_at', [$startDate, $endDate])
                            ->count();
                        
                        $convertedLeads = $record->leads()
                            ->whereBetween('created_at', [$startDate, $endDate])
                            ->whereIn('status', ['confirmed', 'operation_complete', 'document_upload_complete'])
                            ->count();
                        
                        return $totalLeads > 0 ? round(($convertedLeads / $totalLeads) * 100, 1) : 0;
                    }),
                
                TextColumn::make('total_revenue')
                    ->label('Total Revenue')
                    ->formatStateUsing(fn ($state) => 'LKR ' . number_format($state, 0, ',', '.'))
                    ->alignCenter()
                    ->color('success')
                    ->getStateUsing(function (User $record) {
                        $startDate = request('start_date', now()->subDays(30));
                        $endDate = request('end_date', now());
                        
                        return Invoice::whereHas('lead', function($query) use ($record, $startDate, $endDate) {
                            $query->where('assigned_to', $record->id)
                                  ->whereBetween('created_at', [$startDate, $endDate]);
                        })->sum('total_amount');
                    }),
                
                TextColumn::make('avg_deal_size')
                    ->label('Avg Deal Size')
                    ->formatStateUsing(fn ($state) => 'LKR ' . number_format($state, 0, ',', '.'))
                    ->alignCenter()
                    ->getStateUsing(function (User $record) {
                        $startDate = request('start_date', now()->subDays(30));
                        $endDate = request('end_date', now());
                        
                        $convertedLeads = $record->leads()
                            ->whereBetween('created_at', [$startDate, $endDate])
                            ->whereIn('status', ['confirmed', 'operation_complete', 'document_upload_complete'])
                            ->count();
                        
                        $totalRevenue = Invoice::whereHas('lead', function($query) use ($record, $startDate, $endDate) {
                            $query->where('assigned_to', $record->id)
                                  ->whereBetween('created_at', [$startDate, $endDate]);
                        })->sum('total_amount');
                        
                        return $convertedLeads > 0 ? $totalRevenue / $convertedLeads : 0;
                    }),
                
                TextColumn::make('active_leads')
                    ->label('Active Leads')
                    ->numeric()
                    ->alignCenter()
                    ->color('warning')
                    ->getStateUsing(function (User $record) {
                        return $record->leads()
                            ->whereNotIn('status', ['mark_closed', 'operation_complete', 'document_upload_complete'])
                            ->count();
                    }),
                
                TextColumn::make('pending_leads')
                    ->label('Pending Leads')
                    ->numeric()
                    ->alignCenter()
                    ->color('danger')
                    ->getStateUsing(function (User $record) {
                        return $record->leads()
                            ->whereIn('status', ['new', 'assigned_to_sales', 'pricing_in_progress'])
                            ->count();
                    }),
                
                TextColumn::make('this_month_revenue')
                    ->label('This Month Revenue')
                    ->formatStateUsing(fn ($state) => 'LKR ' . number_format($state, 0, ',', '.'))
                    ->alignCenter()
                    ->color('info')
                    ->getStateUsing(function (User $record) {
                        $now = now();
                        return Invoice::whereHas('lead', function($query) use ($record) {
                            $query->where('assigned_to', $record->id);
                        })->whereYear('created_at', $now->year)
                          ->whereMonth('created_at', $now->month)
                          ->sum('total_amount');
                    }),
                
                TextColumn::make('last_activity')
                    ->label('Last Activity')
                    ->dateTime()
                    ->alignCenter()
                    ->getStateUsing(function (User $record) {
                        $lastLead = $record->leads()
                            ->orderBy('updated_at', 'desc')
                            ->first();
                        
                        return $lastLead?->updated_at;
                    }),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->options([
                        'sales' => 'Sales',
                    ])
                    ->default('sales'),
                
                Filter::make('date_range')
                    ->form([
                        DatePicker::make('start_date')
                            ->label('Start Date')
                            ->default(now()->subDays(30)),
                        
                        DatePicker::make('end_date')
                            ->label('End Date')
                            ->default(now()),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['start_date'],
                                fn (Builder $query, $date): Builder => $query->whereHas('leads', function($q) use ($date) {
                                    $q->where('created_at', '>=', $date);
                                }),
                            )
                            ->when(
                                $data['end_date'],
                                fn (Builder $query, $date): Builder => $query->whereHas('leads', function($q) use ($date) {
                                    $q->where('created_at', '<=', $date);
                                }),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('view_details')
                    ->label('View Details')
                    ->icon('heroicon-o-eye')
                    ->url(fn (User $record): string => route('filament.admin.resources.sales-staff-performances.view', $record))
                    ->openUrlInNewTab(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name', 'asc')
            ->striped();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('role', 'sales');
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
            'index' => \App\Filament\Resources\SalesStaffPerformanceResource\Pages\ListSalesStaffPerformance::route('/'),
            'view' => \App\Filament\Resources\SalesStaffPerformanceResource\Pages\ViewSalesStaffPerformance::route('/{record}'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }
}