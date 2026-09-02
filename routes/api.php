<?php

use App\Http\Controllers\Api\FoodRequestController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('throttle:module-api')->group(function (): void {
    Route::post('/requests', [FoodRequestController::class, 'store']);
    Route::get('/requests/{foodRequest}/match', [FoodRequestController::class, 'showMatch']);
    Route::delete('/requests/{foodRequest}', [FoodRequestController::class, 'destroy']);
});
