<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Cage;
use App\Models\Investor;
use App\Models\FeedType;

class CageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get investors and feed types for creating cages
        $investors = Investor::all();
        $feedTypes = FeedType::all();
        $users = \App\Models\User::all();

        if ($investors->isEmpty() || $feedTypes->isEmpty()) {
            $this->command->warn('Investors or FeedTypes not found. Please run InvestorSeeder and FeedTypeSeeder first.');
            return;
        }

        // Get farmer users (exclude admin)
        $farmers = $users->reject(function($user) {
            return in_array($user->email, ['admin@sfm.com']);
        });

        // Create specific cages for testing with farmer assignments
        $cages = [
            [
                'number_of_fingerlings' => 1000,
                'feed_types_id' => $feedTypes->where('feed_type', 'Starter Feed')->first()->id,
                'investor_id' => $investors->where('name', 'John Smith')->first()->id,
                'farmer_id' => $farmers->where('email', 'manager@sfm.com')->first()?->id,
            ],
            [
                'number_of_fingerlings' => 1500,
                'feed_types_id' => $feedTypes->where('feed_type', 'Grower Feed')->first()->id,
                'investor_id' => $investors->where('name', 'Maria Garcia')->first()->id,
                'farmer_id' => $farmers->where('email', 'test@sfm.com')->first()?->id,
            ],
            [
                'number_of_fingerlings' => 2000,
                'feed_types_id' => $feedTypes->where('feed_type', 'Premium Grower')->first()->id,
                'investor_id' => $investors->where('name', 'Robert Johnson')->first()->id,
                'farmer_id' => $farmers->where('email', 'john@sfm.com')->first()?->id,
            ],
            [
                'number_of_fingerlings' => 800,
                'feed_types_id' => $feedTypes->where('feed_type', 'Tilapia Starter')->first()->id,
                'investor_id' => $investors->where('name', 'Ana Santos')->first()->id,
                'farmer_id' => $farmers->where('email', 'jane@sfm.com')->first()?->id,
            ],
            [
                'number_of_fingerlings' => 1200,
                'feed_types_id' => $feedTypes->where('feed_type', 'High Protein Feed')->first()->id,
                'investor_id' => $investors->where('name', 'Carlos Rodriguez')->first()->id,
                'farmer_id' => null, // No farmer assigned
            ],
            [
                'number_of_fingerlings' => 1800,
                'feed_types_id' => $feedTypes->where('feed_type', 'Finisher Feed')->first()->id,
                'investor_id' => $investors->where('name', 'Luz Cruz')->first()->id,
                'farmer_id' => $farmers->where('email', 'bob@sfm.com')->first()?->id,
            ],
            [
                'number_of_fingerlings' => 900,
                'feed_types_id' => $feedTypes->where('feed_type', 'Organic Feed')->first()->id,
                'investor_id' => $investors->where('name', 'Miguel Torres')->first()->id,
                'farmer_id' => null, // No farmer assigned
            ],
            [
                'number_of_fingerlings' => 2500,
                'feed_types_id' => $feedTypes->where('feed_type', 'Premium Finisher')->first()->id,
                'investor_id' => $investors->where('name', 'Isabel Reyes')->first()->id,
                'farmer_id' => $farmers->where('email', 'alice@sfm.com')->first()?->id,
            ],
        ];

        foreach ($cages as $cageData) {
            Cage::firstOrCreate([
                'investor_id' => $cageData['investor_id'],
                'number_of_fingerlings' => $cageData['number_of_fingerlings'],
            ], $cageData);
        }

        // Create additional cages without factory (for production)
        // Randomly assign farmers to ~60% of cages
        for ($i = 0; $i < 15; $i++) {
            $randomInvestor = $investors->random();
            $randomFeedType = $feedTypes->random();
            $fingerlingCount = rand(500, 3000);
            
            // 60% chance to assign a farmer
            $farmerId = null;
            if (rand(1, 100) <= 60 && $farmers->count() > 0) {
                $farmerId = $farmers->random()->id;
            }
            
            Cage::firstOrCreate([
                'investor_id' => $randomInvestor->id,
                'number_of_fingerlings' => $fingerlingCount,
            ], [
                'feed_types_id' => $randomFeedType->id,
                'farmer_id' => $farmerId,
            ]);
        }

        // Create cages with realistic fingerling counts
        // Some with farmers, some without
        $realisticFingerlingCounts = [500, 750, 1000, 1250, 1500, 1750, 2000, 2250, 2500, 3000];
        
        for ($i = 0; $i < 10; $i++) {
            // 50% chance to assign a farmer
            $farmerId = null;
            if (rand(1, 100) <= 50 && $farmers->count() > 0) {
                $farmerId = $farmers->random()->id;
            }
            
            Cage::firstOrCreate([
                'investor_id' => $investors->random()->id,
                'number_of_fingerlings' => $realisticFingerlingCounts[$i],
            ], [
                'feed_types_id' => $feedTypes->random()->id,
                'farmer_id' => $farmerId,
            ]);
        }

        $cagesCount = Cage::count();
        $cagesWithFarmers = Cage::whereNotNull('farmer_id')->count();
        
        $this->command->info('Cages seeded successfully!');
        $this->command->info("Created {$cagesCount} total cages:");
        $this->command->info("  - {$cagesWithFarmers} cages with assigned farmers");
        $this->command->info("  - " . ($cagesCount - $cagesWithFarmers) . " cages without farmers");
        $this->command->info("  - Mix of cage sizes from 500 to 3000 fingerlings");
    }
}
