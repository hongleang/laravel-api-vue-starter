<?php

use Illuminate\Support\Facades\Route;

/*
 * This app is a JSON API. The Vue SPA in ../frontend is the user interface, and
 * Sanctum registers /sanctum/csrf-cookie on the web middleware group.
 */
Route::get('/', fn () => response()->json([
    'name' => config('app.name'),
    'api' => url('/api'),
]));
