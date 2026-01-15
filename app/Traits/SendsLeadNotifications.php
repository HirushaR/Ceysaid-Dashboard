<?php

namespace App\Traits;

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

trait SendsLeadNotifications
{
    /**
     * Send notifications after lead is created
     */
    protected function sendLeadCreatedNotifications(Lead $lead): void
    {
        if ($lead->assigned_to && $lead->assignedUser) {
            try {
                $refId = $lead->reference_id ?: "ID: {$lead->id}";
                // Use MySalesDashboardResource URL for "New Lead Assigned" notifications
                $leadUrl = MySalesDashboardResource::getUrl('view', ['record' => $lead]);
                
                $filamentNotification = Notification::make()
                    ->title('New Lead Assigned')
                    ->body("You have been assigned a new lead: {$lead->customer_name} (Ref: {$refId})")
                    ->success()
                    ->icon('heroicon-o-sparkles')
                    ->actions([
                        Action::make('view')
                            ->label('View Lead')
                            ->button()
                            ->url($leadUrl),
                    ]);

                $lead->assignedUser->notify(new LeadDatabaseNotification($filamentNotification, $lead->id));
                event(new DatabaseNotificationsSent($lead->assignedUser));
            } catch (\Exception $e) {
                \Log::error('Failed to send notification', ['error' => $e->getMessage()]);
            }
        }
    }

    /**
     * Send notifications after lead is updated
     */
    protected function sendLeadUpdatedNotifications(Lead $lead, array $original): void
    {
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

    private function handleAssignmentChange(Lead $lead, $oldAssignedTo, $newAssignedTo): void
    {
        $refId = $lead->reference_id ?: "ID: {$lead->id}";
        $leadUrl = LeadResource::getUrl('view', ['record' => $lead]);
        
        if ($newAssignedTo && $lead->assignedUser) {
            $notification = Notification::make()
                ->title('Lead Assigned to You')
                ->body("Lead {$refId} ({$lead->customer_name}) has been assigned to you")
                ->success()
                ->icon('heroicon-o-user-plus')
                ->actions([
                    Action::make('view')
                        ->label('View Lead')
                        ->button()
                        ->url($leadUrl),
                ]);

            $lead->assignedUser->notify(new LeadDatabaseNotification($notification, $lead->id));
            event(new DatabaseNotificationsSent($lead->assignedUser));
        }

        if ($oldAssignedTo && $oldAssignedTo != $newAssignedTo) {
            $oldUser = \App\Models\User::find($oldAssignedTo);
            if ($oldUser) {
                $notification = Notification::make()
                    ->title('Lead Reassigned')
                    ->body("Lead {$refId} ({$lead->customer_name}) has been reassigned")
                    ->warning()
                    ->icon('heroicon-o-arrow-right')
                    ->actions([
                        Action::make('view')
                            ->label('View Lead')
                            ->button()
                            ->url($leadUrl),
                    ]);

                $oldUser->notify(new LeadDatabaseNotification($notification, $lead->id));
                event(new DatabaseNotificationsSent($oldUser));
            }
        }
    }

    private function handleOperatorAssignmentChange(Lead $lead, $oldOperatorId, $newOperatorId): void
    {
        if ($newOperatorId && $lead->assignedOperator) {
            $refId = $lead->reference_id ?: "ID: {$lead->id}";
            $leadUrl = LeadResource::getUrl('view', ['record' => $lead]);
            
            $notification = Notification::make()
                ->title('Lead Assigned to You (Operator)')
                ->body("Lead {$refId} ({$lead->customer_name}) has been assigned to you for operations")
                ->warning()
                ->icon('heroicon-o-clipboard-document-check')
                ->actions([
                    Action::make('view')
                        ->label('View Lead')
                        ->button()
                        ->url($leadUrl),
                ]);

            $lead->assignedOperator->notify(new LeadDatabaseNotification($notification, $lead->id));
            event(new DatabaseNotificationsSent($lead->assignedOperator));
        }
    }

    private function handleStatusChange(Lead $lead, $oldStatus, $newStatus): void
    {
        $oldStatusLabel = LeadStatus::tryFrom($oldStatus)?->label() ?? $oldStatus;
        $newStatusLabel = LeadStatus::tryFrom($newStatus)?->label() ?? $newStatus;
        $refId = $lead->reference_id ?: "ID: {$lead->id}";

        // Special handling for "info_gather_complete" status
        // Notify ALL operation users when lead status changes to info_gather_complete
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
        $recipients = collect();
        if ($lead->assigned_to && $lead->assignedUser) {
            $recipients->push($lead->assignedUser);
        }
        if ($lead->assigned_operator && $lead->assignedOperator) {
            $recipients->push($lead->assignedOperator);
        }
        if ($lead->created_by && $lead->creator && !$recipients->contains('id', $lead->created_by)) {
            $recipients->push($lead->creator);
        }

        $leadUrl = LeadResource::getUrl('view', ['record' => $lead]);
        
        foreach ($recipients->unique('id') as $recipient) {
            $notification = Notification::make()
                ->title('Lead Status Changed')
                ->body("Lead {$refId} ({$lead->customer_name}) status changed from '{$oldStatusLabel}' to '{$newStatusLabel}'")
                ->warning()
                ->icon('heroicon-o-arrow-path')
                ->actions([
                    Action::make('view')
                        ->label('View Lead')
                        ->button()
                        ->url($leadUrl),
                ]);

            $recipient->notify(new LeadDatabaseNotification($notification, $lead->id));
            event(new DatabaseNotificationsSent($recipient));
        }
    }

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

        $recipients = collect();
        if ($lead->assigned_to && $lead->assignedUser) {
            $recipients->push($lead->assignedUser);
        }
        if ($lead->assigned_operator && $lead->assignedOperator) {
            $recipients->push($lead->assignedOperator);
        }

        $refId = $lead->reference_id ?: "ID: {$lead->id}";
        $leadUrl = LeadResource::getUrl('view', ['record' => $lead]);
        
        foreach ($recipients->unique('id') as $recipient) {
            $notification = Notification::make()
                ->title('Service Status Updated')
                ->body("{$serviceLabel} status for lead {$refId} ({$lead->customer_name}) changed from '{$oldStatusLabel}' to '{$newStatusLabel}'")
                ->success()
                ->icon('heroicon-o-check-circle')
                ->actions([
                    Action::make('view')
                        ->label('View Lead')
                        ->button()
                        ->url($leadUrl),
                ]);

            $recipient->notify(new LeadDatabaseNotification($notification, $lead->id));
            event(new DatabaseNotificationsSent($recipient));
        }
    }
}
