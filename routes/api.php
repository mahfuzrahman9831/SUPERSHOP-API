<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // Auth Routes
    Route::prefix('auth')->group(function () {
        Route::post('login', [\App\Http\Controllers\Api\Auth\AuthController::class, 'login']);
        Route::post('logout', [\App\Http\Controllers\Api\Auth\AuthController::class, 'logout'])->middleware('auth:sanctum');
        Route::get('me', [\App\Http\Controllers\Api\Auth\AuthController::class, 'me'])->middleware('auth:sanctum');
    });

    // Protected Routes
    Route::middleware('auth:sanctum')->group(function () {

        // Dashboard
        Route::get('dashboard', [\App\Http\Controllers\Api\DashboardController::class, 'index']);

    });

});
