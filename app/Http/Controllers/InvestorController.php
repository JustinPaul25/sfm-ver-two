<?php

namespace App\Http\Controllers;

use App\Models\Investor;
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
        $investors = Investor::all();

        return response()->json($investors);
    }

    public function list(Request $request)
    {
        $query = Investor::query();

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
    
}
