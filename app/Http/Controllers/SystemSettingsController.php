<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SystemSettingsController extends Controller
{
    /**
     * Display the system settings page.
     */
    public function index()
    {
        $settings = SystemSetting::all()->keyBy('key');

        return Inertia::render('settings/System', [
            'settings' => $settings,
        ]);
    }

    /**
     * Update the forecasting algorithm setting.
     */
    public function updateForecastingAlgorithm(Request $request)
    {
        $request->validate([
            'algorithm' => 'required|in:lstm,rnn,dense',
        ]);

        SystemSetting::set(
            'forecasting_algorithm',
            $request->algorithm,
            'string',
            'Algorithm used for forecasting: lstm, rnn, or dense'
        );

        return back()->with('success', 'Forecasting algorithm updated successfully.');
    }

    /**
     * Get the current forecasting algorithm.
     */
    public function getForecastingAlgorithm()
    {
        $algorithm = SystemSetting::get('forecasting_algorithm', 'lstm');

        return response()->json([
            'algorithm' => $algorithm,
        ]);
    }
}
