<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Cage;
use App\Models\CageFeedConsumption;

class CageController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Cages/Index');
    }

    public function list(Request $request)
    {
        $query = Cage::query();

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('number_of_fingerlings', 'like', "%{$search}%");
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

    public function store(Request $request)
    {
        $request->validate([
            'number_of_fingerlings' => 'required|integer',
            'feed_types_id' => 'required|exists:feed_types,id',
            'investor_id' => 'required|exists:investors,id',
        ]);

        $cage = Cage::create($request->all());

        return response()->json([
            'message' => 'Cage created successfully',
            'cage' => $cage
        ]);
    }

    public function update(Request $request, Cage $cage)
    {
        $request->validate([
            'number_of_fingerlings' => 'required|integer',
            'feed_types_id' => 'required|exists:feed_types,id',
            'investor_id' => 'required|exists:investors,id',
        ]);

        $cage->update($request->all());

        return response()->json([
            'message' => 'Cage updated successfully',
            'cage' => $cage
        ]);
    }

    public function destroy(Cage $cage)
    {
        $cage->delete();

        return response()->json([
            'message' => 'Cage deleted successfully'
        ]);
    }

    public function show(Cage $cage)
    {
        $cage->load(['feedType', 'investor', 'feedConsumptions']);
        
        return Inertia::render('Cages/View', [
            'cage' => $cage,
            'feedConsumptions' => $cage->feedConsumptions()->orderBy('day_number')->get()
        ]);
    }

    public function getFeedConsumptions(Cage $cage)
    {
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
} 