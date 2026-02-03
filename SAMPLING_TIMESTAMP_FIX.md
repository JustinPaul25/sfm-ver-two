# Sampling Timestamp Fix

## Issue Summary

Two issues were identified in the sampling report:

1. **All samples had identical timestamps** - All samples created by the seeder had the same `created_at` timestamp because they were created in a batch
2. **Untested samples showed timestamps** - Samples without weight/length/width data (not yet tested) were still showing a "Tested At" timestamp

## Changes Made

### 1. SampleSeeder.php

**Location**: `database/seeders/SampleSeeder.php`

**Change**: Added realistic timestamp generation for each sample

```php
// Create realistic timestamp for when this sample was tested
// Samples are tested at different times throughout the sampling day
// Add random minutes (0-180 minutes = 0-3 hours) to the sampling date
$samplingDateTime = \Carbon\Carbon::parse($sampling->date_sampling);
// Set a base time for sampling (e.g., 9:00 AM)
$samplingDateTime->setTime(9, 0, 0);
// Add random minutes for each sample (stagger the testing times)
$minutesOffset = ($i - 1) * rand(5, 15) + rand(0, 5); // Each sample takes 5-15 minutes + random variation
$testedAt = $samplingDateTime->copy()->addMinutes($minutesOffset);

// Create sample with specific timestamps
Sample::create([
    'investor_id' => $sampling->investor_id,
    'sampling_id' => $sampling->id,
    'sample_no' => $i,
    'weight' => $weight,
    'length' => $length,
    'width' => $width,
    'created_at' => $testedAt,
    'updated_at' => $testedAt,
]);
```

**Result**: Each sample now has a unique timestamp, staggered by 5-15 minutes to simulate realistic testing times.

### 2. SamplingReport.vue

**Location**: `resources/js/pages/Samplings/SamplingReport.vue`

**Changes**:

#### Change 1: Only show `testedAt` for samples with weight data
```typescript
return sortedSamples.map(sample => ({
    no: sample.sample_no || '',
    weight: roundToTenth(sample.weight),
    length: sample.length ? roundToTenth(sample.length) : null,
    width: sample.width ? roundToTenth(sample.width) : null,
    type: tooltipData.value.type,
    // Only show tested_at if the sample has actual weight data (has been tested)
    testedAt: sample.weight ? formatTimestamp(sample.created_at) : null,
}));
```

#### Change 2: Display `-` when `testedAt` is null
```vue
<td class="px-4 py-2">{{ row.testedAt || '-' }}</td>
```

**Result**: Samples without weight data now show `-` instead of a timestamp in the "Tested At" column.

## How to Apply the Fix

1. **Re-run the seeder** to regenerate samples with proper timestamps:
   ```bash
   php artisan db:seed --class=SampleSeeder
   ```

2. **Or re-run all seeders**:
   ```bash
   php artisan migrate:fresh --seed
   ```

3. **Rebuild frontend assets**:
   ```bash
   npm run build
   ```
   Or for development:
   ```bash
   npm run dev
   ```

## Expected Behavior After Fix

1. ✅ Each sample has a unique timestamp (staggered by 5-15 minutes)
2. ✅ Samples without weight data show `-` in the "Tested At" column
3. ✅ Samples with weight data show their actual testing timestamp
4. ✅ Timestamps are realistic and not identical

## Example

**Before**:
```
No. | Weight | Length | Width | Type   | Tested At
1   | 52     | 3.3    | 3.3   | Grower | Jan 29, 2026, 2:24 PM
2   | 51     | 2.0    | 8.5   | Grower | Jan 29, 2026, 2:24 PM
3   | -      | -      | -     | Grower | Jan 29, 2026, 2:24 PM
```

**After**:
```
No. | Weight | Length | Width | Type   | Tested At
1   | 52     | 3.3    | 3.3   | Grower | Jan 29, 2026, 9:05 AM
2   | 51     | 2.0    | 8.5   | Grower | Jan 29, 2026, 9:17 AM
3   | -      | -      | -     | Grower | -
```

## Technical Notes

- The `tested_at` field doesn't exist in the database schema - it uses the `created_at` timestamp from the samples table
- The seeder now creates samples with timestamps starting at 9:00 AM on the sampling date
- Each subsequent sample is tested 5-15 minutes after the previous one (with random variation)
- This simulates realistic field conditions where samples are tested sequentially
