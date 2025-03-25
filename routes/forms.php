<?php

\Illuminate\Support\Facades\Route::resource('forms', \App\Http\Controllers\FormController::class)->middleware('auth');
