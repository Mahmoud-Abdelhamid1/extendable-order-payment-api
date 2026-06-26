<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use Illuminate\Support\Facades\Route;

// Auth (public)
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login',    [AuthController::class, 'login']);
});

// Protected
Route::middleware('auth:sanctum')->group(function () {
    Route::post('auth/logout', [AuthController::class, 'logout']);

    // Orders
    Route::apiResource('orders', OrderController::class);

    // Payments
    Route::post('orders/{order}/payments',  [PaymentController::class, 'process']);
    Route::get('orders/{order}/payments',   [PaymentController::class, 'indexForOrder']);
    Route::get('payments',                  [PaymentController::class, 'index']);
});
