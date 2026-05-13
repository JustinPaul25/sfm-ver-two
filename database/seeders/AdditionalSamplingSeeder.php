<?php

namespace Database\Seeders;

use App\Models\Cage;
use App\Models\Investor;
use App\Models\Sample;
use App\Models\Sampling;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class AdditionalSamplingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Adds 2 samplings per month for March, April, and May for each existing investor.
     * Each sampling has 5 samples with realistic growing weights.
     */
    public function run(): void
    {
        DB::transaction(function () {
            // Get all investors (IDs 2-13, excluding 6 based on the image)
            $investors = Investor::whereIn('id', [2, 3, 4, 5, 7, 8, 9, 10, 11, 12, 13])->get();

            // Clean up existing samples and samplings for these investors
            $this->command->info('Cleaning up existing samples and samplings...');
            
            foreach ($investors as $investor) {
                // Delete samples associated with this investor's samplings
                $samplingIds = Sampling::where('investor_id', $investor->id)->pluck('id');
                Sample::whereIn('sampling_id', $samplingIds)->delete();
                
                // Delete samplings for this investor
                Sampling::where('investor_id', $investor->id)->delete();
                
                $this->command->info("Cleaned data for investor: {$investor->name} (ID: {$investor->id})");
            }

            // Define sampling dates: 2 per month for March, April, May
            $samplingDates = [
                ['2026-03-20', '2026-03-27'],  // March
                ['2026-04-10', '2026-04-24'],  // April
                ['2026-05-08', '2026-05-22'],  // May
            ];

            $docCounter = 25000;

            foreach ($investors as $investor) {
                // Get the investor's cage
                $cage = Cage::where('investor_id', $investor->id)->first();

                if (!$cage) {
                    $this->command->warn("No cage found for investor ID {$investor->id}, skipping...");
                    continue;
                }

                foreach ($samplingDates as $monthDates) {
                    foreach ($monthDates as $date) {
                        $sampling = Sampling::create([
                            'investor_id' => $investor->id,
                            'cage_no' => $cage->id,
                            'date_sampling' => $date,
                            'doc' => sprintf('DOC-2026-%s-%05d', Carbon::parse($date)->format('md'), $docCounter),
                            'feed_types_id' => $cage->feed_types_id,
                            'mortality' => rand(0, 3),
                        ]);

                        $this->createSamples($sampling);
                        $docCounter++;
                    }
                }

                $this->command->info("Added additional samplings for investor: {$investor->name} (ID: {$investor->id})");
            }
        });

        $this->command->info('Additional sampling data seeded successfully.');
    }

    private function createSamples(Sampling $sampling): void
    {
        $feedType = strtoupper((string) ($sampling->feedType?->feed_type ?? $sampling->cage?->feedType?->feed_type));
        
        // Calculate days since March 1, 2026 for growth calculation
        $startDate = Carbon::parse('2026-03-01');
        $samplingDate = Carbon::parse($sampling->date_sampling);
        $daysSinceStart = $startDate->diffInDays($samplingDate);

        // Base weight depending on feed type
        $base = match ($feedType) {
            'PRE STARTER' => 42,
            'STARTER FEED' => 115,
            'GROWER' => 235,
            'FINISHER' => 430,
            default => 180,
        };

        // Growth factor: increases over time (0.8% per day)
        $growthFactor = 1 + ($daysSinceStart * 0.008);
        $baseWeight = $base * $growthFactor;

        // Create 5 samples with slight variations
        $variations = [-0.08, -0.03, 0.02, 0.05, 0.09];

        foreach ($variations as $index => $variation) {
            $sampleNo = (string) ($index + 1);
            $weight = round($baseWeight * (1 + $variation), 1);
            
            // Calculate length and width based on weight (realistic fish proportions)
            $length = round(4.85 * ($weight ** (1 / 3)), 2);
            $width = round($length * (0.29 + (($index % 3) * 0.015)), 2);

            Sample::create([
                'sampling_id' => $sampling->id,
                'investor_id' => $sampling->investor_id,
                'sample_no' => $sampleNo,
                'weight' => $weight,
                'length' => $length,
                'width' => $width,
            ]);
        }
    }
}
