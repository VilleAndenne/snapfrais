<?php

namespace App\Listeners;

use Illuminate\Auth\Events\PasswordReset;

class LogPasswordReset
{
    /**
     * Handle the event.
     */
    public function handle(PasswordReset $event): void
    {
        activity('authentication')
            ->causedBy($event->user)
            ->event('password_reset')
            ->withProperties([
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ])
            ->log('Mot de passe réinitialisé');
    }
}
