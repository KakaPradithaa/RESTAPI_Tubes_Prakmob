<?php

use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\VehicleController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// 1. Rute Publik
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);

// 2. Rute yang memerlukan login (User & Admin)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', fn(Request $request) => $request->user());

    // --- Rute untuk User Biasa ---
    Route::get('/my-bookings', [BookingController::class, 'myBookings']);
    Route::post('/bookings', [BookingController::class, 'store']);
    Route::put('/bookings/{booking}', [BookingController::class, 'update']);
    Route::delete('/bookings/{booking}', [BookingController::class, 'destroy']); // User hapus booking-nya sendiri
    
    // Rute bersama (logic di controller)
    Route::get('/bookings/{booking}', [BookingController::class, 'show']);

    // Resource lain untuk user
    Route::apiResource('vehicles', VehicleController::class);
    Route::get('services', [ServiceController::class, 'index']); // User hanya bisa lihat
    Route::get('schedules', [ScheduleController::class, 'index']); // User hanya bisa lihat

    // --- Rute Khusus untuk ADMIN ---
    Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {
        Route::get('/bookings', [BookingController::class, 'index'])->name('bookings.index');
        Route::patch('/bookings/{booking}/status', [BookingController::class, 'updateStatus'])->name('bookings.updateStatus');
        Route::delete('/bookings/{booking}', [BookingController::class, 'destroy'])->name('bookings.destroy'); // Admin hapus booking manapun

        // Admin bisa mengelola services dan schedules
        Route::apiResource('services', ServiceController::class)->except('index');
        Route::apiResource('schedules', ScheduleController::class)->except('index');
    });
});