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

Route::get('/export', [\App\Http\Controllers\ExpenseSheetController::class, 'exportForm'])->middleware(['auth', 'verified'])->name('export');
Route::get('/export/submit', [\App\Http\Controllers\ExpenseSheetController::class, 'export'])->middleware(['auth', 'verified'])->name('expense-sheets.export');

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
require __DIR__ . '/forms.php';
require __DIR__ . '/expenseSheet.php';
