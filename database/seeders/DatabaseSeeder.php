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
            // ClientProductionSeeder::class,
        ]);

        $this->command->info('Seeding complete.');
        $this->command->info('Admin: admin@sfm.com / admin123');
        $this->command->info('Investors: pond1–pond11 / investor123 (dummy email pond{N}@example.invalid)');
        $this->command->info('Users: '.User::count());
    }
}
