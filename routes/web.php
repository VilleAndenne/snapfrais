<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard')->middleware('auth');

Route::resource('departments', \App\Http\Controllers\DepartmentController::class)->middleware(['auth', 'verified']);
Route::resource('users', \App\Http\Controllers\UserController::class)->middleware(['auth', 'verified']);

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
require __DIR__ . '/forms.php';
require __DIR__ . '/expenseSheet.php';
