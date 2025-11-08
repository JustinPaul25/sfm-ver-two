<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Sampling;
use App\Models\Investor;
use App\Models\Cage;

class SamplingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get investors and cages for creating samplings
        $investors = Investor::all();
        $cages = Cage::all();

        if ($investors->isEmpty() || $cages->isEmpty()) {
            $this->command->warn('Investors or Cages not found. Please run InvestorSeeder and CageSeeder first.');
            return;
        }

        // Get only cages with valid investors
        $validCages = $cages->filter(function($cage) use ($investors) {
            return $investors->contains('id', $cage->investor_id);
        })->values();
        
        // Create realistic historical data for the last 90 days with daily sampling
        // This will create nice trending data for the dashboard charts and AI predictions
        $dayCounter = 0;
        for ($daysAgo = 90; $daysAgo >= 0; $daysAgo--) {
            // Determine how many cages to sample per day based on recency
            // Recent days (0-30) have more data for trends
            // Older days (31-90) have less data for historical context
            if ($daysAgo <= 30) {
                $percentageToSample = rand(60, 90); // Sample 60-90% of cages in recent days
            } else {
                $percentageToSample = rand(20, 40); // Sample 20-40% of cages in older days
            }
            
            // Calculate how many cages to sample
            $cagesToSampleCount = max(2, round($validCages->count() * ($percentageToSample / 100)));
            
            // Randomly select cages to sample for this day
            $cagesToSample = $validCages->random(min($cagesToSampleCount, $validCages->count()));
            
            foreach ($cagesToSample as $cage) {
                // Add some mortality data (0-5% of fingerlings)
                $mortality = rand(1, 100) > 80 ? rand(10, 50) : 0;
                
                Sampling::create([
                    'investor_id' => $cage->investor_id,
                    'cage_no' => $cage->id,
                    'date_sampling' => now()->subDays($daysAgo)->format('Y-m-d'),
                    'doc' => 'DOC-' . now()->subDays($daysAgo)->format('Ymd') . '-' . str_pad($dayCounter % 100 + 1, 2, '0', STR_PAD_LEFT),
                    'mortality' => $mortality,
                ]);
                
                $dayCounter++;
            }
        }

        // Create specific recent samplings for testing with known investors
        $specificSamplings = [
            [
                'investor' => 'John Smith',
                'days_ago' => 7,
                'cage_index' => 0,
            ],
            [
                'investor' => 'Maria Garcia',
                'days_ago' => 5,
                'cage_index' => 0,
            ],
            [
                'investor' => 'Robert Johnson',
                'days_ago' => 3,
                'cage_index' => 0,
            ],
            [
                'investor' => 'Ana Santos',
                'days_ago' => 1,
                'cage_index' => 0,
            ],
        ];

        foreach ($specificSamplings as $samplingInfo) {
            $investor = $investors->where('name', $samplingInfo['investor'])->first();
            if ($investor) {
                $cage = $cages->where('investor_id', $investor->id)->values()->get($samplingInfo['cage_index']);
                if ($cage) {
                    Sampling::create([
                        'investor_id' => $investor->id,
                        'cage_no' => $cage->id,
                        'date_sampling' => now()->subDays($samplingInfo['days_ago'])->format('Y-m-d'),
                        'doc' => 'DOC-TEST-' . str_pad($samplingInfo['days_ago'], 3, '0', STR_PAD_LEFT),
                        'mortality' => rand(0, 30),
                    ]);
                }
            }
        }

        $this->command->info('Samplings seeded successfully!');
        $this->command->info("Created {$dayCounter} historical samplings over the last 90 days with realistic trends.");
    }
}
