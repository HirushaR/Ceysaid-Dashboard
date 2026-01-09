# Admin Analytics Dashboard Setup Guide

## Overview

This analytics dashboard provides comprehensive insights into sales, operations, leads, revenue, and staff leave for the Travel CRM system. It includes KPI cards, trend charts, breakdown visualizations, and a leave calendar.

## Features Implemented

### KPI Cards
- **Total Leads**: Count of leads in selected date range with period-over-period comparison
- **Conversion Rate**: Percentage of leads converted to bookings (confirmed/document_upload_complete status)
- **Total Revenue**: Sum of paid invoices with period-over-period comparison
- **Pending Tasks**: Count of pending/assigned call center tasks with overdue indicators

### Chart Widgets
- **Leads Trend**: Line chart showing lead count over time (daily/weekly/monthly intervals)
- **Revenue Trend**: Line chart showing revenue over time with currency formatting
- **Sales Performance**: Bar chart comparing bookings count and revenue by sales staff
- **Pipeline Breakdown**: Doughnut chart showing lead distribution by status
- **Operations Workload**: Bar chart showing total and overdue tasks by operation staff

### Leave Calendar
- **Visual Calendar**: Grid layout showing daily leave counts with color coding
- **Leave Types**: Different colors for annual, sick, personal, and other leave types
- **Staff Names**: Tooltips showing which staff members are on leave
- **Summary Stats**: Total leave days, average daily leave, and peak daily leave

### Filters and Controls
- **Date Range Picker**: Presets (Today, Yesterday, Last 7/30 days, MTD, QTD, YTD, Custom)
- **User Filters**: Filter by sales user, operation user
- **Lead Filters**: Filter by lead source (platform), pipeline stage
- **Real-time Updates**: 30-second polling for live data
- **Caching**: 5-minute cache for performance optimization

## File Structure

```
app/
├── Services/
│   └── DateRangeService.php                    # Date range handling and presets
├── Filament/
│   ├── Pages/
│   │   └── AnalyticsDashboard.php             # Main dashboard page
│   └── Widgets/
│       └── Analytics/
│           ├── KPICardsWidget.php             # KPI statistics cards
│           ├── LeadsTrendWidget.php            # Leads trend chart
│           ├── RevenueTrendWidget.php          # Revenue trend chart
│           ├── SalesPerformanceWidget.php      # Sales performance chart
│           ├── PipelineBreakdownWidget.php     # Pipeline stage breakdown
│           ├── OperationsWorkloadWidget.php    # Operations workload chart
│           └── LeaveCalendarWidget.php         # Leave calendar widget
├── Models/
│   ├── Lead.php                               # Enhanced with analytics scopes
│   ├── Invoice.php                            # Enhanced with analytics scopes
│   └── CallCenterCall.php                    # Enhanced with analytics scopes
└── Providers/
    └── Filament/
        └── AdminPanelProvider.php             # Updated to register dashboard

resources/views/
├── filament/
│   ├── pages/
│   │   └── analytics-dashboard.blade.php       # Dashboard layout
│   └── widgets/
│       └── analytics/
│           └── leave-calendar.blade.php        # Leave calendar template

tests/Feature/Analytics/
└── AnalyticsTest.php                          # Comprehensive test suite

database/factories/
├── LeadFactory.php                            # Lead factory for testing
├── InvoiceFactory.php                         # Invoice factory for testing
├── CallCenterCallFactory.php                  # Call center call factory
└── LeaveFactory.php                           # Leave factory for testing
```

## Setup Instructions

### 1. Install Dependencies

Ensure you have the required packages (already included in Laravel/Filament):

```bash
composer require filament/filament
```

### 2. Database Schema Mapping

The analytics dashboard uses the existing schema with these mappings:

**Leads Table**:
- `created_at` → Lead creation date
- `status` → Pipeline stage (using LeadStatus enum)
- `assigned_to` → Sales user assignment
- `platform` → Lead source (facebook, whatsapp, email)

**Invoices Table**:
- `created_at` → Booking date
- `total_amount` → Revenue amount
- `status` → Payment status (paid, pending, partial)

**Call Center Calls Table**:
- `created_at` → Task creation date
- `status` → Task status (pending, assigned, called, completed)
- `assigned_call_center_user` → Operation user assignment

**Leaves Table**:
- `start_date` / `end_date` → Leave period
- `status` → Leave approval status
- `type` → Leave type (annual, sick, personal, etc.)

### 3. Permission Configuration

The dashboard is restricted to admin users only. Update the `canAccess()` method in `AnalyticsDashboard.php` if you need different permissions:

```php
public static function canAccess(): bool
{
    return auth()->user()?->isAdmin() ?? false;
}
```

### 4. Cache Configuration

The dashboard uses Laravel's default cache driver. For production, consider using Redis:

```bash
# In .env
CACHE_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### 5. Timezone Configuration

Set your application timezone in `config/app.php`:

```php
'timezone' => 'Asia/Colombo', // Colombo, Sri Lanka timezone
```

### 6. Running Tests

Execute the analytics test suite:

```bash
php artisan test tests/Feature/Analytics/AnalyticsTest.php
```

## Usage

### Accessing the Dashboard

1. Log in as an admin user
2. Navigate to `/admin/analytics-dashboard`
3. Use the filters at the top to customize your view
4. Click on KPI cards or chart segments to drill down to detailed views

### Date Range Presets

- **Today**: Current day only
- **Yesterday**: Previous day only
- **Last 7 Days**: Past 7 days including today
- **Last 30 Days**: Past 30 days including today
- **Month to Date**: From start of current month to today
- **Quarter to Date**: From start of current quarter to today
- **Year to Date**: From start of current year to today
- **Custom Range**: Select specific start and end dates

### Chart Intervals

The system automatically selects appropriate intervals based on date range:
- **Daily**: ≤ 7 days
- **Weekly**: 8-90 days
- **Monthly**: > 90 days

### Drill-Down Functionality

Clicking on KPI cards or chart elements will:
- Open the relevant Filament resource
- Apply appropriate date and filter constraints
- Show detailed data for the selected segment

## Performance Considerations

### Caching Strategy

- **Cache Duration**: 5 minutes for all analytics data
- **Cache Keys**: Include user ID, date range, and filters
- **Cache Invalidation**: Automatic on data changes (Lead, Invoice, CallCenterCall updates)

### Database Optimization

Recommended indexes for optimal performance:

```sql
-- Leads table
CREATE INDEX idx_leads_created_at ON leads(created_at);
CREATE INDEX idx_leads_assigned_to ON leads(assigned_to);
CREATE INDEX idx_leads_status ON leads(status);
CREATE INDEX idx_leads_platform ON leads(platform);

-- Invoices table
CREATE INDEX idx_invoices_created_at ON invoices(created_at);
CREATE INDEX idx_invoices_status ON invoices(status);
CREATE INDEX idx_invoices_lead_id ON invoices(lead_id);

-- Call center calls table
CREATE INDEX idx_call_center_calls_created_at ON call_center_calls(created_at);
CREATE INDEX idx_call_center_calls_status ON call_center_calls(status);
CREATE INDEX idx_call_center_calls_assigned_user ON call_center_calls(assigned_call_center_user);

-- Leaves table
CREATE INDEX idx_leaves_start_date ON leaves(start_date);
CREATE INDEX idx_leaves_end_date ON leaves(end_date);
CREATE INDEX idx_leaves_status ON leaves(status);
```

### Query Optimization

- Use eager loading for relationships
- Implement database-level aggregations
- Consider materialized views for complex analytics

## Customization

### Adding New KPIs

1. Create a new widget extending `StatsOverviewWidget`
2. Add the widget to `AnalyticsDashboard::getHeaderWidgets()`
3. Implement caching and permission checks

### Adding New Charts

1. Create a new widget extending `ChartWidget`
2. Add the widget to `AnalyticsDashboard::getFooterWidgets()`
3. Use the existing scopes and filters

### Custom Filters

1. Add filter fields to `AnalyticsDashboard::form()`
2. Update the `applyFilters()` methods in widgets
3. Add corresponding scopes to models

## Troubleshooting

### Common Issues

1. **Charts not loading**: Check browser console for JavaScript errors
2. **Slow performance**: Verify database indexes and cache configuration
3. **Permission denied**: Ensure user has admin role
4. **Data not updating**: Check cache configuration and polling intervals

### Debug Mode

Enable debug mode to see detailed query information:

```php
// In AnalyticsDashboard.php
protected static bool $isLazy = false; // Disable lazy loading for debugging
```

## Security Considerations

- All widgets check admin permissions
- User-specific cache keys prevent data leakage
- Input validation on all filter parameters
- SQL injection protection through Eloquent ORM

## Future Enhancements

Potential improvements for future versions:

1. **Real-time Updates**: WebSocket integration for live data
2. **Export Functionality**: PDF/Excel export of analytics data
3. **Custom Dashboards**: User-configurable widget layouts
4. **Advanced Filtering**: More granular filter options
5. **Mobile Optimization**: Responsive design improvements
6. **Data Retention**: Historical data archiving
7. **API Endpoints**: RESTful API for external integrations
