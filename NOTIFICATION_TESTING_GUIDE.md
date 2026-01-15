# Notification System Testing Guide

## Prerequisites

1. ✅ Migrations completed successfully
2. ✅ At least 2 users in the system (one to create/edit leads, one to receive notifications)
3. ✅ Users with different roles (sales, operation, admin) for comprehensive testing

## Step-by-Step Testing Instructions

### Test 1: New Lead Creation Notification

**Objective:** Verify notifications are sent when a new lead is created and assigned.

**Steps:**
1. Log in as a **Sales User** or **Admin**
2. Navigate to **Leads** → Click **"New Lead"** or **"Create"**
3. Fill in the lead form:
   - Customer Name: "Test Customer"
   - Platform: Select any platform
   - Assign to: Select a different sales user (not yourself)
   - Status: Leave as default or select "Assigned to Sales"
4. Click **"Create"** to save the lead

**Expected Results:**
- ✅ Notification sent to the assigned sales user
- ✅ Notification sent to the assigned user's manager (if exists)
- ✅ Notification bell icon shows unread count in sidebar
- ✅ "My Sales" navigation badge shows count (for assigned user)
- ✅ "Leads" navigation badge shows count

**How to Verify:**
1. Log out and log in as the **assigned sales user**
2. Look for the notification bell icon (🔔) in the top-right of the sidebar
3. Click the bell icon to open notifications modal
4. You should see: "New Lead Assigned" notification
5. Check navigation badges - "My Sales" should show a badge with count

---

### Test 2: Lead Assignment Change Notification

**Objective:** Verify notifications when a lead is reassigned.

**Steps:**
1. Log in as **Admin** or **Sales User** with edit permissions
2. Navigate to **Leads** → Open an existing lead
3. Click **"Edit"**
4. In the **"Assignment & Status"** section:
   - Change **"Assigned Sales Rep"** to a different user
   - Or change **"Assigned Operator"** to a different user
5. Click **"Save"**

**Expected Results:**
- ✅ Notification sent to the newly assigned user
- ✅ Notification sent to the previously assigned user (if changed)
- ✅ Notification sent to the lead creator (if different)
- ✅ Notification sent to managers
- ✅ Badge counts update accordingly

**How to Verify:**
1. Log in as the **newly assigned user**
2. Check notification bell - should see "Lead Assigned to You"
3. Log in as the **previous assignee** - should see "Lead Reassigned"
4. Check navigation badges update

---

### Test 3: Lead Status Change Notification

**Objective:** Verify notifications when lead status changes.

**Steps:**
1. Log in as **Sales User** or **Admin**
2. Navigate to **Leads** → Open an existing lead
3. Click **"Edit"**
4. In the **"Assignment & Status"** section:
   - Change **"Lead Status"** from current status to a different status
   - Example: "New" → "Assigned to Sales" → "Info Gather Complete"
5. Click **"Save"**

**Expected Results:**
- ✅ Notification sent to assigned sales rep
- ✅ Notification sent to assigned operator (if exists)
- ✅ Notification sent to lead creator
- ✅ Notification sent to managers
- ✅ Notification title: "Lead Status Changed"
- ✅ Notification body shows: "from 'Old Status' to 'New Status'"

**How to Verify:**
1. Log in as assigned users
2. Check notification bell - should see status change notification
3. Verify notification shows correct old → new status
4. Check "Confirm Lead" badge if status changed to "confirmed"

---

### Test 4: Service Status Change Notification

**Objective:** Verify notifications when service statuses change.

**Steps:**
1. Log in as **Operation User** or **Admin**
2. Navigate to **Confirm Lead** or **Visa Leads**
3. Open a lead with status "Confirmed" or "Document Upload Complete"
4. Click **"Edit"**
5. In the **"Status Management"** section, change:
   - **Air Ticket Status**: "pending" → "done"
   - **Hotel Status**: "pending" → "done"
   - **Visa Status**: "pending" → "done" (in Visa Leads tab)
   - **Land Package Status**: "pending" → "done"
6. Click **"Save"**

**Expected Results:**
- ✅ Notification sent to assigned operator
- ✅ Notification sent to assigned sales rep
- ✅ Notification sent to manager
- ✅ Notification title: "Service Status Updated"
- ✅ Notification body shows service name and status change

**How to Verify:**
1. Log in as assigned operator
2. Check notification bell - should see service status notifications
3. Log in as assigned sales rep - should also receive notifications
4. Check "Visa Leads" badge if visa status changed

---

### Test 5: Navigation Badge Display

**Objective:** Verify badges appear correctly on navigation items.

**Steps:**
1. Ensure you have unread notifications (from previous tests)
2. Log in as a user with unread notifications
3. Look at the sidebar navigation

**Expected Results:**
- ✅ **"Leads"** menu item shows red badge with count
- ✅ **"Confirm Lead"** menu item shows green badge (if confirmed lead notifications)
- ✅ **"Visa Leads"** menu item shows yellow badge (if visa-related notifications)
- ✅ **"My Sales"** menu item shows blue badge (if sales-related notifications)
- ✅ **"My Operation Lead"** menu item shows yellow badge (if operation-related notifications)

**How to Verify:**
1. Check each navigation item for badge presence
2. Verify badge colors match expected colors
3. Verify badge counts match unread notification counts

---

### Test 6: Notification Modal Functionality

**Objective:** Verify notification modal works correctly.

**Steps:**
1. Log in as a user with unread notifications
2. Click the **notification bell icon** (🔔) in the sidebar

**Expected Results:**
- ✅ Modal opens showing list of notifications
- ✅ Notifications show:
  - Title
  - Body/description
  - Timestamp
  - Icon and color
- ✅ Unread notifications are highlighted
- ✅ Can mark notifications as read
- ✅ Can delete notifications
- ✅ Badge counts update when notifications are read

**How to Verify:**
1. Click bell icon - modal should open
2. Verify notifications are displayed correctly
3. Click "Mark as read" on a notification
4. Verify badge count decreases
5. Verify notification moves to "read" section

---

### Test 7: Manager Notifications

**Objective:** Verify managers receive notifications for their team members.

**Prerequisites:**
- Create a manager user (set `is_manager = true` and same `role`)
- Create team members under that manager

**Steps:**
1. Log in as a **team member** (sales/operation user)
2. Create or edit a lead assigned to that team member
3. Change lead status or assignment

**Expected Results:**
- ✅ Manager receives notification
- ✅ Notification title includes "Team Member:"
- ✅ Notification shows team member name and lead details

**How to Verify:**
1. Log in as the manager
2. Check notification bell
3. Should see notifications prefixed with "Team Member:"

---

### Test 8: Real-time Updates (Polling)

**Objective:** Verify notifications update automatically.

**Steps:**
1. Open the application in two browser windows/tabs
2. Log in as **User A** in Tab 1
3. Log in as **User B** in Tab 2
4. In Tab 1, create/edit a lead assigned to User B
5. Wait up to 30 seconds (polling interval)

**Expected Results:**
- ✅ Tab 2 automatically shows new notification (within 30 seconds)
- ✅ Badge count updates automatically
- ✅ No page refresh needed

**How to Verify:**
1. Watch Tab 2 notification bell
2. Should see count increase automatically
3. Click bell to see new notification appear

---

## Quick Test Checklist

Use this checklist to quickly verify all functionality:

- [ ] New lead creation sends notifications
- [ ] Lead assignment change sends notifications
- [ ] Lead status change sends notifications
- [ ] Service status change sends notifications
- [ ] Previous assignee receives "reassigned" notification
- [ ] Lead creator receives notifications (when different from assignee)
- [ ] Managers receive team member notifications
- [ ] Notification bell icon appears in sidebar
- [ ] Notification modal opens correctly
- [ ] Notifications can be marked as read
- [ ] Badge counts update when notifications read
- [ ] "Leads" navigation badge shows count
- [ ] "Confirm Lead" navigation badge shows count
- [ ] "Visa Leads" navigation badge shows count
- [ ] "My Sales" navigation badge shows count
- [ ] "My Operation Lead" navigation badge shows count
- [ ] Badge colors are correct
- [ ] Notifications poll automatically (30s interval)

---

## Troubleshooting

### Issue: Notifications not appearing

**Check:**
1. Verify migrations ran successfully: `php artisan migrate:status`
2. Check `notifications` table exists: `php artisan tinker` → `DB::table('notifications')->count()`
3. Verify LeadObserver is registered: Check `AppServiceProvider.php`
4. Check Laravel logs: `storage/logs/laravel.log`

### Issue: Badges not showing

**Check:**
1. Verify user has unread notifications
2. Check `NotificationHelper.php` methods are working
3. Clear cache: `php artisan cache:clear`
4. Check browser console for JavaScript errors

### Issue: Notifications not updating automatically

**Check:**
1. Verify polling is enabled: Check `AdminPanelProvider.php` → `databaseNotificationsPolling('30s')`
2. Wait full 30 seconds for polling interval
3. Check browser console for WebSocket/HTTP errors

### Issue: Wrong recipients getting notifications

**Check:**
1. Verify `LeadObserver.php` recipient logic
2. Check user relationships (assigned_to, assigned_operator, created_by)
3. Verify manager detection logic

---

## Testing with Database Queries

You can also test using database queries:

```php
// In tinker: php artisan tinker

// Check if notifications table exists
DB::table('notifications')->count();

// Check notifications for a user
$user = User::find(1);
$user->unreadNotifications()->count();

// View notification data
$user->unreadNotifications()->get()->each(function($n) {
    dump($n->data);
});

// Manually create a test notification
use Filament\Notifications\Notification;
$user = User::find(1);
Notification::make()
    ->title('Test Notification')
    ->body('This is a test')
    ->sendToDatabase($user);
```

---

## Expected Notification Types

When testing, you should see these notification types:

1. **New Lead Assigned** (info, sparkles icon)
2. **Lead Assigned to You** (info, user-plus icon)
3. **Lead Reassigned** (warning, arrow-right icon)
4. **Lead Status Changed** (warning, arrow-path icon)
5. **Service Status Updated** (success, check-circle icon)
6. **Team Member: [Type]** (info, user-group icon)

---

## Performance Testing

For performance testing with many notifications:

1. Create multiple leads quickly
2. Verify notifications are sent efficiently
3. Check badge count calculation performance
4. Monitor database query performance

If performance issues occur, consider:
- Caching badge counts
- Optimizing notification queries
- Adding database indexes

---

## Success Criteria

The notification system is working correctly if:

✅ All notification types are sent correctly
✅ Correct recipients receive notifications
✅ Badges display accurate counts
✅ Notifications can be read/deleted
✅ System handles edge cases (no assignee, no manager, etc.)
✅ Performance is acceptable with normal usage
