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

        // Create samples for each sampling with realistic trends
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
            
            // Create 30 samples per sampling (standard practice)
            for ($sampleNo = 1; $sampleNo <= 30; $sampleNo++) {
                // Add realistic variation (fish don't all weigh exactly the same)
                // Use normal distribution-like variation (±15% around base weight)
                $variationPercent = (rand(-150, 150) / 1000); // -15% to +15%
                $weight = max(30, round($baseWeight * (1 + $variationPercent), 2)); // Minimum 30g

                Sample::create([
                    'investor_id' => $sampling->investor_id,
                    'sampling_id' => $sampling->id,
                    'sample_no' => $sampleNo,
                    'weight' => $weight,
                ]);
            }
        }

        $this->command->info('Samples seeded successfully!');
        $this->command->info('Created samples for ' . $samplings->count() . ' samplings with realistic weight trends.');
    }
}
