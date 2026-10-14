<?php

use App\Http\Controllers\Admin\OptionController;
use App\Http\Controllers\Teacher\CourseElectionController;
use App\Http\Controllers\Teacher\TeacherDashboardController;
use Illuminate\Support\Facades\Route;

// Teacher routes (auth + role:docente,teacher)
Route::prefix('teacher')
    ->name('teacher.')
    ->middleware(['auth', 'role:docente,teacher', 'password.changed'])
    ->group(function () {
        Route::get('/', [TeacherDashboardController::class, 'index'])->name('dashboard');

        // Course elections created by teachers
        Route::get('/elections/create', [CourseElectionController::class, 'create'])->name('elections.create');
        Route::post('/elections', [CourseElectionController::class, 'store'])->name('elections.store');
        Route::get('/elections/{election}', [CourseElectionController::class, 'show'])->name('elections.show');
        Route::post('/elections/{election}/open', [CourseElectionController::class, 'open'])->name('elections.open');
        Route::post('/elections/{election}/close', [CourseElectionController::class, 'close'])->name('elections.close');
        Route::post('/elections/{election}/options', [OptionController::class, 'store'])->name('options.store');
        Route::patch('/options/{option}', [OptionController::class, 'update'])->name('options.update');
        Route::delete('/options/{option}', [OptionController::class, 'destroy'])->name('options.destroy');
    });
