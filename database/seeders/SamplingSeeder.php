<?php

namespace Database\Seeders;

use App\Models\Cage;
use App\Models\FeedType;
use App\Models\Investor;
use App\Models\Sampling;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;

class SamplingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $feedTypes = collect([
                'PRE STARTER' => ['feed_type' => 'PRE STARTER', 'brand' => 'B-Meg'],
                'STARTER FEED' => ['feed_type' => 'STARTER FEED', 'brand' => 'Santeh'],
                'GROWER' => ['feed_type' => 'GROWER', 'brand' => 'CP Aqua'],
                'FINISHER' => ['feed_type' => 'FINISHER', 'brand' => 'Tateh'],
            ])->mapWithKeys(function (array $data, string $key) {
                return [
                    $key => FeedType::firstOrCreate(
                        ['feed_type' => $data['feed_type']],
                        ['brand' => $data['brand']]
                    ),
                ];
            });

            $cages = [
                [
                    'pond' => 'POND 1',
                    'fingerlings' => 450,
                    'feed_type' => 'GROWER',
                    'farmer' => 'CESISTA, ROY B',
                    'email' => 'roy.cesista@example.invalid',
                    'phone' => '0917-410-1201',
                    'mortality' => 2,
                ],
                [
                    'pond' => 'POND 2',
                    'fingerlings' => 450,
                    'feed_type' => 'GROWER',
                    'farmer' => 'CESISTA, RUEL B',
                    'email' => 'ruel.cesista@example.invalid',
                    'phone' => '0917-410-1202',
                    'mortality' => 1,
                ],
                [
                    'pond' => 'POND 3',
                    'fingerlings' => 350,
                    'feed_type' => 'GROWER',
                    'farmer' => 'DAHILAN, MELANIE G',
                    'email' => 'melanie.dahilan@example.invalid',
                    'phone' => '0917-410-1203',
                    'mortality' => 0,
                ],
                [
                    'pond' => 'POND 4',
                    'fingerlings' => 360,
                    'feed_type' => 'GROWER',
                    'farmer' => 'GAITERA, ORLANDO O',
                    'email' => 'orlando.gaitera@example.invalid',
                    'phone' => '0917-410-1204',
                    'mortality' => 3,
                ],
                [
                    'pond' => 'POND 5',
                    'fingerlings' => 300,
                    'feed_type' => 'GROWER',
                    'farmer' => 'GULIBAN JR, GAUDENCIO M',
                    'email' => 'gaudencio.guliban@example.invalid',
                    'phone' => '0917-410-1205',
                    'mortality' => 1,
                ],
                [
                    'pond' => 'POND 6',
                    'fingerlings' => 320,
                    'feed_type' => 'GROWER',
                    'farmer' => 'MEJOS, BERNIEL N',
                    'email' => 'bernie1.mejos@example.invalid',
                    'phone' => '0917-410-1206',
                    'mortality' => 0,
                ],
                [
                    'pond' => 'POND 7',
                    'fingerlings' => 380,
                    'feed_type' => 'STARTER FEED',
                    'farmer' => 'RENOLA, RICHARD G',
                    'email' => 'richard.renola@example.invalid',
                    'phone' => '0917-410-1207',
                    'mortality' => 4,
                ],
                [
                    'pond' => 'POND 8',
                    'fingerlings' => 350,
                    'feed_type' => 'PRE STARTER',
                    'farmer' => 'TARAY, JERRY B',
                    'email' => 'jerry.taray@example.invalid',
                    'phone' => '0917-410-1208',
                    'mortality' => 2,
                ],
                [
                    'pond' => 'POND 9',
                    'fingerlings' => 310,
                    'feed_type' => 'STARTER FEED',
                    'farmer' => 'TOLEDO, RYAN A',
                    'email' => 'ryan.toledo@example.invalid',
                    'phone' => '0917-410-1209',
                    'mortality' => 1,
                ],
                [
                    'pond' => 'POND 10',
                    'fingerlings' => 3000,
                    'feed_type' => 'STARTER FEED',
                    'farmer' => 'OLOHAN, ROLLY',
                    'email' => 'rolly.olohan@example.invalid',
                    'phone' => '0917-410-1210',
                    'mortality' => 18,
                ],
                [
                    'pond' => 'POND 11',
                    'fingerlings' => 320,
                    'feed_type' => 'FINISHER',
                    'farmer' => 'NJ, SORSANO',
                    'email' => 'sorsano.nj@example.invalid',
                    'phone' => '0917-410-1211',
                    'mortality' => 0,
                ],
            ];

            foreach ($cages as $index => $data) {
                $pondNumber = $index + 1;
                $investor = Investor::firstOrCreate(
                    ['name' => $data['pond']],
                    [
                        'address' => 'Barangay San Isidro, General Santos City',
                        'phone' => $data['phone'],
                    ]
                );

                $farmer = User::firstOrCreate(
                    ['email' => $data['email']],
                    [
                        'name' => $data['farmer'],
                        'username' => 'farmer'.$pondNumber,
                        'phone' => $data['phone'],
                        'password' => Hash::make('farmer123'),
                        'role' => 'farmer',
                        'is_active' => true,
                        'investor_id' => $investor->id,
                    ]
                );

                $cage = Cage::updateOrCreate(
                    [
                        'investor_id' => $investor->id,
                        'farmer_id' => $farmer->id,
                    ],
                    [
                        'number_of_fingerlings' => $data['fingerlings'],
                        'feed_types_id' => $feedTypes[$data['feed_type']]->id,
                    ]
                );

                Sampling::updateOrCreate(
                    [
                        'investor_id' => $investor->id,
                        'cage_no' => $cage->id,
                        'date_sampling' => '2026-03-13',
                    ],
                    [
                        'doc' => sprintf('DOC-20260313-%05d', 24000 + ($pondNumber * 1739)),
                        'feed_types_id' => $cage->feed_types_id,
                        'mortality' => $data['mortality'],
                    ]
                );
            }
        });

        $this->command->info('Sample pond cages and samplings seeded successfully.');
    }
}
