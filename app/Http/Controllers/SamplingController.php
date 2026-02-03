<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Sampling;
use App\Models\Sample;
use App\Models\CageFeedConsumption;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class SamplingController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Samplings/Index');
    }

    public function list(Request $request)
    {
        $user = $request->user();
        $query = Sampling::with('investor')->withCount('samples')
            // Hide samplings whose investor has been soft-deleted
            ->whereHas('investor', function($q) {
                $q->whereNull('deleted_at');
            });

        // Investors can only see their own samplings
        if ($user && $user->isInvestor()) {
            $query->where('investor_id', $user->investor_id);
        }

        // Farmers can only see samplings for their own cages
        if ($user && $user->isFarmer()) {
            $query->whereHas('cage', function($q) use ($user) {
                $q->where('farmer_id', $user->id);
            });
        }

        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('doc', 'like', "%{$search}%")
                  ->orWhere('cage_no', 'like', "%{$search}%")
                  ->orWhere('date_sampling', 'like', "%{$search}%")
                  ->orWhereHas('investor', function($investorQuery) use ($search) {
                      $investorQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $samplings = $query->orderBy('date_sampling', 'desc')->paginate(10);

        return response()->json([
            'samplings' => [
                'data' => $samplings->items(),
                'current_page' => $samplings->currentPage(),
                'last_page' => $samplings->lastPage(),
                'per_page' => $samplings->perPage(),
                'total' => $samplings->total(),
                'from' => $samplings->firstItem(),
                'to' => $samplings->lastItem(),
            ],
            'filters' => $request->only(['search', 'page'])
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        
        // Investors cannot create samplings
        if ($user && $user->isInvestor()) {
            return response()->json([
                'message' => 'Investors cannot create samplings'
            ], 403);
        }

        $request->validate([
            'investor_id' => 'required|exists:investors,id',
            'date_sampling' => 'required|date',
            // cage_no is a foreign key to cages.id
            'cage_no' => 'required|exists:cages,id',
            'mortality' => 'nullable|integer|min:0',
        ]);

        // Normalize payload
        $data = $request->all();
        if (isset($data['cage_no'])) {
            // Ensure cage_no is stored as an integer that matches cages.id
            $data['cage_no'] = (int) $data['cage_no'];
            
            // Automatically get feed_types_id from the cage
            $cage = \App\Models\Cage::find($data['cage_no']);
            if ($cage && $cage->feed_types_id) {
                $data['feed_types_id'] = $cage->feed_types_id;
            }

            // Farmers can only create samplings for their own cages
            if ($user && $user->isFarmer() && $cage->farmer_id !== $user->id) {
                return response()->json([
                    'message' => 'You can only create samplings for your own cages'
                ], 403);
            }
        }

        // Auto-generate a unique DOC value based on the sampling date
        $data['doc'] = $this->generateDoc($data['date_sampling'] ?? now()->toDateString());
        
        $sampling = Sampling::create($data);

        return response()->json([
            'message' => 'Sampling created successfully',
            'sampling' => $sampling
        ]);
    }

    public function update(Request $request, Sampling $sampling)
    {
        $user = $request->user();
        
        // Investors cannot update samplings
        if ($user && $user->isInvestor()) {
            return response()->json([
                'message' => 'Investors cannot update samplings'
            ], 403);
        }

        // Farmers can only update samplings for their own cages
        if ($user && $user->isFarmer()) {
            $cage = $sampling->cage;
            if (!$cage || $cage->farmer_id !== $user->id) {
                return response()->json([
                    'message' => 'You can only update samplings for your own cages'
                ], 403);
            }
        }

        $request->validate([
            'investor_id' => 'required|exists:investors,id',
            'date_sampling' => 'required|date',
            // cage_no is a foreign key to cages.id
            'cage_no' => 'required|exists:cages,id',
            'mortality' => 'nullable|integer|min:0',
        ]);

        // Normalize payload
        $data = $request->all();
        if (isset($data['cage_no'])) {
            $data['cage_no'] = (int) $data['cage_no'];
            
            // Automatically get feed_types_id from the cage
            $cage = \App\Models\Cage::find($data['cage_no']);
            if ($cage && $cage->feed_types_id) {
                $data['feed_types_id'] = $cage->feed_types_id;
            }

            // Farmers can only update samplings for their own cages
            if ($user && $user->isFarmer() && $cage->farmer_id !== $user->id) {
                return response()->json([
                    'message' => 'You can only update samplings for your own cages'
                ], 403);
            }
        }

        // Preserve existing DOC; do not allow it to be changed from the request
        unset($data['doc']);
        
        $sampling->update($data);

        return response()->json([
            'message' => 'Sampling updated successfully',
            'sampling' => $sampling
        ]);
    }

    public function destroy(Request $request, Sampling $sampling)
    {
        $user = $request->user();
        
        // Investors cannot delete samplings
        if ($user && $user->isInvestor()) {
            return response()->json([
                'message' => 'Investors cannot delete samplings'
            ], 403);
        }

        // Farmers can only delete samplings for their own cages
        if ($user && $user->isFarmer()) {
            $cage = $sampling->cage;
            if (!$cage || $cage->farmer_id !== $user->id) {
                return response()->json([
                    'message' => 'You can only delete samplings for your own cages'
                ], 403);
            }
        }

        // Delete related samples first to satisfy foreign key constraints
        $sampling->samples()->delete();

        $sampling->delete();

        return response()->json([
            'message' => 'Sampling deleted successfully'
        ]);
    }

    public function destroySample(Request $request, Sample $sample)
    {
        $user = $request->user();
        
        // Investors cannot delete samples
        if ($user && $user->isInvestor()) {
            return response()->json([
                'message' => 'Investors cannot delete samples'
            ], 403);
        }

        // Get the sampling to check cage ownership for farmers
        $sampling = $sample->sampling;
        if (!$sampling) {
            return response()->json([
                'message' => 'Sample not found or has no associated sampling'
            ], 404);
        }

        // Farmers can only delete samples for their own cages
        if ($user && $user->isFarmer()) {
            $cage = $sampling->cage;
            if (!$cage || $cage->farmer_id !== $user->id) {
                return response()->json([
                    'message' => 'You can only delete samples for your own cages'
                ], 403);
            }
        }

        $sample->delete();

        return response()->json([
            'message' => 'Sample deleted successfully'
        ]);
    }

    /**
     * Generate a unique DOC string for a sampling.
     * Format: DOC-YYYYMMDD-XXXXX
     */
    private function generateDoc(string $dateSampling): string
    {
        $prefix = 'DOC-' . Carbon::parse($dateSampling)->format('Ymd');

        do {
            $suffix = str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT);
            $doc = $prefix . '-' . $suffix;
        } while (Sampling::where('doc', $doc)->exists());

        return $doc;
    }

    public function report(Request $request)
    {
        $user = $request->user();
        $samplingId = $request->get('sampling');
        
        if ($samplingId) {
            // Get specific sampling data with cage information
            $samplingQuery = Sampling::with(['investor', 'samples', 'feedType'])
                ->whereHas('investor', function($q) {
                    $q->whereNull('deleted_at');
                });
            
            // Investors can only view their own samplings
            if ($user && $user->isInvestor()) {
                $samplingQuery->where('investor_id', $user->investor_id);
            }
            
            // Farmers can only view samplings for their own cages
            if ($user && $user->isFarmer()) {
                $samplingQuery->whereHas('cage', function($q) use ($user) {
                    $q->where('farmer_id', $user->id);
                });
            }
            
            $sampling = $samplingQuery->find($samplingId);
            
            if ($sampling) {
                // Get cage information for accurate biomass calculation
                $cage = \App\Models\Cage::with('feedType')->where('id', $sampling->cage_no)->first();
                $numberOfFish = $cage ? $cage->number_of_fingerlings : 5000; // Fallback to default
                $mortality = $sampling->mortality ?? 0;
                $presentStocks = $numberOfFish - $mortality;
                
                // Calculate summary statistics
                $samples = $sampling->samples;
                $totalWeight = $samples->sum('weight');
                $totalSamples = $samples->count();
                $avgWeight = $totalSamples > 0 ? round($totalWeight / $totalSamples, 2) : 0;
                
                // Calculate average length and width from samples (all fish in one cage are sized at the same time)
                $avgLength = $samples->whereNotNull('length')->count() > 0 
                    ? round($samples->whereNotNull('length')->avg('length'), 1) : null;
                $avgWidth = $samples->whereNotNull('width')->count() > 0 
                    ? round($samples->whereNotNull('width')->avg('width'), 1) : null;
                
                // Get feed type name from sampling (preferred) or from cage
                $feedTypeName = null;
                if ($sampling->feedType) {
                    $feedTypeName = $sampling->feedType->feed_type;
                } elseif ($cage && $cage->feedType) {
                    $feedTypeName = $cage->feedType->feed_type;
                }
                
                // Calculate biomass (kg) = (Average Body Weight × Present Stocks) / 1000
                $biomass = round(($avgWeight * $presentStocks) / 1000, 2);
                
                // Get previous sampling for comparison
                $previousSampling = Sampling::with('samples')
                    ->where('investor_id', $sampling->investor_id)
                    ->where('date_sampling', '<', $sampling->date_sampling)
                    ->orderBy('date_sampling', 'desc')
                    ->first();
                
                $prevABW = 0;
                $prevBiomass = 0;
                $totalWtGained = 0;
                $dailyWtGained = 0;
                
                if ($previousSampling) {
                    $prevSamples = $previousSampling->samples;
                    $prevTotalWeight = $prevSamples->sum('weight');
                    $prevTotalSamples = $prevSamples->count();
                    $prevABW = $prevTotalSamples > 0 ? round($prevTotalWeight / $prevTotalSamples, 2) : 0;
                    $prevMortality = $previousSampling->mortality ?? 0;
                    $prevPresentStocks = $numberOfFish - $prevMortality;
                    $prevBiomass = round(($prevABW * $prevPresentStocks) / 1000, 2);
                    
                    // Calculate weight gained
                    $totalWtGained = round($biomass - $prevBiomass, 2);
                    
                    // Calculate daily weight gained
                    $daysBetween = \Carbon\Carbon::parse($previousSampling->date_sampling)
                        ->diffInDays(\Carbon\Carbon::parse($sampling->date_sampling));
                    $dailyWtGained = $daysBetween > 0 ? round($totalWtGained / $daysBetween, 2) : 0;
                }
                
                // Get historical data for this investor with enhanced biomass calculations
                $historicalSamplings = Sampling::with('samples')
                    ->where('investor_id', $sampling->investor_id)
                    ->orderBy('date_sampling', 'asc')
                    ->get();
                
                $previousBiomass = null;
                $previousDate = null;
                
                $historicalData = $historicalSamplings->map(function ($s, $index) use ($numberOfFish, &$previousBiomass, &$previousDate, $historicalSamplings) {
                    $samples = $s->samples;
                    $totalWeight = $samples->sum('weight');
                    $totalSamples = $samples->count();
                    $avgWeight = $totalSamples > 0 ? round($totalWeight / $totalSamples, 2) : 0;
                    $sMortality = $s->mortality ?? 0;
                    $sPresentStocks = $numberOfFish - $sMortality;
                    
                    // Calculate biomass for each historical sampling using present stocks
                    $biomass = round(($avgWeight * $sPresentStocks) / 1000, 2);
                    $feedingRate = 3; // Default feeding rate percentage
                    // Daily Feed Ration = (Total Stocks × Avg Body Weight × Feeding Rate) / 1000
                    $dailyFeedRation = round(($numberOfFish * $avgWeight * ($feedingRate / 100)) / 1000, 2);
                    
                    // Calculate feed consumed from CageFeedConsumption records
                    // Get feed consumed between this sampling and the previous one (or from start if first)
                    $feedConsumed = 0;
                    if ($s->cage_no) {
                        $startDate = $previousDate ? Carbon::parse($previousDate)->addDay() : null;
                        $endDate = Carbon::parse($s->date_sampling);
                        
                        $feedQuery = CageFeedConsumption::where('cage_id', $s->cage_no)
                            ->where('consumption_date', '<=', $endDate);
                        
                        if ($startDate) {
                            $feedQuery->where('consumption_date', '>=', $startDate);
                        }
                        
                        $feedConsumed = round($feedQuery->sum('feed_amount'), 2);
                    }
                    
                    // Calculate total weight gained (biomass difference from previous sampling)
                    $totalGained = 0;
                    $wtInc = 0;
                    if ($previousBiomass !== null && $previousDate) {
                        $totalGained = round($biomass - $previousBiomass, 2);
                        
                        // Calculate weight increment per day
                        $daysBetween = Carbon::parse($previousDate)->diffInDays(Carbon::parse($s->date_sampling));
                        if ($daysBetween > 0) {
                            $wtInc = round(($totalGained * 1000) / $daysBetween, 1); // Convert kg to grams per day
                        }
                    }
                    
                    // Calculate FCR (Feed Conversion Ratio) = Feed Consumed / Total Weight Gained
                    $fcr = 0;
                    if ($totalGained > 0 && $feedConsumed > 0) {
                        $fcr = round($feedConsumed / $totalGained, 2);
                    } elseif ($totalGained < 0 && $feedConsumed > 0) {
                        // Negative FCR indicates weight loss despite feeding
                        $fcr = round($feedConsumed / abs($totalGained), 2);
                    }
                    
                    // Update for next iteration
                    $previousBiomass = $biomass;
                    $previousDate = $s->date_sampling;
                    
                    return [
                        'date' => $s->date_sampling,
                        'doc' => $s->doc,
                        'stocks' => $numberOfFish,
                        'mortality' => $sMortality,
                        'present' => $sPresentStocks,
                        'abw' => $avgWeight,
                        'wtInc' => $wtInc,
                        'biomass' => $biomass,
                        'fr' => '3%', // Default value
                        'dfr' => $dailyFeedRation,
                        'feed' => $feedConsumed,
                        'totalGained' => $totalGained,
                        'fcr' => $fcr,
                    ];
                });
                
                // Create cage entry with size and feed type (one entry per cage)
                $cageEntry = [
                    'cageNo' => $sampling->cage_no,
                    'weight' => round($avgWeight / 1000, 1), // Convert to kg
                    'length' => $avgLength,
                    'width' => $avgWidth,
                    'type' => $feedTypeName, // Feed type name (e.g., "Starter Feed", "Grower Feed")
                ];
                
                $reportData = [
                    'sampling' => [
                        'id' => $sampling->id,
                        'date' => $sampling->date_sampling,
                        'investor' => $sampling->investor->name,
                        'cageNo' => $sampling->cage_no,
                        'doc' => $sampling->doc,
                        'created_at' => $sampling->created_at,
                        'updated_at' => $sampling->updated_at,
                    ],
                    'cageEntry' => $cageEntry, // One entry per cage with size and type
                    'samples' => $samples->sortBy('sample_no')->values(),
                    'totals' => [
                        'totalWeight' => $totalWeight,
                        'totalSamples' => $totalSamples,
                        'avgWeight' => $avgWeight,
                        'totalStocks' => $numberOfFish,
                        'mortality' => $mortality,
                        'presentStocks' => $presentStocks,
                        'biomass' => $biomass,
                        'feedingRate' => 3, // Default value (percentage)
                        'dailyFeedRation' => round(($numberOfFish * $avgWeight * (3 / 100)) / 1000, 2), // Daily Feed Ration = (Total Stocks × Avg Body Weight × Feeding Rate) / 1000
                        'feedConsumption' => 0, // Default value
                        'prevABW' => $prevABW,
                        'prevBiomass' => $prevBiomass,
                        'totalWtGained' => $totalWtGained,
                        'dailyWtGained' => $dailyWtGained,
                        'fcr' => 0, // Can be calculated
                    ],
                    'history' => $historicalData,
                ];
                
                return Inertia::render('Samplings/SamplingReport', $reportData);
            }
        }
        
        // Fallback to mock data if no sampling ID or sampling not found
        return Inertia::render('Samplings/SamplingReport');
    }

    public function generateSamples(Request $request, Sampling $sampling)
    {
        // Check if sampling already has samples
        $existingSamples = $sampling->samples()->count();
        if ($existingSamples > 0) {
            return response()->json([
                'message' => 'Samples already exist for this sampling',
                'existing_count' => $existingSamples
            ], 400);
        }

        // Generate 5 samples with realistic weight data
        $samples = [];
        for ($i = 1; $i <= 5; $i++) {
            // Generate realistic weight between 50-500 grams
            $baseWeight = rand(150, 350); // Base weight range
            $variation = rand(-50, 50); // Add some variation
            $weight = max(50, $baseWeight + $variation); // Ensure minimum weight

            $samples[] = [
                'investor_id' => $sampling->investor_id,
                'sampling_id' => $sampling->id,
                'sample_no' => $i,
                'weight' => $weight,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Insert all samples
        Sample::insert($samples);

        return response()->json([
            'message' => '5 samples generated successfully',
            'samples_count' => 5,
            'sampling_id' => $sampling->id
        ]);
    }

    public function exportReport(Request $request, $samplingId = null)
    {
        $user = $request->user();
        
        // If no sampling ID provided, use mock data for now
        if (!$samplingId) {
            return $this->exportMockReport();
        }

        // Get real sampling data with access control
        $samplingQuery = Sampling::with(['investor', 'samples']);
        
        // Investors can only view their own samplings
        if ($user && $user->isInvestor()) {
            $samplingQuery->where('investor_id', $user->investor_id);
        }
        
        // Farmers can only view samplings for their own cages
        if ($user && $user->isFarmer()) {
            $samplingQuery->whereHas('cage', function($q) use ($user) {
                $q->where('farmer_id', $user->id);
            });
        }
        
        $sampling = $samplingQuery->findOrFail($samplingId);
        
        return $this->exportRealReport($sampling);
    }

    private function exportMockReport()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set title
        $sheet->setCellValue('A1', 'SAMPLING REPORT');
        $sheet->mergeCells('A1:F1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);

        // Set report details
        $sheet->setCellValue('A3', 'Date: 22-Jan-25');
        $sheet->setCellValue('A4', 'Investor: Saline Tilapia Demo cage');
        $sheet->setCellValue('A5', 'Cage No: 1');
        $sheet->setCellValue('A6', 'DOC: 54');

        // Sample data headers
        $sheet->setCellValue('A8', 'No.');
        $sheet->setCellValue('B8', 'Weight (g)');
        $sheet->setCellValue('C8', 'No.');
        $sheet->setCellValue('D8', 'Weight (g)');
        $sheet->setCellValue('E8', 'No.');
        $sheet->setCellValue('F8', 'Weight (g)');

        // Sample data
        $samples = [
            [1, 258, 11, 260, 21, 206],
            [2, 322, 12, 204, 22, 215],
            [3, 230, 13, 180, 23, 231],
            [4, 215, 14, 172, 24, 218],
            [5, 215, 15, 218, 25, 207],
            [6, 215, 16, 247, 26, 252],
            [7, 232, 17, 198, 27, 261],
            [8, 240, 18, 200, 28, 210],
            [9, 260, 19, 153, 29, 146],
            [10, 240, 20, 153, 30, 218],
        ];

        $row = 9;
        foreach ($samples as $sample) {
            $sheet->setCellValue('A' . $row, $sample[0]);
            $sheet->setCellValue('B' . $row, $sample[1]);
            $sheet->setCellValue('C' . $row, $sample[2]);
            $sheet->setCellValue('D' . $row, $sample[3]);
            $sheet->setCellValue('E' . $row, $sample[4]);
            $sheet->setCellValue('F' . $row, $sample[5]);
            $row++;
        }

        // Summary section
        $summaryRow = $row + 2;
        $sheet->setCellValue('A' . $summaryRow, 'SUMMARY');
        $sheet->getStyle('A' . $summaryRow)->getFont()->setBold(true)->setSize(14);

        $summaryData = [
            ['Total w.t. of samples:', '6752 grams'],
            ['Total # of samples:', '30 pcs'],
            ['Avg. Body Weight:', '225 grams'],
            ['Total Stocks:', '5000 pcs'],
            ['Mortality to date:', '30 pcs'],
            ['Present Stocks:', '5000 pcs'],
            ['Biomass:', '1127 kgs'],
            ['Feeding Rate:', '3%'],
            ['Daily Feed Ration:', '32 kgs'],
            ['Feed Consumption:', '1080 kgs'],
            ['Previous ABW:', '161 grams'],
            ['Previous biomass:', '803 kgs'],
            ['Total Wt. gained:', '304 kgs'],
            ['Daily weight gained:', '1.1 grams/day'],
            ['Feed Conversion Ratio:', '2.0'],
        ];

        $row = $summaryRow + 1;
        foreach ($summaryData as $item) {
            $sheet->setCellValue('A' . $row, $item[0]);
            $sheet->setCellValue('B' . $row, $item[1]);
            $row++;
        }

        // Historical data
        $historyRow = $row + 2;
        $sheet->setCellValue('A' . $historyRow, 'HISTORICAL DATA');
        $sheet->getStyle('A' . $historyRow)->getFont()->setBold(true)->setSize(14);

        $historyHeaders = [
            'Date', 'DOC (days)', 'Total Stocks', 'Mortality to date (pcs)', 
            'Present Stocks (pcs)', 'ABW (grams)', 'Wt. Increment per day (grams)',
            'Biomass (kgs)', 'Feeding Rate', 'Daily Feed Ration (kgs)',
            'Feed Consumed (kgs)', 'Total Wt. gained (kgs)', 'FCR'
        ];

        $historyRow++;
        $col = 'A';
        foreach ($historyHeaders as $header) {
            $sheet->setCellValue($col . $historyRow, $header);
            $sheet->getStyle($col . $historyRow)->getFont()->setBold(true);
            $col++;
        }

        $historyData = [
            ['05-Sep-24', 1, 5000, 0, 5000, 8, 8, 40, '8%', 40, 17, 0, 0],
            ['Oct 25, 2024', 52, 5000, 12, 4988, 48, 40, 239, '5%', 12, 330, 199, 1.7],
            ['Nov 29, 2024', 112, 5000, 30, 5000, 161, 41, 803, '4%', 32, 375, 304, 2.0],
            ['Jan 22, 2025', 176, 5000, 30, 5000, 225, 64, 1127, '3%', 32, 1080, 324, 2.0],
        ];

        $row = $historyRow + 1;
        foreach ($historyData as $history) {
            $col = 'A';
            foreach ($history as $value) {
                $sheet->setCellValue($col . $row, $value);
                $col++;
            }
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'M') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Create the Excel file
        $writer = new Xlsx($spreadsheet);
        $filename = 'sampling_report_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer->save('php://output');
        exit;
    }

    private function exportRealReport($sampling)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set title
        $sheet->setCellValue('A1', 'SAMPLING REPORT');
        $sheet->mergeCells('A1:F1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);

        // Get cage information
        $cage = \App\Models\Cage::with('feedType')->where('id', $sampling->cage_no)->first();
        $sampling->load('feedType');
        $samples = $sampling->samples;
        $totalWeight = $samples->sum('weight');
        $totalSamples = $samples->count();
        $avgWeight = $totalSamples > 0 ? round($totalWeight / $totalSamples, 2) : 0;
        $avgLength = $samples->whereNotNull('length')->count() > 0 
            ? round($samples->whereNotNull('length')->avg('length'), 1) : null;
        $avgWidth = $samples->whereNotNull('width')->count() > 0 
            ? round($samples->whereNotNull('width')->avg('width'), 1) : null;
        
        // Get feed type name from sampling (preferred) or from cage
        $feedTypeName = null;
        if ($sampling->feedType) {
            $feedTypeName = $sampling->feedType->feed_type;
        } elseif ($cage && $cage->feedType) {
            $feedTypeName = $cage->feedType->feed_type;
        }
        
        $weightKg = round($avgWeight / 1000, 1);

        // Set report details
        $sheet->setCellValue('A3', 'Date: ' . $sampling->date_sampling);
        $sheet->setCellValue('A4', 'Investor: ' . $sampling->investor->name);
        $sheet->setCellValue('A5', 'Cage No: ' . $sampling->cage_no);
        $sheet->setCellValue('A6', 'DOC: ' . $sampling->doc);
        
        // Add cage entry with size and type
        $cageInfo = '(No. ' . $sampling->cage_no . ', weight ' . $weightKg . ' kg';
        if ($avgLength) {
            $cageInfo .= ', length ' . $avgLength . ' cm';
        }
        if ($avgWidth) {
            $cageInfo .= ', width ' . $avgWidth . ' cm';
        }
        $cageInfo .= ', type: ' . ($feedTypeName ?? 'N/A') . ')';
        $sheet->setCellValue('A7', 'Cage Information: ' . $cageInfo);
        $sheet->getStyle('A7')->getFont()->setBold(true);

        // Sample data headers
        $sheet->setCellValue('A9', 'No.');
        $sheet->setCellValue('B9', 'Weight (g)');
        $sheet->setCellValue('C9', 'No.');
        $sheet->setCellValue('D9', 'Weight (g)');
        $sheet->setCellValue('E9', 'No.');
        $sheet->setCellValue('F9', 'Weight (g)');

        // Get samples and organize them sequentially across 3 columns
        // Column 1: 1, 2, 3, 4, 5, 6, 7, 8, 9, 10
        // Column 2: 11, 12, 13, 14, 15, 16, 17, 18, 19, 20
        // Column 3: 21, 22, 23, 24, 25, 26, 27, 28, 29, 30
        $samples = $sampling->samples->sortBy('sample_no')->values();
        $samplesPerColumn = (int) ceil($samples->count() / 3);
        $row = 10;
        
        for ($rowIndex = 0; $rowIndex < $samplesPerColumn; $rowIndex++) {
            $col1Index = $rowIndex;                    // Column 1: 0, 1, 2, 3, 4, 5, 6, 7, 8, 9 (samples 1-10)
            $col2Index = $rowIndex + $samplesPerColumn; // Column 2: 10, 11, 12, 13, 14, 15, 16, 17, 18, 19 (samples 11-20)
            $col3Index = $rowIndex + $samplesPerColumn * 2; // Column 3: 20, 21, 22, 23, 24, 25, 26, 27, 28, 29 (samples 21-30)
            
            if (isset($samples[$col1Index])) {
                $sheet->setCellValue('A' . $row, $samples[$col1Index]->sample_no ?? '');
                $sheet->setCellValue('B' . $row, $samples[$col1Index]->weight ?? '');
            }
            
            if (isset($samples[$col2Index])) {
                $sheet->setCellValue('C' . $row, $samples[$col2Index]->sample_no ?? '');
                $sheet->setCellValue('D' . $row, $samples[$col2Index]->weight ?? '');
            }
            
            if (isset($samples[$col3Index])) {
                $sheet->setCellValue('E' . $row, $samples[$col3Index]->sample_no ?? '');
                $sheet->setCellValue('F' . $row, $samples[$col3Index]->weight ?? '');
            }
            
            $row++;
        }

        // Summary statistics already calculated above

        // Summary section
        $summaryRow = $row + 2;
        $sheet->setCellValue('A' . $summaryRow, 'SUMMARY');
        $sheet->getStyle('A' . $summaryRow)->getFont()->setBold(true)->setSize(14);

        $summaryData = [
            ['Total w.t. of samples:', $totalWeight . ' grams'],
            ['Total # of samples:', $totalSamples . ' pcs'],
            ['Avg. Body Weight:', $avgWeight . ' grams'],
        ];

        $row = $summaryRow + 1;
        foreach ($summaryData as $item) {
            $sheet->setCellValue('A' . $row, $item[0]);
            $sheet->setCellValue('B' . $row, $item[1]);
            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Create the Excel file
        $writer = new Xlsx($spreadsheet);
        $filename = 'sampling_report_' . $sampling->id . '_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');
        
        $writer->save('php://output');
        exit;
    }
}
