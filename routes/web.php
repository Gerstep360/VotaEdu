<?php

use App\Http\Controllers\NetworkAccessController;
use Illuminate\Support\Facades\Route;

// Acceso distribuido y conexión móvil con código QR animado
Route::get('/conectar', [NetworkAccessController::class, 'index'])->name('network.connect');
Route::get('/qr', [NetworkAccessController::class, 'qrRedirect'])->name('network.qr');
Route::get('/qr.svg', [NetworkAccessController::class, 'downloadSvg'])->name('network.qr.svg');

// Redirection from home to login
Route::get('/', function () {
    return redirect()->route('login');
});

// Modular route groups
require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
require __DIR__.'/teacher.php';
require __DIR__.'/student.php';
require __DIR__.'/elections.php';
