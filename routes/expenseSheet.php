<?php

use App\Http\Controllers\ExpenseSheetController;
use Illuminate\Support\Facades\Route;

Route::get('/expense-sheet/{id}', [ExpenseSheetController::class, 'show'])->name('expense-sheet.show');
Route::get('/expense-sheet/{id}/create', [ExpenseSheetController::class, 'create'])->name('expense-sheet.create');
Route::post('/expense-sheet/{id}', [ExpenseSheetController::class, 'store'])->name('expense-sheet.create');
Route::get('/expense-sheet/{id}/edit', [ExpenseSheetController::class, 'edit'])->name('expense-sheet.edit');
