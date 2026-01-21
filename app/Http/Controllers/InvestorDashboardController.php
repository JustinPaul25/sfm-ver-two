<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Sampling;
use App\Models\Sample;
use App\Models\Investor;
use App\Models\Cage;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class InvestorDashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        
        // Get the investor associated with this user
        $investor = Investor::find($user->investor_id);
        
        if (!$investor) {
            return Inertia::render('InvestorDashboard/NoInvestor', [
                'message' => 'You are not associated with any investor account. Please contact the administrator.'
            ]);
        }

        $period = $request->get('period', '30days');
        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');
        $cageNo = $request->get('cage_no');

        // Set date range based on period
        $dateRange = $this->getDateRange($period, $startDate, $endDate);
        
        $analytics = $this->getInvestorAnalytics(
            $investor->id, 
            $dateRange['start'], 
            $dateRange['end'], 
            $cageNo
        );

        return Inertia::render('InvestorDashboard/Index', array_merge($analytics, [
            'investor' => [
                'id' => $investor->id,
                'name' => $investor->name,
                'address' => $investor->address,
                'phone' => $investor->phone,
            ]
        ]));
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

    private function getInvestorAnalytics($investorId, $startDate, $endDate, $cageNo = null)
    {
        // Get cages for this investor
        $cagesQuery = Cage::where('investor_id', $investorId);
        if ($cageNo) {
            $cagesQuery->where('id', $cageNo);
        }
        $cages = $cagesQuery->get();
        $totalCages = $cages->count();
        
        // Get farmers linked to this investor
        $farmers = User::where('investor_id', $investorId)
            ->where('role', 'farmer')
            ->where('is_active', true)
            ->get();
        $totalFarmers = $farmers->count();
        
        // Build base query for filtering
        $samplingQuery = Sampling::where('investor_id', $investorId)
            ->whereBetween('date_sampling', [$startDate, $endDate]);
        
        if ($cageNo) {
            $samplingQuery->where('cage_no', $cageNo);
        }
        
        // Sampling analytics for the date range
        $samplingsInPeriod = $samplingQuery->count();
        $totalSamplings = Sampling::where('investor_id', $investorId)->count();
        
        // Sample analytics
        $sampleQuery = Sample::whereHas('sampling', function($query) use ($investorId, $startDate, $endDate, $cageNo) {
            $query->where('investor_id', $investorId)
                  ->whereBetween('date_sampling', [$startDate, $endDate]);
            if ($cageNo) {
                $query->where('cage_no', $cageNo);
            }
        });
        
        $samplesInPeriod = $sampleQuery->count();
        $totalSamples = Sample::where('investor_id', $investorId)->count();
        
        // Weight analytics
        $weightStats = $sampleQuery->selectRaw('
            COUNT(*) as total_samples,
            AVG(weight) as avg_weight,
            MIN(weight) as min_weight,
            MAX(weight) as max_weight,
            SUM(weight) as total_weight
        ')->first();

        // Sampling trends over time
        $samplingTrendsQuery = Sampling::selectRaw('
            DATE(date_sampling) as date,
            COUNT(DISTINCT samplings.id) as count,
            AVG(samples.weight) as avg_weight
        ')
        ->leftJoin('samples', 'samplings.id', '=', 'samples.sampling_id')
        ->where('samplings.investor_id', $investorId)
        ->whereBetween('date_sampling', [$startDate, $endDate]);
        
        if ($cageNo) {
            $samplingTrendsQuery->where('samplings.cage_no', $cageNo);
        }
        
        $samplingTrends = $samplingTrendsQuery->groupBy('date')
            ->orderBy('date')
            ->get();

        // Cage performance
        $cagePerformance = Cage::selectRaw('
            cages.id,
            cages.number_of_fingerlings,
            users.name as farmer_name,
            COUNT(samplings.id) as sampling_count,
            AVG(samples.weight) as avg_sample_weight
        ')
        ->leftJoin('users', 'cages.farmer_id', '=', 'users.id')
        ->leftJoin('samplings', function($join) use ($investorId) {
            $join->on('cages.id', '=', 'samplings.cage_no')
                 ->where('samplings.investor_id', '=', $investorId);
        })
        ->leftJoin('samples', 'samplings.id', '=', 'samples.sampling_id')
        ->where('cages.investor_id', $investorId)
        ->whereBetween('samplings.date_sampling', [$startDate, $endDate])
        ->groupBy('cages.id', 'cages.number_of_fingerlings', 'users.name')
        ->orderByDesc('avg_sample_weight')
        ->limit(10)
        ->get();

        // Feed type usage for investor's cages
        $feedTypeUsage = Cage::selectRaw('
            feed_types.feed_type,
            feed_types.brand,
            COUNT(*) as cage_count
        ')
        ->join('feed_types', 'cages.feed_types_id', '=', 'feed_types.id')
        ->where('cages.investor_id', $investorId)
        ->groupBy('feed_types.id', 'feed_types.feed_type', 'feed_types.brand')
        ->orderByDesc('cage_count')
        ->limit(5)
        ->get();

        // Growth metrics
        $growthMetrics = $this->calculateGrowthMetrics($investorId, $startDate, $endDate, $cageNo);

        // Get recent samplings
        $recentSamplingsQuery = Sampling::with(['samples'])
            ->where('investor_id', $investorId)
            ->whereBetween('date_sampling', [$startDate, $endDate]);
            
        if ($cageNo) {
            $recentSamplingsQuery->where('cage_no', $cageNo);
        }
        
        $recentSamplings = $recentSamplingsQuery->orderBy('date_sampling', 'desc')
            ->limit(10)
            ->get()
            ->map(function($sampling) {
                return [
                    'id' => $sampling->id,
                    'cage_no' => $sampling->cage_no,
                    'date_sampling' => $sampling->date_sampling,
                    'doc' => $sampling->doc,
                    'mortality' => $sampling->mortality ?? 0,
                    'sample_count' => $sampling->samples->count(),
                    'avg_weight' => $sampling->samples->avg('weight') ?? 0,
                ];
            });

        return [
            'analytics' => [
                'summary' => [
                    'total_cages' => $totalCages,
                    'total_farmers' => $totalFarmers,
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
                'sampling_trends' => $samplingTrends,
                'feed_type_usage' => $feedTypeUsage,
                'cage_performance' => $cagePerformance,
                'growth_metrics' => $growthMetrics,
                'recent_samplings' => $recentSamplings,
                'date_range' => [
                    'start' => $startDate->format('Y-m-d'),
                    'end' => $endDate->format('Y-m-d'),
                    'period' => $this->getPeriodLabel($startDate, $endDate),
                ],
            ],
            'cages' => $cages->map(function($cage) {
                return [
                    'id' => $cage->id,
                    'number_of_fingerlings' => $cage->number_of_fingerlings,
                    'farmer_name' => $cage->farmer ? $cage->farmer->name : 'Unassigned',
                ];
            }),
            'farmers' => $farmers->map(function($farmer) {
                return [
                    'id' => $farmer->id,
                    'name' => $farmer->name,
                    'email' => $farmer->email,
                    'cages_count' => $farmer->cages->count(),
                ];
            }),
        ];
    }

    private function calculateGrowthMetrics($investorId, $startDate, $endDate, $cageNo = null)
    {
        // Previous period for comparison
        $periodLength = $startDate->diffInDays($endDate);
        $previousStart = $startDate->copy()->subDays($periodLength);
        $previousEnd = $startDate->copy()->subDay();

        // Build query for current period
        $currentSamplingsQuery = Sampling::where('investor_id', $investorId)
            ->whereBetween('date_sampling', [$startDate, $endDate]);
        if ($cageNo) {
            $currentSamplingsQuery->where('cage_no', $cageNo);
        }
        $currentSamplings = $currentSamplingsQuery->count();
        
        $currentAvgWeightQuery = Sample::whereHas('sampling', function($query) use ($investorId, $startDate, $endDate, $cageNo) {
            $query->where('investor_id', $investorId)
                  ->whereBetween('date_sampling', [$startDate, $endDate]);
            if ($cageNo) {
                $query->where('cage_no', $cageNo);
            }
        });
        $currentAvgWeight = $currentAvgWeightQuery->avg('weight') ?? 0;

        // Previous period stats
        $previousSamplingsQuery = Sampling::where('investor_id', $investorId)
            ->whereBetween('date_sampling', [$previousStart, $previousEnd]);
        if ($cageNo) {
            $previousSamplingsQuery->where('cage_no', $cageNo);
        }
        $previousSamplings = $previousSamplingsQuery->count();
        
        $previousAvgWeightQuery = Sample::whereHas('sampling', function($query) use ($investorId, $previousStart, $previousEnd, $cageNo) {
            $query->where('investor_id', $investorId)
                  ->whereBetween('date_sampling', [$previousStart, $previousEnd]);
            if ($cageNo) {
                $query->where('cage_no', $cageNo);
            }
        });
        $previousAvgWeight = $previousAvgWeightQuery->avg('weight') ?? 0;

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
