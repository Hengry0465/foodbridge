<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TwoFAController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DonorController;

// Landing page (注意：这里不能再有 ->name('home')，'home' 现在属于 /home)
Route::get('/', function () {
    return view('welcome');
});

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
});