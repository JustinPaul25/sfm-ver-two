## Dashboard Charts Definition

This document defines the charts displayed on the dashboard, including their data sources, configuration, and behavior. It also outlines how to extend the dashboard with new charts.

### Data Flow Overview
- **Controller**: `app/Http/Controllers/DashboardController.php`
  - Endpoint: `GET /dashboard` (Inertia page)
  - Provides `analytics` payload to the Dashboard page, including:
    - `sampling_trends`: Array of `{ date: string (YYYY-MM-DD), count: number, avg_weight: number }`
    - `weight_stats`: Summary stats for samples within the selected period
    - Other aggregates: `top_investors`, `feed_type_usage`, `cage_performance`, `growth_metrics`, `date_range`
- **Page**: `resources/js/pages/Dashboard.vue`
  - Renders filters and cards
  - Passes `analytics.sampling_trends` to `SamplingTrendsChart`
  - Supports period switching (Today, This Week, Last 30 Days, This Month, Custom)

### Chart: Sampling Trends
- **Component**: `resources/js/components/charts/SamplingTrendsChart.vue`
- **Type**: Dual-axis line chart (Chart.js via `vue-chartjs`)
- **Purpose**: Visualize daily sampling activity and average fish weight over the selected time period.

#### Inputs
- `trends?: Array<{ date: string; count: number; avg_weight: number; }>`
  - Provided by the Dashboard page from `analytics.sampling_trends`
  - Derived in controller from `samplings` joined to `samples`, grouped by `DATE(date_sampling)`

#### Datasets
- Dataset 1
  - **Label**: `Samplings`
  - **Data**: `count` per day
  - **Axis**: `y` (left)
  - **Style**: Blue line with light fill
- Dataset 2
  - **Label**: `Avg Weight (g)`
  - **Data**: `avg_weight` per day
  - **Axis**: `y1` (right)
  - **Style**: Green line with light fill

#### Axes
- `x` (CategoryScale): Dates (localized string labels from `YYYY-MM-DD`)
- `y` (LinearScale, left): Samplings Count (begin at zero)
- `y1` (LinearScale, right): Weight in grams (begin at zero, tick suffix `g`)

#### Interactions and UI
- Legend at top with point-style labels
- Tooltips:
  - Shows dataset label and value
  - Adds `g` suffix when label includes “Weight”
- Responsive and maintains a fixed height container (`h-64` in template)
- “Show AI Predictions” toggle:
  - When enabled and if there are at least 10 data points, the chart appends predicted values (dashed lines) for the next 7 days for both datasets

#### AI Predictions
- **Library**: Lazily loads `@tensorflow/tfjs`
- **Model**: Lightweight sequential dense model trained per series (counts and weights) on-the-fly
- **Horizon**: 7 future data points
- **Display**:
  - `Predicted Samplings` (purple, dashed)
  - `Predicted Avg Weight (g)` (green, dashed, on `y1` axis)
- **Conditions**: Only rendered when `showPredictions` is enabled and `trends.length >= 10`

#### Behavior with Filters
- Period controls in `Dashboard.vue` update query params and re-fetch the page via Inertia.
- Custom date range applies when both `start_date` and `end_date` are set.
- The controller recomputes `analytics` for the selected date window; the chart updates via `watch` on `trends`.

### Related Non-Chart Analytics (Displayed on Dashboard)
- **Summary Cards**: Totals for investors, cages, feed types, samplings, samples (current period and cumulative).
- **Weight Statistics**: Average and maximum weight tiles for the selected period.
- Additional aggregates available from the controller (may be shown as lists/tables):
  - `top_investors` (by sampling count and total sample weight)
  - `feed_type_usage` (top feed types by cage count)
  - `cage_performance` (sampling count and average sample weight)
  - `growth_metrics` (period-over-period growth for samplings and average weight)

### Adding a New Chart
1. Compute and expose a new analytics series in `DashboardController@getAnalytics` (e.g., `analytics.my_new_series`).
2. Create a chart component in `resources/js/components/charts/` using `vue-chartjs` or your preferred library.
3. Import and render the component in `resources/js/pages/Dashboard.vue`, passing the new series via props.
4. Keep axis labeling, tooltips, and accessibility consistent with existing charts.
5. If AI predictions are desired, follow the pattern in `SamplingTrendsChart.vue` (lazy-load TF.js, train per-series, append dashed predicted dataset(s)).

### Testing
- Use the dashboard period filters to validate that datasets and axes update correctly.
- Verify tooltips show correct units and that predictions toggle behaves as expected.
- Ensure the chart gracefully renders “No data available” when the series is empty.


