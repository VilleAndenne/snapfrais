<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard')->middleware('auth');

Route::resource('departments', \App\Http\Controllers\DepartmentController::class)->middleware(['auth', 'verified']);
Route::get('/users/import', [\App\Http\Controllers\UserController::class, 'import'])->middleware(['auth', 'verified'])->name('users.import.form');
Route::post('/users/import', [\App\Http\Controllers\UserController::class, 'doImport'])->middleware(['auth', 'verified'])->name('users.import');
Route::resource('users', \App\Http\Controllers\UserController::class)->middleware(['auth', 'verified']);

// Patch Notes routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/patch-notes', [\App\Http\Controllers\PatchNoteController::class, 'index'])->name('patch-notes.index');
    Route::get('/patch-notes/create', [\App\Http\Controllers\PatchNoteController::class, 'create'])->name('patch-notes.create');
    Route::post('/patch-notes', [\App\Http\Controllers\PatchNoteController::class, 'store'])->name('patch-notes.store');
    Route::post('/api/patch-notes/upload-image', [\App\Http\Controllers\PatchNoteController::class, 'uploadImage'])->name('patch-notes.upload-image');
    Route::get('/api/patch-notes/unread', [\App\Http\Controllers\PatchNoteController::class, 'getUnread'])->name('patch-notes.unread');
    Route::post('/api/patch-notes/{id}/mark-as-read', [\App\Http\Controllers\PatchNoteController::class, 'markAsRead'])->name('patch-notes.mark-as-read');
});

// Impersonation routes
Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/impersonate/stop', [\App\Http\Controllers\ImpersonationController::class, 'stop'])->name('impersonate.stop');
    Route::post('/impersonate/{user}', [\App\Http\Controllers\ImpersonationController::class, 'start'])->name('impersonate.start');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
require __DIR__.'/forms.php';
require __DIR__.'/expenseSheet.php';
