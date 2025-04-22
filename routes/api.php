<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [\App\Http\Controllers\API\Auth\AuthenticatedSessionController::class, 'store'])
    ->name('api.login');
