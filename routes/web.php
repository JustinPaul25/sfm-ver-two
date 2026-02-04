<?php

use App\Http\Controllers\InvestorController;
use App\Http\Controllers\InvestorDashboardController;
use App\Http\Controllers\SamplingController;
use App\Http\Controllers\FeedTypeController;
use App\Http\Controllers\CageController;
use App\Http\Controllers\CageFeedingScheduleController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\FeedingReportController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SystemSettingsController;
use App\Http\Controllers\ForecastingSimulationController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', [App\Http\Controllers\DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth'])->group(function () {
    // InvestoreContoller
    Route::get('investors', [InvestorController::class, 'index'])->name('investors.index');
    Route::get('investors/list', [InvestorController::class, 'list'])->name('investors.list');
    Route::post('investors', [InvestorController::class, 'store'])->name('investors.store');
    Route::put('investors/{investor}', [InvestorController::class, 'update'])->name('investors.update');
    Route::delete('investors/{investor}', [InvestorController::class, 'destroy'])->name('investors.destroy');
    Route::get('investors/select', [InvestorController::class, 'select'])->name('investors.select');
    Route::get('investors/{investor}', [InvestorController::class, 'show'])->name('investors.show');
    Route::get('investors/{investor}/report', [InvestorController::class, 'report'])->name('investors.report');

    // SamplingController
    Route::get('samplings', [SamplingController::class, 'index'])->name('samplings.index');
    Route::get('samplings/list', [SamplingController::class, 'list'])->name('samplings.list');
    Route::post('samplings', [SamplingController::class, 'store'])->name('samplings.store');
    Route::put('samplings/{sampling}', [SamplingController::class, 'update'])->name('samplings.update');
    Route::delete('samplings/{sampling}', [SamplingController::class, 'destroy'])->name('samplings.destroy');
    Route::get('samplings/report', [SamplingController::class, 'report'])->name('samplings.report');
    Route::post('samplings/{sampling}/generate-samples', [SamplingController::class, 'generateSamples'])->name('samplings.generate-samples');
    Route::get('samplings/export-report/{sampling?}', [SamplingController::class, 'exportReport'])->name('samplings.export-report');
    Route::get('test-pagination', function() {
        $samplings = \App\Models\Sampling::with('investor')->withCount('samples')->paginate(10);
        return response()->json($samplings);
    });

    // FeedTypeController
    Route::get('feed-types', [FeedTypeController::class, 'index'])->name('feed-types.index');
    Route::get('feed-types/list', [FeedTypeController::class, 'list'])->name('feed-types.list');
    Route::post('feed-types', [FeedTypeController::class, 'store'])->name('feed-types.store');
    Route::put('feed-types/{feedType}', [FeedTypeController::class, 'update'])->name('feed-types.update');
    Route::delete('feed-types/{feedType}', [FeedTypeController::class, 'destroy'])->name('feed-types.destroy');
    Route::post('feed-types/{id}/restore', [FeedTypeController::class, 'restore'])->name('feed-types.restore');

    // CageController
    Route::get('cages', [CageController::class, 'index'])->name('cages.index');
    Route::get('cages/list', [CageController::class, 'list'])->name('cages.list');
    Route::get('cages/select', [CageController::class, 'select'])->name('cages.select');
    Route::get('cages/verification', [CageController::class, 'verification'])->name('cages.verification');
    Route::get('cages/verification/data', [CageController::class, 'verificationData'])->name('cages.verification.data');
    Route::post('cages', [CageController::class, 'store'])->name('cages.store');
    Route::put('cages/{cage}', [CageController::class, 'update'])->name('cages.update');
    Route::delete('cages/{cage}', [CageController::class, 'destroy'])->name('cages.destroy');
    Route::get('cages/{cage}/view', [CageController::class, 'show'])->name('cages.view');
    
    // Cage Feed Consumption Routes
    Route::get('cages/{cage}/feed-consumptions', [CageController::class, 'getFeedConsumptions'])->name('cages.feed-consumptions');
    Route::post('cages/{cage}/feed-consumptions', [CageController::class, 'storeFeedConsumption'])->name('cages.feed-consumptions.store');
    Route::put('cages/{cage}/feed-consumptions/{consumption}', [CageController::class, 'updateFeedConsumption'])->name('cages.feed-consumptions.update');
    Route::delete('cages/{cage}/feed-consumptions/{consumption}', [CageController::class, 'destroyFeedConsumption'])->name('cages.feed-consumptions.destroy');

    // Cage Feeding Schedule Routes
    Route::get('cages/feeding-schedules', [CageFeedingScheduleController::class, 'index'])->name('cages.feeding-schedules');
    Route::get('cages/{cage}/feeding-schedule-details', [CageFeedingScheduleController::class, 'getCageScheduleDetails'])->name('cages.feeding-schedule-details');
    Route::post('cages/feeding-schedules', [CageFeedingScheduleController::class, 'store'])->name('cages.feeding-schedules.store');
    Route::post('cages/feeding-schedules/auto-generate', [CageFeedingScheduleController::class, 'autoGenerate'])->name('cages.feeding-schedules.auto-generate');
    Route::put('cages/feeding-schedules/{schedule}', [CageFeedingScheduleController::class, 'update'])->name('cages.feeding-schedules.update');
    Route::delete('cages/feeding-schedules/{schedule}', [CageFeedingScheduleController::class, 'destroy'])->name('cages.feeding-schedules.destroy');
    Route::post('cages/feeding-schedules/{schedule}/activate', [CageFeedingScheduleController::class, 'activate'])->name('cages.feeding-schedules.activate');
    Route::get('cages/feeding-schedules/today', [CageFeedingScheduleController::class, 'getTodaySchedule'])->name('cages.feeding-schedules.today');

    // ReportsController
    Route::get('reports', [ReportsController::class, 'index'])->name('reports.index');
    Route::get('reports/overall', [ReportsController::class, 'overall'])->name('reports.overall');
    Route::get('reports/export-excel', [ReportsController::class, 'exportExcel'])->name('reports.export-excel');

    // FeedingReportController
    Route::get('reports/feeding', [FeedingReportController::class, 'index'])->name('reports.feeding');
    Route::get('reports/feeding/weekly', [FeedingReportController::class, 'weeklyReport'])->name('reports.feeding.weekly');
    Route::get('reports/feeding/export-weekly', [FeedingReportController::class, 'exportWeeklyReport'])->name('reports.feeding.export-weekly');
});

// Investor-only routes
Route::middleware(['auth', 'investor'])->group(function () {
    Route::get('investor/dashboard', [InvestorDashboardController::class, 'index'])->name('investor.dashboard');
});

// Admin-only routes
Route::middleware(['auth', 'admin'])->group(function () {
    // UserController (admin only)
    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::get('users/list', [UserController::class, 'list'])->name('users.list');
    Route::get('users/statistics', [UserController::class, 'statistics'])->name('users.statistics');
    Route::get('users/farmers-by-investor', [UserController::class, 'getFarmersByInvestor'])->name('users.farmers-by-investor');
    Route::post('users', [UserController::class, 'store'])->name('users.store');
    Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::put('users/{user}/role', [UserController::class, 'updateRole'])->name('users.update-role');
    Route::post('users/{user}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggle-status');
    Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::post('users/{user}/link-investor', [UserController::class, 'linkInvestor'])->name('users.link-investor');
    Route::post('users/fix-investor-links', [UserController::class, 'fixInvestorLinks'])->name('users.fix-investor-links');

    // SystemSettingsController (admin only)
    Route::get('settings/system', [SystemSettingsController::class, 'index'])->name('settings.system');

    // ForecastingSimulationController (admin only)
    Route::get('forecasting/simulation', [ForecastingSimulationController::class, 'index'])->name('forecasting.simulation');
    Route::put('settings/system/harvest-settings', [SystemSettingsController::class, 'updateHarvestSettings'])->name('settings.system.harvest-settings');
});


require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
