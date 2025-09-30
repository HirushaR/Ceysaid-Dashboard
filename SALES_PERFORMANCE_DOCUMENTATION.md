# Sales Staff Performance Tracking

## Overview
The Sales Staff Performance feature provides comprehensive analytics and tracking for sales team members, including conversion rates, revenue generation, and lead management metrics.

## Features

### 1. Sales Staff Performance Widget (Analytics Dashboard)
- **Location**: Analytics Dashboard → Sales Staff Performance table
- **Metrics Tracked**:
  - Total Leads assigned
  - Converted Leads count
  - Conversion Rate percentage
  - Total Revenue generated
  - Average Deal Size
  - Active Leads requiring follow-up
  - Pending Leads needing attention
  - This Month's Revenue
- **Features**:
  - Real-time data with 30-second polling
  - Sortable columns
  - Filterable by date range, sales user, lead source, pipeline stage
  - Cached for performance (5-minute cache)

### 2. Sales Staff Performance Resource
- **Location**: Analytics → Sales Performance
- **Pages**:
  - **List Page**: Comprehensive table view with all sales staff performance metrics
  - **View Page**: Detailed individual sales staff performance with recent leads
- **Features**:
  - Export functionality for performance reports
  - Tabbed views (All, Top Performers, Needs Attention)
  - Detailed individual performance breakdown
  - Recent leads tracking

### 3. Sales Staff Performance Overview Widget (Main Dashboard)
- **Location**: Main Dashboard (Admin only)
- **Metrics**:
  - Total Sales Staff count
  - Overall team conversion rate
  - Total revenue from all sales staff
  - This month's revenue
  - Active leads count
  - Pending leads count
  - Top performer identification

## Performance Metrics Explained

### Conversion Rate
- **Formula**: (Converted Leads / Total Leads) × 100
- **Converted Statuses**: confirmed, operation_complete, document_upload_complete
- **Color Coding**:
  - Green: ≥20% (Excellent)
  - Yellow: 10-19% (Good)
  - Red: <10% (Needs Improvement)

### Revenue Tracking
- **Total Revenue**: Sum of all invoice amounts from leads assigned to sales staff
- **This Month Revenue**: Revenue generated in current month
- **Average Deal Size**: Total Revenue / Converted Leads

### Lead Status Categories
- **Active Leads**: All leads except closed/completed ones
- **Pending Leads**: new, assigned_to_sales, pricing_in_progress
- **Converted Leads**: confirmed, operation_complete, document_upload_complete

## Access Control
- **Admin Only**: All performance tracking features are restricted to admin users
- **Sales Users**: Can view their own performance via SalesKPIWidget on dashboard

## Technical Implementation
- **Caching**: 5-minute cache for performance optimization
- **Real-time Updates**: 30-second polling interval
- **Database Optimization**: Efficient queries with proper indexing
- **Responsive Design**: Works on all device sizes

## Usage Instructions

### For Administrators:
1. Navigate to **Analytics** → **Sales Performance** for detailed team performance
2. Use **Analytics Dashboard** for comprehensive analytics with filters
3. Monitor **Main Dashboard** for quick performance overview
4. Export performance reports for management reviews

### For Sales Staff:
1. View personal performance metrics on the main dashboard
2. Track conversion rates and revenue goals
3. Monitor active and pending leads requiring attention

## Future Enhancements
- Goal setting and tracking
- Performance comparison charts
- Automated performance reports
- Team leaderboards
- Performance trend analysis
