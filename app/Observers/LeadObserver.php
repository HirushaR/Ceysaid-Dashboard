<?php

namespace App\Observers;

use App\Models\Lead;
use App\Models\User;
use App\Enums\LeadStatus;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Events\DatabaseNotificationsSent;
use App\Notifications\LeadDatabaseNotification;
use App\Filament\Resources\LeadResource;
use App\Filament\Resources\MySalesDashboardResource;
use App\Filament\Resources\AllLeadDashboardResource;
use Illuminate\Support\Facades\Log;

class LeadObserver
{
    /**
     * Handle the Lead "created" event.
     */
    public function created(Lead $lead): void
    {
        Log::info('LeadObserver::created called', [
            'lead_id' => $lead->id,
            'assigned_to' => $lead->assigned_to,
            'has_assigned_user' => $lead->assignedUser ? true : false,
        ]);

        // Note: "New Lead Assigned" notifications are handled in CreateLead::afterCreate()
        // to ensure correct URL routing to MySalesDashboardResource
        // Only notify manager here if needed
        if ($lead->assigned_to && $lead->assignedUser) {
            // Notify manager if exists
            $this->notifyManager($lead->assignedUser, $lead, 'new_lead');
        }
    }

    /**
     * Handle the Lead "updated" event.
     */
    public function updated(Lead $lead): void
    {
        Log::info('LeadObserver::updated called', [
            'lead_id' => $lead->id,
            'dirty' => $lead->getDirty(),
        ]);

        // Get original values before update
        $original = $lead->getOriginal();

        // Check for assignment changes
        if (isset($original['assigned_to']) && $original['assigned_to'] != $lead->assigned_to) {
            $this->handleAssignmentChange($lead, $original['assigned_to'], $lead->assigned_to);
        }

        // Check for operator assignment changes
        if (isset($original['assigned_operator']) && $original['assigned_operator'] != $lead->assigned_operator) {
            $this->handleOperatorAssignmentChange($lead, $original['assigned_operator'], $lead->assigned_operator);
        }

        // Check for status changes
        if (isset($original['status']) && $original['status'] != $lead->status) {
            $this->handleStatusChange($lead, $original['status'], $lead->status);
        }

        // Check for service status changes
        $serviceStatuses = ['air_ticket_status', 'hotel_status', 'visa_status', 'land_package_status'];
        foreach ($serviceStatuses as $service) {
            if (isset($original[$service]) && $original[$service] != $lead->$service) {
                $this->handleServiceStatusChange($lead, $service, $original[$service], $lead->$service);
            }
        }
    }

    /**
     * Handle assignment change (assigned_to)
     */
    private function handleAssignmentChange(Lead $lead, $oldAssignedTo, $newAssignedTo): void
    {
        // Notify new assignee
        if ($newAssignedTo && $lead->assignedUser) {
            $refId = $lead->reference_id ?: "ID: {$lead->id}";
            $this->sendNotification(
                $lead->assignedUser,
                'Lead Assigned to You',
                "Lead {$refId} ({$lead->customer_name}) has been assigned to you",
                'info',
                'heroicon-o-user-plus',
                $lead
            );

            // Notify manager
            $this->notifyManager($lead->assignedUser, $lead, 'lead_assignment');
        }

        // Notify previous assignee if changed
        if ($oldAssignedTo && $oldAssignedTo != $newAssignedTo) {
            $oldUser = \App\Models\User::find($oldAssignedTo);
            if ($oldUser) {
                $refId = $lead->reference_id ?: "ID: {$lead->id}";
                $this->sendNotification(
                    $oldUser,
                    'Lead Reassigned',
                    "Lead {$refId} ({$lead->customer_name}) has been reassigned",
                    'warning',
                    'heroicon-o-arrow-right',
                    $lead
                );
            }
        }

        // Notify creator if different from assignee
        if ($lead->created_by && $lead->created_by != $newAssignedTo && $lead->creator) {
            $refId = $lead->reference_id ?: "ID: {$lead->id}";
            $this->sendNotification(
                $lead->creator,
                'Lead Assignment Update',
                "Lead {$refId} ({$lead->customer_name}) has been assigned to " . ($lead->assignedUser ? $lead->assignedUser->name : 'someone'),
                'info',
                'heroicon-o-information-circle',
                $lead
            );
        }
    }

    /**
     * Handle operator assignment change (assigned_operator)
     */
    private function handleOperatorAssignmentChange(Lead $lead, $oldOperatorId, $newOperatorId): void
    {
        // Notify new operator
        if ($newOperatorId && $lead->assignedOperator) {
            $refId = $lead->reference_id ?: "ID: {$lead->id}";
            $this->sendNotification(
                $lead->assignedOperator,
                'Lead Assigned to You (Operator)',
                "Lead {$refId} ({$lead->customer_name}) has been assigned to you for operations",
                'warning',
                'heroicon-o-clipboard-document-check',
                $lead
            );

            // Notify manager
            $this->notifyManager($lead->assignedOperator, $lead, 'lead_assignment');
        }

        // Notify previous operator if changed
        if ($oldOperatorId && $oldOperatorId != $newOperatorId) {
            $oldOperator = \App\Models\User::find($oldOperatorId);
            if ($oldOperator) {
                $refId = $lead->reference_id ?: "ID: {$lead->id}";
                $this->sendNotification(
                    $oldOperator,
                    'Lead Reassigned',
                    "Lead {$refId} ({$lead->customer_name}) has been reassigned to another operator",
                    'warning',
                    'heroicon-o-arrow-right',
                    $lead
                );
            }
        }

        // Notify assigned sales rep
        if ($lead->assigned_to && $lead->assignedUser) {
            $refId = $lead->reference_id ?: "ID: {$lead->id}";
            $this->sendNotification(
                $lead->assignedUser,
                'Operator Assigned',
                "An operator has been assigned to lead {$refId} ({$lead->customer_name})",
                'info',
                'heroicon-o-user-group',
                $lead
            );
        }
    }

    /**
     * Handle status change
     */
    private function handleStatusChange(Lead $lead, $oldStatus, $newStatus): void
    {
        $oldStatusLabel = LeadStatus::tryFrom($oldStatus)?->label() ?? $oldStatus;
        $newStatusLabel = LeadStatus::tryFrom($newStatus)?->label() ?? $newStatus;
        $refId = $lead->reference_id ?: "ID: {$lead->id}";

        // Special handling: When status changes to "info_gather_complete", notify ALL operation users
        if ($newStatus === LeadStatus::INFO_GATHER_COMPLETE->value) {
            $operationUsers = User::where('role', 'operation')->get();
            $leadUrl = AllLeadDashboardResource::getUrl('view', ['record' => $lead]);

            foreach ($operationUsers as $operationUser) {
                $notification = Notification::make()
                    ->title('New Lead Ready for Operations')
                    ->body("Lead {$refId} ({$lead->customer_name}) is ready for operations. Status: Info Gather Complete")
                    ->success()
                    ->icon('heroicon-o-clipboard-document-check')
                    ->actions([
                        Action::make('view')
                            ->label('View Lead')
                            ->button()
                            ->url($leadUrl),
                    ]);

                $operationUser->notify(new LeadDatabaseNotification($notification, $lead->id));
                event(new DatabaseNotificationsSent($operationUser));
            }
        }

        // Regular status change notifications for assigned users
        $recipients = $this->getNotificationRecipients($lead, 'status_change');

        foreach ($recipients as $recipient) {
            $this->sendNotification(
                $recipient,
                'Lead Status Changed',
                "Lead {$refId} ({$lead->customer_name}) status changed from '{$oldStatusLabel}' to '{$newStatusLabel}'",
                'warning',
                'heroicon-o-arrow-path',
                $lead
            );
        }
    }

    /**
     * Handle service status change
     */
    private function handleServiceStatusChange(Lead $lead, string $service, $oldStatus, $newStatus): void
    {
        $serviceLabels = [
            'air_ticket_status' => 'Air Ticket',
            'hotel_status' => 'Hotel',
            'visa_status' => 'Visa',
            'land_package_status' => 'Land Package',
        ];

        $serviceLabel = $serviceLabels[$service] ?? ucfirst(str_replace('_status', '', $service));
        $oldStatusLabel = ucfirst(str_replace('_', ' ', $oldStatus));
        $newStatusLabel = ucfirst(str_replace('_', ' ', $newStatus));
        $refId = $lead->reference_id ?: "ID: {$lead->id}";

        $recipients = $this->getNotificationRecipients($lead, 'service_status_change');

        foreach ($recipients as $recipient) {
            $this->sendNotification(
                $recipient,
                'Service Status Updated',
                "{$serviceLabel} status for lead {$refId} ({$lead->customer_name}) changed from '{$oldStatusLabel}' to '{$newStatusLabel}'",
                'success',
                'heroicon-o-check-circle',
                $lead
            );
        }
    }

    /**
     * Get notification recipients based on lead and notification type
     */
    private function getNotificationRecipients(Lead $lead, string $type): \Illuminate\Support\Collection
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
    private function getManager(\App\Models\User $user): ?\App\Models\User
    {
        return \App\Models\User::where('role', $user->role)
            ->where('is_manager', true)
            ->where('id', '!=', $user->id)
            ->first();
    }

    /**
     * Notify manager of a user
     */
    private function notifyManager(\App\Models\User $user, Lead $lead, string $notificationType): void
    {
        $manager = $this->getManager($user);
        if ($manager) {
            $titles = [
                'new_lead' => 'Team Member: New Lead Assigned',
                'lead_assignment' => 'Team Member: Lead Assignment',
                'lead_status_change' => 'Team Member: Lead Status Changed',
                'service_status_change' => 'Team Member: Service Status Updated',
            ];

            $title = $titles[$notificationType] ?? 'Team Member Notification';

            $refId = $lead->reference_id ?: "ID: {$lead->id}";
            $this->sendNotification(
                $manager,
                $title,
                "{$user->name}: Lead {$refId} ({$lead->customer_name})",
                'info',
                'heroicon-o-user-group',
                $lead
            );
        }
    }

    /**
     * Send notification to a user
     */
    private function sendNotification(
        \App\Models\User $user,
        string $title,
        string $body,
        string $color = 'info',
        string $icon = 'heroicon-o-information-circle',
        ?Lead $lead = null
    ): void {
        try {
            Log::info('LeadObserver::sendNotification called', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'title' => $title,
                'lead_id' => $lead?->id,
            ]);

            $filamentNotification = Notification::make()
                ->title($title)
                ->body($body)
                ->color($color)
                ->icon($icon);

            // Add action to view lead if lead is provided
            if ($lead) {
                // Determine the correct URL based on notification type
                $leadUrl = LeadResource::getUrl('view', ['record' => $lead]);
                
                if ($title === 'New Lead Assigned') {
                    $leadUrl = MySalesDashboardResource::getUrl('view', ['record' => $lead]);
                } elseif ($title === 'New Lead Ready for Operations') {
                    $leadUrl = AllLeadDashboardResource::getUrl('view', ['record' => $lead]);
                }
                    
                $filamentNotification->actions([
                    Action::make('view')
                        ->label('View Lead')
                        ->button()
                        ->url($leadUrl),
                ]);
            }

            $user->notify(new LeadDatabaseNotification($filamentNotification, $lead?->id));

            Log::info('LeadObserver::sendNotification - Notification sent successfully', [
                'user_id' => $user->id,
            ]);

            // Dispatch event for real-time updates (if Echo is configured)
            event(new DatabaseNotificationsSent($user));
        } catch (\Exception $e) {
            Log::error('Failed to send notification', [
                'user_id' => $user->id,
                'title' => $title,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
