# Feed Amount Per Cage (in KG) - Navigation Guide

## Feature Overview
This feature provides comprehensive reporting on **feed amounts per cage in kilograms**, tracking scheduled feeding amounts, actual consumption, and adherence rates over time.

## Available Features for Feed Amount Tracking

### 1. 🎯 PRIMARY FEATURE: Weekly Feeding Report
**The most comprehensive view for feed amounts per cage**

#### What This Feature Shows

| Metric | Description |
|--------|-------------|
| **Total Scheduled** | Total amount of feed scheduled for the period (kg) |
| **Total Consumed** | Actual amount of feed consumed (kg) |
| **Variance** | Difference between consumed and scheduled (kg) |
| **Adherence Rate** | Percentage of schedule adherence |
| **Daily Average** | Average daily feed consumption (kg) |
| **Daily Breakdown** | Day-by-day scheduled vs actual amounts (kg) |

#### How to Access

**Method 1: Via Sidebar Navigation**
1. Log in to your account
2. Click **"Feeding Reports"** in the left sidebar
3. The report will load with the current week's data

**Method 2: Direct URL**
```
/reports/feeding
```

**Breadcrumb Path:**
```
Dashboard → Feeding Reports
```

#### Key Features

##### Summary Dashboard
Shows overall statistics:
- Total Cages
- Cages with Schedules
- Active Schedules
- Total Scheduled Feed (kg)
- Total Consumed Feed (kg)
- Average Adherence (%)

##### Per-Cage Details
For each cage, you can see:
- **Cage Number** and basic info (Investor, Feed Type, Fingerlings count)
- **Scheduled Total**: Total kg scheduled for the period
- **Consumed Total**: Actual kg consumed
- **Variance**: +/- kg difference
- **Adherence Rate**: Percentage with color coding:
  - 🟢 Green: ≥90% (excellent)
  - 🟡 Yellow: 70-89% (good)
  - 🔴 Red: <70% (needs attention)
- **Daily Average**: Average kg consumed per day
- **Feeding Times**: Schedule for the day

##### Daily Breakdown (Expandable)
Click the ▶ button on any cage to see:
- Date-by-date feed amounts
- Scheduled vs Actual amounts for each day
- Daily variance
- Daily adherence rate
- Notes for each day

#### Filtering Options

Filter your report by:
- **Start Date**: Beginning of report period
- **End Date**: End of report period
- **Investor**: Filter by specific investor
- **Cage**: Filter by specific cage number

Default period: Current week (Monday to Sunday)

#### Export Features

- **📊 Export to Excel**: Download complete report with all data
- **🖨️ Print**: Print-friendly version of the report

---

### 2. 📊 ALTERNATIVE: Individual Cage View
**Detailed feed consumption for a specific cage**

#### How to Access

1. Go to **Cages** page (from sidebar)
2. Find the cage you want to view
3. Click the **👁️ View** button on the cage row
4. Or use direct URL: `/cages/{cage_id}/view`

#### What You'll See

**Daily Feed Consumption Section:**
- Total Days Tracked
- Total Feed Consumed (kg)
- Average Daily Feed (kg)

**Feed Consumption Table:**
- Day number
- Date
- Feed Amount (kg)
- Notes
- Actions (Edit/Delete)

**Management Features:**
- Add new feed consumption records
- Edit existing records
- Delete records
- Track day-by-day consumption

---

### 3. 📅 RELATED: Feeding Schedules
**View scheduled feed amounts per feeding time**

#### How to Access

1. Click **"Feeding Schedules"** in sidebar
2. Or go to: `/cages/feeding-schedules`

#### What You'll See

- Scheduled feed amounts for each feeding time
- Total daily amount per cage (kg)
- Feeding times and frequency
- Active/inactive status of schedules

---

## User Access by Role

| Role | Weekly Report | Cage View | Feeding Schedules |
|------|--------------|-----------|-------------------|
| **Admin** | ✅ Full Access | ✅ Full Access | ✅ Full Access |
| **Farmer** | ✅ Full Access | ✅ Full Access | ✅ Full Access |
| **Investor** | ✅ View Only | ✅ View Only | ✅ View Only |

## Data Flow

```
Feeding Schedule → Sets scheduled amounts (kg)
            ↓
Daily Feed Consumption → Records actual amounts (kg)
            ↓
Feeding Report → Analyzes and compares data
```

## Best Practices

### For Accurate Reporting:

1. **Create Feeding Schedules** first
   - Set appropriate feed amounts for each feeding time
   - Activate schedules for cages

2. **Record Daily Consumption**
   - Log actual feed amounts daily
   - Add notes for any variations
   - Use consistent measurement (kg)

3. **Review Weekly Reports**
   - Check adherence rates
   - Identify cages with low adherence
   - Adjust schedules as needed

## Understanding the Data

### Scheduled Amount
- Based on active feeding schedules
- Shows planned feed amount (kg)
- Multiplied by number of days in period

### Actual Amount
- From daily feed consumption records
- Shows real feed given (kg)
- Sum of all daily consumption entries

### Variance
- **Positive (+)**: Fed more than scheduled
- **Negative (-)**: Fed less than scheduled
- Helps identify over/under-feeding

### Adherence Rate
- `(Actual ÷ Scheduled) × 100`
- Measures how closely you follow the schedule
- Target: 90-100% for optimal results

## Tips & Troubleshooting

### ❓ No data showing in report?
- Ensure cages have active feeding schedules
- Check that feed consumption has been recorded
- Verify the date range includes recorded data

### ❓ Adherence rate is 0%?
- No feeding schedule set for that cage
- Create or activate a feeding schedule

### ❓ Want to see historical data?
- Adjust the Start Date and End Date filters
- Can view any historical period
- Export to Excel for long-term analysis

### ❓ Missing specific cage data?
- Use the Cage filter to search
- Check if cage has investor assigned (required)
- Verify cage exists in Cages list

## Quick Reference

### Primary Navigation Paths

| Feature | Path | Route Name |
|---------|------|------------|
| Weekly Feeding Report | `/reports/feeding` | `reports.feeding` |
| Cage View | `/cages/{id}/view` | `cages.view` |
| Feeding Schedules | `/cages/feeding-schedules` | `cages.feeding-schedules` |
| Cages List | `/cages` | `cages.index` |

### API Endpoints

| Endpoint | Purpose |
|----------|---------|
| `/reports/feeding/weekly` | Get report data (JSON) |
| `/reports/feeding/export-weekly` | Export to Excel |
| `/cages/{id}/feed-consumptions` | Get cage consumption data |
| `/cages/{id}/feed-consumptions` | Add consumption record (POST) |

## Related Documentation

- **Feeding Schedules**: How to create and manage feeding schedules
- **Cage Management**: How to set up cages with schedules
- **Per Cage Verification**: View fish size, weight, and population

---

**Last Updated**: January 20, 2026

**Recommended Feature**: 📊 **Weekly Feeding Report** (`/reports/feeding`)  
This is the most comprehensive view for tracking feed amounts per cage in kilograms.
