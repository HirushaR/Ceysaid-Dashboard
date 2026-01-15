# Notification System Implementation Plan

## Overview
This document outlines the plan for implementing a comprehensive notification system for lead-related changes in the Filament application.

## Current State Analysis

### ✅ Already Available
- Filament Notifications package installed (`filament/notifications`)
- User model uses `Notifiable` trait
- `@livewire('notifications')` component exists in layout
- Basic notification infrastructure ready

### ❌ Missing Components
- Database notifications table migration
- Filament notifications configuration file
- Notification trigger component for sidebar
- Lead Observer for automatic notifications
- Sidebar badge display for notification counts

## Notification Events to Implement

### 1. Lead Assignment Notifications
**When:** Lead is assigned to a user
- **Assigned to Sales Rep** (`assigned_to` changes)
  - Notify: Newly assigned sales user
  - Notify: Previous sales user (if changed)
  - Notify: Lead creator (if different from assignee)
  - Notify: Manager of assigned user (if manager exists)

- **Assigned to Operator** (`assigned_operator` changes)
  - Notify: Newly assigned operator
  - Notify: Previous operator (if changed)
  - Notify: Assigned sales rep
  - Notify: Manager of assigned operator (if manager exists)

### 2. Lead Status Change Notifications
**When:** Lead status changes
- **Status Transitions:**
  - `new` → `assigned_to_sales`
  - `assigned_to_sales` → `assigned_to_operations`
  - `assigned_to_operations` → `info_gather_complete`
  - `info_gather_complete` → `pricing_in_progress`
  - `pricing_in_progress` → `sent_to_customer`
  - `sent_to_customer` → `confirmed`
  - `confirmed` → `document_upload_complete`
  - Any status → `mark_closed`
  - Any status → `operation_complete`

- **Notify:**
  - Assigned sales rep
  - Assigned operator
  - Lead creator
  - Managers of assigned users

### 3. Service Status Change Notifications
**When:** Service statuses change
- **Air Ticket Status** (`air_ticket_status` changes)
  - Notify: Assigned operator
  - Notify: Assigned sales rep
  - Notify: Manager of operator

- **Hotel Status** (`hotel_status` changes)
  - Notify: Assigned operator
  - Notify: Assigned sales rep
  - Notify: Manager of operator

- **Visa Status** (`visa_status` changes)
  - Notify: Assigned operator
  - Notify: Assigned sales rep
  - Notify: Manager of operator

- **Land Package Status** (`land_package_status` changes)
  - Notify: Assigned operator
  - Notify: Assigned sales rep
  - Notify: Manager of operator

### 4. New Lead Created Notifications
**When:** New lead is created
- **Notify:**
  - Assigned sales rep (if assigned during creation)
  - Manager of assigned sales rep (if manager exists)
  - Admin users (optional, configurable)

## Implementation Steps

### Phase 1: Database & Configuration Setup
1. **Create notifications table migration**
   ```bash
   php artisan notifications:table
   php artisan migrate
   ```

2. **Publish Filament notifications config**
   ```bash
   php artisan vendor:publish --tag=filament-notifications-config
   ```

3. **Configure notifications in `config/notifications.php`**
   - Enable database notifications
   - Set polling interval (30s default)
   - Configure trigger view path

### Phase 2: Notification Trigger Component
1. **Create notification trigger blade component**
   - Location: `resources/views/notifications/database-notifications-trigger.blade.php`
   - Display unread notification count badge
   - Icon: Bell icon
   - Badge: Red circle with count

2. **Add trigger to Filament sidebar**
   - Modify Filament panel provider or create custom navigation item
   - Position: Top of sidebar, always visible

### Phase 3: Lead Observer Implementation
1. **Create LeadObserver class**
   - Location: `app/Observers/LeadObserver.php`
   - Events to observe:
     - `created` - New lead created
     - `updated` - Lead updated (check for changes)
     - `saved` - Lead saved (for assignment changes)

2. **Implement notification logic**
   - Detect changes in: `assigned_to`, `assigned_operator`, `status`, service statuses
   - Send notifications using Filament's Notification API
   - Include lead reference ID and customer name in notification

3. **Register observer in AppServiceProvider**
   - Register LeadObserver for Lead model

### Phase 4: Notification Content & Formatting
1. **Notification titles:**
   - "New Lead Assigned: {Customer Name}"
   - "Lead Status Changed: {Old Status} → {New Status}"
   - "Service Status Updated: {Service} is now {Status}"
   - "Lead Assigned to Operator: {Customer Name}"

2. **Notification bodies:**
   - Include lead reference ID
   - Include customer name
   - Include relevant status information
   - Link to lead view page

3. **Notification icons & colors:**
   - Assignment: `heroicon-o-user-plus` (info)
   - Status change: `heroicon-o-arrow-path` (warning)
   - Service update: `heroicon-o-check-circle` (success)
   - New lead: `heroicon-o-sparkles` (primary)

### Phase 5: Sidebar Badge Integration
1. **Add notification badges to navigation items**
   - "Leads" - Count of unread lead-related notifications
   - "Confirm Lead" - Count of unread confirmations
   - "Visa Leads" - Count of unread visa-related notifications
   - "My Sales" - Count of unread sales-related notifications
   - "My Operation Lead" - Count of unread operation-related notifications

2. **Create helper method to get notification counts**
   - Filter by notification type/category
   - Cache counts for performance

### Phase 6: Notification Categories & Filtering
1. **Categorize notifications by type:**
   - `lead_assignment`
   - `lead_status_change`
   - `service_status_change`
   - `new_lead`

2. **Implement notification filtering in modal**
   - Filter by category
   - Filter by date
   - Mark as read/unread
   - Delete notifications

## Technical Implementation Details

### Notification Data Structure
```php
[
    'type' => 'lead_assignment|lead_status_change|service_status_change|new_lead',
    'lead_id' => 123,
    'lead_reference_id' => 'REF-12345',
    'customer_name' => 'John Doe',
    'old_value' => 'assigned_to_sales',
    'new_value' => 'confirmed',
    'changed_by' => 'User Name',
    'changed_at' => '2025-01-15 10:30:00',
]
```

### LeadObserver Example Structure
```php
class LeadObserver
{
    public function created(Lead $lead) { }
    public function updated(Lead $lead) { }
    public function saved(Lead $lead) { }
    
    private function notifyAssignmentChange(Lead $lead, $oldAssignedTo, $newAssignedTo) { }
    private function notifyStatusChange(Lead $lead, $oldStatus, $newStatus) { }
    private function notifyServiceStatusChange(Lead $lead, $service, $oldStatus, $newStatus) { }
    private function getNotificationRecipients(Lead $lead, $type) { }
}
```

### Notification Recipient Logic
```php
private function getNotificationRecipients(Lead $lead, string $type): Collection
{
    $recipients = collect();
    
    // Always notify assigned users
    if ($lead->assigned_to) {
        $recipients->push($lead->assignedUser);
    }
    if ($lead->assigned_operator) {
        $recipients->push($lead->assignedOperator);
    }
    
    // Notify creator if different from assignee
    if ($lead->created_by && $lead->created_by !== $lead->assigned_to) {
        $recipients->push($lead->creator);
    }
    
    // Notify managers
    if ($lead->assignedUser && $lead->assignedUser->manager) {
        $recipients->push($lead->assignedUser->manager);
    }
    if ($lead->assignedOperator && $lead->assignedOperator->manager) {
        $recipients->push($lead->assignedOperator->manager);
    }
    
    return $recipients->unique('id');
}
```

## Files to Create/Modify

### New Files
1. `database/migrations/XXXX_XX_XX_create_notifications_table.php` (via artisan command)
2. `app/Observers/LeadObserver.php`
3. `resources/views/notifications/database-notifications-trigger.blade.php`
4. `config/notifications.php` (if not exists, via publish)

### Files to Modify
1. `app/Providers/AppServiceProvider.php` - Register LeadObserver
2. `app/Filament/Resources/LeadResource.php` - Ensure proper form handling
3. `app/Filament/Resources/ConfirmLeadResource.php` - Ensure proper form handling
4. `app/Filament/Resources/DocumentCompleteLeadResource.php` - Ensure proper form handling
5. `app/Filament/Resources/MySalesDashboardResource.php` - Ensure proper form handling
6. `app/Filament/Resources/MyOperationLeadDashboardResource.php` - Ensure proper form handling
7. `app/Filament/Resources/AllLeadDashboardResource.php` - Ensure proper form handling
8. Filament Panel Provider - Add notification trigger to sidebar

## Testing Checklist

- [ ] Notifications table created successfully
- [ ] Notifications config published and configured
- [ ] Notification trigger appears in sidebar
- [ ] Unread count displays correctly
- [ ] New lead creation sends notifications
- [ ] Assignment changes send notifications
- [ ] Status changes send notifications
- [ ] Service status changes send notifications
- [ ] Correct recipients receive notifications
- [ ] Managers receive team member notifications
- [ ] Notification modal opens correctly
- [ ] Notifications can be marked as read
- [ ] Notifications can be deleted
- [ ] Sidebar badges update in real-time (with polling)
- [ ] No duplicate notifications sent
- [ ] Performance is acceptable with many notifications

## Performance Considerations

1. **Polling Interval:** Default 30 seconds (configurable)
2. **Notification Cleanup:** Consider archiving old notifications (>30 days)
3. **Caching:** Cache notification counts for sidebar badges
4. **Batch Notifications:** Group similar notifications if multiple changes happen quickly
5. **Database Indexing:** Ensure proper indexes on notifications table

## Future Enhancements

1. **Real-time via WebSockets:** Replace polling with Laravel Echo/Pusher
2. **Email Notifications:** Send email for critical notifications
3. **Notification Preferences:** Allow users to configure which notifications they receive
4. **Notification Groups:** Group related notifications together
5. **Mobile Push Notifications:** Integrate with mobile app
6. **Notification Sound:** Play sound for new notifications
7. **Notification History:** Archive and search notification history

## Priority Implementation Order

1. **High Priority:**
   - Database setup
   - Basic notification trigger
   - Lead assignment notifications
   - Lead status change notifications

2. **Medium Priority:**
   - Service status notifications
   - Sidebar badges
   - Notification filtering

3. **Low Priority:**
   - Notification preferences
   - Email notifications
   - Real-time WebSockets
