<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Investor;

class InvestorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create specific investors for testing
        $investors = [
            [
                'name' => 'John Smith',
                'phone' => '09123456789',
                'address' => '123 Main Street, Davao City, Philippines',
            ],
            [
                'name' => 'Maria Garcia',
                'phone' => '09234567890',
                'address' => '456 Oak Avenue, Cebu City, Philippines',
            ],
            [
                'name' => 'Robert Johnson',
                'phone' => '09345678901',
                'address' => '789 Pine Road, Manila, Philippines',
            ],
            [
                'name' => 'Ana Santos',
                'phone' => '09456789012',
                'address' => '321 Elm Street, Iloilo City, Philippines',
            ],
            [
                'name' => 'Carlos Rodriguez',
                'phone' => '09567890123',
                'address' => '654 Maple Drive, Bacolod City, Philippines',
            ],
            [
                'name' => 'Luz Cruz',
                'phone' => '09678901234',
                'address' => '987 Cedar Lane, Zamboanga City, Philippines',
            ],
            [
                'name' => 'Miguel Torres',
                'phone' => '09789012345',
                'address' => '147 Birch Court, Tacloban City, Philippines',
            ],
            [
                'name' => 'Isabel Reyes',
                'phone' => '09890123456',
                'address' => '258 Spruce Way, Baguio City, Philippines',
            ],
        ];

        foreach ($investors as $investorData) {
            Investor::firstOrCreate([
                'name' => $investorData['name'],
            ], $investorData);
        }

        // Create additional investors without factory (for production)
        $additionalInvestors = [
            ['name' => 'Pedro Martinez', 'phone' => '09901234567', 'address' => '369 Willow Street, Cagayan de Oro, Philippines'],
            ['name' => 'Carmen Lopez', 'phone' => '09912345678', 'address' => '741 Poplar Avenue, Butuan City, Philippines'],
            ['name' => 'Jose Santos', 'phone' => '09923456789', 'address' => '852 Sycamore Road, General Santos, Philippines'],
            ['name' => 'Rosa Mendoza', 'phone' => '09934567890', 'address' => '963 Aspen Drive, Cotabato City, Philippines'],
            ['name' => 'Antonio Flores', 'phone' => '09945678901', 'address' => '159 Cypress Lane, Marawi City, Philippines'],
            ['name' => 'Elena Ramos', 'phone' => '09956789012', 'address' => '357 Magnolia Court, Pagadian City, Philippines'],
            ['name' => 'Francisco Dela Cruz', 'phone' => '09967890123', 'address' => '468 Juniper Way, Dipolog City, Philippines'],
            ['name' => 'Teresa Gonzales', 'phone' => '09978901234', 'address' => '579 Hickory Street, Ozamiz City, Philippines'],
            ['name' => 'Manuel Aquino', 'phone' => '09989012345', 'address' => '680 Walnut Avenue, Surigao City, Philippines'],
            ['name' => 'Consuelo Bautista', 'phone' => '09990123456', 'address' => '791 Chestnut Road, Tagum City, Philippines'],
            ['name' => 'Ramon Villanueva', 'phone' => '09901234567', 'address' => '802 Beech Drive, Koronadal City, Philippines'],
            ['name' => 'Dolores Castillo', 'phone' => '09912345678', 'address' => '913 Alder Lane, Kidapawan City, Philippines'],
        ];

        foreach ($additionalInvestors as $investorData) {
            Investor::firstOrCreate([
                'name' => $investorData['name'],
            ], $investorData);
        }

        $this->command->info('Investors seeded successfully!');
        $this->command->info('Created ' . count($investors) . ' specific investors and 12 random investors.');
    }
}
