<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TimesheetController;

// Web routes that parallel API behavior; still use Sanctum for auth in this project.
// Public: registration and login
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected web routes (authenticated users)
Route::middleware('auth:sanctum')->group(function () {
    // Profile management
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    Route::put('/profile', [AuthController::class, 'updateProfile']);
    Route::put('/change-password', [AuthController::class, 'changePassword']);

    // Timesheet routes available to authenticated users
    Route::get('/timesheets', [TimesheetController::class, 'index']);
    Route::post('/timesheets', [TimesheetController::class, 'store']);
    Route::get('/timesheets/stats', [TimesheetController::class, 'stats']);

    // Admin-only group (uses AdminMiddleware)
    Route::middleware('admin')->group(function () {
        Route::get('/timesheets/pending', [TimesheetController::class, 'pending']);
        Route::patch('/timesheets/{id}/approve', [TimesheetController::class, 'approve']);
        Route::patch('/timesheets/{id}/reject', [TimesheetController::class, 'reject']);
    });
});