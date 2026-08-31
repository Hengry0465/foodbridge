<?php

use Illuminate\Support\Facades\Route;

/*
| This project's deliverable is the JSON API in routes/api.php (see
| README_PICKUP_MODULE.md). This web route only confirms the app booted.
*/
Route::get('/', function () {
    return view('welcome');
});
