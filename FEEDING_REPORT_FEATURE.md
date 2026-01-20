# Weekly Feeding Report Feature

## Overview
A comprehensive weekly feeding schedule reporting system for fish farmers to track feeding schedules, consumption patterns, and adherence rates.

## Features Implemented

### 1. Backend Controller (`FeedingReportController.php`)
Located at: `app/Http/Controllers/FeedingReportController.php`

**Methods:**
- `index()` - Display the weekly feeding report page
- `weeklyReport()` - Get weekly feeding report data with filters
- `exportWeeklyReport()` - Export report to Excel format
- `generateCageWeeklyReport()` - Generate detailed report for individual cages

**Key Functionality:**
- Date range filtering (defaults to current week)
- Filter by cage or investor
- Calculates daily breakdown of scheduled vs actual consumption
- Tracks adherence rates
- Provides summary statistics across all cages

### 2. Routes (`routes/web.php`)
Added three new routes:
```php
Route::get('reports/feeding', [FeedingReportController::class, 'index'])->name('reports.feeding');
Route::get('reports/feeding/weekly', [FeedingReportController::class, 'weeklyReport'])->name('reports.feeding.weekly');
Route::get('reports/feeding/export-weekly', [FeedingReportController::class, 'exportWeeklyReport'])->name('reports.feeding.export-weekly');
```

### 3. Frontend Component (`FeedingReport.vue`)
Located at: `resources/js/pages/Reports/FeedingReport.vue`

**Features:**
- **Filters Section:**
  - Date range picker (start/end dates)
  - Investor dropdown filter
  - Cage dropdown filter
  - Generate report and reset buttons

- **Summary Statistics Cards:**
  - Total cages
  - Cages with schedules
  - Active schedules
  - Total scheduled feed (kg)
  - Total consumed feed (kg)
  - Average adherence percentage

- **Detailed Cage Reports:**
  - Cage information (investor, feed type, fingerlings count)
  - Schedule details and feeding times
  - Weekly totals (scheduled, consumed, variance)
  - Adherence rate with color coding
  - Daily average consumption
  - Expandable daily breakdown table showing:
    - Date and day of week
    - Scheduled amount
    - Actual amount
    - Variance
    - Adherence percentage
    - Notes

- **Export & Print:**
  - Print report button
  - Export to Excel button

### 4. Navigation Updates (`AppSidebar.vue`)
Added "Feeding Reports" menu item to:
- **Farmer navigation** - Access to feeding reports
- **Admin navigation** - Full access to all reports

The existing "Reports" menu item was renamed to "Sampling Reports" for clarity.

## Data Structure

### Report Response Format
```typescript
{
  report_data: CageReport[],
  summary: {
    total_cages: number,
    total_feed_consumed: number,
    total_scheduled_feed: number,
    average_adherence: number,
    cages_with_schedules: number,
    active_schedules: number
  },
  period: {
    start_date: string,
    end_date: string,
    days_count: number
  },
  filters: {
    investors: Investor[],
    cages: Cage[]
  }
}
```

### Individual Cage Report
```typescript
{
  cage_id: number,
  cage_number: number,
  investor_name: string,
  feed_type: string,
  fingerlings_count: number,
  has_schedule: boolean,
  schedule_name: string,
  feeding_frequency: string,
  feeding_times: string[],
  total_scheduled: number,
  total_consumed: number,
  variance: number,
  adherence_rate: number,
  daily_breakdown: DailyBreakdown[],
  average_daily_consumption: number
}
```

## Visual Indicators

### Adherence Rate Color Coding
- **Green** (≥90%): Excellent adherence
- **Yellow** (70-89%): Good adherence
- **Red** (<70%): Poor adherence

### Variance Display
- **Green**: Positive variance (consumed more than scheduled)
- **Red**: Negative variance (consumed less than scheduled)

## Excel Export Features
The exported Excel file includes:
- Report title and period information
- Overall summary statistics table
- Detailed cage-by-cage breakdown with:
  - Cage header with investor and feed information
  - Schedule details and feeding times
  - Daily breakdown table with all metrics
  - Weekly totals for each cage
- Professional formatting with:
  - Bold headers
  - Colored section headers
  - Auto-sized columns
  - Cell borders

## Usage

### Access the Report
1. Navigate to **Feeding Reports** from the sidebar
2. The report defaults to the current week (Monday to Sunday)

### Generate Custom Report
1. Select start and end dates
2. (Optional) Filter by specific investor
3. (Optional) Filter by specific cage
4. Click "Generate Report"

### View Daily Details
- Click the expand button (▶) on any cage card to see the daily breakdown
- The table shows day-by-day consumption patterns

### Export Data
- Click "📊 Export Excel" to download the complete report
- Click "🖨️ Print" to print the current view

## Database Tables Used
- `cages` - Cage information
- `cage_feeding_schedules` - Feeding schedule configurations
- `cage_feed_consumptions` - Daily feed consumption records
- `investors` - Investor information
- `feed_types` - Feed type details

## Benefits for Fish Farmers
1. **Track Compliance** - Monitor if feeding schedules are being followed
2. **Identify Issues** - Quickly spot cages with poor adherence
3. **Resource Planning** - Understand actual vs planned feed usage
4. **Historical Analysis** - Compare week-over-week performance
5. **Documentation** - Export reports for record-keeping
6. **Multi-cage Management** - Overview of all cages at once

## Technical Notes
- Built with Laravel Inertia.js and Vue 3
- Uses PhpOffice/PhpSpreadsheet for Excel exports
- Carbon library for date handling
- Responsive design for mobile and desktop
- Print-friendly layout with hidden filters when printing

## Future Enhancements (Suggestions)
- Monthly and quarterly report views
- Graphical charts for trends
- Email scheduling for weekly reports
- Notifications for low adherence rates
- Feed inventory tracking integration
- Cost analysis based on consumption
