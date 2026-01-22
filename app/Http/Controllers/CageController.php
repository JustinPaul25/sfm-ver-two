<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Cage;
use App\Models\CageFeedConsumption;
use App\Models\Sampling;

class CageController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Cages/Index');
    }

    public function verification(Request $request)
    {
        return Inertia::render('Cages/Verification');
    }

    public function list(Request $request)
    {
        $user = $request->user();
        $query = Cage::with(['investor', 'farmer']);

        // Investors can only see their own cages
        if ($user && $user->isInvestor()) {
            $query->where('investor_id', $user->investor_id);
        }

        // Farmers can only see their own cages
        if ($user && $user->isFarmer()) {
            $query->where('farmer_id', $user->id);
        }

        if ($request->has('search') && $request->get('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('number_of_fingerlings', 'like', "%{$search}%")
                  ->orWhereHas('investor', function($investorQuery) use ($search) {
                      $investorQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $cages = $query->paginate(10);

        return response()->json([
            'cages' => [
                'data' => $cages->items(),
                'current_page' => $cages->currentPage(),
                'last_page' => $cages->lastPage(),
                'per_page' => $cages->perPage(),
                'total' => $cages->total(),
                'from' => $cages->firstItem(),
                'to' => $cages->lastItem(),
            ],
            'filters' => $request->only(['search', 'page'])
        ]);
    }

    public function select(Request $request)
    {
        $user = $request->user();
        $investorId = $request->get('investor_id');
        
        $query = Cage::query()
            ->whereHas('investor', function($q) {
                $q->whereNull('deleted_at');
            });
        
        // Investors can only see their own cages
        if ($user && $user->isInvestor()) {
            $query->where('investor_id', $user->investor_id);
        }
        
        // Farmers can only see their own cages
        if ($user && $user->isFarmer()) {
            $query->where('farmer_id', $user->id);
        }
        
        if ($investorId) {
            $query->where('investor_id', $investorId);
        }
        
        $cages = $query->get();
        
        return response()->json($cages);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        
        // Investors cannot create cages
        if ($user && $user->isInvestor()) {
            return response()->json([
                'message' => 'Investors cannot create cages'
            ], 403);
        }

        $request->validate([
            'number_of_fingerlings' => 'required|integer',
            'feed_types_id' => 'required|exists:feed_types,id',
            'investor_id' => 'required|exists:investors,id',
            'farmer_id' => 'nullable|exists:users,id',
        ]);

        $data = $request->all();
        
        // Only admin can assign farmers to cages
        // Farmers creating cages are automatically assigned to their own cages
        if ($user && $user->isFarmer()) {
            $data['farmer_id'] = $user->id;
        }

        // Validate that farmer belongs to the same investor
        if (isset($data['farmer_id'])) {
            $farmer = \App\Models\User::find($data['farmer_id']);
            if ($farmer && $farmer->investor_id != $data['investor_id']) {
                return response()->json([
                    'message' => 'Farmer must belong to the same investor'
                ], 422);
            }
        }

        $cage = Cage::create($data);
        $cage->load(['investor', 'farmer', 'feedType']);

        return response()->json([
            'message' => 'Cage created successfully',
            'cage' => $cage
        ]);
    }

    public function update(Request $request, Cage $cage)
    {
        $user = $request->user();
        
        // Investors cannot update cages
        if ($user && $user->isInvestor()) {
            return response()->json([
                'message' => 'Investors cannot update cages'
            ], 403);
        }

        // Farmers can only update their own cages (but cannot reassign farmer)
        if ($user && $user->isFarmer() && $cage->farmer_id !== $user->id) {
            return response()->json([
                'message' => 'You can only update your own cages'
            ], 403);
        }

        $request->validate([
            'number_of_fingerlings' => 'required|integer',
            'feed_types_id' => 'required|exists:feed_types,id',
            'investor_id' => 'required|exists:investors,id',
            'farmer_id' => 'nullable|exists:users,id',
        ]);

        $data = $request->all();

        // Farmers cannot change the farmer assignment
        if ($user && $user->isFarmer()) {
            unset($data['farmer_id']);
        }

        // Validate that farmer belongs to the same investor
        if (isset($data['farmer_id']) && $data['farmer_id']) {
            $farmer = \App\Models\User::find($data['farmer_id']);
            if ($farmer && $farmer->investor_id != $data['investor_id']) {
                return response()->json([
                    'message' => 'Farmer must belong to the same investor'
                ], 422);
            }
        }

        $cage->update($data);
        $cage->load(['investor', 'farmer', 'feedType']);

        return response()->json([
            'message' => 'Cage updated successfully',
            'cage' => $cage
        ]);
    }

    public function destroy(Request $request, Cage $cage)
    {
        $user = $request->user();
        
        // Investors cannot delete cages
        if ($user && $user->isInvestor()) {
            return response()->json([
                'message' => 'Investors cannot delete cages'
            ], 403);
        }

        // Farmers can only delete their own cages
        if ($user && $user->isFarmer() && $cage->farmer_id !== $user->id) {
            return response()->json([
                'message' => 'You can only delete your own cages'
            ], 403);
        }

        // Clean up related records to avoid foreign key constraint errors
        // Delete feed consumptions linked to this cage
        $cage->feedConsumptions()->delete();

        // Delete all feeding schedules for this cage
        $cage->feedingSchedules()->delete();

        // Delete related samplings and their samples
        $samplings = $cage->samplings()->with('samples')->get();
        foreach ($samplings as $sampling) {
            $sampling->samples()->delete();
            $sampling->delete();
        }

        // Finally delete the cage itself
        $cage->delete();

        return response()->json([
            'message' => 'Cage deleted successfully'
        ]);
    }

    public function show(Request $request, Cage $cage)
    {
        $user = $request->user();
        
        // Investors can only view their own cages
        if ($user && $user->isInvestor() && $cage->investor_id !== $user->investor_id) {
            return response()->json([
                'message' => 'You can only view your own cages'
            ], 403);
        }
        
        // Farmers can only view their own cages
        if ($user && $user->isFarmer() && $cage->farmer_id !== $user->id) {
            return response()->json([
                'message' => 'You can only view your own cages'
            ], 403);
        }
        
        $cage->load(['feedType', 'investor', 'feedingSchedule']);
        
        // Paginate feed consumptions - order by latest date first
        $perPage = $request->get('per_page', 10);
        $page = $request->get('page', 1);
        $feedConsumptions = $cage->feedConsumptions()
            ->orderBy('consumption_date', 'desc')
            ->orderBy('day_number', 'desc')
            ->paginate($perPage, ['*'], 'page', $page);
        
        return Inertia::render('Cages/View', [
            'cage' => $cage,
            'feedConsumptions' => $feedConsumptions,
            'feedingSchedule' => $cage->feedingSchedule
        ]);
    }

    public function getFeedConsumptions(Request $request, Cage $cage)
    {
        $user = $request->user();
        
        // Investors can only view their own cages
        if ($user && $user->isInvestor() && $cage->investor_id !== $user->investor_id) {
            return response()->json([
                'message' => 'You can only view your own cages'
            ], 403);
        }
        
        // Farmers can only view their own cages
        if ($user && $user->isFarmer() && $cage->farmer_id !== $user->id) {
            return response()->json([
                'message' => 'You can only view your own cages'
            ], 403);
        }
        
        try {
            $consumptions = $cage->feedConsumptions()
                ->orderBy('day_number')
                ->get();

            return response()->json([
                'consumptions' => $consumptions
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error loading feed consumptions: ' . $e->getMessage(),
                'error' => true
            ], 500);
        }
    }

    public function storeFeedConsumption(Request $request, Cage $cage)
    {
        try {
            $request->validate([
                'day_number' => 'required|integer|min:1',
                'feed_amount' => 'required|numeric|min:0',
                'consumption_date' => 'required|date',
                'notes' => 'nullable|string',
            ]);

            // Check if consumption for this day already exists
            $existing = CageFeedConsumption::where('cage_id', $cage->id)
                ->where('day_number', $request->day_number)
                ->first();

            if ($existing) {
                return back()->withErrors([
                    'message' => 'Feed consumption for day ' . $request->day_number . ' already exists'
                ]);
            }

            $consumption = CageFeedConsumption::create([
                'cage_id' => $cage->id,
                'day_number' => $request->day_number,
                'feed_amount' => $request->feed_amount,
                'consumption_date' => $request->consumption_date,
                'notes' => $request->notes,
            ]);

            return back()->with('success', 'Feed consumption recorded successfully');
        } catch (\Exception $e) {
            return back()->withErrors([
                'message' => 'Error creating feed consumption: ' . $e->getMessage()
            ]);
        }
    }

    public function updateFeedConsumption(Request $request, Cage $cage, CageFeedConsumption $consumption)
    {
        try {
            $request->validate([
                'feed_amount' => 'required|numeric|min:0',
                'consumption_date' => 'required|date',
                'notes' => 'nullable|string',
            ]);

            $consumption->update($request->all());

            return back()->with('success', 'Feed consumption updated successfully');
        } catch (\Exception $e) {
            return back()->withErrors([
                'message' => 'Error updating feed consumption: ' . $e->getMessage()
            ]);
        }
    }

    public function destroyFeedConsumption(Cage $cage, CageFeedConsumption $consumption)
    {
        try {
            $consumption->delete();

            return back()->with('success', 'Feed consumption deleted successfully');
        } catch (\Exception $e) {
            return back()->withErrors([
                'message' => 'Error deleting feed consumption: ' . $e->getMessage()
            ]);
        }
    }

    public function verificationData(Request $request)
    {
        $user = $request->user();
        
        // Build query with relationships
        $query = Cage::with(['investor', 'feedType']);
        
        // Investors can only see their own cages
        if ($user && $user->isInvestor()) {
            $query->where('investor_id', $user->investor_id);
        }
        
        // Farmers can only see their own cages
        if ($user && $user->isFarmer()) {
            $query->where('farmer_id', $user->id);
        }
        
        // Get all cages
        $cages = $query->get();
        
        // Build verification data for each cage
        $verificationData = $cages->map(function ($cage) {
            // Get the latest sampling for this cage
            $latestSampling = Sampling::with('samples')
                ->where('cage_no', $cage->id)
                ->orderBy('date_sampling', 'desc')
                ->first();
            
            $avgWeight = null;
            $avgLength = null;
            $avgWidth = null;
            $numberOfFish = $cage->number_of_fingerlings;
            $mortality = 0;
            $presentStocks = $numberOfFish;
            $lastSamplingDate = null;
            
            if ($latestSampling) {
                $samples = $latestSampling->samples;
                
                // Calculate averages from samples
                if ($samples->count() > 0) {
                    $totalWeight = $samples->sum('weight');
                    $avgWeight = round($totalWeight / $samples->count(), 2);
                    
                    $samplesWithLength = $samples->whereNotNull('length');
                    if ($samplesWithLength->count() > 0) {
                        $avgLength = round($samplesWithLength->avg('length'), 1);
                    }
                    
                    $samplesWithWidth = $samples->whereNotNull('width');
                    if ($samplesWithWidth->count() > 0) {
                        $avgWidth = round($samplesWithWidth->avg('width'), 1);
                    }
                }
                
                $mortality = $latestSampling->mortality ?? 0;
                $presentStocks = $numberOfFish - $mortality;
                $lastSamplingDate = $latestSampling->date_sampling;
            }
            
            return [
                'id' => $cage->id,
                'number_of_fingerlings' => $numberOfFish,
                'mortality' => $mortality,
                'present_stocks' => $presentStocks,
                'investor' => $cage->investor ? $cage->investor->name : 'N/A',
                'feed_type' => $cage->feedType ? $cage->feedType->feed_type : 'N/A',
                'avg_weight' => $avgWeight,
                'avg_length' => $avgLength,
                'avg_width' => $avgWidth,
                'last_sampling_date' => $lastSamplingDate,
            ];
        })->sortBy('id')->values();
        
        return response()->json([
            'verification_data' => $verificationData
        ]);
    }
} 