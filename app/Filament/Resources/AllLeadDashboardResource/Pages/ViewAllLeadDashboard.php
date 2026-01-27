<?php

namespace App\Filament\Resources\AllLeadDashboardResource\Pages;

use App\Filament\Resources\AllLeadDashboardResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;

class ViewAllLeadDashboard extends ViewRecord
{
    protected static string $resource = AllLeadDashboardResource::class;

    protected function resolveRecord($key): \Illuminate\Database\Eloquent\Model
    {
        $query = static::getResource()::getEloquentQuery();
        return $query->with(['actionLogs.user', 'notes.user'])->findOrFail($key);
    }

    protected function getHeaderActions(): array
    {
        $user = auth()->user();
        $isAssignedToMe = $user && $user->isOperation() && $this->record->assigned_operator === $user->id;
        $isAdmin = $user && $user->isAdmin();
        
        return [
            \Filament\Actions\EditAction::make()
                ->visible(fn() => $isAdmin || $isAssignedToMe),
            
            \Filament\Actions\Action::make('assign_to_me')
                ->label('Assign to Me')
                ->icon('heroicon-o-user-plus')
                ->color('success')
                ->button()
                ->visible(fn() => $user && $user->isOperation() && !$isAssignedToMe)
                ->action(function () {
                    $user = auth()->user();
                    $this->record->status = \App\Enums\LeadStatus::ASSIGNED_TO_OPERATIONS->value;
                    $this->record->assigned_operator = $user ? $user->id : null;
                    $this->record->save();
                    Notification::make()
                        ->success()
                        ->title('Lead assigned to you.')
                        ->send();
                }),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        $user = auth()->user();
        $isAssignedToMe = $user && $user->isOperation() && $this->record->assigned_operator === $user->id;
        $isAdmin = $user && $user->isAdmin();
        $showFullDetails = $isAdmin || $isAssignedToMe;

        // For operation users who haven't assigned the lead to themselves, show limited info
        if (!$showFullDetails && $user && $user->isOperation()) {
            return $infolist
                ->schema([
                    Components\Section::make('Lead Information')
                        ->schema([
                            Components\Grid::make(4)
                                ->schema([
                                    Components\TextEntry::make('id')
                                        ->label('Lead ID')
                                        ->badge()
                                        ->color('primary')
                                        ->weight('bold'),
                                    Components\TextEntry::make('customer_name')
                                        ->label('Customer')
                                        ->size(Components\TextEntry\TextEntrySize::Large)
                                        ->weight('bold'),
                                    Components\TextEntry::make('status')
                                        ->label('Status')
                                        ->badge()
                                        ->color(fn (string $state): string => match ($state) {
                                            'new' => 'gray',
                                            'assigned_to_sales' => 'info',
                                            'assigned_to_operations' => 'warning',
                                            'info_gather_complete' => 'success',
                                            'rate_requested' => 'warning',
                                            'amendment' => 'warning',
                                            'pricing_in_progress' => 'primary',
                                            'sent_to_customer' => 'accent',
                                            'confirmed' => 'brand',
                                            'mark_closed' => 'danger',
                                            'operation_complete' => 'success',
                                            default => 'gray',
                                        })
                                        ->formatStateUsing(fn ($state) => \App\Enums\LeadStatus::tryFrom($state)?->label() ?? $state),
                                    Components\TextEntry::make('priority')
                                        ->label('Priority')
                                        ->badge()
                                        ->color(fn (?string $state): string => match ($state) {
                                            'low' => 'gray',
                                            'medium' => 'warning',
                                            'high' => 'danger',
                                            default => 'gray',
                                        })
                                        ->formatStateUsing(fn ($state) => ucfirst($state ?? 'medium')),
                                ]),
                            Components\TextEntry::make('assign_prompt')
                                ->label('')
                                ->getStateUsing(function () {
                                    return 'Assign this lead to yourself to view full details.';
                                })
                                ->columnSpanFull()
                                ->color('warning')
                                ->icon('heroicon-o-information-circle'),
                        ])
                        ->columns(1),
                ])
                ->columns(1);
        }

        // Full details for admin or assigned operation users
        return $infolist
            ->schema([
                // Header section with key info
                Components\Section::make('Lead Overview')
                    ->schema([
                        Components\Grid::make(4)
                            ->schema([
                                Components\TextEntry::make('id')
                                    ->label('Lead ID')
                                    ->badge()
                                    ->color('primary')
                                    ->weight('bold'),
                                Components\TextEntry::make('reference_id')
                                    ->label('Reference ID')
                                    ->badge()
                                    ->color('gray'),
                                Components\TextEntry::make('status')
                                    ->label('Status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'new' => 'gray',
                                        'assigned_to_sales' => 'info',
                                        'assigned_to_operations' => 'warning',
                                        'info_gather_complete' => 'success',
                                        'rate_requested' => 'warning',
                                        'amendment' => 'warning',
                                        'pricing_in_progress' => 'primary',
                                        'sent_to_customer' => 'accent',
                                        'confirmed' => 'brand',
                                        'mark_closed' => 'danger',
                                        'operation_complete' => 'success',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn ($state) => \App\Enums\LeadStatus::tryFrom($state)?->label() ?? $state),
                                Components\TextEntry::make('priority')
                                    ->label('Priority')
                                    ->badge()
                                    ->color(fn (?string $state): string => match ($state) {
                                        'low' => 'gray',
                                        'medium' => 'warning',
                                        'high' => 'danger',
                                        default => 'gray',
                                    })
                                    ->formatStateUsing(fn ($state) => ucfirst($state ?? 'medium')),
                            ]),
                    ])
                    ->columns(1),

                // Customer Information
                Components\Section::make('Customer Information')
                    ->schema([
                        Components\Grid::make(2)
                            ->schema([
                                Components\TextEntry::make('customer_name')
                                    ->label('Customer Name')
                                    ->size(Components\TextEntry\TextEntrySize::Large)
                                    ->weight('bold'),
                                Components\TextEntry::make('platform')
                                    ->label('Source Platform')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'facebook' => 'info',
                                        'whatsapp' => 'success',
                                        'email' => 'warning',
                                        default => 'gray',
                                    }),
                            ]),
                        Components\Grid::make(2)
                            ->schema([
                                Components\TextEntry::make('contact_method')
                                    ->label('Contact Method')
                                    ->formatStateUsing(fn ($state) => ucfirst($state ?? 'Not specified')),
                                Components\TextEntry::make('contact_value')
                                    ->label('Contact Value')
                                    ->placeholder('Not provided')
                                    ->copyable(),
                            ]),
                        Components\TextEntry::make('message')
                            ->label('Customer Message')
                            ->placeholder('No message provided')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),

                // Travel Details
                Components\Section::make('Travel Information')
                    ->schema([
                        Components\Grid::make(3)
                            ->schema([
                                Components\TextEntry::make('destination')
                                    ->label('Destination')
                                    ->placeholder('Not specified'),
                                Components\TextEntry::make('country')
                                    ->label('Country')
                                    ->placeholder('Not specified'),
                                Components\TextEntry::make('subject')
                                    ->label('Trip Subject')
                                    ->placeholder('Not specified'),
                            ]),
                        Components\Grid::make(3)
                            ->schema([
                                Components\TextEntry::make('arrival_date')
                                    ->label('Arrival Date')
                                    ->date('M j, Y')
                                    ->placeholder('Not set'),
                                Components\TextEntry::make('depature_date')
                                    ->label('Departure Date')
                                    ->date('M j, Y')
                                    ->placeholder('Not set'),
                                Components\TextEntry::make('number_of_days')
                                    ->label('Duration')
                                    ->suffix(' days')
                                    ->placeholder('Not specified'),
                            ]),
                        Components\Grid::make(3)
                            ->schema([
                                Components\TextEntry::make('number_of_adults')
                                    ->label('Adults')
                                    ->placeholder('0'),
                                Components\TextEntry::make('number_of_children')
                                    ->label('Children')
                                    ->placeholder('0'),
                                Components\TextEntry::make('number_of_infants')
                                    ->label('Infants')
                                    ->placeholder('0'),
                            ]),
                        Components\TextEntry::make('tour')
                            ->label('Tour Requirements')
                            ->placeholder('No requirements specified')
                            ->columnSpanFull(),
                        Components\TextEntry::make('tour_details')
                            ->label('Detailed Tour Information')
                            ->placeholder('No details provided')
                            ->columnSpanFull(),
                    ])
                    ->columns(3),

                // Assignment Information
                Components\Section::make('Assignment & Team')
                    ->schema([
                        Components\Grid::make(2)
                            ->schema([
                                Components\TextEntry::make('assignedUser.name')
                                    ->label('Assigned Sales Rep')
                                    ->placeholder('Unassigned')
                                    ->badge()
                                    ->color('info'),
                                Components\TextEntry::make('assignedOperator.name')
                                    ->label('Assigned Operator')
                                    ->placeholder('Unassigned')
                                    ->badge()
                                    ->color('success'),
                            ]),
                        Components\Grid::make(2)
                            ->schema([
                                Components\TextEntry::make('creator.name')
                                    ->label('Created By')
                                    ->placeholder('Unknown'),
                                Components\TextEntry::make('customer.name')
                                    ->label('Linked Customer')
                                    ->placeholder('No customer link'),
                            ]),
                    ])
                    ->columns(2)
                    ->collapsed(),

                // System Information
                Components\Section::make('System Information')
                    ->schema([
                        Components\Grid::make(2)
                            ->schema([
                                Components\TextEntry::make('created_at')
                                    ->label('Created At')
                                    ->dateTime('M j, Y \a\t g:i A'),
                                Components\TextEntry::make('updated_at')
                                    ->label('Last Updated')
                                    ->dateTime('M j, Y \a\t g:i A'),
                            ]),
                    ])
                    ->columns(2)
                    ->collapsed(),

                // Action Log (Admin Only)
                Components\Section::make('Action Log')
                    ->schema([
                        Components\TextEntry::make('action_logs_table')
                            ->label('')
                            ->getStateUsing(function ($record) {
                                $logs = $record->actionLogs;
                                if ($logs->isEmpty()) {
                                    return new \Illuminate\Support\HtmlString('<p class="text-gray-500 dark:text-gray-400 text-sm">No actions logged yet.</p>');
                                }
                                
                                $html = '<div class="fi-ta-content overflow-x-auto rounded-lg bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">';
                                $html .= '<table class="fi-ta-table w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5">';
                                $html .= '<thead class="divide-y divide-gray-200 dark:divide-white/5">';
                                $html .= '<tr class="bg-gray-50 dark:bg-white/5">';
                                $html .= '<th class="px-3 py-3.5 pe-3 text-start"><span class="text-xs font-semibold text-gray-950 dark:text-white">Action</span></th>';
                                $html .= '<th class="px-3 py-3.5 pe-3 text-start"><span class="text-xs font-semibold text-gray-950 dark:text-white">Performed By</span></th>';
                                $html .= '<th class="px-3 py-3.5 pe-3 text-start"><span class="text-xs font-semibold text-gray-950 dark:text-white">Description</span></th>';
                                $html .= '<th class="px-3 py-3.5 pe-3 text-start"><span class="text-xs font-semibold text-gray-950 dark:text-white">When</span></th>';
                                $html .= '</tr></thead>';
                                $html .= '<tbody class="divide-y divide-gray-200 whitespace-nowrap dark:divide-white/5">';
                                
                                foreach ($logs as $log) {
                                    $actionBadgeColor = match($log->action) {
                                        'created' => 'bg-success-50 text-success-700 ring-success-600/20 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/20',
                                        'status_changed' => 'bg-warning-50 text-warning-700 ring-warning-600/20 dark:bg-warning-400/10 dark:text-warning-400 dark:ring-warning-400/20',
                                        'assigned' => 'bg-info-50 text-info-700 ring-info-600/20 dark:bg-info-400/10 dark:text-info-400 dark:ring-info-400/20',
                                        'operator_assigned' => 'bg-primary-50 text-primary-700 ring-primary-600/20 dark:bg-primary-400/10 dark:text-primary-400 dark:ring-primary-400/20',
                                        'archived' => 'bg-gray-50 text-gray-700 ring-gray-600/20 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/20',
                                        'unarchived' => 'bg-success-50 text-success-700 ring-success-600/20 dark:bg-success-400/10 dark:text-success-400 dark:ring-success-400/20',
                                        default => 'bg-gray-50 text-gray-700 ring-gray-600/20 dark:bg-gray-400/10 dark:text-gray-400 dark:ring-gray-400/20',
                                    };
                                    
                                    $actionLabel = ucfirst(str_replace('_', ' ', $log->action));
                                    $performedBy = $log->user ? $log->user->name : 'System';
                                    $when = $log->created_at->format('M j, Y \a\t g:i A');
                                    
                                    $html .= '<tr class="group transition duration-75 hover:bg-gray-50 dark:hover:bg-white/5">';
                                    $html .= '<td class="px-3 py-4 pe-3"><span class="inline-flex items-center gap-x-1.5 rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset ' . $actionBadgeColor . '">' . htmlspecialchars($actionLabel) . '</span></td>';
                                    $html .= '<td class="px-3 py-4 pe-3 text-sm text-gray-950 dark:text-white">' . htmlspecialchars($performedBy) . '</td>';
                                    $html .= '<td class="px-3 py-4 pe-3 text-sm text-gray-950 dark:text-white">' . htmlspecialchars($log->description) . '</td>';
                                    $html .= '<td class="px-3 py-4 pe-3 text-sm text-gray-500 dark:text-gray-400">' . htmlspecialchars($when) . '</td>';
                                    $html .= '</tr>';
                                }
                                
                                $html .= '</tbody></table></div>';
                                return new \Illuminate\Support\HtmlString($html);
                            })
                            ->columnSpanFull(),
                    ])
                    ->visible(fn() => auth()->user()?->isAdmin())
                    ->collapsed(),
            ])
            ->columns(1);
    }
} 