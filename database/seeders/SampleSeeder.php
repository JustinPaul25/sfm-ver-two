<?php

namespace Database\Seeders;

use App\Models\Sample;
use App\Models\Sampling;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class SampleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            Sampling::with(['feedType', 'cage.feedType', 'samples'])
                ->orderBy('id')
                ->get()
                ->each(function (Sampling $sampling) {
                    $baseWeight = $this->baseWeightFor($sampling);
                    $variations = [-0.08, -0.03, 0.02, 0.05, 0.09];

                    foreach ($variations as $index => $variation) {
                        $sampleNo = (string) ($index + 1);
                        $weight = round($baseWeight * (1 + $variation), 1);
                        $length = $this->lengthForWeight($weight);
                        $width = round($length * (0.29 + (($index % 3) * 0.015)), 2);

                        $sample = Sample::firstOrNew([
                            'sampling_id' => $sampling->id,
                            'sample_no' => $sampleNo,
                        ]);

                        if ($sample->exists && (float) $sample->weight > 0) {
                            continue;
                        }

                        $sample->fill([
                            'investor_id' => $sampling->investor_id,
                            'weight' => $weight,
                            'length' => $length,
                            'width' => $width,
                        ]);
                        $sample->save();
                    }
                });
        });

        $this->command->info('Realistic sample measurements seeded successfully.');
    }

    private function baseWeightFor(Sampling $sampling): float
    {
        $feedType = strtoupper((string) ($sampling->feedType?->feed_type ?? $sampling->cage?->feedType?->feed_type));
        $cageNo = (int) $sampling->cage_no;

        $base = match ($feedType) {
            'PRE STARTER' => 42,
            'STARTER FEED' => 115,
            'GROWER' => 235,
            'FINISHER' => 430,
            default => 180,
        };

        return $base + (($cageNo % 5) * 7);
    }

    private function lengthForWeight(float $weight): float
    {
        return round(4.85 * ($weight ** (1 / 3)), 2);
    }
}
