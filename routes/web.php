<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'service' => 'comments-service',
        'status' => 'healthy',
    ]);
});
