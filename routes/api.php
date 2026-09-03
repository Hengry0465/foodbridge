<?php
use App\Http\Controllers\Api\DonationController;
use App\Http\Controllers\Api\FoodRequestController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // Public: exchange email/password for an API token
    Route::post('/login', [AuthController::class, 'apiLogin']);

    Route::middleware('auth:sanctum')->group(function () {
        // Module 2: Donations
        Route::get('/donations', [DonationController::class, 'index']);
        Route::get('/donations/{id}', [DonationController::class, 'show']);
        Route::post('/donations', [DonationController::class, 'store']);
        Route::post('/donations/{id}/reserve', [DonationController::class, 'reserve']);

        // Module 3: Requests
        Route::post('/requests', [FoodRequestController::class, 'store']);
        Route::get('/requests/{foodRequest}/match', [FoodRequestController::class, 'showMatch']);
        Route::delete('/requests/{foodRequest}', [FoodRequestController::class, 'destroy']);
    });
});