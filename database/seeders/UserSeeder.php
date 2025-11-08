<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin user
        User::firstOrCreate([
            'email' => 'admin@sfm.com',
        ], [
            'name' => 'System Administrator',
            'email_verified_at' => now(),
            'password' => Hash::make('admin123'),
        ]);

        // Create test user
        User::firstOrCreate([
            'email' => 'test@sfm.com',
        ], [
            'name' => 'Test User',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        // Create manager user
        User::firstOrCreate([
            'email' => 'manager@sfm.com',
        ], [
            'name' => 'Farm Manager',
            'email_verified_at' => now(),
            'password' => Hash::make('manager123'),
        ]);

        // Create additional users without factory (for production)
        $additionalUsers = [
            ['name' => 'John Doe', 'email' => 'john@sfm.com', 'password' => 'password'],
            ['name' => 'Jane Smith', 'email' => 'jane@sfm.com', 'password' => 'password'],
            ['name' => 'Bob Wilson', 'email' => 'bob@sfm.com', 'password' => 'password'],
            ['name' => 'Alice Brown', 'email' => 'alice@sfm.com', 'password' => 'password'],
            ['name' => 'Charlie Davis', 'email' => 'charlie@sfm.com', 'password' => 'password'],
        ];

        foreach ($additionalUsers as $userData) {
            User::firstOrCreate([
                'email' => $userData['email'],
            ], [
                'name' => $userData['name'],
            'email_verified_at' => now(),
                'password' => Hash::make($userData['password']),
        ]);
        }

        // Create unverified users for testing email verification
        $unverifiedUsers = [
            ['name' => 'Unverified User 1', 'email' => 'unverified1@sfm.com', 'password' => 'password'],
            ['name' => 'Unverified User 2', 'email' => 'unverified2@sfm.com', 'password' => 'password'],
            ['name' => 'Unverified User 3', 'email' => 'unverified3@sfm.com', 'password' => 'password'],
        ];

        foreach ($unverifiedUsers as $userData) {
            User::firstOrCreate([
                'email' => $userData['email'],
            ], [
                'name' => $userData['name'],
            'email_verified_at' => null,
                'password' => Hash::make($userData['password']),
        ]);
        }

        $this->command->info('Users seeded successfully!');
        $this->command->info('Admin: admin@sfm.com / admin123');
        $this->command->info('Test: test@sfm.com / password');
        $this->command->info('Manager: manager@sfm.com / manager123');
    }
}
