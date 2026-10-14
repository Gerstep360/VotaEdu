<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

// Guest authentication routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

// Authenticated session and profile routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // First-login mandatory or profile password change
    Route::get('/change-password', [ProfileController::class, 'showChangePassword'])->name('password.change');
    Route::post('/change-password', [ProfileController::class, 'updatePassword'])->name('password.update');

    // Personal profile (Section 3.3)
    Route::middleware('password.changed')->group(function () {
        Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    });
});
