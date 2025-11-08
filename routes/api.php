<?php

use App\Http\Controllers\Api\DocsController;
use Illuminate\Support\Facades\Route;

// API Routes for mobile/device integration
// Note: Laravel automatically prefixes these with /api when using api() in bootstrap/app.php
    
// Cages endpoint - Get all cages with samplings
Route::get('cages', [DocsController::class, 'index']);

// Weight calculation endpoint - Calculate weight from dimensions
Route::post('weight', [DocsController::class, 'getWeight']);

// Calculate samplings endpoint - Get next sample to measure
Route::post('sampling/calculate', [DocsController::class, 'calculateSamplings']);

// Get sampling details endpoint
Route::get('sampling/{id}', [DocsController::class, 'getSampling']);

