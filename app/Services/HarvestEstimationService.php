<?php

namespace App\Services;

use App\Models\Cage;
use App\Models\SystemSetting;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class HarvestEstimationService
{
    public const DEFAULT_TARGET_WEIGHT_G = 500;

    public const DEFAULT_GROWTH_RATE_G_PER_DAY = 3;

    /**
     * Get target harvest weight in grams from system settings.
     */
    public function getTargetWeightG(): float
    {
        $v = SystemSetting::get('harvest_target_weight_grams', (string) self::DEFAULT_TARGET_WEIGHT_G);
        return (float) ($v !== null ? $v : self::DEFAULT_TARGET_WEIGHT_G);
    }

    /**
     * Get default daily growth rate (g/day) when cage has only one sampling.
     */
    public function getDefaultGrowthRateGPerDay(): float
    {
        $v = SystemSetting::get('harvest_default_growth_rate_g_per_day', (string) self::DEFAULT_GROWTH_RATE_G_PER_DAY);
        return (float) ($v !== null ? $v : self::DEFAULT_GROWTH_RATE_G_PER_DAY);
    }

    /**
     * Estimate harvest for a single cage.
     *
     * @return array{estimated_harvest_date: string|null, days_until_harvest: int|null, current_avg_weight_g: float, target_weight_g: float, growth_rate_used_g_per_day: float, is_ready: bool, latest_sampling_date: string|null}
     */
    public function estimateForCage(Cage $cage): array
    {
        $targetWeightG = $this->getTargetWeightG();
        $defaultGrowthGPerDay = $this->getDefaultGrowthRateGPerDay();

        $samplings = $cage->samplings()
            ->with('samples')
            ->orderByDesc('date_sampling')
            ->limit(5)
            ->get();

        $latestWithSamples = $samplings->first(fn ($s) => $s->samples && $s->samples->isNotEmpty());
        if (! $latestWithSamples) {
            return [
                'estimated_harvest_date' => null,
                'days_until_harvest' => null,
                'current_avg_weight_g' => 0.0,
                'target_weight_g' => $targetWeightG,
                'growth_rate_used_g_per_day' => $defaultGrowthGPerDay,
                'is_ready' => false,
                'latest_sampling_date' => null,
            ];
        }

        $currentAvgG = (float) $latestWithSamples->samples->avg('weight');
        $latestDate = Carbon::parse($latestWithSamples->date_sampling);

        $growthRateGPerDay = $defaultGrowthGPerDay;
        $pair = $this->latestTwoSamplingsWithAvgWeight($samplings);
        if ($pair !== null) {
            [$avg1, $date1, $avg2, $date2] = $pair;
            $daysBetween = Carbon::parse($date1)->diffInDays(Carbon::parse($date2));
            if ($daysBetween > 0 && $avg1 > 0) {
                $derived = ($avg2 - $avg1) / $daysBetween;
                if ($derived > 0) {
                    $growthRateGPerDay = $derived;
                }
            }
        }

        if ($currentAvgG >= $targetWeightG) {
            return [
                'estimated_harvest_date' => $latestDate->toDateString(),
                'days_until_harvest' => 0,
                'current_avg_weight_g' => round($currentAvgG, 2),
                'target_weight_g' => $targetWeightG,
                'growth_rate_used_g_per_day' => round($growthRateGPerDay, 2),
                'is_ready' => true,
                'latest_sampling_date' => $latestDate->toDateString(),
            ];
        }

        $daysUntil = (int) ceil(($targetWeightG - $currentAvgG) / $growthRateGPerDay);
        $estimatedDate = $latestDate->copy()->addDays($daysUntil);

        return [
            'estimated_harvest_date' => $estimatedDate->toDateString(),
            'days_until_harvest' => max(0, $daysUntil),
            'current_avg_weight_g' => round($currentAvgG, 2),
            'target_weight_g' => $targetWeightG,
            'growth_rate_used_g_per_day' => round($growthRateGPerDay, 2),
            'is_ready' => false,
            'latest_sampling_date' => $latestDate->toDateString(),
        ];
    }

    /**
     * Get harvest anticipation for multiple cages (e.g. for dashboard).
     * Returns cages that have at least one sampling with samples, sorted by days_until_harvest ascending (soonest first).
     *
     * @param  Collection<int, Cage>  $cages
     * @return array<int, array{cage_id: int, cage: array, estimated_harvest_date: string|null, days_until_harvest: int|null, current_avg_weight_g: float, target_weight_g: float, is_ready: bool}>
     */
    public function anticipateForCages(Collection $cages): array
    {
        $out = [];
        foreach ($cages as $cage) {
            $est = $this->estimateForCage($cage);
            if ($est['latest_sampling_date'] === null) {
                continue;
            }
            $out[] = [
                'cage_id' => $cage->id,
                'cage' => [
                    'id' => $cage->id,
                    'number_of_fingerlings' => $cage->number_of_fingerlings,
                    'investor' => $cage->investor ? ['id' => $cage->investor->id, 'name' => $cage->investor->name] : null,
                ],
                'estimated_harvest_date' => $est['estimated_harvest_date'],
                'days_until_harvest' => $est['days_until_harvest'],
                'current_avg_weight_g' => $est['current_avg_weight_g'],
                'target_weight_g' => $est['target_weight_g'],
                'is_ready' => $est['is_ready'],
            ];
        }
        usort($out, fn ($a, $b) => ($a['days_until_harvest'] ?? PHP_INT_MAX) <=> ($b['days_until_harvest'] ?? PHP_INT_MAX));
        return $out;
    }

    /**
     * @param  Collection<int, \App\Models\Sampling>  $samplings
     * @return array{0: float, 1: string, 2: float, 3: string}|null [avg1, date1, avg2, date2] with date1 < date2 (chronological)
     */
    private function latestTwoSamplingsWithAvgWeight(Collection $samplings): ?array
    {
        $withAvg = [];
        foreach ($samplings as $s) {
            if (! $s->samples || $s->samples->isEmpty()) {
                continue;
            }
            $withAvg[] = [
                'avg' => (float) $s->samples->avg('weight'),
                'date' => $s->date_sampling,
            ];
        }
        if (count($withAvg) < 2) {
            return null;
        }
        usort($withAvg, fn ($a, $b) => strcmp($a['date'], $b['date']));
        $first = $withAvg[count($withAvg) - 2];
        $second = $withAvg[count($withAvg) - 1];
        return [$first['avg'], $first['date'], $second['avg'], $second['date']];
    }
}
