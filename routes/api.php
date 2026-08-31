<?php

use App\Http\Controllers\Api\V1\AdminResourceController;
use App\Http\Controllers\Api\V1\AdminUserController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\ReportExportController;
use App\Http\Controllers\Api\V1\StatsSummaryController;
use App\Http\Middleware\EnsureUserIsAdmin;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function (): void {
    Route::middleware(['auth:sanctum', EnsureUserIsAdmin::class])->group(function (): void {
        Route::get('stats/summary', StatsSummaryController::class)->name('stats.summary');

        Route::get('reports', ReportController::class)->name('reports.index');
        Route::post('reports/export', [ReportExportController::class, 'store'])->name('reports.export');
        Route::get('reports/exports/{export}', [ReportExportController::class, 'show'])->name('reports.exports.show');
        Route::get('reports/exports/{export}/download', [ReportExportController::class, 'download'])
            ->name('reports.exports.download');

        Route::prefix('admin')->name('admin.')->group(function (): void {
            Route::get('users', [AdminResourceController::class, 'users'])->name('users.index');
            Route::get('donations', [AdminResourceController::class, 'donations'])->name('donations.index');
            Route::get('requests', [AdminResourceController::class, 'requests'])->name('requests.index');
            Route::get('matches', [AdminResourceController::class, 'matches'])->name('matches.index');
            Route::get('pickups', [AdminResourceController::class, 'pickups'])->name('pickups.index');

            Route::patch('users/{user}/deactivate', [AdminUserController::class, 'deactivate'])
                ->name('users.deactivate');
        });
    });
});
