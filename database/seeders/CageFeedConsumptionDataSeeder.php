<?php

namespace Database\Seeders;

use App\Models\Cage;
use App\Models\CageFeedConsumption;
use App\Models\Sampling;
use App\Models\Sample;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class CageFeedConsumptionDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Adds realistic feed consumption data for all cages between sampling dates.
     */
    public function run(): void
    {
        // Get all cages for investors 2-13 (excluding 6)
        $cages = Cage::whereIn('investor_id', [2, 3, 4, 5, 7, 8, 9, 10, 11, 12, 13])->get();

        foreach ($cages as $cage) {
            $this->command->info("Processing cage ID {$cage->id} for investor {$cage->investor_id}");
            
            // Get all samplings for this cage ordered by date
            $samplings = Sampling::where('cage_no', $cage->id)
                ->orderBy('date_sampling', 'asc')
                ->get();

            if ($samplings->count() < 2) {
                $this->command->warn("Not enough samplings for cage ID {$cage->id}, skipping...");
                continue;
            }

            // Clean up existing feed consumption for this cage
            CageFeedConsumption::where('cage_id', $cage->id)->delete();

            $previousSampling = null;
            $dayNumber = 1;

            foreach ($samplings as $sampling) {
                if (!$previousSampling) {
                    // First sampling - no previous period to calculate
                    $previousSampling = $sampling;
                    continue;
                }

                // Calculate date range between previous and current sampling
                $startDate = Carbon::parse($previousSampling->date_sampling)->addDay();
                $endDate = Carbon::parse($sampling->date_sampling);

                // Get samples for current sampling to calculate average weight
                $samples = $sampling->samples;
                $totalWeight = $samples->sum('weight');
                $totalSamples = $samples->count();
                $avgWeight = $totalSamples > 0 ? round($totalWeight / $totalSamples, 2) : 200;

                // Calculate daily feed ration
                $numberOfFish = $cage->number_of_fingerlings ?? 500;
                $feedingRate = 3; // 3%
                $dailyFeedRation = ($numberOfFish * $avgWeight * ($feedingRate / 100)) / 1000;

                // Add feed consumption records for each day in the period
                $currentDate = $startDate->copy();
                while ($currentDate->lte($endDate)) {
                    // Add some realistic variation (±15%)
                    $variation = (rand(-15, 15) / 100);
                    $feedAmount = round($dailyFeedRation * (1 + $variation), 2);

                    CageFeedConsumption::create([
                        'cage_id' => $cage->id,
                        'day_number' => $dayNumber,
                        'feed_amount' => max(0, $feedAmount),
                        'consumption_date' => $currentDate->toDateString(),
                        'notes' => null,
                    ]);

                    $currentDate->addDay();
                    $dayNumber++;
                }

                $this->command->info("Added feed consumption from {$startDate->toDateString()} to {$endDate->toDateString()} for cage ID {$cage->id}");
                $previousSampling = $sampling;
            }
        }

        $this->command->info('Feed consumption data seeded successfully.');
    }
}
