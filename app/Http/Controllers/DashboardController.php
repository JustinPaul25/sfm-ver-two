<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Sampling;
use App\Models\Sample;
use App\Models\Investor;
use App\Models\Cage;
use App\Models\FeedType;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $period = $request->get('period', '30days'); // 30days, month, week, day, custom
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        // Set date range based on period
        $dateRange = $this->getDateRange($period, $startDate, $endDate);
        
        $analytics = $this->getAnalytics($dateRange['start'], $dateRange['end']);

        return Inertia::render('Dashboard', $analytics);
    }

    private function getDateRange($period, $startDate = null, $endDate = null)
    {
        $now = Carbon::now();

        switch ($period) {
            case 'day':
                $start = $now->copy()->startOfDay();
                $end = $now->copy()->endOfDay();
                break;
            case 'week':
                $start = $now->copy()->startOfWeek();
                $end = $now->copy()->endOfWeek();
                break;
            case '30days':
            default:
                $start = $now->copy()->subDays(30)->startOfDay();
                $end = $now->copy()->endOfDay();
                break;
            case 'month':
                $start = $now->copy()->startOfMonth();
                $end = $now->copy()->endOfMonth();
                break;
            case 'custom':
                $start = $startDate ? Carbon::parse($startDate)->startOfDay() : $now->copy()->subDays(30)->startOfDay();
                $end = $endDate ? Carbon::parse($endDate)->endOfDay() : $now->copy()->endOfDay();
                break;
        }

        return [
            'start' => $start,
            'end' => $end,
            'period' => $period
        ];
    }

    private function getAnalytics($startDate, $endDate)
    {
        // Total counts
        $totalInvestors = Investor::count();
        $totalCages = Cage::count();
        $totalFeedTypes = FeedType::count();
        
        // Sampling analytics for the date range
        $samplingsInPeriod = Sampling::whereBetween('date_sampling', [$startDate, $endDate])
            ->whereHas('investor', function($q) {
                $q->whereNull('deleted_at');
            })
            ->count();
        $totalSamplings = Sampling::count();
        
        // Sample analytics
        $samplesInPeriod = Sample::whereHas('sampling', function($query) use ($startDate, $endDate) {
            $query->whereBetween('date_sampling', [$startDate, $endDate])
                  ->whereHas('investor', function($q) {
                      $q->whereNull('deleted_at');
                  });
        })->count();
        $totalSamples = Sample::count();
        
        // Weight analytics
        $weightStats = Sample::whereHas('sampling', function($query) use ($startDate, $endDate) {
            $query->whereBetween('date_sampling', [$startDate, $endDate])
                  ->whereHas('investor', function($q) {
                      $q->whereNull('deleted_at');
                  });
        })->selectRaw('
            COUNT(*) as total_samples,
            AVG(weight) as avg_weight,
            MIN(weight) as min_weight,
            MAX(weight) as max_weight,
            SUM(weight) as total_weight
        ')->first();

        // Top performing investors
        $topInvestors = Investor::withCount(['samplings' => function($query) use ($startDate, $endDate) {
            $query->whereBetween('date_sampling', [$startDate, $endDate]);
        }])
        ->withSum(['samples' => function($query) use ($startDate, $endDate) {
            $query->whereHas('sampling', function($q) use ($startDate, $endDate) {
                $q->whereBetween('date_sampling', [$startDate, $endDate]);
            });
        }], 'weight')
        ->whereNull('deleted_at')
        ->orderByDesc('samplings_count')
        ->limit(5)
        ->get();

        // Sampling trends over time
        $samplingTrends = Sampling::selectRaw('
            DATE(date_sampling) as date,
            COUNT(DISTINCT samplings.id) as count,
            AVG(samples.weight) as avg_weight
        ')
        ->leftJoin('samples', 'samplings.id', '=', 'samples.sampling_id')
        ->join('investors', 'samplings.investor_id', '=', 'investors.id')
        ->whereNull('investors.deleted_at')
        ->whereBetween('date_sampling', [$startDate, $endDate])
        ->groupBy('date')
        ->orderBy('date')
        ->get();

        // Feed type usage
        $feedTypeUsage = Cage::selectRaw('
            feed_types.feed_type,
            feed_types.brand,
            COUNT(*) as cage_count
        ')
        ->join('feed_types', 'cages.feed_types_id', '=', 'feed_types.id')
        ->groupBy('feed_types.id', 'feed_types.feed_type', 'feed_types.brand')
        ->orderByDesc('cage_count')
        ->limit(5)
        ->get();

        // Cage performance
        $cagePerformance = Cage::selectRaw('
            cages.id,
            cages.number_of_fingerlings,
            investors.name as investor_name,
            COUNT(samplings.id) as sampling_count,
            AVG(samples.weight) as avg_sample_weight
        ')
        ->leftJoin('investors', 'cages.investor_id', '=', 'investors.id')
        ->leftJoin('samplings', 'cages.id', '=', 'samplings.cage_no')
        ->leftJoin('samples', 'samplings.id', '=', 'samples.sampling_id')
        ->whereNull('investors.deleted_at')
        ->whereBetween('samplings.date_sampling', [$startDate, $endDate])
        ->groupBy('cages.id', 'cages.number_of_fingerlings', 'investors.name')
        ->orderByDesc('avg_sample_weight')
        ->limit(5)
        ->get();

        // Growth metrics
        $growthMetrics = $this->calculateGrowthMetrics($startDate, $endDate);

        return [
            'analytics' => [
                'summary' => [
                    'total_investors' => $totalInvestors,
                    'total_cages' => $totalCages,
                    'total_feed_types' => $totalFeedTypes,
                    'samplings_in_period' => $samplingsInPeriod,
                    'total_samplings' => $totalSamplings,
                    'samples_in_period' => $samplesInPeriod,
                    'total_samples' => $totalSamples,
                ],
                'weight_stats' => [
                    'total_samples' => $weightStats->total_samples ?? 0,
                    'avg_weight' => round($weightStats->avg_weight ?? 0, 2),
                    'min_weight' => $weightStats->min_weight ?? 0,
                    'max_weight' => $weightStats->max_weight ?? 0,
                    'total_weight' => $weightStats->total_weight ?? 0,
                ],
                'top_investors' => $topInvestors,
                'sampling_trends' => $samplingTrends,
                'feed_type_usage' => $feedTypeUsage,
                'cage_performance' => $cagePerformance,
                'growth_metrics' => $growthMetrics,
                'date_range' => [
                    'start' => $startDate->format('Y-m-d'),
                    'end' => $endDate->format('Y-m-d'),
                    'period' => $this->getPeriodLabel($startDate, $endDate),
                ],
            ],
        ];
    }

    private function calculateGrowthMetrics($startDate, $endDate)
    {
        // Previous period for comparison
        $periodLength = $startDate->diffInDays($endDate);
        $previousStart = $startDate->copy()->subDays($periodLength);
        $previousEnd = $startDate->copy()->subDay();

        // Current period stats
        $currentSamplings = Sampling::whereBetween('date_sampling', [$startDate, $endDate])
            ->whereHas('investor', function($q) {
                $q->whereNull('deleted_at');
            })
            ->count();
        $currentAvgWeight = Sample::whereHas('sampling', function($query) use ($startDate, $endDate) {
            $query->whereBetween('date_sampling', [$startDate, $endDate])
                  ->whereHas('investor', function($q) {
                      $q->whereNull('deleted_at');
                  });
        })->avg('weight') ?? 0;

        // Previous period stats
        $previousSamplings = Sampling::whereBetween('date_sampling', [$previousStart, $previousEnd])
            ->whereHas('investor', function($q) {
                $q->whereNull('deleted_at');
            })
            ->count();
        $previousAvgWeight = Sample::whereHas('sampling', function($query) use ($previousStart, $previousEnd) {
            $query->whereBetween('date_sampling', [$previousStart, $previousEnd])
                  ->whereHas('investor', function($q) {
                      $q->whereNull('deleted_at');
                  });
        })->avg('weight') ?? 0;

        // Calculate growth percentages
        $samplingGrowth = $previousSamplings > 0 ? 
            (($currentSamplings - $previousSamplings) / $previousSamplings) * 100 : 0;
        
        $weightGrowth = $previousAvgWeight > 0 ? 
            (($currentAvgWeight - $previousAvgWeight) / $previousAvgWeight) * 100 : 0;

        return [
            'sampling_growth' => round($samplingGrowth, 2),
            'weight_growth' => round($weightGrowth, 2),
            'current_samplings' => $currentSamplings,
            'previous_samplings' => $previousSamplings,
            'current_avg_weight' => round($currentAvgWeight, 2),
            'previous_avg_weight' => round($previousAvgWeight, 2),
        ];
    }

    private function getPeriodLabel($startDate, $endDate)
    {
        if ($startDate->isSameDay($endDate)) {
            return 'Today';
        }
        
        if ($startDate->diffInDays($endDate) <= 7) {
            return 'This Week';
        }
        
        if ($startDate->month === $endDate->month && $startDate->year === $endDate->year) {
            return 'This Month';
        }
        
        return 'Custom Period';
    }
} 