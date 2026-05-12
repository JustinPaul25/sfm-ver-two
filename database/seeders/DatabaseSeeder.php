<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            SamplingSeeder::class,
            SampleSeeder::class,
            // ClientProductionSeeder::class,
        ]);

        $this->command->info('Seeding complete.');
        $this->command->info('Admin: admin@sfm.com / admin123');
        $this->command->info('Sample farmers: farmer1-farmer11 / farmer123');
        $this->command->info('Users: '.User::count());
    }
}
