<?php

use Illuminate\Support\Facades\Route;

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
