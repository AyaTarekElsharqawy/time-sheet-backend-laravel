<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TimesheetController;

// Public routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);
    
    // Timesheets routes
    Route::get('/timesheets', [TimesheetController::class, 'index']);
    Route::post('/timesheets', [TimesheetController::class, 'store']);
    Route::patch('/timesheets/{id}/approve', [TimesheetController::class, 'approve']);
    Route::patch('/timesheets/{id}/reject', [TimesheetController::class, 'reject']);
    Route::get('/timesheets/stats', [TimesheetController::class, 'stats']);
});
