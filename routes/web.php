<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/debug', function () {
    return response()->json([
        'uri' => request()->getRequestUri(),
        'path' => request()->path(),
        'full' => request()->fullUrl()
    ]);
});
