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
     * Get the current forecasting algorithm (LSTM is used as default for best accuracy).
     */
    public function getForecastingAlgorithm()
    {
        $algorithm = SystemSetting::get('forecasting_algorithm', 'lstm');

        return response()->json([
            'algorithm' => $algorithm,
        ]);
    }

    /**
     * Update harvest anticipation settings.
     */
    public function updateHarvestSettings(Request $request)
    {
        $request->validate([
            'harvest_target_weight_grams' => 'required|numeric|min:1|max:10000',
            'harvest_default_growth_rate_g_per_day' => 'required|numeric|min:0.1|max:100',
        ]);

        SystemSetting::set(
            'harvest_target_weight_grams',
            (string) $request->harvest_target_weight_grams,
            'float',
            'Target harvest weight in grams'
        );
        SystemSetting::set(
            'harvest_default_growth_rate_g_per_day',
            (string) $request->harvest_default_growth_rate_g_per_day,
            'float',
            'Default daily growth rate (g/day) when only one sampling exists'
        );

        return back()->with('success', 'Harvest settings updated successfully.');
    }
}
