<?php

use App\Http\Controllers\Api\DonationController;
use App\Http\Controllers\Api\FoodRequestController;
use App\Http\Controllers\Api\PickupController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AdminResourceController;
use App\Http\Controllers\Api\V1\AdminUserController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\ReportExportController;
use App\Http\Controllers\Api\V1\StatsSummaryController;

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

        // Module 4: Pickups
        Route::post('/pickups', [PickupController::class, 'store']);
        Route::patch('/pickups/{pickup}/status', [PickupController::class, 'updateStatus']);
        Route::get('/pickups/history', [PickupController::class, 'history']);
        Route::get('/pickups/{pickup}', [PickupController::class, 'show']);
    });

    // Module 5: Admin
    Route::middleware('role:admin')->prefix('admin')->name('api.v1.admin.')->group(function () {
        Route::get('/users', [AdminResourceController::class, 'users']);
        Route::get('/donations', [AdminResourceController::class, 'donations']);
        Route::get('/requests', [AdminResourceController::class, 'requests']);
        Route::get('/matches', [AdminResourceController::class, 'matches']);
        Route::get('/pickups', [AdminResourceController::class, 'pickups']);
        Route::post('/users/{user}/deactivate', [AdminUserController::class, 'deactivate']);
    });

    Route::middleware('role:admin')->name('api.v1.')->group(function () {
        Route::get('/stats/summary', StatsSummaryController::class);
        Route::get('/reports', ReportController::class);
        Route::post('/reports/export', [ReportExportController::class, 'store'])->name('reports.export');
        Route::get('/reports/exports/{export}', [ReportExportController::class, 'show'])->name('reports.exports.show');
        Route::get('/reports/exports/{export}/download', [ReportExportController::class, 'download'])->name('reports.exports.download');
    });
});
