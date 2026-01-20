# Quick Start Guide - Running Updated Seeders

## Quick Commands

### Reset Database and Seed Everything
```bash
php artisan migrate:fresh --seed
```

### Seed Without Resetting (Add to Existing Data)
```bash
php artisan db:seed
```

### Run Specific Seeder Only
```bash
php artisan db:seed --class=SamplingSeeder
php artisan db:seed --class=SampleSeeder
php artisan db:seed --class=CageSeeder
php artisan db:seed --class=CageFeedConsumptionSeeder
```

---

## Expected Results

After running `php artisan migrate:fresh --seed`:

### User Accounts Created
- **Admin**: admin@sfm.com / admin123
- **Test User**: test@sfm.com / password
- **Manager**: manager@sfm.com / manager123
- **Additional Users**: john@sfm.com, jane@sfm.com, bob@sfm.com, alice@sfm.com, charlie@sfm.com

### Data Summary
- **Users**: ~11 users
- **Investors**: ~8-10 investors
- **Feed Types**: ~8 feed types
- **Cages**: ~33 cages
  - ~20 with assigned farmers
  - ~13 without farmers
- **Samplings**: ~2,500-3,000 samplings
  - 120 days of historical data
  - Every cage has recent samplings
- **Samples**: ~20,000-30,000 samples
  - 6-10 samples per sampling
  - Realistic weight, length, width
- **Feed Consumptions**: ~4,000+ records
  - 120 days per cage
  - Biomass-based calculations

### Seeding Time
**Expected Duration**: 2-5 minutes

Progress indicators will show:
```
Starting comprehensive seeding process...
Creating comprehensive historical sampling data for verification feature testing...
Creating guaranteed recent samplings for all cages...
Creating specific test samplings for edge cases...
Samplings seeded successfully!
Creating comprehensive sample data for verification feature...
Samples seeded successfully!
Cages seeded successfully!
Creating comprehensive historical feed consumption data...
Feed consumptions seeded successfully!
```

---

## Accessing the Verification Feature

### 1. Start Laravel Server
```bash
php artisan serve
```

### 2. Navigate to Verification Page
```
http://localhost:8000/cages/verification
```

Or if using Laravel Herd:
```
http://sfm.test/cages/verification
```

### 3. Login as Different Users

**As Admin (sees all cages):**
- Email: admin@sfm.com
- Password: admin123
- Should see all 33 cages

**As Farmer (sees only assigned cages):**
- Email: manager@sfm.com
- Password: manager123
- Should see only cages assigned to this farmer

**As Test User:**
- Email: test@sfm.com
- Password: password

---

## What You Should See in Verification

Each cage row displays:
- ✅ **Cage #**: Unique identifier (e.g., #1, #2, #3)
- ✅ **Investor**: Name (e.g., "John Smith", "Maria Garcia")
- ✅ **Feed Type**: Type name (e.g., "Starter Feed", "Grower Feed")
- ✅ **Size**: 
  - Length: X.X cm
  - Width: X.X cm
- ✅ **Weight**: X.X g (average from samples)
- ✅ **Number of Fish**:
  - Total: Initial fingerling count
  - Mortality: Cumulative deaths
  - Present: Total - Mortality
- ✅ **Last Sampling**: Date (should be within last 5 days)

---

## Testing Scenarios

### 1. Search Functionality
Search by:
- Cage number (e.g., "1", "5", "10")
- Investor name (e.g., "John", "Maria")
- Feed type (e.g., "Starter", "Grower")

### 2. Data Accuracy
Verify:
- All cages show recent sampling dates (≤7 days old)
- Weights are realistic (30g-250g range)
- Present stocks = Total - Mortality
- Length and width show (no "N/A")

### 3. Role-Based Access
Test:
- Admin sees all cages
- Farmers see only their assigned cages
- Search works correctly for filtered data

### 4. Edge Cases
Look for:
- Cages with zero mortality
- Cages with high mortality (80+ fish)
- Various cage sizes (500-3,000 fingerlings)
- Different feed types

---

## Troubleshooting

### Issue: "No cages found"
**Solution**: Make sure you're logged in and seeders ran successfully
```bash
php artisan migrate:fresh --seed
```

### Issue: "No sampling data"
**Solution**: Check if samplings were created
```bash
php artisan tinker
>>> App\Models\Sampling::count()
>>> App\Models\Sample::count()
```

### Issue: All values show "N/A"
**Solution**: Verify samples exist for recent samplings
```bash
php artisan tinker
>>> $sampling = App\Models\Sampling::latest('date_sampling')->first()
>>> $sampling->samples()->count()
```

### Issue: Seeding takes too long
**Normal**: Creating 20,000+ records takes 2-5 minutes
**If stuck**: Check database connection and disk space

---

## Database Inspection

### Using Tinker
```bash
php artisan tinker
```

```php
// Check cage with latest sampling
$cage = App\Models\Cage::first();
$latestSampling = $cage->samplings()->latest('date_sampling')->first();
$avgWeight = $latestSampling->samples->avg('weight');
echo "Cage {$cage->id}: Avg weight = {$avgWeight}g\n";

// Check mortality
echo "Mortality: {$latestSampling->mortality}\n";
echo "Present stocks: " . ($cage->number_of_fingerlings - $latestSampling->mortality);
```

### Using Database Client
```sql
-- Check verification data for a cage
SELECT 
    c.id as cage_id,
    c.number_of_fingerlings,
    s.mortality,
    s.date_sampling,
    AVG(sa.weight) as avg_weight,
    AVG(sa.length) as avg_length,
    AVG(sa.width) as avg_width
FROM cages c
JOIN samplings s ON s.cage_no = c.id
JOIN samples sa ON sa.sampling_id = s.id
WHERE s.date_sampling = (
    SELECT MAX(date_sampling) 
    FROM samplings 
    WHERE cage_no = c.id
)
GROUP BY c.id, c.number_of_fingerlings, s.mortality, s.date_sampling
LIMIT 5;
```

---

## Performance Notes

The seeders are optimized but create large datasets:
- Uses `firstOrCreate()` to prevent duplicates
- Batch creates samples efficiently
- Realistic calculations may take time

**Tips for faster seeding:**
- Use MySQL/PostgreSQL instead of SQLite
- Ensure database indexes are present
- Run on SSD storage

---

## Next Steps

After verifying the data:
1. ✅ Check the verification page displays correctly
2. ✅ Test search and filtering
3. ✅ Verify role-based access control
4. ✅ Check that calculations are accurate
5. ✅ Test with different user roles
6. ✅ Export or print verification reports (if feature exists)

---

## Additional Features to Test

If your app has these features:
- **Dashboard Charts**: Should show 120 days of trend data
- **Reports**: Should have comprehensive historical data
- **Feed Schedules**: Should correlate with feed consumption data
- **Sampling Reports**: Should show growth progression

All seeders now provide realistic data for these features!
