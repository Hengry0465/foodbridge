<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PickupController;

/**
 * Author: Prem A/L Murugiah
 * Student ID: 2113456
 * Educational reference implementation
 * 
 * API Routes for Pickup Scheduling & Tracking Module
 */

Route::middleware(['auth:sanctum'])->group(function () {
    // POST /api/v1/pickups - Create a scheduled pickup
    Route::post('/pickups', [PickupController::class, 'store']);
    
    // PATCH /api/v1/pickups/{pickup}/status - Update pickup status
    Route::patch('/pickups/{pickup}/status', [PickupController::class, 'updateStatus']);
    
    // GET /api/v1/pickups/history - Get pickup history
    Route::get('/pickups/history', [PickupController::class, 'history']);
    
    // GET /api/v1/pickups/{pickup} - View a specific pickup
    Route::get('/pickups/{pickup}', [PickupController::class, 'show']);
});
