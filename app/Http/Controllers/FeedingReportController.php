<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Cage;
use App\Models\CageFeedConsumption;
use App\Models\CageFeedingSchedule;
use App\Models\Investor;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Carbon\Carbon;

class FeedingReportController extends Controller
{
    /**
     * Display the weekly feeding report page
     */
    public function index(Request $request)
    {
        return Inertia::render('Reports/FeedingReport');
    }

    /**
     * Get weekly feeding report data
     */
    public function weeklyReport(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'cage_id' => 'nullable|exists:cages,id',
            'investor_id' => 'nullable|exists:investors,id',
        ]);

        // Default to current week if no dates provided
        $startDate = $request->start_date 
            ? Carbon::parse($request->start_date)->startOfDay()
            : Carbon::now()->startOfWeek();
        
        $endDate = $request->end_date 
            ? Carbon::parse($request->end_date)->endOfDay()
            : Carbon::now()->endOfWeek();

        $user = $request->user();
        
        // Build query for cages
        $cagesQuery = Cage::with(['investor', 'feedType', 'feedingSchedule', 'feedConsumptions']);

        // Investors can only see their own cages
        if ($user && $user->isInvestor()) {
            $cagesQuery->where('investor_id', $user->investor_id);
        }

        // Farmers can only see their own cages
        if ($user && $user->isFarmer()) {
            $cagesQuery->where('farmer_id', $user->id);
        }

        if ($request->cage_id) {
            $cagesQuery->where('id', $request->cage_id);
        }

        if ($request->investor_id) {
            $cagesQuery->where('investor_id', $request->investor_id);
        }

        $cages = $cagesQuery->get();

        $reportData = [];
        $overallSummary = [
            'total_cages' => $cages->count(),
            'total_feed_consumed' => 0,
            'total_scheduled_feed' => 0,
            'average_adherence' => 0,
            'cages_with_schedules' => 0,
            'active_schedules' => 0,
        ];

        foreach ($cages as $cage) {
            $cageReport = $this->generateCageWeeklyReport($cage, $startDate, $endDate);
            $reportData[] = $cageReport;

            // Aggregate overall summary
            $overallSummary['total_feed_consumed'] += $cageReport['total_consumed'];
            $overallSummary['total_scheduled_feed'] += $cageReport['total_scheduled'];
            
            if ($cage->feedingSchedule) {
                $overallSummary['cages_with_schedules']++;
                if ($cage->feedingSchedule->is_active) {
                    $overallSummary['active_schedules']++;
                }
            }
        }

        // Calculate average adherence
        if ($overallSummary['total_scheduled_feed'] > 0) {
            $overallSummary['average_adherence'] = round(
                ($overallSummary['total_feed_consumed'] / $overallSummary['total_scheduled_feed']) * 100,
                1
            );
        }

        // Get investors and cages for filters (filtered by role)
        $investorsQuery = Investor::orderBy('name');
        if ($user && $user->isInvestor()) {
            $investorsQuery->where('id', $user->investor_id);
        }
        $investors = $investorsQuery->get(['id', 'name']);
        
        $allCagesQuery = Cage::with('investor')->orderBy('id');
        if ($user && $user->isInvestor()) {
            $allCagesQuery->where('investor_id', $user->investor_id);
        }
        if ($user && $user->isFarmer()) {
            $allCagesQuery->where('farmer_id', $user->id);
        }
        $allCages = $allCagesQuery->get()->map(function($cage) {
            return [
                'id' => $cage->id,
                'label' => "Cage {$cage->id}" . ($cage->investor ? " - {$cage->investor->name}" : ''),
            ];
        });

        return response()->json([
            'report_data' => $reportData,
            'summary' => $overallSummary,
            'period' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d'),
                'days_count' => (int) round($startDate->diffInDays($endDate) + 1),
            ],
            'filters' => [
                'investors' => $investors,
                'cages' => $allCages,
            ],
        ]);
    }

    /**
     * Generate weekly report for a single cage
     */
    private function generateCageWeeklyReport(Cage $cage, Carbon $startDate, Carbon $endDate)
    {
        $schedule = $cage->feedingSchedule;
        
        // Get feed consumptions within the date range
        $consumptions = $cage->feedConsumptions()
            ->whereBetween('consumption_date', [$startDate, $endDate])
            ->orderBy('consumption_date')
            ->get();

        // Calculate daily breakdown
        $dailyBreakdown = [];
        $currentDate = $startDate->copy();
        
        while ($currentDate <= $endDate) {
            $dateStr = $currentDate->format('Y-m-d');
            // Match consumption by comparing dates (consumption_date is cast to Carbon date)
            $consumption = $consumptions->first(function ($item) use ($currentDate) {
                return $item->consumption_date->isSameDay($currentDate);
            });
            
            $scheduledAmount = $schedule ? (float) $schedule->total_daily_amount : 0;
            $actualAmount = $consumption ? (float) $consumption->feed_amount : 0;
            
            // Only include days with actual consumption data
            if ($actualAmount > 0) {
                $dailyBreakdown[] = [
                    'date' => $dateStr,
                    'day_name' => $currentDate->format('l'),
                    'scheduled_amount' => $scheduledAmount,
                    'actual_amount' => $actualAmount,
                    'variance' => $actualAmount - $scheduledAmount,
                    'adherence_rate' => $scheduledAmount > 0 
                        ? round(($actualAmount / $scheduledAmount) * 100, 1)
                        : 0,
                    'notes' => $consumption ? $consumption->notes : null,
                ];
            }
            
            $currentDate->addDay();
        }

        // Calculate totals
        $totalScheduled = $schedule 
            ? (float) $schedule->total_daily_amount * count($dailyBreakdown)
            : 0;
        $totalConsumed = $consumptions->sum('feed_amount');
        $adherenceRate = $totalScheduled > 0 
            ? round(($totalConsumed / $totalScheduled) * 100, 1)
            : 0;

        // Get feeding times for the schedule
        $feedingTimes = [];
        if ($schedule) {
            $feedingTimes = $schedule->feeding_times;
        }

        return [
            'cage_id' => $cage->id,
            'cage_number' => $cage->id,
            'investor_name' => $cage->investor ? $cage->investor->name : 'N/A',
            'feed_type' => $cage->feedType ? $cage->feedType->feed_type : 'N/A',
            'fingerlings_count' => (int) $cage->number_of_fingerlings,
            'has_schedule' => $schedule ? true : false,
            'schedule_name' => $schedule ? $schedule->schedule_name : 'No Schedule',
            'feeding_frequency' => $schedule ? $schedule->frequency : null,
            'feeding_times' => $feedingTimes,
            'total_scheduled' => $totalScheduled,
            'total_consumed' => $totalConsumed,
            'variance' => $totalConsumed - $totalScheduled,
            'adherence_rate' => $adherenceRate,
            'daily_breakdown' => $dailyBreakdown,
            'average_daily_consumption' => count($dailyBreakdown) > 0 
                ? round($totalConsumed / count($dailyBreakdown), 2)
                : 0,
        ];
    }

    /**
     * Export weekly feeding report to Excel
     */
    public function exportWeeklyReport(Request $request)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'cage_id' => 'nullable|exists:cages,id',
            'investor_id' => 'nullable|exists:investors,id',
        ]);

        // Get report data
        $reportResponse = $this->weeklyReport($request);
        $reportData = json_decode($reportResponse->getContent(), true);

        // Create Excel file
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set title
        $sheet->setCellValue('A1', 'WEEKLY FEEDING SCHEDULE REPORT');
        $sheet->mergeCells('A1:J1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Set period info
        $row = 3;
        $period = $reportData['period'];
        $sheet->setCellValue('A' . $row, 'Report Period: ' . $period['start_date'] . ' to ' . $period['end_date']);
        $sheet->setCellValue('F' . $row, 'Days: ' . $period['days_count']);
        $row += 2;

        // Summary section
        $sheet->setCellValue('A' . $row, 'OVERALL SUMMARY');
        $sheet->mergeCells('A' . $row . ':J' . $row);
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $row++;

        $summary = $reportData['summary'];
        $summaryData = [
            ['Total Cages:', $summary['total_cages'], 'Cages with Schedules:', $summary['cages_with_schedules']],
            ['Total Feed Scheduled:', number_format($summary['total_scheduled_feed'], 2) . ' kg', 'Active Schedules:', $summary['active_schedules']],
            ['Total Feed Consumed:', number_format($summary['total_feed_consumed'], 2) . ' kg', 'Average Adherence:', $summary['average_adherence'] . '%'],
        ];

        foreach ($summaryData as $summaryRow) {
            $sheet->setCellValue('A' . $row, $summaryRow[0]);
            $sheet->setCellValue('B' . $row, $summaryRow[1]);
            $sheet->setCellValue('E' . $row, $summaryRow[2]);
            $sheet->setCellValue('F' . $row, $summaryRow[3]);
            $row++;
        }

        $row += 2;

        // Detailed data for each cage
        foreach ($reportData['report_data'] as $cageData) {
            // Cage header
            $sheet->setCellValue('A' . $row, 'CAGE #' . $cageData['cage_number']);
            $sheet->mergeCells('A' . $row . ':J' . $row);
            $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
            $sheet->getStyle('A' . $row)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('E3F2FD');
            $row++;

            // Cage info
            $sheet->setCellValue('A' . $row, 'Investor: ' . $cageData['investor_name']);
            $sheet->setCellValue('D' . $row, 'Feed Type: ' . $cageData['feed_type']);
            $sheet->setCellValue('G' . $row, 'Fingerlings: ' . $cageData['fingerlings_count']);
            $row++;

            $sheet->setCellValue('A' . $row, 'Schedule: ' . $cageData['schedule_name']);
            $sheet->setCellValue('D' . $row, 'Frequency: ' . ($cageData['feeding_frequency'] ?? 'N/A'));
            $sheet->setCellValue('G' . $row, 'Adherence: ' . $cageData['adherence_rate'] . '%');
            $row++;

            if (!empty($cageData['feeding_times'])) {
                $sheet->setCellValue('A' . $row, 'Feeding Times: ' . implode(', ', $cageData['feeding_times']));
                $row++;
            }

            $row++;

            // Daily breakdown header
            $headers = ['Date', 'Day', 'Scheduled (kg)', 'Actual (kg)', 'Variance (kg)', 'Adherence %', 'Notes'];
            $col = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($col . $row, $header);
                $sheet->getStyle($col . $row)->getFont()->setBold(true);
                $sheet->getStyle($col . $row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F5F5F5');
                $col++;
            }
            $row++;

            // Daily data
            foreach ($cageData['daily_breakdown'] as $day) {
                $sheet->setCellValue('A' . $row, $day['date']);
                $sheet->setCellValue('B' . $row, $day['day_name']);
                $sheet->setCellValue('C' . $row, number_format($day['scheduled_amount'], 2));
                $sheet->setCellValue('D' . $row, number_format($day['actual_amount'], 2));
                $sheet->setCellValue('E' . $row, number_format($day['variance'], 2));
                $sheet->setCellValue('F' . $row, $day['adherence_rate'] . '%');
                $sheet->setCellValue('G' . $row, $day['notes'] ?? '');
                $row++;
            }

            // Cage totals
            $sheet->setCellValue('A' . $row, 'TOTALS');
            $sheet->setCellValue('C' . $row, number_format($cageData['total_scheduled'], 2));
            $sheet->setCellValue('D' . $row, number_format($cageData['total_consumed'], 2));
            $sheet->setCellValue('E' . $row, number_format($cageData['variance'], 2));
            $sheet->setCellValue('F' . $row, $cageData['adherence_rate'] . '%');
            $sheet->getStyle('A' . $row . ':G' . $row)->getFont()->setBold(true);
            $row += 3;
        }

        // Auto-size columns
        foreach (range('A', 'J') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Create the Excel file
        $writer = new Xlsx($spreadsheet);
        $filename = 'weekly_feeding_report_' . date('Y-m-d_H-i-s') . '.xlsx';
        
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        $writer->save('php://output');
        exit;
    }
}
