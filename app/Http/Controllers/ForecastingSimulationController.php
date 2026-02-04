<?php

namespace App\Http\Controllers;

use Inertia\Inertia;

class ForecastingSimulationController extends Controller
{
    /**
     * Display the forecasting simulation page (admin only).
     * JavaScript alternative to the Jupyter notebook for testing LSTM, RNN, and Dense algorithms.
     */
    public function index()
    {
        return Inertia::render('ForecastingSimulation');
    }
}
