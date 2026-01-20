<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Cage;
use App\Models\CageFeedConsumption;

class CageFeedConsumptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get existing cages or create some if none exist
        $cages = Cage::all();
        
        if ($cages->isEmpty()) {
            // Create some cages if none exist
            $this->command->warn('No cages found. Please run CageSeeder first.');
            return;
        }

        $this->command->info('Creating comprehensive historical feed consumption data...');
        
        $totalConsumptions = 0;
        
        // Create realistic feed consumption data for all cages
        // This matches the verification feature's timeframe
        foreach ($cages as $cage) {
            // Create 120 days of feed consumption with realistic growth patterns
            // This aligns with the 120 days of sampling data
            for ($day = 1; $day <= 120; $day++) {
                // Calculate realistic feed amount based on:
                // 1. Number of fingerlings in cage
                // 2. Age of fish (older = more food)
                // 3. Feed conversion ratio (FCR) typical values
                
                // Base feeding rate: ~3-5% of biomass per day for tilapia
                // As fish grow, feeding rate decreases slightly as % but increases in absolute amount
                
                // Estimate average fish weight based on age (day number)
                // Start at 30g, grow by realistic amount per day
                if ($day <= 30) {
                    $avgFishWeight = 30 + ($day * 2); // Fast initial growth
                } elseif ($day <= 60) {
                    $avgFishWeight = 90 + (($day - 30) * 1.5);
                } elseif ($day <= 90) {
                    $avgFishWeight = 135 + (($day - 60) * 1.2);
                } else {
                    $avgFishWeight = 171 + (($day - 90) * 0.8);
                }
                
                // Calculate total biomass (kg)
                $totalBiomass = ($cage->number_of_fingerlings * $avgFishWeight) / 1000;
                
                // Feeding rate decreases as fish age (% of biomass)
                if ($day <= 30) {
                    $feedingRate = 0.05; // 5% for young fish
                } elseif ($day <= 60) {
                    $feedingRate = 0.04; // 4% for growing fish
                } elseif ($day <= 90) {
                    $feedingRate = 0.035; // 3.5% for mature fish
                } else {
                    $feedingRate = 0.03; // 3% for older fish
                }
                
                // Calculate base feed amount
                $baseFeedAmount = $totalBiomass * $feedingRate;
                
                // Add realistic daily variation (±10%)
                $variation = rand(-10, 10) / 100;
                $feedAmount = $baseFeedAmount * (1 + $variation);
                
                // Ensure minimum feed amount
                $feedAmount = max(0.5, round($feedAmount, 2));
                
                // Calculate consumption date
                $consumptionDate = now()->subDays(120 - $day);
                
                // Add notes for important milestones
                $notes = null;
                if ($day % 30 === 0) {
                    $notes = "Monthly feeding review - Day {$day}";
                } elseif ($day % 7 === 0) {
                    $notes = "Weekly check";
                }
                
                CageFeedConsumption::firstOrCreate([
                    'cage_id' => $cage->id,
                    'day_number' => $day,
                ], [
                    'feed_amount' => $feedAmount,
                    'consumption_date' => $consumptionDate,
                    'notes' => $notes,
                ]);
                
                $totalConsumptions++;
            }
        }
        
        // Create some edge case feed consumptions for specific testing
        $this->command->info('Creating edge case feed consumption data...');
        
        // Test cases: very low and very high feed amounts
        if ($cages->count() >= 3) {
            // Edge case 1: Very low feeding (potential issue)
            $testCage1 = $cages->random();
            CageFeedConsumption::firstOrCreate([
                'cage_id' => $testCage1->id,
                'day_number' => 150,
            ], [
                'feed_amount' => 0.5,
                'consumption_date' => now()->subDays(5),
                'notes' => 'Low feeding test case',
            ]);
            $totalConsumptions++;
            
            // Edge case 2: Very high feeding (aggressive strategy)
            $testCage2 = $cages->random();
            CageFeedConsumption::firstOrCreate([
                'cage_id' => $testCage2->id,
                'day_number' => 151,
            ], [
                'feed_amount' => 50.0,
                'consumption_date' => now()->subDays(3),
                'notes' => 'High feeding test case',
            ]);
            $totalConsumptions++;
            
            // Edge case 3: Skipped feeding day (zero or missing)
            $testCage3 = $cages->random();
            CageFeedConsumption::firstOrCreate([
                'cage_id' => $testCage3->id,
                'day_number' => 152,
            ], [
                'feed_amount' => 0.0,
                'consumption_date' => now()->subDays(2),
                'notes' => 'Skipped feeding - maintenance day',
            ]);
            $totalConsumptions++;
        }
        
        $this->command->info('Feed consumptions seeded successfully!');
        $this->command->info("Created {$totalConsumptions} total feed consumption records:");
        $this->command->info("  - 120 days of historical data per cage");
        $this->command->info("  - Realistic biomass-based feeding calculations");
        $this->command->info("  - Age-appropriate feeding rates (3-5% of biomass)");
        $this->command->info("  - Daily variation to simulate real conditions");
        $this->command->info("  - Edge cases for testing");
    }
}
