<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'dashboard');

Route::view('/recipient-pickup', 'recipient-pickup');

Route::view('/donor-pickup', 'donor-pickup');

Route::view('/pickup-history', 'pickup-history');