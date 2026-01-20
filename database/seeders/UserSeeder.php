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
            'role' => 'admin',
            'is_active' => true,
        ]);

        // Create test user
        User::firstOrCreate([
            'email' => 'test@sfm.com',
        ], [
            'name' => 'Test User',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'role' => 'farmer',
            'is_active' => true,
        ]);

        // Create manager user (NOTE: This is just a farmer, not a special role)
        User::firstOrCreate([
            'email' => 'manager@sfm.com',
        ], [
            'name' => 'Farm Manager',
            'email_verified_at' => now(),
            'password' => Hash::make('manager123'),
            'role' => 'farmer', // Same privileges as any other farmer
            'is_active' => true,
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
                'role' => 'farmer',
                'is_active' => true,
            ]);
        }

        // Create unverified users for testing email verification
        $unverifiedUsers = [
            ['name' => 'Unverified User 1', 'email' => 'unverified1@sfm.com', 'password' => 'password'],
            ['name' => 'Unverified User 2', 'email' => 'unverified2@sfm.com', 'password' => 'password'],
        ];

        foreach ($unverifiedUsers as $userData) {
            User::firstOrCreate([
                'email' => $userData['email'],
            ], [
                'name' => $userData['name'],
                'email_verified_at' => null,
                'password' => Hash::make($userData['password']),
                'role' => 'farmer',
                'is_active' => true,
            ]);
        }

        // Create inactive users for testing activation/deactivation feature
        $inactiveUsers = [
            ['name' => 'Inactive Farmer 1', 'email' => 'inactive1@sfm.com', 'password' => 'password'],
            ['name' => 'Inactive Farmer 2', 'email' => 'inactive2@sfm.com', 'password' => 'password'],
        ];

        foreach ($inactiveUsers as $userData) {
            User::firstOrCreate([
                'email' => $userData['email'],
            ], [
                'name' => $userData['name'],
                'email_verified_at' => now(),
                'password' => Hash::make($userData['password']),
                'role' => 'farmer',
                'is_active' => false, // These users are inactive
            ]);
        }

        $totalUsers = User::count();
        $activeUsers = User::where('is_active', true)->count();
        $inactiveUsersCount = User::where('is_active', false)->count();
        $adminUsers = User::where('role', 'admin')->count();
        $farmerUsers = User::where('role', 'farmer')->count();

        $this->command->info('Users seeded successfully!');
        $this->command->info('');
        $this->command->info('=== Default Login Credentials ===');
        $this->command->info('Admin: admin@sfm.com / admin123');
        $this->command->info('Test: test@sfm.com / password');
        $this->command->info('Manager: manager@sfm.com / manager123');
        $this->command->info('');
        $this->command->info('=== User Statistics ===');
        $this->command->info("Total Users: {$totalUsers}");
        $this->command->info("  - Active: {$activeUsers}");
        $this->command->info("  - Inactive: {$inactiveUsersCount}");
        $this->command->info('');
        $this->command->info("Roles Distribution:");
        $this->command->info("  - Admins: {$adminUsers}");
        $this->command->info("  - Farmers: {$farmerUsers}");
    }
}
