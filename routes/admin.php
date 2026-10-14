<?php

use App\Http\Controllers\Admin\AcademicYearController;
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\ElectionController as AdminElectionController;
use App\Http\Controllers\Admin\OptionController as AdminOptionController;
use App\Http\Controllers\Admin\StudentController as AdminStudentController;
use App\Http\Controllers\Admin\TeacherController as AdminTeacherController;
use Illuminate\Support\Facades\Route;

// Administrator & Director routes (auth + role:admin,director)
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'role:admin,director', 'password.changed'])
    ->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

        // Students management
        Route::get('/students', [AdminStudentController::class, 'index'])->name('students.index');
        Route::get('/students/create', [AdminStudentController::class, 'create'])->name('students.create');
        Route::post('/students', [AdminStudentController::class, 'store'])->name('students.store');
        Route::get('/students/{student}/edit', [AdminStudentController::class, 'edit'])->name('students.edit');
        Route::patch('/students/{student}', [AdminStudentController::class, 'update'])->name('students.update');
        Route::post('/students/{student}/reset-password', [AdminStudentController::class, 'resetPassword'])->name('students.reset-password');
        Route::post('/students/{student}/toggle-status', [AdminStudentController::class, 'toggleStatus'])->name('students.toggle-status');

        // Teachers management
        Route::get('/teachers', [AdminTeacherController::class, 'index'])->name('teachers.index');
        Route::get('/teachers/create', [AdminTeacherController::class, 'create'])->name('teachers.create');
        Route::post('/teachers', [AdminTeacherController::class, 'store'])->name('teachers.store');
        Route::get('/teachers/{teacher}/edit', [AdminTeacherController::class, 'edit'])->name('teachers.edit');
        Route::patch('/teachers/{teacher}', [AdminTeacherController::class, 'update'])->name('teachers.update');

        // Academic Years management
        Route::get('/academic-years', [AcademicYearController::class, 'index'])->name('academic-years.index');
        Route::post('/academic-years', [AcademicYearController::class, 'store'])->name('academic-years.store');
        Route::post('/academic-years/{academicYear}/activate', [AcademicYearController::class, 'activate'])->name('academic-years.activate');

        // Courses management
        Route::get('/courses', [CourseController::class, 'index'])->name('courses.index');
        Route::post('/courses', [CourseController::class, 'store'])->name('courses.store');

        // Audit Logs
        Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');

        // Elections management
        Route::get('/elections', [AdminElectionController::class, 'index'])->name('elections.index');
        Route::get('/elections/create', [AdminElectionController::class, 'create'])->name('elections.create');
        Route::post('/elections', [AdminElectionController::class, 'store'])->name('elections.store');
        Route::get('/elections/{election}', [AdminElectionController::class, 'show'])->name('elections.show');
        Route::get('/elections/{election}/edit', [AdminElectionController::class, 'edit'])->name('elections.edit');
        Route::patch('/elections/{election}', [AdminElectionController::class, 'update'])->name('elections.update');
        Route::delete('/elections/{election}', [AdminElectionController::class, 'destroy'])->name('elections.destroy');
        Route::post('/elections/{election}/open', [AdminElectionController::class, 'open'])->name('elections.open');
        Route::post('/elections/{election}/close', [AdminElectionController::class, 'close'])->name('elections.close');
        Route::post('/elections/{election}/cancel', [AdminElectionController::class, 'cancel'])->name('elections.cancel');
        Route::post('/elections/{election}/duplicate', [AdminElectionController::class, 'duplicate'])->name('elections.duplicate');

        // Options management (within draft elections)
        Route::post('/elections/{election}/options', [AdminOptionController::class, 'store'])->name('options.store');
        Route::patch('/options/{option}', [AdminOptionController::class, 'update'])->name('options.update');
        Route::delete('/options/{option}', [AdminOptionController::class, 'destroy'])->name('options.destroy');
    });
