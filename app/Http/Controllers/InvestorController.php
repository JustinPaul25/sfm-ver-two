<?php

namespace App\Http\Controllers;

use App\Models\Investor;
use App\Models\Cage;
use App\Models\Sampling;
use App\Models\Sample;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InvestorController extends Controller
{

    public function index(Request $request)
    {
        return Inertia::render('Investors/Index');
    }

    public function select(Request $request)
    {
        $user = $request->user();
        $query = Investor::whereNull('deleted_at');
        
        // Investors can only see their own investor record
        if ($user && $user->isInvestor()) {
            $query->where('id', $user->investor_id);
        }
        
        $investors = $query->get();

        return response()->json($investors);
    }

    public function list(Request $request)
    {
        $user = $request->user();
        $query = Investor::query();
        
        // Investors can only see their own investor record
        if ($user && $user->isInvestor()) {
            $query->where('id', $user->investor_id);
        }

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $investors = $query->paginate(10);

        return response()->json([
            'investors' => [
                'data' => $investors->items(),
                'current_page' => $investors->currentPage(),
                'last_page' => $investors->lastPage(),
                'per_page' => $investors->perPage(),
                'total' => $investors->total(),
                'from' => $investors->firstItem(),
                'to' => $investors->lastItem(),
            ],
            'filters' => $request->only(['search', 'page'])
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
        ]);

        $investor = Investor::create($request->all());

        return response()->json($investor);
    }

    public function update(Request $request, Investor $investor)
    {
        $investor->update($request->all());

        return response()->json($investor);
    }

    public function destroy(Investor $investor)
    {
        $investor->delete();

        return response()->json(null, 204);
    }

    public function report(Request $request, Investor $investor)
    {
        $user = $request->user();
        
        // Investors can only view their own report
        if ($user && $user->isInvestor() && $investor->id !== $user->investor_id) {
            return response()->json([
                'message' => 'You can only view your own investor report'
            ], 403);
        }
        
        // Get all cages for this investor
        $cages = $investor->cages()->with(['feedType', 'samplings.samples'])->get();
        
        // Get all samplings for all cages of this investor
        $cageIds = $cages->pluck('id')->toArray();
        $samplings = Sampling::with(['samples', 'cage'])
            ->where('investor_id', $investor->id)
            ->whereIn('cage_no', $cageIds)
            ->orderBy('date_sampling', 'desc')
            ->get();
        
        // If no cages, return empty report
        if ($cages->isEmpty()) {
            return Inertia::render('Investors/Report', [
                'investor' => [
                    'id' => $investor->id,
                    'name' => $investor->name,
                    'address' => $investor->address,
                    'phone' => $investor->phone,
                ],
                'summary' => [
                    'total_cages' => 0,
                    'total_fingerlings' => 0,
                    'total_samplings' => 0,
                    'total_samples' => 0,
                    'total_weight' => 0,
                    'total_weight_kg' => 0,
                    'avg_weight' => 0,
                    'min_weight' => 0,
                    'max_weight' => 0,
                    'total_mortality' => 0,
                    'total_present_stocks' => 0,
                    'total_biomass' => 0,
                    'earliest_sampling_date' => null,
                    'latest_sampling_date' => null,
                ],
                'cage_stats' => [],
                'samplings_by_cage' => [],
            ]);
        }

        // Calculate consolidated statistics
        $totalCages = $cages->count();
        $totalFingerlings = $cages->sum('number_of_fingerlings');
        
        // Calculate total samplings, samples, and weights across all cages
        $totalSamplings = $samplings->count();
        $totalSamples = 0;
        $totalWeight = 0;
        $allWeights = [];
        $totalMortality = 0;
        
        // Per-cage statistics
        $cageStats = [];
        foreach ($cages as $cage) {
            // Ensure proper type comparison (cage_no is integer after migration)
            $cageSamplings = $samplings->filter(function($sampling) use ($cage) {
                return (int)$sampling->cage_no === (int)$cage->id;
            });
            $cageTotalSamples = 0;
            $cageTotalWeight = 0;
            $cageMortality = 0;
            $cageWeights = [];
            
            foreach ($cageSamplings as $sampling) {
                $samples = $sampling->samples;
                $cageTotalSamples += $samples->count();
                $cageTotalWeight += $samples->sum('weight');
                $cageMortality += $sampling->mortality ?? 0;
                
                foreach ($samples as $sample) {
                    $cageWeights[] = $sample->weight;
                    $allWeights[] = $sample->weight;
                }
            }
            
            $totalSamples += $cageTotalSamples;
            $totalWeight += $cageTotalWeight;
            $totalMortality += $cageMortality;
            
            $cageStats[] = [
                'id' => $cage->id,
                'number_of_fingerlings' => $cage->number_of_fingerlings,
                'feed_type' => $cage->feedType->feed_type ?? 'N/A',
                'total_samplings' => $cageSamplings->count(),
                'total_samples' => $cageTotalSamples,
                'total_weight' => $cageTotalWeight,
                'avg_weight' => count($cageWeights) > 0 ? round(array_sum($cageWeights) / count($cageWeights), 2) : 0,
                'total_mortality' => $cageMortality,
                'present_stocks' => $cage->number_of_fingerlings - $cageMortality,
            ];
        }
        
        // Overall statistics
        $avgWeight = count($allWeights) > 0 ? round(array_sum($allWeights) / count($allWeights), 2) : 0;
        $minWeight = count($allWeights) > 0 ? min($allWeights) : 0;
        $maxWeight = count($allWeights) > 0 ? max($allWeights) : 0;
        $totalPresentStocks = $totalFingerlings - $totalMortality;
        $totalBiomass = round(($avgWeight * $totalPresentStocks) / 1000, 2); // in kg
        
        // Get latest sampling date
        $latestSampling = $samplings->first();
        $latestSamplingDate = $latestSampling ? $latestSampling->date_sampling : null;
        
        // Get earliest sampling date
        $earliestSampling = $samplings->last();
        $earliestSamplingDate = $earliestSampling ? $earliestSampling->date_sampling : null;
        
        // Organize samplings by cage for display
        $samplingsByCage = [];
        foreach ($cages as $cage) {
            $cageSamplings = $samplings->filter(function($sampling) use ($cage) {
                return (int)$sampling->cage_no === (int)$cage->id;
            })->values();
            if ($cageSamplings->count() > 0) {
                $samplingsByCage[] = [
                    'cage_id' => $cage->id,
                    'cage_fingerlings' => $cage->number_of_fingerlings,
                    'feed_type' => $cage->feedType->feed_type ?? 'N/A',
                    'samplings' => $cageSamplings->map(function($sampling) {
                        $samples = $sampling->samples;
                        $sampleCount = $samples->count();
                        $sampleWeight = $samples->sum('weight');
                        $avgWeight = $sampleCount > 0 ? round($sampleWeight / $sampleCount, 2) : 0;
                        
                        return [
                            'id' => $sampling->id,
                            'date_sampling' => $sampling->date_sampling,
                            'doc' => $sampling->doc,
                            'mortality' => $sampling->mortality ?? 0,
                            'sample_count' => $sampleCount,
                            'total_weight' => $sampleWeight,
                            'avg_weight' => $avgWeight,
                            'min_weight' => $samples->min('weight') ?? 0,
                            'max_weight' => $samples->max('weight') ?? 0,
                        ];
                    }),
                ];
            }
        }
        
        return Inertia::render('Investors/Report', [
            'investor' => [
                'id' => $investor->id,
                'name' => $investor->name,
                'address' => $investor->address,
                'phone' => $investor->phone,
            ],
            'summary' => [
                'total_cages' => $totalCages,
                'total_fingerlings' => $totalFingerlings,
                'total_samplings' => $totalSamplings,
                'total_samples' => $totalSamples,
                'total_weight' => $totalWeight,
                'total_weight_kg' => round($totalWeight / 1000, 2),
                'avg_weight' => $avgWeight,
                'min_weight' => $minWeight,
                'max_weight' => $maxWeight,
                'total_mortality' => $totalMortality,
                'total_present_stocks' => $totalPresentStocks,
                'total_biomass' => $totalBiomass,
                'earliest_sampling_date' => $earliestSamplingDate,
                'latest_sampling_date' => $latestSamplingDate,
            ],
            'cage_stats' => $cageStats,
            'samplings_by_cage' => $samplingsByCage,
        ]);
    }
    
}
