<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TwoFAController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DonorController;
use App\Http\Controllers\RecipientController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserController;

// Landing page (注意：这里不能再有 ->name('home')，'home' 现在属于 /home)
Route::get('/', function () {
    return view('welcome');
})->name('welcome');

// ---- Module 1: Auth ----
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/verify-2fa', [TwoFAController::class, 'showVerifyForm'])->name('verify2fa.form');
Route::post('/verify-2fa', [TwoFAController::class, 'verify'])->name('verify2fa.verify');
Route::post('/verify-2fa/resend', [TwoFAController::class, 'resend'])->name('verify2fa.resend');

Route::get('/registered', function () {
    return view('auth.registered');
})->name('registered');

Route::get('/forgot-password', [ForgotPasswordController::class, 'showForm'])->name('password.forgot');
Route::post('/forgot-password', [ForgotPasswordController::class, 'sendCode'])->name('password.send-code');
Route::get('/reset-password', [ForgotPasswordController::class, 'showResetForm'])->name('password.reset.form');
Route::post('/reset-password', [ForgotPasswordController::class, 'reset'])->name('password.reset');

Route::get('/home', [HomeController::class, 'index'])->middleware('auth')->name('home');

Route::get('/profile', [ProfileController::class, 'edit'])->middleware('auth')->name('profile.edit');
Route::post('/profile', [ProfileController::class, 'update'])->middleware('auth')->name('profile.update');
Route::get('/profile/password', [ProfileController::class, 'showPasswordForm'])->middleware('auth')->name('profile.password.form');
Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->middleware('auth')->name('profile.password.update');

// Module 2 — Donor web routes
// ---- Module 2: Donor ----
Route::middleware('auth')->group(function () {
    Route::get('/donor/dashboard', [DonorController::class, 'dashboard'])->name('donor.dashboard');
    Route::post('/donor/donations', [DonorController::class, 'store'])->name('donor.donations.store');
    Route::get('/donor/donations/{id}/edit', [DonorController::class, 'edit'])->name('donor.donations.edit');
    Route::put('/donor/donations/{id}', [DonorController::class, 'update'])->name('donor.donations.update');
    Route::delete('/donor/donations/{id}', [DonorController::class, 'destroy'])->name('donor.donations.destroy');
    Route::get('/donor/donations/history', [DonorController::class, 'history'])->name('donor.donations.history');
    Route::get('/donor/donations/all', [DonorController::class, 'allDonations'])->name('donor.donations.all');
    Route::get('/donor/pickups', [DonorController::class, 'pickups'])->name('donor.pickups');
    Route::post('/donor/pickups/{pickup}/status', [DonorController::class, 'updatePickupStatus'])->name('donor.pickups.updateStatus');
    Route::get('/donor/pickups/history', [DonorController::class, 'pickupHistory'])->name('donor.pickups.history');
});

// ---- Module 3: Recipient ----
Route::middleware(['auth', 'role:recipient'])->group(function () {
    Route::get('/recipient/dashboard', [RecipientController::class, 'index'])->name('recipient.dashboard');
    Route::post('/recipient/requests', [RecipientController::class, 'store'])->middleware('throttle:recipient-actions')->name('recipient.requests.store');
    Route::delete('/recipient/requests/{foodRequest}', [RecipientController::class, 'destroy'])->middleware('throttle:recipient-actions')->name('recipient.requests.destroy');
    Route::get('/recipient/pickups', [RecipientController::class, 'pickups'])->name('recipient.pickups');
    Route::post('/recipient/pickups/schedule', [RecipientController::class, 'schedulePickup'])->name('recipient.pickups.schedule');
    Route::post('/recipient/pickups/{pickup}/cancel', [RecipientController::class, 'cancelPickup'])->name('recipient.pickups.cancel');
    Route::get('/recipient/pickups/history', [RecipientController::class, 'pickupHistory'])->name('recipient.pickups.history');
});

// ---- Module 5: Admin ----
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/reports/export', [DashboardController::class, 'export'])->name('reports.export');

    Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::post('/users/{user}/deactivate', [UserController::class, 'deactivate'])->name('users.deactivate');
    Route::post('/users/{user}/activate', [UserController::class, 'activate'])->name('users.activate');
});
