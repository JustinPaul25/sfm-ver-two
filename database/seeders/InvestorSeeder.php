<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Investor;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

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

        // Seed a soft-deleted investor sample for testing archives/restore flows
        $archived = Investor::firstOrCreate([
            'name' => 'Archived Investor',
        ], [
            'phone' => '09000000000',
            'address' => 'Archived Address, Philippines',
        ]);
        if (is_null($archived->deleted_at)) {
            $archived->delete();
        }

        // Create a login account for every non-archived investor (skip if one already exists)
        $allInvestors = Investor::whereNull('deleted_at')->orderBy('id')->get();
        $createdInvestorUsers = 0;
        $investorCredentials = [];

        foreach ($allInvestors as $investor) {
            $existing = User::where('investor_id', $investor->id)->where('role', 'investor')->first();
            if ($existing) {
                $investorCredentials[] = "{$existing->email} / password";
                continue;
            }
            $email = strtolower(str_replace(' ', '.', $investor->name)) . '@investor.com';
            User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $investor->name,
                    'email_verified_at' => now(),
                    'password' => Hash::make('password'),
                    'role' => 'investor',
                    'is_active' => true,
                    'investor_id' => $investor->id,
                ]
            );
            $createdInvestorUsers++;
            $investorCredentials[] = "{$email} / password";
        }

        // Create farmer users and link them to investors
        $farmerData = [
            // Farmers for John Smith
            [
                'name' => 'Pedro Santos',
                'email' => 'pedro.santos@sfm.com',
                'password' => 'password',
                'investor_name' => 'John Smith',
            ],
            [
                'name' => 'Juan Dela Cruz',
                'email' => 'juan.delacruz@sfm.com',
                'password' => 'password',
                'investor_name' => 'John Smith',
            ],
            // Farmers for Maria Garcia
            [
                'name' => 'Carmen Rivera',
                'email' => 'carmen.rivera@sfm.com',
                'password' => 'password',
                'investor_name' => 'Maria Garcia',
            ],
            [
                'name' => 'Sofia Mercado',
                'email' => 'sofia.mercado@sfm.com',
                'password' => 'password',
                'investor_name' => 'Maria Garcia',
            ],
            // Farmers for Robert Johnson
            [
                'name' => 'Ricardo Gomez',
                'email' => 'ricardo.gomez@sfm.com',
                'password' => 'password',
                'investor_name' => 'Robert Johnson',
            ],
            [
                'name' => 'Fernando Lopez',
                'email' => 'fernando.lopez@sfm.com',
                'password' => 'password',
                'investor_name' => 'Robert Johnson',
            ],
            [
                'name' => 'Gabriel Cruz',
                'email' => 'gabriel.cruz@sfm.com',
                'password' => 'password',
                'investor_name' => 'Robert Johnson',
            ],
            // Farmers for Ana Santos
            [
                'name' => 'Rosa Diaz',
                'email' => 'rosa.diaz@sfm.com',
                'password' => 'password',
                'investor_name' => 'Ana Santos',
            ],
            [
                'name' => 'Lucia Martinez',
                'email' => 'lucia.martinez@sfm.com',
                'password' => 'password',
                'investor_name' => 'Ana Santos',
            ],
            // Farmers for Carlos Rodriguez
            [
                'name' => 'Miguel Ramos',
                'email' => 'miguel.ramos@sfm.com',
                'password' => 'password',
                'investor_name' => 'Carlos Rodriguez',
            ],
            [
                'name' => 'Andres Fernandez',
                'email' => 'andres.fernandez@sfm.com',
                'password' => 'password',
                'investor_name' => 'Carlos Rodriguez',
            ],
            // Farmers for Luz Cruz
            [
                'name' => 'Elena Vargas',
                'email' => 'elena.vargas@sfm.com',
                'password' => 'password',
                'investor_name' => 'Luz Cruz',
            ],
            // Farmers for Miguel Torres
            [
                'name' => 'Diego Morales',
                'email' => 'diego.morales@sfm.com',
                'password' => 'password',
                'investor_name' => 'Miguel Torres',
            ],
            [
                'name' => 'Antonio Herrera',
                'email' => 'antonio.herrera@sfm.com',
                'password' => 'password',
                'investor_name' => 'Miguel Torres',
            ],
            // Farmers for Isabel Reyes
            [
                'name' => 'Isabella Castro',
                'email' => 'isabella.castro@sfm.com',
                'password' => 'password',
                'investor_name' => 'Isabel Reyes',
            ],
            [
                'name' => 'Valentina Ortiz',
                'email' => 'valentina.ortiz@sfm.com',
                'password' => 'password',
                'investor_name' => 'Isabel Reyes',
            ],
            // Farmers for additional investors
            [
                'name' => 'Eduardo Silva',
                'email' => 'eduardo.silva@sfm.com',
                'password' => 'password',
                'investor_name' => 'Pedro Martinez',
            ],
            [
                'name' => 'Catalina Mendoza',
                'email' => 'catalina.mendoza@sfm.com',
                'password' => 'password',
                'investor_name' => 'Carmen Lopez',
            ],
            [
                'name' => 'Francisco Ruiz',
                'email' => 'francisco.ruiz@sfm.com',
                'password' => 'password',
                'investor_name' => 'Jose Santos',
            ],
            [
                'name' => 'Mariana Flores',
                'email' => 'mariana.flores@sfm.com',
                'password' => 'password',
                'investor_name' => 'Rosa Mendoza',
            ],
            [
                'name' => 'Alberto Jimenez',
                'email' => 'alberto.jimenez@sfm.com',
                'password' => 'password',
                'investor_name' => 'Antonio Flores',
            ],
        ];

        $createdFarmers = 0;
        foreach ($farmerData as $farmer) {
            $investor = Investor::where('name', $farmer['investor_name'])->first();
            
            if ($investor) {
                User::firstOrCreate([
                    'email' => $farmer['email'],
                ], [
                    'name' => $farmer['name'],
                    'email_verified_at' => now(),
                    'password' => Hash::make($farmer['password']),
                    'role' => 'farmer',
                    'is_active' => true,
                    'investor_id' => $investor->id,
                ]);
                $createdFarmers++;
            }
        }

        $this->command->info('Investors seeded successfully!');
        $this->command->info('Created ' . count($investors) . ' specific investors and ' . count($additionalInvestors) . ' additional investors, plus 1 archived investor.');
        $this->command->info('Created ' . $createdInvestorUsers . ' new investor login accounts (all ' . $allInvestors->count() . ' non-archived investors have logins).');
        $this->command->info('Created ' . $createdFarmers . ' farmers linked to investors.');
        $this->command->info('');
        $this->command->info('=== Investor Login Credentials (all use password: password) ===');
        foreach ($investorCredentials as $cred) {
            $this->command->info($cred);
        }
    }
}
