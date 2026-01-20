# Seeder Updates for Verification Feature Testing

## Overview
Updated all seeders to create comprehensive historical data for testing the cage verification feature. The verification feature displays size, weight, and number of fish for each cage based on the latest sampling data.

## Changes Made

### 1. UserSeeder.php
**Enhanced to support comprehensive user management feature testing:**

- **Admin User**: System administrator with full access
  - Email: `admin@sfm.com` / Password: `admin123`
  - Role: admin, Status: active

- **Farmer Users**: Multiple farmer users for testing (8 users)
  - `test@sfm.com`, `manager@sfm.com`, `john@sfm.com`, `jane@sfm.com`, `bob@sfm.com`, `alice@sfm.com`, `charlie@sfm.com`
  - All with password: `password` (manager uses `manager123`)
  - All active and email verified
  - ⚠️ **Note**: "manager@sfm.com" is just a farmer, NOT a special role - same privileges as all other farmers

- **Inactive Users**: NEW - Added 2 inactive farmer users for testing
  - `inactive1@sfm.com` (farmer role, inactive)
  - `inactive2@sfm.com` (farmer role, inactive)
  - Tests user activation/deactivation feature
  - Cannot log in (blocked by middleware)

- **Unverified Users**: For email verification testing (2 users)
  - `unverified1@sfm.com`, `unverified2@sfm.com`
  - No email verification date
  - All active but not verified

**Statistics Output**: Enhanced seeder output showing:
- Total users count
- Active vs inactive users
- Role distribution (admins, farmers)

**Benefits for User Management Feature:**
- Tests both user roles (admin, farmer)
- Tests active/inactive status toggling
- Provides diverse user base for filtering and search
- Tests role-based access control
- Tests admin actions (cannot deactivate self)

---

### 2. CageSeeder.php
**Updated to include farmer assignments and align with user roles:**

- **Farmer Assignments**: 
  - Assigns farmers to ~55-60% of cages
  - Tests farmer-specific access controls
  - Some cages remain unassigned for admin-only visibility

- **Specific Test Cases**: 8 known cages with assigned farmers:
  - `manager@sfm.com`, `test@sfm.com`, `john@sfm.com`, `jane@sfm.com`, `bob@sfm.com`, `alice@sfm.com`
  - Admin can see all cages
  - Farmers can only see their assigned cages
  - Some cages have no farmer assigned

- **Realistic Cage Sizes**: 
  - Fingerling counts range from 500 to 3,000
  - Mix of starter, grower, and finisher feeds
  - Multiple investors assigned

**Benefits for Verification Feature:**
- Tests role-based data filtering (farmers see only their cages)
- Tests investor visibility (can view but not edit)
- Validates multi-user authorization scenarios
- Ensures proper cage ownership tracking

---

### 3. SamplingSeeder.php
**Enhanced to create realistic progressive mortality tracking and comprehensive historical data:**

- **Extended Historical Data**: Now creates 120 days of sampling data (up from 90 days)
- **Progressive Mortality**: Implements cumulative mortality tracking that:
  - Tracks total mortality per cage over time
  - Adds new mortality events in ~30% of samplings
  - Mortality events range from 5-50 fish (1-3% of cage population)
  - Cumulative mortality increases realistically over time

- **Guaranteed Recent Data**: Ensures ALL cages have recent samplings:
  - Creates samplings for days 1, 3, and 5 ago for every cage
  - Ensures verification feature always has fresh data to display
  - Prevents empty data issues in the UI

- **Edge Case Testing**: Adds specific test samplings with:
  - Zero mortality cases
  - Low mortality (15 fish)
  - Medium mortality (45 fish)
  - High mortality (80 fish)

**Benefits for Verification Feature:**
- Every cage is guaranteed to have recent sampling data
- Mortality calculations are realistic and cumulative
- Progressive data shows how fish populations change over time

---

### 4. SampleSeeder.php
**Completely rewritten to create realistic growth progression:**

- **Growth Tracking**: Tracks fish growth per cage from initial sampling
  - Start weight: 20-40g (realistic fingerling size)
  - Growth rate: 1.5-2.5g per day with decreasing rate as fish age
  - Implements realistic growth curves (fast → medium → slow → very slow)

- **Variable Sample Count**: Creates 6-10 samples per sampling (not fixed at 5)
  - Provides more statistical accuracy for averages
  - Reflects real-world sampling variability

- **Normal Distribution Variation**: 
  - Weight varies ±15% around mean (most fish near average, few outliers)
  - Simulates realistic population distribution

- **Allometric Relationships**:
  - Length calculated using allometric formula: `length ≈ 2.5 * weight^0.33`
  - Width proportional to length (1.1-1.3x multiplier)
  - Based on actual fish morphology

**Benefits for Verification Feature:**
- Accurate average weight, length, and width calculations
- Shows realistic fish growth over time
- Proper statistical sampling for verification data

---

### 5. CageFeedConsumptionSeeder.php
**Completely rewritten with realistic biomass-based calculations:**

- **Biomass-Based Feeding**: 
  - Calculates feed based on estimated fish biomass
  - Uses realistic feeding rates (3-5% of biomass per day)
  - Adjusts feeding rate as fish age

- **Growth-Adjusted Feed Amounts**:
  - Feed increases as fish grow (more biomass = more food)
  - Age-appropriate feeding rates:
    - Days 1-30: 5% of biomass
    - Days 31-60: 4% of biomass
    - Days 61-90: 3.5% of biomass
    - Days 91+: 3% of biomass

- **120 Days of Data**: Matches sampling timeframe
  - Complete historical feed consumption
  - Daily variation (±10%) for realism
  - Milestone notes (weekly, monthly reviews)

- **Edge Cases**:
  - Very low feeding (0.5kg)
  - Very high feeding (50kg)
  - Skipped feeding days (0kg)

**Benefits for Verification Feature:**
- Provides context for fish growth rates
- Helps correlate feeding amounts with fish size
- Shows feeding patterns that lead to current fish weights

---

## Data Statistics

After running the updated seeders:

### Users
- **Total**: 13 users
- **Active**: 11 users (1 admin, 8 farmers verified, 2 farmers unverified)
- **Inactive**: 2 users (2 farmers)
- **Roles**: 1 admin, 12 farmers
- **Email Verified**: 11 users
- **Unverified**: 2 users

### Samplings
- **Total**: ~2,500-3,000 samplings
- **Historical**: 120 days per cage with variable coverage
- **Recent**: Every cage has samplings from last 7 days
- **Progressive mortality**: Cumulative tracking over time

### Samples
- **Total**: ~20,000-30,000 samples
- **Per Sampling**: 6-10 samples for statistical accuracy
- **Growth**: Realistic progression from 20-40g to 200+ grams
- **Dimensions**: Allometrically calculated length and width

### Feed Consumptions
- **Total**: ~4,000+ records
- **Per Cage**: 120 days of feeding data
- **Amounts**: Biomass-based, realistic increases
- **Pattern**: 3-5% of biomass per day

### Cages
- **Total**: 33 cages
- **With Farmers**: ~20 cages (60%)
- **Without Farmers**: ~13 cages (40%)
- **Size Range**: 500-3,000 fingerlings

---

## Testing the Verification Feature

### What the Verification Feature Shows:
1. **Cage Number** - Unique identifier
2. **Investor** - Name from relationship
3. **Feed Type** - Type from relationship
4. **Size** - Average length and width from latest sampling
5. **Weight** - Average weight from latest sampling
6. **Number of Fish** - Initial count, mortality, and present stocks
7. **Last Sampling Date** - Date of most recent sampling

### Test Scenarios:
1. ✅ **All cages have data** - Every cage has recent sampling
2. ✅ **Realistic weights** - 30g-250g based on age
3. ✅ **Progressive mortality** - Cumulative over time
4. ✅ **Present stocks calculation** - Initial - mortality
5. ✅ **Recent data** - Latest sampling within 7 days
6. ✅ **Edge cases** - Zero, low, medium, high mortality
7. ✅ **Role-based filtering** - Farmers see only their cages

### How to Test:
```bash
# Reset database and run seeders
php artisan migrate:fresh --seed

# Navigate to verification page
http://your-domain/cages/verification

# Test as different users:
# - Admin (admin@sfm.com / admin123): sees all cages, can manage all users
# - Farmer (manager@sfm.com / manager123): sees only assigned cages, can create/edit
# - Farmer (test@sfm.com / password): sees only assigned cages
# - Inactive users (inactive1@sfm.com / password): cannot log in (blocked by middleware)
```

### User Management Testing:
```bash
# Log in as admin
# Navigate to http://your-domain/users

# Test features:
# - View user statistics (13 total, 11 active, 2 inactive)
# - Search users by name or email
# - Filter by role (admin, farmer)
# - Filter by status (active, inactive)
# - Create new user
# - Edit user details
# - Change user role
# - Toggle user active/inactive status
# - Delete user (except yourself)
```

---

## Key Improvements

### 1. **Data Completeness**
- Every cage is guaranteed to have recent data
- No "N/A" or missing values in verification table
- Complete historical context for trends

### 2. **Realistic Progression**
- Fish weights increase naturally over time
- Mortality accumulates progressively
- Feed consumption grows with biomass

### 3. **Statistical Accuracy**
- 6-10 samples per sampling (not fixed)
- Normal distribution around mean weights
- Better averages for verification display

### 4. **Edge Case Coverage**
- Zero mortality scenarios
- High mortality scenarios (80+ fish)
- Recent vs. old samplings
- Various cage sizes (500-3,000 fingerlings)

### 5. **Testing Flexibility**
- Specific named investors and cages
- Farmer role assignments
- Date-based filtering capabilities
- Search functionality testing

---

## Database Seeding Time

**Estimated time**: 2-5 minutes depending on system performance

The increased data volume is intentional:
- Provides realistic performance testing
- Tests pagination and search
- Ensures UI handles large datasets
- Simulates production-like data volume

---

## Verification Feature Calculations

The verification feature calculates:

```php
// Latest sampling per cage
$latestSampling = Sampling::where('cage_no', $cage->id)
    ->orderBy('date_sampling', 'desc')
    ->first();

// Average weight from samples
$avgWeight = $latestSampling->samples->avg('weight');

// Average length from samples (non-null)
$avgLength = $latestSampling->samples->whereNotNull('length')->avg('length');

// Average width from samples (non-null)
$avgWidth = $latestSampling->samples->whereNotNull('width')->avg('width');

// Present stocks
$presentStocks = $cage->number_of_fingerlings - $latestSampling->mortality;
```

The seeder data ensures all these calculations return realistic values.

---

## Future Enhancements

Potential improvements for seeders:
1. Add seasonal variations in growth rates
2. Simulate disease outbreaks (sudden mortality spikes)
3. Add water quality factors affecting growth
4. Temperature-based growth adjustments
5. Feed conversion ratio (FCR) tracking

---

## Conclusion

The updated seeders provide comprehensive, realistic data for thoroughly testing the cage verification feature. Every aspect of the feature—from displaying basic cage info to calculating complex averages and stock levels—is now testable with meaningful, production-like data.
