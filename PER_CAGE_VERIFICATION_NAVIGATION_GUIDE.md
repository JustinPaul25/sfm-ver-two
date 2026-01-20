# Per Cage Verification - Navigation Guide

## Feature Overview
The **Per Cage Verification** feature provides a comprehensive view of all cages with detailed information about fish size, weight, and population counts based on the latest sampling data.

## What This Feature Shows

The verification table displays the following information for each cage:

| Column | Description |
|--------|-------------|
| **Cage #** | Unique cage identifier |
| **Investor** | Name of the investor associated with the cage |
| **Feed Type** | Type of feed being used |
| **Size** | Fish dimensions (Length and Width in centimeters) |
| **Weight** | Average fish weight in grams |
| **Number of Fish** | Three values:<br>• Total: Initial number of fingerlings<br>• Mortality: Number of fish that died<br>• Present: Current stock count |
| **Last Sampling** | Date of the most recent sampling data |

## How to Access This Feature

### Method 1: From the Cages Page (Recommended)

1. **Log in** to your account
2. **Navigate to Cages**:
   - Click on **"Cages"** in the left sidebar menu
   - Or go directly to: `/cages`
3. **Click the "Per Cage Verification" button**:
   - Located in the top-right corner of the page
   - It's an outlined button next to the "Create Cage" button

### Method 2: Direct URL Access

Simply navigate to:
```
/cages/verification
```

## User Access by Role

| Role | Can Access? | Notes |
|------|-------------|-------|
| **Admin** | ✅ Yes | Full access to all verification data |
| **Farmer** | ✅ Yes | Can view all cages |
| **Investor** | ✅ Yes | Can view all cages (read-only) |

## Feature Capabilities

### Search & Filter
- Search by:
  - Cage number
  - Investor name
  - Feed type
- Real-time filtering as you type

### Refresh Data
- Click the "🔄 Refresh" button to reload the latest verification data

### Data Display
- Shows all cages with their latest sampling information
- Displays "N/A" for cages without sampling data
- Shows "No sampling" for cages that haven't been sampled yet

## Visual Navigation Path

```
Login → Dashboard → Cages (sidebar) → Per Cage Verification (button)
```

or

```
Login → Dashboard → Direct URL: /cages/verification
```

## Technical Details

- **Route Name**: `cages.verification`
- **Component**: `resources/js/pages/Cages/Verification.vue`
- **API Endpoint**: `/cages/verification/data`
- **Controller**: `CageController@verification` and `CageController@verificationData`

## Breadcrumb Navigation

When on the verification page, you'll see:
```
Dashboard > Cages > Per Cage Verification
```

You can click any breadcrumb to navigate back.

## Data Source

The verification data is pulled from:
- **Cages table**: Basic cage information
- **Samplings & Samples tables**: Latest fish measurements (size, weight)
- **Investors table**: Investor names
- **Feed Types table**: Feed type information

The system automatically calculates:
- Average length, width, and weight from the latest sampling
- Present stock count (fingerlings - mortality)

## Tips

1. **No Data Showing?** 
   - Ensure sampling has been performed for the cages
   - Check that sample measurements have been recorded

2. **Outdated Information?**
   - Click the refresh button to get the latest data
   - Verify that recent samplings have been saved

3. **Can't Find a Specific Cage?**
   - Use the search box to filter by cage number, investor, or feed type
   - Check if the cage exists in the Cages list first

## Related Features

- **Cages Management**: `/cages` - Create and manage cages
- **Sampling Reports**: `/samplings` - Record fish sampling data
- **Overall Reports**: `/reports/overall` - Comprehensive reporting

---

**Last Updated**: January 20, 2026
