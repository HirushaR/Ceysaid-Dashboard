<?php

namespace App\Filament\Resources\MySalesDashboardResource\Pages;

use App\Filament\Resources\MySalesDashboardResource;
use Filament\Resources\Pages\ViewRecord;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action as NotificationAction;
use Filament\Notifications\Events\DatabaseNotificationsSent;
use App\Notifications\LeadDatabaseNotification;
use Filament\Infolists\Infolist;
use Filament\Infolists\Components;
use Filament\Forms;
use App\Models\LeadNote;
use App\Models\User;

class ViewMySalesDashboard extends ViewRecord
{
    protected static string $resource = MySalesDashboardResource::class;

    protected function resolveRecord($key): \Illuminate\Database\Eloquent\Model
    {
        $query = static::getResource()::getEloquentQuery();
        return $query->with(['actionLogs.user', 'notes.user'])->findOrFail($key);
    }
    
    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // Header section with key info
                Components\Section::make('Lead Overview')
                    ->schema([
                        Components\Grid::make(3)
                            ->schema([
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
                                        'pricing_in_progress' => 'primary',
                                        'sent_to_customer' => 'accent',
                                        'confirmed' => 'brand',
                                        'mark_closed' => 'danger',
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

                // Internal Notes Section
                Components\Section::make('Internal Notes')
                    ->schema([
                        Components\TextEntry::make('notes_table')
                            ->label('')
                            ->getStateUsing(function ($record) {
                                $notes = $record->notes;
                                if ($notes->isEmpty()) {
                                    return new \Illuminate\Support\HtmlString('<p class="text-gray-500 dark:text-gray-400 text-sm">No internal notes yet.</p>');
                                }
                                
                                $html = '<div class="fi-ta-content overflow-x-auto rounded-lg bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">';
                                $html .= '<table class="fi-ta-table w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5">';
                                $html .= '<thead class="divide-y divide-gray-200 dark:divide-white/5">';
                                $html .= '<tr class="bg-gray-50 dark:bg-white/5">';
                                $html .= '<th class="px-3 py-3.5 pe-3 text-start"><span class="text-xs font-semibold text-gray-950 dark:text-white">Note</span></th>';
                                $html .= '<th class="px-3 py-3.5 pe-3 text-start"><span class="text-xs font-semibold text-gray-950 dark:text-white">Added By</span></th>';
                                $html .= '<th class="px-3 py-3.5 pe-3 text-start"><span class="text-xs font-semibold text-gray-950 dark:text-white">When</span></th>';
                                $html .= '</tr></thead>';
                                $html .= '<tbody class="divide-y divide-gray-200 dark:divide-white/5">';
                                
                                foreach ($notes as $note) {
                                    $addedBy = $note->user ? $note->user->name : 'Unknown';
                                    $when = $note->created_at->format('M j, Y \a\t g:i A');
                                    $noteText = nl2br(htmlspecialchars($note->note));
                                    
                                    $html .= '<tr class="group transition duration-75 hover:bg-gray-50 dark:hover:bg-white/5">';
                                    $html .= '<td class="px-3 py-4 pe-3 text-sm text-gray-950 dark:text-white whitespace-normal">' . $noteText . '</td>';
                                    $html .= '<td class="px-3 py-4 pe-3 whitespace-nowrap text-sm text-gray-950 dark:text-white">' . htmlspecialchars($addedBy) . '</td>';
                                    $html .= '<td class="px-3 py-4 pe-3 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">' . htmlspecialchars($when) . '</td>';
                                    $html .= '</tr>';
                                }
                                
                                $html .= '</tbody></table></div>';
                                return new \Illuminate\Support\HtmlString($html);
                            })
                            ->columnSpanFull(),
                    ])
                    ->collapsed(),

                // Action Log Section
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
                    ->collapsed(),
            ])
            ->columns(1);
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\EditAction::make()
                ->label('Edit')
                ->icon('heroicon-o-pencil')
                ->button(),

            \Filament\Actions\Action::make('add_note')
                ->label('Add Internal Note')
                ->icon('heroicon-o-document-text')
                ->color('info')
                ->button()
                ->form([
                    Forms\Components\Textarea::make('note')
                        ->label('Internal Note')
                        ->required()
                        ->rows(4)
                        ->placeholder('Add an internal note about this lead...')
                        ->helperText('This note will be visible to all users who have access to this lead.'),
                ])
                ->action(function (array $data) {
                    $user = auth()->user();
                    $note = $this->record->notes()->create([
                        'user_id' => $user->id,
                        'note' => $data['note'],
                    ]);

                    // Send notifications to all users working on this lead
                    $this->sendNoteNotifications($this->record, $note, $user);

                    Notification::make()
                        ->success()
                        ->title('Internal note added successfully.')
                        ->send();
                }),

            \Filament\Actions\Action::make('info_gather_complete')
                ->label('Info Gather Complete')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->action(function () {
                    $this->record->status = \App\Enums\LeadStatus::INFO_GATHER_COMPLETE->value;
                    $this->record->save();
                    \Filament\Notifications\Notification::make()
                        ->success()
                        ->title('Lead marked as Info Gather Complete.')
                        ->send();
                })
                ->visible(fn ($record) => $record->status === \App\Enums\LeadStatus::ASSIGNED_TO_SALES->value),
            \Filament\Actions\Action::make('sent_to_customer')
                ->label('Sent to Customer')
                ->color('success')
                ->icon('heroicon-o-paper-airplane')
                ->requiresConfirmation()
                ->modalHeading('Are you sure?')
                ->modalDescription('Confirm that all steps are done and this lead will be marked as sent to customer.')
                ->action(function () {
                    $this->record->status = \App\Enums\LeadStatus::SENT_TO_CUSTOMER->value;
                    $this->record->save();
                    \Filament\Notifications\Notification::make()
                        ->success()
                        ->title('Lead marked as Sent to Customer.')
                        ->send();
                })
                ->visible(fn ($record) => $record->status === \App\Enums\LeadStatus::OPERATION_COMPLETE->value),
            \Filament\Actions\Action::make('confirm_lead')
                ->label('Confirm Lead')
                ->color('info')
                ->icon('heroicon-o-check')
                ->requiresConfirmation()
                ->modalHeading('Confirm this lead?')
                ->modalDescription('This will mark the lead as confirmed.')
                ->action(function () {
                    $this->record->status = \App\Enums\LeadStatus::CONFIRMED->value;
                    $this->record->save();
                    \Filament\Notifications\Notification::make()
                        ->success()
                        ->title('Lead confirmed successfully.')
                        ->send();
                })
                ->visible(fn ($record) => $record->status === \App\Enums\LeadStatus::SENT_TO_CUSTOMER->value),
        ];
    }

    /**
     * Send notifications to all users working on the lead when a note is added
     */
    private function sendNoteNotifications(\App\Models\Lead $lead, LeadNote $note, User $addedBy): void
    {
        $recipients = $this->getNotificationRecipients($lead);
        
        // Don't notify the user who added the note
        $recipients = $recipients->reject(fn($user) => $user->id === $addedBy->id);

        $refId = $lead->reference_id ?: "ID: {$lead->id}";
        $notePreview = \Str::limit($note->note, 100);

        foreach ($recipients as $recipient) {
            // Get the correct URL based on recipient's role
            $leadUrl = $this->getLeadUrlForUser($recipient, $lead);
            
            $notification = Notification::make()
                ->title('New Internal Note Added')
                ->body("{$addedBy->name} added a note to lead {$refId} ({$lead->customer_name}): {$notePreview}")
                ->info()
                ->icon('heroicon-o-document-text')
                ->actions([
                    NotificationAction::make('view')
                        ->label('View Lead')
                        ->button()
                        ->url($leadUrl),
                ]);

            $recipient->notify(new LeadDatabaseNotification($notification, $lead->id));
            event(new DatabaseNotificationsSent($recipient));
        }
    }

    /**
     * Get notification recipients - all users working on the lead
     */
    private function getNotificationRecipients(\App\Models\Lead $lead): \Illuminate\Support\Collection
    {
        $recipients = collect();

        // Always notify assigned users
        if ($lead->assigned_to && $lead->assignedUser) {
            $recipients->push($lead->assignedUser);
        }

        if ($lead->assigned_operator && $lead->assignedOperator) {
            $recipients->push($lead->assignedOperator);
        }

        // Notify creator if different from assignees
        if ($lead->created_by && $lead->creator) {
            $isCreatorAlreadyIncluded = $recipients->contains('id', $lead->created_by);
            if (!$isCreatorAlreadyIncluded) {
                $recipients->push($lead->creator);
            }
        }

        // Notify managers
        if ($lead->assignedUser) {
            $manager = $this->getManager($lead->assignedUser);
            if ($manager && !$recipients->contains('id', $manager->id)) {
                $recipients->push($manager);
            }
        }

        if ($lead->assignedOperator) {
            $manager = $this->getManager($lead->assignedOperator);
            if ($manager && !$recipients->contains('id', $manager->id)) {
                $recipients->push($manager);
            }
        }

        return $recipients->unique('id');
    }

    /**
     * Get manager for a user (users with same role and is_manager = true)
     */
    private function getManager(User $user): ?User
    {
        return User::where('role', $user->role)
            ->where('is_manager', true)
            ->where('id', '!=', $user->id)
            ->first();
    }

    /**
     * Get the correct lead URL based on user role
     */
    private function getLeadUrlForUser(User $user, \App\Models\Lead $lead): string
    {
        if ($user->isSales()) {
            return MySalesDashboardResource::getUrl('view', ['record' => $lead]);
        } elseif ($user->isOperation()) {
            return \App\Filament\Resources\MyOperationLeadDashboardResource::getUrl('view', ['record' => $lead]);
        }

        // Default to main LeadResource for admin and other roles
        return \App\Filament\Resources\LeadResource::getUrl('view', ['record' => $lead]);
    }
} 