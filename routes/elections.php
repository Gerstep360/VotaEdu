<?php

use App\Http\Controllers\ResultController;
use Illuminate\Support\Facades\Route;

// Results and Official Acta routes (Authenticated)
Route::middleware(['auth', 'role', 'password.changed'])->group(function () {
    Route::get('/elections/{election}/results', [ResultController::class, 'show'])->name('results.show');
    Route::get('/elections/{election}/acta', [ResultController::class, 'acta'])->name('results.acta');
    Route::get('/elections/{election}/acta/pdf', [ResultController::class, 'download'])->name('results.pdf');
});
