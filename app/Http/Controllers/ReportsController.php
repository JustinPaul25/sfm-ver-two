<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Sampling;
use App\Models\Sample;
use App\Models\Investor;
use App\Models\Cage;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ReportsController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Reports/Index');
    }

    public function overall(Request $request)
    {
        $query = Sampling::with(['investor', 'samples']);

        // Apply filters
        if ($request->filled('investor_id')) {
            $query->where('investor_id', $request->investor_id);
        }

        if ($request->filled('date_from')) {
            $query->where('date_sampling', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('date_sampling', '<=', $request->date_to);
        }

        if ($request->filled('cage_no')) {
            $query->where('cage_no', 'like', '%' . $request->cage_no . '%');
        }

        $samplings = $query->orderBy('date_sampling', 'desc')->get();

        // Calculate summary statistics
        $summary = $this->calculateSummary($samplings);

        // Get investors for filter dropdown
        $investors = Investor::orderBy('name')->get();

        return Inertia::render('Reports/Overall', [
            'samplings' => $samplings,
            'summary' => $summary,
            'investors' => $investors,
            'filters' => $request->only(['investor_id', 'date_from', 'date_to', 'cage_no'])
        ]);
    }

    public function exportExcel(Request $request)
    {
        $query = Sampling::with(['investor', 'samples']);

        // Apply same filters as the view
        if ($request->filled('investor_id')) {
            $query->where('investor_id', $request->investor_id);
        }

        if ($request->filled('date_from')) {
            $query->where('date_sampling', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('date_sampling', '<=', $request->date_to);
        }

        if ($request->filled('cage_no')) {
            $query->where('cage_no', 'like', '%' . $request->cage_no . '%');
        }

        $samplings = $query->orderBy('date_sampling', 'desc')->get();
        $summary = $this->calculateSummary($samplings);

        // Create Excel file
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set title
        $sheet->setCellValue('A1', 'OVERALL SAMPLING REPORTS');
        $sheet->mergeCells('A1:H1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Set filters info
        $row = 3;
        if ($request->filled('date_from') || $request->filled('date_to')) {
            $dateRange = '';
            if ($request->filled('date_from')) $dateRange .= 'From: ' . $request->date_from;
            if ($request->filled('date_to')) $dateRange .= ' To: ' . $request->date_to;
            $sheet->setCellValue('A' . $row, 'Date Range: ' . $dateRange);
            $row++;
        }

        if ($request->filled('investor_id')) {
            $investor = Investor::find($request->investor_id);
            $sheet->setCellValue('A' . $row, 'Investor: ' . $investor->name);
            $row++;
        }

        if ($request->filled('cage_no')) {
            $sheet->setCellValue('A' . $row, 'Cage No: ' . $request->cage_no);
            $row++;
        }

        $row += 2;

        // Summary section
        $sheet->setCellValue('A' . $row, 'SUMMARY STATISTICS');
        $sheet->mergeCells('A' . $row . ':H' . $row);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $row++;

        $summaryHeaders = [
            'Total Samplings', 'Total Samples', 'Average Weight (g)', 'Total Weight (kg)',
            'Min Weight (g)', 'Max Weight (g)', 'Total Investors', 'Total Cages'
        ];

        $summaryValues = [
            $summary['total_samplings'],
            $summary['total_samples'],
            number_format($summary['avg_weight'], 2),
            number_format($summary['total_weight_kg'], 2),
            $summary['min_weight'],
            $summary['max_weight'],
            $summary['total_investors'],
            $summary['total_cages']
        ];

        for ($i = 0; $i < count($summaryHeaders); $i++) {
            $sheet->setCellValue(chr(65 + $i) . $row, $summaryHeaders[$i]);
            $sheet->setCellValue(chr(65 + $i) . ($row + 1), $summaryValues[$i]);
        }

        $row += 3;

        // Detailed data headers
        $headers = [
            'Date', 'Investor', 'Cage No', 'DOC', 'Sample Count', 'Total Weight (g)', 
            'Average Weight (g)', 'Min Weight (g)', 'Max Weight (g)'
        ];

        for ($i = 0; $i < count($headers); $i++) {
            $sheet->setCellValue(chr(65 + $i) . $row, $headers[$i]);
            $sheet->getStyle(chr(65 + $i) . $row)->getFont()->setBold(true);
        }

        $row++;

        // Detailed data
        foreach ($samplings as $sampling) {
            $samples = $sampling->samples;
            $sampleCount = $samples->count();
            $totalWeight = $samples->sum('weight');
            $avgWeight = $sampleCount > 0 ? $totalWeight / $sampleCount : 0;
            $minWeight = $samples->min('weight');
            $maxWeight = $samples->max('weight');

            $sheet->setCellValue('A' . $row, $sampling->date_sampling);
            $sheet->setCellValue('B' . $row, $sampling->investor->name);
            $sheet->setCellValue('C' . $row, $sampling->cage_no);
            $sheet->setCellValue('D' . $row, $sampling->doc);
            $sheet->setCellValue('E' . $row, $sampleCount);
            $sheet->setCellValue('F' . $row, $totalWeight);
            $sheet->setCellValue('G' . $row, number_format($avgWeight, 2));
            $sheet->setCellValue('H' . $row, $minWeight);
            $sheet->setCellValue('I' . $row, $maxWeight);

            $row++;
        }

        // Auto-size columns
        foreach (range('A', 'I') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Add borders
        $lastRow = $row - 1;
        $sheet->getStyle('A1:H' . $lastRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        // Create the Excel file
        $writer = new Xlsx($spreadsheet);
        $filename = 'sampling_reports_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }

    private function calculateSummary($samplings)
    {
        $totalSamplings = $samplings->count();
        $totalSamples = 0;
        $totalWeight = 0;
        $allWeights = [];
        $investorIds = [];
        $cageNumbers = [];

        foreach ($samplings as $sampling) {
            $samples = $sampling->samples;
            $totalSamples += $samples->count();
            $totalWeight += $samples->sum('weight');
            
            foreach ($samples as $sample) {
                $allWeights[] = $sample->weight;
            }
            
            $investorIds[] = $sampling->investor_id;
            $cageNumbers[] = $sampling->cage_no;
        }

        return [
            'total_samplings' => $totalSamplings,
            'total_samples' => $totalSamples,
            'avg_weight' => count($allWeights) > 0 ? array_sum($allWeights) / count($allWeights) : 0,
            'total_weight_kg' => $totalWeight / 1000, // Convert to kg
            'min_weight' => count($allWeights) > 0 ? min($allWeights) : 0,
            'max_weight' => count($allWeights) > 0 ? max($allWeights) : 0,
            'total_investors' => count(array_unique($investorIds)),
            'total_cages' => count(array_unique($cageNumbers)),
        ];
    }
} 