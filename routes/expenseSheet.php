<?php

use App\Http\Controllers\ExpenseSheetController;
use Illuminate\Support\Facades\Route;

Route::get('/expense-sheet/{id}', [ExpenseSheetController::class, 'show'])->name('expense-sheet.show')->middleware('auth');
Route::get('/expense-sheet/{id}/create', [ExpenseSheetController::class, 'create'])->name('expense-sheet.create')->middleware('auth');
Route::post('/expense-sheet/{id}', [ExpenseSheetController::class, 'store'])->name('expense-sheet.create')->middleware('auth');
Route::get('/expense-sheet/{id}/edit', [ExpenseSheetController::class, 'edit'])->name('expense-sheet.edit')->middleware('auth');
Route::post('/expense-sheet/{id}/approve', [ExpenseSheetController::class, 'approve'])->name('expense-sheet.approve')->middleware('auth');
Route::get('/expense-sheet/{id}/edit', [ExpenseSheetController::class, 'edit'])->name('expense-sheet.edit')->middleware('auth');
Route::put('/expense-sheet/{id}', [ExpenseSheetController::class, 'update'])->name('expense-sheet.update')->middleware('auth');