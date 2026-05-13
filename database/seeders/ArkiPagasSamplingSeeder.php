<?php

namespace Database\Seeders;

use App\Models\Cage;
use App\Models\FeedType;
use App\Models\Investor;
use App\Models\Sample;
use App\Models\Sampling;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;
use Carbon\Carbon;

class ArkiPagasSamplingSeeder extends Seeder
{
    /**
     * Run the database seeds for investor id=15 (Arki Pagas).
     */
    public function run(): void
    {
        DB::transaction(function () {
            $investor = Investor::firstOrCreate(
                ['id' => 15],
                [
                    'name' => 'Arki Pagas',
                    'address' => 'New Visayas, Panabo City',
                    'phone' => '+639456721334',
                ]
            );

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

            $farmer = User::firstOrCreate(
                ['email' => 'arki.farmer@example.invalid'],
                [
                    'name' => 'FARMER, ARKI',
                    'username' => 'arkifarmer',
                    'phone' => '+639456721334',
                    'password' => Hash::make('farmer123'),
                    'role' => 'farmer',
                    'is_active' => true,
                    'investor_id' => $investor->id,
                ]
            );

            $cageData = [
                [
                    'pond' => 'POND A',
                    'fingerlings' => 500,
                    'feed_type' => 'STARTER FEED',
                    'mortality' => 3,
                ],
                [
                    'pond' => 'POND B',
                    'fingerlings' => 480,
                    'feed_type' => 'GROWER',
                    'mortality' => 1,
                ],
            ];

            $cages = [];
            foreach ($cageData as $data) {
                $cages[] = Cage::updateOrCreate(
                    [
                        'investor_id' => $investor->id,
                        'type' => $data['pond'],
                    ],
                    [
                        'number_of_fingerlings' => $data['fingerlings'],
                        'feed_types_id' => $feedTypes[$data['feed_type']]->id,
                        'farmer_id' => $farmer->id,
                    ]
                );
            }

            $startDate = Carbon::parse('2026-03-01');
            $endDate = Carbon::parse('2026-05-10');
            $currentDate = $startDate->copy();

            $docCounter = 1;
            while ($currentDate->lte($endDate)) {
                foreach ($cages as $cageIndex => $cage) {
                    $sampling = Sampling::updateOrCreate(
                        [
                            'investor_id' => $investor->id,
                            'cage_no' => $cage->id,
                            'date_sampling' => $currentDate->toDateString(),
                        ],
                        [
                            'doc' => sprintf('DOC-2026-%s-%05d', $currentDate->format('md'), $docCounter),
                            'feed_types_id' => $cage->feed_types_id,
                            'mortality' => rand(0, 2),
                        ]
                    );

                    $this->createSamples($sampling);
                    $docCounter++;
                }

                $currentDate->addDays(7);
            }
        });

        $this->command->info('Arki Pagas (investor_id=15) sampling data seeded from March 1 to May 10.');
    }

    private function createSamples(Sampling $sampling): void
    {
        $feedType = strtoupper((string) ($sampling->feedType?->feed_type ?? $sampling->cage?->feedType?->feed_type));
        $daysSinceStart = Carbon::parse('2026-03-01')->diffInDays(Carbon::parse($sampling->date_sampling));

        $base = match ($feedType) {
            'PRE STARTER' => 42,
            'STARTER FEED' => 115,
            'GROWER' => 235,
            'FINISHER' => 430,
            default => 180,
        };

        $growthFactor = 1 + ($daysSinceStart * 0.008);
        $baseWeight = $base * $growthFactor;
        $variations = [-0.08, -0.03, 0.02, 0.05, 0.09];

        foreach ($variations as $index => $variation) {
            $sampleNo = (string) ($index + 1);
            $weight = round($baseWeight * (1 + $variation), 1);
            $length = round(4.85 * ($weight ** (1 / 3)), 2);
            $width = round($length * (0.29 + (($index % 3) * 0.015)), 2);

            Sample::updateOrCreate(
                [
                    'sampling_id' => $sampling->id,
                    'sample_no' => $sampleNo,
                ],
                [
                    'investor_id' => $sampling->investor_id,
                    'weight' => $weight,
                    'length' => $length,
                    'width' => $width,
                ]
            );
        }
    }
}
