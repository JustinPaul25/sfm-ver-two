<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
        ]);

        $this->command->info('Seeding complete.');
        $this->command->info('Admin: admin@sfm.com / admin123');
        $this->command->info('Users: ' . User::count());
    }
}
