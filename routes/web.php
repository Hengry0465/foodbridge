<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DonorController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::post('/register', function () {
    return redirect('/')->with('message', 'Registration endpoint not implemented yet.');
})->name('register');

// Module 2 — Donor web routes
Route::get('/donor/dashboard', [DonorController::class, 'dashboard'])->name('donor.dashboard');
Route::post('/donor/donations', [DonorController::class, 'store'])->name('donor.donations.store');
Route::get('/donor/donations/{id}/edit', [DonorController::class, 'edit'])->name('donor.donations.edit');
Route::put('/donor/donations/{id}', [DonorController::class, 'update'])->name('donor.donations.update');
Route::delete('/donor/donations/{id}', [DonorController::class, 'destroy'])->name('donor.donations.destroy');
Route::get('/donor/donations/history', [DonorController::class, 'history'])->name('donor.donations.history');
Route::get('/donor/donations/all', [DonorController::class, 'allDonations'])->name('donor.donations.all');