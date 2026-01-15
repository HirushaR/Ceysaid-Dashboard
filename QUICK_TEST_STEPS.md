# Quick Testing Steps - Notification System

## 🚀 Fast Test (5 minutes)

### Step 1: Create a Test Lead
1. **Login** as Admin or Sales user
2. Go to **Leads** → Click **"New Lead"** or **"Create"**
3. Fill form:
   - Customer Name: `Test Customer`
   - Platform: `facebook` (or any)
   - **Assigned Sales Rep**: Select a DIFFERENT user (not yourself)
   - Status: `Assigned to Sales`
4. Click **"Create"**

### Step 2: Check Notifications (as Assigned User)
1. **Logout** and **Login** as the user you assigned the lead to
2. Look at the **top-right sidebar** - you should see a **bell icon (🔔)**
3. The bell should show a **red badge** with a number (unread count)
4. Click the **bell icon** to open notifications
5. You should see: **"New Lead Assigned"** notification

### Step 3: Check Navigation Badges
1. Look at the **sidebar navigation menu**
2. Check these menu items for **colored badges**:
   - **"Leads"** - should show badge
   - **"My Sales"** - should show badge (if you're the assigned user)

### Step 4: Test Status Change
1. **Login** as Admin or the assigned user
2. Go to **Leads** → Open the test lead
3. Click **"Edit"**
4. Change **Status** from `Assigned to Sales` → `Info Gather Complete`
5. Click **"Save"**
6. **Login** as assigned user again
7. Check notification bell - should see **"Lead Status Changed"** notification

### Step 5: Mark as Read
1. Click the **notification bell**
2. Click **"Mark as read"** on a notification
3. Badge count should **decrease**
4. Notification moves to "read" section

---

## ✅ What You Should See

### In Sidebar:
- 🔔 **Bell icon** with red badge showing unread count
- **Navigation badges** on menu items:
  - Leads (red badge)
  - My Sales (blue badge)
  - Confirm Lead (green badge - if confirmed)
  - Visa Leads (yellow badge - if visa-related)
  - My Operation Lead (yellow badge - if operation-related)

### In Notification Modal:
- List of notifications with:
  - Title (e.g., "New Lead Assigned")
  - Body/description
  - Timestamp
  - Icon and color
- Ability to mark as read
- Ability to delete

---

## 🐛 If Something Doesn't Work

### No Bell Icon?
- Check: `AdminPanelProvider.php` has `->databaseNotifications()`
- Clear cache: `php artisan cache:clear`
- Refresh browser

### No Notifications?
- Check: `AppServiceProvider.php` has `Lead::observe(LeadObserver::class)`
- Check Laravel logs: `storage/logs/laravel.log`
- Verify notifications table exists: `php artisan tinker` → `DB::table('notifications')->count()`

### Badges Not Showing?
- Verify user has unread notifications
- Check `NotificationHelper.php` methods
- Clear cache: `php artisan cache:clear`

### Notifications Not Updating?
- Wait 30 seconds (polling interval)
- Check `AdminPanelProvider.php` has `->databaseNotificationsPolling('30s')`
- Refresh page

---

## 📝 Test Checklist

Quick checklist - check each item:

- [ ] Bell icon appears in sidebar
- [ ] Bell shows unread count badge
- [ ] Clicking bell opens notification modal
- [ ] New lead creation sends notification
- [ ] Assigned user receives notification
- [ ] Status change sends notification
- [ ] Navigation badges appear
- [ ] Badge counts are correct
- [ ] Can mark notifications as read
- [ ] Badge count decreases when read
- [ ] Notifications update automatically (within 30s)

---

## 🎯 Expected Notification Examples

When you create/edit leads, you'll see notifications like:

1. **"New Lead Assigned"**
   - "You have been assigned a new lead: Test Customer (Ref: REF-12345)"
   - Icon: ✨ (sparkles)
   - Color: Blue (info)

2. **"Lead Status Changed"**
   - "Lead REF-12345 (Test Customer) status changed from 'Assigned to Sales' to 'Info Gather Complete'"
   - Icon: 🔄 (arrow-path)
   - Color: Yellow (warning)

3. **"Service Status Updated"**
   - "Visa status for lead REF-12345 (Test Customer) changed from 'pending' to 'done'"
   - Icon: ✅ (check-circle)
   - Color: Green (success)

---

## 💡 Pro Tips

1. **Use Two Browser Windows**: 
   - Window 1: Create/edit leads
   - Window 2: Watch notifications appear automatically

2. **Check Different User Roles**:
   - Test as Sales user
   - Test as Operation user
   - Test as Manager
   - Test as Admin

3. **Test Edge Cases**:
   - Lead with no assignee
   - Lead with no manager
   - Lead creator same as assignee
   - Multiple rapid changes

4. **Monitor Performance**:
   - Create 10+ leads quickly
   - Verify all notifications sent
   - Check badge calculation speed

---

## 📞 Need Help?

If notifications aren't working:

1. **Check Logs**: `tail -f storage/logs/laravel.log`
2. **Check Database**: `php artisan tinker` → `DB::table('notifications')->get()`
3. **Verify Observer**: Check `AppServiceProvider.php` has observer registered
4. **Clear Everything**: 
   ```bash
   php artisan cache:clear
   php artisan config:clear
   php artisan view:clear
   ```

---

## ✨ Success!

If you see:
- ✅ Bell icon with badge
- ✅ Notifications in modal
- ✅ Badges on navigation items
- ✅ Notifications updating automatically

**Congratulations! The notification system is working! 🎉**
