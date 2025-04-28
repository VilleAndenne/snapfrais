<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [\App\Http\Controllers\API\Auth\AuthController::class, 'login'])
    ->name('api.login')->middleware('api');
Route::get('/verify', [\App\Http\Controllers\API\Auth\AuthController::class, 'verify'])
    ->name('api.verify')->middleware('auth:sanctum');

Route::get('/forms', [\App\Http\Controllers\API\FormController::class, 'index'])->middleware('auth:sanctum');
Route::get('/expense-sheets', [\App\Http\Controllers\API\ExpenseSheetController::class, 'index'])->middleware('auth:sanctum');
Route::get('/expense-sheets/validate', [\App\Http\Controllers\API\ExpenseSheetController::class, 'validateIndex'])->middleware('auth:sanctum');
