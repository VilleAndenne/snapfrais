<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Failed;

class LogFailedLogin
{
    /**
     * Handle the event.
     */
    public function handle(Failed $event): void
    {
        activity('authentication')
            ->causedByAnonymous()
            ->event('failed_login')
            ->withProperties([
                'email' => $event->credentials['email'] ?? 'unknown',
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'guard' => $event->guard,
            ])
            ->log('Tentative de connexion échouée');
    }
}
