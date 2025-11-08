<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Investor;
use App\Models\FeedType;
use App\Models\Cage;
use App\Models\CageFeedConsumption;
use App\Models\Sampling;
use App\Models\Sample;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->command->info('Starting comprehensive seeding process...');
        
        // Call all seeders in the correct order
        $this->call([
            UserSeeder::class,
            InvestorSeeder::class,
            FeedTypeSeeder::class,
            CageSeeder::class,
            CageFeedingScheduleSeeder::class,
            SamplingSeeder::class,
            SampleSeeder::class,
            CageFeedConsumptionSeeder::class,
        ]);

        $this->command->info('All data seeded successfully!');
        $this->command->info('');
        $this->command->info('=== Login Credentials ===');
        $this->command->info('Admin: admin@sfm.com / admin123');
        $this->command->info('Test: test@sfm.com / password');
        $this->command->info('Manager: manager@sfm.com / manager123');
        $this->command->info('');
        $this->command->info('=== Data Summary ===');
        $this->command->info('Users: ' . User::count());
        $this->command->info('Investors: ' . Investor::count());
        $this->command->info('Feed Types: ' . FeedType::count());
        $this->command->info('Cages: ' . Cage::count());
        $this->command->info('Samplings: ' . Sampling::count());
        $this->command->info('Samples: ' . Sample::count());
        $this->command->info('Feed Consumptions: ' . CageFeedConsumption::count());
    }
}
