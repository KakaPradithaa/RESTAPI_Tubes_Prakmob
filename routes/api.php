<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\VehicleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Rute Publik untuk Autentikasi
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

// Rute yang Dilindungi (Memerlukan Login)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Rute standar dari apiResource
    Route::apiResource('vehicles', VehicleController::class);
    Route::apiResource('services', ServiceController::class);
    Route::apiResource('schedules', ScheduleController::class);
    Route::apiResource('bookings', BookingController::class);

    // Rute KUSTOM untuk mengubah status booking oleh admin
    Route::patch('bookings/{booking}/status', [BookingController::class, 'updateStatus']);
});
