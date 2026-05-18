<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;

// ── Public routes ─────────────────────────────────────────────
Route::post('/login', [AuthController::class, 'login']);
Route::get('/health', [DashboardController::class, 'health']);

// ── Protected routes (Sanctum token) ──────────────────────────
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Products
    Route::apiResource('products', ProductController::class);
    Route::get('products/{product}/stock-movements', [ProductController::class, 'stockMovements']);
    Route::post('products/{product}/stock-movements', [ProductController::class, 'storeStockMovement']);

    // Categories
    Route::apiResource('categories', CategoryController::class);
});
