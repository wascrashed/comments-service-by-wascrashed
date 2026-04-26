<?php

use App\Http\Controllers\CommentController;
use Illuminate\Support\Facades\Route;

Route::prefix('posts')->group(function () {
    Route::get('{post}/comments', [CommentController::class, 'index']);
    Route::post('{post}/comments', [CommentController::class, 'store']);
});

Route::prefix('comments')->group(function () {
    Route::get('{comment}', [CommentController::class, 'show']);
    Route::patch('{comment}', [CommentController::class, 'update']);
    Route::delete('{comment}', [CommentController::class, 'destroy']);
});
