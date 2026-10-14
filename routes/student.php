<?php

use App\Http\Controllers\StudentDashboardController;
use App\Http\Controllers\VotingController;
use Illuminate\Support\Facades\Route;

// Student routes (auth + role:student,estudiante)
Route::middleware(['auth', 'role:student,estudiante', 'password.changed'])->group(function () {
    Route::get('/student', [StudentDashboardController::class, 'index'])->name('student.dashboard');
    Route::get('/elections/{election}/vote', [VotingController::class, 'show'])->name('voting.show');
    Route::post('/elections/{election}/vote', [VotingController::class, 'vote'])->name('voting.store');
});
