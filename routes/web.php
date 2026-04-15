<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\ImpersonationController;
use App\Http\Controllers\PatchNoteController;
use App\Http\Controllers\StatisticsController;
use App\Http\Controllers\TermsAcceptanceController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('/cgu', function () {
    return Inertia::render('Legal/Terms');
})->name('terms.show');

Route::middleware('auth')->group(function () {
    Route::get('/cgu/accepter', [TermsAcceptanceController::class, 'show'])->name('terms.accept');
    Route::post('/cgu/accepter', [TermsAcceptanceController::class, 'store'])->name('terms.accept.store');
});

Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard')->middleware('auth');

Route::resource('departments', DepartmentController::class)->middleware(['auth', 'verified']);
Route::get('/users/import', [UserController::class, 'import'])->middleware(['auth', 'verified'])->name('users.import.form');
Route::post('/users/import', [UserController::class, 'doImport'])->middleware(['auth', 'verified'])->name('users.import');
Route::post('/users/{user}/send-password-reset', [UserController::class, 'sendPasswordReset'])->middleware(['auth', 'verified'])->name('users.send-password-reset');
Route::resource('users', UserController::class)->middleware(['auth', 'verified']);

// Patch Notes routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/patch-notes', [PatchNoteController::class, 'index'])->name('patch-notes.index');
    Route::get('/patch-notes/create', [PatchNoteController::class, 'create'])->name('patch-notes.create');
    Route::post('/patch-notes', [PatchNoteController::class, 'store'])->name('patch-notes.store');
    Route::post('/api/patch-notes/upload-image', [PatchNoteController::class, 'uploadImage'])->name('patch-notes.upload-image');
    Route::get('/api/patch-notes/unread', [PatchNoteController::class, 'getUnread'])->name('patch-notes.unread');
    Route::post('/api/patch-notes/{id}/mark-as-read', [PatchNoteController::class, 'markAsRead'])->name('patch-notes.mark-as-read');
});

// Activity Log routes (admin only)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/admin/activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
    Route::get('/admin/activity-logs/{activity}', [ActivityLogController::class, 'show'])->name('activity-logs.show');
});

// Statistics routes (admin only)
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/admin/statistics', [StatisticsController::class, 'index'])->name('statistics.index');
});

// Impersonation routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/impersonate/stop', [ImpersonationController::class, 'stop'])->name('impersonate.stop');
    Route::post('/impersonate/{user}', [ImpersonationController::class, 'start'])->name('impersonate.start');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
require __DIR__.'/forms.php';
require __DIR__.'/expenseSheet.php';
