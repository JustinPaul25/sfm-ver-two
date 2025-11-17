<?php

namespace App\Http\Controllers\Api;

use App\Models\Cage;
use App\Models\Sample;
use App\Models\Sampling;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class DocsController extends Controller
{
    /**
     * Check if the provided key is valid
     * In a real system, you'd check against a database or config
     * For now, we'll use a simple validation or API key from config
     */
    private function checkKey($key)
    {
        // TODO: Implement proper API key validation
        // For now, we'll check against config or use a simple validation
        $validKey = config('services.api_key', env('API_KEY', 'default-api-key'));
        
        return $key !== $validKey;
    }

    /**
     * Get all cages with their samplings
     * 
     * GET /api/cages?key=your-api-key
     */
    public function index(Request $request)
    {
        if (!$request->filled("key")) {
            return response()->json(['message' => 'Key is required or the key is invalid'], 422);
        } else {
            $isInvalid = $this->checkKey($request->input('key'));

            if ($isInvalid) {
                return response()->json(['message' => 'Key is required or the key is invalid'], 422);
            }
        }

        $cages = Cage::with(['samplings', 'investor', 'feedType'])->get();

        return response()->json(
            [
                'message' => 'Cages fetch successfully',
                'data' => $cages,
            ],
            200
        );
    }

    /**
     * Calculate weight from height and width measurements
     * This is for mobile/device input where weight is calculated from dimensions
     * 
     * POST /api/weight?key=your-api-key
     * Body: { "height": 10, "width": 5, "doc": "DOC-20251116-25" }
     */
    public function getWeight(Request $request)
    {
        if (!$request->filled("key")) {
            return response()->json(['message' => 'Key is required or the key is invalid'], 422);
        } else {
            $isInvalid = $this->checkKey($request->input('key'));

            if ($isInvalid) {
                return response()->json(['message' => 'Key is required or the key is invalid'], 422);
            }
        }

        $rules = [
            'height' => 'required|numeric|min:0',
            'width' => 'required|numeric|min:0',
            'doc' => 'required|exists:samplings,doc',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Convert measurements to weight using the formula from the reference
        // Formula: weight = (width * (height^2)) / 690
        // Convert cm to inches: 1 cm = 0.3937 inches
        // Multiply by 453.592 to convert pounds to grams
        $height = ($request->input('height') * 0.3937) * 1.9;
        $width = $request->input('width') * 0.3937;
        $weight = ($width * ($height * $height)) / 690;
        $final_weight = $weight * 453.592;

        // Find the sampling using DOC instead of ID
        $sampling = Sampling::with('samples')
            ->where('doc', $request->input('doc'))
            ->firstOrFail();

        // Ensure there are always 30 sample slots for this sampling (1-30)
        $desiredSampleCount = 30;
        
        // Get all existing sample numbers for this sampling (convert to integers for proper comparison)
        $existingSampleNos = Sample::where('sampling_id', $sampling->id)
            ->pluck('sample_no')
            ->map(function($no) {
                return (int) $no; // Convert string to integer
            })
            ->toArray();
        
        // Create any missing samples from 1 to 30
        for ($i = 1; $i <= $desiredSampleCount; $i++) {
            if (!in_array($i, $existingSampleNos)) {
                Sample::create([
                    'investor_id' => $sampling->investor_id,
                    'sampling_id' => $sampling->id,
                    'sample_no' => (string) $i, // Store as string to match database type
                    'weight' => 0,
                ]);
            }
        }

        // Get the first sample with weight 0 (unfilled), ordered by sample_no as integer
        // Use CAST to ensure proper numeric ordering (not string ordering)
        $current_sample = Sample::where('weight', 0)
            ->where('sampling_id', $sampling->id)
            ->orderByRaw('CAST(sample_no AS UNSIGNED) ASC')
            ->first();

        if (!$current_sample) {
            // All 30 samples have already been filled
            return response()->json(['message' => 'All data is filled in this sampling.'], 422);
        }

        DB::beginTransaction();
        try {
            // Update the current sample with calculated weight
            $current_sample->update([
                'weight' => round($final_weight, 3)
            ]);

            // Recalculate statistics for the sampling
            $desiredSampleCount = 30;
            $total_weight = Sample::where('sampling_id', $sampling->id)->sum('weight');
            $has_data_count = Sample::where('weight', '>', 0)
                ->where('sampling_id', $sampling->id)
                ->count();

            // Calculate Average Body Weight (ABW)
            $abw = $has_data_count > 0 ? round($total_weight / $has_data_count, 3) : 0;

            // Remaining samples = total slots (30) - filled slots
            $remaining_samples = max(0, $desiredSampleCount - $has_data_count);

            // If you want to update sampling with these stats, uncomment below
            // Note: Add these fields to samplings table if they don't exist
            // $sampling->update([
            //     'abw' => $abw,
            //     'total_weight_gain' => round($total_weight, 3),
            //     'biomass' => round($abw, 3) // or calculate based on stocks
            // ]);

            DB::commit();

            return response()->json(
                [
                    'message' => 'Successfully get the fish weight',
                    'data' => [
                        'weight' => round($final_weight, 3),
                        'sample_no' => $current_sample->sample_no,
                        'abw' => $abw,
                        'total_weight' => round($total_weight, 3),
                        'remaining_samples' => $remaining_samples
                    ],
                ],
                200
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Something went wrong while processing.'], 422);
        }
    }

    /**
     * Calculate samplings for a given sampling session
     * This is for getting the next sample to measure
     * 
     * POST /api/sampling/calculate?key=your-api-key
     * Body: { "sampling_id": 1 }
     */
    public function calculateSamplings(Request $request)
    {
        if (!$request->filled("key")) {
            return response()->json(['message' => 'Key is required or the key is invalid'], 422);
        } else {
            $isInvalid = $this->checkKey($request->input('key'));

            if ($isInvalid) {
                return response()->json(['message' => 'Key is required or the key is invalid'], 422);
            }
        }

        $rules = [
            'sampling_id' => 'required|exists:samplings,id',
        ];

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Get the sampling with its samples
        $sampling = Sampling::with('samples')->findOrFail($request->input('sampling_id'));

        // Get the next unfilled sample
        $next_sample = Sample::where('weight', 0)
            ->where('sampling_id', $sampling->id)
            ->orderBy('sample_no', 'asc')
            ->first();

        if (!$next_sample) {
            return response()->json([
                'message' => 'All samples have been measured for this sampling.',
                'sampling_complete' => true
            ], 422);
        }

        // Get statistics
        $total_samples = $sampling->samples->count();
        $filled_samples = Sample::where('weight', '>', 0)
            ->where('sampling_id', $sampling->id)
            ->count();
        $remaining_samples = $total_samples - $filled_samples;

        return response()->json(
            [
                'message' => 'Next sample retrieved successfully',
                'data' => [
                    'sampling_id' => $sampling->id,
                    'current_sample' => [
                        'id' => $next_sample->id,
                        'sample_no' => $next_sample->sample_no,
                    ],
                    'progress' => [
                        'filled' => $filled_samples,
                        'remaining' => $remaining_samples,
                        'total' => $total_samples,
                        'percentage' => round(($filled_samples / $total_samples) * 100, 2)
                    ],
                    'sampling_info' => [
                        'investor' => $sampling->investor->name ?? 'N/A',
                        'date' => $sampling->date_sampling,
                        'doc' => $sampling->doc,
                    ]
                ],
            ],
            200
        );
    }

    /**
     * Get sampling details
     * 
     * GET /api/sampling/{id}?key=your-api-key
     */
    public function getSampling(Request $request, $id)
    {
        if (!$request->filled("key")) {
            return response()->json(['message' => 'Key is required or the key is invalid'], 422);
        } else {
            $isInvalid = $this->checkKey($request->input('key'));

            if ($isInvalid) {
                return response()->json(['message' => 'Key is required or the key is invalid'], 422);
            }
        }

        $sampling = Sampling::with(['investor', 'cage', 'samples'])
            ->findOrFail($id);

        // Calculate statistics
        $total_weight = $sampling->samples->sum('weight');
        $filled_samples = $sampling->samples->where('weight', '>', 0)->count();
        $avg_weight = $filled_samples > 0 ? round($total_weight / $filled_samples, 3) : 0;

        return response()->json(
            [
                'message' => 'Sampling retrieved successfully',
                'data' => [
                    'sampling' => $sampling,
                    'statistics' => [
                        'total_samples' => $sampling->samples->count(),
                        'filled_samples' => $filled_samples,
                        'total_weight' => round($total_weight, 3),
                        'average_weight' => $avg_weight,
                    ]
                ],
            ],
            200
        );
    }
}

