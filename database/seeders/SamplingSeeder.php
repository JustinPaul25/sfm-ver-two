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
        
        $this->command->info('Creating comprehensive historical sampling data for verification feature testing...');
        
        // Create realistic historical data for the last 120 days with daily sampling
        // This will create nice trending data for the dashboard charts, verification feature, and AI predictions
        $dayCounter = 0;
        $samplingsBycage = []; // Track samplings per cage for mortality progression
        
        for ($daysAgo = 120; $daysAgo >= 0; $daysAgo--) {
            // Determine how many cages to sample per day based on recency
            // Recent days (0-30) have more data for trends and verification
            // Older days (31-120) have less data for historical context
            if ($daysAgo <= 30) {
                $percentageToSample = rand(70, 95); // Sample 70-95% of cages in recent days
            } elseif ($daysAgo <= 60) {
                $percentageToSample = rand(40, 60); // Sample 40-60% of cages in medium days
            } else {
                $percentageToSample = rand(20, 40); // Sample 20-40% of cages in older days
            }
            
            // Calculate how many cages to sample
            $cagesToSampleCount = max(2, round($validCages->count() * ($percentageToSample / 100)));
            
            // Randomly select cages to sample for this day
            $cagesToSample = $validCages->random(min($cagesToSampleCount, $validCages->count()));
            
            foreach ($cagesToSample as $cage) {
                // Initialize tracking array if not exists
                if (!isset($samplingsBycage[$cage->id])) {
                    $samplingsBycage[$cage->id] = [
                        'total_mortality' => 0,
                        'sampling_count' => 0,
                    ];
                }
                
                // Calculate realistic progressive mortality
                // Mortality accumulates over time and recent samplings show cumulative mortality
                $baselineMortality = $samplingsBycage[$cage->id]['total_mortality'];
                
                // Add new mortality events (happens in ~30% of samplings)
                $newMortality = 0;
                if (rand(1, 100) <= 30) {
                    // Mortality events range from 5-50 fish depending on cage size
                    $mortalityPercent = rand(1, 3) / 100; // 1-3% mortality per event
                    $newMortality = (int) round($cage->number_of_fingerlings * $mortalityPercent);
                    $newMortality = max(5, min(50, $newMortality)); // Between 5-50 fish
                }
                
                // Update cumulative mortality
                $cumulativeMortality = $baselineMortality + $newMortality;
                $samplingsBycage[$cage->id]['total_mortality'] = $cumulativeMortality;
                $samplingsBycage[$cage->id]['sampling_count']++;
                
                Sampling::create([
                    'investor_id' => $cage->investor_id,
                    'cage_no' => $cage->id,
                    'date_sampling' => now()->subDays($daysAgo)->format('Y-m-d'),
                    'doc' => 'DOC-' . now()->subDays($daysAgo)->format('Ymd') . '-' . str_pad($dayCounter % 100 + 1, 2, '0', STR_PAD_LEFT),
                    'mortality' => $cumulativeMortality,
                    'feed_types_id' => $cage->feed_types_id,
                ]);
                
                $dayCounter++;
            }
        }

        // Create guaranteed recent samplings for ALL cages to ensure verification data is complete
        // This ensures every cage has recent data for the verification feature
        $this->command->info('Creating guaranteed recent samplings for all cages...');
        
        foreach ($validCages as $cage) {
            // Ensure each cage has at least 3 recent samplings (last 7 days)
            $recentDays = [1, 3, 5]; // 1, 3, and 5 days ago
            
            foreach ($recentDays as $daysAgo) {
                // Check if cage already has sampling for this day
                $existingSampling = Sampling::where('cage_no', $cage->id)
                    ->where('date_sampling', now()->subDays($daysAgo)->format('Y-m-d'))
                    ->first();
                
                if (!$existingSampling) {
                    // Initialize tracking if not exists
                    if (!isset($samplingsBycage[$cage->id])) {
                        $samplingsBycage[$cage->id] = [
                            'total_mortality' => 0,
                            'sampling_count' => 0,
                        ];
                    }
                    
                    // Get the most recent mortality for this cage
                    $latestSampling = Sampling::where('cage_no', $cage->id)
                        ->orderBy('date_sampling', 'desc')
                        ->first();
                    
                    $currentMortality = $latestSampling ? $latestSampling->mortality : 0;
                    
                    // Small chance to add new mortality in recent days
                    if (rand(1, 100) <= 20) {
                        $mortalityPercent = rand(1, 2) / 100;
                        $newMortality = (int) round($cage->number_of_fingerlings * $mortalityPercent);
                        $newMortality = max(5, min(30, $newMortality));
                        $currentMortality += $newMortality;
                    }
                    
                    Sampling::create([
                        'investor_id' => $cage->investor_id,
                        'cage_no' => $cage->id,
                        'date_sampling' => now()->subDays($daysAgo)->format('Y-m-d'),
                        'doc' => 'DOC-RECENT-' . $cage->id . '-' . $daysAgo,
                        'mortality' => $currentMortality,
                        'feed_types_id' => $cage->feed_types_id,
                    ]);
                    
                    $dayCounter++;
                }
            }
        }

        // Create specific test samplings for edge cases and verification testing
        $this->command->info('Creating specific test samplings for edge cases...');
        
        $specificSamplings = [
            [
                'investor' => 'John Smith',
                'days_ago' => 0, // Today
                'cage_index' => 0,
                'mortality' => 0, // No mortality
            ],
            [
                'investor' => 'Maria Garcia',
                'days_ago' => 2,
                'cage_index' => 0,
                'mortality' => 15, // Low mortality
            ],
            [
                'investor' => 'Robert Johnson',
                'days_ago' => 1,
                'cage_index' => 0,
                'mortality' => 45, // Medium mortality
            ],
            [
                'investor' => 'Ana Santos',
                'days_ago' => 0, // Today
                'cage_index' => 0,
                'mortality' => 80, // High mortality for testing
            ],
        ];

        foreach ($specificSamplings as $samplingInfo) {
            $investor = $investors->where('name', $samplingInfo['investor'])->first();
            if ($investor) {
                $cage = $cages->where('investor_id', $investor->id)->values()->get($samplingInfo['cage_index']);
                if ($cage) {
                    // Delete existing sampling for this day if exists
                    Sampling::where('cage_no', $cage->id)
                        ->where('date_sampling', now()->subDays($samplingInfo['days_ago'])->format('Y-m-d'))
                        ->delete();
                    
                    Sampling::create([
                        'investor_id' => $investor->id,
                        'cage_no' => $cage->id,
                        'date_sampling' => now()->subDays($samplingInfo['days_ago'])->format('Y-m-d'),
                        'doc' => 'DOC-TEST-EDGE-' . str_pad($samplingInfo['days_ago'], 3, '0', STR_PAD_LEFT),
                        'mortality' => $samplingInfo['mortality'],
                        'feed_types_id' => $cage->feed_types_id,
                    ]);
                }
            }
        }

        $totalSamplings = Sampling::count();
        $this->command->info('Samplings seeded successfully!');
        $this->command->info("Created {$totalSamplings} total samplings:");
        $this->command->info("  - Historical samplings over 120 days with realistic progressive mortality");
        $this->command->info("  - Guaranteed recent samplings for all cages (verification feature)");
        $this->command->info("  - Specific test samplings for edge cases");
    }
}
