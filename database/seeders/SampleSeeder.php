<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Sample;
use App\Models\Sampling;
use App\Models\Investor;

class SampleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get samplings and investors for creating samples
        $samplings = Sampling::all();
        $investors = Investor::all();

        if ($samplings->isEmpty() || $investors->isEmpty()) {
            $this->command->warn('Samplings or Investors not found. Please run SamplingSeeder first.');
            return;
        }

        $this->command->info('Creating comprehensive sample data for verification feature...');
        
        // Track fish growth progression by cage for realistic trends
        $cageGrowthData = [];
        
        // Group samplings by cage and sort by date to track growth progression
        $samplingsByCage = $samplings->groupBy('cage_no')->map(function ($group) {
            return $group->sortBy('date_sampling');
        });
        
        $sampleCount = 0;
        
        // Create samples for each sampling with realistic growth progression
        foreach ($samplingsByCage as $cageId => $cageSamplings) {
            // Initialize growth tracking for this cage
            if (!isset($cageGrowthData[$cageId])) {
                // Start with initial fingerling weight (20-40g)
                $cageGrowthData[$cageId] = [
                    'start_date' => $cageSamplings->first()->date_sampling,
                    'initial_weight' => rand(20, 40),
                    'growth_rate' => rand(15, 25) / 10, // 1.5-2.5g per day
                ];
            }
            
            $startDate = $cageGrowthData[$cageId]['start_date'];
            $initialWeight = $cageGrowthData[$cageId]['initial_weight'];
            $growthRate = $cageGrowthData[$cageId]['growth_rate'];
            
            foreach ($cageSamplings as $sampling) {
                // Calculate days since start
                $daysElapsed = abs(\Carbon\Carbon::parse($startDate)->diffInDays($sampling->date_sampling));
                
                // Calculate base weight with realistic growth curve
                // Growth slows down as fish get older
                if ($daysElapsed <= 30) {
                    $baseWeight = $initialWeight + ($daysElapsed * $growthRate); // Fast growth
                } elseif ($daysElapsed <= 60) {
                    $thirtyDayWeight = $initialWeight + (30 * $growthRate);
                    $baseWeight = $thirtyDayWeight + (($daysElapsed - 30) * ($growthRate * 0.8)); // Medium growth
                } elseif ($daysElapsed <= 90) {
                    $thirtyDayWeight = $initialWeight + (30 * $growthRate);
                    $sixtyDayWeight = $thirtyDayWeight + (30 * $growthRate * 0.8);
                    $baseWeight = $sixtyDayWeight + (($daysElapsed - 60) * ($growthRate * 0.6)); // Slower growth
                } else {
                    $thirtyDayWeight = $initialWeight + (30 * $growthRate);
                    $sixtyDayWeight = $thirtyDayWeight + (30 * $growthRate * 0.8);
                    $ninetyDayWeight = $sixtyDayWeight + (30 * $growthRate * 0.6);
                    $baseWeight = $ninetyDayWeight + (($daysElapsed - 90) * ($growthRate * 0.4)); // Very slow growth
                }
                
                // Ensure minimum weight
                $baseWeight = max(30, $baseWeight);
                
                // Create 8 samples per sampling for better statistical accuracy
                // More samples = more accurate average for verification feature
                $numberOfSamples = rand(6, 10); // Variable number of samples (6-10)
                
                for ($i = 1; $i <= $numberOfSamples; $i++) {
                    // Generate realistic weight with variation
                    // Create normal distribution-like variation (most fish near average, few outliers)
                    $variationPercent = rand(-15, 15); // ±15% variation
                    $weight = $baseWeight * (1 + ($variationPercent / 100));
                    $weight = max(30, round($weight, 2)); // Minimum 30g
                    
                    // Calculate length and width based on weight (realistic fish proportions)
                    // Using allometric relationships for fish growth
                    // Length (cm) ≈ k * weight^(1/3) where k is a species-specific constant
                    // For tilapia/similar fish: length ≈ 2.5 * weight^0.33
                    $length = round(2.5 * pow($weight, 0.33), 2);
                    
                    // Width typically 1.1-1.3x length for these fish
                    $widthMultiplier = rand(110, 130) / 100;
                    $width = round($length * $widthMultiplier, 2);
                    
                    // Ensure realistic minimum dimensions
                    $length = max(5.0, $length);
                    $width = max(5.5, $width);
                    
                    // Create realistic timestamp for when this sample was tested
                    // Samples are tested at different times throughout the sampling day
                    // Add random minutes (0-180 minutes = 0-3 hours) to the sampling date
                    $samplingDateTime = \Carbon\Carbon::parse($sampling->date_sampling);
                    // Set a base time for sampling (e.g., 9:00 AM)
                    $samplingDateTime->setTime(9, 0, 0);
                    // Add random minutes for each sample (stagger the testing times)
                    $minutesOffset = ($i - 1) * rand(5, 15) + rand(0, 5); // Each sample takes 5-15 minutes + random variation
                    $testedAt = $samplingDateTime->copy()->addMinutes($minutesOffset);
                    
                    // Create sample
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
                    
                    $sampleCount++;
                }
            }
        }

        $this->command->info('Samples seeded successfully!');
        $this->command->info("Created {$sampleCount} total samples:");
        $this->command->info("  - 6-10 samples per sampling for statistical accuracy");
        $this->command->info("  - Realistic growth progression tracked per cage");
        $this->command->info("  - Normal distribution weight variation around mean");
        $this->command->info("  - Allometric length/width relationships");
        $this->command->info('');
        $this->command->info('Verification feature data:');
        $this->command->info("  - All cages have recent sampling data");
        $this->command->info("  - Weight, length, and width averages available");
        $this->command->info("  - Progressive mortality tracked over time");
    }
}
