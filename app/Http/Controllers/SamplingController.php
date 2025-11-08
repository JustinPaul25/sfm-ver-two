<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Sampling;
use App\Models\Sample;
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
        $query = Sampling::with('investor')->withCount('samples');

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
        $request->validate([
            'investor_id' => 'required|exists:investors,id',
            'date_sampling' => 'required|date',
            'doc' => 'required|string',
            'cage_no' => 'required',
            'mortality' => 'nullable|integer|min:0',
        ]);

        // Ensure cage_no is converted to string if it comes as integer
        $data = $request->all();
        if (isset($data['cage_no'])) {
            $data['cage_no'] = (string) $data['cage_no'];
        }
        
        $sampling = Sampling::create($data);

        return response()->json([
            'message' => 'Sampling created successfully',
            'sampling' => $sampling
        ]);
    }

    public function update(Request $request, Sampling $sampling)
    {
        $request->validate([
            'investor_id' => 'required|exists:investors,id',
            'date_sampling' => 'required|date',
            'doc' => 'required|string',
            'cage_no' => 'required',
            'mortality' => 'nullable|integer|min:0',
        ]);

        // Ensure cage_no is converted to string if it comes as integer
        $data = $request->all();
        if (isset($data['cage_no'])) {
            $data['cage_no'] = (string) $data['cage_no'];
        }
        
        $sampling->update($data);

        return response()->json([
            'message' => 'Sampling updated successfully',
            'sampling' => $sampling
        ]);
    }

    public function destroy(Sampling $sampling)
    {
        $sampling->delete();

        return response()->json([
            'message' => 'Sampling deleted successfully'
        ]);
    }

    public function report(Request $request)
    {
        $samplingId = $request->get('sampling');
        
        if ($samplingId) {
            // Get specific sampling data with cage information
            $sampling = Sampling::with(['investor', 'samples'])->find($samplingId);
            
            if ($sampling) {
                // Get cage information for accurate biomass calculation
                $cage = \App\Models\Cage::where('id', $sampling->cage_no)->first();
                $numberOfFish = $cage ? $cage->number_of_fingerlings : 5000; // Fallback to default
                $mortality = $sampling->mortality ?? 0;
                $presentStocks = $numberOfFish - $mortality;
                
                // Calculate summary statistics
                $samples = $sampling->samples;
                $totalWeight = $samples->sum('weight');
                $totalSamples = $samples->count();
                $avgWeight = $totalSamples > 0 ? round($totalWeight / $totalSamples, 2) : 0;
                
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
                    ->get()
                    ->map(function ($s) use ($numberOfFish) {
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
                        
                        return [
                            'date' => $s->date_sampling,
                            'doc' => $s->doc,
                            'stocks' => $numberOfFish,
                        'mortality' => $sMortality,
                        'present' => $sPresentStocks,
                            'abw' => $avgWeight,
                            'wtInc' => 0, // Can be calculated from previous sampling
                            'biomass' => $biomass,
                            'fr' => '3%', // Default value
                            'dfr' => $dailyFeedRation, // Daily Feed Ration = Biomass × Feeding Rate
                            'feed' => 0, // Default value
                            'totalGained' => 0, // Can be calculated
                            'fcr' => 0, // Can be calculated
                        ];
                    });
                
                $reportData = [
                    'sampling' => [
                        'id' => $sampling->id,
                        'date' => $sampling->date_sampling,
                        'investor' => $sampling->investor->name,
                        'cageNo' => $sampling->cage_no,
                        'doc' => $sampling->doc,
                    ],
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
                    'history' => $historicalSamplings,
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

        // Generate 30 samples with realistic weight data
        $samples = [];
        for ($i = 1; $i <= 30; $i++) {
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
            'message' => '30 samples generated successfully',
            'samples_count' => 30,
            'sampling_id' => $sampling->id
        ]);
    }

    public function exportReport(Request $request, $samplingId = null)
    {
        // If no sampling ID provided, use mock data for now
        if (!$samplingId) {
            return $this->exportMockReport();
        }

        // Get real sampling data
        $sampling = Sampling::with(['investor', 'samples'])->findOrFail($samplingId);
        
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

        // Set report details
        $sheet->setCellValue('A3', 'Date: ' . $sampling->date_sampling);
        $sheet->setCellValue('A4', 'Investor: ' . $sampling->investor->name);
        $sheet->setCellValue('A5', 'Cage No: ' . $sampling->cage_no);
        $sheet->setCellValue('A6', 'DOC: ' . $sampling->doc);

        // Sample data headers
        $sheet->setCellValue('A8', 'No.');
        $sheet->setCellValue('B8', 'Weight (g)');
        $sheet->setCellValue('C8', 'No.');
        $sheet->setCellValue('D8', 'Weight (g)');
        $sheet->setCellValue('E8', 'No.');
        $sheet->setCellValue('F8', 'Weight (g)');

        // Get samples and organize them in groups of 3
        $samples = $sampling->samples->sortBy('sample_no')->values();
        $row = 9;
        
        for ($i = 0; $i < count($samples); $i += 3) {
            $sheet->setCellValue('A' . $row, $samples[$i]->sample_no ?? '');
            $sheet->setCellValue('B' . $row, $samples[$i]->weight ?? '');
            
            if (isset($samples[$i + 1])) {
                $sheet->setCellValue('C' . $row, $samples[$i + 1]->sample_no ?? '');
                $sheet->setCellValue('D' . $row, $samples[$i + 1]->weight ?? '');
            }
            
            if (isset($samples[$i + 2])) {
                $sheet->setCellValue('E' . $row, $samples[$i + 2]->sample_no ?? '');
                $sheet->setCellValue('F' . $row, $samples[$i + 2]->weight ?? '');
            }
            
            $row++;
        }

        // Calculate summary statistics
        $totalWeight = $samples->sum('weight');
        $totalSamples = $samples->count();
        $avgWeight = $totalSamples > 0 ? round($totalWeight / $totalSamples, 2) : 0;

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
