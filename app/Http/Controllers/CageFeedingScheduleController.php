<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\CageFeedingSchedule;
use App\Models\Cage;
use Inertia\Inertia;

class CageFeedingScheduleController extends Controller
{
    public function index(Request $request)
    {
        $cageId = $request->get('cage_id');
        
        if ($cageId) {
            $cage = Cage::with(['feedingSchedules', 'investor', 'feedType'])->find($cageId);
            
            if (!$cage) {
                return response()->json(['error' => 'Cage not found'], 404);
            }
            
            return Inertia::render('Cages/FeedingSchedule', [
                'cage' => $cage,
                'schedules' => $cage->feedingSchedules()->orderBy('created_at', 'desc')->get(),
                'activeSchedule' => $cage->feedingSchedule,
            ]);
        }
        
        // List all cages with their feeding schedules
        $user = $request->user();
        $cagesQuery = Cage::with(['feedingSchedule', 'investor', 'feedType'])
            ->orderBy('created_at', 'desc');
        
        // Investors can only see their own cages
        if ($user && $user->isInvestor()) {
            $cagesQuery->where('investor_id', $user->investor_id);
        }
        
        // Farmers can only see their own cages
        if ($user && $user->isFarmer()) {
            $cagesQuery->where('farmer_id', $user->id);
        }
        
        $cages = $cagesQuery->get()
            ->map(function ($cage) {
                return [
                    'id' => $cage->id,
                    'investor_name' => $cage->investor ? $cage->investor->name : null,
                    'feed_type' => $cage->feedType ? $cage->feedType->feed_type : null,
                    'number_of_fingerlings' => $cage->number_of_fingerlings,
                    'has_schedule' => $cage->feedingSchedule ? true : false,
                    'next_feeding' => $cage->feedingSchedule ? $cage->feedingSchedule->next_feeding_time : null,
                    'total_daily_amount' => $cage->feedingSchedule ? $cage->feedingSchedule->total_daily_amount : 0,
                ];
            });
        
        return Inertia::render('Cages/FeedingSchedules', [
            'cages' => $cages,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'cage_id' => 'required|exists:cages,id',
            'schedule_name' => 'required|string|max:255',
            'feeding_time_1' => 'nullable|date_format:H:i',
            'feeding_time_2' => 'nullable|date_format:H:i',
            'feeding_time_3' => 'nullable|date_format:H:i',
            'feeding_time_4' => 'nullable|date_format:H:i',
            'feeding_amount_1' => 'nullable|numeric|min:0',
            'feeding_amount_2' => 'nullable|numeric|min:0',
            'feeding_amount_3' => 'nullable|numeric|min:0',
            'feeding_amount_4' => 'nullable|numeric|min:0',
            'frequency' => 'required|in:daily,twice_daily,thrice_daily,four_times_daily',
            'notes' => 'nullable|string',
        ]);

        // Deactivate any existing active schedule for this cage
        CageFeedingSchedule::where('cage_id', $request->cage_id)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        $schedule = CageFeedingSchedule::create([
            'cage_id' => $request->cage_id,
            'schedule_name' => $request->schedule_name,
            'feeding_time_1' => $request->feeding_time_1,
            'feeding_time_2' => $request->feeding_time_2,
            'feeding_time_3' => $request->feeding_time_3,
            'feeding_time_4' => $request->feeding_time_4,
            'feeding_amount_1' => $request->feeding_amount_1 ?? 0,
            'feeding_amount_2' => $request->feeding_amount_2 ?? 0,
            'feeding_amount_3' => $request->feeding_amount_3 ?? 0,
            'feeding_amount_4' => $request->feeding_amount_4 ?? 0,
            'frequency' => $request->frequency,
            'is_active' => true,
            'notes' => $request->notes,
        ]);

        return response()->json([
            'message' => 'Feeding schedule created successfully',
            'schedule' => $schedule->load('cage'),
        ]);
    }

    public function update(Request $request, CageFeedingSchedule $schedule)
    {
        $request->validate([
            'schedule_name' => 'required|string|max:255',
            'feeding_time_1' => 'nullable|date_format:H:i',
            'feeding_time_2' => 'nullable|date_format:H:i',
            'feeding_time_3' => 'nullable|date_format:H:i',
            'feeding_time_4' => 'nullable|date_format:H:i',
            'feeding_amount_1' => 'nullable|numeric|min:0',
            'feeding_amount_2' => 'nullable|numeric|min:0',
            'feeding_amount_3' => 'nullable|numeric|min:0',
            'feeding_amount_4' => 'nullable|numeric|min:0',
            'frequency' => 'required|in:daily,twice_daily,thrice_daily,four_times_daily',
            'notes' => 'nullable|string',
        ]);

        $schedule->update([
            'schedule_name' => $request->schedule_name,
            'feeding_time_1' => $request->feeding_time_1,
            'feeding_time_2' => $request->feeding_time_2,
            'feeding_time_3' => $request->feeding_time_3,
            'feeding_time_4' => $request->feeding_time_4,
            'feeding_amount_1' => $request->feeding_amount_1 ?? 0,
            'feeding_amount_2' => $request->feeding_amount_2 ?? 0,
            'feeding_amount_3' => $request->feeding_amount_3 ?? 0,
            'feeding_amount_4' => $request->feeding_amount_4 ?? 0,
            'frequency' => $request->frequency,
            'notes' => $request->notes,
        ]);

        return response()->json([
            'message' => 'Feeding schedule updated successfully',
            'schedule' => $schedule->load('cage'),
        ]);
    }

    public function destroy(CageFeedingSchedule $schedule)
    {
        $schedule->delete();

        return response()->json([
            'message' => 'Feeding schedule deleted successfully',
        ]);
    }

    public function activate(CageFeedingSchedule $schedule)
    {
        // Deactivate all other schedules for this cage
        CageFeedingSchedule::where('cage_id', $schedule->cage_id)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        // Activate this schedule
        $schedule->update(['is_active' => true]);

        return response()->json([
            'message' => 'Feeding schedule activated successfully',
            'schedule' => $schedule->load('cage'),
        ]);
    }

    public function getTodaySchedule(Request $request)
    {
        $user = $request->user();
        $today = now()->format('Y-m-d');
        
        $query = CageFeedingSchedule::with(['cage.investor', 'cage.feedType'])
            ->where('is_active', true);
        
        // Investors can only see their own cages' schedules
        if ($user && $user->isInvestor()) {
            $query->whereHas('cage', function($q) use ($user) {
                $q->where('investor_id', $user->investor_id);
            });
        }
        
        // Farmers can only see their own cages' schedules
        if ($user && $user->isFarmer()) {
            $query->whereHas('cage', function($q) use ($user) {
                $q->where('farmer_id', $user->id);
            });
        }
        
        $schedules = $query->get()
            ->filter(function ($schedule) {
                return $schedule->isFeedingTime();
            })
            ->map(function ($schedule) {
                return [
                    'id' => $schedule->id,
                    'cage_id' => $schedule->cage_id,
                    'cage_name' => "Cage {$schedule->cage_id}",
                    'investor_name' => $schedule->cage && $schedule->cage->investor ? $schedule->cage->investor->name : null,
                    'schedule_name' => $schedule->schedule_name,
                    'next_feeding_time' => $schedule->next_feeding_time,
                    'feeding_times' => $schedule->feeding_times,
                    'feeding_amounts' => $schedule->feeding_amounts,
                    'total_daily_amount' => $schedule->total_daily_amount,
                    'notes' => $schedule->notes,
                ];
            });

        return response()->json([
            'date' => $today,
            'schedules' => $schedules->values(),
        ]);
    }

    public function getCageScheduleDetails(Request $request, Cage $cage)
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
        
        $schedule = $cage->feedingSchedule;
        
        if (!$schedule) {
            return response()->json([
                'has_schedule' => false,
                'message' => 'No active schedule found for this cage'
            ]);
        }

        // Get upcoming feedings for today and tomorrow
        $upcomingFeedings = $this->getUpcomingFeedings($schedule);

        return response()->json([
            'has_schedule' => true,
            'cage' => [
                'id' => $cage->id,
                'number_of_fingerlings' => (int) $cage->number_of_fingerlings,
                'investor_name' => $cage->investor ? $cage->investor->name : null,
                'feed_type' => $cage->feedType ? $cage->feedType->feed_type : null,
            ],
            'schedule' => [
                'id' => $schedule->id,
                'schedule_name' => $schedule->schedule_name,
                'frequency' => $schedule->frequency,
                'total_daily_amount' => (float) $schedule->total_daily_amount,
                'feeding_times' => $schedule->feeding_times,
                'feeding_amounts' => array_map(function($amount) {
                    return (float) $amount;
                }, $schedule->feeding_amounts),
                'next_feeding_time' => $schedule->next_feeding_time,
                'notes' => $schedule->notes,
                'created_at' => $schedule->created_at,
            ],
            'upcoming_feedings' => array_map(function($feeding) {
                return [
                    'date' => $feeding['date'],
                    'time' => $feeding['time'],
                    'amount' => (float) $feeding['amount'],
                    'formatted_time' => $feeding['formatted_time'],
                ];
            }, $upcomingFeedings),
        ]);
    }

    private function getUpcomingFeedings(CageFeedingSchedule $schedule)
    {
        $upcoming = [];
        $now = now();
        $today = $now->format('Y-m-d');
        $tomorrow = $now->copy()->addDay()->format('Y-m-d');
        
        $feedingTimes = $schedule->feeding_times;
        $feedingAmounts = $schedule->feeding_amounts;
        
        // Get remaining feedings for today
        foreach ($feedingTimes as $index => $time) {
            $feedingDateTime = $today . ' ' . $time;
            if (strtotime($feedingDateTime) > $now->timestamp) {
                $upcoming[] = [
                    'date' => $today,
                    'time' => $time,
                    'amount' => $feedingAmounts[$index] ?? 0,
                    'formatted_time' => date('g:i A', strtotime($feedingDateTime)),
                ];
            }
        }
        
        // Get all feedings for tomorrow
        foreach ($feedingTimes as $index => $time) {
            $upcoming[] = [
                'date' => $tomorrow,
                'time' => $time,
                'amount' => $feedingAmounts[$index] ?? 0,
                'formatted_time' => date('g:i A', strtotime($tomorrow . ' ' . $time)),
            ];
        }
        
        // Sort by date and time
        usort($upcoming, function($a, $b) {
            $dateCompare = strcmp($a['date'], $b['date']);
            if ($dateCompare !== 0) {
                return $dateCompare;
            }
            return strcmp($a['time'], $b['time']);
        });
        
        // Limit to next 5 feedings
        return array_slice($upcoming, 0, 5);
    }

    public function autoGenerate(Request $request)
    {
        $request->validate([
            'cage_ids' => 'required|array',
            'cage_ids.*' => 'exists:cages,id',
            'overwrite_existing' => 'boolean',
        ]);

        $cages = Cage::with(['investor', 'feedType', 'feedingSchedule', 'samplings.samples'])
            ->whereIn('id', $request->cage_ids)
            ->get();

        $generatedSchedules = [];
        $errors = [];

        foreach ($cages as $cage) {
            try {
                // Skip if cage already has an active schedule and overwrite is false
                if ($cage->feedingSchedule && !$request->overwrite_existing) {
                    $errors[] = "Cage {$cage->id} already has an active schedule";
                    continue;
                }

                $schedule = $this->generateScheduleForCage($cage);
                
                if ($schedule) {
                    $generatedSchedules[] = $schedule;
                }
            } catch (\Exception $e) {
                $errors[] = "Failed to generate schedule for Cage {$cage->id}: " . $e->getMessage();
            }
        }

        return response()->json([
            'message' => count($generatedSchedules) . ' schedules generated successfully',
            'generated_schedules' => $generatedSchedules,
            'errors' => $errors,
        ]);
    }

    private function generateScheduleForCage(Cage $cage)
    {
        // Calculate fish age based on sampling data
        $latestSampling = $cage->samplings()->with('samples')->latest()->first();
        $fishAge = $latestSampling ? abs(now()->diffInDays($latestSampling->date_sampling)) : 30; // Default 30 days
        
        // Calculate base feeding amount based on size and weight if available
        $totalDailyAmount = $this->calculateDailyFeedAmount($cage, $latestSampling, $fishAge);
        
        // Get weight info if available
        $avgWeight = null;
        $calculationMethod = 'age-based';
        if ($latestSampling && $latestSampling->samples) {
            $totalWeight = $latestSampling->samples->sum('weight');
            $sampleCount = $latestSampling->samples->count();
            if ($sampleCount > 0) {
                $avgWeight = round($totalWeight / $sampleCount, 2);
                $calculationMethod = 'weight-based';
            }
        }
        
        // Determine feeding frequency based on fish age and fingerling count
        $feedingFrequency = $this->determineFeedingFrequency($fishAge, $cage->number_of_fingerlings);
        
        // Generate feeding times based on frequency
        $feedingTimes = $this->generateFeedingTimes($feedingFrequency);
        
        // Distribute daily amount across feeding times
        $feedingAmounts = $this->distributeFeedingAmounts($totalDailyAmount, $feedingFrequency);
        
        // Deactivate existing active schedule
        CageFeedingSchedule::where('cage_id', $cage->id)
            ->where('is_active', true)
            ->update(['is_active' => false]);

        // Create new schedule
        $schedule = CageFeedingSchedule::create([
            'cage_id' => $cage->id,
            'schedule_name' => $this->generateScheduleName($cage, $feedingFrequency),
            'feeding_time_1' => $feedingTimes[0] ?? null,
            'feeding_time_2' => $feedingTimes[1] ?? null,
            'feeding_time_3' => $feedingTimes[2] ?? null,
            'feeding_time_4' => $feedingTimes[3] ?? null,
            'feeding_amount_1' => $feedingAmounts[0] ?? 0,
            'feeding_amount_2' => $feedingAmounts[1] ?? 0,
            'feeding_amount_3' => $feedingAmounts[2] ?? 0,
            'feeding_amount_4' => $feedingAmounts[3] ?? 0,
            'frequency' => $feedingFrequency,
            'is_active' => true,
            'notes' => $this->generateScheduleNotes($cage, $fishAge, $totalDailyAmount, $avgWeight, $calculationMethod),
        ]);

        return [
            'id' => $schedule->id,
            'cage_id' => $cage->id,
            'schedule_name' => $schedule->schedule_name,
            'feeding_frequency' => $feedingFrequency,
            'total_daily_amount' => $totalDailyAmount,
            'fish_age_days' => $fishAge,
            'avg_weight' => $avgWeight,
            'calculation_method' => $calculationMethod,
            'notes' => $schedule->notes,
        ];
    }

    /**
     * Calculate daily feed amount based on detected size & weight or age-based estimation
     * Uses the same formula as the sampling report: (Total Stocks × Avg Body Weight × Feeding Rate) / 1000
     */
    private function calculateDailyFeedAmount(Cage $cage, $latestSampling, $fishAge)
    {
        // If we have sampling data with weight measurements, use weight-based calculation
        if ($latestSampling && $latestSampling->samples) {
            $totalWeight = $latestSampling->samples->sum('weight');
            $sampleCount = $latestSampling->samples->count();
            
            if ($sampleCount > 0) {
                $avgWeight = $totalWeight / $sampleCount; // Average body weight in grams
                $totalStocks = $cage->number_of_fingerlings;
                $feedingRate = 3; // 3% of body weight per day
                
                // Daily Feed Ration (kg) = (Total Stocks × Avg Body Weight × Feeding Rate) / 1000
                $totalDailyAmount = ($totalStocks * $avgWeight * ($feedingRate / 100)) / 1000;
                
                return round($totalDailyAmount, 2);
            }
        }
        
        // Fallback to age-based calculation if no weight data
        return $this->calculateAgeBasedAmount($cage, $fishAge);
    }

    private function calculateAgeBasedAmount(Cage $cage, $fishAge)
    {
        // Feeding amount per fish based on age (grams per fish per day)
        $baseAmountPerFish = $this->calculateBaseAmountPerFish($fishAge);
        $totalDailyAmount = $cage->number_of_fingerlings * $baseAmountPerFish;
        
        // Convert grams to kg
        return round($totalDailyAmount / 1000, 2);
    }

    private function calculateBaseAmountPerFish($fishAge)
    {
        // Feeding amount per fish based on age (grams per fish per day)
        if ($fishAge <= 7) {
            return 0.05; // 0.05g per fish (very young)
        } elseif ($fishAge <= 14) {
            return 0.1; // 0.1g per fish
        } elseif ($fishAge <= 30) {
            return 0.2; // 0.2g per fish
        } elseif ($fishAge <= 60) {
            return 0.5; // 0.5g per fish
        } elseif ($fishAge <= 90) {
            return 1.0; // 1.0g per fish
        } else {
            return 1.5; // 1.5g per fish (mature)
        }
    }

    private function determineFeedingFrequency($fishAge, $fingerlingCount)
    {
        // Young fish need more frequent feeding
        if ($fishAge <= 14) {
            return 'four_times_daily';
        } elseif ($fishAge <= 30) {
            return 'thrice_daily';
        } elseif ($fishAge <= 60) {
            return 'twice_daily';
        } else {
            return 'daily';
        }
    }

    private function generateFeedingTimes($frequency)
    {
        switch ($frequency) {
            case 'four_times_daily':
                return ['06:00', '10:00', '14:00', '18:00'];
            case 'thrice_daily':
                return ['07:00', '12:00', '17:00'];
            case 'twice_daily':
                return ['08:00', '16:00'];
            case 'daily':
                return ['09:00'];
            default:
                return ['08:00'];
        }
    }

    private function distributeFeedingAmounts($totalDailyAmount, $frequency)
    {
        $amounts = [];
        
        switch ($frequency) {
            case 'four_times_daily':
                // Distribute evenly with slight morning bias
                $amounts = [
                    $totalDailyAmount * 0.25, // 25% at 6 AM
                    $totalDailyAmount * 0.25, // 25% at 10 AM
                    $totalDailyAmount * 0.25, // 25% at 2 PM
                    $totalDailyAmount * 0.25, // 25% at 6 PM
                ];
                break;
            case 'thrice_daily':
                // Morning, noon, evening distribution
                $amounts = [
                    $totalDailyAmount * 0.35, // 35% in morning
                    $totalDailyAmount * 0.30, // 30% at noon
                    $totalDailyAmount * 0.35, // 35% in evening
                ];
                break;
            case 'twice_daily':
                // Morning and evening
                $amounts = [
                    $totalDailyAmount * 0.45, // 45% in morning
                    $totalDailyAmount * 0.55, // 55% in evening
                ];
                break;
            case 'daily':
                // Single feeding
                $amounts = [$totalDailyAmount];
                break;
        }

        return array_map(function($amount) {
            return round($amount, 2);
        }, $amounts);
    }

    private function generateScheduleName(Cage $cage, $frequency)
    {
        $frequencyLabels = [
            'daily' => 'Daily',
            'twice_daily' => 'Twice Daily',
            'thrice_daily' => 'Thrice Daily',
            'four_times_daily' => 'Four Times Daily',
        ];

        $label = $frequencyLabels[$frequency] ?? 'Custom';
        return "Auto-Generated {$label} Schedule";
    }

    private function generateScheduleNotes(Cage $cage, $fishAge, $totalDailyAmount, $avgWeight = null, $calculationMethod = 'age-based')
    {
        $notes = "Auto-generated schedule for Cage {$cage->id}\n";
        $notes .= "Calculation Method: {$calculationMethod}\n";
        $notes .= "Fish Age: {$fishAge} days\n";
        
        if ($avgWeight !== null) {
            $notes .= "Average Body Weight: {$avgWeight} g\n";
            $notes .= "Feeding Rate: 3% of body weight\n";
        }
        
        $notes .= "Fingerlings: {$cage->number_of_fingerlings}\n";
        $notes .= "Daily Amount: {$totalDailyAmount} kg\n";
        $notes .= "Feed Type: " . ($cage->feedType ? $cage->feedType->feed_type : 'N/A') . "\n";
        $notes .= "Generated on: " . now()->format('Y-m-d H:i:s');
        
        return $notes;
    }
} 