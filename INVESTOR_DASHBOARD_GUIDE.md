# Investor Dashboard Guide

## Overview
The Investor Dashboard is a dedicated interface for investors to monitor their aquaculture operations, including cages, farmers, samplings, and reports. Investors can view real-time analytics and performance metrics for their investments.

## Features

### 1. **Dashboard Analytics**
Investors can view comprehensive analytics about their operations:

- **Summary Cards:**
  - Total number of cages owned
  - Total number of active farmers working for them
  - Samplings during the selected period
  - Average sample weight with growth metrics

- **Time Period Filters:**
  - Today
  - This Week
  - Last 30 Days
  - This Month
  - Custom Date Range

- **Cage Filtering:**
  - View analytics for all cages or specific cages
  - Filter by cage number and farmer name

### 2. **Sampling Trends**
Visual representation of sampling activities and average weights over time using interactive charts.

### 3. **Weight Statistics**
Detailed weight analytics including:
- Average weight
- Maximum weight
- Minimum weight
- Total weight of all samples

### 4. **Cage Performance**
View performance metrics for each cage:
- Cage ID and fingerling count
- Assigned farmer
- Number of samplings
- Average sample weight

### 5. **Feed Type Usage**
See which feed types and brands are being used across all cages.

### 6. **My Farmers Section**
View all farmers linked to the investor with:
- Farmer name and email
- Number of cages they manage

### 7. **Recent Samplings**
Tabular view of recent sampling activities with:
- Cage number
- Sampling date
- Days of Culture (DOC)
- Sample count
- Average weight
- Mortality count

### 8. **Growth Metrics**
Compare current period performance with the previous period:
- Sampling growth percentage
- Weight growth percentage
- Visual indicators for positive/negative growth

## Access Control

### Middleware
- **Route Protection:** Only users with `role = 'investor'` can access the investor dashboard
- **Middleware:** `EnsureUserIsInvestor` checks user role before granting access
- **Auto-filtering:** Dashboard automatically filters data based on the logged-in investor's `investor_id`

### Security Features
- Users without investor role receive a 403 Forbidden error
- Investors can only see data related to their own operations
- No manual investor selection required - automatic based on logged-in user

## Database Structure

### User-Investor Relationship
```sql
users table:
  - investor_id (foreign key to investors.id)
  - role (enum: 'farmer', 'investor', 'admin')
```

- Users with `role = 'investor'` have access to the investor dashboard
- The `investor_id` links the user account to their investor profile
- Multiple users can have the same `investor_id` if needed

## Routes

### Investor Dashboard Routes
```php
Route::middleware(['auth', 'investor'])->group(function () {
    Route::get('investor/dashboard', [InvestorDashboardController::class, 'index'])
        ->name('investor.dashboard');
});
```

## Seeded Investor User Accounts

The system includes pre-seeded investor accounts for testing:

| Name | Email | Password | Investor ID |
|------|-------|----------|-------------|
| John Smith | john.smith@investor.com | password | Linked to John Smith |
| Maria Garcia | maria.garcia@investor.com | password | Linked to Maria Garcia |
| Robert Johnson | robert.johnson@investor.com | password | Linked to Robert Johnson |
| Ana Santos | ana.santos@investor.com | password | Linked to Ana Santos |
| Carlos Rodriguez | carlos.rodriguez@investor.com | password | Linked to Carlos Rodriguez |

## Usage Examples

### Accessing the Investor Dashboard

1. **Login as an investor:**
   ```
   Email: john.smith@investor.com
   Password: password
   ```

2. **Navigate to:** `/investor/dashboard`

3. **The dashboard will automatically show:**
   - All cages owned by John Smith
   - All farmers working for John Smith
   - All samplings for John Smith's cages
   - Performance metrics and analytics

### Filtering Data

**By Time Period:**
- Click on period buttons (Today, This Week, etc.)
- For custom range, select "Custom Range" and pick dates

**By Cage:**
- Click on the cage dropdown in the Sampling Trends section
- Search by cage number or farmer name
- Select a specific cage or "All Cages"

## Controller: InvestorDashboardController

### Main Method: `index()`
Handles the main dashboard view:

```php
public function index(Request $request)
{
    $user = $request->user();
    $investor = Investor::find($user->investor_id);
    
    // Auto-filters all data by investor_id
    $analytics = $this->getInvestorAnalytics(
        $investor->id, 
        $dateRange['start'], 
        $dateRange['end'], 
        $cageNo
    );
    
    return Inertia::render('InvestorDashboard/Index', $analytics);
}
```

### Analytics Methods

**`getInvestorAnalytics()`**
- Retrieves comprehensive analytics for the investor
- Filters all queries by `investor_id`
- Includes cages, farmers, samplings, and performance metrics

**`calculateGrowthMetrics()`**
- Compares current period with previous period
- Calculates percentage growth for samplings and weights

**`getDateRange()`**
- Converts period selection into date ranges
- Supports day, week, month, 30 days, and custom ranges

## Vue Components

### InvestorDashboard/Index.vue
Main dashboard component with:
- Responsive grid layout
- Interactive charts
- Filterable data views
- Real-time period selection

### InvestorDashboard/NoInvestor.vue
Error page shown when:
- User has investor role but no linked `investor_id`
- Investor record doesn't exist
- Provides clear error message and contact information

## API Queries

### Summary Statistics
```php
$totalCages = Cage::where('investor_id', $investorId)->count();
$totalFarmers = User::where('investor_id', $investorId)
    ->where('role', 'farmer')
    ->where('is_active', true)
    ->count();
```

### Sampling Trends
```php
$samplingTrends = Sampling::selectRaw('
    DATE(date_sampling) as date,
    COUNT(DISTINCT samplings.id) as count,
    AVG(samples.weight) as avg_weight
')
->leftJoin('samples', 'samplings.id', '=', 'samples.sampling_id')
->where('samplings.investor_id', $investorId)
->whereBetween('date_sampling', [$startDate, $endDate])
->groupBy('date')
->orderBy('date')
->get();
```

### Cage Performance
```php
$cagePerformance = Cage::selectRaw('
    cages.id,
    cages.number_of_fingerlings,
    users.name as farmer_name,
    COUNT(samplings.id) as sampling_count,
    AVG(samples.weight) as avg_sample_weight
')
->leftJoin('users', 'cages.farmer_id', '=', 'users.id')
->leftJoin('samplings', function($join) use ($investorId) {
    $join->on('cages.id', '=', 'samplings.cage_no')
         ->where('samplings.investor_id', '=', $investorId);
})
->leftJoin('samples', 'samplings.id', '=', 'samples.sampling_id')
->where('cages.investor_id', $investorId)
->whereBetween('samplings.date_sampling', [$startDate, $endDate])
->groupBy('cages.id', 'cages.number_of_fingerlings', 'users.name')
->orderByDesc('avg_sample_weight')
->get();
```

## Testing the Feature

### Manual Testing Steps

1. **Login as Investor:**
   ```bash
   Email: john.smith@investor.com
   Password: password
   ```

2. **Verify Dashboard Access:**
   - Navigate to `/investor/dashboard`
   - Confirm you see John Smith's name in the header
   - Verify summary cards show correct counts

3. **Test Period Filters:**
   - Click "Today" - verify data updates
   - Click "This Week" - verify date range changes
   - Click "Custom Range" - select dates and apply
   - Verify analytics update correctly

4. **Test Cage Filtering:**
   - Click cage dropdown
   - Select a specific cage
   - Verify charts and tables update to show only that cage's data
   - Select "All Cages" to reset

5. **Verify Data Isolation:**
   - Login as a different investor
   - Confirm you only see your own data
   - Cannot see other investors' cages or farmers

### Using Tinker

```php
php artisan tinker

// Get investor user
$user = User::where('email', 'john.smith@investor.com')->first();
$investor = $user->investor;

// Check investor's cages
$investor->cages; // Should show John Smith's cages

// Check investor's farmers
$investor->farmers; // Should show farmers linked to John Smith

// Verify relationships
$farmer = User::where('email', 'pedro.santos@sfm.com')->first();
$farmer->investor->name; // Should return "John Smith"
```

## Troubleshooting

### Issue: "Access denied. Investor privileges required."
**Solution:** Ensure the user has:
- `role = 'investor'` in the users table
- Valid `investor_id` linking to an existing investor

### Issue: "You are not associated with any investor account"
**Solution:** 
- Check if the user's `investor_id` is set
- Verify the investor record exists in the investors table
- Update the user record:
  ```php
  $user = User::find($userId);
  $investor = Investor::where('name', 'Investor Name')->first();
  $user->investor_id = $investor->id;
  $user->save();
  ```

### Issue: Dashboard shows no data
**Solution:**
- Verify the investor has linked cages in the database
- Check if samplings exist for the selected date range
- Ensure the investor_id is correctly set on cages and samplings

## Future Enhancements

Potential features for future development:

1. **Export Reports:** PDF/Excel export of dashboard data
2. **Email Notifications:** Alerts for low weights, high mortality
3. **Comparative Analysis:** Compare performance across multiple periods
4. **Financial Dashboard:** Revenue, expenses, ROI calculations
5. **Mobile App:** Native mobile application for investors
6. **Real-time Updates:** WebSocket-based live data updates
7. **Advanced Filters:** Filter by farmer, feed type, date ranges
8. **Custom Alerts:** Set thresholds for automated notifications

## Related Files

### Backend
- `app/Http/Controllers/InvestorDashboardController.php` - Main controller
- `app/Http/Middleware/EnsureUserIsInvestor.php` - Access control
- `app/Models/User.php` - User model with investor relationship
- `app/Models/Investor.php` - Investor model with farmers relationship
- `routes/web.php` - Route definitions
- `bootstrap/app.php` - Middleware registration

### Frontend
- `resources/js/pages/InvestorDashboard/Index.vue` - Main dashboard
- `resources/js/pages/InvestorDashboard/NoInvestor.vue` - Error page
- `resources/js/components/charts/SamplingTrendsChart.vue` - Charts

### Database
- `database/migrations/2026_01_21_000000_add_investor_id_to_users_table.php`
- `database/seeders/InvestorSeeder.php` - Seeder with investor users

## Support

For issues or questions:
1. Check this documentation
2. Review the related files listed above
3. Contact the development team
4. Check application logs: `storage/logs/laravel.log`
