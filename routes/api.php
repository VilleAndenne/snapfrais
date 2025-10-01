<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [\App\Http\Controllers\API\Auth\AuthController::class, 'login'])
    ->name('api.login')->middleware('api');
Route::get('/verify', [\App\Http\Controllers\API\Auth\AuthController::class, 'verify'])
    ->name('api.verify')->middleware('auth:sanctum');

Route::get('/expense-sheets/summary', [\App\Http\Controllers\API\ExpenseSheetController::class, 'summary'])->middleware('auth:sanctum');
Route::get('/expense-sheets/summary/{month}', [\App\Http\Controllers\API\ExpenseSheetController::class, 'monthsSummary'])->middleware('auth:sanctum');
Route::get('/forms', [\App\Http\Controllers\API\FormController::class, 'index'])->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    // Récupère tout ce qu'il faut pour afficher l'écran de création mobile
    Route::get('/forms/{id}/expense-sheet/create', [\App\Http\Controllers\API\ExpenseSheetController::class, 'createPayload']);

    // Crée la note de frais (équivalent de store)
    Route::post('/forms/{id}/expense-sheets', [\App\Http\Controllers\API\ExpenseSheetController::class, 'store']);

    // (Optionnel) Calcul distance Google si tu veux le faire côté serveur
    Route::post('/maps/distance', [\App\Http\Controllers\API\ExpenseSheetController::class, 'computeDistance']);
});
Route::get('/expense-sheets/{id}', [\App\Http\Controllers\API\ExpenseSheetController::class, 'show'])->middleware('auth:sanctum');
