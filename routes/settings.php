<?php

use App\Http\Controllers\Settings\NotificationController;
use App\Http\Controllers\Settings\PasskeyController;
use App\Http\Controllers\Settings\PasswordController;
use App\Http\Controllers\Settings\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('auth')->group(function () {
    Route::redirect('settings', '/settings/profile');

    Route::get('settings/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('settings/profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('settings/password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('settings/password', [PasswordController::class, 'update'])->name('password.update');

    Route::get('settings/passkeys', [PasskeyController::class, 'edit'])->name('passkeys.edit');
    Route::get('settings/passkeys/options', [PasskeyController::class, 'generateOptions'])->name('passkeys.options');
    Route::post('settings/passkeys', [PasskeyController::class, 'store'])->name('passkeys.store');
    Route::delete('settings/passkeys/{passkey}', [PasskeyController::class, 'destroy'])->name('passkeys.destroy');

    Route::get('settings/notifications', [NotificationController::class, 'edit'])->name('notifications.edit');
    Route::patch('settings/notifications', [NotificationController::class, 'update'])->name('notifications.update');

    Route::get('settings/appearance', function () {
        return Inertia::render('settings/Appearance');
    })->name('appearance');
});
