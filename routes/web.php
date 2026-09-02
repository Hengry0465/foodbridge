<?php

use App\Http\Controllers\RecipientController;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('welcome');

Route::view('/login', 'auth.login')->name('login');

Route::post('/demo/recipient-login', function () {
    abort_unless(app()->environment('local'), 404);
    $recipient = User::query()->where('role', 'recipient')->firstOrFail();
    Auth::login($recipient);
    request()->session()->regenerate();

    return to_route('recipient.dashboard');
})->middleware('throttle:3,1')->name('demo.recipient.login');

Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return to_route('welcome');
})->middleware('auth')->name('logout');

Route::middleware(['auth', 'role:recipient'])->group(function (): void {
    Route::get('/recipient/dashboard', [RecipientController::class, 'index'])
        ->name('recipient.dashboard');

    Route::post('/recipient/requests', [RecipientController::class, 'store'])
        ->middleware('throttle:recipient-actions')
        ->name('recipient.requests.store');

    Route::delete('/recipient/requests/{foodRequest}', [RecipientController::class, 'destroy'])
        ->middleware('throttle:recipient-actions')
        ->name('recipient.requests.destroy');
});
