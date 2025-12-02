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

        // Create 5 samples for each sampling
        foreach ($samplings as $sampling) {
            // Determine base weight based on how old the fish are (sampling date)
            $daysOld = abs(now()->diffInDays($sampling->date_sampling));
            
            // Calculate realistic base weight (fish grow approximately 1-3g per day depending on age)
            // Younger fish grow faster
            if ($daysOld <= 30) {
                $baseWeight = 50 + ($daysOld * 2.5); // Grow 2.5g/day when young
            } elseif ($daysOld <= 60) {
                $baseWeight = 125 + (($daysOld - 30) * 2); // Grow 2g/day when medium
            } elseif ($daysOld <= 90) {
                $baseWeight = 185 + (($daysOld - 60) * 1.5); // Grow 1.5g/day when mature
            } else {
                $baseWeight = 230 + (($daysOld - 90) * 1); // Grow 1g/day when very mature
            }
            
            // Create 5 samples with realistic weight variations
            for ($i = 1; $i <= 5; $i++) {
                // Generate realistic weight with variation (similar to SamplingController)
                // Base weight range with variation
                $weightVariation = rand(-50, 50); // Add variation between -50g and +50g
                $weight = max(30, round($baseWeight + $weightVariation, 2)); // Minimum 30g
            
            // Calculate length and width based on weight (realistic fish proportions)
            // Using proportional relationships: length and width scale with weight
            // For fish: length typically 4-6x the cube root of weight, width typically 0.8-1.2x length
            // Converting to realistic cm measurements for grams
            $length = round(sqrt($weight / 10) * 2.5, 2); // Proportional to weight
            $width = round($length * 1.2, 2); // Width is typically 1.2x length
            
                // Create sample
            Sample::create([
                'investor_id' => $sampling->investor_id,
                'sampling_id' => $sampling->id,
                    'sample_no' => $i,
                'weight' => $weight,
                'length' => $length,
                'width' => $width,
            ]);
            }
        }

        $this->command->info('Samples seeded successfully!');
        $this->command->info('Created 5 samples per sampling for ' . $samplings->count() . ' samplings.');
    }
}
