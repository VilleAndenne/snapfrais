<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [\App\Http\Controllers\API\Auth\Authcontroller::class, 'login'])
    ->name('api.login')->middleware('api');
